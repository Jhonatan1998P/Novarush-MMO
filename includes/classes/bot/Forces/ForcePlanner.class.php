<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';

/**
 * NovaRush Bot AI v2 - Force Planner
 *
 * Military force composition and dynamic progression planner for NovaRush.
 * Evaluates over 50 fleet units (202-267) and 60 defense units (401-460) in uni1_vars
 * using CombatCaps (attack, shield, defend, weapon class, armor class).
 *
 * Tactical Combined-Arms Doctrine:
 * 1. Dynamic Personality Budget:
 *    - Fleet, Defense, and Missiles scaled by BotPersonality (aggressive, defensive, balanced, economic).
 * 2. Strict Screening Fodder Quota (Target: 40% - 50%):
 *    - If Fodder >= 50% of combat fleet: PAUSE fodder recruitment completely and divert 100% of fodder budget to attack warships.
 *    - If Fodder < 50%: Split 60% Light Hunter (204) / 40% Heavy Hunter (205).
 * 3. Tri-Role Combined-Arms Strike Fleet:
 *    - Role 1 (Anti-Fodder Escort): Cruisers (206) - 25% warship budget
 *    - Role 2 (Main Battle Capital): Battleships (207) + Battlecruisers (215) - 45% warship budget
 *    - Role 3 (Heavy Capital / Siege): Destructors (213) / Bombers (211) / Galeons (225) - 30% warship budget
 *    - Real affordability filter: Never selects unbuildable phantom titans. Unspent budget cascades dynamically.
 * 4. Multi-Tier Layered Defense:
 *    - Shield Domes (407, 408) as absolute priority if missing.
 *    - Balanced defense recruitment between heavy turrets (Plasma/Dora/Gauss) and anti-shield (Ion/Laser).
 * 5. Silo & Anti-Missiles:
 *    - 60% Interceptors (502) / 40% Interplanetary Missiles (503).
 */
class BotForcePlanner
{
    private $ctx;
    private $gateway;

    const ROLE_ESCORT  = 'escort';   // Anti-fodder rapidfire screen killers (206, 263, 224, 245)
    const ROLE_CAPITAL = 'capital';  // Mainline battle line (207, 215, 234, 255, 235)
    const ROLE_HEAVY   = 'heavy';    // Heavy siege & capital anchors (213, 211, 225, 226, 214, 216, 246)

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
     * Gets or calibrates the bot's recruit interval and returns doctrinal metadata.
     * Window interval: 10 to 30 min (600 to 1800 seconds).
     */
    public function getOrAssignFleetDoctrine()
    {
        $heavy    = isset($this->ctx->botRow['doctrine_heavy']) ? (int)$this->ctx->botRow['doctrine_heavy'] : 0;
        $medium   = isset($this->ctx->botRow['doctrine_medium']) ? (int)$this->ctx->botRow['doctrine_medium'] : 0;
        $light    = isset($this->ctx->botRow['doctrine_light']) ? (int)$this->ctx->botRow['doctrine_light'] : 0;
        $fodder   = isset($this->ctx->botRow['doctrine_fodder']) ? (int)$this->ctx->botRow['doctrine_fodder'] : 0;
        $interval = isset($this->ctx->botRow['recruit_interval']) ? (int)$this->ctx->botRow['recruit_interval'] : 0;

        $heavyOptions   = array(225, 214, 216); // Galeon, Estrella de la Muerte, Luna Negra
        $mediumOptions  = array(213, 226, 211); // Destructor, Megadestructor, Bombardero
        $supportOptions = array(206, 207, 215); // Crucero, Nave de Batalla, Acorazado
        $fodderOptions  = array(204, 205);      // Caza Ligero, Caza Pesado

        $updates = array();

        if ($heavy > 0 && !in_array($heavy, $heavyOptions)) {
            $heavy = 0;
            $updates['doctrine_heavy'] = 0;
        }
        if ($medium > 0 && !in_array($medium, $mediumOptions)) {
            $medium = 0;
            $updates['doctrine_medium'] = 0;
        }
        if ($light > 0 && !in_array($light, $supportOptions)) {
            $light = 0;
            $updates['doctrine_light'] = 0;
        }
        if ($fodder > 0 && !in_array($fodder, $fodderOptions)) {
            $fodder = 0;
            $updates['doctrine_fodder'] = 0;
        }

        if ($interval < 600 || $interval > 1800) {
            $pers = $this->ctx->personality->name;
            if ($pers === BotPersonality::AGGRESSIVE || $pers === 'raider') {
                $interval = mt_rand(600, 900); // 10 a 15 min
            } elseif ($pers === BotPersonality::DEFENSIVE || $pers === BotPersonality::ECONOMIC || $pers === 'turtle') {
                $interval = mt_rand(1200, 1800); // 20 a 30 min
            } else {
                $interval = mt_rand(900, 1200); // 15 a 20 min (balanced)
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

    /**
     * Resuelve de forma dinámica y progresiva la unidad a reclutar para una de las 4 categorías:
     * - 'fodder'  (25%): Caza Ligero (204) o Caza Pesado (205) -> doctrine_fodder
     * - 'support' (30%): Crucero (206), Nave de Batalla (207), Acorazado (215) -> doctrine_light
     * - 'medium'  (25%): Destructor (213), Megadestructor (226), Bombardero (211) -> doctrine_medium
     * - 'heavy'   (20%): Galeón (225), Estrella de la Muerte (214), Luna Negra (216) -> doctrine_heavy
     *
     * Reglas del sistema:
     * 1. Si ya se eligió una unidad para siempre en BD (> 0):
     *    - Si está accesible en el planeta actual, se recluta esa unidad.
     *    - Si aún no está accesible en esta colonia, se usa la mejor unidad accesible de esa categoría,
     *      o fallback a la mejor unidad de combate.
     * 2. Si aún no está elegida para siempre (0):
     *    - 0 unidades de la categoría investigadas: Fallback a la mejor unidad disponible en combate.
     *    - 1 unidad de la categoría investigada (ej. solo Cruceros): Se recluta pero NO se tira el dado
     *      definitivo todavía hasta desbloquear las otras de la categoría.
     *    - >= 2 unidades de la categoría investigadas: ¡Se tira el dado para decidir la unidad final elegida
     *      para siempre por el bot! Se guarda en uni1_bots y se recluta esa unidad (o la mejor accesible).
     */
    public function resolveCategoryUnit($category, array $pData)
    {
        $categoryMap = array(
            'fodder'  => array('col' => 'doctrine_fodder', 'options' => array(204, 205)),
            'support' => array('col' => 'doctrine_light',  'options' => array(206, 207, 215)),
            'medium'  => array('col' => 'doctrine_medium', 'options' => array(213, 226, 211)),
            'heavy'   => array('col' => 'doctrine_heavy',  'options' => array(225, 214, 216)),
        );

        if (!isset($categoryMap[$category])) {
            return 204;
        }

        $col     = $categoryMap[$category]['col'];
        $options = $categoryMap[$category]['options'];

        $assigned = isset($this->ctx->botRow[$col]) ? (int)$this->ctx->botRow[$col] : 0;
        if (!in_array($assigned, $options)) {
            $assigned = 0;
        }

        // 1. Unidad ya elegida permanentemente
        if ($assigned > 0) {
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $assigned, array())) {
                return $assigned;
            }
            foreach ($options as $optId) {
                if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $optId, array())) {
                    return $optId;
                }
            }
            return $this->getBestFallbackCombatShip($pData, $category);
        }

        // 2. Unidad no elegida aún (assigned === 0): evaluar unidades investigadas en este planeta
        $accessible = array();
        foreach ($options as $optId) {
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $optId, array())) {
                $accessible[] = $optId;
            }
        }

        // Caso 2a: Ninguna unidad de la categoría investigada
        if (empty($accessible)) {
            return $this->getBestFallbackCombatShip($pData, $category);
        }

        // Caso 2b: Solo 1 unidad investigada en esta categoría (esquema progresivo sin fijar aún)
        if (count($accessible) === 1) {
            return $accessible[0];
        }

        // Caso 2c: >= 2 unidades desbloqueadas -> Tirar el dado para siempre
        $chosen = $options[array_rand($options)];
        $this->ctx->updateBotRow(array($col => $chosen));
        $this->ctx->botRow[$col] = $chosen;

        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $chosen, array())) {
            return $chosen;
        }

        return $accessible[0];
    }

    /**
     * Fallback cuando no se tienen investigadas las unidades de la categoría:
     * - Para Fodder: mayor puntos de escudo y casco en menor coste MSE: (Shield + Hull) / Coste_MSE
     * - Para Apoyo, Medios y Pesados: mayor puntos de daño en menor coste MSE: Attack / Coste_MSE
     */
    public function getBestFallbackCombatShip(array $pData, $category = 'support')
    {
        global $CombatCaps;

        $combatShipList = array(204, 205, 206, 207, 215, 211, 213, 226, 225, 214, 216);

        $bestShipId = 204;
        $bestScore  = -1.0;

        foreach ($combatShipList as $shipId) {
            if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $shipId, array())) {
                continue;
            }

            $caps = isset($CombatCaps[$shipId]) ? $CombatCaps[$shipId] : array();
            $att  = isset($caps['attack']) ? (float)$caps['attack'] : 10.0;
            $shd  = isset($caps['shield']) ? (float)$caps['shield'] : 10.0;
            $hull = isset($caps['defend']) ? (float)$caps['defend'] / 10.0 : 10.0;

            $costMSE = BotEconomyValuator::getElementPriceMSE($shipId);
            if ($costMSE <= 0.0) {
                $costMSE = 1.0;
            }

            if ($category === 'fodder') {
                $score = ($shd + $hull) / $costMSE;
            } else {
                $score = $att / $costMSE;
            }

            if ($score > $bestScore) {
                $bestScore  = $score;
                $bestShipId = $shipId;
            }
        }

        return $bestShipId;
    }

    private function refreshPlanetData($pId, array $pData)
    {
        if (method_exists($this->gateway, 'getFreshPlanet')) {
            return $this->gateway->getFreshPlanet($pId);
        }
        $fresh = Database::get()->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :pId;", array(':pId' => $pId));
        return !empty($fresh) ? $fresh : $pData;
    }

    /**
     * Calcula los segundos restantes en la cola de hangar sumando duraciones y restando b_hangar
     */
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

    /**
     * Anti-duplicación de slots en cola: comprueba si la unidad ya está en b_hangar_id
     */
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

    /**
     * Calcula el valor MSE total de las defensas existentes en un planeta
     */
    public function calculateExistingDefenseMSE(array $planet)
    {
        global $reslist, $resource;
        $totalMSE = 0.0;
        $defList = isset($reslist['defense']) && is_array($reslist['defense']) ? $reslist['defense'] : array();
        foreach ($defList as $defId) {
            $key = isset($resource[$defId]) ? $resource[$defId] : '';
            if (!empty($key) && isset($planet[$key]) && (int)$planet[$key] > 0) {
                $count = (int)$planet[$key];
                $costMSE = BotEconomyValuator::getElementPriceMSE($defId);
                $totalMSE += ($count * $costMSE);
            }
        }
        return $totalMSE;
    }

    /**
     * Fórmula tri-recurso min(M/costM, C/costC, D/costD) con reserva opcional de ahorro por categoría
     */
    public function calculateTriResourceMaxUnits(array $pData, array $categoryCredits, $unitId, $lockedDeficitRes = 0, $lockedDeficitAmount = 0.0)
    {
        global $pricelist;
        $costM = isset($pricelist[$unitId]['cost'][901]) ? (float)$pricelist[$unitId]['cost'][901] : 0.0;
        $costC = isset($pricelist[$unitId]['cost'][902]) ? (float)$pricelist[$unitId]['cost'][902] : 0.0;
        $costD = isset($pricelist[$unitId]['cost'][903]) ? (float)$pricelist[$unitId]['cost'][903] : 0.0;

        $availM = min((float)$pData['metal'], (float)($categoryCredits['metal'] ?? 0.0));
        $availC = min((float)$pData['crystal'], (float)($categoryCredits['crystal'] ?? 0.0));
        $availD = min((float)$pData['deuterium'], (float)($categoryCredits['deuterium'] ?? 0.0));

        // Si esta categoría tiene un ahorro activo, reservar únicamente el déficit correspondiente
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

    /**
     * Calcula el presupuesto de gasto autorizado para este ciclo (Válvula de Flujo Continuo con Vaciado de Excedentes 5%-10%)
     * Fórmula: SpendCap = IngresoFresco(deltaT) + (drainRate * ExcedenteAcumulado)
     *
     * @param int $pId
     * @param array $pData
     * @param string $category 'fleet'|'defense'
     * @param array $totalCredits
     * @param int $lastRecruitTime
     * @param bool $isEmergency
     * @return array [metal, crystal, deuterium]
     */
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

        // 1. Ingreso fresco producido en el intervalo
        $freshM = ($prodM * $elapsed / 3600.0) * $r_k;
        $freshC = ($prodC * $elapsed / 3600.0) * $r_k;
        $freshD = ($prodD * $elapsed / 3600.0) * $r_k;

        $totM = max(0.0, (float)($totalCredits['metal'] ?? 0.0));
        $totC = max(0.0, (float)($totalCredits['crystal'] ?? 0.0));
        $totD = max(0.0, (float)($totalCredits['deuterium'] ?? 0.0));

        // 2. Excedente acumulado por encima del ingreso fresco
        $surplusM = max(0.0, $totM - $freshM);
        $surplusC = max(0.0, $totC - $freshC);
        $surplusD = max(0.0, $totD - $freshD);

        // 3. Tasa de drenaje: 8% en condiciones normales (ventana 5%-10%), 100% en emergencia/asedio
        $drainRate = $isEmergency ? 1.00 : 0.08;

        $authM = min($totM, $freshM + ($drainRate * $surplusM));
        $authC = min($totC, $freshC + ($drainRate * $surplusC));
        $authD = min($totD, $freshD + ($drainRate * $surplusD));

        return array(
            'metal'     => round($authM, 2),
            'crystal'   => round($authC, 2),
            'deuterium' => round($authD, 2),
        );
    }

    /**
     * Determina el tamaño de lote constructible respetando cola, presupuesto de ciclo (SpendCap) y blindajes
     */
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
        $count = min($triMax, $maxConstructible);
        if ($count <= 0) {
            return 0;
        }

        $unitTime = max(0.001, (float)BuildFunctions::getBuildingTime($this->ctx->user, $pData, $unitId));
        $availQueueSec = max(0, $maxHangarDuration - $queueRemainingSec);
        $maxByQueueCap = (int)floor($availQueueSec / $unitTime);

        // El lote está dimensionado por el cupo financiero doctrinal del ciclo (SpendCap).
        // Se acota únicamente por el margen de seguridad de cola en hangar.
        $finalBatch = $count;
        if (!$isEmergency) {
            if ($availQueueSec <= 0) {
                return 0;
            }
            $finalBatch = min($finalBatch, max(1, $maxByQueueCap));
        }

        return max(0, $finalBatch);
    }

    /**
     * Modernización Progresiva de Flota Civil con Buffer de Seguridad
     * - Cadena 202 -> 203 -> 217
     * - Cadena 209 -> 219
     */
    public function upgradeCivilianFleets($planetId, array &$pData)
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

        // 1. Cadena 202 -> 203 -> 217
        $canBuild217 = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 217, array());
        if ($canBuild217 && !$this->isUnitInQueue($pData, 217) && $queuedJobs < $maxRegularSlots) {
            // Verificación estricta de Deuterio: 217 requiere 1,500 Deut por unidad; 202 y 203 dan 0 Deut al desguazarse
            $deutStock = (float)$pData['deuterium'];
            $max217ByDeut = (int)floor($deutStock / 1500.0);

            if ($max217ByDeut > 0) {
                $unitTime217 = max(0.001, (float)BuildFunctions::getBuildingTime($this->ctx->user, $pData, 217));
                $maxByBatchTime = max(1, (int)floor($batchTimeTarget / $unitTime217));
                $availQueueSec = max(0, $maxHangarDuration - $queueRemainingSec);
                $maxByQueueCap = max(1, (int)floor($availQueueSec / $unitTime217));
                $batchLimit217 = min($maxByBatchTime, $maxByQueueCap);

                // Mantener Buffer Operativo Mínimo: 100x 202 en tierra
                $cur202 = isset($pData[$resource[202]]) ? (int)$pData[$resource[202]] : 0;
                $avail202 = max(0, $cur202 - 100);
                // 18x 202 financian Metal (36k) y Cristal (36k) de 1x 217
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

                // Mantener Buffer Operativo Mínimo: 20x 203 en tierra (solo si 217 no fue ya encolado para evitar duplicar slot)
                if ($max217ByDeut > 0 && !$this->isUnitInQueue($pData, 217) && $queuedJobs < $maxRegularSlots && $queueRemainingSec < $maxHangarDuration) {
                    $cur203 = isset($pData[$resource[203]]) ? (int)$pData[$resource[203]] : 0;
                    $avail203 = max(0, $cur203 - 20);
                    // 6x 203 financian Metal (36k) y Cristal (36k) de 1x 217
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
            // Sub-cadena 202 -> 203 si 217 no es accesible aún
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

        // 2. Cadena 209 -> 219 (100x 209 = 1x 219)
        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 219, array()) && !$this->isUnitInQueue($pData, 219) && $queuedJobs < $maxRegularSlots && $queueRemainingSec < $maxHangarDuration) {
            $cur209 = isset($pData[$resource[209]]) ? (int)$pData[$resource[209]] : 0;
            $avail209 = max(0, $cur209 - 20); // Buffer de 20x 209
            $unitTime219 = max(0.001, (float)BuildFunctions::getBuildingTime($this->ctx->user, $pData, 219));
            $maxBatch219 = min(max(1, (int)floor($batchTimeTarget / $unitTime219)), max(1, (int)floor(max(0, $maxHangarDuration - $queueRemainingSec) / $unitTime219)));

            // Solo desguazar en múltiplos de 100 para unidades enteras de 219, o si el presupuesto cubre la diferencia
            $batches219 = min((int)floor($avail209 / 100), $maxBatch219);
            if ($batches219 > 0) {
                $scrap209 = $batches219 * 100;
                $ref = $this->gateway->scrapUnits($planetId, 209, $scrap209);
                if ($ref) {
                    $this->gateway->constructUnits($planetId, 219, $batches219);
                    $upgraded[219] = isset($upgraded[219]) ? $upgraded[219] + $batches219 : $batches219;
                    $pData = $this->refreshPlanetData($planetId, $pData);
                }
            } elseif ($avail209 >= 20 && $maxBatch219 >= 1) {
                // Presupuesto cubre la diferencia para 1x 219: 1x 219 = 1M Metal, 600k Cristal, 200k Deut
                $scrap209 = min(100, $avail209);
                $yieldM = $scrap209 * 10000.0;
                $yieldC = $scrap209 * 6000.0;
                $yieldD = $scrap209 * 2000.0;
                $diffM = max(0.0, 1000000.0 - $yieldM);
                $diffC = max(0.0, 600000.0 - $yieldC);
                $diffD = max(0.0, 200000.0 - $yieldD);

                $fleetCreds = isset($this->ctx->budgetManager) ? $this->ctx->budgetManager->getPlanetFleetCredits($planetId) : null;
                $hasBudget = ($fleetCreds === null || ($fleetCreds['metal'] >= $diffM && $fleetCreds['crystal'] >= $diffC && $fleetCreds['deuterium'] >= $diffD));
                $hasStock  = ((float)$pData['metal'] >= $diffM && (float)$pData['crystal'] >= $diffC && (float)$pData['deuterium'] >= $diffD);

                if ($hasBudget && $hasStock) {
                    $ref = $this->gateway->scrapUnits($planetId, 209, $scrap209);
                    if ($ref) {
                        $this->gateway->constructUnits($planetId, 219, 1);
                        $upgraded[219] = 1;
                        $pData = $this->refreshPlanetData($planetId, $pData);
                    }
                }
            }
        }

        return $upgraded;
    }

    /**
     * Plan and queue military recruitment across the empire with decoupled fleet and defense cycles
     *
     * @param BotEmpireState $empire
     * @param bool $forceRun Bypass timing check (useful for manual runs / tests)
     * @param BotWorldModel|null $world Optional perception model
     * @return array [unitId => count recruited]
     */
    public function planForces(BotEmpireState $empire, $forceRun = false, $world = null)
    {
        global $resource, $pricelist, $reslist, $CombatCaps;

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

        // Threat check for bypass
        $threatIncoming = false;
        if ($world && isset($world->threats) && method_exists($world->threats, 'getActiveThreats')) {
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

        foreach ($allBodies as $pId => $body) {
            $pData = $body['data'];

            $queuedJobs = !empty($pData['b_hangar_id']) ? count(@unserialize($pData['b_hangar_id'])) : 0;
            $maxQueueJobs = 8;

            // Dificultad y configuración de colas
            $maxHangarDuration = isset($this->ctx->difficulty->maxHangarQueueDuration) ? (int)$this->ctx->difficulty->maxHangarQueueDuration : 2700;
            $batchTimeTarget = isset($this->ctx->difficulty->batchTimeTarget) ? (int)$this->ctx->difficulty->batchTimeTarget : 900;

            $maxRegularSlots = isset($this->ctx->difficulty->maxRegularSlots) ? (int)$this->ctx->difficulty->maxRegularSlots : 6;

            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);

            // --- 1. Energy Stabilization: Solar Satellites (212) - Bypass inmediato (0s) ---
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

            // Si ambos ciclos militares están en cooldown y no hay bypass, pasar al siguiente planeta
            if (!$canRunFleet && !$canRunDefense) {
                continue;
            }

            // --- Modernización Progresiva de Flota Civil (Cadena 202->203->217 y 209->219) ---
            if ($canRunFleet) {
                $upgradedCiv = $this->upgradeCivilianFleets($pId, $pData);
                if (!empty($upgradedCiv)) {
                    foreach ($upgradedCiv as $civId => $civCount) {
                        $recruited[$civId] = isset($recruited[$civId]) ? $recruited[$civId] + $civCount : $civCount;
                        $fleetRecruitedTotal += $civCount;
                    }
                    $queuedJobs = !empty($pData['b_hangar_id']) ? count(@unserialize($pData['b_hangar_id'])) : 0;
                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                }
            }

            // Presupuestos tri-recurso con Válvula de Flujo Continuo y Vaciado de Excedentes (5%-10%)
            $rawFleetCredits = isset($this->ctx->budgetManager) 
                ? $this->ctx->budgetManager->getPlanetFleetCredits($pId) 
                : array('metal' => (float)$pData['metal'], 'crystal' => (float)$pData['crystal'], 'deuterium' => (float)$pData['deuterium']);
            $rawDefCredits = isset($this->ctx->budgetManager) 
                ? $this->ctx->budgetManager->getPlanetDefenseCredits($pId) 
                : array('metal' => (float)$pData['metal'], 'crystal' => (float)$pData['crystal'], 'deuterium' => (float)$pData['deuterium']);

            $isEmergency = ($threatIncoming || $isSiegeActive);

            $fleetCredits = $this->calculateCycleSpendBudget($pId, $pData, 'fleet', $rawFleetCredits, $lastFleetRecruit, $isEmergency);
            $defCredits   = $this->calculateCycleSpendBudget($pId, $pData, 'defense', $rawDefCredits, $lastDefRecruit, $isEmergency);

            $fleetLock = (isset($this->ctx->budgetManager) && method_exists($this->ctx->budgetManager, 'getSavingsLockForCategory'))
                ? $this->ctx->budgetManager->getSavingsLockForCategory($pId, 'fleet') : null;
            $lockedDefResFleet = ($fleetLock && !empty($fleetLock['deficit_resource'])) ? (int)$fleetLock['deficit_resource'] : 0;
            $lockedDefAmountFleet = ($fleetLock && isset($fleetLock['cost'][$lockedDefResFleet])) ? (float)$fleetLock['cost'][$lockedDefResFleet] : 0.0;

            $defLock = (isset($this->ctx->budgetManager) && method_exists($this->ctx->budgetManager, 'getSavingsLockForCategory'))
                ? $this->ctx->budgetManager->getSavingsLockForCategory($pId, 'defense') : null;
            $lockedDefResDef = ($defLock && !empty($defLock['deficit_resource'])) ? (int)$defLock['deficit_resource'] : 0;
            $lockedDefAmountDef = ($defLock && isset($defLock['cost'][$lockedDefResDef])) ? (float)$defLock['cost'][$lockedDefResDef] : 0.0;

            // --- 2. Missile & Silo Management (Exclusivo en el ciclo de reclutamiento de defensa) ---
            // Regla: Único lugar del código autorizado para reclutar misiles (502 / 503).
            // Siempre recluta y repone hasta el máximo que permita el nivel de silo.
            // Ratio objetivo: 60% Interceptores (502) / 40% Interplanetarios (503).
            // Apenas vea déficit, repone la cantidad suficiente para restablecer el ratio 60/40.
            // Al llegar a tope (o tras reponer el déficit), se pasa el presupuesto restante a defensas.
            $siloLevel = isset($pData[$resource[44]]) ? (int)$pData[$resource[44]] : 0;
            if ($canRunDefense && $queuedJobs < $maxQueueJobs && $siloLevel >= 2) {
                $rocketsCapacity = BuildFunctions::getMaxConstructibleRockets($this->ctx->user, $pData);
                $freeSpaces = isset($rocketsCapacity[502]) ? (int)$rocketsCapacity[502] : 0;
                $cur502 = isset($pData[$resource[502]]) ? (int)$pData[$resource[502]] : 0;
                $cur503 = isset($pData[$resource[503]]) ? (int)$pData[$resource[503]] : 0;
                $impulse = isset($this->ctx->user[$resource[117]]) ? (int)$this->ctx->user[$resource[117]] : 0;

                // Capacidad máxima total de misiles dada por el silo
                $totalSiloMax = $cur502 + $cur503 + $freeSpaces;

                $canBuild502 = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 502, array());
                $canBuild503 = ($siloLevel >= 4 && $impulse >= 1 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 503, array()));

                if ($canBuild502 && $canBuild503) {
                    $target502 = (int)floor($totalSiloMax * 0.60);
                    $target503 = (int)floor($totalSiloMax * 0.40);
                } elseif ($canBuild502) {
                    $target502 = $totalSiloMax;
                    $target503 = 0;
                } else {
                    $target502 = 0;
                    $target503 = 0;
                }

                $deficit502 = max(0, $target502 - $cur502);
                $deficit503 = max(0, $target503 - $cur503);

                // Reponer ABM (502) si hay déficit
                if ($deficit502 > 0 && $freeSpaces > 0 && $queuedJobs < $maxQueueJobs && !$this->isUnitInQueue($pData, 502)) {
                    $batch502 = $this->getConstructibleBatch($pData, $defCredits, 502, $queuedJobs, $maxQueueJobs, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, true, $lockedDefAmountDef);
                    $batch502 = min($batch502, $deficit502, $freeSpaces);
                    $enqueued502 = ($batch502 > 0) ? (int)$this->gateway->constructUnits($pId, 502, $batch502) : 0;
                    if ($enqueued502 > 0) {
                        $recruited[502] = isset($recruited[502]) ? $recruited[502] + $enqueued502 : $enqueued502;
                        $defRecruitedTotal += $enqueued502;
                        $queuedJobs++;
                        $freeSpaces -= $enqueued502;
                        $cur502 += $enqueued502;

                        $costM502 = (float)(isset($pricelist[502]['cost'][901]) ? $pricelist[502]['cost'][901] * $enqueued502 : 0);
                        $costC502 = (float)(isset($pricelist[502]['cost'][902]) ? $pricelist[502]['cost'][902] * $enqueued502 : 0);
                        $costD502 = (float)(isset($pricelist[502]['cost'][903]) ? $pricelist[502]['cost'][903] * $enqueued502 : 0);
                        $defCredits['metal']     = max(0.0, $defCredits['metal'] - $costM502);
                        $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - $costC502);
                        $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - $costD502);

                        $pData = $this->refreshPlanetData($pId, $pData);
                        $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                    }
                }

                // Reponer MIP (503) si hay déficit
                if ($deficit503 > 0 && $freeSpaces > 0 && $canBuild503 && $queuedJobs < $maxRegularSlots && !$this->isUnitInQueue($pData, 503)) {
                    $batch503 = $this->getConstructibleBatch($pData, $defCredits, 503, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                    $batch503 = min($batch503, $deficit503, $freeSpaces);
                    $enqueued503 = ($batch503 > 0) ? (int)$this->gateway->constructUnits($pId, 503, $batch503) : 0;
                    if ($enqueued503 > 0) {
                        $recruited[503] = isset($recruited[503]) ? $recruited[503] + $enqueued503 : $enqueued503;
                        $defRecruitedTotal += $enqueued503;
                        $queuedJobs++;
                        $freeSpaces -= $enqueued503;
                        $cur503 += $enqueued503;

                        $costM503 = (float)(isset($pricelist[503]['cost'][901]) ? $pricelist[503]['cost'][901] * $enqueued503 : 0);
                        $costC503 = (float)(isset($pricelist[503]['cost'][902]) ? $pricelist[503]['cost'][902] * $enqueued503 : 0);
                        $costD503 = (float)(isset($pricelist[503]['cost'][903]) ? $pricelist[503]['cost'][903] * $enqueued503 : 0);
                        $defCredits['metal']     = max(0.0, $defCredits['metal'] - $costM503);
                        $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - $costC503);
                        $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - $costD503);

                        $pData = $this->refreshPlanetData($pId, $pData);
                        $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                    }
                }
            }

            // --- 3. Planetary Defense Management ---
            // Al estar a tope los misiles o atendido el déficit, el presupuesto se vuelca a defensas:
            // - Prioridad 0: Cúpulas (407, 408) si faltan.
            // - 40% del presupuesto para el Top 3 de armas de alto daño (plasma/graviton con fuego rápido).
            // - 60% (+ sobrante del 40%) para la mejor carne de cañón con ataque tipo iónico.
            if ($canRunDefense) {
                // Prioridad 1: Cúpulas (407, 408) si faltan
                if (!empty($reslist['domes'])) {
                    foreach ($reslist['domes'] as $domeId) {
                        if ($queuedJobs >= $maxRegularSlots) break;
                        $curDome = isset($pData[$resource[$domeId]]) ? (int)$pData[$resource[$domeId]] : 0;
                        if ($curDome == 0 && !$this->isUnitInQueue($pData, $domeId) && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $domeId, array())) {
                            $batchDome = $this->getConstructibleBatch($pData, $defCredits, $domeId, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                            $enqueuedDome = ($batchDome >= 1) ? (int)$this->gateway->constructUnits($pId, $domeId, 1) : 0;
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

                // Reclutamiento de Defensas: escalamiento continuo sin techo artificial, regulado por créditos de presupuesto
                if ($queuedJobs < $maxRegularSlots) {
                    $eliteShare = 0.40;
                    $elitePool = array(
                        'metal'     => $defCredits['metal'] * $eliteShare,
                        'crystal'   => $defCredits['crystal'] * $eliteShare,
                        'deuterium' => $defCredits['deuterium'] * $eliteShare,
                    );

                    // A. 40% Defensas de Élite (Top 1, 2 y 3 de daño con plasma/graviton y fuego rápido, con fallback a mayor daño)
                    $eliteTriad = $this->getEliteDefenseTriad($pData);
                    if (!empty($eliteTriad)) {
                        $remainingEliteRoles = count($eliteTriad);
                        foreach ($eliteTriad as $eliteDefId) {
                            if ($queuedJobs >= $maxRegularSlots) break;
                            $allocFraction = ($remainingEliteRoles <= 1) ? 1.00 : (1.0 / $remainingEliteRoles);
                            $targetRoleCredits = array(
                                'metal'     => $elitePool['metal'] * $allocFraction,
                                'crystal'   => $elitePool['crystal'] * $allocFraction,
                                'deuterium' => $elitePool['deuterium'] * $allocFraction,
                            );

                            $batchDef = $this->getConstructibleBatch($pData, $targetRoleCredits, $eliteDefId, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                            $enqueuedDef = ($batchDef > 0) ? (int)$this->gateway->constructUnits($pId, $eliteDefId, $batchDef) : 0;
                            if ($enqueuedDef > 0) {
                                $recruited[$eliteDefId] = isset($recruited[$eliteDefId]) ? $recruited[$eliteDefId] + $enqueuedDef : $enqueuedDef;
                                $defRecruitedTotal += $enqueuedDef;
                                $queuedJobs++;

                                $costMDef = (float)(isset($pricelist[$eliteDefId]['cost'][901]) ? $pricelist[$eliteDefId]['cost'][901] * $enqueuedDef : 0);
                                $costCDef = (float)(isset($pricelist[$eliteDefId]['cost'][902]) ? $pricelist[$eliteDefId]['cost'][902] * $enqueuedDef : 0);
                                $costDDef = (float)(isset($pricelist[$eliteDefId]['cost'][903]) ? $pricelist[$eliteDefId]['cost'][903] * $enqueuedDef : 0);

                                $elitePool['metal']     = max(0.0, $elitePool['metal'] - $costMDef);
                                $elitePool['crystal']   = max(0.0, $elitePool['crystal'] - $costCDef);
                                $elitePool['deuterium'] = max(0.0, $elitePool['deuterium'] - $costDDef);

                                $defCredits['metal']     = max(0.0, $defCredits['metal'] - $costMDef);
                                $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - $costCDef);
                                $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - $costDDef);

                                $pData = $this->refreshPlanetData($pId, $pData);
                                $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                            }
                            $remainingEliteRoles--;
                        }
                    }

                    // B. Carne de Cañón Iónica: consume el 100% del saldo remanente de defensa ($defCredits)
                    // (60% base de defensa más cualquier excedente no gastado por las unidades de élite)
                    if ($queuedJobs < $maxRegularSlots) {
                        $fodderCredits = array(
                            'metal'     => max(0.0, (float)$defCredits['metal']),
                            'crystal'   => max(0.0, (float)$defCredits['crystal']),
                            'deuterium' => max(0.0, (float)$defCredits['deuterium']),
                        );

                        $bestFodderId = $this->getBestIonicFodderDefense($pData);
                        if ($bestFodderId > 0 && !$this->isUnitInQueue($pData, $bestFodderId)) {
                            $batchFodder = $this->getConstructibleBatch($pData, $fodderCredits, $bestFodderId, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResDef, false, $lockedDefAmountDef);
                            $enqueuedFodder = ($batchFodder > 0) ? (int)$this->gateway->constructUnits($pId, $bestFodderId, $batchFodder) : 0;
                            if ($enqueuedFodder > 0) {
                                $recruited[$bestFodderId] = isset($recruited[$bestFodderId]) ? $recruited[$bestFodderId] + $enqueuedFodder : $enqueuedFodder;
                                $defRecruitedTotal += $enqueuedFodder;
                                $queuedJobs++;

                                $costMFodder = (float)(isset($pricelist[$bestFodderId]['cost'][901]) ? $pricelist[$bestFodderId]['cost'][901] * $enqueuedFodder : 0);
                                $costCFodder = (float)(isset($pricelist[$bestFodderId]['cost'][902]) ? $pricelist[$bestFodderId]['cost'][902] * $enqueuedFodder : 0);
                                $costDFodder = (float)(isset($pricelist[$bestFodderId]['cost'][903]) ? $pricelist[$bestFodderId]['cost'][903] * $enqueuedFodder : 0);

                                $defCredits['metal']     = max(0.0, $defCredits['metal'] - $costMFodder);
                                $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - $costCFodder);
                                $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - $costDFodder);

                                $pData = $this->refreshPlanetData($pId, $pData);
                                $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                            }
                        }
                    }
                }
            }

            // --- 4. Fleet Planning: 100% Unified Fleet Budget ---
            // 20% Pesados, 25% Medios, 30% Apoyo, 20% Fodder, 5% Carga (2.5% Recicladores, 2.5% Transportadores)
            if ($canRunFleet && $queuedJobs < $maxRegularSlots) {
                // Sondas de Espionaje (Mínimo táctico operativo < 60 sondas para soportar misiones de 30+ sondas)
                $curProbes = isset($pData[$resource[210]]) ? (int)$pData[$resource[210]] : 0;
                if ($curProbes < 60 && $queuedJobs < $maxRegularSlots && !$this->isUnitInQueue($pData, 210) && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 210, array())) {
                    $utilProbeCredits = array(
                        'metal'     => $fleetCredits['metal'] * 0.02,
                        'crystal'   => $fleetCredits['crystal'] * 0.02,
                        'deuterium' => $fleetCredits['deuterium'] * 0.02,
                    );
                    $batchProbe = $this->getConstructibleBatch($pData, $utilProbeCredits, 210, $queuedJobs, $maxRegularSlots, $queueRemainingSec, $maxHangarDuration, $batchTimeTarget, $lockedDefResFleet, false, $lockedDefAmountFleet);
                    $batchProbe = min($batchProbe, 60 - $curProbes);
                    $enqueuedProbe = ($batchProbe > 0) ? (int)$this->gateway->constructUnits($pId, 210, $batchProbe) : 0;
                    if ($enqueuedProbe > 0) {
                        $recruited[210] = isset($recruited[210]) ? $recruited[210] + $enqueuedProbe : $enqueuedProbe;
                        $fleetRecruitedTotal += $enqueuedProbe;
                        $queuedJobs++;

                        $costMP = (float)(isset($pricelist[210]['cost'][901]) ? $pricelist[210]['cost'][901] * $enqueuedProbe : 0);
                        $costCP = (float)(isset($pricelist[210]['cost'][902]) ? $pricelist[210]['cost'][902] * $enqueuedProbe : 0);
                        $costDP = (float)(isset($pricelist[210]['cost'][903]) ? $pricelist[210]['cost'][903] * $enqueuedProbe : 0);
                        $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - $costMP);
                        $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - $costCP);
                        $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - $costDP);

                        $pData = $this->refreshPlanetData($pId, $pData);
                        $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                    }
                }

                // Reclutamiento Unificado de Flota: 6 Categorías Doctrinales con Cascada Completa
                // Reparto: 20% Pesados, 25% Medios, 30% Apoyo, 20% Fodder, 2.5% Recicladores, 2.5% Transportadores
                if ($queuedJobs < $maxRegularSlots) {
                    $unitHeavy       = $this->resolveCategoryUnit('heavy', $pData);
                    $unitMedium      = $this->resolveCategoryUnit('medium', $pData);
                    $unitSupport     = $this->resolveCategoryUnit('support', $pData);
                    $unitFodder      = $this->resolveCategoryUnit('fodder', $pData);
                    $unitRecycler    = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 219, array()) ? 219 : (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 209, array()) ? 209 : 0);
                    $unitTransporter = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 217, array()) ? 217 : (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 203, array()) ? 203 : (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 202, array()) ? 202 : 0));

                    $categoryConfigs = array(
                        'heavy'       => array('unit' => $unitHeavy,       'share' => 0.200, 'priority' => 1),
                        'medium'      => array('unit' => $unitMedium,      'share' => 0.250, 'priority' => 2),
                        'support'     => array('unit' => $unitSupport,     'share' => 0.300, 'priority' => 3),
                        'fodder'      => array('unit' => $unitFodder,      'share' => 0.200, 'priority' => 4),
                        'recycler'    => array('unit' => $unitRecycler,    'share' => 0.025, 'priority' => 5),
                        'transporter' => array('unit' => $unitTransporter, 'share' => 0.025, 'priority' => 6),
                    );

                    // Agrupar unidades coincidentes para no duplicar slots de cola en el hangar
                    $initialFleetCredits = $fleetCredits;
                    $cascadedExtra = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
                    $unitPools = array();

                    foreach ($categoryConfigs as $catKey => $cCfg) {
                        $uId = (int)$cCfg['unit'];
                        if ($uId <= 0) {
                            // Si no hay tecnología para esta categoría, su cuota se transfiere directamente a la cascada
                            $cascadedExtra['metal']     += $initialFleetCredits['metal'] * $cCfg['share'];
                            $cascadedExtra['crystal']   += $initialFleetCredits['crystal'] * $cCfg['share'];
                            $cascadedExtra['deuterium'] += $initialFleetCredits['deuterium'] * $cCfg['share'];
                            continue;
                        }
                        if (!isset($unitPools[$uId])) {
                            $unitPools[$uId] = array(
                                'unit_id'    => $uId,
                                'share'      => 0.0,
                                'priority'   => $cCfg['priority'],
                                'categories' => array(),
                            );
                        }
                        $unitPools[$uId]['share'] += $cCfg['share'];
                        $unitPools[$uId]['categories'][] = $catKey;
                        $unitPools[$uId]['priority'] = min($unitPools[$uId]['priority'], $cCfg['priority']);
                    }

                    // Ordenar por prioridad: Pesados (1) -> Medios (2) -> Apoyo (3) -> Carne de Cañón (4)
                    uasort($unitPools, function($a, $b) {
                        return $a['priority'] - $b['priority'];
                    });

                    $poolIndex = 0;
                    $totalPools = count($unitPools);

                    foreach ($unitPools as $uId => $pool) {
                        $poolIndex++;
                        $isLastPool = ($poolIndex === $totalPools);

                        if ($queuedJobs >= $maxRegularSlots) {
                            break;
                        }

                        if ($isLastPool) {
                            // La última categoría absorbe todo el remanente disponible de flota
                            $poolCredits = array(
                                'metal'     => max(0.0, (float)$fleetCredits['metal']),
                                'crystal'   => max(0.0, (float)$fleetCredits['crystal']),
                                'deuterium' => max(0.0, (float)$fleetCredits['deuterium']),
                            );
                        } else {
                            $targetM = ($initialFleetCredits['metal'] * $pool['share']) + $cascadedExtra['metal'];
                            $targetC = ($initialFleetCredits['crystal'] * $pool['share']) + $cascadedExtra['crystal'];
                            $targetD = ($initialFleetCredits['deuterium'] * $pool['share']) + $cascadedExtra['deuterium'];

                            $poolCredits = array(
                                'metal'     => min(max(0.0, (float)$fleetCredits['metal']), max(0.0, $targetM)),
                                'crystal'   => min(max(0.0, (float)$fleetCredits['crystal']), max(0.0, $targetC)),
                                'deuterium' => min(max(0.0, (float)$fleetCredits['deuterium']), max(0.0, $targetD)),
                            );
                        }
                        $cascadedExtra = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

                        $batch = $this->getConstructibleBatch(
                            $pData,
                            $poolCredits,
                            $uId,
                            $queuedJobs,
                            $maxRegularSlots,
                            $queueRemainingSec,
                            $maxHangarDuration,
                            $batchTimeTarget,
                            $lockedDefResFleet,
                            false,
                            $lockedDefAmountFleet
                        );

                        $enqueuedWarship = ($batch > 0) ? (int)$this->gateway->constructUnits($pId, $uId, $batch) : 0;
                        if ($enqueuedWarship > 0) {
                            $recruited[$uId] = isset($recruited[$uId]) ? $recruited[$uId] + $enqueuedWarship : $enqueuedWarship;
                            $fleetRecruitedTotal += $enqueuedWarship;
                            $queuedJobs++;

                            $costM = (float)(isset($pricelist[$uId]['cost'][901]) ? $pricelist[$uId]['cost'][901] * $enqueuedWarship : 0);
                            $costC = (float)(isset($pricelist[$uId]['cost'][902]) ? $pricelist[$uId]['cost'][902] * $enqueuedWarship : 0);
                            $costD = (float)(isset($pricelist[$uId]['cost'][903]) ? $pricelist[$uId]['cost'][903] * $enqueuedWarship : 0);

                            $poolCredits['metal']     = max(0.0, $poolCredits['metal'] - $costM);
                            $poolCredits['crystal']   = max(0.0, $poolCredits['crystal'] - $costC);
                            $poolCredits['deuterium'] = max(0.0, $poolCredits['deuterium'] - $costD);

                            $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - $costM);
                            $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - $costC);
                            $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - $costD);

                            $pData = $this->refreshPlanetData($pId, $pData);
                            $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                        }

                        // Cascada hacia la siguiente categoría
                        if (!$isLastPool) {
                            $cascadedExtra['metal']     += $poolCredits['metal'];
                            $cascadedExtra['crystal']   += $poolCredits['crystal'];
                            $cascadedExtra['deuterium'] += $poolCredits['deuterium'];
                        } else {
                            // Barrido final en la última unidad si quedan créditos remanentes suficientes
                            if ($queuedJobs < $maxRegularSlots && ($poolCredits['metal'] > 3000 || $poolCredits['crystal'] > 1000)) {
                                $batchFinal = $this->getConstructibleBatch(
                                    $pData,
                                    $poolCredits,
                                    $uId,
                                    $queuedJobs,
                                    $maxRegularSlots,
                                    $queueRemainingSec,
                                    $maxHangarDuration,
                                    $batchTimeTarget,
                                    $lockedDefResFleet,
                                    false,
                                    $lockedDefAmountFleet
                                );
                                $enqueuedFinal = ($batchFinal > 0) ? (int)$this->gateway->constructUnits($pId, $uId, $batchFinal) : 0;
                                if ($enqueuedFinal > 0) {
                                    $recruited[$uId] = isset($recruited[$uId]) ? $recruited[$uId] + $enqueuedFinal : $enqueuedFinal;
                                    $fleetRecruitedTotal += $enqueuedFinal;
                                    $queuedJobs++;

                                    $costMF = (float)(isset($pricelist[$uId]['cost'][901]) ? $pricelist[$uId]['cost'][901] * $enqueuedFinal : 0);
                                    $costCF = (float)(isset($pricelist[$uId]['cost'][902]) ? $pricelist[$uId]['cost'][902] * $enqueuedFinal : 0);
                                    $costDF = (float)(isset($pricelist[$uId]['cost'][903]) ? $pricelist[$uId]['cost'][903] * $enqueuedFinal : 0);

                                    $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - $costMF);
                                    $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - $costCF);
                                    $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - $costDF);

                                    $pData = $this->refreshPlanetData($pId, $pData);
                                    $queueRemainingSec = $this->calculateQueueRemainingSeconds($pData);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Marcas temporales desacopladas
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
     * Selects specialized warships according to the perfected tri-role doctrine:
     * - Fallback sin solapamiento:
     *   Rol Pesado: 225, 226, 216, 214, 213
     *   Rol Capital: 215, 211, 207
     *   Rol Escolta: 206, 207, 205
     * - Compensación activa de déficit de flota en combate (priorizar el rol que haya sufrido bajas)
     */
    public function selectTacticalWarships(array $pData, $attackBudget, array $doctrine)
    {
        global $resource;

        $heavyList   = array(225, 226, 216, 214, 213);
        $capitalList = array(215, 211, 207);
        $escortList  = array(206, 207, 205);

        // 1. Rol Pesado
        $chosenHeavy = null;
        if (!empty($doctrine['heavy']) && in_array((int)$doctrine['heavy'], $heavyList) && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, (int)$doctrine['heavy'], array())) {
            $chosenHeavy = (int)$doctrine['heavy'];
        } else {
            foreach ($heavyList as $hId) {
                if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $hId, array())) {
                    $chosenHeavy = $hId;
                    break;
                }
            }
        }
        if ($chosenHeavy === null) {
            $chosenHeavy = 213;
        }

        // 2. Rol Capital
        $chosenCapital = null;
        if (!empty($doctrine['medium']) && in_array((int)$doctrine['medium'], $capitalList) && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, (int)$doctrine['medium'], array())) {
            $chosenCapital = (int)$doctrine['medium'];
        } else {
            foreach ($capitalList as $cId) {
                if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $cId, array())) {
                    $chosenCapital = $cId;
                    break;
                }
            }
        }
        if ($chosenCapital === null) {
            $chosenCapital = 207;
        }

        // 3. Rol Escolta (sin solapamiento con el rol capital)
        $chosenEscort = null;
        if (!empty($doctrine['light']) && in_array((int)$doctrine['light'], $escortList) && (int)$doctrine['light'] !== $chosenCapital && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, (int)$doctrine['light'], array())) {
            $chosenEscort = (int)$doctrine['light'];
        } else {
            foreach ($escortList as $eId) {
                if ($eId === $chosenCapital) {
                    continue; // Evitar solapamiento con el rol capital
                }
                if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $eId, array())) {
                    $chosenEscort = $eId;
                    break;
                }
            }
        }
        if ($chosenEscort === null) {
            $chosenEscort = ($chosenCapital === 207) ? 206 : 205;
        }

        // Compensación activa de déficit de flota en combate (priorizar el rol que haya sufrido bajas)
        $curHeavyCount = 0;
        foreach ($heavyList as $hId) {
            $curHeavyCount += isset($pData[$resource[$hId]]) ? (int)$pData[$resource[$hId]] : 0;
        }
        $curCapitalCount = 0;
        foreach ($capitalList as $cId) {
            if ($cId === 207 && $chosenEscort === 207) {
                continue; // 207 asignado como escolta, no contar como capital
            }
            $curCapitalCount += isset($pData[$resource[$cId]]) ? (int)$pData[$resource[$cId]] : 0;
        }
        $curEscortCount = 0;
        foreach ($escortList as $eId) {
            if ($eId === 207 && $chosenEscort !== 207) {
                continue; // 207 asignado como capital, no contar como escolta
            }
            $curEscortCount += isset($pData[$resource[$eId]]) ? (int)$pData[$resource[$eId]] : 0;
        }

        $totalWarships = $curHeavyCount + $curCapitalCount + $curEscortCount;
        $targetShareHeavy   = 0.35;
        $targetShareCapital = 0.35;
        $targetShareEscort  = 0.30;

        if ($totalWarships > 0) {
            $actualShareHeavy   = $curHeavyCount / $totalWarships;
            $actualShareCapital = $curCapitalCount / $totalWarships;
            $actualShareEscort  = $curEscortCount / $totalWarships;

            $defHeavy   = max(0.0, $targetShareHeavy - $actualShareHeavy);
            $defCapital = max(0.0, $targetShareCapital - $actualShareCapital);
            $defEscort  = max(0.0, $targetShareEscort - $actualShareEscort);

            $shareHeavy   = $targetShareHeavy + ($defHeavy * 0.6);
            $shareCapital = $targetShareCapital + ($defCapital * 0.6);
            $shareEscort  = $targetShareEscort + ($defEscort * 0.6);

            $sumShares = $shareHeavy + $shareCapital + $shareEscort;
            $shareHeavy   = round($shareHeavy / $sumShares, 4);
            $shareCapital = round($shareCapital / $sumShares, 4);
            $shareEscort  = round(1.0 - $shareHeavy - $shareCapital, 4);
        } else {
            $shareHeavy   = $targetShareHeavy;
            $shareCapital = $targetShareCapital;
            $shareEscort  = $targetShareEscort;
        }

        $selected = array();
        $roles = array(
            array('role' => self::ROLE_HEAVY,   'unit_id' => $chosenHeavy,   'share' => $shareHeavy),
            array('role' => self::ROLE_CAPITAL, 'unit_id' => $chosenCapital, 'share' => $shareCapital),
            array('role' => self::ROLE_ESCORT,  'unit_id' => $chosenEscort,  'share' => $shareEscort)
        );

        foreach ($roles as $r) {
            $uId = $r['unit_id'];
            $score = $this->calculateShipScore($uId, $pData);
            $selected[] = array(
                'role'    => $r['role'],
                'unit_id' => $uId,
                'score'   => $score,
                'share'   => $r['share']
            );
        }

        return $selected;
    }

    /**
     * Technical scoring for warships based on firepower, defense, weapon/armor class and personality
     */
    private function calculateShipScore($shipId, array $pData)
    {
        global $CombatCaps;

        $caps = isset($CombatCaps[$shipId]) ? $CombatCaps[$shipId] : array();
        $att  = isset($caps['attack']) ? (float)$caps['attack'] : 10.0;
        $shd  = isset($caps['shield']) ? (float)$caps['shield'] : 10.0;
        $def  = isset($caps['defend']) ? (float)$caps['defend'] / 10.0 : 10.0;
        $rawPower = $att + $shd + $def;

        $weaponMult = 1.0;
        $hasIon = false;
        $hasPlasmaOrGrav = false;

        if (isset($caps['type_gun'])) {
            $gun = strtolower((string)$caps['type_gun']);
            if (strpos($gun, 'gravity') !== false) {
                $weaponMult = max($weaponMult, 1.80);
                $hasPlasmaOrGrav = true;
            }
            if (strpos($gun, 'plasma') !== false) {
                $weaponMult = max($weaponMult, 1.50);
                $hasPlasmaOrGrav = true;
            }
            if (strpos($gun, 'ion') !== false) {
                $weaponMult = max($weaponMult, 1.30);
                $hasIon = true;
            }
            if (strpos($gun, 'laser') !== false) {
                $weaponMult = max($weaponMult, 1.10);
            }
        }

        $armorMult = 1.0;
        $hasMediumOrHeavyShield = false;

        if (isset($caps['type_defend'])) {
            $arm = strtolower((string)$caps['type_defend']);
            if ($arm === 'heavy') {
                $armorMult = 1.40;
                $hasMediumOrHeavyShield = true;
            } elseif ($arm === 'medium') {
                $armorMult = 1.25;
                $hasMediumOrHeavyShield = true;
            }
        }

        $effectivePower = $rawPower * $weaponMult * $armorMult;
        $costMSE        = BotEconomyValuator::getElementPriceMSE($shipId);
        $costEfficiency = $effectivePower / max(1.0, $costMSE);

        $superPlus = 1.0;
        if (($hasIon && $hasPlasmaOrGrav) || ($rawPower >= 800.0 && $hasMediumOrHeavyShield && $costMSE <= 350000.0)) {
            $superPlus = 2.0;
        }

        $qualityScore = sqrt(max(1.0, $effectivePower)) * $costEfficiency * 60.0 * $superPlus;

        // Phase 5 Dimensional combat power: ((Ataque + Escudo + Casco/10) / Coste_MSE) * 1000 * ArchetypeMultiplier
        $dimensionalScore = ($rawPower / max(1.0, $costMSE)) * 1000.0;
        $archFleetMult    = $this->ctx->personality->getCategoryMultiplier('fleet');

        $finalScore = ($dimensionalScore + $qualityScore) * $archFleetMult;

        if ($rawPower > 5000.0)  $finalScore += 250.0;
        if ($rawPower > 25000.0) $finalScore += 500.0;

        return round($finalScore, 2);
    }

    /**
     * Dynamic evaluation of planetary defenses
     *
     * @param array $pData
     * @param string|null $categoryFilter 'light', 'heavy', or null
     * @return array
     */
    public function evaluateDefenseUnits(array $pData, $categoryFilter = null)
    {
        global $reslist, $CombatCaps;

        $defList = (isset($reslist['defense']) && is_array($reslist['defense'])) ? $reslist['defense'] : array();
        $candidates = array();

        $lightIds = array(401, 402, 403, 405);

        foreach ($defList as $defId) {
            $defId = (int)$defId;
            if (in_array($defId, $reslist['domes']) || in_array($defId, $reslist['missile'])) {
                continue;
            }

            if ($categoryFilter === 'light' && !in_array($defId, $lightIds)) {
                continue;
            } elseif ($categoryFilter === 'heavy' && in_array($defId, $lightIds)) {
                continue;
            }

            if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $defId, array())) {
                continue;
            }

            $caps = isset($CombatCaps[$defId]) ? $CombatCaps[$defId] : array();
            $att  = isset($caps['attack']) ? (float)$caps['attack'] : 10.0;
            $shd  = isset($caps['shield']) ? (float)$caps['shield'] : 10.0;
            $def  = isset($caps['defend']) ? (float)$caps['defend'] / 10.0 : 10.0;
            $rawPower = $att + $shd + $def;

            $weaponMult = 1.0;
            if (isset($caps['type_gun'])) {
                $gun = strtolower((string)$caps['type_gun']);
                if (strpos($gun, 'gravity') !== false) $weaponMult = max($weaponMult, 1.80);
                if (strpos($gun, 'plasma') !== false)  $weaponMult = max($weaponMult, 1.50);
                if (strpos($gun, 'ion') !== false)     $weaponMult = max($weaponMult, 1.30);
                if (strpos($gun, 'laser') !== false)   $weaponMult = max($weaponMult, 1.10);
            }

            $effectivePower = $rawPower * $weaponMult;
            $costMSE        = BotEconomyValuator::getElementPriceMSE($defId);
            $costEfficiency = $effectivePower / max(1.0, $costMSE);

            $qualityScore = sqrt(max(1.0, $effectivePower)) * $costEfficiency * 50.0;

            // Phase 5 Dimensional combat power: ((Ataque + Escudo + Casco/10) / Coste_MSE) * 1000 * ArchetypeMultiplier
            $dimensionalScore = ($rawPower / max(1.0, $costMSE)) * 1000.0;
            $archDefMult      = $this->ctx->personality->getCategoryMultiplier('defense');

            $finalScore = ($dimensionalScore + $qualityScore) * $archDefMult;
            if ($rawPower > 1500.0) $finalScore += 200.0;

            $candidates[] = array(
                'unit_id' => $defId,
                'power'   => $effectivePower,
                'score'   => round($finalScore, 2)
            );
        }

        usort($candidates, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $candidates;
    }

    /**
     * Encuentra la mejor defensa "carne de cañón" con ataque de tipo Iónico (para el 60% del presupuesto de defensa).
     * Evalúa las unidades defensivas accesibles cuyo type_gun contenga 'ion',
     * ordenadas por mayor eficiencia de absorción de daño: (Casco + Escudo) / Coste_MSE.
     * Si ninguna defensa con ataque iónico está disponible o investigada,
     * utiliza como fallback la defensa ligera investigada más eficiente (401 o 402).
     *
     * @param array $pData
     * @return int ID de la mejor unidad defensiva de carne de cañón iónica
     */
    public function getBestIonicFodderDefense(array $pData)
    {
        global $reslist, $CombatCaps;

        $defList = (isset($reslist['defense']) && is_array($reslist['defense'])) ? $reslist['defense'] : array();
        $candidates = array();

        foreach ($defList as $defId) {
            $defId = (int)$defId;
            if (in_array($defId, $reslist['domes']) || in_array($defId, $reslist['missile'])) {
                continue;
            }

            if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $defId, array())) {
                continue;
            }

            $caps = isset($CombatCaps[$defId]) ? $CombatCaps[$defId] : array();
            $gun  = strtolower((string)($caps['type_gun'] ?? ''));

            if (strpos($gun, 'ion') !== false) {
                $shd = isset($caps['shield']) ? (float)$caps['shield'] : 10.0;
                $def = isset($caps['defend']) ? (float)$caps['defend'] / 10.0 : 10.0;
                $costMSE = BotEconomyValuator::getElementPriceMSE($defId);
                $fodderEfficiency = ($shd + $def) / max(1.0, $costMSE);

                $candidates[] = array(
                    'unit_id'    => $defId,
                    'efficiency' => $fodderEfficiency,
                );
            }
        }

        if (!empty($candidates)) {
            usort($candidates, function($a, $b) {
                return $b['efficiency'] <=> $a['efficiency'];
            });
            return $candidates[0]['unit_id'];
        }

        // Fallback: si aún no hay tecnología iónica investigada, usar la mejor defensa ligera disponible (401 o 402)
        $fallbacks = array(401, 402);
        foreach ($fallbacks as $fId) {
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $fId, array())) {
                return $fId;
            }
        }

        return 401;
    }

    /**
     * Encuentra la tríada (Top 1, 2 y 3) de defensas de alto daño para el 40% del presupuesto.
     * Criterios estrictos:
     * 1. Top 1, 2 y 3 por máxima cantidad de daño (attack en CombatCaps).
     * 2. Mínimo ataque tipo plasma o graviton/gravity en type_gun.
     * 3. Fuego rápido contra Acorazados (215), Destructores (213), Naves de Batalla (207), Cruceros (206), Cazadores Ligeros (204) o Galeones (225).
     * 4. Deben ser unidades investigadas y accesibles en el planeta.
     * Fallback: si no se cumplen los criterios para llenar los 3 tipos, se rellenan los slots restantes
     * con las mejores armas defensivas investigadas ordenadas por mayor puntos de daño.
     *
     * @param array $pData
     * @return array Lista de IDs de defensas (hasta 3) ordenadas por daño
     */
    public function getEliteDefenseTriad(array $pData)
    {
        global $reslist, $CombatCaps;

        $targetShips = array(215, 213, 207, 206, 204, 225);
        $defList = (isset($reslist['defense']) && is_array($reslist['defense'])) ? $reslist['defense'] : array();

        $strictCandidates = array();
        $fallbackCandidates = array();

        foreach ($defList as $defId) {
            $defId = (int)$defId;
            if (in_array($defId, $reslist['domes']) || in_array($defId, $reslist['missile'])) {
                continue;
            }

            if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $defId, array())) {
                continue;
            }

            $caps = isset($CombatCaps[$defId]) ? $CombatCaps[$defId] : array();
            $att  = isset($caps['attack']) ? (float)$caps['attack'] : 10.0;
            $gun  = strtolower((string)($caps['type_gun'] ?? ''));

            $hasPlasmaOrGrav = (strpos($gun, 'plasma') !== false || strpos($gun, 'gravity') !== false || strpos($gun, 'graviton') !== false);

            $sd = isset($caps['sd']) && is_array($caps['sd']) ? $caps['sd'] : array();
            $hasRapidfire = false;
            foreach ($targetShips as $tShip) {
                if (isset($sd[$tShip]) && (float)$sd[$tShip] > 1) {
                    $hasRapidfire = true;
                    break;
                }
            }

            if ($hasPlasmaOrGrav && $hasRapidfire) {
                $strictCandidates[] = array(
                    'unit_id' => $defId,
                    'attack'  => $att,
                );
            } else {
                $fallbackCandidates[] = array(
                    'unit_id' => $defId,
                    'attack'  => $att,
                );
            }
        }

        // Ordenar candidatos estrictos por daño descendente
        usort($strictCandidates, function($a, $b) {
            return $b['attack'] <=> $a['attack'];
        });

        // Ordenar candidatos fallback por daño descendente
        usort($fallbackCandidates, function($a, $b) {
            return $b['attack'] <=> $a['attack'];
        });

        $triad = array();
        foreach ($strictCandidates as $cand) {
            $triad[] = $cand['unit_id'];
            if (count($triad) >= 3) break;
        }

        // Aplicar fallback si no se alcanzaron 3 tipos que cumplan todos los criterios
        if (count($triad) < 3) {
            foreach ($fallbackCandidates as $fCand) {
                if (!in_array($fCand['unit_id'], $triad)) {
                    $triad[] = $fCand['unit_id'];
                    if (count($triad) >= 3) break;
                }
            }
        }

        return $triad;
    }
}
