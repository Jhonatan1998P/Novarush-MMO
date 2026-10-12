<?php

require_once ROOT_PATH . 'includes/classes/class.BuildFunctions.php';

/**
 * NovaRush Bot AI — Bot Achievement Manager
 *
 * Sincroniza y calcula de forma legítima, orgánica y de alto rendimiento
 * los 23 logros canónicos (5001..5023) para los bots de NovaRush.
 *
 * Características de rendimiento:
 * - Dirty checking: 0 consultas SQL si ningún logro ha cambiado.
 * - Single-query update: Si hay cambios, se actualizan todos juntos en un solo UPDATE.
 * - Sin spam a la tabla uni1_messages.
 * - Evaluación en memoria contra el imperio completo del bot (planetas y lunas).
 */
class BotAchievementManager
{
    private $ctx;

    public function __construct(BotContext $ctx = null)
    {
        $this->ctx = $ctx;
    }

    /**
     * Sincroniza los logros del bot en base a su estado imperial actual.
     *
     * @param BotEmpireState $empire
     * @return array Mapa de logros actualizados ['achievements_X' => nuevoNivel]
     */
    public function sync(BotEmpireState $empire)
    {
        if (empty($this->ctx)) {
            return array();
        }

        $user = $this->ctx->user;
        $planets = array();
        foreach (array_merge($empire->getPlanets(), $empire->getMoons()) as $p) {
            $planets[] = isset($p['data']) ? $p['data'] : $p;
        }

        $updates = self::calculateDifferences($user, $planets);

        if (empty($updates)) {
            return array();
        }

        // Aplicar cambios en memoria al contexto
        foreach ($updates as $col => $newLvl) {
            $this->ctx->user[$col] = $newLvl;
        }

        // Ejecutar un único UPDATE en la base de datos
        self::applyDatabaseUpdate((int)$user['id'], $updates);

        if (isset($this->ctx->decisionLog)) {
            $details = array();
            foreach ($updates as $col => $lvl) {
                $details[] = "{$col} -> Niv. {$lvl}";
            }
            $this->ctx->decisionLog->record(
                'achievements_sync',
                'Logros sincronizados a niveles legítimos: ' . implode(', ', $details),
                $updates,
                'achieved',
                1.0
            );
        }

        return $updates;
    }

    /**
     * Calcula los logros reales y devuelve únicamente los que difieran del estado actual.
     *
     * @param array $user
     * @param array $planets
     * @return array
     */
    public static function calculateDifferences(array $user, array $planets)
    {
        global $resource, $reslist, $pricelist, $requeriments;

        if (empty($resource) || empty($reslist) || empty($reslist['achievements'])) {
            require_once ROOT_PATH . 'includes/vars/General.php';
            foreach (get_defined_vars() as $k => $v) {
                $GLOBALS[$k] = $v;
            }
        }

        $diffs = array();

        foreach ($reslist['achievements'] as $Element) {
            $col = isset($resource[$Element]) ? $resource[$Element] : "achievements_{$Element}";
            $maxLevel = isset($pricelist[$Element]['max']) ? (int)$pricelist[$Element]['max'] : 50;
            $currentDbLevel = isset($user[$col]) ? (int)$user[$col] : 0;

            $realLevel = self::calculateSingleAchievementLevel($user, $planets, $Element, $maxLevel);

            if ($realLevel !== $currentDbLevel) {
                $diffs[$col] = $realLevel;
            }
        }

        return $diffs;
    }

    /**
     * Calcula el nivel legítimo máximo alcanzado para un logro específico.
     *
     * @param array $user
     * @param array $planets
     * @param int   $elementId
     * @param int   $maxLevel
     * @return int
     */
    public static function calculateSingleAchievementLevel(array $user, array $planets, $elementId, $maxLevel)
    {
        global $resource;

        $col = isset($resource[$elementId]) ? $resource[$elementId] : "achievements_{$elementId}";
        $currentLvl = 0;

        $testUser = $user;

        while ($currentLvl < $maxLevel) {
            $testUser[$col] = $currentLvl;
            $accessible = false;

            if (empty($planets)) {
                if (BuildFunctions::isTechnologieAccessible($testUser, array(), $elementId, array())) {
                    $accessible = true;
                }
            } else {
                foreach ($planets as $p) {
                    $pData = isset($p['data']) ? $p['data'] : $p;
                    if (BuildFunctions::isTechnologieAccessible($testUser, $pData, $elementId, array())) {
                        $accessible = true;
                        break;
                    }
                }
            }

            if ($accessible) {
                $currentLvl++;
            } else {
                break;
            }
        }

        return $currentLvl;
    }

    /**
     * Aplica la actualización por lotes en una sola consulta SQL optimizada.
     *
     * @param int   $userId
     * @param array $updates
     */
    public static function applyDatabaseUpdate($userId, array $updates)
    {
        if (empty($updates)) {
            return;
        }

        $db = Database::get();
        $setClauses = array();
        $params = array(':id' => (int)$userId);

        foreach ($updates as $col => $val) {
            $setClauses[] = "`{$col}` = :{$col}";
            $params[":{$col}"] = (int)$val;
        }

        $sql = "UPDATE %%USERS%% SET " . implode(', ', $setClauses) . " WHERE id = :id;";
        $db->update($sql, $params);
    }

    /**
     * Recalcula y persiste los logros legítimos para un bot específico.
     *
     * @param int $botId
     * @return array
     */
    public static function recalculateBot($botId)
    {
        $db = Database::get();
        $user = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id;", array(':id' => (int)$botId));
        if (empty($user)) {
            return array('error' => "Bot ID {$botId} not found");
        }

        $planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :id AND destruyed = 0 ORDER BY id ASC;", array(':id' => (int)$botId));

        $diffs = self::calculateDifferences($user, $planets);
        if (!empty($diffs)) {
            self::applyDatabaseUpdate((int)$botId, $diffs);
        }

        return array(
            'bot_id'   => $botId,
            'username' => $user['username'],
            'changes'  => $diffs
        );
    }

    /**
     * Recalcula y persiste los logros para todos los bots registrados.
     *
     * @return array
     */
    public static function recalculateAllBots()
    {
        $db = Database::get();
        $bots = $db->select("SELECT bot_id FROM %%BOTS%% ORDER BY bot_id ASC;");

        $results = array();
        foreach ($bots as $b) {
            $results[] = self::recalculateBot((int)$b['bot_id']);
        }

        return $results;
    }
}
