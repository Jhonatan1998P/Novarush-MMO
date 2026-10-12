<?php

/**
 * NovaRush - Rebalance Bot Empires to 50% M / 35% C / 15% D and Synchronize Virtual Budgets
 *
 * Requirements:
 * 1. For each bot in uni1_bots:
 *    - Calculate Total MSE of its empire (Metal + 2*Crystal + 4*Deuterium) across all active colonies.
 *    - Distribute this Total MSE into:
 *      * 50% Metal (en MSE)
 *      * 35% Crystal (en MSE)
 *      * 15% Deuterium (en MSE)
 *    - Divide these quantities equally across each colony/planet of the bot.
 * 2. Update physical resources in uni1_planets for each colony.
 * 3. Update virtual budgets (budget_credits in uni1_bots):
 *    - Local category bags per planet: 'mines', 'fleet', 'defense' based on personality macro ratios.
 *    - Global empire category bag: 'research' based on personality research ratio.
 *    - Synchronize 'last_physical' to match new colony resources exactly (preventing windfall false positives).
 *    - Preserve existing trader cooldowns.
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/classes/bot/Config/Personality.class.php';

$db = Database::get();

$bots = $db->select("SELECT b.bot_id, u.username, b.personality, b.budget_credits FROM %%BOTS%% b JOIN %%USERS%% u ON b.bot_id = u.id ORDER BY b.bot_id ASC;");

if (empty($bots)) {
    echo "No bots found in uni1_bots.\n";
    exit(0);
}

$report = array();

foreach ($bots as $bot) {
    $botId = (int)$bot['bot_id'];
    $username = $bot['username'];
    $personalityName = $bot['personality'] ?? 'balanced';
    $pers = new BotPersonality($personalityName);
    $ratios = $pers->getBudgetRatios();

    $existingCredits = @json_decode($bot['budget_credits'], true);
    if (!is_array($existingCredits)) {
        $existingCredits = array();
    }

    // 1. Fetch all active colonies (planet_type = 1)
    $planets = $db->select("SELECT id, name, galaxy, system, planet, metal, crystal, deuterium FROM %%PLANETS%% WHERE id_owner = :uid AND destruyed = 0 AND planet_type = 1 ORDER BY id ASC;", array(':uid' => $botId));
    $numPlanets = count($planets);

    if ($numPlanets === 0) {
        continue;
    }

    $rawM = 0.0;
    $rawC = 0.0;
    $rawD = 0.0;

    foreach ($planets as $p) {
        $rawM += (float)$p['metal'];
        $rawC += (float)$p['crystal'];
        $rawD += (float)$p['deuterium'];
    }

    // 2. Calculate Total Empire MSE
    $totalEmpireMSE = $rawM + (2.0 * $rawC) + (4.0 * $rawD);

    // 3. Distribute Total MSE into 50% Metal, 35% Crystal, 15% Deuterium
    $targetTotalM = round($totalEmpireMSE * 0.50, 2);
    $targetTotalC = round($totalEmpireMSE * 0.35, 2);
    $targetTotalD = round($totalEmpireMSE * 0.15, 2);

    // 4. Divide equally among colonies
    $perColonyM = round($targetTotalM / $numPlanets, 2);
    $perColonyC = round($targetTotalC / $numPlanets, 2);
    $perColonyD = round($targetTotalD / $numPlanets, 2);

    // Residual adjustment assigned to first colony to ensure exact empire sum
    $remainderM = round($targetTotalM - ($perColonyM * $numPlanets), 2);
    $remainderC = round($targetTotalC - ($perColonyC * $numPlanets), 2);
    $remainderD = round($targetTotalD - ($perColonyD * $numPlanets), 2);

    $newPlanetBudgets = array();
    $newLastPhysical  = array();
    $updatedColonies  = array();

    foreach ($planets as $idx => $p) {
        $pId = (int)$p['id'];

        $colonyM = $perColonyM + (($idx === 0) ? $remainderM : 0.0);
        $colonyC = $perColonyC + (($idx === 0) ? $remainderC : 0.0);
        $colonyD = $perColonyD + (($idx === 0) ? $remainderD : 0.0);

        // Update physical resources in uni1_planets
        $db->update(
            "UPDATE %%PLANETS%% SET metal = :m, crystal = :c, deuterium = :d WHERE id = :id;",
            array(
                ':m'  => $colonyM,
                ':c'  => $colonyC,
                ':d'  => $colonyD,
                ':id' => $pId,
            )
        );

        // Build virtual bags for this colony based on personality macro ratios
        $newPlanetBudgets[$pId] = array(
            'mines' => array(
                'metal'     => round($colonyM * $ratios['mines'], 2),
                'crystal'   => round($colonyC * $ratios['mines'], 2),
                'deuterium' => round($colonyD * $ratios['mines'], 2),
            ),
            'fleet' => array(
                'metal'     => round($colonyM * $ratios['fleet'], 2),
                'crystal'   => round($colonyC * $ratios['fleet'], 2),
                'deuterium' => round($colonyD * $ratios['fleet'], 2),
            ),
            'defense' => array(
                'metal'     => round($colonyM * $ratios['defense'], 2),
                'crystal'   => round($colonyC * $ratios['defense'], 2),
                'deuterium' => round($colonyD * $ratios['defense'], 2),
            ),
        );

        // Baseline last_physical
        $newLastPhysical[$pId] = array(
            'metal'     => $colonyM,
            'crystal'   => $colonyC,
            'deuterium' => $colonyD,
        );

        $updatedColonies[] = array(
            'id'        => $pId,
            'name'      => $p['name'],
            'coords'    => "[{$p['galaxy']}:{$p['system']}:{$p['planet']}]",
            'metal'     => $colonyM,
            'crystal'   => $colonyC,
            'deuterium' => $colonyD,
            'total_res' => $colonyM + $colonyC + $colonyD,
        );
    }

    // Global research budget for the empire
    $newResearchBudget = array(
        'metal'     => round($targetTotalM * $ratios['research'], 2),
        'crystal'   => round($targetTotalC * $ratios['research'], 2),
        'deuterium' => round($targetTotalD * $ratios['research'], 2),
    );

    // Build complete budget_credits array
    $budgetCreditsData = array(
        'planets'          => $newPlanetBudgets,
        'research'         => $newResearchBudget,
        'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        'last_physical'    => $newLastPhysical,
        'last_updated'     => time(),
    );

    // Preserve trader cooldowns if present
    if (isset($existingCredits['trader_empire_cooldown'])) {
        $budgetCreditsData['trader_empire_cooldown'] = $existingCredits['trader_empire_cooldown'];
    }
    if (isset($existingCredits['trader_planet_cooldowns'])) {
        $budgetCreditsData['trader_planet_cooldowns'] = $existingCredits['trader_planet_cooldowns'];
    }
    if (isset($existingCredits['trader_cooldowns'])) {
        $budgetCreditsData['trader_cooldowns'] = $existingCredits['trader_cooldowns'];
    }

    $db->update(
        "UPDATE %%BOTS%% SET budget_credits = :credits WHERE bot_id = :uid;",
        array(
            ':credits' => json_encode($budgetCreditsData),
            ':uid'     => $botId,
        )
    );

    $report[] = array(
        'bot_id'           => $botId,
        'username'         => $username,
        'personality'      => $personalityName,
        'ratios'           => $ratios,
        'num_planets'      => $numPlanets,
        'initial_raw'      => array(
            'metal'     => $rawM,
            'crystal'   => $rawC,
            'deuterium' => $rawD,
        ),
        'empire_mse'       => $totalEmpireMSE,
        'target_totals'    => array(
            'metal'     => $targetTotalM,
            'crystal'   => $targetTotalC,
            'deuterium' => $targetTotalD,
        ),
        'per_colony'       => array(
            'metal'     => $perColonyM,
            'crystal'   => $perColonyC,
            'deuterium' => $perColonyD,
        ),
        'colonies'         => $updatedColonies,
        'virtual_research' => $newResearchBudget,
    );
}

echo json_encode($report, JSON_PRETTY_PRINT);
