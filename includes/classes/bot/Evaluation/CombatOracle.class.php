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

        // If no Monte Carlo iterations requested or if combat is hopeless suicide (<1% win prob in surrogate)
        if ($mcIterations <= 1 || $surrogate['win_prob'] < 0.01) {
            return array(
                'win_probability'    => $surrogate['win_prob'],
                'loss_probability'   => 1.0 - $surrogate['win_prob'],
                'draw_probability'   => 0.0,
                'att_loss_pct'       => $surrogate['att_loss_pct'],
                'def_loss_pct'       => $surrogate['def_loss_pct'],
                'eval_method'        => 'surrogate',
                'ratio'              => isset($surrogate['ratio']) ? $surrogate['ratio'] : 1.0,
            );
        }

        // 2. High-Fidelity Monte Carlo Simulation (Always run when mcIterations >= 2 to verify real outcome)
        $mc = BotEngineSimulator::simulate(
            $attackerFleet,
            $defenderUnits,
            $attackerPlayer,
            $defenderPlayer,
            $mcIterations
        );

        $attackerFleetValue = 0.0;
        foreach ($attackerFleet as $sId => $cnt) {
            if ($cnt > 0) {
                $attackerFleetValue += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
            }
        }
        $attLossPct = $attackerFleetValue > 0 ? min(1.0, max(0.0, (float)$mc['avg_att_loss'] / $attackerFleetValue)) : 0.0;

        $defenderUnitsValue = 0.0;
        foreach ($defenderUnits as $uId => $cnt) {
            if ($cnt > 0) {
                $defenderUnitsValue += BotEconomyValuator::getElementPriceMSE($uId, $cnt);
            }
        }
        $defLossPct = $defenderUnitsValue > 0 ? min(1.0, max(0.0, (float)$mc['avg_def_loss'] / $defenderUnitsValue)) : 0.0;

        return array(
            'win_probability'    => $mc['win_rate'],
            'loss_probability'   => $mc['loss_rate'],
            'draw_probability'   => $mc['draw_rate'],
            'att_loss_pct'       => round($attLossPct, 4),
            'def_loss_pct'       => round($defLossPct, 4),
            'att_loss_mse'       => $mc['avg_att_loss'],
            'def_loss_mse'       => $mc['avg_def_loss'],
            'debris_metal'       => $mc['avg_debris_met'],
            'debris_crystal'     => $mc['avg_debris_cry'],
            'eval_method'        => 'monte_carlo',
            'iterations'         => $mc['iterations'],
            'ratio'              => isset($surrogate['ratio']) ? $surrogate['ratio'] : 1.0,
        );
    }
}
