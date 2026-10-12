<?php
$data = json_decode(file_get_contents('scratch_report_10min.json'), true);
foreach ($data['bots'] as $botName => $b) {
    echo "\n=======================================================\n";
    echo "DETALLE PLANETARIO: $botName\n";
    echo "=======================================================\n";
    foreach ($b['planets'] as $pid => $p) {
        echo "\n>> Planeta #$pid: {$p['name']} {$p['coords']}\n";
        echo "  BUDGET FLOTA:    [Ini] M: " . number_format($p['initial_fleet']['metal']) . ", C: " . number_format($p['initial_fleet']['crystal']) . ", D: " . number_format($p['initial_fleet']['deuterium']) . "\n";
        echo "                   [Fin] M: " . number_format($p['final_fleet']['metal']) . ", C: " . number_format($p['final_fleet']['crystal']) . ", D: " . number_format($p['final_fleet']['deuterium']) . "\n";
        echo "  BUDGET DEFENSA:  [Ini] M: " . number_format($p['initial_defense']['metal']) . ", C: " . number_format($p['initial_defense']['crystal']) . ", D: " . number_format($p['initial_defense']['deuterium']) . "\n";
        echo "                   [Fin] M: " . number_format($p['final_defense']['metal']) . ", C: " . number_format($p['final_defense']['crystal']) . ", D: " . number_format($p['final_defense']['deuterium']) . "\n";
        echo "  GASTO FLOTA:     M: " . number_format($p['spent']['fleet']['metal']) . ", C: " . number_format($p['spent']['fleet']['crystal']) . ", D: " . number_format($p['spent']['fleet']['deuterium']) . "\n";
        echo "  GASTO DEFENSA:   M: " . number_format($p['spent']['defense']['metal']) . ", C: " . number_format($p['spent']['defense']['crystal']) . ", D: " . number_format($p['spent']['defense']['deuterium']) . "\n";
        
        $uFleet = array();
        foreach ($p['units']['fleet'] as $un => $c) $uFleet[] = "$c $un";
        $uDef = array();
        foreach ($p['units']['defense'] as $un => $c) $uDef[] = "$c $un";

        echo "  UNIDADES FLOTA:   " . (!empty($uFleet) ? implode(', ', $uFleet) : 'Ninguna') . "\n";
        echo "  UNIDADES DEFENSA: " . (!empty($uDef) ? implode(', ', $uDef) : 'Ninguna') . "\n";
    }
}
