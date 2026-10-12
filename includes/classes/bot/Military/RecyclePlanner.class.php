<?php

/**
 * NovaRush Bot AI v2 - Recycle Planner (Cirugía de Eficiencia & Anti-Spam)
 *
 * Mejoras de Alto Rendimiento:
 * 1. Anti-Duplicados y Anti-Spam: Consulta flotas activas en vuelo hacia los escombros (fleet_mission = 8).
 *    Si la capacidad asignada ya cubre el 100% de los escombros, no envía flotas redundantes.
 * 2. Geolocalización Multicolonia Óptima: Evalúa todas las colonias del imperio del bot y despacha
 *    desde la más cercana con menor tiempo de vuelo.
 * 3. Exactitud de Capacidad: Usa 200.000.000 para Giga Recicladores (219) y 20.000 para estándar (209).
 *    Envía únicamente las naves necesarias para recoger los escombros restantes.
 * 4. Despacho Escalonado/Pinza: Si la colonia más cercana no cubre el 100%, toma lo disponible y
 *    permite a la siguiente colonia más cercana cubrir el remanente exacto.
 */
class BotRecyclePlanner
{
    private $ctx;
    private $gateway;

    const CAP_RECYCLER_209      = 20000.0;
    const CAP_GIGA_RECYCLER_219 = 200000000.0;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Calcula la capacidad total de reciclaje que este bot ya tiene volando hacia las coordenadas objetivo
     *
     * @param int $galaxy
     * @param int $system
     * @param int $planet
     * @return float
     */
    public function getInFlightRecyclerCapacity($galaxy, $system, $planet)
    {
        $db = Database::get();
        $fleets = $db->select("SELECT fleet_array FROM %%FLEETS%% 
            WHERE fleet_owner = :botId 
              AND fleet_mission = 8 
              AND fleet_end_galaxy = :g 
              AND fleet_end_system = :s 
              AND fleet_end_planet = :p 
              AND fleet_end_type = 2 
              AND fleet_mess = 0;", array(
            ':botId' => (int)$this->ctx->botId,
            ':g'     => (int)$galaxy,
            ':s'     => (int)$system,
            ':p'     => (int)$planet,
        ));

        if (empty($fleets)) {
            return 0.0;
        }

        $totalCapacity = 0.0;
        foreach ($fleets as $f) {
            $ships = explode(';', $f['fleet_array']);
            foreach ($ships as $shipStr) {
                if (empty($shipStr)) continue;
                $parts = explode(',', $shipStr);
                if (count($parts) < 2) continue;
                $shipId = (int)$parts[0];
                $amount = (float)$parts[1];

                if ($shipId === 219) {
                    $totalCapacity += ($amount * self::CAP_GIGA_RECYCLER_219);
                } elseif ($shipId === 209) {
                    $totalCapacity += ($amount * self::CAP_RECYCLER_209);
                }
            }
        }

        return $totalCapacity;
    }

    /**
     * Harvest debris fields in target candidate sectors with multi-colony geo-optimization
     *
     * @param BotWorldModel $world
     * @param array $basePlanet
     * @param array $targetCandidates
     * @return int Number of recycling fleets sent
     */
    public function harvestDebris(BotWorldModel $world, array $basePlanet, array $targetCandidates)
    {
        global $resource;

        if (!is_array($targetCandidates) || empty($targetCandidates)) {
            return 0;
        }

        $sent = 0;
        $empirePlanets = $world->empire->getPlanets();

        // 1. Filtrar objetivos con escombros reales
        $debrisTargets = array();
        foreach ($targetCandidates as $entry) {
            $t = isset($entry['target']) ? $entry['target'] : array();
            if (empty($t) || empty($t['has_debris'])) {
                continue;
            }

            $totalDebris = (float)$t['debris_metal'] + (float)$t['debris_crystal'];
            if ($totalDebris < 50000.0) {
                continue;
            }

            // Chequeo Anti-Spam: Descontar capacidad ya en vuelo
            $inFlightCap = $this->getInFlightRecyclerCapacity($t['galaxy'], $t['system'], $t['planet']);
            $remDebris = $totalDebris - $inFlightCap;

            // Si ya hay suficiente capacidad en vuelo para cubrir los escombros, ignorar
            if ($remDebris <= 10000.0) {
                continue;
            }

            $debrisTargets[] = array(
                'target'      => $t,
                'totalDebris' => $totalDebris,
                'remDebris'   => $remDebris,
                'inFlightCap' => $inFlightCap
            );
        }

        if (empty($debrisTargets)) {
            return 0;
        }

        // 2. Para cada campo de escombros, ordenar las colonias del imperio por menor distancia
        foreach ($debrisTargets as $dT) {
            $t = $dT['target'];
            $remDebris = $dT['remDebris'];
            $tCoords = array((int)$t['galaxy'], (int)$t['system'], (int)$t['planet']);

            // Evaluar todas las colonias del bot con recicladores disponibles
            $availableBases = array();
            foreach ($empirePlanets as $p) {
                $pData = $p['data'];
                $r219 = isset($pData[$resource[219]]) ? (int)$pData[$resource[219]] : 0;
                $r209 = isset($pData[$resource[209]]) ? (int)$pData[$resource[209]] : 0;

                if ($r219 <= 0 && $r209 <= 0) {
                    continue;
                }

                $pCoords = array((int)$p['galaxy'], (int)$p['system'], (int)$p['planet']);
                $dist = FleetFunctions::GetTargetDistance($pCoords, $tCoords);

                $availableBases[] = array(
                    'planet'   => $p,
                    'distance' => $dist,
                    'recs219'  => $r219,
                    'recs209'  => $r209,
                );
            }

            if (empty($availableBases)) {
                break; // No hay recicladores libres en todo el imperio
            }

            // Ordenar por menor distancia (la colonia más cercana primero)
            usort($availableBases, function($a, $b) {
                return $a['distance'] - $b['distance'];
            });

            // 3. Despachar recicladores desde la(s) base(s) más cercana(s) hasta cubrir $remDebris
            foreach ($availableBases as $b) {
                if ($remDebris <= 10000.0) {
                    break;
                }

                $p = $b['planet'];
                $pData = &$p['data'];
                $avail219 = isset($pData[$resource[219]]) ? (int)$pData[$resource[219]] : 0;
                $avail209 = isset($pData[$resource[209]]) ? (int)$pData[$resource[209]] : 0;

                if ($avail219 <= 0 && $avail209 <= 0) {
                    continue;
                }

                $fleet = array();
                $neededCap = $remDebris;

                // Prioridad 1: Giga Recyclers 219 (ultra rápidos y capacidad 200M)
                if ($avail219 > 0) {
                    $take219 = min($avail219, (int)ceil($neededCap / self::CAP_GIGA_RECYCLER_219));
                    if ($take219 > 0) {
                        $fleet[219] = $take219;
                        $capacityCovered = $take219 * self::CAP_GIGA_RECYCLER_219;
                        $neededCap -= $capacityCovered;
                        $avail219 -= $take219;
                        $pData[$resource[219]] -= $take219;
                    }
                }

                // Prioridad 2: Recicladores estándar 209 si queda remanente
                if ($neededCap > 0 && $avail209 > 0) {
                    $take209 = min($avail209, (int)ceil($neededCap / self::CAP_RECYCLER_209));
                    if ($take209 > 0) {
                        $fleet[209] = $take209;
                        $capacityCovered = $take209 * self::CAP_RECYCLER_209;
                        $neededCap -= $capacityCovered;
                        $avail209 -= $take209;
                        $pData[$resource[209]] -= $take209;
                    }
                }

                if (empty($fleet)) {
                    continue;
                }

                $ok = $this->gateway->launchFleet(
                    $fleet,
                    8, // Mission 8: Recycling
                    $p['id'],
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
                    $coveredThisDispatch = ($remDebris - max(0.0, $neededCap));
                    $remDebris = max(0.0, $neededCap);

                    $this->ctx->decisionLog->record(
                        'recycling_launched',
                        "Despachados {$dispatchedCount} recicladores desde {$p['name']} [{$p['galaxy']}:{$p['system']}:{$p['planet']}] hacia escombros en [{$t['galaxy']}:{$t['system']}:{$t['planet']}] (Distancia: {$b['distance']} | Cubre: " . number_format($coveredThisDispatch) . " recursos)",
                        array(
                            'target'     => $t,
                            'from'       => "{$p['galaxy']}:{$p['system']}:{$p['planet']}",
                            'distance'   => $b['distance'],
                            'fleet'      => $fleet,
                            'totalDebris'=> $dT['totalDebris'],
                            'remDebris'  => $remDebris
                        ),
                        "recycle_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                        $coveredThisDispatch
                    );
                }
            }
        }

        return $sent;
    }
}
