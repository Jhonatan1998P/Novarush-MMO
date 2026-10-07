<?php
define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require 'includes/classes/class.BuildFunctions.php';

global $pricelist;
echo "Pricelist 134:\n";
print_r($pricelist[134]);

$user = Database::get()->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1;");
$planet = Database::get()->selectSingle("SELECT * FROM %%PLANETS%% WHERE id_owner = 1 LIMIT 1;");
$planet['tech_inter'] = $planet['laboratory'];
$user['factor'] = getFactors($user);

// Simulate ShowResearchPage line 489
$researchList = array(
    'id' => 134,
    'factor' => $pricelist[134]['factor'],
    'factors' => array(
        901 => isset($pricelist[134]['factor901']) ? $pricelist[134]['factor901'] : $pricelist[134]['factor'],
        902 => isset($pricelist[134]['factor902']) ? $pricelist[134]['factor902'] : $pricelist[134]['factor'],
        903 => isset($pricelist[134]['factor903']) ? $pricelist[134]['factor903'] : $pricelist[134]['factor'],
    ),
);
print_r($researchList);
