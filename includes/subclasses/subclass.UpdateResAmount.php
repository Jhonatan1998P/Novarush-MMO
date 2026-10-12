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

//$new_code
foreach($reslist['resstype'][1] as $resPM)
{
    if(isset($costResources[$resPM])) {
        if($PLANET[$resource[$resPM]] < $costResources[$resPM]* $amount){
            $this->printMessage("".$LNG['bd_notres']."", true, array($href, 2));
        }
    }
}
        
foreach($reslist['resstype'][3] as $resUM)
{
    if(isset($costResources[$resUM])) {
        if($USER[$resource[$resUM]] < $costResources[$resUM]* $amount){
            $this->printMessage("".$LNG['bd_notres']."", true, array($href, 2));
        }
    }
}
        
foreach($reslist['resstype'][1] as $resP)
{
    if(isset($costResources[$resP])) {$PLANET[$resource[$resP]]	-= $costResources[$resP]* $amount; }
}
        
foreach($reslist['resstype'][3] as $resU)
{
    if(isset($costResources[$resU]) && $costResources[$resU] > 0) {
        $source = !empty($mode) ? $mode . '_buy' : 'subclass_buy';
        $elementId = isset($Element) ? (int)$Element : 0;
        $totalCost = $costResources[$resU] * $amount;
        if (!PremiumEconomy::debit($USER, $resU, $totalCost, $source, $elementId, "amt={$amount}")) {
            $this->printMessage("".$LNG['bd_notres']."", true, array($href, 2));
        }
    }
}
//$new_code