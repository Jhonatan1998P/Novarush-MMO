<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';

/**
 * NovaRush Bot AI v4.0 - Force Planner
 *
 * Arquitectura Militar de NovaRush v4.0 (Huella Militar Única por Slot):
 * 1. Asignación Única e Inmutable de por Vida por Slot:
 *    - Cada bot elige 1 SOLA UNIDAD de por vida para cada Slot de Flota (Slots 1 a 5) y Defensa (Slots 1 a 4).
 *    - Persistencia en uni1_bots (doctrine_fodder, doctrine_light, doctrine_medium, doctrine_heavy, doctrine_special, def_slot1..def_slot4).
 *    - 100% de compatibilidad con la raza del bot (1501 Fotorianos, 1502 Sinuria, 1503 Terran, 1504 Kratonianos).
 *    - Diversificación determinista mediante hash de botId.
 * 2. Slot 0 — Unidades de Apoyo Logístico (Máximo 2.5% del Budget de Flota):
 *    - Construye Cargueros (#217 -> #203 -> #202) y Recicladores (#219 -> #209), más buffer de 60 Sondas (#210).
 *    - Techo imperial duro: (cargoMSE + recyclerMSE) / totalFleetMSE <= 0.025, con piso operativo de emergencia.
 *    - Umbrales realistas en upgradeCivilianFleets: desguace de #202 con > 30 y #209 con > 20.
 * 3. Ratios Duros de Presupuesto:
 *    - Flota: Slot 0 = 2.5%, Slot 1 (Fodder) = 17.5%, Slot 2 (Light) = 20.0%, Slot 3 (Medium) = 10.0%, Slot 4 (Heavy) = 40.0%, Slot 5 (Special) = 10.0% (Total = 100.0%).
 *    - Defensa: Slot 1 (Fodder) = 18.0%, Slot 2 (Ion) = 22.0%, Slot 3 (Plasma) = 30.0%, Slot 4 (Graviton) = 30.0% (Total = 100.0%).
 * 4. Motor de Rollover (Cascada Descendente) y Modo Ahorro:
 *    - Flota: Evaluación en orden 5 -> 4 -> 3 -> 2 -> 1.
 *    - Defensa: Evaluación en orden 4 -> 3 -> 2 -> 1.
 *    - En cada ranura se evalúa EXCLUSIVAMENTE la unidad asignada del bot.
 *    - Si la unidad pesada no alcanza por costo pero es accesible, se activa Modo Ahorro (no transfiere su cuota base al rollover).
 *    - El cambio sobrante de unidades construidas o slots no accesibles desciende al siguiente slot.
 * 5. Flujo Activo en calculateCycleSpendBudget (drainRate: 0.35 a 1.00).
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
        if (isset($this->ctx->user['race']) && in_array((int)$this->ctx->user['race'], array(1501, 1502, 1503, 1504))) {
            return (int)$this->ctx->user['race'];
        }

        foreach (array(1501, 1502, 1503, 1504) as $rId) {
            if (!empty($this->ctx->user['race_' . $rId])) {
                return $rId;
            }
        }

        $pers = isset($this->ctx->personality->name) ? strtolower($this->ctx->personality->name) : 'balanced';
        if ($pers === 'aggressive' || $pers === 'raider' || (defined('BotPersonality::AGGRESSIVE') && $pers === BotPersonality::AGGRESSIVE)) {
            return 1504; // Kratonianos
        } elseif ($pers === 'turtle' || $pers === 'defensive' || (defined('BotPersonality::DEFENSIVE') && $pers === BotPersonality::DEFENSIVE)) {
            return 1502; // Sinuria
        } elseif ($pers === 'miner' || $pers === 'economic' || (defined('BotPersonality::ECONOMIC') && $pers === BotPersonality::ECONOMIC)) {
            return 1501; // Fotorianos
        }

        $raceMap = array(0 => 1504, 1 => 1501, 2 => 1502, 3 => 1503);
        $vIdx = $this->getBotVariantIndex();
        return isset($raceMap[$vIdx]) ? $raceMap[$vIdx] : 1504;
    }

    /**
     * Comprueba si una unidad es 100% compatible con la raza del bot
     */
    public function isUnitCompatibleWithBotRace($unitId, $race = null)
    {
        global $requeriments;
        if ($race === null) {
            $race = $this->getBotFactionRace();
        }
        $req = isset($requeriments[$unitId]) ? $requeriments[$unitId] : array();
        foreach (array(1501, 1502, 1503, 1504) as $r) {
            if (isset($req[$r])) {
                return ($r === (int)$race);
            }
        }
        return true;
    }

    /**
     * Calcula el Ratio Calidad/Precio (C/P) de una unidad:
     * (Ataque + Escudo + ((Metal + Cristal) * 0.10)) / max(1.0, MSE)
     */
    public function calculateQualityPriceRatio($unitId)
    {
        global $pricelist, $CombatCaps;
        $costM = (float)(isset($pricelist[$unitId]['cost'][901]) ? $pricelist[$unitId]['cost'][901] : 0);
        $costC = (float)(isset($pricelist[$unitId]['cost'][902]) ? $pricelist[$unitId]['cost'][902] : 0);
        $costD = (float)(isset($pricelist[$unitId]['cost'][903]) ? $pricelist[$unitId]['cost'][903] : 0);
        $mse   = max(1.0, $costM + 1.5 * $costC + 3.0 * $costD);
        $att   = (float)(isset($CombatCaps[$unitId]['attack']) ? $CombatCaps[$unitId]['attack'] : 0);
        $def   = (float)(isset($CombatCaps[$unitId]['shield']) ? $CombatCaps[$unitId]['shield'] : 0);
        $hull  = ($costM + $costC) * 0.10;
        return ($att + $def + $hull) / $mse;
    }

    /**
     * Asignación Canónica de por Vida de Ranuras de Flota (Slots 1 a 5)
     */
    public function getOrAssignFleetDoctrine()
    {
        $botId = (int)$this->ctx->botId;
        $race  = $this->getBotFactionRace();

        $heavy   = isset($this->ctx->botRow['doctrine_heavy']) ? (int)$this->ctx->botRow['doctrine_heavy'] : 0;
        $medium  = isset($this->ctx->botRow['doctrine_medium']) ? (int)$this->ctx->botRow['doctrine_medium'] : 0;
        $light   = isset($this->ctx->botRow['doctrine_light']) ? (int)$this->ctx->botRow['doctrine_light'] : 0;
        $fodder  = isset($this->ctx->botRow['doctrine_fodder']) ? (int)$this->ctx->botRow['doctrine_fodder'] : 0;
        $special = isset($this->ctx->botRow['doctrine_special']) ? (int)$this->ctx->botRow['doctrine_special'] : 0;
        $interval= isset($this->ctx->botRow['recruit_interval']) ? (int)$this->ctx->botRow['recruit_interval'] : 0;

        $updates = array();

        // 1. Slot 1 — Fodder (17.5%): 204 o 205
        if ($fodder <= 0 || !in_array($fodder, array(204, 205))) {
            $fodder = ($botId % 2 === 0) ? 204 : 205;
            $updates['doctrine_fodder'] = $fodder;
        }

        // 2. Slot 2 — Light (20.0%): 206, 207, 215 y racial
        $s2Pool = array(206, 207, 215);
        if ($race === 1501) $s2Pool[] = 237;
        elseif ($race === 1502) $s2Pool[] = 245;
        elseif ($race === 1503) $s2Pool[] = 255;
        elseif ($race === 1504) $s2Pool[] = 224;

        if ($light <= 0 || !in_array($light, $s2Pool) || !$this->isUnitCompatibleWithBotRace($light, $race)) {
            $light = $s2Pool[abs(crc32("slot_2_bot_{$botId}")) % count($s2Pool)];
            $updates['doctrine_light'] = $light;
        }

        // 3. Slot 3 — Medium (10.0%): 211, 213 y racial
        $s3Pool = array(211, 213);
        if ($race === 1501) $s3Pool[] = 239;
        elseif ($race === 1502) $s3Pool[] = 247;
        elseif ($race === 1503) $s3Pool[] = 256;
        elseif ($race === 1504) $s3Pool[] = 264;

        if ($medium <= 0 || !in_array($medium, $s3Pool) || !$this->isUnitCompatibleWithBotRace($medium, $race)) {
            $medium = $s3Pool[abs(crc32("slot_3_bot_{$botId}")) % count($s3Pool)];
            $updates['doctrine_medium'] = $medium;
        }

        // 4. Slot 4 — Heavy (40.0%): 225, 226, 214, 216 y racial
        $s4Pool = array(225, 226, 214, 216);
        if ($race === 1501) $s4Pool[] = 240;
        elseif ($race === 1502) $s4Pool[] = 246;
        elseif ($race === 1503) $s4Pool[] = 258;
        elseif ($race === 1504) $s4Pool[] = 227;

        if ($heavy <= 0 || !in_array($heavy, $s4Pool) || !$this->isUnitCompatibleWithBotRace($heavy, $race)) {
            $heavy = $s4Pool[abs(crc32("slot_4_bot_{$botId}")) % count($s4Pool)];
            $updates['doctrine_heavy'] = $heavy;
        }

        // 5. Slot 5 — Special / Elite (10.0%): 228, 222, 218 y élite racial
        $s5Pool = array(228, 222, 218);
        if ($race === 1501) $s5Pool[] = 241;
        elseif ($race === 1502) $s5Pool[] = 252;
        elseif ($race === 1503) $s5Pool[] = 221;
        elseif ($race === 1504) $s5Pool[] = 267;
        // Evitar duplicado con heavy
        $s5Pool = array_values(array_diff($s5Pool, array($heavy)));

        if ($special <= 0 || !in_array($special, $s5Pool) || !$this->isUnitCompatibleWithBotRace($special, $race)) {
            $special = $s5Pool[abs(crc32("slot_5_bot_{$botId}")) % count($s5Pool)];
            $updates['doctrine_special'] = $special;
        }

        // Intervalo de reclutamiento
        if ($interval < 600 || $interval > 1800) {
            $pers = isset($this->ctx->personality->name) ? strtolower($this->ctx->personality->name) : 'balanced';
            if ($pers === 'aggressive' || $pers === 'raider') {
                $interval = mt_rand(600, 900);
            } elseif ($pers === 'defensive' || $pers === 'economic' || $pers === 'turtle' || $pers === 'miner') {
                $interval = mt_rand(1200, 1800);
            } else {
                $interval = mt_rand(900, 1200);
            }
            $updates['recruit_interval'] = $interval;
        }

        if (!empty($updates)) {
            $this->ctx->updateBotRow($updates);
            foreach ($updates as $k => $v) {
                $this->ctx->botRow[$k] = $v;
            }
        }

        return array(
            'heavy'    => $heavy,
            'medium'   => $medium,
            'light'    => $light,
            'support'  => $light,
            'fodder'   => $fodder,
            'special'  => $special,
            'interval' => $interval,
        );
    }

    /**
     * Asignación Canónica de por Vida de Ranuras de Defensa (Slots 1 a 4) por Calidad/Precio
     */
    public function getOrAssignDefenseDoctrine()
    {
        global $requeriments, $reslist, $pricelist, $CombatCaps;

        $botId = (int)$this->ctx->botId;
        $race  = $this->getBotFactionRace();

        $d1 = isset($this->ctx->botRow['def_slot1']) ? (int)$this->ctx->botRow['def_slot1'] : 0;
        $d2 = isset($this->ctx->botRow['def_slot2']) ? (int)$this->ctx->botRow['def_slot2'] : 0;
        $d3 = isset($this->ctx->botRow['def_slot3']) ? (int)$this->ctx->botRow['def_slot3'] : 0;
        $d4 = isset($this->ctx->botRow['def_slot4']) ? (int)$this->ctx->botRow['def_slot4'] : 0;

        $domesAndSilos = array(407, 408, 409, 419, 502, 503);
        $officerLocked = array(411);

        $updates = array();

        // Slot 1 — Fodder Barato (18.0%): MSE <= 15,000 determinista
        $d1Pool = array(402, 401);
        if ($race === 1501) $d1Pool[] = 423;
        elseif ($race === 1502) $d1Pool[] = 434;
        elseif ($race === 1503) $d1Pool[] = 446;
        elseif ($race === 1504) $d1Pool[] = 454;

        if ($d1 <= 0 || !in_array($d1, $d1Pool) || !$this->isUnitCompatibleWithBotRace($d1, $race)) {
            $d1 = $d1Pool[abs(crc32("def_1_bot_{$botId}")) % count($d1Pool)];
            $updates['def_slot1'] = $d1;
        }

        // Slot 2 — Ion (22.0%): Máximo Ratio Calidad/Precio para la raza del bot
        if ($d2 <= 0 || !$this->isUnitCompatibleWithBotRace($d2, $race)) {
            $d2Candidates = array();
            $ionList = array(405, 431, 445, 454);
            if (isset($reslist['defense'])) {
                $ionList = array_unique(array_merge($ionList, $reslist['defense']));
            }
            foreach ($ionList as $candId) {
                if (in_array($candId, $domesAndSilos) || in_array($candId, $officerLocked)) continue;
                if (!$this->isUnitCompatibleWithBotRace($candId, $race)) continue;
                $req = isset($requeriments[$candId]) ? $requeriments[$candId] : array();
                if (in_array($candId, array(405, 431, 445, 454)) || (isset($req[121]) && !isset($req[122]) && !isset($req[199]))) {
                    $d2Candidates[$candId] = $this->calculateQualityPriceRatio($candId);
                }
            }
            arsort($d2Candidates);
            $d2 = !empty($d2Candidates) ? (int)key($d2Candidates) : 405;
            $updates['def_slot2'] = $d2;
        }

        // Slot 3 — Plasma (30.0%): Máximo Ratio Calidad/Precio para la raza del bot
        if ($d3 <= 0 || !$this->isUnitCompatibleWithBotRace($d3, $race)) {
            $d3Candidates = array();
            $plasmaList = array(406, 412, 421, 422, 455, 456, 404);
            if (isset($reslist['defense'])) {
                $plasmaList = array_unique(array_merge($plasmaList, $reslist['defense']));
            }
            foreach ($plasmaList as $candId) {
                if (in_array($candId, $domesAndSilos) || in_array($candId, $officerLocked)) continue;
                if (!$this->isUnitCompatibleWithBotRace($candId, $race)) continue;
                $req = isset($requeriments[$candId]) ? $requeriments[$candId] : array();
                if (in_array($candId, array(406, 412, 421, 422, 455, 456, 404)) || (isset($req[122]) && !isset($req[199]))) {
                    $d3Candidates[$candId] = $this->calculateQualityPriceRatio($candId);
                }
            }
            arsort($d3Candidates);
            $d3 = !empty($d3Candidates) ? (int)key($d3Candidates) : 406;
            $updates['def_slot3'] = $d3;
        }

        // Slot 4 — Gravitón (30.0%): Máximo Ratio Calidad/Precio para la raza del bot
        if ($d4 <= 0 || !$this->isUnitCompatibleWithBotRace($d4, $race)) {
            $d4Candidates = array();
            $gravList = array(410, 418, 457, 458, 459, 460);
            if (isset($reslist['defense'])) {
                $gravList = array_unique(array_merge($gravList, $reslist['defense']));
            }
            foreach ($gravList as $candId) {
                if (in_array($candId, $domesAndSilos) || in_array($candId, $officerLocked)) continue;
                if (!$this->isUnitCompatibleWithBotRace($candId, $race)) continue;
                $req = isset($requeriments[$candId]) ? $requeriments[$candId] : array();
                if (in_array($candId, array(410, 418, 457, 458, 459, 460)) || isset($req[199])) {
                    $d4Candidates[$candId] = $this->calculateQualityPriceRatio($candId);
                }
            }
            arsort($d4Candidates);
            $d4 = !empty($d4Candidates) ? (int)key($d4Candidates) : 410;
            $updates['def_slot4'] = $d4;
        }

        if (!empty($updates)) {
            $this->ctx->updateBotRow($updates);
            foreach ($updates as $k => $v) {
                $this->ctx->botRow[$k] = $v;
            }
        }

        return array(
            'def_slot1' => $d1,
            'def_slot2' => $d2,
            'def_slot3' => $d3,
            'def_slot4' => $d4,
        );
    }

    /**
     * Resuelve el objetivo de nave de carne de cañón exclusiva (Compatibilidad v3)
     */
    public function getFodderShipTarget()
    {
        $doctrine = $this->getOrAssignFleetDoctrine();
        $target = $doctrine['fodder'];
        return array('target' => $target, 'bridge' => $target);
    }

    /**
     * Resuelve la unidad de carne de cañón exclusiva accesible en la colonia actual (Sin unidad puente)
     */
    public function resolveFodderShip(array $pData)
    {
        $doctrine = $this->getOrAssignFleetDoctrine();
        $target = $doctrine['fodder'];
        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $target, array())) {
            return $target;
        }
        return 0;
    }

    /**
     * Retorna las especificaciones de ranuras de combate 2 a 5 según la Doctrina Militar
     */
    public function getDoctrineWarshipSlots()
    {
        $doctrine = $this->getOrAssignFleetDoctrine();
        return array(
            'doctrine_id' => 4,
            'name'        => 'Doctrina de Huella Unica por Slot v4.0',
            'slots'       => array(
                2 => array('name' => 'Naves Ligeras',     'base_share' => 0.20,  'units' => array($doctrine['light'])),
                3 => array('name' => 'Naves Medias',      'base_share' => 0.10,  'units' => array($doctrine['medium'])),
                4 => array('name' => 'Naves Pesadas',     'base_share' => 0.40,  'units' => array($doctrine['heavy'])),
                5 => array('name' => 'Naves Especiales',  'base_share' => 0.10,  'units' => array($doctrine['special'])),
            ),
        );
    }

    /**
     * Resuelve la unidad exclusiva de carne de cañón defensiva para esta colonia
     */
    public function getFodderDefenseId(array $pData)
    {
        $defDoctrine = $this->getOrAssignDefenseDoctrine();
        $target = $defDoctrine['def_slot1'];
        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $target, array())) {
            return $target;
        }
        return 0;
    }

    /**
     * Catálogo maestro de unidades por Capa Defensiva
     */
    public function getDefenseCapas()
    {
        $defDoctrine = $this->getOrAssignDefenseDoctrine();
        return array(
            'domes' => array(407, 408, 409, 419),
            'silo'  => array(502, 503),
            'capa1' => array($defDoctrine['def_slot1']),
            'capa2' => array($defDoctrine['def_slot2']),
            'capa3' => array($defDoctrine['def_slot3']),
            'capa4' => array($defDoctrine['def_slot4']),
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

        foreach ($capas['domes'] as $dId) {
            $cnt = (int)($planet[$resource[$dId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($dId);
                $breakdown['domes_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        foreach ($capas['silo'] as $sId) {
            $cnt = (int)($planet[$resource[$sId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($sId);
                $breakdown['silo_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        foreach ($capas['capa2'] as $uId) {
            $cnt = (int)($planet[$resource[$uId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($uId);
                $breakdown['capa2_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        foreach ($capas['capa3'] as $uId) {
            $cnt = (int)($planet[$resource[$uId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($uId);
                $breakdown['capa3_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        foreach ($capas['capa4'] as $uId) {
            $cnt = (int)($planet[$resource[$uId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($uId);
                $breakdown['capa4_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }
        foreach ($capas['capa1'] as $uId) {
            $cnt = (int)($planet[$resource[$uId]] ?? 0);
            if ($cnt > 0) {
                $mse = $cnt * BotEconomyValuator::getElementPriceMSE($uId);
                $breakdown['capa1_mse'] += $mse;
                $breakdown['total_mse'] += $mse;
            }
        }

        // Defensas restantes no categorizadas
        $accounted = array_merge($capas['domes'], $capas['silo'], $capas['capa1'], $capas['capa2'], $capas['capa3'], $capas['capa4']);
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

        $doctrine = $this->getOrAssignFleetDoctrine();
        $targetFodder = $doctrine['fodder'];
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

            // Naves de combate
            foreach ($fleetList as $sId) {
                $sId = (int)$sId;
                if (in_array($sId, array(208, 210, 212, 233))) {
                    continue;
                }
                $cnt = (int)($bd[$resource[$sId]] ?? 0);
                if ($cnt <= 0) continue;

                $shipMSE = BotEconomyValuator::getElementPriceMSE($sId) * $cnt;
                $stats['total_fleet_mse'] += $shipMSE;

                if ($sId === $targetFodder) {
                    $stats['fodder_mse'] += $shipMSE;
                } elseif (!in_array($sId, array(202, 203, 217, 209, 219))) {
                    $stats['combat_mse'] += $shipMSE;
                }
            }
        }

        return $stats;
    }

    public function resolveCategoryUnit($category, array $pData)
    {
        $doctrine = $this->getOrAssignFleetDoctrine();
        if ($category === 'fodder') {
            return $this->resolveFodderShip($pData);
        }
        $map = array(
            'support' => 'light',
            'light'   => 'light',
            'medium'  => 'medium',
            'heavy'   => 'heavy',
            'elite'   => 'special',
            'special' => 'special',
        );
        $key = $map[$category] ?? 'light';
        $uId = $doctrine[$key] ?? 206;
        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $uId, array())) {
            return $uId;
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
            $unitTime = $this->getUnitBuildTimeMs($this->ctx->user, $planet, $unitId) / 1000.0;
            $totalSeconds += ($unitTime * $count);
        }

        $elapsed = isset($planet['b_hangar']) ? (int)$planet['b_hangar'] : 0;
        return (int)ceil(max(0.0, $totalSeconds - $elapsed));
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

        $slot1Res = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
        if (isset($this->ctx->budgetManager) && method_exists($this->ctx->budgetManager, 'getNextQueuedSlotCost') && !empty($pData['id'])) {
            $slot1Res = $this->ctx->budgetManager->getNextQueuedSlotCost((int)$pData['id']);
        }
        $physAvailM = max(0.0, (float)$pData['metal'] - (float)$slot1Res['metal']);
        $physAvailC = max(0.0, (float)$pData['crystal'] - (float)$slot1Res['crystal']);
        $physAvailD = max(0.0, (float)$pData['deuterium'] - (float)$slot1Res['deuterium']);

        $availM = min($physAvailM, (float)($categoryCredits['metal'] ?? 0.0));
        $availC = min($physAvailC, (float)($categoryCredits['crystal'] ?? 0.0));
        $availD = min($physAvailD, (float)($categoryCredits['deuterium'] ?? 0.0));

        if ($lockedDeficitRes === 901 && $costM > 0) {
            $availM = max(0.0, $availM - $lockedDeficitAmount);
        } elseif ($lockedDeficitRes === 902 && $costC > 0) {
            $availC = max(0.0, $availC - $lockedDeficitAmount);
        } elseif ($lockedDeficitRes === 903 && $costD > 0) {
            $availD = max(0.0, $availD - $lockedDeficitAmount);
        }

        $maxM = ($costM > 0) ? (int)floor($availM / $costM) : PHP_INT_MAX;
        $maxC = ($costC > 0) ? (int)floor($availC / $costC) : PHP_INT_MAX;
        $maxD = ($costD > 0) ? (int)floor($availD / $costD) : PHP_INT_MAX;

        return max(0, min($maxM, $maxC, $maxD));
    }

    /**
     * Válvula de Flujo Activo de Presupuesto (100% de los créditos disponibles autorizados)
     */
    public function calculateCycleSpendBudget($pId, array $pData, $category, array $totalCredits, $lastRecruitTime = 0, $isEmergency = false)
    {
        $totM = max(0.0, (float)($totalCredits['metal'] ?? 0.0));
        $totC = max(0.0, (float)($totalCredits['crystal'] ?? 0.0));
        $totD = max(0.0, (float)($totalCredits['deuterium'] ?? 0.0));

        return array(
            'metal'     => round($totM, 2),
            'crystal'   => round($totC, 2),
            'deuterium' => round($totD, 2),
        );
    }

    /**
     * Obtiene el tiempo real exacto de construcción de 1 unidad en el hangar en milisegundos (ms),
     * soportando resolución sub-segundo (< 1000 ms, hasta 0.1 ms = 0.0001s) cuando hay Nanobots altos.
     */
    public function getUnitBuildTimeMs(array $user, array $planet, $elementId)
    {
        global $pricelist, $resource;

        $hangarLvl = (int)($planet[$resource[21]] ?? ($planet['hangar'] ?? 0));
        $nanoLvl   = (int)($planet[$resource[15]] ?? ($planet['nano_factory'] ?? 0));
        $mod82Lvl  = (int)($planet[$resource[82]] ?? ($planet['defensive_module'] ?? 0));
        $mod83Lvl  = (int)($planet[$resource[83]] ?? ($planet['military_module'] ?? 0));
        $modBonus  = ($elementId >= 400) ? ($mod82Lvl * 10) : ($mod83Lvl * 10);

        if ((!isset($planet['fleet']) || $planet['fleet'] <= 0) && ($hangarLvl > 0 || $nanoLvl > 0)) {
            $planet['fleet'] = max(0.0, (1.0 + $hangarLvl + $modBonus) * pow(2.0, $nanoLvl) - 1.0);
        }
        if ((!isset($planet['defense']) || $planet['defense'] <= 0) && ($hangarLvl > 0 || $nanoLvl > 0)) {
            $planet['defense'] = max(0.0, (1.0 + $hangarLvl + $modBonus) * pow(2.0, $nanoLvl) - 1.0);
        }

        $engineSec = (float)BuildFunctions::getBuildingTime($user, $planet, $elementId);
        if ($engineSec > 0.0) {
            return max(0.1, $engineSec * 1000.0);
        }

        return 1000.0;
    }

    public function getConstructibleBatch(array $pData, array $categoryCredits, $unitId, $queuedJobs, $maxAllowedSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefRes = 0, $isEmergency = false, $lockedDefAmount = 0.0)
    {
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

        $unitTime = max(0.0001, $this->getUnitBuildTimeMs($this->ctx->user, $pData, $unitId) / 1000.0);

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
     * Modernización Progresiva de Flota Civil con Umbrales Realistas
     * - Desguace de #202 con > 30 unidades
     * - Desguace de #209 con > 20 unidades
     */
    /**
     * Realiza una conversión civil (desguace + construcción) de forma atómica y segura.
     * Si la construcción falla o encola 0 unidades, se revierte la transacción para no perder
     * las unidades desguazadas ni dejar al bot en un estado inconsistente.
     */
    protected function executeSafeCivilianConversion($planetId, array &$pData, $sourceUnitId, $scrapCount, $targetUnitId, $targetBatches, &$queuedJobs, &$queueRemainingSec)
    {
        if ($scrapCount <= 0 || $targetBatches <= 0) {
            return 0;
        }

        $db = Database::get();
        $isOuterTx = $db->inTransaction();
        if (!$isOuterTx) {
            $db->beginTransaction();
        }

        try {
            $refund = $this->gateway->scrapUnits($planetId, $sourceUnitId, $scrapCount);
            if ($refund !== false) {
                $enqueued = (int)$this->gateway->constructUnits($planetId, $targetUnitId, $targetBatches, 'civilian_conversion');
                if ($enqueued > 0) {
                    if (!$isOuterTx) {
                        $db->commit();
                    }
                    $pData = $this->refreshPlanetData($planetId, $pData);
                    $queuedJobs++;
                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                    return $enqueued;
                } else {
                    // La construcción falló: revertir el desguace para no destruir unidades en vano
                    if (!$isOuterTx) {
                        $db->rollBack();
                    }
                    if (isset($this->ctx->budgetManager) && is_array($refund)) {
                        $this->ctx->budgetManager->revertScrapRefund($planetId, $refund, 'fleet');
                    }
                    $pData = $this->refreshPlanetData($planetId, $pData);
                    return 0;
                }
            } else {
                if (!$isOuterTx) {
                    $db->rollBack();
                }
                return 0;
            }
        } catch (Throwable $e) {
            if (!$isOuterTx && $db->inTransaction()) {
                $db->rollBack();
            }
            return 0;
        }
    }

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

        $civCapReached = ($totalFleetMSE > 0 && (($cargoMSE + $recyclerMSE) / $totalFleetMSE) >= 0.025);
        if ($civCapReached) {
            return $upgraded;
        }

        // 1. Cadena 202 -> 203 -> 217 (Desguazar 202 con > 30)
        $canBuild217 = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 217, array());
        if ($canBuild217 && !$this->isUnitInQueue($pData, 217) && $queuedJobs < $maxRegularSlots) {
            $cur202 = isset($pData[$resource[202]]) ? (int)$pData[$resource[202]] : 0;
            $avail202 = max(0, $cur202 - 30);
            if ($avail202 >= 18) {
                $batches217 = (int)floor($avail202 / 18);
                $scrap202 = $batches217 * 18;
                $enq217 = $this->executeSafeCivilianConversion($planetId, $pData, 202, $scrap202, 217, $batches217, $queuedJobs, $queueRemainingSec);
                if ($enq217 > 0) {
                    $upgraded[217] = isset($upgraded[217]) ? $upgraded[217] + $enq217 : $enq217;
                }
            }
        } elseif (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 203, array()) && !$this->isUnitInQueue($pData, 203) && $queuedJobs < $maxRegularSlots) {
            $cur202 = isset($pData[$resource[202]]) ? (int)$pData[$resource[202]] : 0;
            $avail202 = max(0, $cur202 - 30);
            if ($avail202 >= 3) {
                $batches203 = (int)floor($avail202 / 3);
                $scrap202 = $batches203 * 3;
                $enq203 = $this->executeSafeCivilianConversion($planetId, $pData, 202, $scrap202, 203, $batches203, $queuedJobs, $queueRemainingSec);
                if ($enq203 > 0) {
                    $upgraded[203] = isset($upgraded[203]) ? $upgraded[203] + $enq203 : $enq203;
                }
            }
        }

        // 2. Cadena 209 -> 219 (Desguazar 209 con > 20, max 1,500 #219)
        if ($count219 < 1500 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 219, array()) && !$this->isUnitInQueue($pData, 219) && $queuedJobs < $maxRegularSlots) {
            $cur209 = isset($pData[$resource[209]]) ? (int)$pData[$resource[209]] : 0;
            $avail209 = max(0, $cur209 - 20);
            if ($avail209 >= 100) {
                $allowed219 = max(0, 1500 - $count219);
                $batches219 = min((int)floor($avail209 / 100), $allowed219);
                if ($batches219 > 0) {
                    $scrap209 = $batches219 * 100;
                    $enq219 = $this->executeSafeCivilianConversion($planetId, $pData, 209, $scrap209, 219, $batches219, $queuedJobs, $queueRemainingSec);
                    if ($enq219 > 0) {
                        $upgraded[219] = isset($upgraded[219]) ? $upgraded[219] + $enq219 : $enq219;
                    }
                }
            }
        }

        return $upgraded;
    }

    /**
     * Planifica y encola reclutamiento militar en todo el imperio con Arquitectura v4.0
     */
    public function planForces(BotEmpireState $empire, $forceRun = false, $world = null)
    {
        global $resource, $pricelist, $reslist;

        $fleetDoctrine = $this->getOrAssignFleetDoctrine();
        $defDoctrine   = $this->getOrAssignDefenseDoctrine();

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
        $militaryFleetRecruitedTotal = 0;
        $defRecruitedTotal = 0;

        $allBodies = $empire->getAllBodies();
        if (!is_array($allBodies)) {
            return $recruited;
        }

        $empireStats = $this->getEmpireFleetStats($empire);
        $totalFleetMSE = max(1.0, (float)$empireStats['total_fleet_mse']);
        $count219      = (int)$empireStats['count_219'];

        $civCapReached = ((($empireStats['cargo_mse'] + $empireStats['recycler_mse']) / $totalFleetMSE) >= 0.025);
        $recyclerCapReached = ($civCapReached || ($count219 >= 1500));

        foreach ($allBodies as $pId => $body) {
            $pData = $body['data'];

            $queuedJobs = !empty($pData['b_hangar_id']) ? count(@unserialize($pData['b_hangar_id'])) : 0;
            $maxQueueJobs = 14;
            $maxRegularSlots = isset($this->ctx->difficulty->maxRegularSlots) ? (int)$this->ctx->difficulty->maxRegularSlots : 14;
            $maxHangarDuration = isset($this->ctx->difficulty->maxHangarQueueDuration) ? (int)$this->ctx->difficulty->maxHangarQueueDuration : 2700;
            $batchTimeTarget = isset($this->ctx->difficulty->batchTimeTarget) ? (int)$this->ctx->difficulty->batchTimeTarget : 900;

            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);

            // --- 1. Estabilización Energética: Satélites Solares (212) ---
            // Los satélites solares son infraestructura energética planetaria: se financian
            // desde el presupuesto de producción/minas (mines) o swap interno, sin canibalizar
            // el presupuesto de flota de combate ni reiniciar el cooldown militar.
            $energy = (float)$pData['energy'];
            if ($energy < 0 && $queuedJobs < $maxQueueJobs && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 212, array()) && !$this->isUnitInQueue($pData, 212)) {
                $energyPerSat = max(1.0, (($pData['temp_max'] + 160) / 6.0));
                $satsNeeded   = min(50, (int)ceil(abs($energy) / $energyPerSat));
                $maxSats      = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, 212);
                $batchSats    = min($satsNeeded, $maxSats);

                // Comprobación explícita de presupuesto aplicable (mines / infraestructura energética)
                $maxSatsByBudget = $batchSats;
                if ($batchSats > 0 && isset($this->ctx->budgetManager)) {
                    $minesCreds = $this->ctx->budgetManager->getPlanetMinesCredits($pId);
                    $availC = (float)($minesCreds['crystal'] ?? 0.0);
                    $availD = (float)($minesCreds['deuterium'] ?? 0.0);
                    $maxSatsByBudget = max(0, (int)floor(min($availC / 2000.0, $availD / 500.0)));
                }
                $batchSats = min($batchSats, $maxSatsByBudget);

                $enqueuedSats = ($batchSats > 0) ? (int)$this->gateway->constructUnits($pId, 212, $batchSats, 'energy_stabilization', 'mines') : 0;
                if ($enqueuedSats > 0) {
                    $recruited[212] = isset($recruited[212]) ? $recruited[212] + $enqueuedSats : $enqueuedSats;
                    // Nota: Los satélites solares NO incrementan $militaryFleetRecruitedTotal para no reiniciar el cooldown militar
                    $queuedJobs++;
                    $pData = $this->refreshPlanetData($pId, $pData);
                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                }
            }

            if (!$canRunFleet && !$canRunDefense) {
                continue;
            }

            // Modernización civil
            if ($canRunFleet) {
                $upgradedCiv = $this->upgradeCivilianFleets($pId, $pData, $empireStats);
                if (!empty($upgradedCiv)) {
                    foreach ($upgradedCiv as $civId => $civCount) {
                        $recruited[$civId] = isset($recruited[$civId]) ? $recruited[$civId] + $civCount : $civCount;
                        // Nota: Conversiones civiles registradas en $recruited pero NO en $militaryFleetRecruitedTotal
                        if ($civId === 219) {
                            $empireStats['count_219'] += $civCount;
                        }
                    }
                    $queuedJobs = !empty($pData['b_hangar_id']) ? count(@unserialize($pData['b_hangar_id'])) : 0;
                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                }
            }

            // Presupuestos autorizados del ciclo
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
            // --- 2. GESTIÓN DEFENSIVA MULTICAPA (Prioridad 0, 1, y Slots 4 -> 1) ---
            // =========================================================================
            if ($canRunDefense && $queuedJobs < 14) {
                $maxDefJobsThisCycle = $isEmergency ? 14 : 4;
                $defJobsEnqueued = 0;
                $defSec = isset($this->ctx->difficulty->defenseEnqueueSeconds) ? (int)$this->ctx->difficulty->defenseEnqueueSeconds : 480;
                $totalDefTimeMs = (float)($defSec * 1000);

                // Prioridad 0: Cúpulas Únicas (407, 408, 409, 419)
                foreach (array(407, 408, 409, 419) as $domeId) {
                    if ($queuedJobs >= 14) break;
                    $curDome = isset($pData[$resource[$domeId]]) ? (int)$pData[$resource[$domeId]] : 0;
                    if ($curDome == 0 && !$this->isUnitInQueue($pData, $domeId) && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $domeId, array())) {
                        $batchDome = $this->getConstructibleBatch($pData, $defCredits, $domeId, $queuedJobs, 14, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                        if ($batchDome >= 1) {
                            $enqueuedDome = (int)$this->gateway->constructUnits($pId, $domeId, 1);
                            if ($enqueuedDome >= 1) {
                                $recruited[$domeId] = isset($recruited[$domeId]) ? $recruited[$domeId] + $enqueuedDome : $enqueuedDome;
                                $defRecruitedTotal += $enqueuedDome;
                                $queuedJobs++;
                                $defJobsEnqueued++;

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

                // Prioridad 1: Silo de Misiles (502 ABM y 503 MIP)
                $siloLevel = isset($pData[$resource[44]]) ? (int)$pData[$resource[44]] : 0;
                if ($queuedJobs < 14 && $siloLevel >= 2) {
                    $rocketsCapacity = BuildFunctions::getMaxConstructibleRockets($this->ctx->user, $pData);
                    $freeSpaces = isset($rocketsCapacity[502]) ? (int)$rocketsCapacity[502] : 0;
                    $cur502 = isset($pData[$resource[502]]) ? (int)$pData[$resource[502]] : 0;
                    $cur503 = isset($pData[$resource[503]]) ? (int)$pData[$resource[503]] : 0;
                    $impulse = isset($this->ctx->user[$resource[117]]) ? (int)$this->ctx->user[$resource[117]] : 0;
                    $totalSiloMax = $cur502 + $cur503 + $freeSpaces;

                    $canBuild502 = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 502, array());
                    $canBuild503 = ($siloLevel >= 4 && $impulse >= 1 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 503, array()));

                    $pers = isset($this->ctx->personality->name) ? strtolower($this->ctx->personality->name) : 'balanced';
                    if ($canBuild502 && $canBuild503) {
                        if ($isSiegeActive) {
                            $target502 = (int)floor($totalSiloMax * 0.30);
                            $target503 = (int)floor($totalSiloMax * 0.70);
                        } elseif ($pers === 'turtle' || $pers === 'miner') {
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

                    if ($deficit502 > 0 && $freeSpaces > 0 && $queuedJobs < 14 && !$this->isUnitInQueue($pData, 502)) {
                        $batch502 = $this->getConstructibleBatch($pData, $defCredits, 502, $queuedJobs, 14, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, true, $lockedDefAmountDef);
                        $batch502 = min($batch502, $deficit502, $freeSpaces);
                        $enqueued502 = ($batch502 > 0) ? (int)$this->gateway->constructUnits($pId, 502, $batch502) : 0;
                        if ($enqueued502 > 0) {
                            $recruited[502] = isset($recruited[502]) ? $recruited[502] + $enqueued502 : $enqueued502;
                            $defRecruitedTotal += $enqueued502;
                            $queuedJobs++;
                            $defJobsEnqueued++;
                            $freeSpaces -= $enqueued502;
                            $costM502 = ((float)($pricelist[502]['cost'][901] ?? 0) * $enqueued502);
                            $costC502 = ((float)($pricelist[502]['cost'][902] ?? 0) * $enqueued502);
                            $costD502 = ((float)($pricelist[502]['cost'][903] ?? 0) * $enqueued502);
                            $defCredits['metal']     = max(0.0, $defCredits['metal'] - $costM502);
                            $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - $costC502);
                            $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - $costD502);
                            $pData = $this->refreshPlanetData($pId, $pData);
                            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                        }
                    }

                    if ($deficit503 > 0 && $freeSpaces > 0 && $canBuild503 && $queuedJobs < 14 && !$this->isUnitInQueue($pData, 503)) {
                        $batch503 = $this->getConstructibleBatch($pData, $defCredits, 503, $queuedJobs, 14, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                        $batch503 = min($batch503, $deficit503, $freeSpaces);
                        $enqueued503 = ($batch503 > 0) ? (int)$this->gateway->constructUnits($pId, 503, $batch503) : 0;
                        if ($enqueued503 > 0) {
                            $recruited[503] = isset($recruited[503]) ? $recruited[503] + $enqueued503 : $enqueued503;
                            $defRecruitedTotal += $enqueued503;
                            $queuedJobs++;
                            $defJobsEnqueued++;
                            $freeSpaces -= $enqueued503;
                            $costM503 = ((float)($pricelist[503]['cost'][901] ?? 0) * $enqueued503);
                            $costC503 = ((float)($pricelist[503]['cost'][902] ?? 0) * $enqueued503);
                            $costD503 = ((float)($pricelist[503]['cost'][903] ?? 0) * $enqueued503);
                            $defCredits['metal']     = max(0.0, $defCredits['metal'] - $costM503);
                            $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - $costC503);
                            $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - $costD503);
                            $pData = $this->refreshPlanetData($pId, $pData);
                            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                        }
                    }
                }

                // --- Prioridad 2: Artillería Defensiva con Cascada de Tiempo en Hangar (Slots 4 -> 3 -> 2 -> 1) ---
                if ($queuedJobs < 14 && $defJobsEnqueued < $maxDefJobsThisCycle) {
                    $defBreakdown = $this->calculateDefenseBreakdown($pData);
                    $totDefMSE = max(1.0, $defBreakdown['total_mse']);
                    $capa1Ratio = $defBreakdown['capa1_mse'] / $totDefMSE;
                    $capa2Ratio = $defBreakdown['capa2_mse'] / $totDefMSE;
                    $hasHigherDefCapas = (($defBreakdown['capa3_mse'] + $defBreakdown['capa4_mse']) > 0);

                    // Porcentajes de plantilla de Defensa: Slot 4 = 30%, Slot 3 = 30%, Slot 2 = 22%, Slot 1 = 18%
                    $defSlotShares = array(
                        4 => 0.300, // 30.0% Pesada / Gravitón
                        3 => 0.300, // 30.0% Media / Plasma
                        2 => 0.220, // 22.0% Ligera / Ion
                        1 => 0.180, // 18.0% Fodder
                    );

                    $defSlotKeys = array(
                        4 => 'def_slot4',
                        3 => 'def_slot3',
                        2 => 'def_slot2',
                        1 => 'def_slot1',
                    );

                    $slot1DefBlocked = ($capa1Ratio >= 0.22 && $hasHigherDefCapas);
                    $rolloverTimeMs = 0.0;
                    if ($slot1DefBlocked) {
                        $rolloverTimeMs = $totalDefTimeMs * $defSlotShares[1];
                        $defSlotShares[1] = 0.0;
                    }

                    for ($dSlot = 4; $dSlot >= 1; $dSlot--) {
                        if ($queuedJobs >= 14 || $defJobsEnqueued >= $maxDefJobsThisCycle) {
                            break;
                        }

                        $slotTimeMs = ($totalDefTimeMs * $defSlotShares[$dSlot]) + $rolloverTimeMs;
                        $defUnitId = (int)($defDoctrine[$defSlotKeys[$dSlot]] ?? 0);

                        // Comprobar candados anti-monopolio
                        $isBlockedByCap = false;
                        if ($dSlot === 2 && $capa2Ratio >= 0.25 && $hasHigherDefCapas) {
                            $isBlockedByCap = true; // Candado anti-monopolio al 25% para Ion
                        } elseif ($dSlot === 1 && $capa1Ratio >= 0.22 && $hasHigherDefCapas) {
                            $isBlockedByCap = true; // Techo estricto 22% para Fodder
                        }

                        $isAccessible = ($defUnitId > 0 && !$isBlockedByCap && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $defUnitId, array()));

                        if (!$isAccessible) {
                            // Pasa todo el tiempo al slot anterior (dSlot - 1)
                            $rolloverTimeMs = $slotTimeMs;
                            continue;
                        }

                        $unitTimeMs  = $this->getUnitBuildTimeMs($this->ctx->user, $pData, $defUnitId);
                        $unitsByTime = (int)floor($slotTimeMs / $unitTimeMs);

                        if ($unitsByTime < 1) {
                            // Pasa todo el tiempo al slot anterior (dSlot - 1)
                            $rolloverTimeMs = $slotTimeMs;
                            continue;
                        }

                        $maxAffordable = $this->calculateTriResourceMaxUnits($pData, $defCredits, $defUnitId, $lockedDefResDef, $lockedDefAmountDef);
                        $batch = min($unitsByTime, $maxAffordable);

                        if ($batch >= 1) {
                            $enqueued = (int)$this->gateway->constructUnits($pId, $defUnitId, $batch);
                            if ($enqueued > 0) {
                                $recruited[$defUnitId] = isset($recruited[$defUnitId]) ? $recruited[$defUnitId] + $enqueued : $enqueued;
                                $defRecruitedTotal += $enqueued;
                                $queuedJobs++;
                                $defJobsEnqueued++;

                                $costM = (float)($pricelist[$defUnitId]['cost'][901] ?? 0) * $enqueued;
                                $costC = (float)($pricelist[$defUnitId]['cost'][902] ?? 0) * $enqueued;
                                $costD = (float)($pricelist[$defUnitId]['cost'][903] ?? 0) * $enqueued;

                                $defCredits['metal']     = max(0.0, $defCredits['metal'] - $costM);
                                $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - $costC);
                                $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - $costD);

                                $pData = $this->refreshPlanetData($pId, $pData);
                                $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);

                                $usedTimeMs     = $enqueued * $unitTimeMs;
                                $rolloverTimeMs = max(0.0, $slotTimeMs - $usedTimeMs);
                            } else {
                                $rolloverTimeMs = $slotTimeMs;
                            }
                        } else {
                            // Pasa todo el tiempo al slot anterior (dSlot - 1)
                            $rolloverTimeMs = $slotTimeMs;
                        }
                    }
                }
            }

            // =========================================================================
            // --- 3. RECLUTAMIENTO DE FLOTA COMBINADA (Slot 0 y Slots 5 -> 1) ---
            // =========================================================================
            if ($canRunFleet && $queuedJobs < 14) {
                $fleetSec = isset($this->ctx->difficulty->fleetEnqueueSeconds) ? (int)$this->ctx->difficulty->fleetEnqueueSeconds : 300;
                $totalFleetTimeMs = (float)($fleetSec * 1000);

                // Sondas de Espionaje: buffer mínimo de 60 sondas por colonia
                $curProbes = isset($pData[$resource[210]]) ? (int)$pData[$resource[210]] : 0;
                if ($curProbes < 60 && $queuedJobs < 14 && !$this->isUnitInQueue($pData, 210) && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 210, array())) {
                    $probeCreds = array(
                        'metal'     => $fleetCredits['metal'] * 0.02,
                        'crystal'   => $fleetCredits['crystal'] * 0.02,
                        'deuterium' => $fleetCredits['deuterium'] * 0.02,
                    );
                    $bProbe = $this->getConstructibleBatch($pData, $probeCreds, 210, $queuedJobs, 14, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResFleet, false, $lockedDefAmountFleet);
                    $bProbe = min($bProbe, 60 - $curProbes);
                    if ($bProbe > 0) {
                        $enqProbe = (int)$this->gateway->constructUnits($pId, 210, $bProbe);
                        if ($enqProbe > 0) {
                            $recruited[210] = isset($recruited[210]) ? $recruited[210] + $enqProbe : $enqProbe;
                            $militaryFleetRecruitedTotal += $enqProbe;
                            $queuedJobs++;
                            $costMProbe = ((float)($pricelist[210]['cost'][901] ?? 0) * $enqProbe);
                            $costCProbe = ((float)($pricelist[210]['cost'][902] ?? 0) * $enqProbe);
                            $costDProbe = ((float)($pricelist[210]['cost'][903] ?? 0) * $enqProbe);
                            $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - $costMProbe);
                            $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - $costCProbe);
                            $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - $costDProbe);
                            $pData = $this->refreshPlanetData($pId, $pData);
                            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                        }
                    }
                }

                // ---------------------------------------------------------------------
                // Slot 0 — Unidades de Apoyo Logístico (2.5% del tiempo total)
                // ---------------------------------------------------------------------
                $slot0TimeMs = $totalFleetTimeMs * 0.025;
                $recTimeMs   = $slot0TimeMs * 0.50; // 50% para Recicladores
                $cargoTimeMs = $slot0TimeMs * 0.50; // 50% para Cargueros
                $leftoverRecMs   = $recTimeMs;
                $leftoverCargoMs = $cargoTimeMs;

                $pMetalStock = (float)$pData['metal'];
                $pCrystalStock = (float)$pData['crystal'];
                $pDeutStock = (float)$pData['deuterium'];
                $stationedRes = $pMetalStock + $pCrystalStock + $pDeutStock;

                $curCargosCap = ((int)($pData[$resource[202]] ?? 0) * 5000)
                              + ((int)($pData[$resource[203]] ?? 0) * 25000)
                              + ((int)($pData[$resource[217]] ?? 0) * 400000000);
                $curRecsCount = (int)($pData[$resource[209]] ?? 0) + (int)($pData[$resource[219]] ?? 0);

                // Piso operativo: capaz de transportar 50% de recursos o 50 cargueros / 25 recicladores
                $belowFloor = ($curCargosCap < ($stationedRes * 0.50) || ((int)($pData[$resource[203]] ?? 0) < 50) || ($curRecsCount < 25));

                if ((!$civCapReached || $belowFloor) && $queuedJobs < 14) {
                    // Mitad 1: Recicladores (el mejor accesible entre 219 -> 209)
                    $chosenRecId = 0;
                    if (!$recyclerCapReached && $empireStats['count_219'] < 1500 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 219, array())) {
                        $chosenRecId = 219;
                    } elseif (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 209, array())) {
                        $chosenRecId = 209;
                    }

                    if ($chosenRecId > 0 && !$this->isUnitInQueue($pData, $chosenRecId) && $queuedJobs < 14) {
                        $unitTimeMs = $this->getUnitBuildTimeMs($this->ctx->user, $pData, $chosenRecId);
                        $unitsByTime = (int)floor($recTimeMs / $unitTimeMs);
                        if ($unitsByTime >= 1) {
                            $maxAff = $this->calculateTriResourceMaxUnits($pData, $fleetCredits, $chosenRecId, $lockedDefResFleet, $lockedDefAmountFleet);
                            if ($chosenRecId === 219) {
                                $maxAff = min($maxAff, max(0, 1500 - $empireStats['count_219']));
                            }
                            $batchRec = min($unitsByTime, $maxAff);
                            if ($batchRec >= 1) {
                                $enqRec = (int)$this->gateway->constructUnits($pId, $chosenRecId, $batchRec);
                                if ($enqRec > 0) {
                                    $recruited[$chosenRecId] = isset($recruited[$chosenRecId]) ? $recruited[$chosenRecId] + $enqRec : $enqRec;
                                    $militaryFleetRecruitedTotal += $enqRec;
                                    if ($chosenRecId === 219) {
                                        $empireStats['count_219'] += $enqRec;
                                        if ($empireStats['count_219'] >= 1500) {
                                            $recyclerCapReached = true;
                                        }
                                    }
                                    $queuedJobs++;

                                    $costM = (float)($pricelist[$chosenRecId]['cost'][901] ?? 0) * $enqRec;
                                    $costC = (float)($pricelist[$chosenRecId]['cost'][902] ?? 0) * $enqRec;
                                    $costD = (float)($pricelist[$chosenRecId]['cost'][903] ?? 0) * $enqRec;

                                    $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - $costM);
                                    $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - $costC);
                                    $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - $costD);

                                    $pData = $this->refreshPlanetData($pId, $pData);
                                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);

                                    $leftoverRecMs = max(0.0, $recTimeMs - ($enqRec * $unitTimeMs));
                                }
                            }
                        }
                    }

                    // Mitad 2: Cargueros (el mejor accesible entre 217 -> 203 -> 202)
                    $chosenCargoId = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 217, array()) ? 217
                                   : (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 203, array()) ? 203
                                   : (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 202, array()) ? 202 : 0));

                    if ($chosenCargoId > 0 && !$this->isUnitInQueue($pData, $chosenCargoId) && $queuedJobs < 14) {
                        $unitTimeMs = $this->getUnitBuildTimeMs($this->ctx->user, $pData, $chosenCargoId);
                        $unitsByTime = (int)floor($cargoTimeMs / $unitTimeMs);
                        if ($unitsByTime >= 1) {
                            $maxAff = $this->calculateTriResourceMaxUnits($pData, $fleetCredits, $chosenCargoId, $lockedDefResFleet, $lockedDefAmountFleet);
                            $batchCargo = min($unitsByTime, $maxAff);
                            if ($batchCargo >= 1) {
                                $enqCargo = (int)$this->gateway->constructUnits($pId, $chosenCargoId, $batchCargo);
                                if ($enqCargo > 0) {
                                    $recruited[$chosenCargoId] = isset($recruited[$chosenCargoId]) ? $recruited[$chosenCargoId] + $enqCargo : $enqCargo;
                                    $militaryFleetRecruitedTotal += $enqCargo;
                                    $queuedJobs++;

                                    $costM = (float)($pricelist[$chosenCargoId]['cost'][901] ?? 0) * $enqCargo;
                                    $costC = (float)($pricelist[$chosenCargoId]['cost'][902] ?? 0) * $enqCargo;
                                    $costD = (float)($pricelist[$chosenCargoId]['cost'][903] ?? 0) * $enqCargo;

                                    $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - $costM);
                                    $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - $costC);
                                    $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - $costD);

                                    $pData = $this->refreshPlanetData($pId, $pData);
                                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);

                                    $leftoverCargoMs = max(0.0, $cargoTimeMs - ($enqCargo * $unitTimeMs));
                                }
                            }
                        }
                    }

                    $leftoverSlot0TimeMs = $leftoverRecMs + $leftoverCargoMs;
                } else {
                    $leftoverSlot0TimeMs = $slot0TimeMs;
                }

                // ---------------------------------------------------------------------
                // Flota de Combate con Cascada de Tiempo en Hangar (Slots 5 -> 4 -> 3 -> 2 -> 1)
                // Ratios duros exactos:
                // Slot 5 = 10.0%, Slot 4 = 40.0%, Slot 3 = 10.0%, Slot 2 = 20.0%, Slot 1 = 17.5%
                // (Total = 97.5% comb + 2.5% civil = 100.0%)
                // ---------------------------------------------------------------------
                if ($queuedJobs < 14) {
                    $fodderMSE   = (float)$empireStats['fodder_mse'];
                    $fodderRatio = ($totalFleetMSE > 0) ? ($fodderMSE / $totalFleetMSE) : 0.0;
                    $hasAnyCombatWarship = (($empireStats['combat_mse'] ?? 0.0) > 0);

                    $fleetSlotShares = array(
                        5 => 0.100, // 10.0% Especiales
                        4 => 0.400, // 40.0% Pesadas
                        3 => 0.100, // 10.0% Medias
                        2 => 0.200, // 20.0% Ligeras
                        1 => 0.175, // 17.5% Fodder
                    );

                    $fleetSlotKeys = array(
                        5 => 'special',
                        4 => 'heavy',
                        3 => 'medium',
                        2 => 'light',
                        1 => 'fodder',
                    );

                    $slot1FleetBlocked = ($fodderRatio >= 0.22 && $hasAnyCombatWarship);
                    $rolloverTimeMs = $leftoverSlot0TimeMs;
                    if ($slot1FleetBlocked) {
                        $rolloverTimeMs += ($totalFleetTimeMs * $fleetSlotShares[1]);
                        $fleetSlotShares[1] = 0.0;
                    }

                    for ($sNum = 5; $sNum >= 1; $sNum--) {
                        if ($queuedJobs >= 14) {
                            break;
                        }

                        $slotTimeMs = ($totalFleetTimeMs * $fleetSlotShares[$sNum]) + $rolloverTimeMs;

                        $chosenShipId = (int)($fleetDoctrine[$fleetSlotKeys[$sNum]] ?? 0);

                        // Candado anti-monopolio en Slot 1 (Fodder)
                        $isBlockedByCap = false;
                        if ($sNum === 1 && $fodderRatio >= 0.22 && $hasAnyCombatWarship) {
                            $isBlockedByCap = true; // Techo estricto 22% cuando ya hay naves superiores
                        }

                        $isAccessible = ($chosenShipId > 0 && !$isBlockedByCap && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $chosenShipId, array()));

                        if (!$isAccessible) {
                            // Pasa todo el tiempo al slot anterior (sNum - 1)
                            $rolloverTimeMs = $slotTimeMs;
                            continue;
                        }

                        $unitTimeMs  = $this->getUnitBuildTimeMs($this->ctx->user, $pData, $chosenShipId);
                        $unitsByTime = (int)floor($slotTimeMs / $unitTimeMs);

                        if ($unitsByTime < 1) {
                            // Pasa todo el tiempo al slot anterior (sNum - 1)
                            $rolloverTimeMs = $slotTimeMs;
                            continue;
                        }

                        $maxAffordable = $this->calculateTriResourceMaxUnits($pData, $fleetCredits, $chosenShipId, $lockedDefResFleet, $lockedDefAmountFleet);
                        $batch = min($unitsByTime, $maxAffordable);

                        if ($batch >= 1) {
                            $enqueued = (int)$this->gateway->constructUnits($pId, $chosenShipId, $batch);
                            if ($enqueued > 0) {
                                $recruited[$chosenShipId] = isset($recruited[$chosenShipId]) ? $recruited[$chosenShipId] + $enqueued : $enqueued;
                                $militaryFleetRecruitedTotal += $enqueued;
                                $queuedJobs++;

                                $costM = (float)($pricelist[$chosenShipId]['cost'][901] ?? 0) * $enqueued;
                                $costC = (float)($pricelist[$chosenShipId]['cost'][902] ?? 0) * $enqueued;
                                $costD = (float)($pricelist[$chosenShipId]['cost'][903] ?? 0) * $enqueued;

                                $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - $costM);
                                $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - $costC);
                                $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - $costD);

                                $pData = $this->refreshPlanetData($pId, $pData);
                                $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);

                                $usedTimeMs     = $enqueued * $unitTimeMs;
                                $rolloverTimeMs = max(0.0, $slotTimeMs - $usedTimeMs);
                            } else {
                                $rolloverTimeMs = $slotTimeMs;
                            }
                        } else {
                            // Pasa el tiempo al slot anterior (sNum - 1)
                            $rolloverTimeMs = $slotTimeMs;
                        }
                    }
                }
            }
        }

        // Marcas temporales desacopladas en BD
        // Solo se confirma reclutamiento si ActionGateway encoló unidades reales.
        // Los intentos con 0 unidades encoladas NO reinician el cooldown completo de 900s/180s,
        // pero establecen una pequeña ventana de reintento anti-saturación (60s) para no quemar CPU en cada tick.
        $now = BotClock::get();
        $updates = array();

        if ($militaryFleetRecruitedTotal > 0) {
            $updates['last_fleet_recruit'] = $now;
        } elseif ($canRunFleet) {
            $minRetryDelay = min(60, (int)($milInterval / 3));
            $updates['last_fleet_recruit'] = max((int)$lastFleetRecruit, $now - $milInterval + $minRetryDelay);
        }

        if ($defRecruitedTotal > 0) {
            $updates['last_defense_recruit'] = $now;
        } elseif ($canRunDefense) {
            $minDefRetry = min(60, (int)($defInterval / 3));
            $updates['last_defense_recruit'] = max((int)$lastDefRecruit, $now - $defInterval + $minDefRetry);
        }

        if ($militaryFleetRecruitedTotal > 0 || $defRecruitedTotal > 0) {
            $updates['last_recruit'] = $now;
        }

        if (!empty($updates)) {
            $this->ctx->updateBotRow($updates);
        }

        return $recruited;
    }

    /**
     * Métodos auxiliares y de compatibilidad pública
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
        $defDoctrine = $this->getOrAssignDefenseDoctrine();
        $elite = array();
        foreach (array($defDoctrine['def_slot4'], $defDoctrine['def_slot3'], $defDoctrine['def_slot2']) as $uId) {
            if ($uId > 0 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $uId, array())) {
                $elite[] = $uId;
            }
        }
        return $elite;
    }
}
