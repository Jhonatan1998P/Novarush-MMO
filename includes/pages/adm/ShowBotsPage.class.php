<?php

/*
 * NovaRush - Admin Panel: Gestión de Bots de Inteligencia Artificial
 */

require_once 'includes/classes/bot/BotEngine.class.php';

class ShowBotsPage extends AbstractAdminPage
{
    public static $requireModule = 0;

    function __construct() 
    {
        if (!allowedTo(str_replace(array(dirname(__FILE__), '\\', '/', '.php'), '', __FILE__))) {
            throw new Exception("Permission error!");
        }
        parent::__construct();
    }

    function show()
    {
        global $LNG;
        $db = Database::get();
        BotEngine::ensureTables();

        $action = HTTP::_GP('action', '');

        // 1. Crear nuevo bot
        if ($action == 'create') {
            try {
                $name        = HTTP::_GP('name', '', UTF8_SUPPORT);
                $personality = HTTP::_GP('personality', 'balanced');
                $tier        = HTTP::_GP('tier', 'intermediate');
                $galaxy      = HTTP::_GP('galaxy', 1);
                $system      = HTTP::_GP('system', 0);
                $planet      = HTTP::_GP('planet', 0);

                $created = BotEngine::createBot(
                    empty($name) ? null : $name,
                    $personality,
                    $tier,
                    $galaxy,
                    $system > 0 ? $system : null,
                    $planet > 0 ? $planet : null
                );

                $this->printMessage("¡Bot '{$created['name']}' creado exitosamente en {$created['coordinates']} con nivel {$created['tier']}!", true, array('?page=bots', 3));
            } catch (Exception $e) {
                $this->printMessage("Error al crear el bot: " . $e->getMessage(), true, array('?page=bots', 4));
            }
            return;
        }

        // 2. Registrar usuario existente como bot
        if ($action == 'register_existing') {
            try {
                $userId      = HTTP::_GP('user_id', 0);
                $personality = HTTP::_GP('personality', 'balanced');
                BotEngine::registerExistingUserAsBot($userId, $personality);
                $this->printMessage("Usuario #{$userId} registrado como Bot IA exitosamente.", true, array('?page=bots', 3));
            } catch (Exception $e) {
                $this->printMessage("Error al registrar usuario: " . $e->getMessage(), true, array('?page=bots', 4));
            }
            return;
        }

        // 3. Toggle activar / pausar bot
        if ($action == 'toggle') {
            try {
                $botId = HTTP::_GP('bot_id', 0);
                $db->update("UPDATE " . DB_PREFIX . "bots SET is_active = IF(is_active = 1, 0, 1) WHERE bot_id = :id;", array(':id' => $botId));
                $this->printMessage("Estado del bot actualizado.", true, array('?page=bots', 2));
            } catch (Exception $e) {
                $this->printMessage("Error: " . $e->getMessage(), true, array('?page=bots', 3));
            }
            return;
        }

        // 4. Eliminar bot
        if ($action == 'delete') {
            try {
                $botId = HTTP::_GP('bot_id', 0);
                $db->delete("DELETE FROM " . DB_PREFIX . "bots WHERE bot_id = :id;", array(':id' => $botId));
                $this->printMessage("Bot desvinculado de la IA exitosamente.", true, array('?page=bots', 2));
            } catch (Exception $e) {
                $this->printMessage("Error: " . $e->getMessage(), true, array('?page=bots', 3));
            }
            return;
        }

        // 5. Ejecutar ciclo manual de IA
        $cycleResults = null;
        if ($action == 'run_cycle') {
            try {
                $cycleResults = BotEngine::runAllBots();
            } catch (Exception $e) {
                $this->printMessage("Error al ejecutar ciclo: " . $e->getMessage(), true, array('?page=bots', 4));
                return;
            }
        }

        // Cargar lista de bots
        $bots = BotEngine::getBots(false);
        $enhancedBots = array();
        foreach ($bots as $b) {
            $colonyCount = (int)$db->selectSingle("SELECT COUNT(*) as cnt FROM %%PLANETS%% WHERE id_owner = :id;", array(':id' => (int)$b['bot_id']), 'cnt');
            $homePlanet = $db->selectSingle("SELECT galaxy, system, planet, name FROM %%PLANETS%% WHERE id = :id;", array(':id' => $b['home_planet_id']));

            $b['colony_count'] = $colonyCount;
            $b['coords'] = !empty($homePlanet) ? "[{$homePlanet['galaxy']}:{$homePlanet['system']}:{$homePlanet['planet']}]" : "N/A";
            $b['home_name'] = !empty($homePlanet) ? $homePlanet['name'] : "Principal";
            $b['points_fmt'] = number_format((float)$b['total_points']);
            $enhancedBots[] = $b;
        }

        // Cargar usuarios no-bot para opción de convertir
        $existingUsers = $db->select("SELECT u.id, u.username, u.authlevel 
            FROM %%USERS%% as u 
            WHERE u.authlevel = 0 AND u.id NOT IN (SELECT bot_id FROM " . DB_PREFIX . "bots)
            ORDER BY u.id ASC;");

        // Cargar logs recientes
        $logs = BotEngine::getRecentLogs(null, 30);

        // Cargar decisiones estructuradas v2 (Explicabilidad)
        $decisions = $db->select("SELECT d.*, u.username 
                                  FROM " . DB_PREFIX . "bot_decisions d 
                                  LEFT JOIN %%USERS%% u ON u.id = d.bot_id 
                                  ORDER BY d.id DESC LIMIT 30;");

        $this->assign(array(
            'bots'          => $enhancedBots,
            'existingUsers' => $existingUsers,
            'logs'          => $logs,
            'decisions'     => $decisions,
            'cycleResults'  => $cycleResults
        ));

        $this->display('page.bots.default.tpl');
    }
}
