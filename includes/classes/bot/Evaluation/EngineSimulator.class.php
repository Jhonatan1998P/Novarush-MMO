<?php

/**
 * NovaRush Bot AI v2 - Engine Simulator
 *
 * Runs the authentic NovaRush calculateAttack() combat engine in Monte Carlo simulations.
 * Pure in-memory execution: zero database writes.
 * Level 2 of the Combat Oracle.
 */
class BotEngineSimulator
{
    private static $engineLoaded = false;

    private static function loadEngine()
    {
        if (!self::$engineLoaded) {
            require_once 'includes/classes/missions/functions/calculateAttack.php';
            self::$engineLoaded = true;
        }
    }

    /**
     * Simulate combat over multiple Monte Carlo iterations
     *
     * @param array $attackerFleet [shipId => count]
     * @param array $defenderUnits [shipId/defId => count]
     * @param array $attackerPlayer ['id' => int, 'factor' => array]
     * @param array $defenderPlayer ['id' => int, 'factor' => array]
     * @param int $iterations Number of Monte Carlo iterations (1 to 15)
     * @param float $fleetDebrisPct Percentage of fleet entering debris field (e.g. 0.7)
     * @param float $defDebrisPct Percentage of defense entering debris field (e.g. 0.0)
     * @return array Simulation summary statistics
     */
    public static function simulate(
        array $attackerFleet,
        array $defenderUnits,
        array $attackerPlayer,
        array $defenderPlayer,
        $iterations = 5,
        $fleetDebrisPct = 0.7,
        $defDebrisPct = 0.0
    ) {
        self::loadEngine();

        $defaultFactors = function_exists('getFactors') ? getFactors(array()) : array();
        $requiredKeys = array(
            'Attack' => 0.0, 'AttackA' => 0.0, 'AttackD' => 0.0,
            'Defensive' => 0.0, 'DefensiveA' => 0.0, 'DefensiveD' => 0.0,
            'Shield' => 0.0, 'ShieldA' => 0.0, 'ShieldD' => 0.0,
            'DoubleAttack' => 0.0, 'DoubleShield' => 0.0, 'DoubleDefensive' => 0.0,
            'DoubleAttackBonus' => 0.0, 'DoubleShieldBonus' => 0.0, 'DoubleDefensiveBonus' => 0.0
        );
        $baseFactors = array_merge($requiredKeys, $defaultFactors);

        $attackerPlayer['factor'] = isset($attackerPlayer['factor']) && is_array($attackerPlayer['factor'])
            ? array_merge($baseFactors, $attackerPlayer['factor'])
            : $baseFactors;

        $defenderPlayer['factor'] = isset($defenderPlayer['factor']) && is_array($defenderPlayer['factor'])
            ? array_merge($baseFactors, $defenderPlayer['factor'])
            : $baseFactors;

        if (empty($attackerFleet)) {
            return array('win_rate' => 0.0, 'draw_rate' => 0.0, 'loss_rate' => 1.0, 'avg_att_losses' => 0, 'avg_def_losses' => 0, 'avg_debris' => array(901 => 0, 902 => 0));
        }
        if (empty($defenderUnits)) {
            return array('win_rate' => 1.0, 'draw_rate' => 0.0, 'loss_rate' => 0.0, 'avg_att_losses' => 0, 'avg_def_losses' => 0, 'avg_debris' => array(901 => 0, 902 => 0));
        }

        $wins   = 0;
        $draws  = 0;
        $losses = 0;
        $totalAttLossMetal = 0.0;
        $totalAttLossCry   = 0.0;
        $totalDefLossMetal = 0.0;
        $totalDefLossCry   = 0.0;
        $totalDebrisMetal  = 0.0;
        $totalDebrisCry    = 0.0;

        $iterations = max(1, min(20, (int)$iterations));

        for ($i = 0; $i < $iterations; $i++) {
            // Re-clone structures because calculateAttack mutates them
            $simAttackers = array(
                1 => array(
                    'fleetID' => 1,
                    'unit'    => $attackerFleet,
                    'player'  => $attackerPlayer,
                ),
            );

            $simDefenders = array(
                0 => array(
                    'fleetID' => 0,
                    'unit'    => $defenderUnits,
                    'player'  => $defenderPlayer,
                ),
            );

            $res = calculateAttack($simAttackers, $simDefenders, $fleetDebrisPct * 100, $defDebrisPct * 100);

            if ($res['won'] === 'a') {
                $wins++;
            } elseif ($res['won'] === 'r') {
                $losses++;
            } else {
                $draws++;
            }

            if (isset($res['unitLost'])) {
                $totalAttLossMetal += isset($res['unitLost']['attacker']) ? $res['unitLost']['attacker'] : 0;
                $totalDefLossMetal += isset($res['unitLost']['defender']) ? $res['unitLost']['defender'] : 0;
            }

            if (isset($res['debris'])) {
                $totalDebrisMetal += isset($res['debris']['attacker'][901]) ? $res['debris']['attacker'][901] : 0;
                $totalDebrisMetal += isset($res['debris']['defender'][901]) ? $res['debris']['defender'][901] : 0;
                $totalDebrisCry   += isset($res['debris']['attacker'][902]) ? $res['debris']['attacker'][902] : 0;
                $totalDebrisCry   += isset($res['debris']['defender'][902]) ? $res['debris']['defender'][902] : 0;
            }
        }

        return array(
            'win_rate'       => round($wins / $iterations, 3),
            'draw_rate'      => round($draws / $iterations, 3),
            'loss_rate'      => round($losses / $iterations, 3),
            'avg_att_loss'   => round($totalAttLossMetal / $iterations, 0),
            'avg_def_loss'   => round($totalDefLossMetal / $iterations, 0),
            'avg_debris_met' => round($totalDebrisMetal / $iterations, 0),
            'avg_debris_cry' => round($totalDebrisCry / $iterations, 0),
            'iterations'     => $iterations,
        );
    }
}
