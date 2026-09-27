<?php
/**
 * Arbitrage detector using Bellman-Ford algorithm on negative log exchange rates.
 * Checks for infinite currency generation cycles.
 */
define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);
chdir(ROOT_PATH);

require_once 'includes/common.php';
while (ob_get_level()) ob_end_flush();

echo "=== Verificador de Arbitraje de NovaRush (Bellman-Ford) ===\n\n";

$PRS = max(1.0, PremiumEconomy::get('prs', 1000000.0));
$profiles = array(
    'Novato (P = Ingreso Base)' => PremiumEconomy::baseIncomeMSE(),
    'Medio (P = PRS)'          => $PRS,
    'Veterano (P = 10x PRS)'   => 10.0 * $PRS,
);

$allPassed = true;

foreach ($profiles as $profileName => $P) {
    echo "Analizando perfil: $profileName (P = " . number_format($P, 0) . " MSE/h)\n";
    $Pidx = max($P, PremiumEconomy::get('floor_alpha', 0.5) * $PRS);
    
    // Tasas reales de conversión
    // 1. MSE -> MO (Feria 2301-2303 primera conversión: 6h * Pidx -> 1000 MO)
    $mse_to_dm = PremiumEconomy::get('fair_dm_out', 1000) / (PremiumEconomy::get('fair_hours', 6) * $Pidx);
    
    // 2. MO -> AM (Feria 2306: 12.000 MO -> 1.000 AM = 1/12)
    $dm_to_am = 1000.0 / 12000.0;
    
    // 3. AM <-> SD (Feria 2304 / 2305: 1800 AM -> 1 SD / 1 SD -> 1200 AM)
    $am_to_sd = 1.0 / 1800.0;
    $sd_to_am = 1200.0;
    
    // 4. AM <-> CT (Feria 2308 / 2307: 600 AM -> 100 CT / 100 CT -> 400 AM)
    $am_to_ct = 100.0 / 600.0;
    $ct_to_am = 400.0 / 100.0;
    
    // 5. CT -> MSE (Apertura de Contenedor: 0.2h PRS -> MSE)
    $ct_to_mse = PremiumEconomy::get('ct_open_hours', 0.2) * $PRS;
    
    $edges = array(
        array('MSE', 'DM', $mse_to_dm),
        array('DM',  'AM', $dm_to_am),
        array('AM',  'SD', $am_to_sd),
        array('SD',  'AM', $sd_to_am),
        array('AM',  'CT', $am_to_ct),
        array('CT',  'AM', $ct_to_am),
        array('CT',  'MSE', $ct_to_mse),
    );

    // Bellman-Ford con peso w = -ln(tasa)
    $nodes = array('MSE', 'DM', 'AM', 'SD', 'CT');
    $dist = array_fill_keys($nodes, 0.0);
    $numNodes = count($nodes);

    for ($i = 0; $i < $numNodes - 1; $i++) {
        foreach ($edges as $e) {
            list($u, $v, $rate) = $e;
            if ($rate <= 0) continue;
            $w = -log($rate);
            if ($dist[$u] + $w < $dist[$v]) {
                $dist[$v] = $dist[$u] + $w;
            }
        }
    }

    $cycleDetected = false;
    foreach ($edges as $e) {
        list($u, $v, $rate) = $e;
        if ($rate <= 0) continue;
        $w = -log($rate);
        if ($dist[$u] + $w < $dist[$v] - 1e-6) {
            echo "  [ALERTA] Ciclo de arbitraje con ganancia detectado entre $u y $v!\n";
            $cycleDetected = true;
            $allPassed = false;
            break;
        }
    }

    if (!$cycleDetected) {
        echo "  [OK] No existen ciclos de arbitraje infinito para este perfil.\n";
    }
    echo "\n";
}

if ($allPassed) {
    echo "RESULTADO FINAL: Sistema 100% blindado contra arbitraje monetario.\n";
    exit(0);
} else {
    echo "RESULTADO FINAL: Falla de arbitraje detectada.\n";
    exit(1);
}
