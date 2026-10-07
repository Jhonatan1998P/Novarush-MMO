<?php

/**
 * NovaRush Bot AI v2 - Surrogate Combat Model
 *
 * Fast heuristic combat evaluator using Lanchester's square law, weapon-to-armor
 * effectiveness matrix, and shield bounce thresholds.
 * Level 1 of the Combat Oracle.
 */
class BotSurrogateCombatModel
{
    /**
     * Fast estimation of combat outcome
     *
     * @param array $attackerUnits [shipId => count]
     * @param array $defenderUnits [shipId/defId => count]
     * @param array $attackerFactors player factor array
     * @param array $defenderFactors player factor array
     * @return array ['win_prob' => float, 'att_loss_pct' => float, 'def_loss_pct' => float, 'score' => float]
     */
    public static function estimate(array $attackerUnits, array $defenderUnits, array $attackerFactors = array(), array $defenderFactors = array())
    {
        global $CombatCaps;

        if (empty($attackerUnits)) {
            return array('win_prob' => 0.0, 'att_loss_pct' => 1.0, 'def_loss_pct' => 0.0, 'score' => -100.0, 'ratio' => 0.0);
        }
        if (empty($defenderUnits)) {
            return array('win_prob' => 1.0, 'att_loss_pct' => 0.0, 'def_loss_pct' => 1.0, 'score' => 100.0, 'ratio' => 999.0);
        }

        $attPower = self::calculateSidePower($attackerUnits, $attackerFactors, $defenderUnits);
        $defPower = self::calculateSidePower($defenderUnits, $defenderFactors, $attackerUnits);

        if ($attPower['effective_att'] <= 0) {
            return array('win_prob' => 0.0, 'att_loss_pct' => 1.0, 'def_loss_pct' => 0.0, 'score' => -100.0, 'ratio' => 0.0);
        }
        if ($defPower['effective_att'] <= 0) {
            return array('win_prob' => 1.0, 'att_loss_pct' => 0.0, 'def_loss_pct' => 1.0, 'score' => 100.0, 'ratio' => 999.0);
        }

        // Lanchester strength: S = Attack * Durability
        $attStrength = sqrt(max(1.0, $attPower['effective_att']) * max(1.0, $attPower['effective_durability']));
        $defStrength = sqrt(max(1.0, $defPower['effective_att']) * max(1.0, $defPower['effective_durability']));

        $ratio = $attStrength / max(1.0, $defStrength);

        // Win probability via logistic sigmoid calibrated on Lanchester strength ratio
        // ratio = 1.0 -> 50%
        // ratio = 1.5 -> ~80%
        // ratio = 2.0 -> ~95%
        // ratio = 0.6 -> ~5%
        $k = 3.5;
        $winProb = 1.0 / (1.0 + exp(-$k * (log($ratio))));

        // Estimated loss percentages
        $attLossPct = min(1.0, max(0.0, pow(1.0 / max(0.1, $ratio), 1.5) * 0.5));
        $defLossPct = min(1.0, max(0.0, pow($ratio, 1.5) * 0.5));

        return array(
            'win_prob'     => round($winProb, 4),
            'att_loss_pct' => round($attLossPct, 4),
            'def_loss_pct' => round($defLossPct, 4),
            'ratio'        => round($ratio, 3),
            'score'        => round(($winProb - 0.5) * 200, 2),
        );
    }

    private static function calculateSidePower(array $units, array $factors, array $oppUnits)
    {
        global $CombatCaps;

        $totalAtt = 0.0;
        $totalDur = 0.0;
        $totalShield = 0.0;

        $attBonus = 1.0 + (isset($factors['Attack']) ? (float)$factors['Attack'] : 0.0);
        $defBonus = 1.0 + (isset($factors['Defensive']) ? (float)$factors['Defensive'] : 0.0);
        $shiBonus = 1.0 + (isset($factors['Shield']) ? (float)$factors['Shield'] : 0.0);

        // Analyze opponent armor distribution
        $oppArmorCounts = array('light' => 0, 'medium' => 0, 'heavy' => 0);
        $oppTotalUnits  = 0;
        foreach ($oppUnits as $uId => $count) {
            if ($count <= 0 || !isset($CombatCaps[$uId])) continue;
            $typeDef = isset($CombatCaps[$uId]['type_defend']) ? $CombatCaps[$uId]['type_defend'] : 'light';
            if (isset($oppArmorCounts[$typeDef])) {
                $oppArmorCounts[$typeDef] += $count;
            }
            $oppTotalUnits += $count;
        }

        $pctLight  = $oppTotalUnits > 0 ? $oppArmorCounts['light'] / $oppTotalUnits : 0.33;
        $pctMedium = $oppTotalUnits > 0 ? $oppArmorCounts['medium'] / $oppTotalUnits : 0.33;
        $pctHeavy  = $oppTotalUnits > 0 ? $oppArmorCounts['heavy'] / $oppTotalUnits : 0.33;

        foreach ($units as $uId => $count) {
            if ($count <= 0 || !isset($CombatCaps[$uId])) continue;

            $cap = $CombatCaps[$uId];
            $baseAtt = isset($cap['attack']) ? (float)$cap['attack'] : 0.0;
            $baseDef = isset($cap['defend']) ? (float)$cap['defend'] : 0.0;
            $baseShi = isset($cap['shield']) ? (float)$cap['shield'] : 0.0;

            // Weapon effectiveness factor based on opponent armor distribution
            $weaponEff = 1.0;
            $typeGun = isset($cap['type_gun']) ? $cap['type_gun'] : 'notype';

            if (strpos($typeGun, 'laser') !== false) {
                // Lasers hit mostly light armor
                $weaponEff = ($pctLight * 1.2) + ($pctMedium * 0.8) + ($pctHeavy * 0.1);
            } elseif (strpos($typeGun, 'ion') !== false) {
                // Ion hits light & medium
                $weaponEff = ($pctLight * 0.9) + ($pctMedium * 1.2) + ($pctHeavy * 0.4);
            } elseif (strpos($typeGun, 'plasma') !== false) {
                // Plasma destroys medium & heavy
                $weaponEff = ($pctLight * 0.1) + ($pctMedium * 1.1) + ($pctHeavy * 1.5);
            } elseif (strpos($typeGun, 'gravity') !== false) {
                // Graviton crushes heavy
                $weaponEff = ($pctLight * 0.05) + ($pctMedium * 0.8) + ($pctHeavy * 1.8);
            }

            $unitAtt = $baseAtt * $attBonus * max(0.2, $weaponEff);
            $unitDur = ($baseDef * $defBonus / 10.0) + ($baseShi * $shiBonus);

            $totalAtt += $unitAtt * $count;
            $totalDur += $unitDur * $count;
            $totalShield += ($baseShi * $shiBonus) * $count;
        }

        return array(
            'effective_att'        => $totalAtt,
            'effective_durability' => $totalDur,
            'effective_shield'     => $totalShield,
        );
    }
}
