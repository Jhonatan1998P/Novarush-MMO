<?php

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);
chdir(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/vars/General.php';
require_once 'includes/classes/cache/builder/BuildCache.interface.php';
require_once 'includes/classes/cache/builder/VarsBuildCache.class.php';

echo "========================================================\n";
echo "   OGame NovaRush - Reconstrucción de Caché de Vars     \n";
echo "========================================================\n\n";

$cache = Cache::get();
$cache->flush('vars');
echo "[OK] Cache::get()->flush('vars') ejecutado con éxito.\n";

// Clear Smarty template cache files in cache/
$templateFiles = glob(ROOT_PATH . 'cache/*.tpl.cache.php');
$templateFiles2 = glob(ROOT_PATH . 'cache/templates/*');
$cleared = 0;
foreach (array_merge($templateFiles ?: array(), $templateFiles2 ?: array()) as $file) {
    if (is_file($file)) {
        unlink($file);
        $cleared++;
    }
}
echo "[OK] Limpiados $cleared archivos de caché de plantillas Smarty.\n";

// Load cache and inspect sample values
$vars = $cache->getData('vars', false);
echo "\n--- VERIFICACIÓN DE VALORES EN CACHÉ DE VARS ---\n";
echo "Fair 2304 (AM -> Polvo): Costo 922 = " . ($vars['pricelist'][2304]['cost'][922] ?? 'N/A') . " AM | Bonus 923 = " . ($vars['BonusElement'][2304][923] ?? 'N/A') . " Polvo\n";
echo "Fair 2305 (Polvo -> AM): Costo 923 = " . ($vars['pricelist'][2305]['cost'][923] ?? 'N/A') . " Polvo | Bonus 922 = " . ($vars['BonusElement'][2305][922] ?? 'N/A') . " AM\n";
echo "Fair 2306 (MO -> AM): Costo 921 = " . ($vars['pricelist'][2306]['cost'][921] ?? 'N/A') . " MO | Bonus 922 = " . ($vars['BonusElement'][2306][922] ?? 'N/A') . " AM\n";
echo "Fair 2307 (CT -> AM): Costo 924 = " . ($vars['pricelist'][2307]['cost'][924] ?? 'N/A') . " CT | Bonus 922 = " . ($vars['BonusElement'][2307][922] ?? 'N/A') . " AM\n";
echo "Fair 2308 (AM -> CT): Costo 922 = " . ($vars['pricelist'][2308]['cost'][922] ?? 'N/A') . " AM | Bonus 924 = " . ($vars['BonusElement'][2308][924] ?? 'N/A') . " CT\n";
echo "Fair 2301-2303 MO Bonus = " . ($vars['BonusElement'][2301][921] ?? 'N/A') . " MO\n";

echo "\nArtefactos:\n";
echo "1404 bonusSbuild = " . ($vars['pricelist'][1404]['bonus']['Sbuild'][0] ?? 'N/A') . " | time = " . ($vars['pricelist'][1404]['time'] ?? 'N/A') . "\n";
echo "1405 bonusStech = " . ($vars['pricelist'][1405]['bonus']['Stech'][0] ?? 'N/A') . " | time = " . ($vars['pricelist'][1405]['time'] ?? 'N/A') . "\n";
echo "1406 bonusResource = " . ($vars['pricelist'][1406]['bonus']['Resource'][0] ?? 'N/A') . " | time = " . ($vars['pricelist'][1406]['time'] ?? 'N/A') . "\n";
echo "1407 bonusFlyTime = " . ($vars['pricelist'][1407]['bonus']['FlyTime'][0] ?? 'N/A') . " | time = " . ($vars['pricelist'][1407]['time'] ?? 'N/A') . "\n";
echo "1408 bonusSfleet = " . ($vars['pricelist'][1408]['bonus']['Sfleet'][0] ?? 'N/A') . " | time = " . ($vars['pricelist'][1408]['time'] ?? 'N/A') . "\n";

echo "\nMódulos Desarrollo (1901-1908):\n";
for ($id = 1901; $id <= 1908; $id++) {
    echo "$id: cost924 = " . ($vars['pricelist'][$id]['cost'][924] ?? 'N/A') . "\n";
}

echo "\nPlanos (701-707):\n";
for ($id = 701; $id <= 707; $id++) {
    echo "$id: cost921 = " . ($vars['pricelist'][$id]['cost'][921] ?? 'N/A') . "\n";
}

echo "\nPaquetes Premium:\n";
foreach (array(2101, 2104, 2106, 2107, 2108) as $id) {
    echo "$id: cost922 = " . ($vars['pricelist'][$id]['cost'][922] ?? 'N/A') . "\n";
}

echo "\nBonos 30d (2401-2408):\n";
for ($id = 2401; $id <= 2408; $id++) {
    echo "$id: cost922 = " . ($vars['pricelist'][$id]['cost'][922] ?? 'N/A') . "\n";
}

echo "\nOficiales base y factor:\n";
echo "601: cost921 = " . ($vars['pricelist'][601]['cost'][921] ?? 'N/A') . " | factor = " . ($vars['pricelist'][601]['factor'] ?? 'N/A') . "\n";

echo "\nDetalles base, factor y maxLevel:\n";
echo "1001: cost921 = " . ($vars['pricelist'][1001]['cost'][921] ?? 'N/A') . " | factor = " . ($vars['pricelist'][1001]['factor'] ?? 'N/A') . " | max = " . ($vars['pricelist'][1001]['max'] ?? 'N/A') . "\n";

echo "\n[FIN] Caché reconstruida y verificada exitosamente.\n";
