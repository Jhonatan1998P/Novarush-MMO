<?php

/**
 * NovaRush Bot AI v2 - Recycle Planner
 *
 * Harvests confirmed orbital debris fields using dedicated recyclers (Mission 8: Recycling).
 * Strictly complies with engine rules: recyclers only, real debris fields only.
 */
class BotRecyclePlanner
{
    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Harvest debris fields in target candidate sectors
     *
     * @param BotWorldModel $world
     * @param array $basePlanet
     * @param array $targetCandidates
     * @return int Number of recycling fleets sent
     */
    public function harvestDebris(BotWorldModel $world, array $basePlanet, array $targetCandidates)
    {
        global $resource, $pricelist;

        $pData = $basePlanet['data'];
        $recs209 = isset($pData[$resource[209]]) ? (int)$pData[$resource[209]] : 0;
        $recs219 = isset($pData[$resource[219]]) ? (int)$pData[$resource[219]] : 0;
        if ($recs209 <= 0 && $recs219 <= 0) {
            return 0;
        }

        $sent = 0;
        $cap209 = isset($pricelist[209]['capacity']) ? (float)$pricelist[209]['capacity'] : 20000;
        $cap219 = isset($pricelist[219]['capacity']) ? (float)$pricelist[219]['capacity'] : 200000000;

        if (!is_array($targetCandidates)) {
            return 0;
        }

        foreach ($targetCandidates as $entry) {
            $t = $entry['target'];
            if (!$t['has_debris']) {
                continue;
            }

            $totalDebris = $t['debris_metal'] + $t['debris_crystal'];
            if ($totalDebris < 50000) {
                continue;
            }

            $fleet = array();
            $neededCap = $totalDebris;

            // Prioritize 219 (giga recycler) if available
            if ($recs219 > 0) {
                $take219 = min($recs219, (int)ceil($neededCap / $cap219));
                $fleet[219] = $take219;
                $neededCap -= ($take219 * $cap219);
                $recs219 -= $take219;
            }

            // Fill remainder with 209 (standard recycler)
            if ($neededCap > 0 && $recs209 > 0) {
                $take209 = min($recs209, (int)ceil($neededCap / $cap209));
                $fleet[209] = $take209;
                $recs209 -= $take209;
            }

            if (empty($fleet)) break;

            $ok = $this->gateway->launchFleet(
                $fleet,
                8, // Mission 8: Recycling
                $basePlanet['id'],
                $t['planet_id'],
                0, // Target owner 0 for debris fields
                $t['galaxy'],
                $t['system'],
                $t['planet'],
                2, // Planet type 2 = Debris
                array(),
                10
            );

            if ($ok) {
                $sent++;
                $dispatchedCount = array_sum($fleet);

                $this->ctx->decisionLog->record(
                    'recycling_launched',
                    "Dispatched {$dispatchedCount} recyclers to debris at {$t['galaxy']}:{$t['system']}:{$t['planet']}",
                    array('target' => $t, 'recyclers' => $dispatchedCount, 'debris' => $totalDebris),
                    "recycle_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                    $totalDebris
                );
            }

            if ($recs209 <= 0 && $recs219 <= 0) break;
        }

        return $sent;
    }
}
