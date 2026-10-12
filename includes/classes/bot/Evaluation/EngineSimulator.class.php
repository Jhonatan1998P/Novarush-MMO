<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

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

        // Compute initial forces in MSE
        $initialAttackerMSE = 0.0;
        foreach ($attackerFleet as $sId => $cnt) {
            if ($cnt > 0) {
                $initialAttackerMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
            }
        }

        $initialDefenderMSE = 0.0;
        foreach ($defenderUnits as $uId => $cnt) {
            if ($cnt > 0) {
                $initialDefenderMSE += BotEconomyValuator::getElementPriceMSE($uId, $cnt);
            }
        }

        if (empty($attackerFleet) || $initialAttackerMSE <= 0.0) {
            return array(
                'win_rate'           => 0.0,
                'draw_rate'          => 0.0,
                'loss_rate'          => 1.0,
                'initial_att_mse'    => 0.0,
                'initial_def_mse'    => round($initialDefenderMSE, 2),
                'avg_att_loss_mse'   => 0.0,
                'avg_def_loss_mse'   => 0.0,
                'att_loss_pct'       => 0.0,
                'def_loss_pct'       => 0.0,
                'avg_att_lost_units' => array(),
                'avg_def_lost_units' => array(),
                'avg_att_loss'       => 0.0,
                'avg_def_loss'       => 0.0,
                'avg_debris_met'     => 0,
                'avg_debris_cry'     => 0,
                'iterations'         => $iterations,
            );
        }
        if (empty($defenderUnits) || $initialDefenderMSE <= 0.0) {
            return array(
                'win_rate'           => 1.0,
                'draw_rate'          => 0.0,
                'loss_rate'          => 0.0,
                'initial_att_mse'    => round($initialAttackerMSE, 2),
                'initial_def_mse'    => 0.0,
                'avg_att_loss_mse'   => 0.0,
                'avg_def_loss_mse'   => 0.0,
                'att_loss_pct'       => 0.0,
                'def_loss_pct'       => 0.0,
                'avg_att_lost_units' => array(),
                'avg_def_lost_units' => array(),
                'avg_att_loss'       => 0.0,
                'avg_def_loss'       => 0.0,
                'avg_debris_met'     => 0,
                'avg_debris_cry'     => 0,
                'iterations'         => $iterations,
            );
        }

        $wins   = 0;
        $draws  = 0;
        $losses = 0;
        $totalAttLossMSE  = 0.0;
        $totalDefLossMSE  = 0.0;
        $totalDebrisMetal = 0.0;
        $totalDebrisCry   = 0.0;

        $totalAttLostUnits = array();
        $totalDefLostUnits = array();
        foreach ($attackerFleet as $sId => $cnt) {
            $totalAttLostUnits[$sId] = 0;
        }
        foreach ($defenderUnits as $uId => $cnt) {
            $totalDefLostUnits[$uId] = 0;
        }

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

            $won = isset($res['won']) ? $res['won'] : 'w';
            if ($won === 'a') {
                $wins++;
            } elseif ($won === 'r') {
                $losses++;
            } else {
                $draws++;
            }

            // 1. Exact destroyed attacker units & MSE loss
            $iterAttLossMSE = 0.0;
            if ($won === 'r') {
                // Defender won: 100% of attacker fleet wiped out in combat
                foreach ($attackerFleet as $sId => $initCnt) {
                    $totalAttLostUnits[$sId] += $initCnt;
                    $iterAttLossMSE += BotEconomyValuator::getElementPriceMSE($sId, $initCnt);
                }
            } else {
                $survivingAtt = isset($simAttackers[1]['unit']) ? $simAttackers[1]['unit'] : array();
                foreach ($attackerFleet as $sId => $initCnt) {
                    $survCnt = isset($survivingAtt[$sId]) ? max(0, min($initCnt, (int)$survivingAtt[$sId])) : 0;
                    $lostCnt = max(0, $initCnt - $survCnt);
                    $totalAttLostUnits[$sId] += $lostCnt;
                    if ($lostCnt > 0) {
                        $iterAttLossMSE += BotEconomyValuator::getElementPriceMSE($sId, $lostCnt);
                    }
                }
            }
            $totalAttLossMSE += $iterAttLossMSE;

            // 2. Exact destroyed defender units & MSE loss
            $iterDefLossMSE = 0.0;
            if ($won === 'a') {
                // Attacker won: 100% of defender forces wiped out in combat
                foreach ($defenderUnits as $uId => $initCnt) {
                    $totalDefLostUnits[$uId] += $initCnt;
                    $iterDefLossMSE += BotEconomyValuator::getElementPriceMSE($uId, $initCnt);
                }
            } else {
                // Last round pre-rebuild state
                $lastRound = (!empty($res['rw']) && is_array($res['rw'])) ? end($res['rw']) : array();
                $endDefUnits = isset($lastRound['defenders'][0]['unit']) ? $lastRound['defenders'][0]['unit'] : array();

                foreach ($defenderUnits as $uId => $initCnt) {
                    if ($uId < 300) {
                        // Ships: read surviving count directly
                        $survCnt = isset($simDefenders[0]['unit'][$uId]) ? max(0, min($initCnt, (int)$simDefenders[0]['unit'][$uId])) : 0;
                    } else {
                        // Defenses: read surviving count before giveback from final round
                        $survCnt = isset($endDefUnits[$uId]) ? max(0, min($initCnt, (int)$endDefUnits[$uId])) : 0;
                    }
                    $lostCnt = max(0, $initCnt - $survCnt);
                    $totalDefLostUnits[$uId] += $lostCnt;
                    if ($lostCnt > 0) {
                        $iterDefLossMSE += BotEconomyValuator::getElementPriceMSE($uId, $lostCnt);
                    }
                }
            }
            $totalDefLossMSE += $iterDefLossMSE;

            // Debris fields generated
            if (isset($res['debris'])) {
                $totalDebrisMetal += isset($res['debris']['attacker'][901]) ? (float)$res['debris']['attacker'][901] : 0.0;
                $totalDebrisMetal += isset($res['debris']['defender'][901]) ? (float)$res['debris']['defender'][901] : 0.0;
                $totalDebrisCry   += isset($res['debris']['attacker'][902]) ? (float)$res['debris']['attacker'][902] : 0.0;
                $totalDebrisCry   += isset($res['debris']['defender'][902]) ? (float)$res['debris']['defender'][902] : 0.0;
            }
        }

        $avgAttLossMSE = round($totalAttLossMSE / $iterations, 2);
        $avgDefLossMSE = round($totalDefLossMSE / $iterations, 2);

        $attLossPct = ($initialAttackerMSE > 0.0) ? min(1.0, max(0.0, $avgAttLossMSE / $initialAttackerMSE)) : 0.0;
        $defLossPct = ($initialDefenderMSE > 0.0) ? min(1.0, max(0.0, $avgDefLossMSE / $initialDefenderMSE)) : 0.0;

        $avgAttLostUnits = array();
        foreach ($totalAttLostUnits as $sId => $sumLost) {
            $avgAttLostUnits[$sId] = (int)round($sumLost / $iterations);
        }

        $avgDefLostUnits = array();
        foreach ($totalDefLostUnits as $uId => $sumLost) {
            $avgDefLostUnits[$uId] = (int)round($sumLost / $iterations);
        }

        return array(
            'win_rate'           => round($wins / $iterations, 3),
            'draw_rate'          => round($draws / $iterations, 3),
            'loss_rate'          => round($losses / $iterations, 3),
            'initial_att_mse'    => round($initialAttackerMSE, 2),
            'initial_def_mse'    => round($initialDefenderMSE, 2),
            'avg_att_loss_mse'   => $avgAttLossMSE,
            'avg_def_loss_mse'   => $avgDefLossMSE,
            'att_loss_pct'       => round($attLossPct, 4),
            'def_loss_pct'       => round($defLossPct, 4),
            'avg_att_lost_units' => $avgAttLostUnits,
            'avg_def_lost_units' => $avgDefLostUnits,
            'avg_att_loss'       => $avgAttLossMSE, // backwards-compatible key
            'avg_def_loss'       => $avgDefLossMSE, // backwards-compatible key
            'avg_debris_met'     => round($totalDebrisMetal / $iterations, 0),
            'avg_debris_cry'     => round($totalDebrisCry / $iterations, 0),
            'iterations'         => $iterations,
        );
    }
}
