<?php

/*
 * ╔══╗╔══╗╔╗──╔╗╔═══╗╔══╗╔╗─╔╗╔╗╔╗──╔╗╔══╗╔══╗╔══╗
 * ║╔═╝║╔╗║║║──║║║╔═╗║║╔╗║║╚═╝║║║║║─╔╝║╚═╗║║╔═╝╚═╗║
 * ║║──║║║║║╚╗╔╝║║╚═╝║║╚╝║║╔╗─║║╚╝║─╚╗║╔═╝║║╚═╗──║║
 * ║║──║║║║║╔╗╔╗║║╔══╝║╔╗║║║╚╗║╚═╗║──║║╚═╗║║╔╗║──║║
 * ║╚═╗║╚╝║║║╚╝║║║║───║║║║║║─║║─╔╝║──║║╔═╝║║╚╝║──║║
 * ╚══╝╚══╝╚╝──╚╝╚╝───╚╝╚╝╚╝─╚╝─╚═╝──╚╝╚══╝╚══╝──╚╝
 *
 * @author Tsvira Yaroslav <https://github.com/Yaro2709>
 * @info ***
 * @link https://github.com/Yaro2709/New-Star
 * @Basis 2Moons: XG-Project v2.8.0
 * @Basis New-Star: 2Moons v1.8.0
 */

class ShowInfobonusPage extends AbstractGamePage
{	
    public static $requireModule = MODULE_INFO_BONUS;

    function __construct() 
    {
        parent::__construct();
    }
	
    function show()
    {
        global $USER, $PLANET, $LNG, $reslist, $pricelist, $resource;

        $categories = array(
            'combat' => array(
                'AttackA', 'DefensiveA', 'ShieldA', 'AttackD', 'DefensiveD', 'ShieldD',
                'Attack', 'AttackSlaser', 'AttackSion', 'AttackSplasma', 'AttackSgravity',
                'Defensive', 'DefensiveSlight', 'DefensiveSmedium', 'DefensiveSheavy',
                'Shield', 'ShieldSlight', 'ShieldSmedium', 'ShieldSheavy',
                'DoubleAttack', 'DoubleShield', 'DoubleDefensive',
                'DoubleAttackBonus', 'DoubleShieldBonus', 'DoubleDefensiveBonus',
                'Focusing', 'AntiFocusing', 'AccurateShots', 'ChainReaction', 'ExpBoost'
            ),
            'economy' => array(
                'Resource', 'Pmetal', 'Pcrystal', 'Pdeuterium', 'Senergy',
                'ResourceStorage', 'ShipStorage'
            ),
            'speed' => array(
                'Sbuild', 'Stech', 'Sfleet', 'Sdefense', 'Smissile',
                'FlyTime', 'FlyTimeCom', 'FlyTimeImp', 'FlyTimeHyp', 'GateCoolTime'
            ),
            'expansion' => array(
                'FleetSlots', 'Planets', 'SpyPower', 'Expedition',
                'BuildSlots', 'ResearchSlots', 'ResearchSlotPlanet',
                'MoreFound', 'ShieldDome', 'OrbitalBases'
            ),
            'efficiency' => array(
                'CostRbuild', 'CostRfleet', 'CostRtech', 'CostRdefense', 'CostRmissile',
                'Debris', 'DefRecovery', 'FuelConsum'
            )
        );

        $intBonuses = array(
            'FleetSlots', 'Planets', 'SpyPower', 'Expedition', 'BuildSlots',
            'ResearchSlots', 'ResearchSlotPlanet', 'AccurateShots', 'ExpBoost',
            'ShieldDome', 'OrbitalBases'
        );

        $capMap = array(
            'Resource'  => class_exists('PremiumEconomy') ? PremiumEconomy::get('cap_resource', 4.0) : 4.0,
            'Attack'    => class_exists('PremiumEconomy') ? PremiumEconomy::get('cap_combat', 3.0) : 3.0,
            'Defensive' => class_exists('PremiumEconomy') ? PremiumEconomy::get('cap_combat', 3.0) : 3.0,
            'Shield'    => class_exists('PremiumEconomy') ? PremiumEconomy::get('cap_combat', 3.0) : 3.0,
            'Sbuild'    => class_exists('PremiumEconomy') ? PremiumEconomy::get('cap_speed', 3.0) : 3.0,
            'Stech'     => class_exists('PremiumEconomy') ? PremiumEconomy::get('cap_speed', 3.0) : 3.0,
            'Sfleet'    => class_exists('PremiumEconomy') ? PremiumEconomy::get('cap_speed', 3.0) : 3.0,
            'FlyTime'   => class_exists('PremiumEconomy') ? PremiumEconomy::get('cap_speed', 3.0) : 3.0,
        );

        // Classify source elements
        $tempLists = array_merge(
            isset($reslist['dmfunc']) ? $reslist['dmfunc'] : array(),
            isset($reslist['premium']) ? $reslist['premium'] : array(),
            isset($reslist['artifact']) ? $reslist['artifact'] : array(),
            isset($reslist['development']) ? $reslist['development'] : array(),
            isset($reslist['party']) ? $reslist['party'] : array()
        );

        $sqrtLists = array_merge(
            isset($reslist['ars']) ? $reslist['ars'] : array(),
            isset($reslist['details']) ? $reslist['details'] : array()
        );

        // Pre-scan active sources
        $sourcesByBonus = array();
        $rawTempByBonus = array();

        if (isset($reslist['bonus']) && is_array($reslist['bonus'])) {
            foreach ($reslist['bonus'] as $elementID) {
                if (isset($PLANET[$resource[$elementID]])) {
                    $level = $PLANET[$resource[$elementID]];
                } elseif (isset($USER[$resource[$elementID]])) {
                    $level = $USER[$resource[$elementID]];
                } else {
                    continue;
                }

                if (empty($level)) {
                    continue;
                }

                $isTemp = in_array($elementID, $tempLists);

                if ($isTemp) {
                    if ($level <= TIMESTAMP) {
                        continue;
                    }
                } else {
                    if ($level <= 0) {
                        continue;
                    }
                }

                if (!isset($pricelist[$elementID]['bonus']) || !is_array($pricelist[$elementID]['bonus'])) {
                    continue;
                }

                // Determine category label for the source
                $srcCat = 'Especial';
                if ($elementID >= 100 && $elementID < 200) {
                    $srcCat = isset($LNG['tech'][100]) ? $LNG['tech'][100] : 'Investigación';
                } elseif ($elementID >= 600 && $elementID < 700) {
                    $srcCat = isset($LNG['tech'][600]) ? $LNG['tech'][600] : 'Oficial';
                } elseif ($elementID >= 700 && $elementID < 800) {
                    $srcCat = isset($LNG['tech'][700]) ? $LNG['tech'][700] : 'Planos';
                } elseif ($elementID >= 1000 && $elementID < 1100) {
                    $srcCat = isset($LNG['tech'][1000]) ? $LNG['tech'][1000] : 'Detalles';
                } elseif ($elementID >= 1200 && $elementID < 1300) {
                    $srcCat = isset($LNG['tech'][1200]) ? $LNG['tech'][1200] : 'Facción';
                } elseif ($elementID >= 1400 && $elementID < 1500) {
                    $srcCat = isset($LNG['tech'][1400]) ? $LNG['tech'][1400] : 'Artefacto';
                } elseif ($elementID >= 1900 && $elementID < 2000) {
                    $srcCat = isset($LNG['tech'][1900]) ? $LNG['tech'][1900] : 'Desarrollo';
                } elseif ($elementID >= 2000 && $elementID < 2100) {
                    $srcCat = isset($LNG['tech'][2000]) ? $LNG['tech'][2000] : 'Arsenal';
                } elseif ($elementID >= 2100 && $elementID < 2200) {
                    $srcCat = isset($LNG['tech'][2100]) ? $LNG['tech'][2100] : 'Premium';
                } elseif ($elementID >= 2400 && $elementID < 2500) {
                    $srcCat = 'Bono 30d';
                } elseif (isset($reslist['tech']) && in_array($elementID, $reslist['tech'])) {
                    $srcCat = isset($LNG['tech'][100]) ? $LNG['tech'][100] : 'Investigación';
                } elseif (isset($reslist['officier']) && in_array($elementID, $reslist['officier'])) {
                    $srcCat = isset($LNG['tech'][600]) ? $LNG['tech'][600] : 'Oficial';
                } elseif (isset($reslist['artifact']) && in_array($elementID, $reslist['artifact'])) {
                    $srcCat = isset($LNG['tech'][1400]) ? $LNG['tech'][1400] : 'Artefacto';
                } elseif (isset($reslist['development']) && in_array($elementID, $reslist['development'])) {
                    $srcCat = isset($LNG['tech'][1900]) ? $LNG['tech'][1900] : 'Desarrollo';
                } elseif (isset($reslist['premium']) && in_array($elementID, $reslist['premium'])) {
                    $srcCat = isset($LNG['tech'][2100]) ? $LNG['tech'][2100] : 'Premium';
                } elseif (isset($reslist['dmfunc']) && in_array($elementID, $reslist['dmfunc'])) {
                    $srcCat = isset($LNG['tech'][700]) ? $LNG['tech'][700] : 'Planos';
                } elseif (isset($reslist['ars']) && in_array($elementID, $reslist['ars'])) {
                    $srcCat = isset($LNG['tech'][2000]) ? $LNG['tech'][2000] : 'Arsenal';
                } elseif (isset($reslist['details']) && in_array($elementID, $reslist['details'])) {
                    $srcCat = isset($LNG['tech'][1000]) ? $LNG['tech'][1000] : 'Detalles';
                } elseif (isset($reslist['party']) && in_array($elementID, $reslist['party'])) {
                    $srcCat = isset($LNG['tech'][1200]) ? $LNG['tech'][1200] : 'Facción';
                }

                $srcName = isset($LNG['tech'][$elementID]) ? $LNG['tech'][$elementID] : "ID #$elementID";

                foreach ($pricelist[$elementID]['bonus'] as $bonusKey => $bonusData) {
                    $baseBonus = (float)$bonusData[0];
                    if (empty($baseBonus)) {
                        continue;
                    }

                    if ($isTemp) {
                        $contribVal = $baseBonus;
                        if (!isset($rawTempByBonus[$bonusKey])) {
                            $rawTempByBonus[$bonusKey] = 0;
                        }
                        $rawTempByBonus[$bonusKey] += $baseBonus;
                    } elseif (in_array($elementID, $sqrtLists)) {
                        $contribVal = sqrt($level) * $baseBonus;
                    } else {
                        $contribVal = $level * $baseBonus;
                    }

                    $isIntegerBonus = in_array($bonusKey, $intBonuses);
                    if ($isIntegerBonus) {
                        $valStr = ($contribVal >= 0 ? '+' : '') . round($contribVal);
                    } else {
                        $pv = $contribVal * 100;
                        $pvFormatted = (round($pv, 1) == round($pv)) ? round($pv) : number_format($pv, 1, '.', '');
                        $valStr = ($pv >= 0 ? '+' : '') . $pvFormatted . '%';
                    }

                    $sourcesByBonus[$bonusKey][] = array(
                        'id'         => $elementID,
                        'name'       => $srcName,
                        'category'   => $srcCat,
                        'level'      => $isTemp ? 1 : $level,
                        'is_temp'    => $isTemp,
                        'time_str'   => $isTemp ? pretty_time($level - TIMESTAMP) : '',
                        'val_str'    => $valStr,
                    );
                }
            }
        }

        // Process all bonuses and assign to rows
        $bonusRows = array();
        $catCounts = array(
            'all'        => array('total' => 0, 'active' => 0),
            'combat'     => array('total' => 0, 'active' => 0),
            'economy'    => array('total' => 0, 'active' => 0),
            'speed'      => array('total' => 0, 'active' => 0),
            'expansion'  => array('total' => 0, 'active' => 0),
            'efficiency' => array('total' => 0, 'active' => 0),
        );

        foreach ($categories as $catKey => $keys) {
            foreach ($keys as $k) {
                $isPercent = !in_array($k, $intBonuses);
                $effectiveVal = isset($USER['factor'][$k]) ? (float)$USER['factor'][$k] : 0.0;
                $rawTemp = isset($rawTempByBonus[$k]) ? (float)$rawTempByBonus[$k] : 0.0;
                $sources = isset($sourcesByBonus[$k]) ? $sourcesByBonus[$k] : array();

                // Soft Cap check
                $isSoftCapped = false;
                $softCapData = null;
                if (isset($capMap[$k]) && $rawTemp > 0 && class_exists('PremiumEconomy')) {
                    $cappedTemp = PremiumEconomy::softCap($rawTemp, $capMap[$k]);
                    $diff = $rawTemp - $cappedTemp;
                    if ($diff > 0.001) {
                        $isSoftCapped = true;
                        $softCapData = array(
                            'raw'    => number_format($rawTemp * 100, 1, '.', '') . '%',
                            'capped' => number_format($cappedTemp * 100, 1, '.', '') . '%',
                            'loss'   => number_format($diff * 100, 1, '.', '') . '%',
                        );
                    }
                }

                // Format effective value
                $isZero = (abs($effectiveVal) < 0.00001);
                if ($isZero) {
                    $formattedVal = $isPercent ? '0%' : '0';
                    $color = '#64748b';
                } else {
                    if ($isPercent) {
                        $pct = $effectiveVal * 100;
                        $pctFormatted = (round($pct, 1) == round($pct)) ? round($pct) : number_format($pct, 1, '.', '');
                        $formattedVal = ($pct > 0 ? '+' : '') . $pctFormatted . '%';
                    } else {
                        $intVal = round($effectiveVal);
                        $formattedVal = ($intVal > 0 ? '+' : '') . $intVal;
                    }
                    $color = ($effectiveVal > 0) ? '#00e676' : '#ff5252';
                }

                $name = isset($LNG['bonus'][$k]) ? $LNG['bonus'][$k] : $k;
                $desc = isset($LNG['bonus_desc'][$k]) ? $LNG['bonus_desc'][$k] : '';
                $descPreview = (mb_strlen($desc) > 85) ? (mb_substr($desc, 0, 82) . '...') : $desc;

                $tooltipHtml = self::buildTooltipHtml($k, $name, $desc, $formattedVal, $color, $sources, $isSoftCapped, $softCapData, $LNG);

                $bonusRows[] = array(
                    'key'             => $k,
                    'category'        => $catKey,
                    'name'            => $name,
                    'desc'            => $desc,
                    'desc_preview'    => $descPreview,
                    'formatted_value' => $formattedVal,
                    'color'           => $color,
                    'is_zero'         => $isZero,
                    'is_softcapped'   => $isSoftCapped,
                    'source_count'    => count($sources),
                    'tooltip_html'    => $tooltipHtml,
                );

                $catCounts[$catKey]['total']++;
                $catCounts['all']['total']++;
                if (!$isZero) {
                    $catCounts[$catKey]['active']++;
                    $catCounts['all']['active']++;
                }
            }
        }

        $tabs = array(
            'all'        => array('id' => 'all',        'name' => $LNG['inb_all'],            'counts' => $catCounts['all']),
            'combat'     => array('id' => 'combat',     'name' => $LNG['inb_cat_combat'],     'counts' => $catCounts['combat']),
            'economy'    => array('id' => 'economy',    'name' => $LNG['inb_cat_economy'],    'counts' => $catCounts['economy']),
            'speed'      => array('id' => 'speed',      'name' => $LNG['inb_cat_speed'],      'counts' => $catCounts['speed']),
            'expansion'  => array('id' => 'expansion',  'name' => $LNG['inb_cat_expansion'],  'counts' => $catCounts['expansion']),
            'efficiency' => array('id' => 'efficiency', 'name' => $LNG['inb_cat_efficiency'], 'counts' => $catCounts['efficiency']),
        );

        $this->tplObj->assign_vars(array(
            'bonusRows'  => $bonusRows,
            'tabs'       => $tabs,
            'catCounts'  => $catCounts,
            'USER'       => $USER,
        ));

        $this->display("page.infobonus.default.tpl");
    }

    private static function buildTooltipHtml($key, $name, $desc, $displayVal, $valColor, $sources, $isSoftCapped, $softCapData, $LNG)
    {
        $html = "<div class='ib-tip-box' style='min-width:270px;max-width:350px;font-size:11px;line-height:1.4;color:#d1d5db;text-align:left;'>";
        $html .= "<div style='display:flex;align-items:center;border-bottom:1px solid #374151;padding-bottom:5px;margin-bottom:6px;'>";
        $html .= "<span style='font-weight:bold;font-size:12px;color:#60a5fa;'>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</span>";
        $html .= "</div>";

        if (!empty($desc)) {
            $html .= "<div style='color:#9ca3af;margin-bottom:7px;font-style:italic;font-size:11px;'>" . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . "</div>";
        }

        $html .= "<div style='background:rgba(255,255,255,0.05);border-radius:3px;padding:5px 8px;margin-bottom:7px;display:flex;justify-content:space-between;align-items:center;'>";
        $html .= "<span style='color:#e5e7eb;font-weight:600;'>" . $LNG['inb_effective_value'] . ":</span>";
        $html .= "<span style='font-weight:bold;font-size:12px;color:" . $valColor . ";'>" . $displayVal . "</span>";
        $html .= "</div>";

        if ($isSoftCapped && !empty($softCapData)) {
            $html .= "<div style='background:rgba(245,158,11,0.12);border-left:3px solid #f59e0b;padding:4px 6px;margin-bottom:7px;font-size:10.5px;color:#fde68a;'>";
            $html .= "⚠️ <b>" . $LNG['inb_softcap_note'] . "</b><br>";
            $html .= "Temporal: +" . $softCapData['raw'] . " → Efectivo: +" . $softCapData['capped'] . " (-" . $softCapData['loss'] . ")";
            $html .= "</div>";
        }

        $html .= "<div style='border-top:1px solid #374151;padding-top:5px;'>";
        $srcCount = count($sources);
        $html .= "<div style='font-weight:bold;color:#93c5fd;margin-bottom:4px;font-size:10.5px;text-transform:uppercase;'>";
        $html .= $LNG['inb_breakdown'] . " (" . $srcCount . "):";
        $html .= "</div>";

        if ($srcCount === 0) {
            $html .= "<div style='color:#6b7280;font-style:italic;'>" . $LNG['inb_no_active'] . "</div>";
        } else {
            $html .= "<table style='width:100%;border-collapse:collapse;font-size:10.5px;'>";
            foreach ($sources as $s) {
                $html .= "<tr style='border-bottom:1px dashed rgba(255,255,255,0.07);'>";
                $html .= "<td style='padding:3px 2px;color:#f3f4f6;'>";
                $html .= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8');
                $html .= " <span style='color:#9ca3af;font-size:9.5px;'>(" . htmlspecialchars($s['category'], ENT_QUOTES, 'UTF-8');
                if (!$s['is_temp'] && $s['level'] > 1) {
                    $html .= " Lv." . $s['level'];
                }
                $html .= ")</span>";
                if ($s['is_temp']) {
                    $html .= "<div style='color:#fbbf24;font-size:9.5px;'>⏳ " . $LNG['inb_expires_in'] . ": " . $s['time_str'] . "</div>";
                }
                $html .= "</td>";
                $html .= "<td style='padding:3px 2px;text-align:right;font-weight:bold;color:#34d399;vertical-align:top;white-space:nowrap;'>";
                $html .= $s['val_str'];
                $html .= "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
        }
        $html .= "</div>";
        $html .= "</div>";

        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }
}