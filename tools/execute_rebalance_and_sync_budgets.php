<?php

/**
 * NovaRush - Rebalance Bot Empires & Synchronize Virtual Budgets
 *
 * 1. Ajusta y reduce el deuterio colosal de los bots con tope en 10.000M (10.000.000.000).
 * 2. Calcula el Total MSE del Imperio de cada bot (Metal + 2*Cristal + 4*Deuterio).
 * 3. Divide la cantidad total de MSE en:
 *    - 50% Metal
 *    - 35% Cristal
 *    - 15% Deuterio
 *    (siguiendo el modelo exacto del usuario: MSE 1M = 500K M, 350K C, 150K D).
 * 4. Distribuye equitativamente esas cantidades en todas las colonias del bot en uni1_planets.
 * 5. Actualiza las bolsas virtuales (budget_credits) en uni1_bots según la personalidad
 *    del bot (raider, miner, turtle, balanced) y sincroniza last_physical.
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/classes/bot/Config/Personality.class.php';

$db = Database::get();

$CAP_DEUTERIUM = 10000000000.0; // 10.000M = 10,000,000,000

$bots = $db->select("SELECT b.bot_id, u.username, b.personality, b.budget_credits FROM %%BOTS%% b JOIN %%USERS%% u ON b.bot_id = u.id ORDER BY b.bot_id ASC;");

$results = array();

foreach ($bots as $bot) {
    $botId = (int)$bot['bot_id'];
    $username = $bot['username'];
    $pers = new BotPersonality($bot['personality'] ?? 'balanced');
    $ratios = $pers->getBudgetRatios();

    // 1. Obtener todos los planetas del imperio
    $planets = $db->select("SELECT id, name, metal, crystal, deuterium FROM %%PLANETS%% WHERE id_owner = :uid ORDER BY id ASC;", array(':uid' => $botId));
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

    // 2. Reducir deuterio desmedido a 10.000M
    $adjustedD = min($rawD, $CAP_DEUTERIUM);

    // 3. Contar el Total en MSE del imperio
    $empireMSE = $rawM + (2.0 * $rawC) + (4.0 * $adjustedD);

    // 4. Distribuir el MSE según fórmula del usuario (50% M, 35% C, 15% D)
    $targetTotalM = $empireMSE * 0.50;
    $targetTotalC = $empireMSE * 0.35;
    $targetTotalD = $empireMSE * 0.15;

    // 5. Repartir equitativamente entre las colonias
    $perColonyM = round($targetTotalM / $numPlanets, 2);
    $perColonyC = round($targetTotalC / $numPlanets, 2);
    $perColonyD = round($targetTotalD / $numPlanets, 2);

    // 6. Actualizar cada planeta físicamente en uni1_planets
    $newPlanetBudgets = array();
    $newLastPhysical  = array();

    foreach ($planets as $p) {
        $pId = (int)$p['id'];

        $db->update(
            "UPDATE %%PLANETS%% SET metal = :m, crystal = :c, deuterium = :d WHERE id = :id;",
            array(
                ':m'  => $perColonyM,
                ':c'  => $perColonyC,
                ':d'  => $perColonyD,
                ':id' => $pId
            )
        );

        // Bolsas virtuales locales por colonia
        $newPlanetBudgets[$pId] = array(
            'mines' => array(
                'metal'     => round($perColonyM * $ratios['mines'], 2),
                'crystal'   => round($perColonyC * $ratios['mines'], 2),
                'deuterium' => round($perColonyD * $ratios['mines'], 2)
            ),
            'fleet' => array(
                'metal'     => round($perColonyM * $ratios['fleet'], 2),
                'crystal'   => round($perColonyC * $ratios['fleet'], 2),
                'deuterium' => round($perColonyD * $ratios['fleet'], 2)
            ),
            'defense' => array(
                'metal'     => round($perColonyM * $ratios['defense'], 2),
                'crystal'   => round($perColonyC * $ratios['defense'], 2),
                'deuterium' => round($perColonyD * $ratios['defense'], 2)
            )
        );

        // Sincronizar last_physical para que el daemon no lo tome como ingreso anómalo
        $newLastPhysical[$pId] = array(
            'metal'     => $perColonyM,
            'crystal'   => $perColonyC,
            'deuterium' => $perColonyD
        );
    }

    // Bolsa virtual global de investigación para el imperio
    $newResearchBudget = array(
        'metal'     => round($targetTotalM * $ratios['research'], 2),
        'crystal'   => round($targetTotalC * $ratios['research'], 2),
        'deuterium' => round($targetTotalD * $ratios['research'], 2)
    );

    // Estructura completa de budget_credits
    $budgetCreditsData = array(
        'planets'          => $newPlanetBudgets,
        'research'         => $newResearchBudget,
        'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        'last_physical'    => $newLastPhysical
    );

    $db->update(
        "UPDATE %%BOTS%% SET budget_credits = :credits WHERE bot_id = :uid;",
        array(
            ':credits' => json_encode($budgetCreditsData),
            ':uid'     => $botId
        )
    );

    $results[] = array(
        'bot_id'           => $botId,
        'username'         => $username,
        'personality'      => $pers->name,
        'ratios'           => $ratios,
        'num_planets'      => $numPlanets,
        'initial_raw_deut' => $rawD,
        'adjusted_deut'    => $adjustedD,
        'empire_mse'       => $empireMSE,
        'total_metal'      => $targetTotalM,
        'total_crystal'    => $targetTotalC,
        'total_deuterium'  => $targetTotalD,
        'per_colony_m'     => $perColonyM,
        'per_colony_c'     => $perColonyC,
        'per_colony_d'     => $perColonyD
    );
}

echo json_encode($results, JSON_PRETTY_PRINT);
