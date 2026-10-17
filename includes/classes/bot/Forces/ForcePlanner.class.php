<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';

/**
 * NovaRush Bot AI v3.0 - Force Planner (Rediseño Integral Multi-Doctrina y Multicapa)
 *
 * Arquitectura Militar de NovaRush v3.0:
 * 1. Erradicación de Monopolios:
 *    - Techo estricto imperial para Recicladores (#209 + #219) <= 4.0% MSE y <= 1,500 Giga-Recicladores (#219).
 *    - Techo estricto imperial para Transportadores (#202 + #203 + #217) <= 4.0% MSE.
 *    - Erradicación de la hegemonía del Cañón Iónico (#405, antes 97.7%): techo estricto <= 25% MSE por colonia.
 * 2. Flota de Combate Multi-Doctrina (5 Ranuras):
 *    - Ranura 1: Carne de Cañón Exclusiva por Variante ($variantIdx = $botId % 4):
 *      * Var 0: #205 Caza Pesado (puente #204)
 *      * Var 1: #204 Caza Ligero
 *      * Var 2: #218 Caza Espacial (puente #204)
 *      * Var 3: Caza Élite de Facción (#229 Kratoniano, #236 Fotoriano, #243 Sinuria, #253 Terran; puente #205)
 *      * Cuota estricta: Base 14%, Suelo < 10% -> 24%, Techo >= 18% -> 0% (redistribuido a Slots 2-5).
 *    - Ranuras 2 a 5: Armamento Ion+ Mandatorio (Ion, Plasma, Gravitón) según 4 Doctrinas:
 *      * Slot 2: Vanguardia Iónica (20% / 25%)
 *      * Slot 3: Asalto Plasma (25% / 30%)
 *      * Slot 4: Capital Pesada (25%)
 *      * Slot 5: Superunidades Gravitón (16% / 11%)
 *      * Selección Dual-Tier en ranuras con >= 2 unidades investigadas: 70% a Tier superior, 30% a 2º Tier.
 * 3. Sistema Multicapa de Defensa Planetaria:
 *    - Prioridad 0: Cúpulas únicas (#407, #408, #409, #419) exactamente 1 unidad si faltan.
 *    - Prioridad 1: Silos de misiles (#502 ABM y #503 MIP) hasta capacidad.
 *    - Prioridad 2: Artillería defensiva en 4 capas:
 *      * Capa 1: Carne de cañón exclusiva por variante (#402/#420, #401/#423, #403/#434, o facción #453/#430/#444/#450) - Base 14%.
 *      * Capa 2: Cortina Iónica (#405 y facción) - Base 20%, candado estricto: si >= 25% MSE -> 0%!
 *      * Capa 3: Artillería de Plasma (#406, #412, #421, #422, #455, #456) - Base 32%, reparto 70/30.
 *      * Capa 4: Artillería Gravitón (#410, #418, #457, #458, #459, #460) - Base 34%, reparto 70/30.
 * 4. Blindajes Presupuestarios: Cero cascada de combate hacia civil/recicladores.
 */
class BotForcePlanner
{
    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Helper to safely get metal cost from $pricelist
     */
    public function getCostMetal($unitId)
    {
        global $pricelist;
        if (isset($pricelist[$unitId]['cost'][901])) {
            return max(1.0, (float)$pricelist[$unitId]['cost'][901]);
        }
        return 1000.0;
    }

    /**
     * Índice determinista de variante de bot (0, 1, 2, 3) basado en botId
     */
    public function getBotVariantIndex()
    {
        return (int)($this->ctx->botId % 4);
    }

    /**
     * Resuelve la raza o facción del bot (1501 Fotorianos, 1502 Sinuria, 1503 Terran, 1504 Kratonianos)
     */
    public function getBotFactionRace()
    {
        global $resource;
        $races = array(1501, 1502, 1503, 1504);
        if (isset($this->ctx->user) && is_array($this->ctx->user)) {
            foreach ($races as $rId) {
                if (!empty($this->ctx->user[$resource[$rId]])) {
                    return $rId;
                }
            }
        }

        $pers = isset($this->ctx->personality->name) ? $this->ctx->personality->name : 'balanced';
        if ($pers === 'aggressive' || $pers === 'raider' || $pers === BotPersonality::AGGRESSIVE) {
            return 1504; // Kratonianos
        } elseif ($pers === 'turtle' || $pers === BotPersonality::DEFENSIVE) {
            return 1502; // Sinuria
        } elseif ($pers === 'miner' || $pers === BotPersonality::ECONOMIC) {
            return 1501; // Fotorianos
        }

        $raceMap = array(0 => 1504, 1 => 1501, 2 => 1502, 3 => 1503);
        $vIdx = $this->getBotVariantIndex();
        return isset($raceMap[$vIdx]) ? $raceMap[$vIdx] : 1504;
    }

    /**
     * Resuelve el objetivo de nave de carne de cañón exclusiva y su nave puente
     */
    public function getFodderShipTarget()
    {
        $vIdx = $this->getBotVariantIndex();
        if ($vIdx === 0) {
            return array('target' => 205, 'bridge' => 204); // Caza Pesado (puente Caza Ligero)
        } elseif ($vIdx === 1) {
            return array('target' => 204, 'bridge' => 204); // Caza Ligero puro
        } elseif ($vIdx === 2) {
            return array('target' => 218, 'bridge' => 204); // Caza Espacial (puente Caza Ligero)
        } else {
            // Variante 3: Caza Élite de Facción
            $race = $this->getBotFactionRace();
            if ($race === 1504) {
                return array('target' => 229, 'bridge' => 205); // Caza Pesado Avanzado Kratoniano
            } elseif ($race === 1501) {
                return array('target' => 236, 'bridge' => 204); // Interceptor Ligero Fotoriano
            } elseif ($race === 1502) {
                return array('target' => 243, 'bridge' => 205); // Caza Guardián Sinuria
            } elseif ($race === 1503) {
                return array('target' => 253, 'bridge' => 204); // Caza Ligero Orbital Terran
            }
            return array('target' => 205, 'bridge' => 204);
        }
    }

    /**
     * Resuelve la unidad de carne de cañón exclusiva accesible en la colonia actual
     */
    public function resolveFodderShip(array $pData)
    {
        $info = $this->getFodderShipTarget();
        $target = $info['target'];
        $bridge = $info['bridge'];

        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $target, array())) {
            return $target;
        }
        if ($bridge > 0 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $bridge, array())) {
            return $bridge;
        }
        return 0;
    }

    /**
     * Retorna las especificaciones de ranuras de combate 2 a 5 según la Doctrina Militar
     */
    public function getDoctrineWarshipSlots()
    {
        $race = $this->getBotFactionRace();
        $pers = isset($this->ctx->personality->name) ? $this->ctx->personality->name : 'balanced';

        // Doctrina 1: Vanguardia de Asalto Pesado (Aggressive / 1504 Kratonianos)
        if ($race === 1504 || $pers === 'aggressive' || $pers === 'raider' || $pers === BotPersonality::AGGRESSIVE) {
            return array(
                'doctrine_id' => 1,
                'name'        => 'Vanguardia de Asalto Pesado (Kratoniana)',
                'slots'       => array(
                    2 => array('name' => 'Vanguardia Iónica',      'base_share' => 0.20, 'units' => array(206, 223, 230)),
                    3 => array('name' => 'Asalto Plasma',          'base_share' => 0.25, 'units' => array(211, 238, 262, 263)),
                    4 => array('name' => 'Capital Pesada',         'base_share' => 0.25, 'units' => array(213, 226, 225, 227)),
                    5 => array('name' => 'Superunidades Gravitón', 'base_share' => 0.16, 'units' => array(214, 216, 264, 265, 266, 267)),
                ),
            );
        }

        // Doctrina 2: Fortaleza de Plasma y Capitales (Turtle / 1502 Sinuria)
        if ($race === 1502 || $pers === 'turtle' || $pers === BotPersonality::DEFENSIVE) {
            return array(
                'doctrine_id' => 2,
                'name'        => 'Fortaleza de Plasma y Capitales (Sinuria)',
                'slots'       => array(
                    2 => array('name' => 'Artillería Iónica Móvil', 'base_share' => 0.20, 'units' => array(206, 224, 244)),
                    3 => array('name' => 'Bombarderos & Plasma',    'base_share' => 0.30, 'units' => array(211, 245, 262, 263)),
                    4 => array('name' => 'Naves de Línea Pesadas',  'base_share' => 0.25, 'units' => array(213, 225, 246, 226)),
                    5 => array('name' => 'Superfortalezas Gravitón','base_share' => 0.11, 'units' => array(214, 248, 216, 266)),
                ),
            );
        }

        // Doctrina 3: Flota Rápida de Interdicción (Miner / 1501 Fotorianos)
        if ($race === 1501 || $pers === 'miner' || $pers === BotPersonality::ECONOMIC) {
            return array(
                'doctrine_id' => 3,
                'name'        => 'Flota Rápida de Interdicción (Fotoriana)',
                'slots'       => array(
                    2 => array('name' => 'Interdictores Iónicos Rápidos',    'base_share' => 0.25, 'units' => array(206, 223, 237)),
                    3 => array('name' => 'Cruceros Plasma de Alta Velocidad','base_share' => 0.25, 'units' => array(211, 239, 262)),
                    4 => array('name' => 'Destructores y Buques Insignia',   'base_share' => 0.25, 'units' => array(213, 240, 225)),
                    5 => array('name' => 'Superunidades Gravitón',           'base_share' => 0.11, 'units' => array(214, 241, 216, 265)),
                ),
            );
        }

        // Doctrina 4: Doctrina Táctica Estándar (Terran 1503 / Balanced)
        return array(
            'doctrine_id' => 4,
            'name'        => 'Doctrina Táctica Estándar (Terran / Equilibrada)',
            'slots'       => array(
                2 => array('name' => 'Vanguardia Iónica',       'base_share' => 0.20, 'units' => array(206, 223, 254)),
                3 => array('name' => 'Plasma y Bombardeo',      'base_share' => 0.25, 'units' => array(211, 256, 262, 263)),
                4 => array('name' => 'Acorazados y Destructores','base_share' => 0.25, 'units' => array(213, 226, 257, 225)),
                5 => array('name' => 'Superunidades Gravitón',  'base_share' => 0.16, 'units' => array(214, 216, 258, 264)),
            ),
        );
    }

    /**
     * Resuelve la unidad exclusiva de carne de cañón defensiva para esta colonia
     */
    public function getFodderDefenseId(array $pData)
    {
        $vIdx = $this->getBotVariantIndex();
        if ($vIdx === 0) {
            // Variante 0: #420 Cañón Láser Mejorado (o #402 Láser Pesado)
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 420, array())) {
                return 420;
            }
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 402, array())) {
                return 402;
            }
            return BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 401, array()) ? 401 : 0;
        } elseif ($vIdx === 1) {
            // Variante 1: #423 Torreta de Misiles Pesados (o #401 Lanzamisiles)
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 423, array())) {
                return 423;
            }
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 401, array())) {
                return 401;
            }
            return 0;
        } elseif ($vIdx === 2) {
            // Variante 2: #434 Batería Defensiva Balística (o #403 Gauss Ligero)
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 434, array())) {
                return 434;
            }
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 403, array())) {
                return 403;
            }
            return BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 401, array()) ? 401 : 0;
        } else {
            // Variante 3: Fodder de Facción
            $race = $this->getBotFactionRace();
            $target = 453;
            $fallback = 402;
            if ($race === 1504) {
                $target = 453; // Lanzador Balístico Pesado
                $fallback = 402;
            } elseif ($race === 1501) {
                $target = 430; // Proyector Fotónico Básico
                $fallback = 402;
            } elseif ($race === 1502) {
                $target = 444; // Púlsar de Escudo Defensivo
                $fallback = 402;
            } elseif ($race === 1503) {
                $target = 450; // Torreta Doble Automática
                $fallback = 401;
            }
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $target, array())) {
                return $target;
            }
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $fallback, array())) {
                return $fallback;
            }
            return BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 401, array()) ? 401 : 0;
        }
    }

    /**
     * Catálogo maestro de unidades por Capa Defensiva
     */
    public function getDefenseCapas()
    {
        return array(
            'domes' => array(407, 408, 409, 419),
            'silo'  => array(502, 503),
            'capa2' => array(405, 431, 445, 454),
            'capa3' => array(406, 412, 421, 422, 455, 456),
            'capa4' => array(410, 418, 457, 458, 459, 460),
        );
    }

    /**
     * Desglose forense del valor MSE de defensas en una colonia por capas
     */
    public function calculateDefenseBreakdown(array $planet)
    {
        global $resource, $reslist;
        $capas = $this->getDefenseCapas();
        $breakdown = array(
            'total_mse' => 0.0,
            'domes_mse' => 0.0,
            'silo_mse'  => 0.0,
            'capa1_mse' => 0.0,
            'capa2_mse' => 0.0,
            'capa3_mse' => 0.0,
            'capa4_mse' => 0.0,
        );

        // Domes
        foreach ($capas['domes'] as $dId) {
            $cnt = (int)($planet[$resource[$dId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($dId);
                $breakdown['domes_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        // Silo
        foreach ($capas['silo'] as $sId) {
            $cnt = (int)($planet[$resource[$sId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($sId);
                $breakdown['silo_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        // Capa 2 (Ion)
        foreach ($capas['capa2'] as $uId) {
            $cnt = (int)($planet[$resource[$uId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($uId);
                $breakdown['capa2_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        // Capa 3 (Plasma)
        foreach ($capas['capa3'] as $uId) {
            $cnt = (int)($planet[$resource[$uId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($uId);
                $breakdown['capa3_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        // Capa 4 (Graviton)
        foreach ($capas['capa4'] as $uId) {
            $cnt = (int)($planet[$resource[$uId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($uId);
                $breakdown['capa4_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        // Capa 1: Fodder units
        $allFodderDefIds = array(401, 402, 403, 420, 423, 430, 434, 444, 450, 453);
        foreach ($allFodderDefIds as $uId) {
            $cnt = (int)($planet[$resource[$uId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($uId);
                $breakdown['capa1_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        // Any remaining defense in reslist
        $accounted = array_merge($capas['domes'], $capas['silo'], $capas['capa2'], $capas['capa3'], $capas['capa4'], $allFodderDefIds);
        if (!empty($reslist['defense'])) {
            foreach ($reslist['defense'] as $defId) {
                $defId = (int)$defId;
                if (!in_array($defId, $accounted)) {
                    $cnt = (int)($planet[$resource[$defId]] ?? 0);
                    if ($cnt > 0) {
                        $mse = $cnt * BotEconomyValuator::getElementPriceMSE($defId);
                        $breakdown['total_mse'] += $mse;
                    }
                }
            }
        }

        return $breakdown;
    }

    /**
     * Calcula estadísticas militares del imperio completo para calibrar cuotas y topes
     */
    public function getEmpireFleetStats(BotEmpireState $empire)
    {
        global $resource, $reslist;
        $stats = array(
            'total_fleet_mse' => 0.0,
            'recycler_mse'    => 0.0,
            'cargo_mse'       => 0.0,
            'fodder_mse'      => 0.0,
            'combat_mse'      => 0.0,
            'count_219'       => 0,
            'count_recyclers' => 0,
            'count_cargos'    => 0,
        );

        $fodderInfo = $this->getFodderShipTarget();
        $targetFodder = $infoFodderTarget = $fodderInfo['target'];
        $bridgeFodder = $fodderInfo['bridge'];

        $fleetList = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();

        foreach ($empire->getAllBodies() as $b) {
            $bd = $b['data'];

            // Recyclers (209, 219)
            $c209 = (int)($bd[$resource[209]] ?? 0);
            $c219 = (int)($bd[$resource[219]] ?? 0);
            $stats['count_219'] += $c219;
            $stats['count_recyclers'] += ($c209 + $c219);
            $stats['recycler_mse'] += ($c209 * BotEconomyValuator::getElementPriceMSE(209)) + ($c219 * BotEconomyValuator::getElementPriceMSE(219));

            // Cargos (202, 203, 217)
            $c202 = (int)($bd[$resource[202]] ?? 0);
            $c203 = (int)($bd[$resource[203]] ?? 0);
            $c217 = (int)($bd[$resource[217]] ?? 0);
            $stats['count_cargos'] += ($c202 + $c203 + $c217);
            $stats['cargo_mse'] += ($c202 * BotEconomyValuator::getElementPriceMSE(202))
                                 + ($c203 * BotEconomyValuator::getElementPriceMSE(203))
                                 + ($c217 * BotEconomyValuator::getElementPriceMSE(217));

            // Todas las naves de combate
            foreach ($fleetList as $sId) {
                $sId = (int)$sId;
                if (in_array($sId, array(208, 210, 212, 233))) {
                    continue; // Excluir satélites, sondas y colonizadores del cómputo de flota armada
                }
                $cnt = (int)($bd[$resource[$sId]] ?? 0);
                if ($cnt <= 0) continue;

                $shipMSE = BotEconomyValuator::getElementPriceMSE($sId) * $cnt;
                $stats['total_fleet_mse'] += $shipMSE;

                if ($sId === $targetFodder || $sId === $bridgeFodder) {
                    $stats['fodder_mse'] += $shipMSE;
                } elseif (!in_array($sId, array(202, 203, 217, 209, 219))) {
                    $stats['combat_mse'] += $shipMSE;
                }
            }
        }

        return $stats;
    }

    /**
     * Mantiene compatibilidad con BD para asignación doctrinal básica
     */
    public function getOrAssignFleetDoctrine()
    {
        $heavy    = isset($this->ctx->botRow['doctrine_heavy']) ? (int)$this->ctx->botRow['doctrine_heavy'] : 0;
        $medium   = isset($this->ctx->botRow['doctrine_medium']) ? (int)$this->ctx->botRow['doctrine_medium'] : 0;
        $light    = isset($this->ctx->botRow['doctrine_light']) ? (int)$this->ctx->botRow['doctrine_light'] : 0;
        $fodder   = isset($this->ctx->botRow['doctrine_fodder']) ? (int)$this->ctx->botRow['doctrine_fodder'] : 0;
        $interval = isset($this->ctx->botRow['recruit_interval']) ? (int)$this->ctx->botRow['recruit_interval'] : 0;

        $fodderTarget = $this->getFodderShipTarget();
        $targetFodderId = $fodderTarget['target'];

        $updates = array();
        if ($fodder !== $targetFodderId) {
            $fodder = $targetFodderId;
            $updates['doctrine_fodder'] = $fodder;
        }

        if ($interval < 600 || $interval > 1800) {
            $pers = isset($this->ctx->personality->name) ? $this->ctx->personality->name : 'balanced';
            if ($pers === BotPersonality::AGGRESSIVE || $pers === 'raider' || $pers === 'aggressive') {
                $interval = mt_rand(600, 900); // 10 a 15 min
            } elseif ($pers === BotPersonality::DEFENSIVE || $pers === BotPersonality::ECONOMIC || $pers === 'turtle' || $pers === 'miner') {
                $interval = mt_rand(1200, 1800); // 20 a 30 min
            } else {
                $interval = mt_rand(900, 1200); // 15 a 20 min
            }
            $updates['recruit_interval'] = $interval;
        }

        if (!empty($updates)) {
            $this->ctx->updateBotRow($updates);
        }

        return array(
            'heavy'    => $heavy,
            'medium'   => $medium,
            'light'    => $light,
            'support'  => $light,
            'fodder'   => $fodder,
            'interval' => $interval
        );
    }

    public function resolveCategoryUnit($category, array $pData)
    {
        if ($category === 'fodder') {
            return $this->resolveFodderShip($pData);
        }
        $doctrineData = $this->getDoctrineWarshipSlots();
        $slots = $doctrineData['slots'];
        $map = array(
            'support' => 2,
            'medium'  => 3,
            'heavy'   => 4,
            'elite'   => 5,
        );
        $slotIdx = $map[$category] ?? 2;
        $units = $slots[$slotIdx]['units'] ?? array(206);
        foreach (array_reverse($units) as $uId) {
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $uId, array())) {
                return $uId;
            }
        }
        return 206;
    }

    public function getBestFallbackCombatShip(array $pData, $category = 'support')
    {
        return $this->resolveCategoryUnit($category, $pData);
    }

    private function refreshPlanetData($pId, array $pData)
    {
        if (method_exists($this->gateway, 'getFreshPlanet')) {
            return $this->gateway->getFreshPlanet($pId);
        }
        $fresh = Database::get()->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :pId;", array(':pId' => $pId));
        return !empty($fresh) ? $fresh : $pData;
    }

    public function calculateQueueRemainingSeconds(array $planet)
    {
        if (empty($planet['b_hangar_id'])) {
            return 0;
        }
        $queue = @unserialize($planet['b_hangar_id']);
        if (!is_array($queue) || empty($queue)) {
            return 0;
        }

        $totalSeconds = 0;
        foreach ($queue as $item) {
            $unitId = (int)$item[0];
            $count  = (int)$item[1];
            $unitTime = BuildFunctions::getBuildingTime($this->ctx->user, $planet, $unitId);
            $totalSeconds += ($unitTime * $count);
        }

        $elapsed = isset($planet['b_hangar']) ? (int)$planet['b_hangar'] : 0;
        return max(0, $totalSeconds - $elapsed);
    }

    public function isUnitInQueue(array $planet, $unitId)
    {
        if (empty($planet['b_hangar_id'])) {
            return false;
        }
        $queue = @unserialize($planet['b_hangar_id']);
        if (!is_array($queue)) {
            return false;
        }
        foreach ($queue as $item) {
            if ((int)$item[0] === (int)$unitId) {
                return true;
            }
        }
        return false;
    }

    public function calculateExistingDefenseMSE(array $planet)
    {
        $breakdown = $this->calculateDefenseBreakdown($planet);
        return $breakdown['total_mse'];
    }

    public function calculateTriResourceMaxUnits(array $pData, array $categoryCredits, $unitId, $lockedDeficitRes = 0, $lockedDeficitAmount = 0.0)
    {
        global $pricelist;
        $costM = isset($pricelist[$unitId]['cost'][901]) ? (float)$pricelist[$unitId]['cost'][901] : 0.0;
        $costC = isset($pricelist[$unitId]['cost'][902]) ? (float)$pricelist[$unitId]['cost'][902] : 0.0;
        $costD = isset($pricelist[$unitId]['cost'][903]) ? (float)$pricelist[$unitId]['cost'][903] : 0.0;

        $availM = min((float)$pData['metal'], (float)($categoryCredits['metal'] ?? 0.0));
        $availC = min((float)$pData['crystal'], (float)($categoryCredits['crystal'] ?? 0.0));
        $availD = min((float)$pData['deuterium'], (float)($categoryCredits['deuterium'] ?? 0.0));

        if ($lockedDeficitRes === 901 && $costM > 0) {
            $availM = max(0.0, $availM - $lockedDeficitAmount);
        } elseif ($lockedDeficitRes === 902 && $costC > 0) {
            $availC = max(0.0, $availC - $lockedDeficitAmount);
        } elseif ($lockedDeficitRes === 903 && $costD > 0) {
            $availD = max(0.0, $availD - $lockedDeficitAmount);
        }

        $canBuildTri = PHP_INT_MAX;
        if ($costM > 0) {
            $canBuildTri = min($canBuildTri, (int)floor($availM / $costM));
        }
        if ($costC > 0) {
            $canBuildTri = min($canBuildTri, (int)floor($availC / $costC));
        }
        if ($costD > 0) {
            $canBuildTri = min($canBuildTri, (int)floor($availD / $costD));
        }

        if ($canBuildTri === PHP_INT_MAX) {
            $canBuildTri = 0;
        }

        return max(0, $canBuildTri);
    }

    public function calculateCycleSpendBudget($pId, array $pData, $category, array $totalCredits, $lastRecruitTime = 0, $isEmergency = false)
    {
        $now = BotClock::get();
        $elapsed = ($lastRecruitTime > 0) ? max(45, $now - $lastRecruitTime) : 180;
        $elapsed = min(3600, $elapsed);

        $ratios = (isset($this->ctx->personality) && method_exists($this->ctx->personality, 'getBudgetRatios'))
            ? $this->ctx->personality->getBudgetRatios()
            : array('fleet' => 0.35, 'defense' => 0.15, 'mines' => 0.30, 'research' => 0.20);

        $r_k = (float)($ratios[$category] ?? 0.25);

        $prodM = max(0.0, (float)($pData['metal_perhour'] ?? 0.0));
        $prodC = max(0.0, (float)($pData['crystal_perhour'] ?? 0.0));
        $prodD = max(0.0, (float)($pData['deuterium_perhour'] ?? 0.0));

        $freshM = ($prodM * $elapsed / 3600.0) * $r_k;
        $freshC = ($prodC * $elapsed / 3600.0) * $r_k;
        $freshD = ($prodD * $elapsed / 3600.0) * $r_k;

        $totM = max(0.0, (float)($totalCredits['metal'] ?? 0.0));
        $totC = max(0.0, (float)($totalCredits['crystal'] ?? 0.0));
        $totD = max(0.0, (float)($totalCredits['deuterium'] ?? 0.0));

        $surplusM = max(0.0, $totM - $freshM);
        $surplusC = max(0.0, $totC - $freshC);
        $surplusD = max(0.0, $totD - $freshD);
        $surplusMSE = $surplusM + ($surplusC * 1.5) + ($surplusD * 3.0);

        $drainRate = $isEmergency ? 1.00 : (($surplusMSE > 50000000) ? 0.45 : 0.08);

        $authM = min($totM, $freshM + ($drainRate * $surplusM));
        $authC = min($totC, $freshC + ($drainRate * $surplusC));
        $authD = min($totD, $freshD + ($drainRate * $surplusD));

        return array(
            'metal'     => round($authM, 2),
            'crystal'   => round($authC, 2),
            'deuterium' => round($authD, 2),
        );
    }

    public function getConstructibleBatch(array $pData, array $categoryCredits, $unitId, $queuedJobs, $maxAllowedSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefRes = 0, $isEmergency = false, $lockedDefAmount = 0.0)
    {
        global $pricelist;

        if ($this->isUnitInQueue($pData, $unitId)) {
            return 0;
        }

        if ($queuedJobs >= $maxAllowedSlots) {
            return 0;
        }

        if (!$isEmergency && $queueRemainingSec >= $maxHangarDuration) {
            return 0;
        }

        if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $unitId, array())) {
            return 0;
        }

        $triMax = $this->calculateTriResourceMaxUnits($pData, $categoryCredits, $unitId, $lockedDefRes, $lockedDefAmount);
        $maxConstructible = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, $unitId);
        $affordableUnits = min($triMax, $maxConstructible);

        if ($affordableUnits <= 0) {
            return 0;
        }

        $unitTime = max(0.001, (float)BuildFunctions::getBuildingTime($this->ctx->user, $pData, $unitId));

        if ($isEmergency) {
            return $affordableUnits;
        }

        $maxByBatchTime = max(1, (int)floor($batchTimeTarget / $unitTime));
        $availQueueSec  = max(0, $maxHangarDuration - $queueRemainingSec);
        $maxByQueueCap  = max(1, (int)floor($availQueueSec / $unitTime));

        $batchLimit = min($maxByBatchTime, $maxByQueueCap);

        return max(0, min($affordableUnits, $batchLimit));
    }

    /**
     * Modernización Progresiva de Flota Civil con Topes Rígidos (Anti-Monopolio)
     * - Cadena 202 -> 203 -> 217 (Topada al 4% MSE de flota)
     * - Cadena 209 -> 219 (Topada al 4% MSE de flota y máximo 1,500 #219 imperio-wide)
     * - CERO gasto de créditos de combate en recicladores o cargueros.
     */
    public function upgradeCivilianFleets($planetId, array &$pData, array $empireStats = array())
    {
        global $resource;
        $upgraded = array();

        $queuedJobs = !empty($pData['b_hangar_id']) ? count(@unserialize($pData['b_hangar_id'])) : 0;
        $maxRegularSlots = isset($this->ctx->difficulty->maxRegularSlots) ? (int)$this->ctx->difficulty->maxRegularSlots : 6;
        $maxHangarDuration = isset($this->ctx->difficulty->maxHangarQueueDuration) ? (int)$this->ctx->difficulty->maxHangarQueueDuration : 2700;
        $batchTimeTarget = isset($this->ctx->difficulty->batchTimeTarget) ? (int)$this->ctx->difficulty->batchTimeTarget : 900;
        $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);

        if ($queuedJobs >= $maxRegularSlots || $queueRemainingSec >= $maxHangarDuration) {
            return $upgraded;
        }

        $totalFleetMSE = (float)($empireStats['total_fleet_mse'] ?? 0.0);
        $recyclerMSE   = (float)($empireStats['recycler_mse'] ?? 0.0);
        $cargoMSE      = (float)($empireStats['cargo_mse'] ?? 0.0);
        $count219      = (int)($empireStats['count_219'] ?? 0);

        $cargoCapReached    = ($totalFleetMSE > 0 && ($cargoMSE / $totalFleetMSE) >= 0.04);
        $recyclerCapReached = (($totalFleetMSE > 0 && ($recyclerMSE / $totalFleetMSE) >= 0.04) || ($count219 >= 1500));

        // 1. Cadena 202 -> 203 -> 217
        if (!$cargoCapReached) {
            $canBuild217 = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 217, array());
            if ($canBuild217 && !$this->isUnitInQueue($pData, 217) && $queuedJobs < $maxRegularSlots) {
                $deutStock = (float)$pData['deuterium'];
                $max217ByDeut = (int)floor($deutStock / 1500.0);

                if ($max217ByDeut > 0) {
                    $unitTime217 = max(0.001, (float)BuildFunctions::getBuildingTime($this->ctx->user, $pData, 217));
                    $maxByBatchTime = max(1, (int)floor($batchTimeTarget / $unitTime217));
                    $availQueueSec = max(0, $maxHangarDuration - $queueRemainingSec);
                    $maxByQueueCap = max(1, (int)floor($availQueueSec / $unitTime217));
                    $batchLimit217 = min($maxByBatchTime, $maxByQueueCap);

                    $cur202 = isset($pData[$resource[202]]) ? (int)$pData[$resource[202]] : 0;
                    $avail202 = max(0, $cur202 - 100);
                    $batches202 = min((int)floor($avail202 / 18), $max217ByDeut, $batchLimit217);

                    if ($batches202 > 0) {
                        $scrap202 = $batches202 * 18;
                        $ref = $this->gateway->scrapUnits($planetId, 202, $scrap202);
                        if ($ref) {
                            $this->gateway->constructUnits($planetId, 217, $batches202);
                            $upgraded[217] = isset($upgraded[217]) ? $upgraded[217] + $batches202 : $batches202;
                            $pData = $this->refreshPlanetData($planetId, $pData);
                            $max217ByDeut -= $batches202;
                            $queuedJobs++;
                            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                        }
                    }

                    if ($max217ByDeut > 0 && !$this->isUnitInQueue($pData, 217) && $queuedJobs < $maxRegularSlots && $queueRemainingSec < $maxHangarDuration) {
                        $cur203 = isset($pData[$resource[203]]) ? (int)$pData[$resource[203]] : 0;
                        $avail203 = max(0, $cur203 - 20);
                        $batches203 = min((int)floor($avail203 / 6), $max217ByDeut, $batchLimit217);

                        if ($batches203 > 0) {
                            $scrap203 = $batches203 * 6;
                            $ref = $this->gateway->scrapUnits($planetId, 203, $scrap203);
                            if ($ref) {
                                $this->gateway->constructUnits($planetId, 217, $batches203);
                                $upgraded[217] = isset($upgraded[217]) ? $upgraded[217] + $batches203 : $batches203;
                                $pData = $this->refreshPlanetData($planetId, $pData);
                                $queuedJobs++;
                                $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                            }
                        }
                    }
                }
            } elseif (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 203, array()) && !$this->isUnitInQueue($pData, 203) && $queuedJobs < $maxRegularSlots && $queueRemainingSec < $maxHangarDuration) {
                $cur202 = isset($pData[$resource[202]]) ? (int)$pData[$resource[202]] : 0;
                $avail202 = max(0, $cur202 - 100);
                $unitTime203 = max(0.001, (float)BuildFunctions::getBuildingTime($this->ctx->user, $pData, 203));
                $maxBatch203 = min(max(1, (int)floor($batchTimeTarget / $unitTime203)), max(1, (int)floor(max(0, $maxHangarDuration - $queueRemainingSec) / $unitTime203)));
                $batches203 = min((int)floor($avail202 / 3), $maxBatch203);
                if ($batches203 > 0) {
                    $scrap202 = $batches203 * 3;
                    $ref = $this->gateway->scrapUnits($planetId, 202, $scrap202);
                    if ($ref) {
                        $this->gateway->constructUnits($planetId, 203, $batches203);
                        $upgraded[203] = isset($upgraded[203]) ? $upgraded[203] + $batches203 : $batches203;
                        $pData = $this->refreshPlanetData($planetId, $pData);
                        $queuedJobs++;
                        $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                    }
                }
            }
        }

        // 2. Cadena 209 -> 219 (100x 209 = 1x 219)
        // Estricto: SOLO desguace puro 100:1, CERO uso de presupuesto de combate, y respetando límites
        if (!$recyclerCapReached && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 219, array()) && !$this->isUnitInQueue($pData, 219) && $queuedJobs < $maxRegularSlots && $queueRemainingSec < $maxHangarDuration) {
            $cur209 = isset($pData[$resource[209]]) ? (int)$pData[$resource[209]] : 0;
            $avail209 = max(0, $cur209 - 20); // Buffer de 20x 209
            $unitTime219 = max(0.001, (float)BuildFunctions::getBuildingTime($this->ctx->user, $pData, 219));
            $maxBatch219 = min(max(1, (int)floor($batchTimeTarget / $unitTime219)), max(1, (int)floor(max(0, $maxHangarDuration - $queueRemainingSec) / $unitTime219)));

            $allowed219 = max(0, 1500 - $count219);
            $batches219 = min((int)floor($avail209 / 100), $maxBatch219, $allowed219);
            if ($batches219 > 0) {
                $scrap209 = $batches219 * 100;
                $ref = $this->gateway->scrapUnits($planetId, 209, $scrap209);
                if ($ref) {
                    $this->gateway->constructUnits($planetId, 219, $batches219);
                    $upgraded[219] = isset($upgraded[219]) ? $upgraded[219] + $batches219 : $batches219;
                    $pData = $this->refreshPlanetData($planetId, $pData);
                }
            }
        }

        return $upgraded;
    }

    /**
     * Planifica y encola reclutamiento militar en todo el imperio
     */
    public function planForces(BotEmpireState $empire, $forceRun = false, $world = null)
    {
        global $resource, $pricelist, $reslist;

        $doctrine = $this->getOrAssignFleetDoctrine();

        $lastFleetRecruit = isset($this->ctx->botRow['last_fleet_recruit']) ? (int)$this->ctx->botRow['last_fleet_recruit'] : 0;
        $lastDefRecruit   = isset($this->ctx->botRow['last_defense_recruit']) ? (int)$this->ctx->botRow['last_defense_recruit'] : 0;
        if ($lastFleetRecruit === 0 && isset($this->ctx->botRow['last_recruit'])) {
            $lastFleetRecruit = (int)$this->ctx->botRow['last_recruit'];
        }
        if ($lastDefRecruit === 0 && isset($this->ctx->botRow['last_recruit'])) {
            $lastDefRecruit = (int)$this->ctx->botRow['last_recruit'];
        }

        $isSiegeActive = (!empty($this->ctx->botRow['target_type']) && $this->ctx->botRow['target_type'] === 'siege');

        $threatIncoming = false;
        if (defined('SIMULATION_MODE') && SIMULATION_MODE) {
            $threatIncoming = false;
        } elseif ($world && isset($world->threats) && method_exists($world->threats, 'getActiveThreats')) {
            $threats = $world->threats->getActiveThreats();
            $threatIncoming = !empty($threats);
        } else {
            require_once 'includes/classes/bot/Perception/ThreatBoard.class.php';
            $tb = new BotThreatBoard($this->ctx->botId, isset($this->ctx->user['spy_tech']) ? (int)$this->ctx->user['spy_tech'] : 0);
            $threats = $tb->getActiveThreats();
            $threatIncoming = !empty($threats);
        }

        $milInterval = isset($this->ctx->difficulty->militaryCycleInterval) ? (int)$this->ctx->difficulty->militaryCycleInterval : 900;
        $defInterval = isset($this->ctx->difficulty->defenseCycleInterval) ? (int)$this->ctx->difficulty->defenseCycleInterval : 1800;

        $canRunFleet   = ($forceRun || $isSiegeActive || $threatIncoming || ($lastFleetRecruit === 0) || ((BotClock::get() - $lastFleetRecruit) >= $milInterval));
        $canRunDefense = ($forceRun || $isSiegeActive || $threatIncoming || ($lastDefRecruit === 0) || ((BotClock::get() - $lastDefRecruit) >= $defInterval));

        $recruited = array();
        $fleetRecruitedTotal = 0;
        $defRecruitedTotal = 0;

        $allBodies = $empire->getAllBodies();
        if (!is_array($allBodies)) {
            return $recruited;
        }

        // Estadísticas militares del imperio completo para calibrar cuotas
        $empireStats = $this->getEmpireFleetStats($empire);
        $totalFleetMSE = $empireStats['total_fleet_mse'];
        $fodderMSE     = $empireStats['fodder_mse'];
        $count219      = $empireStats['count_219'];

        $cargoCapReached    = ($totalFleetMSE > 0 && ($empireStats['cargo_mse'] / $totalFleetMSE) >= 0.04);
        $recyclerCapReached = (($totalFleetMSE > 0 && ($empireStats['recycler_mse'] / $totalFleetMSE) >= 0.04) || ($count219 >= 1500));
        $fodderRatio        = ($totalFleetMSE > 0) ? ($fodderMSE / $totalFleetMSE) : 0.0;

        $doctrineSpecs = $this->getDoctrineWarshipSlots();

        foreach ($allBodies as $pId => $body) {
            $pData = $body['data'];

            $queuedJobs = !empty($pData['b_hangar_id']) ? count(@unserialize($pData['b_hangar_id'])) : 0;
            $maxQueueJobs = 8;
            $maxRegularSlots = isset($this->ctx->difficulty->maxRegularSlots) ? (int)$this->ctx->difficulty->maxRegularSlots : 6;
            $maxHangarDuration = isset($this->ctx->difficulty->maxHangarQueueDuration) ? (int)$this->ctx->difficulty->maxHangarQueueDuration : 2700;
            $batchTimeTarget = isset($this->ctx->difficulty->batchTimeTarget) ? (int)$this->ctx->difficulty->batchTimeTarget : 900;

            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);

            // --- 1. Estabilización Energética: Satélites Solares (212) ---
            $energy = (float)$pData['energy'];
            if ($energy < 0 && $queuedJobs < $maxQueueJobs && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 212, array()) && !$this->isUnitInQueue($pData, 212)) {
                $energyPerSat = max(1.0, (($pData['temp_max'] + 160) / 6.0));
                $satsNeeded   = min(50, (int)ceil(abs($energy) / $energyPerSat));
                $maxSats      = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, 212);
                $batchSats    = min($satsNeeded, $maxSats);

                $enqueuedSats = ($batchSats > 0) ? (int)$this->gateway->constructUnits($pId, 212, $batchSats) : 0;
                if ($enqueuedSats > 0) {
                    $recruited[212] = isset($recruited[212]) ? $recruited[212] + $enqueuedSats : $enqueuedSats;
                    $fleetRecruitedTotal += $enqueuedSats;
                    $queuedJobs++;
                    $this->ctx->decisionLog->record(
                        'solar_satellite_build',
                        "Construidos {$enqueuedSats}x Satélites Solares en planeta #{$pId} para resolver déficit energético ({$energy})",
                        array('deficit' => $energy, 'batch' => $enqueuedSats),
                        "sat_{$pId}",
                        1000.0
                    );
                    $pData = $this->refreshPlanetData($pId, $pData);
                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                }
            }

            if (!$canRunFleet && !$canRunDefense) {
                continue;
            }

            // --- Modernización de Flota Civil con Topes Rígidos ---
            if ($canRunFleet) {
                $upgradedCiv = $this->upgradeCivilianFleets($pId, $pData, $empireStats);
                if (!empty($upgradedCiv)) {
                    foreach ($upgradedCiv as $civId => $civCount) {
                        $recruited[$civId] = isset($recruited[$civId]) ? $recruited[$civId] + $civCount : $civCount;
                        $fleetRecruitedTotal += $civCount;
                        if ($civId === 219) {
                            $empireStats['count_219'] += $civCount;
                            if ($empireStats['count_219'] >= 1500) {
                                $recyclerCapReached = true;
                            }
                        }
                    }
                    $queuedJobs = !empty($pData['b_hangar_id']) ? count(@unserialize($pData['b_hangar_id'])) : 0;
                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                }
            }

            // Presupuestos de ciclo con Válvula de Flujo Continuo
            $rawFleetCredits = isset($this->ctx->budgetManager)
                ? $this->ctx->budgetManager->getPlanetFleetCredits($pId)
                : array('metal' => (float)$pData['metal'], 'crystal' => (float)$pData['crystal'], 'deuterium' => (float)$pData['deuterium']);
            $rawDefCredits = isset($this->ctx->budgetManager)
                ? $this->ctx->budgetManager->getPlanetDefenseCredits($pId)
                : array('metal' => (float)$pData['metal'], 'crystal' => (float)$pData['crystal'], 'deuterium' => (float)$pData['deuterium']);

            $isEmergency = ($threatIncoming || $isSiegeActive);

            $fleetCredits = $this->calculateCycleSpendBudget($pId, $pData, 'fleet', $rawFleetCredits, $lastFleetRecruit, $isEmergency);
            $defCredits   = $this->calculateCycleSpendBudget($pId, $pData, 'defense', $rawDefCredits, $lastDefRecruit, $isEmergency);

            // Reserva obligatoria para Colonizadores (#208)
            if (class_exists('PlayerUtil')) {
                $maxPlanets = (int)PlayerUtil::maxPlanetCount($this->ctx->user);
                $curPlanets = count($allBodies);
                if ($curPlanets < $maxPlanets && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 208, array())) {
                    $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - 10000.0);
                    $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - 20000.0);
                    $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - 10000.0);
                }
            }

            $fleetLock = (isset($this->ctx->budgetManager) && method_exists($this->ctx->budgetManager, 'getSavingsLockForCategory'))
                ? $this->ctx->budgetManager->getSavingsLockForCategory($pId, 'fleet') : null;
            $lockedDefResFleet = ($fleetLock && !empty($fleetLock['deficit_resource'])) ? (int)$fleetLock['deficit_resource'] : 0;
            $lockedDefAmountFleet = ($fleetLock && isset($fleetLock['cost'][$lockedDefResFleet])) ? (float)$fleetLock['cost'][$lockedDefResFleet] : 0.0;

            $defLock = (isset($this->ctx->budgetManager) && method_exists($this->ctx->budgetManager, 'getSavingsLockForCategory'))
                ? $this->ctx->budgetManager->getSavingsLockForCategory($pId, 'defense') : null;
            $lockedDefResDef = ($defLock && !empty($defLock['deficit_resource'])) ? (int)$defLock['deficit_resource'] : 0;
            $lockedDefAmountDef = ($defLock && isset($defLock['cost'][$lockedDefResDef])) ? (float)$defLock['cost'][$lockedDefResDef] : 0.0;

            // =========================================================================
            // --- 2. GESTIÓN DEFENSIVA MULTICAPA (Prioridad 0, 1, 2) ---
            // =========================================================================
            if ($canRunDefense) {
                $capasMaster = $this->getDefenseCapas();

                // --- Prioridad 0: Cúpulas Únicas (407, 408, 409, 419) ---
                foreach ($capasMaster['domes'] as $domeId) {
                    if ($queuedJobs >= $maxRegularSlots) break;
                    $curDome = isset($pData[$resource[$domeId]]) ? (int)$pData[$resource[$domeId]] : 0;
                    if ($curDome == 0 && !$this->isUnitInQueue($pData, $domeId) && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $domeId, array())) {
                        $batchDome = $this->getConstructibleBatch($pData, $defCredits, $domeId, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                        if ($batchDome >= 1) {
                            $enqueuedDome = (int)$this->gateway->constructUnits($pId, $domeId, 1);
                            if ($enqueuedDome >= 1) {
                                $recruited[$domeId] = isset($recruited[$domeId]) ? $recruited[$domeId] + $enqueuedDome : $enqueuedDome;
                                $defRecruitedTotal += $enqueuedDome;
                                $queuedJobs++;

                                $costMDome = (float)(isset($pricelist[$domeId]['cost'][901]) ? $pricelist[$domeId]['cost'][901] : 0);
                                $costCDome = (float)(isset($pricelist[$domeId]['cost'][902]) ? $pricelist[$domeId]['cost'][902] : 0);
                                $costDDome = (float)(isset($pricelist[$domeId]['cost'][903]) ? $pricelist[$domeId]['cost'][903] : 0);
                                $defCredits['metal']     = max(0.0, $defCredits['metal'] - $costMDome);
                                $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - $costCDome);
                                $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - $costDDome);

                                $pData = $this->refreshPlanetData($pId, $pData);
                                $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                            }
                        }
                    }
                }

                // --- Prioridad 1: Silo de Misiles (502 ABM y 503 MIP) ---
                $siloLevel = isset($pData[$resource[44]]) ? (int)$pData[$resource[44]] : 0;
                if ($queuedJobs < $maxQueueJobs && $siloLevel >= 2) {
                    $rocketsCapacity = BuildFunctions::getMaxConstructibleRockets($this->ctx->user, $pData);
                    $freeSpaces = isset($rocketsCapacity[502]) ? (int)$rocketsCapacity[502] : 0;
                    $cur502 = isset($pData[$resource[502]]) ? (int)$pData[$resource[502]] : 0;
                    $cur503 = isset($pData[$resource[503]]) ? (int)$pData[$resource[503]] : 0;
                    $impulse = isset($this->ctx->user[$resource[117]]) ? (int)$this->ctx->user[$resource[117]] : 0;
                    $totalSiloMax = $cur502 + $cur503 + $freeSpaces;

                    $canBuild502 = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 502, array());
                    $canBuild503 = ($siloLevel >= 4 && $impulse >= 1 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 503, array()));

                    $pers = isset($this->ctx->personality->name) ? $this->ctx->personality->name : 'balanced';
                    if ($canBuild502 && $canBuild503) {
                        if ($isSiegeActive) {
                            $target502 = (int)floor($totalSiloMax * 0.30);
                            $target503 = (int)floor($totalSiloMax * 0.70);
                        } elseif ($pers === 'turtle' || $pers === 'miner' || $pers === BotPersonality::DEFENSIVE || $pers === BotPersonality::ECONOMIC) {
                            $target502 = (int)floor($totalSiloMax * 0.85);
                            $target503 = (int)floor($totalSiloMax * 0.15);
                        } else {
                            $target502 = (int)floor($totalSiloMax * 0.50);
                            $target503 = (int)floor($totalSiloMax * 0.50);
                        }
                    } elseif ($canBuild502) {
                        $target502 = $totalSiloMax;
                        $target503 = 0;
                    } else {
                        $target502 = 0;
                        $target503 = 0;
                    }

                    $deficit502 = max(0, $target502 - $cur502);
                    $deficit503 = max(0, $target503 - $cur503);

                    if ($deficit502 > 0 && $freeSpaces > 0 && $queuedJobs < $maxQueueJobs && !$this->isUnitInQueue($pData, 502)) {
                        $batch502 = $this->getConstructibleBatch($pData, $defCredits, 502, $queuedJobs, $maxQueueJobs, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, true, $lockedDefAmountDef);
                        $batch502 = min($batch502, $deficit502, $freeSpaces);
                        $enqueued502 = ($batch502 > 0) ? (int)$this->gateway->constructUnits($pId, 502, $batch502) : 0;
                        if ($enqueued502 > 0) {
                            $recruited[502] = isset($recruited[502]) ? $recruited[502] + $enqueued502 : $enqueued502;
                            $defRecruitedTotal += $enqueued502;
                            $queuedJobs++;
                            $freeSpaces -= $enqueued502;
                            $defCredits['metal']     = max(0.0, $defCredits['metal'] - ((float)($pricelist[502]['cost'][901] ?? 0) * $enqueued502));
                            $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - ((float)($pricelist[502]['cost'][902] ?? 0) * $enqueued502));
                            $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - ((float)($pricelist[502]['cost'][903] ?? 0) * $enqueued502));
                            $pData = $this->refreshPlanetData($pId, $pData);
                            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                        }
                    }

                    if ($deficit503 > 0 && $freeSpaces > 0 && $canBuild503 && $queuedJobs < $maxRegularSlots && !$this->isUnitInQueue($pData, 503)) {
                        $batch503 = $this->getConstructibleBatch($pData, $defCredits, 503, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                        $batch503 = min($batch503, $deficit503, $freeSpaces);
                        $enqueued503 = ($batch503 > 0) ? (int)$this->gateway->constructUnits($pId, 503, $batch503) : 0;
                        if ($enqueued503 > 0) {
                            $recruited[503] = isset($recruited[503]) ? $recruited[503] + $enqueued503 : $enqueued503;
                            $defRecruitedTotal += $enqueued503;
                            $queuedJobs++;
                            $freeSpaces -= $enqueued503;
                            $defCredits['metal']     = max(0.0, $defCredits['metal'] - ((float)($pricelist[503]['cost'][901] ?? 0) * $enqueued503));
                            $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - ((float)($pricelist[503]['cost'][902] ?? 0) * $enqueued503));
                            $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - ((float)($pricelist[503]['cost'][903] ?? 0) * $enqueued503));
                            $pData = $this->refreshPlanetData($pId, $pData);
                            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                        }
                    }
                }

                // --- Prioridad 2: Artillería Defensiva Multicapa (Capas 1, 2, 3, 4) ---
                if ($queuedJobs < $maxRegularSlots) {
                    $defBreakdown = $this->calculateDefenseBreakdown($pData);
                    $totDefMSE = max(1.0, $defBreakdown['total_mse']);
                    $capa1Ratio = $defBreakdown['capa1_mse'] / $totDefMSE;
                    $capa2Ratio = $defBreakdown['capa2_mse'] / $totDefMSE;

                    // Unidades accesibles por capa
                    $fodderDefId = $this->getFodderDefenseId($pData);

                    $accCapa2 = array();
                    foreach ($capasMaster['capa2'] as $u2) {
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $u2, array())) {
                            $accCapa2[] = $u2;
                        }
                    }
                    usort($accCapa2, function($a, $b) {
                        return BotEconomyValuator::getElementPriceMSE($b) <=> BotEconomyValuator::getElementPriceMSE($a);
                    });

                    $accCapa3 = array();
                    foreach ($capasMaster['capa3'] as $u3) {
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $u3, array())) {
                            $accCapa3[] = $u3;
                        }
                    }
                    usort($accCapa3, function($a, $b) {
                        return BotEconomyValuator::getElementPriceMSE($b) <=> BotEconomyValuator::getElementPriceMSE($a);
                    });

                    $accCapa4 = array();
                    foreach ($capasMaster['capa4'] as $u4) {
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $u4, array())) {
                            $accCapa4[] = $u4;
                        }
                    }
                    usort($accCapa4, function($a, $b) {
                        return BotEconomyValuator::getElementPriceMSE($b) <=> BotEconomyValuator::getElementPriceMSE($a);
                    });

                    $hasHigherDefCapas = (!empty($accCapa3) || !empty($accCapa4));

                    // Cuotas y Candados Anti-Monopolio:
                    // Capa 1: Fodder
                    if ($fodderDefId <= 0) {
                        $shareCapa1 = 0.0;
                    } elseif ($capa1Ratio >= 0.18 && ($hasHigherDefCapas || !empty($accCapa2))) {
                        $shareCapa1 = 0.0; // Techo estricto 18%
                    } elseif ($capa1Ratio < 0.10) {
                        $shareCapa1 = 0.24; // Suelo de mantenimiento de pantalla
                    } else {
                        $shareCapa1 = 0.14;
                    }

                    // Capa 2: Cortina Iónica (#405) - Candado anti-monopolio al 25%
                    if (empty($accCapa2)) {
                        $shareCapa2 = 0.0;
                    } elseif ($capa2Ratio >= 0.25 && $hasHigherDefCapas) {
                        $shareCapa2 = 0.0; // ¡Candado activado! Corta en seco el spam de #405
                    } else {
                        $shareCapa2 = 0.20;
                    }

                    $shareCapa3 = !empty($accCapa3) ? 0.32 : 0.0;
                    $shareCapa4 = !empty($accCapa4) ? 0.34 : 0.0;

                    $totActiveDefShares = $shareCapa1 + $shareCapa2 + $shareCapa3 + $shareCapa4;
                    if ($totActiveDefShares > 0) {
                        $shareCapa1 /= $totActiveDefShares;
                        $shareCapa2 /= $totActiveDefShares;
                        $shareCapa3 /= $totActiveDefShares;
                        $shareCapa4 /= $totActiveDefShares;
                    } elseif ($fodderDefId > 0) {
                        $shareCapa1 = 1.0;
                    }

                    $initialDefCredits = $defCredits;

                    // Despliegue por Capas: de mayor calibre a menor (Capa 4 -> 3 -> 2 -> 1)
                    // Capa 4: Artillería Pesada de Gravitón (Dual-Tier 70/30)
                    if ($shareCapa4 > 0 && !empty($accCapa4) && $queuedJobs < $maxRegularSlots) {
                        $c4Budget = array(
                            'metal'     => $initialDefCredits['metal'] * $shareCapa4,
                            'crystal'   => $initialDefCredits['crystal'] * $shareCapa4,
                            'deuterium' => $initialDefCredits['deuterium'] * $shareCapa4,
                        );
                        $unitsC4 = (count($accCapa4) >= 2) ? array($accCapa4[0] => 0.70, $accCapa4[1] => 0.30) : array($accCapa4[0] => 1.00);
                        foreach ($unitsC4 as $uC4 => $uFraction) {
                            if ($queuedJobs >= $maxRegularSlots) break;
                            $uCreds = array(
                                'metal'     => min($defCredits['metal'], $c4Budget['metal'] * $uFraction),
                                'crystal'   => min($defCredits['crystal'], $c4Budget['crystal'] * $uFraction),
                                'deuterium' => min($defCredits['deuterium'], $c4Budget['deuterium'] * $uFraction),
                            );
                            $bC4 = $this->getConstructibleBatch($pData, $uCreds, $uC4, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                            if ($bC4 > 0) {
                                $enqC4 = (int)$this->gateway->constructUnits($pId, $uC4, $bC4);
                                if ($enqC4 > 0) {
                                    $recruited[$uC4] = isset($recruited[$uC4]) ? $recruited[$uC4] + $enqC4 : $enqC4;
                                    $defRecruitedTotal += $enqC4;
                                    $queuedJobs++;
                                    $defCredits['metal']     = max(0.0, $defCredits['metal'] - ((float)($pricelist[$uC4]['cost'][901] ?? 0) * $enqC4));
                                    $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - ((float)($pricelist[$uC4]['cost'][902] ?? 0) * $enqC4));
                                    $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - ((float)($pricelist[$uC4]['cost'][903] ?? 0) * $enqC4));
                                    $pData = $this->refreshPlanetData($pId, $pData);
                                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                                }
                            }
                        }
                    }

                    // Capa 3: Artillería de Plasma (Dual-Tier 70/30)
                    if ($shareCapa3 > 0 && !empty($accCapa3) && $queuedJobs < $maxRegularSlots) {
                        $c3Budget = array(
                            'metal'     => $initialDefCredits['metal'] * $shareCapa3,
                            'crystal'   => $initialDefCredits['crystal'] * $shareCapa3,
                            'deuterium' => $initialDefCredits['deuterium'] * $shareCapa3,
                        );
                        $unitsC3 = (count($accCapa3) >= 2) ? array($accCapa3[0] => 0.70, $accCapa3[1] => 0.30) : array($accCapa3[0] => 1.00);
                        foreach ($unitsC3 as $uC3 => $uFraction) {
                            if ($queuedJobs >= $maxRegularSlots) break;
                            $uCreds = array(
                                'metal'     => min($defCredits['metal'], $c3Budget['metal'] * $uFraction),
                                'crystal'   => min($defCredits['crystal'], $c3Budget['crystal'] * $uFraction),
                                'deuterium' => min($defCredits['deuterium'], $c3Budget['deuterium'] * $uFraction),
                            );
                            $bC3 = $this->getConstructibleBatch($pData, $uCreds, $uC3, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                            if ($bC3 > 0) {
                                $enqC3 = (int)$this->gateway->constructUnits($pId, $uC3, $bC3);
                                if ($enqC3 > 0) {
                                    $recruited[$uC3] = isset($recruited[$uC3]) ? $recruited[$uC3] + $enqC3 : $enqC3;
                                    $defRecruitedTotal += $enqC3;
                                    $queuedJobs++;
                                    $defCredits['metal']     = max(0.0, $defCredits['metal'] - ((float)($pricelist[$uC3]['cost'][901] ?? 0) * $enqC3));
                                    $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - ((float)($pricelist[$uC3]['cost'][902] ?? 0) * $enqC3));
                                    $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - ((float)($pricelist[$uC3]['cost'][903] ?? 0) * $enqC3));
                                    $pData = $this->refreshPlanetData($pId, $pData);
                                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                                }
                            }
                        }
                    }

                    // Capa 2: Cortina Iónica
                    if ($shareCapa2 > 0 && !empty($accCapa2) && $queuedJobs < $maxRegularSlots) {
                        $topIonId = $accCapa2[0];
                        $c2Creds = array(
                            'metal'     => min($defCredits['metal'], $initialDefCredits['metal'] * $shareCapa2),
                            'crystal'   => min($defCredits['crystal'], $initialDefCredits['crystal'] * $shareCapa2),
                            'deuterium' => min($defCredits['deuterium'], $initialDefCredits['deuterium'] * $shareCapa2),
                        );
                        $bC2 = $this->getConstructibleBatch($pData, $c2Creds, $topIonId, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                        if ($bC2 > 0) {
                            $enqC2 = (int)$this->gateway->constructUnits($pId, $topIonId, $bC2);
                            if ($enqC2 > 0) {
                                $recruited[$topIonId] = isset($recruited[$topIonId]) ? $recruited[$topIonId] + $enqC2 : $enqC2;
                                $defRecruitedTotal += $enqC2;
                                $queuedJobs++;
                                $defCredits['metal']     = max(0.0, $defCredits['metal'] - ((float)($pricelist[$topIonId]['cost'][901] ?? 0) * $enqC2));
                                $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - ((float)($pricelist[$topIonId]['cost'][902] ?? 0) * $enqC2));
                                $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - ((float)($pricelist[$topIonId]['cost'][903] ?? 0) * $enqC2));
                                $pData = $this->refreshPlanetData($pId, $pData);
                                $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                            }
                        }
                    }

                    // Capa 1: Fodder Exclusivo
                    if ($shareCapa1 > 0 && $fodderDefId > 0 && $queuedJobs < $maxRegularSlots) {
                        $c1Creds = array(
                            'metal'     => min($defCredits['metal'], $initialDefCredits['metal'] * $shareCapa1),
                            'crystal'   => min($defCredits['crystal'], $initialDefCredits['crystal'] * $shareCapa1),
                            'deuterium' => min($defCredits['deuterium'], $initialDefCredits['deuterium'] * $shareCapa1),
                        );
                        $bC1 = $this->getConstructibleBatch($pData, $c1Creds, $fodderDefId, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                        if ($bC1 > 0) {
                            $enqC1 = (int)$this->gateway->constructUnits($pId, $fodderDefId, $bC1);
                            if ($enqC1 > 0) {
                                $recruited[$fodderDefId] = isset($recruited[$fodderDefId]) ? $recruited[$fodderDefId] + $enqC1 : $enqC1;
                                $defRecruitedTotal += $enqC1;
                                $queuedJobs++;
                                $defCredits['metal']     = max(0.0, $defCredits['metal'] - ((float)($pricelist[$fodderDefId]['cost'][901] ?? 0) * $enqC1));
                                $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - ((float)($pricelist[$fodderDefId]['cost'][902] ?? 0) * $enqC1));
                                $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - ((float)($pricelist[$fodderDefId]['cost'][903] ?? 0) * $enqC1));
                                $pData = $this->refreshPlanetData($pId, $pData);
                                $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                            }
                        }
                    }
                }
            }

            // =========================================================================
            // --- 3. RECLUTAMIENTO DE FLOTA COMBINADA (Ranuras 1 a 5) ---
            // =========================================================================
            if ($canRunFleet && $queuedJobs < $maxRegularSlots) {
                // Sondas de Espionaje: buffer mínimo de 60 sondas
                $curProbes = isset($pData[$resource[210]]) ? (int)$pData[$resource[210]] : 0;
                if ($curProbes < 60 && $queuedJobs < $maxRegularSlots && !$this->isUnitInQueue($pData, 210) && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 210, array())) {
                    $probeCreds = array(
                        'metal'     => $fleetCredits['metal'] * 0.02,
                        'crystal'   => $fleetCredits['crystal'] * 0.02,
                        'deuterium' => $fleetCredits['deuterium'] * 0.02,
                    );
                    $bProbe = $this->getConstructibleBatch($pData, $probeCreds, 210, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResFleet, false, $lockedDefAmountFleet);
                    $bProbe = min($bProbe, 60 - $curProbes);
                    if ($bProbe > 0) {
                        $enqProbe = (int)$this->gateway->constructUnits($pId, 210, $bProbe);
                        if ($enqProbe > 0) {
                            $recruited[210] = isset($recruited[210]) ? $recruited[210] + $enqProbe : $enqProbe;
                            $fleetRecruitedTotal += $enqProbe;
                            $queuedJobs++;
                            $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - ((float)($pricelist[210]['cost'][901] ?? 0) * $enqProbe));
                            $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - ((float)($pricelist[210]['cost'][902] ?? 0) * $enqProbe));
                            $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - ((float)($pricelist[210]['cost'][903] ?? 0) * $enqProbe));
                            $pData = $this->refreshPlanetData($pId, $pData);
                            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                        }
                    }
                }

                // Cargueros mínimos de colonia (Buffer 30 unidades) respetando techo imperial
                if (!$cargoCapReached && $queuedJobs < $maxRegularSlots) {
                    $curTrans = (int)($pData[$resource[203]] ?? 0) + (int)($pData[$resource[202]] ?? 0) + (int)($pData[$resource[217]] ?? 0);
                    if ($curTrans < 30 && !$this->isUnitInQueue($pData, 203) && !$this->isUnitInQueue($pData, 202)) {
                        $uTransId = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 203, array()) ? 203 : (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 202, array()) ? 202 : 0);
                        if ($uTransId > 0) {
                            $transCreds = array(
                                'metal'     => $fleetCredits['metal'] * 0.025,
                                'crystal'   => $fleetCredits['crystal'] * 0.025,
                                'deuterium' => $fleetCredits['deuterium'] * 0.025,
                            );
                            $bTrans = $this->getConstructibleBatch($pData, $transCreds, $uTransId, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResFleet, false, $lockedDefAmountFleet);
                            $bTrans = min($bTrans, 30 - $curTrans);
                            if ($bTrans > 0) {
                                $enqTrans = (int)$this->gateway->constructUnits($pId, $uTransId, $bTrans);
                                if ($enqTrans > 0) {
                                    $recruited[$uTransId] = isset($recruited[$uTransId]) ? $recruited[$uTransId] + $enqTrans : $enqTrans;
                                    $fleetRecruitedTotal += $enqTrans;
                                    $queuedJobs++;
                                    $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - ((float)($pricelist[$uTransId]['cost'][901] ?? 0) * $enqTrans));
                                    $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - ((float)($pricelist[$uTransId]['cost'][902] ?? 0) * $enqTrans));
                                    $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - ((float)($pricelist[$uTransId]['cost'][903] ?? 0) * $enqTrans));
                                    $pData = $this->refreshPlanetData($pId, $pData);
                                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                                }
                            }
                        }
                    }
                }

                // Recicladores mínimos de colonia (Buffer 15 unidades) respetando techo imperial estricto (4% y 1,500 #219)
                if (!$recyclerCapReached && $queuedJobs < $maxRegularSlots) {
                    $curRecs = (int)($pData[$resource[209]] ?? 0) + (int)($pData[$resource[219]] ?? 0);
                    if ($curRecs < 15 && !$this->isUnitInQueue($pData, 209) && !$this->isUnitInQueue($pData, 219)) {
                        $uRecId = ($empireStats['count_219'] < 1500 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 219, array())) ? 219 : (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 209, array()) ? 209 : 0);
                        if ($uRecId > 0) {
                            $recCreds = array(
                                'metal'     => $fleetCredits['metal'] * 0.025,
                                'crystal'   => $fleetCredits['crystal'] * 0.025,
                                'deuterium' => $fleetCredits['deuterium'] * 0.025,
                            );
                            $bRec = $this->getConstructibleBatch($pData, $recCreds, $uRecId, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResFleet, false, $lockedDefAmountFleet);
                            $bRec = min($bRec, 15 - $curRecs);
                            if ($uRecId === 219) {
                                $bRec = min($bRec, max(0, 1500 - $empireStats['count_219']));
                            }
                            if ($bRec > 0) {
                                $enqRec = (int)$this->gateway->constructUnits($pId, $uRecId, $bRec);
                                if ($enqRec > 0) {
                                    $recruited[$uRecId] = isset($recruited[$uRecId]) ? $recruited[$uRecId] + $enqRec : $enqRec;
                                    $fleetRecruitedTotal += $enqRec;
                                    if ($uRecId === 219) {
                                        $empireStats['count_219'] += $enqRec;
                                        if ($empireStats['count_219'] >= 1500) {
                                            $recyclerCapReached = true;
                                        }
                                    }
                                    $queuedJobs++;
                                    $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - ((float)($pricelist[$uRecId]['cost'][901] ?? 0) * $enqRec));
                                    $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - ((float)($pricelist[$uRecId]['cost'][902] ?? 0) * $enqRec));
                                    $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - ((float)($pricelist[$uRecId]['cost'][903] ?? 0) * $enqRec));
                                    $pData = $this->refreshPlanetData($pId, $pData);
                                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                                }
                            }
                        }
                    }
                }

                // --- 5 Ranuras Doctrinales de Combate (Slots 1 a 5) ---
                if ($queuedJobs < $maxRegularSlots) {
                    $fodderShipId = $this->resolveFodderShip($pData);

                    // Filtrar naves accesibles en Slots 2, 3, 4, 5
                    $accessibleWarshipsBySlot = array();
                    $hasAnyCombatWarship = false;
                    foreach ($doctrineSpecs['slots'] as $sNum => $sData) {
                        $accessibleWarshipsBySlot[$sNum] = array();
                        foreach ($sData['units'] as $shipCandidate) {
                            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $shipCandidate, array())) {
                                $accessibleWarshipsBySlot[$sNum][] = $shipCandidate;
                                $hasAnyCombatWarship = true;
                            }
                        }
                        // Ordenar por valor MSE descendente (mayor tier primero)
                        usort($accessibleWarshipsBySlot[$sNum], function($a, $b) {
                            return BotEconomyValuator::getElementPriceMSE($b) <=> BotEconomyValuator::getElementPriceMSE($a);
                        });
                    }

                    // Cuota de Slot 1 (Fodder Exclusivo)
                    if ($fodderShipId <= 0) {
                        $slot1Share = 0.0;
                    } elseif ($fodderRatio >= 0.18 && $hasAnyCombatWarship) {
                        $slot1Share = 0.0; // ¡Techo estricto 18%! Todo el presupuesto va a Slots 2-5
                    } elseif ($fodderRatio < 0.10) {
                        $slot1Share = 0.24; // Suelo de mantenimiento de pantalla
                    } else {
                        $slot1Share = 0.14;
                    }

                    // Cuotas de Slots 2 a 5
                    $slotShares = array();
                    foreach ($doctrineSpecs['slots'] as $sNum => $sData) {
                        if (!empty($accessibleWarshipsBySlot[$sNum])) {
                            $slotShares[$sNum] = (float)$sData['base_share'];
                        } else {
                            $slotShares[$sNum] = 0.0;
                        }
                    }

                    $totalActiveCombatShares = $slot1Share + array_sum($slotShares);
                    if ($totalActiveCombatShares > 0) {
                        $slot1Share /= $totalActiveCombatShares;
                        foreach ($slotShares as $sNum => $sh) {
                            $slotShares[$sNum] = $sh / $totalActiveCombatShares;
                        }
                    } elseif ($fodderShipId > 0) {
                        $slot1Share = 1.0;
                    }

                    $initialCombatPool = $fleetCredits;

                    // Despliegue en orden de potencia: Slot 5 -> 4 -> 3 -> 2 -> 1
                    foreach (array(5, 4, 3, 2) as $sNum) {
                        if ($queuedJobs >= $maxRegularSlots) break;
                        $sh = $slotShares[$sNum] ?? 0.0;
                        if ($sh <= 0 || empty($accessibleWarshipsBySlot[$sNum])) continue;

                        $slotBudget = array(
                            'metal'     => $initialCombatPool['metal'] * $sh,
                            'crystal'   => $initialCombatPool['crystal'] * $sh,
                            'deuterium' => $initialCombatPool['deuterium'] * $sh,
                        );

                        $accUnits = $accessibleWarshipsBySlot[$sNum];
                        $unitsInSlot = (count($accUnits) >= 2) ? array($accUnits[0] => 0.70, $accUnits[1] => 0.30) : array($accUnits[0] => 1.00);

                        foreach ($unitsInSlot as $wId => $uFraction) {
                            if ($queuedJobs >= $maxRegularSlots) break;
                            $wCreds = array(
                                'metal'     => min($fleetCredits['metal'], $slotBudget['metal'] * $uFraction),
                                'crystal'   => min($fleetCredits['crystal'], $slotBudget['crystal'] * $uFraction),
                                'deuterium' => min($fleetCredits['deuterium'], $slotBudget['deuterium'] * $uFraction),
                            );

                            $bWar = $this->getConstructibleBatch($pData, $wCreds, $wId, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResFleet, false, $lockedDefAmountFleet);
                            if ($bWar > 0) {
                                $enqWar = (int)$this->gateway->constructUnits($pId, $wId, $bWar);
                                if ($enqWar > 0) {
                                    $recruited[$wId] = isset($recruited[$wId]) ? $recruited[$wId] + $enqWar : $enqWar;
                                    $fleetRecruitedTotal += $enqWar;
                                    $queuedJobs++;
                                    $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - ((float)($pricelist[$wId]['cost'][901] ?? 0) * $enqWar));
                                    $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - ((float)($pricelist[$wId]['cost'][902] ?? 0) * $enqWar));
                                    $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - ((float)($pricelist[$wId]['cost'][903] ?? 0) * $enqWar));
                                    $pData = $this->refreshPlanetData($pId, $pData);
                                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                                }
                            }
                        }
                    }

                    // Slot 1: Fodder Exclusivo
                    if ($slot1Share > 0 && $fodderShipId > 0 && $queuedJobs < $maxRegularSlots) {
                        $fodderBudget = array(
                            'metal'     => min($fleetCredits['metal'], $initialCombatPool['metal'] * $slot1Share),
                            'crystal'   => min($fleetCredits['crystal'], $initialCombatPool['crystal'] * $slot1Share),
                            'deuterium' => min($fleetCredits['deuterium'], $initialCombatPool['deuterium'] * $slot1Share),
                        );
                        $bFodder = $this->getConstructibleBatch($pData, $fodderBudget, $fodderShipId, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResFleet, false, $lockedDefAmountFleet);
                        if ($bFodder > 0) {
                            $enqFodder = (int)$this->gateway->constructUnits($pId, $fodderShipId, $bFodder);
                            if ($enqFodder > 0) {
                                $recruited[$fodderShipId] = isset($recruited[$fodderShipId]) ? $recruited[$fodderShipId] + $enqFodder : $enqFodder;
                                $fleetRecruitedTotal += $enqFodder;
                                $queuedJobs++;
                                $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - ((float)($pricelist[$fodderShipId]['cost'][901] ?? 0) * $enqFodder));
                                $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - ((float)($pricelist[$fodderShipId]['cost'][902] ?? 0) * $enqFodder));
                                $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - ((float)($pricelist[$fodderShipId]['cost'][903] ?? 0) * $enqFodder));
                                $pData = $this->refreshPlanetData($pId, $pData);
                                $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                            }
                        }
                    }
                }
            }
        }

        // Marcas temporales desacopladas en BD
        $now = BotClock::get();
        $updates = array();
        if ($fleetRecruitedTotal > 0 || $canRunFleet) {
            $updates['last_fleet_recruit'] = $now;
        }
        if ($defRecruitedTotal > 0 || $canRunDefense) {
            $updates['last_defense_recruit'] = $now;
        }
        if (!empty($recruited)) {
            $updates['last_recruit'] = $now;
        }
        if (!empty($updates)) {
            $this->ctx->updateBotRow($updates);
        }

        return $recruited;
    }

    /**
     * Métodos auxiliares y de compatibilidad
     */
    public function selectTacticalWarships(array $pData, $attackBudget, array $doctrine)
    {
        $slots = $this->getDoctrineWarshipSlots()['slots'];
        $selected = array();
        foreach (array(5, 4, 3, 2) as $sNum) {
            foreach ($slots[$sNum]['units'] as $uId) {
                if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $uId, array())) {
                    $selected[] = $uId;
                    break;
                }
            }
        }
        return $selected;
    }

    public function evaluateDefenseUnits(array $pData, $categoryFilter = null)
    {
        $breakdown = $this->calculateDefenseBreakdown($pData);
        return array('breakdown' => $breakdown);
    }

    public function getBestIonicFodderDefense(array $pData)
    {
        return $this->getFodderDefenseId($pData);
    }

    public function getEliteDefenseTriad(array $pData)
    {
        $capas = $this->getDefenseCapas();
        $elite = array();
        foreach (array_merge($capas['capa4'], $capas['capa3']) as $uId) {
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $uId, array())) {
                $elite[] = $uId;
                if (count($elite) >= 3) break;
            }
        }
        return $elite;
    }
}
