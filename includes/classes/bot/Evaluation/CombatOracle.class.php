<?php

require_once 'includes/classes/bot/Evaluation/SurrogateCombatModel.class.php';
require_once 'includes/classes/bot/Evaluation/EngineSimulator.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/vars/General.php';

/**
 * NovaRush Bot AI v2 - Combat Oracle
 *
 * Two-level combat evaluation:
 * - Level 1: SurrogateCombatModel for high-throughput candidate screening.
 * - Level 2: EngineSimulator for high-fidelity Monte Carlo simulation.
 */
class BotCombatOracle
{
    /**
     * Resolve defender player factors using either DB user record or spied technologies.
     *
     * @param int $targetUserId
     * @param array $intel
     * @return array Factors map with Attack, Shield, Defensive, etc.
     */
    public static function resolveDefenderFactors($targetUserId, array $intel = array())
    {
        $factors = array();
        if ($targetUserId > 0) {
            $db = Database::get();
            $targetUser = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :userId LIMIT 1;", array(
                ':userId' => (int)$targetUserId
            ));
            if (!empty($targetUser) && function_exists('getFactors')) {
                $factors = getFactors($targetUser, 'attack');
            }
        }

        // Fallback or fill from spied intelligence if user query is not available or factor is empty
        if (empty($factors) && !empty($intel['techs']) && is_array($intel['techs'])) {
            $techs = $intel['techs'];
            $attTech = (float)(isset($techs[109]) ? $techs[109] : (isset($techs['109']) ? $techs['109'] : 0));
            $shiTech = (float)(isset($techs[110]) ? $techs[110] : (isset($techs['110']) ? $techs['110'] : 0));
            $defTech = (float)(isset($techs[111]) ? $techs[111] : (isset($techs['111']) ? $techs['111'] : 0));

            $factors = array(
                'Attack'    => $attTech * 0.10,
                'Shield'    => $shiTech * 0.10,
                'Defensive' => $defTech * 0.10,
            );
        }

        return $factors;
    }

    /**
     * Evaluate combat between attacker fleet and defender forces
     *
     * @param array $attackerFleet [shipId => count]
     * @param array $defenderUnits [shipId/defId => count]
     * @param array $attackerPlayer ['id' => int, 'factor' => array]
     * @param array $defenderPlayer ['id' => int, 'factor' => array]
     * @param int $mcIterations Number of Monte Carlo iterations (0 to use surrogate only)
     * @return array Detailed outcome evaluation
     */
    public static function evaluate(
        array $attackerFleet,
        array $defenderUnits,
        array $attackerPlayer,
        array $defenderPlayer,
        $mcIterations = 5
    ) {
        $attFactors = isset($attackerPlayer['factor']) && is_array($attackerPlayer['factor']) ? $attackerPlayer['factor'] : array();
        $defFactors = isset($defenderPlayer['factor']) && is_array($defenderPlayer['factor']) ? $defenderPlayer['factor'] : array();

        // Auto-resolve attacker factors if missing but attacker ID is present
        if (empty($attFactors) && !empty($attackerPlayer['id'])) {
            $attFactors = self::resolveDefenderFactors((int)$attackerPlayer['id']);
            $attackerPlayer['factor'] = $attFactors;
        }

        // Auto-resolve defender factors if missing but defender ID is present
        if (empty($defFactors) && !empty($defenderPlayer['id'])) {
            $defFactors = self::resolveDefenderFactors((int)$defenderPlayer['id']);
            $defenderPlayer['factor'] = $defFactors;
        }

        // 1. Fast Surrogate Evaluation
        $surrogate = BotSurrogateCombatModel::estimate($attackerFleet, $defenderUnits, $attFactors, $defFactors);

        $attackerFleetValue = 0.0;
        foreach ($attackerFleet as $sId => $cnt) {
            if ($cnt > 0) {
                $attackerFleetValue += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
            }
        }
        $defenderUnitsValue = 0.0;
        foreach ($defenderUnits as $uId => $cnt) {
            if ($cnt > 0) {
                $defenderUnitsValue += BotEconomyValuator::getElementPriceMSE($uId, $cnt);
            }
        }

        // If no Monte Carlo iterations requested ($mcIterations < 1) or if combat is hopeless suicide (<1% win prob in surrogate and >1 iterations)
        if ($mcIterations < 1 || ($mcIterations > 1 && $surrogate['win_prob'] < 0.01)) {
            $attLossMse = round($attackerFleetValue * (float)$surrogate['att_loss_pct'], 2);
            $defLossMse = round($defenderUnitsValue * (float)$surrogate['def_loss_pct'], 2);
            return array(
                'win_probability'    => $surrogate['win_prob'],
                'loss_probability'   => 1.0 - $surrogate['win_prob'],
                'draw_probability'   => 0.0,
                'initial_att_mse'    => round($attackerFleetValue, 2),
                'initial_def_mse'    => round($defenderUnitsValue, 2),
                'att_loss_pct'       => $surrogate['att_loss_pct'],
                'def_loss_pct'       => $surrogate['def_loss_pct'],
                'att_loss_mse'       => $attLossMse,
                'def_loss_mse'       => $defLossMse,
                'lost_units_att'     => array(),
                'lost_units_def'     => array(),
                'debris_metal'       => 0,
                'debris_crystal'     => 0,
                'debris_mse'         => 0.0,
                'net_ev_mse'         => 0.0 - $attLossMse,
                'is_profitable'      => false,
                'eval_method'        => 'surrogate',
                'ratio'              => isset($surrogate['ratio']) ? $surrogate['ratio'] : 1.0,
            );
        }

        // 2. High-Fidelity Monte Carlo Simulation (Always run when mcIterations >= 1 to verify real outcome)
        $mc = BotEngineSimulator::simulate(
            $attackerFleet,
            $defenderUnits,
            $attackerPlayer,
            $defenderPlayer,
            $mcIterations
        );

        $attLossPct = $mc['att_loss_pct'];
        $defLossPct = $mc['def_loss_pct'];

        // Compute economic profitability (EV in MSE) for attacker
        $debrisMse = ((float)$mc['avg_debris_met'] * 1.0) + ((float)$mc['avg_debris_cry'] * 1.5);
        $expectedGainMse = $debrisMse * 0.70; // 70% typical debris recovery
        $netEvMse = $expectedGainMse - (float)$mc['avg_att_loss_mse'];
        $isProfitable = ($netEvMse > 0.0);

        return array(
            'win_probability'    => $mc['win_rate'],
            'loss_probability'   => $mc['loss_rate'],
            'draw_probability'   => $mc['draw_rate'],
            'initial_att_mse'    => $mc['initial_att_mse'],
            'initial_def_mse'    => $mc['initial_def_mse'],
            'att_loss_pct'       => round($attLossPct, 4),
            'def_loss_pct'       => round($defLossPct, 4),
            'att_loss_mse'       => $mc['avg_att_loss_mse'],
            'def_loss_mse'       => $mc['avg_def_loss_mse'],
            'lost_units_att'     => $mc['avg_att_lost_units'],
            'lost_units_def'     => $mc['avg_def_lost_units'],
            'debris_metal'       => $mc['avg_debris_met'],
            'debris_crystal'     => $mc['avg_debris_cry'],
            'debris_mse'         => round($debrisMse, 2),
            'net_ev_mse'         => round($netEvMse, 2),
            'is_profitable'      => $isProfitable,
            'eval_method'        => 'monte_carlo',
            'iterations'         => $mc['iterations'],
            'ratio'              => isset($surrogate['ratio']) ? $surrogate['ratio'] : 1.0,
        );
    }

    /**
     * Comprehensive Financial Balance (NAV / ROI / Delta MSE) Evaluator
     *
     * Evaluates battle outcome in real economic terms (MSE):
     * Profit = (Loot + Realizable Debris) - (Own Losses + Deuterium Fuel)
     *
     * @param array $simResult Result from evaluate()
     * @param float $deployedFleetMSE Total MSE of attacker/defender mobile fleet
     * @param float $fuelMSE Fuel cost of fleet and recyclers in MSE
     * @param float $availableRecCap Total capacity of available recyclers (MSE)
     * @param float $lootMSE Projected loot from ground storage
     * @param float|null $customDebrisRate Optional universe debris rate
     * @return array
     */
    public static function evaluateFinancialBalance(
        array $simResult,
        $deployedFleetMSE,
        $fuelMSE = 0.0,
        $availableRecCap = 0.0,
        $lootMSE = 0.0,
        $customDebrisRate = null
    ) {
        $cfg = class_exists('Config') ? Config::get() : null;
        $fCdr = ($customDebrisRate !== null)
            ? (float)$customDebrisRate
            : ($cfg && isset($cfg->Fleet_Cdr) ? ((float)$cfg->Fleet_Cdr / 100.0) : 0.70);

        // Losses
        $attLossPct = isset($simResult['att_loss_pct']) ? (float)$simResult['att_loss_pct'] : 0.0;
        $ownLossMSE = isset($simResult['att_loss_mse']) && (float)$simResult['att_loss_mse'] > 0
            ? (float)$simResult['att_loss_mse']
            : ((float)$deployedFleetMSE * $attLossPct);

        $defLossMSE = isset($simResult['def_loss_mse']) ? (float)$simResult['def_loss_mse'] : 0.0;

        // Total Debris
        $debrisMse = isset($simResult['debris_mse']) && (float)$simResult['debris_mse'] > 0
            ? (float)$simResult['debris_mse']
            : ((float)($simResult['debris_metal'] ?? 0) * 1.0 + (float)($simResult['debris_crystal'] ?? 0) * 1.5);

        if ($debrisMse <= 0.0 && ($ownLossMSE > 0 || $defLossMSE > 0)) {
            $debrisMse = ($ownLossMSE + $defLossMSE) * $fCdr;
        }

        // Recycler Coverage
        $availableRecCap = max(0.0, (float)$availableRecCap);
        $recyclerCoverage = ($debrisMse > 0.0) ? min(1.0, $availableRecCap / $debrisMse) : 1.0;
        $realizableDebris = ($availableRecCap > 0.0) ? min($debrisMse, $availableRecCap) : 0.0;

        // Revenues & Costs
        $winProb = isset($simResult['win_probability']) ? (float)$simResult['win_probability'] : 0.0;

        // In NovaRush/2Moons combat engine, loot is strictly conditional on attacker victory.
        // A draw or defeat yields 0 loot. Thus, realizable loot must be weighted by win probability.
        // This prevents suicidal bait runs into lethal defenses where bots expect guaranteed loot.
        $realizableLoot  = max(0.0, (float)$lootMSE) * $winProb;
        $grossRevenue    = $realizableLoot + $realizableDebris;
        $actualCosts     = (float)$ownLossMSE + (float)$fuelMSE;
        $netProfitMSE    = $grossRevenue - $actualCosts;
        $costDenominator = max(1.0, $actualCosts);
        $roi             = $netProfitMSE / $costDenominator;

        $isMutualSacrifice = ($attLossPct >= 0.50 && $netProfitMSE > 0.0);

        return array(
            'net_profit_mse'       => round($netProfitMSE, 2),
            'roi'                  => round($roi, 4),
            'realizable_loot'      => round($realizableLoot, 2),
            'gross_revenue'        => round($grossRevenue, 2),
            'recycler_coverage'    => round($recyclerCoverage, 4),
            'realizable_debris'    => round($realizableDebris, 2),
            'total_debris_mse'     => round($debrisMse, 2),
            'own_losses_mse'       => round($ownLossMSE, 2),
            'def_losses_mse'       => round($defLossMSE, 2),
            'total_costs_mse'      => round($actualCosts, 2),
            'win_probability'      => $winProb,
            'att_loss_pct'         => round($attLossPct, 4),
            'is_net_profitable'    => ($netProfitMSE > 0.0),
            'is_mutual_sacrifice'  => $isMutualSacrifice,
        );
    }

    /**
     * Virtual simulation of NovaRush MIP attack using the exact engine algorithm from calculateMIPAttack.php.
     *
     * @param array $targetDefense Defenses map [unitId => count]
     * @param int $mipsCount Available interplanetary missiles to fire
     * @param int $abmCount Defender interceptor missiles (ABM #502)
     * @param int $attackerMilitaryTech Attacker military technology level
     * @param int $defenderShieldTech Defender shield/armor technology level
     * @param int $primaryTarget Chosen primary target unit ID (e.g. 406 Plasma, 404 Gauss)
     * @return array ['remaining' => array, 'destroyed' => array, 'effective_mips' => int]
     */
    public static function simulateMIPBreach(
        array $targetDefense,
        $mipsCount,
        $abmCount,
        $attackerMilitaryTech = 0,
        $defenderShieldTech = 0,
        $primaryTarget = 0
    ) {
        global $pricelist, $CombatCaps;

        $mipsCount = (int)$mipsCount;
        $abmCount  = (int)$abmCount;
        $countMissiles = max(0, $mipsCount - $abmCount);

        $destroyed = array();
        $remaining = $targetDefense;
        unset($remaining[502]); // Exclude ABMs from ground defense

        if ($countMissiles <= 0 || empty($remaining)) {
            return array(
                'remaining'      => $remaining,
                'destroyed'      => $destroyed,
                'effective_mips' => 0,
            );
        }

        $baseAttack = isset($CombatCaps[503]['attack']) ? (float)$CombatCaps[503]['attack'] : 12000.0;
        $totalAttack = $countMissiles * $baseAttack * (1.0 + 0.1 * (int)$attackerMilitaryTech);

        // Put primary target first if specified and present
        if ($primaryTarget > 0 && isset($remaining[$primaryTarget])) {
            $firstData = array($primaryTarget => $remaining[$primaryTarget]);
            unset($remaining[$primaryTarget]);
            $remaining = $firstData + $remaining;
        }

        foreach ($remaining as $element => $count) {
            if ($count <= 0) continue;
            $costM = isset($pricelist[$element]['cost'][901]) ? (float)$pricelist[$element]['cost'][901] : 0.0;
            $costC = isset($pricelist[$element]['cost'][902]) ? (float)$pricelist[$element]['cost'][902] : 0.0;
            $elementStructurePoints = ($costM + $costC) * (1.0 + 0.1 * (int)$defenderShieldTech) / 10.0;
            if ($elementStructurePoints <= 0.0) continue;

            $destroyCount = (int)min($count, floor($totalAttack / $elementStructurePoints));
            if ($destroyCount > 0) {
                $destroyed[$element] = $destroyCount;
                $remaining[$element] -= $destroyCount;
                $totalAttack -= ($destroyCount * $elementStructurePoints);
            }

            if ($totalAttack <= 0) {
                break;
            }
        }

        $remaining = array_filter($remaining);

        return array(
            'remaining'      => $remaining,
            'destroyed'      => $destroyed,
            'effective_mips' => $countMissiles,
        );
    }

    /**
     * Selects surgical primary target for MIP attack based on attacker fleet doctrine in NovaRush.
     *
     * @param array $targetDefense
     * @param array $attackerFleet
     * @return int Primary target element ID (e.g. 406, 404, 403, 401)
     */
    public static function selectSurgicalPrimaryTarget(array $targetDefense, array $attackerFleet = array())
    {
        // Calculate attacker fleet composition: heavy capital vs light/swarm
        $heavyCapitalCount = 0;
        $lightSwarmCount   = 0;

        foreach ($attackerFleet as $sId => $cnt) {
            if ($cnt <= 0) continue;
            // Heavy capital: 207 Battleship, 213 Destructor, 214 Deathstar, 215 Battlecruiser, 216, 224, 225 Galleon
            if (in_array($sId, array(207, 213, 214, 215, 216, 224, 225, 226))) {
                $heavyCapitalCount += $cnt;
            }
            // Light / Cruiser: 202/203/217 Cargo, 204 Light Fighter, 205 Heavy Fighter, 206 Cruiser
            if (in_array($sId, array(202, 203, 204, 205, 206, 217))) {
                $lightSwarmCount += $cnt;
            }
        }

        $hasPlasma = !empty($targetDefense[406]);
        $hasGauss  = !empty($targetDefense[404]);
        $hasHeavyL = !empty($targetDefense[403]);
        $hasRocket = !empty($targetDefense[401]) && (int)$targetDefense[401] >= 1000;

        // Doctrine 1: Capital fleet -> neutralize armor-piercing lethal weapons
        if ($heavyCapitalCount >= 20 || $heavyCapitalCount >= $lightSwarmCount) {
            if ($hasPlasma) return 406; // Plasma Turret
            if ($hasGauss)  return 404; // Gauss Cannon
            if ($hasHeavyL) return 403; // Heavy Laser
        }

        // Doctrine 2: Swarm / Cruisers -> neutralize shield domes and heavy lasers
        if ($lightSwarmCount > $heavyCapitalCount) {
            if ($hasHeavyL) return 403; // Heavy Laser
            if (!empty($targetDefense[408])) return 408; // Large Shield Dome
            if ($hasPlasma) return 406;
            if ($hasGauss)  return 404;
        }

        // Doctrine 3: Screening fodder clear if defender has massive rocket wall
        if ($hasRocket) {
            return 401; // Rocket Launcher
        }

        // Fallback: highest tech unit present
        foreach (array(406, 404, 405, 403, 402, 401) as $candidate) {
            if (!empty($targetDefense[$candidate])) {
                return $candidate;
            }
        }

        return 401;
    }
}


