<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
global $pricelist;

echo "CAPACIDAD 209 (Reciclador): " . (isset($pricelist[209]['capacity']) ? $pricelist[209]['capacity'] : 'NO DEFINIDA') . "\n";
echo "CAPACIDAD 219 (Giga Reciclador): " . (isset($pricelist[219]['capacity']) ? $pricelist[219]['capacity'] : 'NO DEFINIDA') . "\n";
echo "COSTO 209: " . json_encode($pricelist[209]['cost']) . "\n";
echo "COSTO 219: " . json_encode($pricelist[219]['cost']) . "\n";
