<?php

require_once 'includes/classes/bot/Evaluation/SurrogateCombatModel.class.php';
require_once 'includes/classes/bot/Evaluation/EngineSimulator.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

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
        $attFactors = isset($attackerPlayer['factor']) ? $attackerPlayer['factor'] : array();
        $defFactors = isset($defenderPlayer['factor']) ? $defenderPlayer['factor'] : array();

        // 1. Fast Surrogate Evaluation
        $surrogate = BotSurrogateCombatModel::estimate($attackerFleet, $defenderUnits, $attFactors, $defFactors);

        // If no Monte Carlo iterations requested or outcome is extreme (>99% win or <1% win), return surrogate
        if ($mcIterations <= 1 || $surrogate['win_prob'] > 0.98 || $surrogate['win_prob'] < 0.02) {
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

        // 2. High-Fidelity Monte Carlo Simulation
        $mc = BotEngineSimulator::simulate(
            $attackerFleet,
            $defenderUnits,
            $attackerPlayer,
            $defenderPlayer,
            $mcIterations
        );

        return array(
            'win_probability'    => $mc['win_rate'],
            'loss_probability'   => $mc['loss_rate'],
            'draw_probability'   => $mc['draw_rate'],
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
