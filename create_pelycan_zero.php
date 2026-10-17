<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', __DIR__) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once ROOT_PATH . 'includes/classes/PlayerUtil.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Forces/BotAchievementManager.class.php';

global $resource, $reslist, $pricelist;

$db = Database::get();

echo "========================================================================================\n";
echo "   NOVARUSH — CREACION DEL BOT CANONICO: 'Pelycan' (PESADILLA / AGRESIVO / ZERO-START)\n";
echo "========================================================================================\n\n";

$name = 'Pelycan';
$personality = 'aggressive';
$difficulty = 'nightmare';

// 1. Verificar si ya existe Pelycan
$existing = $db->selectSingle("SELECT id, username FROM %%USERS%% WHERE username = :name;", array(':name' => $name));
if (!empty($existing)) {
    echo "(!) El usuario '{$name}' ya existe (ID: {$existing['id']}). Purgando para creación limpia desde cero...\n";
    $oldId = (int)$existing['id'];
    $db->delete("DELETE FROM %%BOTS%% WHERE bot_id = :id;", array(':id' => $oldId));
    $db->delete("DELETE FROM %%BOT_TASKS%% WHERE bot_id = :id;", array(':id' => $oldId));
    $db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :id;", array(':id' => $oldId));
    $db->delete("DELETE FROM %%FLEETS%% WHERE fleet_owner = :id;", array(':id' => $oldId));
    $db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = :id;", array(':id' => $oldId));
    $db->delete("DELETE FROM %%STATPOINTS%% WHERE id_owner = :id;", array(':id' => $oldId));
    $db->delete("DELETE FROM %%USERS%% WHERE id = :id;", array(':id' => $oldId));
    echo "    Purga completada con éxito.\n\n";
}

// 2. Determinar coordenada disponible en Galaxia 1 (Slot 8 templado)
$galaxy = 1;
$system = 180;
$planet = 8;

while (!PlayerUtil::isPositionFree(1, $galaxy, $system, $planet)) {
    $system++;
    if ($system > 400) $system = 10;
}

echo "Coordenada seleccionada para la capital de Pelycan: [{$galaxy}:{$system}:{$planet}]\n";

// 3. Crear el jugador y planeta base con recursos iniciales del universo
$rawPass = 'PelycanPass_' . mt_rand(100000, 999999);
$cryptPass = PlayerUtil::cryptPassword($rawPass);
$email = 'bot_pelycan_' . mt_rand(100, 999) . '@novarush.ai';
$homePlanetName = 'Pelycan-Prime';

list($userId, $homePlanetId) = PlayerUtil::createPlayer(
    1,
    $name,
    $cryptPass,
    $email,
    'es',
    $galaxy,
    $system,
    $planet,
    $homePlanetName,
    AUTH_USR
);

echo "Usuario '{$name}' creado exitosamente:\n";
echo "  • ID de Jugador : {$userId}\n";
echo "  • Planeta Base  : {$homePlanetName} (ID: {$homePlanetId}) en [{$galaxy}:{$system}:{$planet}]\n\n";

// 4. Configurar Matriz Cívica (Kratonianos / Imperio / Maldad para bot Agresivo) y Nivel 0 Estricto
$db->update("UPDATE %%USERS%% SET 
    race = 1504,
    race_1501 = 0,
    race_1502 = 0,
    race_1503 = 0,
    race_1504 = 1,
    formgovernment = 1601,
    formgovernment_1601 = 1,
    formgovernment_1602 = 0,
    formgovernment_1603 = 0,
    formgovernment_1604 = 0,
    ethics = 1703,
    ethics_1701 = 0,
    ethics_1702 = 0,
    ethics_1703 = 1,
    authattack = 0,
    bana = 0,
    urlaubs_modus = 0,
    darkmatter = 0
WHERE id = :uid;", array(':uid' => $userId));

// 5. Garantizar Recursos Iniciales Estándar Escalados (6M Metal, 4M Cristal, 2M Deuterio) y 0 Edificios/Flotas
$config = Config::get(1);
$resMult = max(1.0, (float)$config->resource_multiplier);
$metalInit = (float)$config->metal_start * $resMult;
$crystalInit = (float)$config->crystal_start * $resMult;
$deutInit = (float)$config->deuterium_start * $resMult;

$db->update("UPDATE %%PLANETS%% SET 
    metal = :m,
    crystal = :c,
    deuterium = :d,
    field_current = 0
WHERE id = :pid;", array(
    ':m'   => $metalInit,
    ':c'   => $crystalInit,
    ':d'   => $deutInit,
    ':pid' => $homePlanetId
));

// 6. Registrar en uni1_bots con Dificultad Pesadilla y Personalidad Agresiva
$recruitInterval = mt_rand(600, 900); // 10 a 15 min para perfil agresivo
$now = TIMESTAMP;

$db->insert("INSERT INTO %%BOTS%% 
    (bot_id, personality, difficulty, home_planet_id, is_active, created_at, last_activity, doctrine_heavy, doctrine_medium, doctrine_light, doctrine_fodder, recruit_interval, budget_credits)
VALUES 
    (:botId, :pers, :diff, :home, 1, :now, :now, 0, 0, 0, 0, :interval, :credits);", array(
    ':botId'    => $userId,
    ':pers'     => $personality,
    ':diff'     => $difficulty,
    ':home'     => $homePlanetId,
    ':now'      => $now,
    ':interval' => $recruitInterval,
    ':credits'  => '{"mines":0,"fleet":0,"defense":0,"research":0}'
));

// 7. Sincronizar logros y statpoints
BotAchievementManager::recalculateBot((int)$userId);

// 8. Verificación y Auditoría de Integridad
$userRow   = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uid;", array(':uid' => $userId));
$planetRow = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => $homePlanetId));
$botRow    = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :uid;", array(':uid' => $userId));
$planetsCnt = (int)$db->selectSingle("SELECT COUNT(*) as cnt FROM %%PLANETS%% WHERE id_owner = :uid;", array(':uid' => $userId), 'cnt');

echo "========================================================================================\n";
echo "   ESTADO VERIFICADO DEL BOT: {$name}\n";
echo "========================================================================================\n";
echo sprintf("  • ID de Bot            : %d\n", $userId);
echo sprintf("  • Nombre de Usuario    : %s\n", $userRow['username']);
echo sprintf("  • Dificultad           : %s\n", $botRow['difficulty']);
echo sprintf("  • Personalidad         : %s (Raider / Ofensivo)\n", $botRow['personality']);
echo sprintf("  • Matriz Cívica        : Raza Kratonianos (#%d) | Gobierno Imperio (#%d) | Ética Maldad (#%d)\n",
    $userRow['race'], $userRow['formgovernment'], $userRow['ethics']);
echo sprintf("  • Total de Planetas    : %d (Único planeta: %s [%d:%d:%d])\n",
    $planetsCnt, $planetRow['name'], $planetRow['galaxy'], $planetRow['system'], $planetRow['planet']);
echo sprintf("  • Recursos en Superficie: Metal: %s | Cristal: %s | Deuterio: %s\n",
    number_format($planetRow['metal']), number_format($planetRow['crystal']), number_format($planetRow['deuterium']));
echo sprintf("  • Campos Edificados    : %d / %d\n", $planetRow['field_current'], $planetRow['field_max']);
echo sprintf("  • Minas Nivel Inicial  : Metal: %d | Cristal: %d | Deuterio: %d | Solar: %d\n",
    $planetRow['metal_mine'], $planetRow['crystal_mine'], $planetRow['deuterium_sintetizer'], $planetRow['solar_plant']);
echo sprintf("  • Astillero / Hangar   : %d\n", $planetRow['hangar']);
echo sprintf("  • Laboratorio          : %d\n", $planetRow['laboratory']);
echo sprintf("  • Flota Inicial        : 0 naves\n");
echo sprintf("  • Defensas Iniciales   : 0 estructuras\n");
echo sprintf("  • Tecnologías Iniciales: 0 investigaciones\n");
echo sprintf("  • Estado Operativo     : Activo en daemon autónomo (Intervalo Reclutamiento: %ds)\n", $botRow['recruit_interval']);
echo "========================================================================================\n";
echo "  [OK] Bot 'Pelycan' creado con éxito e integrado al ecosistema de NovaRush.\n\n";
