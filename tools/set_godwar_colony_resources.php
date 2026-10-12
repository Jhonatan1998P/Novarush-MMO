<?php

/**
 * NovaRush - Set -GODWAR- Colony Resources to 10,000M (50% M / 35% C / 15% D)
 * 5,000,000,000 Metal
 * 3,500,000,000 Crystal
 * 1,500,000,000 Deuterium
 * Applied to all 8 colonies (planet_type = 1).
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';

$db = Database::get();

// Verify user 4
$user = $db->selectSingle("SELECT id, username FROM %%USERS%% WHERE id = 4;");
if (empty($user)) {
    echo "ERROR: User ID 4 not found.\n";
    exit(1);
}

$planets = $db->select("SELECT id, name, galaxy, system, planet, metal, crystal, deuterium, b_hangar_id FROM %%PLANETS%% WHERE id_owner = 4 AND destruyed = 0 AND planet_type = 1 ORDER BY id ASC;");

if (empty($planets)) {
    echo "ERROR: No colonies found for user 4.\n";
    exit(1);
}

echo "========================================================================\n";
echo "       ACTUALIZACIÓN DE RECURSOS EN COLONIAS DE -GODWAR- (ID 4)         \n";
echo "========================================================================\n\n";

echo "--- ESTADO PREVIO EN LAS 8 COLONIAS ---\n";
foreach ($planets as $p) {
    echo sprintf("Colonia %-12s [ID %2d]: M=%18s | C=%18s | D=%18s\n",
        $p['name'], $p['id'],
        number_format((float)$p['metal'], 2),
        number_format((float)$p['crystal'], 2),
        number_format((float)$p['deuterium'], 2)
    );
}

$targetM = 5000000000.0; // 5,000M (50%)
$targetC = 3500000000.0; // 3,500M (35%)
$targetD = 1500000000.0; // 1,500M (15%)

echo "\n------------------------------------------------------------------------\n";
echo "NUEVAS CANTIDADES POR COLONIA (Total 10.000M recursos):\n";
echo "  - Metal (50%):     " . number_format($targetM, 2) . " (5.000M)\n";
echo "  - Cristal (35%):   " . number_format($targetC, 2) . " (3.500M)\n";
echo "  - Deuterio (15%):  " . number_format($targetD, 2) . " (1.500M)\n";
echo "  - Total Nominal:   10,000,000,000.00 (10.000M unidades)\n";
echo "  - Total MSE (1:2:4): " . number_format($targetM + 2*$targetC + 4*$targetD, 2) . " MSE (18.000M MSE)\n";
echo "------------------------------------------------------------------------\n\n";

$db->beginTransaction();

try {
    foreach ($planets as $p) {
        $db->update(
            "UPDATE %%PLANETS%% SET metal = :m, crystal = :c, deuterium = :d WHERE id = :id;",
            array(
                ':m'  => $targetM,
                ':c'  => $targetC,
                ':d'  => $targetD,
                ':id' => (int)$p['id'],
            )
        );
    }

    $db->commit();
    echo "¡Actualización completada exitosamente en las 8 colonias!\n\n";

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "ERROR EXCEPCIÓN: " . $e->getMessage() . "\n";
    exit(1);
}

// Verificación post-update
$updatedPlanets = $db->select("SELECT id, name, metal, crystal, deuterium, b_hangar_id FROM %%PLANETS%% WHERE id_owner = 4 AND destruyed = 0 AND planet_type = 1 ORDER BY id ASC;");

echo "--- ESTADO FINAL EN LAS 8 COLONIAS ---\n";
foreach ($updatedPlanets as $p) {
    $hasQueue = !empty($p['b_hangar_id']) ? "COLA ACTIVA" : "SIN COLA";
    echo sprintf("Colonia %-12s [ID %2d]: M=%16s | C=%16s | D=%16s | [%s]\n",
        $p['name'], $p['id'],
        number_format((float)$p['metal'], 2),
        number_format((float)$p['crystal'], 2),
        number_format((float)$p['deuterium'], 2),
        $hasQueue
    );
}

echo "========================================================================\n";
