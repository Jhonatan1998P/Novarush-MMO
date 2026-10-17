<?php

/**
 * NovaRush Bot AI - Real-time Action & Economy Telemetry Logger
 *
 * Provides dedicated streaming log files for each bot: tele_{botname}.log
 * Captures all actions per planet:
 * 1. Constructions (Buildings, levels, costs, reasons).
 * 2. Technology Research (Tech, levels, costs, origins, reasons).
 * 3. Ship Recruitment (Types, amounts, costs, colonies, reasons).
 * 4. Defense Recruitment (Types, amounts, costs, colonies, reasons).
 * 5. On-Demand Merchant (Category, reason, MSE before/after 50/35/15, bilateral trades, 50 MO fee, cooldowns).
 * 6. Premium Currency Spending (MO & AM, officers, ideologies, trader fees, fast actions).
 * 7. Premium Resource Exchanges & Arbitrage (Stardust -> AM, Containers -> AM, MO -> AM, AM -> Containers).
 * 8. Planetary Parties (7-day duration / 604,800s, 7,000 AM cost, tactical reasoning).
 * 9. Premium Currency Earnings (Expeditions, fair mining, trades, container drops).
 * 10. Container Openings (PIB resources, screening ships, FOB/home recipient).
 */
class BotTelemetryLogger
{
    public static $disabled = false;
    private static $instances = array();
    private static $botIdToUsername = null;

    private $botName;
    private $botId;
    private $logFile;
    private $writeCount = 0;

    const MAX_LOG_SIZE = 10485760; // 10 MB auto-rotation limit

    public function __construct($botName, $botId = 0)
    {
        $this->botName = (string)$botName;
        $this->botId   = (int)$botId;

        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $this->botName));
        $root = defined('ROOT_PATH') ? ROOT_PATH : (dirname(__DIR__, 4) . DIRECTORY_SEPARATOR);
        $this->logFile = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . "tele_{$cleanName}.log";
    }

    /**
     * Get or create logger instance for a given bot name or bot user ID
     *
     * @param string|int $botNameOrId
     * @return BotTelemetryLogger
     */
    public static function getForBot($botNameOrId)
    {
        if (is_numeric($botNameOrId)) {
            $botId = (int)$botNameOrId;
            $botName = self::getBotUsername($botId);
            if (empty($botName)) {
                $botName = "bot_{$botId}";
            }
        } else {
            $botName = (string)$botNameOrId;
            $botId = 0;
            $map = self::getBotIdMap();
            foreach ($map as $id => $uname) {
                if (strcasecmp($uname, $botName) === 0) {
                    $botId = $id;
                    break;
                }
            }
        }

        $key = strtolower($botName);
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($botName, $botId);
        }
        return self::$instances[$key];
    }

    /**
     * Check if a user ID belongs to an AI bot
     *
     * @param int $userId
     * @return bool
     */
    public static function isBotUser($userId)
    {
        $map = self::getBotIdMap();
        return isset($map[(int)$userId]);
    }

    /**
     * Get bot username from user ID
     *
     * @param int $userId
     * @return string|null
     */
    public static function getBotUsername($userId)
    {
        $map = self::getBotIdMap();
        return $map[(int)$userId] ?? null;
    }

    /**
     * Internal mapping of bot ID -> bot username
     */
    private static function getBotIdMap()
    {
        if (self::$botIdToUsername === null) {
            self::$botIdToUsername = array();
            try {
                $db = Database::get();
                $rows = $db->select("SELECT b.bot_id, u.username FROM %%BOTS%% b JOIN %%USERS%% u ON u.id = b.bot_id;");
                if (!empty($rows)) {
                    foreach ($rows as $r) {
                        self::$botIdToUsername[(int)$r['bot_id']] = $r['username'];
                    }
                }
            } catch (Throwable $e) {
                // Fallback mapping for standard bots
                self::$botIdToUsername = array(
                    1011 => 'PROMETHEUS',
                    1010 => 'Kimimaru',
                    1007 => 'LaParca',
                );
            }

            // Always ensure known bots are mapped
            if (!isset(self::$botIdToUsername[1011])) self::$botIdToUsername[1011] = 'PROMETHEUS';
            if (!isset(self::$botIdToUsername[1010])) self::$botIdToUsername[1010] = 'Kimimaru';
            if (!isset(self::$botIdToUsername[1007])) self::$botIdToUsername[1007] = 'LaParca';
        }
        return self::$botIdToUsername;
    }

    /**
     * Write a structured telemetry line to the bot's log
     * Format: [YYYY-MM-DD HH:MM:SS] [TAG] [Location] ACTION | DETAIL | COST/GAIN | REASON
     */
    public function writeLog($tag, $location, $action, $detail = '', $costOrGain = '', $reason = '')
    {
        if (self::$disabled) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $locStr = !empty($location) ? "[{$location}] " : "";
        $tagStr = "[{$tag}]";

        $lineParts = array("{$tagStr} {$locStr}{$action}");
        if (!empty($detail)) {
            $lineParts[] = $detail;
        }
        if (!empty($costOrGain)) {
            $lineParts[] = $costOrGain;
        }
        if (!empty($reason)) {
            $lineParts[] = "Motivo: {$reason}";
        }

        $formattedLine = "[{$now}] " . implode(' | ', $lineParts) . PHP_EOL;

        // Auto-rotation check every 50 writes
        $this->writeCount++;
        if ($this->writeCount % 50 === 0) {
            $this->checkRotation();
        }

        @file_put_contents($this->logFile, $formattedLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Check and rotate log if > 10 MB
     */
    private function checkRotation()
    {
        if (file_exists($this->logFile) && filesize($this->logFile) > self::MAX_LOG_SIZE) {
            $oldFile = $this->logFile . '.old';
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
            @rename($this->logFile, $oldFile);
        }
    }

    /**
     * 1. Log building construction
     */
    public function logBuilding($planetName, $coords, $elementId, $level, array $cost, $reason = '')
    {
        $elName = self::getElementName($elementId);
        $action = "Ampliación de Estructura: {$elName} a Nivel {$level}";
        $detail = "ID: #{$elementId}";
        $costStr = "Coste: " . self::formatResources($cost);
        $loc = trim("{$planetName} {$coords}");
        if (empty($reason)) {
            $reason = "Desarrollo de infraestructura y capacidad industrial planetaria";
        }
        $this->writeLog('CONSTRUCCION', $loc, $action, $detail, $costStr, $reason);
    }

    /**
     * 2. Log technology research
     */
    public function logResearch($techId, $level, $planetName, $coords, array $cost, $reason = '')
    {
        $techName = self::getElementName($techId);
        $action = "Investigación Científica: {$techName} a Nivel {$level}";
        $detail = "ID: #{$techId}";
        $costStr = "Coste: " . self::formatResources($cost);
        $loc = trim("{$planetName} {$coords}");
        if (empty($reason)) {
            $reason = "Avance tecnológico imperial y desbloqueo de capacidades avanzadas";
        }
        $this->writeLog('INVESTIGACION', $loc, $action, $detail, $costStr, $reason);
    }

    /**
     * 3. Log ship recruitment
     */
    public function logFleet($planetName, $coords, $shipId, $count, array $cost, $reason = '')
    {
        $shipName = self::getElementName($shipId);
        $countFormatted = self::formatNumber($count);
        $action = "Reclutamiento de Flota: {$countFormatted} {$shipName}";
        $detail = "ID: #{$shipId} | Cantidad: " . number_format($count);
        $costStr = "Coste: " . self::formatResources($cost);
        $loc = trim("{$planetName} {$coords}");
        if (empty($reason)) {
            $reason = "Expansión militar de armada y cumplimiento de ratios doctrinales";
        }
        $this->writeLog('FLOTA', $loc, $action, $detail, $costStr, $reason);
    }

    /**
     * 4. Log defense recruitment
     */
    public function logDefense($planetName, $coords, $defenseId, $count, array $cost, $reason = '')
    {
        $defName = self::getElementName($defenseId);
        $countFormatted = self::formatNumber($count);
        $action = "Construcción Defensiva: {$countFormatted} {$defName}";
        $detail = "ID: #{$defenseId} | Cantidad: " . number_format($count);
        $costStr = "Coste: " . self::formatResources($cost);
        $loc = trim("{$planetName} {$coords}");
        if (empty($reason)) {
            $reason = "Fortificación perimetral de bastión para disuasión de asedios";
        }
        $this->writeLog('DEFENSA', $loc, $action, $detail, $costStr, $reason);
    }

    /**
     * 5. Log on-demand merchant usage
     */
    public function logTrader($planetName, $coords, $category, $reason, array $beforeRes, array $afterRes, array $exchanges, $feeMO = 50)
    {
        $loc = trim("{$planetName} {$coords}");
        $action = "Mercader Bajo Demanda (" . ucfirst($category) . "): Rebalanceo Canónico 50/35/15 MSE";
        
        $bMse = isset($beforeRes['mse']) ? $beforeRes['mse'] : ($beforeRes['metal'] + (2.0 * $beforeRes['crystal']) + (4.0 * $beforeRes['deuterium']));
        $aMse = isset($afterRes['mse']) ? $afterRes['mse'] : ($afterRes['metal'] + (2.0 * $afterRes['crystal']) + (4.0 * $afterRes['deuterium']));

        $detail = "Antes: " . self::formatNumber($beforeRes['metal']) . " M, " . self::formatNumber($beforeRes['crystal']) . " C, " . self::formatNumber($beforeRes['deuterium']) . " D (" . self::formatNumber($bMse) . " MSE)"
                . " -> Después: " . self::formatNumber($afterRes['metal']) . " M, " . self::formatNumber($afterRes['crystal']) . " C, " . self::formatNumber($afterRes['deuterium']) . " D (" . self::formatNumber($aMse) . " MSE)"
                . " | Intercambios: " . count($exchanges);

        $tradeDescList = array();
        foreach ($exchanges as $ex) {
            $fromName = self::getShortResName($ex['from_res']);
            $toName   = self::getShortResName($ex['to_res']);
            $tradeDescList[] = "{$fromName} -> {$toName} (" . self::formatNumber($ex['mse']) . " MSE)";
        }
        if (!empty($tradeDescList)) {
            $detail .= " [" . implode(', ', $tradeDescList) . "]";
        }

        $costStr = "Tasa: " . self::formatNumber($feeMO) . " MO | Cooldowns: Imperio 1h (3600s), Planeta 2h (7200s)";
        $this->writeLog('MERCADER', $loc, $action, $detail, $costStr, $reason);
    }

    /**
     * 6. Log premium currency spending
     */
    public function logPremiumSpend($location, $actionName, $currencyId, $amount, $balanceAfter, $reason = '')
    {
        $curShort = self::getCurrencyShort($currencyId);
        $action = "Gasto Premium: {$actionName}";
        $detail = "Saldo restante: " . self::formatNumber($balanceAfter) . " {$curShort}";
        $costStr = "Coste: -" . self::formatNumber($amount) . " {$curShort}";
        $this->writeLog('PREMIUM_GASTO', $location, $action, $detail, $costStr, $reason);
    }

    /**
     * 7. Log planetary party activation
     */
    public function logParty($partyId, $duration, $costAM, $balanceAfter, $reason = '')
    {
        $pName = self::getElementName($partyId);
        $durationDays = round($duration / 86400, 1);
        $action = "Activada {$pName} (#{$partyId}) por {$durationDays} Días (" . number_format($duration) . "s)";
        $detail = "Saldo restante: " . self::formatNumber($balanceAfter) . " AM";
        $costStr = "Coste: -" . self::formatNumber($costAM) . " AM";
        $this->writeLog('FIESTA', 'Imperio', $action, $detail, $costStr, $reason);
    }

    /**
     * 8. Log premium exchanges and arbitrage
     */
    public function logPremiumExchange($exchangeType, $costDesc, $gainDesc, $reason = '')
    {
        $action = "Canje / Arbitraje Premium: {$exchangeType}";
        $detail = "Coste: {$costDesc} -> Ganancia: {$gainDesc}";
        $this->writeLog('PREMIUM_CANJE', 'Imperio', $action, $detail, '', $reason);
    }

    /**
     * 9. Log premium currency gains
     */
    public function logPremiumGain($location, $source, $currencyId, $amount, $balanceAfter, $details = '')
    {
        $curShort = self::getCurrencyShort($currencyId);
        $action = "Ganancia Premium ({$source}): +" . self::formatNumber($amount) . " {$curShort}";
        $detail = "Nuevo Saldo: " . self::formatNumber($balanceAfter) . " {$curShort}";
        $this->writeLog('PREMIUM_GANANCIA', $location, $action, $detail, '', $details);
    }

    /**
     * 10. Log container opening
     */
    public function logContainerOpen($planetName, $coords, $count, array $resGained, array $shipsGained, $remainingContainers, $reason = '')
    {
        $loc = trim("{$planetName} {$coords}");
        $action = "Apertura Táctica de {$count} Contenedores Precursores (#924)";
        $detail = "Recompensas PIB: " . self::formatNumber($resGained['metal']) . " M, " . self::formatNumber($resGained['crystal']) . " C, " . self::formatNumber($resGained['deuterium']) . " D"
                . " | Screening: " . self::formatNumber($shipsGained[204] ?? 0) . " C.Ligeros, " . self::formatNumber($shipsGained[205] ?? 0) . " C.Pesados, " . self::formatNumber($shipsGained[206] ?? 0) . " Cruceros"
                . " | Stock restante: {$remainingContainers} contenedores";
        $this->writeLog('CONTENEDORES', $loc, $action, $detail, '', $reason);
    }

    /**
     * 11. Log fleet mission launch
     */
    public function logFleetMission($startPlanet, $startCoords, $targetPlanet, $targetCoords, $missionType, array $fleetArray, array $cargo = array(), $fuel = 0.0, $reason = '')
    {
        $missionNames = array(
            1  => 'Ataque Militar',
            2  => 'Ataque Confederado (SAC)',
            3  => 'Transporte Logístico',
            4  => 'Despliegue / Reubicación',
            5  => 'Mantener Posición',
            6  => 'Misión de Espionaje',
            7  => 'Misión Colonizadora',
            8  => 'Recolección de Escombros',
            9  => 'Destruir Luna',
            10 => 'Ataque con Misiles',
            15 => 'Expedición de Exploración',
            18 => 'Expedición Militar (Sector 16)',
        );
        $mTitle = $missionNames[$missionType] ?? "Misión #{$missionType}";
        $loc = trim("{$startPlanet} {$startCoords}");

        $shipParts = array();
        $totalShips = 0;
        foreach ($fleetArray as $sId => $qty) {
            $totalShips += $qty;
            $sName = self::getElementName($sId);
            $shipParts[] = self::formatNumber($qty) . " {$sName}";
        }
        $shipsSummary = implode(', ', array_slice($shipParts, 0, 4));
        if (count($shipParts) > 4) {
            $shipsSummary .= " (+otros " . (count($shipParts) - 4) . " tipos)";
        }

        $action = "Despacho de Flota: {$mTitle} hacia {$targetPlanet} {$targetCoords}";
        $detail = "Flota (" . self::formatNumber($totalShips) . " naves): {$shipsSummary}";

        $cargoParts = array();
        if (!empty($cargo[901]) && $cargo[901] > 0) $cargoParts[] = self::formatNumber($cargo[901]) . " M";
        if (!empty($cargo[902]) && $cargo[902] > 0) $cargoParts[] = self::formatNumber($cargo[902]) . " C";
        if (!empty($cargo[903]) && $cargo[903] > 0) $cargoParts[] = self::formatNumber($cargo[903]) . " D";
        if (!empty($cargoParts)) {
            $detail .= " | Carga: " . implode(', ', $cargoParts);
        }

        $costStr = ($fuel > 0) ? "Combustible: -" . self::formatNumber($fuel) . " Deuterio" : "";
        if (empty($reason)) {
            $reason = "Operación estratégica de la doctrina naval del bot";
        }

        $this->writeLog('MISION_FLOTA', $loc, $action, $detail, $costStr, $reason);
    }

    /**
     * 12. Log fleet scrapping
     */
    public function logScrap($planetName, $coords, $shipId, $count, array $refund, $reason = '')
    {
        $loc = trim("{$planetName} {$coords}");
        $shipName = self::getElementName($shipId);
        $action = "Desguace de Naves: " . self::formatNumber($count) . " {$shipName} (#{$shipId})";
        $detail = "Reembolso acreditado: " . self::formatResources($refund);
        if (empty($reason)) {
            $reason = "Reciclaje de excedentes para reconversión en naves pesadas";
        }
        $this->writeLog('DESGUACE', $loc, $action, $detail, '', $reason);
    }

    /**
     * Global Hook called by PremiumEconomy::ledger
     * Captures ALL premium gain/spend events from any origin (expeditions, fair mining, events, etc.)
     */
    public static function onLedgerEntry($uid, $cur, $delta, $after, $source, $el, $meta)
    {
        if (!self::isBotUser($uid)) {
            return;
        }

        $logger = self::getForBot($uid);

        // Sources already covered by rich high-level logs to avoid duplicates:
        $richHandled = array(
            'trader_call',
            'party_buy',
            'container_open',
            'officier_buy',
            'ideology_buy',
            'race_buy',
            'fair_trade',
        );

        if (in_array($source, $richHandled)) {
            return; // Handled with rich tactical rationale by specific modules
        }

        // 1. Expedition rewards (Sector 16)
        if ($source === 'expedition') {
            $curName = self::getCurrencyName($cur);
            $curShort = self::getCurrencyShort($cur);
            $action = "Cosecha de {$curName} en Misión de Exploración";
            $detail = "Nuevo Saldo: " . self::formatNumber($after) . " {$curShort}";
            $costGain = "Ganancia: +" . self::formatNumber($delta) . " {$curShort}";
            $reason = "Misión de Expedición al Sector 16 con armada táctica";
            $logger->writeLog('PREMIUM_GANANCIA', 'Expedición Sector 16', $action, $detail, $costGain, $reason);
            return;
        }

        // 2. Resource Fair Daily Mining (2301, 2302, 2303)
        if ($source === 'fair_res') {
            $elName = self::getElementName($el);
            $action = "Minado Diario de MO en Feria ({$elName})";
            $detail = "Nuevo Saldo: " . self::formatNumber($after) . " MO";
            $costGain = "Ganancia: +" . self::formatNumber($delta) . " MO";
            $reason = "Extracción diaria de yacimiento en Feria de Recursos ({$meta})";
            $logger->writeLog('PREMIUM_GANANCIA', 'Feria de Recursos', $action, $detail, $costGain, $reason);
            return;
        }

        // 3. Generic fallback for any other source
        $curShort = self::getCurrencyShort($cur);
        if ($delta < 0) {
            $action = "Gasto Premium ({$source})";
            $detail = "Elemento: #" . (int)$el . " | Saldo restante: " . self::formatNumber($after) . " {$curShort}";
            $costGain = "Coste: -" . self::formatNumber(abs($delta)) . " {$curShort}";
            $reason = !empty($meta) ? $meta : "Operación económica del sistema";
            $logger->writeLog('PREMIUM_GASTO', 'Imperio', $action, $detail, $costGain, $reason);
        } else {
            $action = "Ingreso Premium ({$source})";
            $detail = "Elemento: #" . (int)$el . " | Saldo: " . self::formatNumber($after) . " {$curShort}";
            $costGain = "Ganancia: +" . self::formatNumber($delta) . " {$curShort}";
            $reason = !empty($meta) ? $meta : "Recompensa o abono del sistema";
            $logger->writeLog('PREMIUM_GANANCIA', 'Imperio', $action, $detail, $costGain, $reason);
        }
    }

    /**
     * Helpers for names and formatting
     */
    public static function getCurrencyName($currencyId)
    {
        switch ((int)$currencyId) {
            case 921: return 'Materia Oscura (MO)';
            case 922: return 'Antimateria (AM)';
            case 923: return 'Polvo Estelar (Stardust)';
            case 924: return 'Contenedores Precursores';
            default:  return "Divisa #{$currencyId}";
        }
    }

    public static function getCurrencyShort($currencyId)
    {
        switch ((int)$currencyId) {
            case 921: return 'MO';
            case 922: return 'AM';
            case 923: return 'Stardust';
            case 924: return 'Contenedor';
            default:  return "#{$currencyId}";
        }
    }

    public static function getShortResName($resId)
    {
        switch ((int)$resId) {
            case 901: return 'Metal';
            case 902: return 'Cristal';
            case 903: return 'Deuterio';
            case 921: return 'MO';
            case 922: return 'AM';
            case 923: return 'Stardust';
            case 924: return 'Contenedor';
            default:  return "Res #{$resId}";
        }
    }

    public static function getElementName($elementId)
    {
        global $LNG;
        if (!empty($LNG['tech'][$elementId])) {
            return $LNG['tech'][$elementId];
        }

        static $names = array(
            1 => 'Mina de Metal',
            2 => 'Mina de Cristal',
            3 => 'Sintetizador de Deuterio',
            4 => 'Planta de Energía Solar',
            12 => 'Planta de Fusión',
            14 => 'Fábrica de Robots',
            15 => 'Fábrica de Nanobots',
            21 => 'Hangar / Astillero',
            22 => 'Almacén de Metal',
            23 => 'Almacén de Cristal',
            24 => 'Tanque de Deuterio',
            31 => 'Laboratorio de Investigación',
            33 => 'Terraformer',
            34 => 'Depósito de la Alianza',
            44 => 'Silo de Misiles',
            // Techs
            106 => 'Tecnología de Espionaje',
            108 => 'Tecnología de Computación',
            109 => 'Tecnología Militar (Ataque)',
            110 => 'Tecnología de Defensa (Escudos)',
            111 => 'Tecnología de Blindaje',
            113 => 'Tecnología de Energía',
            114 => 'Tecnología de Hiperespacio',
            115 => 'Motor de Combustión',
            117 => 'Motor de Impulso',
            118 => 'Propulsor Hiperespacial',
            120 => 'Tecnología Láser',
            121 => 'Tecnología Iónica',
            122 => 'Tecnología de Plasma',
            123 => 'Red de Investigación Intergaláctica (IRN)',
            124 => 'Astrofísica',
            131 => 'Tecnología de Producción',
            199 => 'Tecnología de Gravitón',
            // Ships
            202 => 'Nave Pequeña de Carga',
            203 => 'Nave Grande de Carga',
            204 => 'Caza Ligero',
            205 => 'Caza Pesado',
            206 => 'Crucero',
            207 => 'Nave de Batalla',
            208 => 'Colonizador',
            209 => 'Reciclador',
            210 => 'Sonda de Espionaje',
            211 => 'Bombardero',
            212 => 'Satélite Solar',
            213 => 'Destructor',
            214 => 'Estrella de la Muerte',
            215 => 'Acorazado',
            216 => 'Luna Negra',
            217 => 'Megatransportador',
            218 => 'Fragata',
            219 => 'Mega Reciclador',
            225 => 'Galeón',
            // Defenses
            401 => 'Lanzamisiles',
            402 => 'Láser Pequeño',
            403 => 'Láser Grande',
            404 => 'Cañón Gauss',
            405 => 'Cañón Iónico',
            406 => 'Cañón de Plasma',
            407 => 'Cúpula Pequeña',
            408 => 'Cúpula Grande',
            409 => 'Cúpula Gravitacional',
            410 => 'Dora',
            411 => 'Cañón de Fotones',
            502 => 'Misil de Intercepción',
            503 => 'Misil Interplanetario',
            // Officers
            601 => 'Oficial Geólogo',
            602 => 'Oficial Almirante',
            603 => 'Oficial Ingeniero',
            604 => 'Oficial Tecnócrata',
            // Parties
            1201 => 'Fiesta de los Industriales',
            1202 => 'Fiesta Científica',
            1203 => 'Fiesta de los Federalistas',
            1204 => 'Fiesta de los Pacifistas',
            1205 => 'Fiesta de los Militaristas',
            1206 => 'Fiesta de los Expansionistas',
            1207 => 'Fiesta de los Académicos',
            1208 => 'Fiesta de los Ecologistas',
            // Ideologies
            1301 => 'Ideología Liberalismo',
            1302 => 'Ideología Anarquismo',
            1303 => 'Ideología Comunismo',
            1304 => 'Ideología Socialdemocracia',
            1305 => 'Ideología Fascismo',
            1306 => 'Ideología Conservadurismo',
            // Currencies & Specials
            921 => 'Materia Oscura',
            922 => 'Antimateria',
            923 => 'Polvo Estelar',
            924 => 'Contenedor Precursor',
            2301 => 'Feria: Extracción de Metal',
            2302 => 'Feria: Extracción de Cristal',
            2303 => 'Feria: Extracción de Deuterio',
            2305 => 'Feria: Transmutación Stardust (2305)',
            2306 => 'Feria: Transmutación MO a AM (2306)',
            2307 => 'Feria: Venta Contenedores a AM (2307)',
            2308 => 'Feria: Compra AM a Contenedores (2308)',
        );

        return $names[$elementId] ?? "Elemento #{$elementId}";
    }

    public static function formatResources(array $cost)
    {
        $parts = array();
        if (!empty($cost[901]) || !empty($cost['metal'])) {
            $amt = !empty($cost[901]) ? $cost[901] : $cost['metal'];
            $parts[] = self::formatNumber($amt) . " Metal";
        }
        if (!empty($cost[902]) || !empty($cost['crystal'])) {
            $amt = !empty($cost[902]) ? $cost[902] : $cost['crystal'];
            $parts[] = self::formatNumber($amt) . " Cristal";
        }
        if (!empty($cost[903]) || !empty($cost['deuterium'])) {
            $amt = !empty($cost[903]) ? $cost[903] : $cost['deuterium'];
            $parts[] = self::formatNumber($amt) . " Deuterio";
        }
        if (!empty($cost[921]) || !empty($cost['darkmatter'])) {
            $amt = !empty($cost[921]) ? $cost[921] : $cost['darkmatter'];
            $parts[] = self::formatNumber($amt) . " MO";
        }
        if (!empty($cost[922]) || !empty($cost['antimatter'])) {
            $amt = !empty($cost[922]) ? $cost[922] : $cost['antimatter'];
            $parts[] = self::formatNumber($amt) . " AM";
        }
        return empty($parts) ? "Sin coste" : implode(', ', $parts);
    }

    public static function formatNumber($n)
    {
        $n = (float)$n;
        if (abs($n) >= 1000000000) {
            return round($n / 1000000000.0, 2) . 'B';
        }
        if (abs($n) >= 1000000) {
            return round($n / 1000000.0, 2) . 'M';
        }
        if (abs($n) >= 1000) {
            return round($n / 1000.0, 1) . 'k';
        }
        return number_format($n, 0);
    }
}
