<?php
/* Smarty version 3.1.36, created on 2026-09-27 04:12:30
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.shipyard.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab87b8e812b53_54375478',
  'has_nocache_code' => true,
  'file_dependency' => 
  array (
    '2cae796ef2966f8fb98e2784bf8ef49c879fc176' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.shipyard.default.tpl',
      1 => 1642351636,
      2 => 'extends',
    ),
    '3797e1c95194d9bb60aec273dcd0b0667dc139dd' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.shipyard.default.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
    'd63d6432ef23500a7b3d46d973fb3cfb4eab4e48' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\layout.full.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
    '48e3dc7c81da9ec904a0f8602367542c854e3727' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\main.header.tpl',
      1 => 1790387714,
      2 => 'file',
    ),
    '0a851ce8eec566aca67772a206ca7211221f0f83' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\main.navigation.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
    'ff3b3b778c041f26c841a05fa5174228da36306d' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\main.topnav.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:page.shipyard.default.tpl' => 1,
    'file:layout.full.tpl' => 1,
    'file:main.header.tpl' => 1,
    'file:main.navigation.tpl' => 1,
    'file:main.topnav.tpl' => 1,
    'file:main.footer.tpl' => 1,
  ),
),false)) {
function content_6ab87b8e812b53_54375478 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
$_smarty_tpl->compiled->nocache_hash = '2413373286ab87b8e654f35_38151844';
$_smarty_tpl->_subTemplateRender('file:page.shipyard.default.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, '3797e1c95194d9bb60aec273dcd0b0667dc139dd', 'content_6ab87b8e670b68_27557198');
$_smarty_tpl->inheritance->endChild($_smarty_tpl);
$_smarty_tpl->_subTemplateRender('file:layout.full.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, 'd63d6432ef23500a7b3d46d973fb3cfb4eab4e48', 'content_6ab87b8e7839f8_80417462');
}
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.shipyard.default.tpl" =============================*/
function content_6ab87b8e670b68_27557198 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '2413373286ab87b8e654f35_38151844';
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_93277226ab87b8e6bc5e8_13959392', "title");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_13592429856ab87b8e6c2cd1_37167152', "content");
}
/* {block "title"} */
class Block_93277226ab87b8e6bc5e8_13959392 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_93277226ab87b8e6bc5e8_13959392',
  ),
);
public $prepend = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['2413373286ab87b8e654f35_38151844'] = true;
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'mode\']->value == "defense") {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_defenses\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_shipshard\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
}
}
/* {/block "title"} */
/* {block "content"} */
class Block_13592429856ab87b8e6c2cd1_37167152 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_13592429856ab87b8e6c2cd1_37167152',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['2413373286ab87b8e654f35_38151844'] = true;
?>

<link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
css/building.css">
<div id="page">
    <div id="content">        
        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'Queue\']->value)) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

            <div id="buildlist" class="buildlist">
                <div class="buildtitle">
                    <div id="build_process" class="shi4era">
                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'Queue\']->value, \'List\');
$_smarty_tpl->tpl_vars[\'List\']->iteration = 0;
$_smarty_tpl->tpl_vars[\'List\']->index = -1;
$_smarty_tpl->tpl_vars[\'List\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'List\']->value) {
$_smarty_tpl->tpl_vars[\'List\']->do_else = false;
$_smarty_tpl->tpl_vars[\'List\']->iteration++;
$_smarty_tpl->tpl_vars[\'List\']->index++;
$_smarty_tpl->tpl_vars[\'List\']->first = !$_smarty_tpl->tpl_vars[\'List\']->index;
$__foreach_List_0_saved = $_smarty_tpl->tpl_vars[\'List\'];
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php $_smarty_tpl->_assignInScope(\'ID\', $_smarty_tpl->tpl_vars[\'List\']->value[\'element\'] ,true);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <div class="element_row <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'List\']->first) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
active_row<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" style="background:url(<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.gif)">
                            <div class="right_hand">
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'List\']->first) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                <span class="onlistretit"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'ID\']->value];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>
                                <form action="game.php?page=shipyard&amp;mode=<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'mode\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" method="post" class="build_form">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="auftr[]" value="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'List\']->iteration-1;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                                    <button type="submit" class="del tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'bd_cancel_warning\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"></button>
                                </form>
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                <form action="game.php?page=shipyard&amp;mode=<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'mode\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" method="post" class="build_form">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="auftr[]" value="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'List\']->iteration-1;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                                    <button type="submit" class="del tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'bd_cancel_warning\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"></button>
                                </form>
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'List\']->first) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                <div id="time" data-time="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_time($_smarty_tpl->tpl_vars[\'List\']->value[\'timeque\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"></div>
                                <div id="bx" class="shipyardd1"></div>
                                <span class="shipyardd2">(в процессе)</span>
                                <span class="onlistremov"><span id="auftr0"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'List\']->value[\'amount\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></span>
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                <span class="onlistrenum"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'List\']->iteration;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!$_smarty_tpl->tpl_vars[\'List\']->first) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                <span class="onlistremov"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'List\']->value[\'amount\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                
                            </div>
                        </div>
                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_smarty_tpl->tpl_vars[\'List\'] = $__foreach_List_0_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (isModuleAvailable(@constant(\'MODULE_STORE\'))) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <div class="element_row">
                            <a href="game.php?page=store"><div class="pluscos">+</div></a>
                        </div>
                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <span id="timeleft" class="shipyardd3"></span>
                    </div>
                </div>
            </div>
        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

        <form action="game.php?page=shipyard&amp;mode=<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'mode\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" method="post">
            <div id="build_content" class="conteiner ship_build">
                <div id="fildes_band">
                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'mode\']->value == "defense") {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                    <div class="gray_stripe" style="height:0px; padding-right: 0; padding: 0;">
                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (isModuleAvailable(@constant(\'MODULE_FLEET_TRADER\'))) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <a href="game.php?page=fleetDealer" class="palanetarium_linck ico_trader tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_fleettrader\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"></a>
                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <div style="float:left"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_defenses\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</div>
                        <span class="record_btn ico_star record_btn_active" onclick="rank();"></span>
                        <span class="record_btn ico_rank_0" onclick="rank0();"></span>
                        <span class="record_btn ico_rank_1" onclick="rank1();"></span>
                        <span class="record_btn ico_rank_2" onclick="rank2();"></span>
                        <span class="record_btn ico_rank_3" onclick="rank3();"></span>
                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (isModuleAvailable(@constant(\'MODULE_BUY_DEFENSE\'))) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <a href="game.php?page=buyDefense" class="right_flank button" style="margin-left: 10px;"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_buydefense\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</a>
                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                    </div>
                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                    <div class="gray_stripe" style="height:0px; padding-right: 0; padding: 0;">
                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (isModuleAvailable(@constant(\'MODULE_FLEET_TRADER\'))) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <a href="game.php?page=fleetDealer" class="palanetarium_linck ico_trader tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_fleettrader\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"></a>
                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <div style="float:left"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_shipshard\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</div>
                        <span class="record_btn ico_star record_btn_active" onclick="rank();"></span>
                        <span class="record_btn ico_rank_0" onclick="rank0();"></span>
                        <span class="record_btn ico_rank_1" onclick="rank1();"></span>
                        <span class="record_btn ico_rank_2" onclick="rank2();"></span>
                        <span class="record_btn ico_rank_3" onclick="rank3();"></span>
                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (isModuleAvailable(@constant(\'MODULE_BUY_FLEET\'))) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <a href="game.php?page=buyFleet" class="right_flank button" style="margin-left: 10px;"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_buyfleet\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</a>
                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                    </div>
                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                </div>
                <div id="build_elements">
                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'elementList\']->value, \'Element\', false, \'ID\');
$_smarty_tpl->tpl_vars[\'Element\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'ID\']->value => $_smarty_tpl->tpl_vars[\'Element\']->value) {
$_smarty_tpl->tpl_vars[\'Element\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                    <div class="build_elements">
                        <div id="s_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="build_box <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'mode\']->value == \'defense\') {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'ID\']->value == in_array($_smarty_tpl->tpl_vars[\'ID\']->value,$_smarty_tpl->tpl_vars[\'reslist\']->value[\'spec_defense\'][1])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
rank0<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'ID\']->value == in_array($_smarty_tpl->tpl_vars[\'ID\']->value,$_smarty_tpl->tpl_vars[\'reslist\']->value[\'spec_defense\'][2])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
rank1<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'ID\']->value == in_array($_smarty_tpl->tpl_vars[\'ID\']->value,$_smarty_tpl->tpl_vars[\'reslist\']->value[\'spec_defense\'][3])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
rank2<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'ID\']->value == in_array($_smarty_tpl->tpl_vars[\'ID\']->value,$_smarty_tpl->tpl_vars[\'reslist\']->value[\'spec_defense\'][4])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
rank3<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'ID\']->value == in_array($_smarty_tpl->tpl_vars[\'ID\']->value,$_smarty_tpl->tpl_vars[\'reslist\']->value[\'spec_fleet\'][1])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
rank0<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'ID\']->value == in_array($_smarty_tpl->tpl_vars[\'ID\']->value,$_smarty_tpl->tpl_vars[\'reslist\']->value[\'spec_fleet\'][2])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
rank1<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'ID\']->value == in_array($_smarty_tpl->tpl_vars[\'ID\']->value,$_smarty_tpl->tpl_vars[\'reslist\']->value[\'spec_fleet\'][3])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
rank2<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'ID\']->value == in_array($_smarty_tpl->tpl_vars[\'ID\']->value,$_smarty_tpl->tpl_vars[\'reslist\']->value[\'spec_fleet\'][4])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
rank3<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!$_smarty_tpl->tpl_vars[\'Element\']->value[\'techacc\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
required<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                            <div class="head">
                                <a href="#" onclick="return Dialog.info(<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
)" class="interrogation">?</a>                
                                <a href="#" onclick="return Dialog.info(<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
)" class="title"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'ID\']->value];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 </a> 
                                <span class="tooltip available" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'bd_available\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                                <span id="val_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'available\'] != 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 (<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'available\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
)<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>    
                            </div>
                            <div class="content_box">
                                <div class="image">
                                    <a href="#" onclick="return Dialog.info(<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
)"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.gif" alt="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'ID\']->value];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" /></a>
                                </div>
                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!$_smarty_tpl->tpl_vars[\'Element\']->value[\'techacc\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                <div class="prices">
                                    <div class="necccos"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'bd_needed_tech\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</div>
                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'Element\']->value[\'AllTech\'], \'requireList\', false, \'elementID\');
$_smarty_tpl->tpl_vars[\'requireList\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'elementID\']->value => $_smarty_tpl->tpl_vars[\'requireList\']->value) {
$_smarty_tpl->tpl_vars[\'requireList\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'requireList\']->value, \'NeedLevel\', false, \'requireID\');
$_smarty_tpl->tpl_vars[\'NeedLevel\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'requireID\']->value => $_smarty_tpl->tpl_vars[\'NeedLevel\']->value) {
$_smarty_tpl->tpl_vars[\'NeedLevel\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <div class="required_block required_smal_text">
                                            <a href="#" onclick="return Dialog.info(<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'requireID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
)" class="tooltip" data-tooltip-content="<span style='color:<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'NeedLevel\']->value[\'own\'] < $_smarty_tpl->tpl_vars[\'NeedLevel\']->value[\'count\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
red<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
lime<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
;'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'requireID\']->value];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tt_lvl\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
  <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'NeedLevel\']->value[\'count\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 (<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'NeedLevel\']->value[\'own\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'NeedLevel\']->value[\'count\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
)</span>">
                                            <img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'requireID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.gif" alt="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'requireID\']->value];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" />
                                            <div class="text" style="color:<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'NeedLevel\']->value[\'own\'] < $_smarty_tpl->tpl_vars[\'NeedLevel\']->value[\'count\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
red<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
lime<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
;"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'NeedLevel\']->value[\'own\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'NeedLevel\']->value[\'count\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</div></a>
                                        </div>
                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                </div>     
                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                <div class="prices">
                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'Element\']->value[\'costResources\'], \'RessAmount\', false, \'RessID\');
$_smarty_tpl->tpl_vars[\'RessAmount\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'RessID\']->value => $_smarty_tpl->tpl_vars[\'RessAmount\']->value) {
$_smarty_tpl->tpl_vars[\'RessAmount\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                    <div class="price res<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'RessID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'costOverflow\'][$_smarty_tpl->tpl_vars[\'RessID\']->value] == 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
required<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                                        <div class="ico"></div>
                                        <div class="text" data-tooltip-content=""><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'RessAmount\']->value);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</div>                                        
                                    </div>
                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
   
                                    <div class="res_global_info">
                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'Element\']->value[\'fleetgun\'])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'fleetgun\'] == \'notype\') {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <div class="res_info info_res_901"><a class="tooltip" data-tooltip-content="
                                            <table class='reducefleet_table'>
                                                <tr>
                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/notype.png'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'in_attack_pt\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'attack\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                </tr>
                                            </table>"><img height="15" width="15" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/notype.png"></a>
                                        </div>
                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <div class="res_info info_res_901"><a class="tooltip" data-tooltip-content="
                                            <table class='reducefleet_table'>
                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'Element\']->value[\'fleetgun\'][\'laser\'][\'attack\'])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                <tr>
                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/laser.jpg'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'in_attack_laser\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'fleetgun\'][\'laser\'][\'attack\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                </tr>
                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'Element\']->value[\'fleetgun\'][\'ion\'][\'attack\'])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                <tr>
                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/ion.jpg'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'in_attack_ionic\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'fleetgun\'][\'ion\'][\'attack\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                </tr>
                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'Element\']->value[\'fleetgun\'][\'plasma\'][\'attack\'])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                <tr>
                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/plasma.jpg'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'in_attack_buster\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'fleetgun\'][\'plasma\'][\'attack\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                </tr>
                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'Element\']->value[\'fleetgun\'][\'gravity\'][\'attack\'])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                <tr>
                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/gravity.jpg'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'in_attack_graviton\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'fleetgun\'][\'gravity\'][\'attack\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                </tr>
                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                            </table>"><img height="15" width="15" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/type_at.png"></a>
                                        </div>
                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <div class="res_info info_res_901"><a class="tooltip" data-tooltip-content="
                                            <table class='reducefleet_table'>
                                                <tr>
                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/d_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'info\'][\'class_defend\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.png'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value["in_armor_".((string)$_smarty_tpl->tpl_vars[\'Element\']->value[\'info\'][\'class_defend\'])];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'defend\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                </tr>
                                            </table>"><img height="15" width="15" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/d_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'info\'][\'class_defend\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.png"></a>
                                        </div>
                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'info\'][\'class_shield\'] != \'s_none\') {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <div class="res_info info_res_901"><a class="tooltip" data-tooltip-content="
                                            <table class='reducefleet_table'>
                                                <tr>
                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/s_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'info\'][\'class_shield\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.png'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value["in_shield_".((string)$_smarty_tpl->tpl_vars[\'Element\']->value[\'info\'][\'class_shield\'])];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'shield\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                </tr>
                                            </table>"><img height="15" width="15" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/s_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'info\'][\'class_shield\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.png"></a>
                                        </div>
                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'Element\']->value[\'tech\']) && !empty($_smarty_tpl->tpl_vars[\'Element\']->value[\'speed1\'])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <div class="res_info info_res_901">
                                        <a class="tooltip" data-tooltip-content="
                                            <table class='reducefleet_table'>
                                                <tr>
                                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'tech\'] == 1 || $_smarty_tpl->tpl_vars[\'Element\']->value[\'tech\'] == 4) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/comb.png'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][115];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'speed1\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'Element\']->value[\'tech\'] == 2 || $_smarty_tpl->tpl_vars[\'Element\']->value[\'tech\'] == 5) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/imp.png'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][117];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'speed1\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/hyper.png'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][118];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'speed1\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                </tr>
                                                <tr>
                                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'Element\']->value[\'consumption1\'])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/consumption.png'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'in_consumption\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'consumption1\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                </tr>
                                                <tr>
                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'Element\']->value[\'capacity\'])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/capacity.png'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'in_capacity\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'capacity\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></td>
                                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                </tr>
                                            </table>">
                                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'tech\'] == 1 || $_smarty_tpl->tpl_vars[\'Element\']->value[\'tech\'] == 4) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                <img height="15" width="15" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/comb.png">
                                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'Element\']->value[\'tech\'] == 2 || $_smarty_tpl->tpl_vars[\'Element\']->value[\'tech\'] == 5) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                <img height="15" width="15" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/imp.png">
                                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                <img height="15" width="15" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/hyper.png">
                                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        </a>
                                        </div>     
                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 
                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'ID\']->value == in_array($_smarty_tpl->tpl_vars[\'ID\']->value,$_smarty_tpl->tpl_vars[\'reslist\']->value[\'defense\'])) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <div class="res_info info_res_901"><a class="tooltip" data-tooltip-content="
                                            <table class='reducefleet_table'>
                                                <tr>
                                                    <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/recovery.png'></td>
                                                    <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'in_recovery\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</td>
                                                </tr>
                                            </table>"><img height="15" width="15" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/information/recovery.png"></a>
                                        </div>
                                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 
                                    </div>
                                </div>        
                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
			
                                <div class="clear"></div>
                                <div class="time_build">
                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!$_smarty_tpl->tpl_vars[\'Element\']->value[\'techacc\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'elementTime\'] >= 1) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 
                                        <span class="time_build_text"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/time.png" style="height: 12px; top: -1.4px;position: relative;"> <span class="time_build_edit"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_time($_smarty_tpl->tpl_vars[\'Element\']->value[\'elementTime\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></span>
                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                        <span class="time_build_text"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/time.png" style="height: 12px; top: -1.4px;position: relative;"> <span class="time_build_edit"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo round(1/$_smarty_tpl->tpl_vars[\'Element\']->value[\'elementTime\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'sec_conv\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></span>
                                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 
                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                </div>
                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'NotBuilding\']->value && $_smarty_tpl->tpl_vars[\'Element\']->value[\'buyable\'] && $_smarty_tpl->tpl_vars[\'Element\']->value[\'techacc\'] && $_smarty_tpl->tpl_vars[\'Element\']->value[\'maxBuildable\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 
                                <div id="res_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" style="display: block;">
                                    <div class="btn_build_border btn_build_border_left">
                                        <label title="макс" onclick="$('#input_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
').val('<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'maxBuildable\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
'); counting('<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
');" class="max_btn_ship">Max</label>
                                        <div class="div_text count_ships_dots">
                                            <input onkeyup="counting('<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
');" name="fmenge[<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
]" id="input_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" size="15" maxlength="15" value="0" class="text" tabindex="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo (isset($_smarty_tpl->tpl_vars[\'__smarty_foreach_FleetList\']->value[\'iteration\']) ? $_smarty_tpl->tpl_vars[\'__smarty_foreach_FleetList\']->value[\'iteration\'] : null);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" type="text" data-cip-id="input_210">
                                        </div>
                                    </div>
                                    <div class="btn_build_border btn_build_border_right">
                                        <input value="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'bd_build_ships\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="input_btn" type="submit">
                                    </div>
                                </div>
                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif (!$_smarty_tpl->tpl_vars[\'Element\']->value[\'techacc\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                <div class="btn_build_border">
                                    <span class="btn_build red"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'bd_notres\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>
                                </div>
                                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            </div>
                        </div>              
                    </div>   
                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 			
                    <div class="clear"></div>				
                </div>
            </div>    
        </form>
    </div>
    <?php echo '<script'; ?>
 type="text/javascript">
    data			= <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'BuildList\']->value);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
;
    bd_operating	= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['bd_operating'];?>
';
    bd_available	= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['bd_available'];?>
';

	DatatList		= {
	<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'elementList\']->value, \'Element\', false, \'ID\');
$_smarty_tpl->tpl_vars[\'Element\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'ID\']->value => $_smarty_tpl->tpl_vars[\'Element\']->value) {
$_smarty_tpl->tpl_vars[\'Element\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

	
	"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
":{"id":"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
","available":"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'available\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
","costResources":{<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'Element\']->value[\'costResources\'], \'RessAmount\', false, \'RessID\');
$_smarty_tpl->tpl_vars[\'RessAmount\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'RessID\']->value => $_smarty_tpl->tpl_vars[\'RessAmount\']->value) {
$_smarty_tpl->tpl_vars[\'RessAmount\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'RessID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
":<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'RessAmount\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
,<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
},"elementTime":<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'elementTime\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
,"buyable":true,"maxBuildable":"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'maxBuildable\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
","AlreadyBuild":false,"AlreadyBuildOne":false},
	
	<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>


	};
	
	MaxCount		= '<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'max\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
';
	bd_operating	= '(busy)';
   	LNGning			= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['bd_remaining'];?>
';
   	LNGtech901		= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][901];?>
';
   	LNGtech902		= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][902];?>
';
   	LNGtech903		= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][903];?>
';
   	LNGtech911		= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][911];?>
';
   	LNGtech921		= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][921];?>
';
   	short_day 		= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['short_day'];?>
';
   	short_hour 		= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['short_hour'];?>
';
   	short_minute 	= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['short_minute'];?>
';
   	short_second 	= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['short_second'];?>
';
    <?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="scripts/game/shipyard.js"><?php echo '</script'; ?>
>
    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'BuildList\']->value)) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

    <?php echo '<script'; ?>
 src="scripts/base/bcmath.js"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 type="text/javascript">
    $(function() {
        ShipyardInit();
    });
    <?php echo '</script'; ?>
>
    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

</div>
<?php
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.shipyard.default.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
function content_6ab87b8e785034_15038176 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '2413373286ab87b8e654f35_38151844';
?>
<!DOCTYPE html>

<!--[if lt IE 7 ]> <html lang="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="no-js"> <!--<![endif]-->
<head>
    <!--title-->
	<title><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_6650384726ab87b8e7869f6_24243245', "title");
?>
</title>
	<meta name="generator" content="NovaRush">
	<meta name="keywords" content="NovaRush">
	<meta name="description" content="NovaRush Browsergame by LORDA1998">
    <!--favicon-->
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
    <!--goto refresh-->
	<?php if (!empty($_smarty_tpl->tpl_vars['goto']->value)) {?>
	<meta http-equiv="refresh" content="<?php echo $_smarty_tpl->tpl_vars['gotoinsec']->value;?>
;URL=<?php echo $_smarty_tpl->tpl_vars['goto']->value;?>
">
	<?php }?>
    <!--content-type-->
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <!--keypress-->
    <?php echo '<script'; ?>
 type="text/javascript" src="./scripts/base/keypress.js"><?php echo '</script'; ?>
>
    <!--jquery-->
    <link rel="stylesheet" type="text/css" href="./styles/resource/css/base/jquery_1.8.18.css">
    <?php echo '<script'; ?>
 type="text/javascript" src="./scripts/base/jquery.js"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 type="text/javascript" src="./scripts/base/jquery.ui.js"><?php echo '</script'; ?>
>
	<?php echo '<script'; ?>
 type="text/javascript" src="./scripts/base/jquery.cookie.js"><?php echo '</script'; ?>
>
    <!--fancybox-->
    <link rel="stylesheet" type="text/css" href="./styles/resource/css/base/jquery.fancybox_3.5.7.css">
    <?php echo '<script'; ?>
 type="text/javascript" src="./scripts/base/jquery.fancybox.js"><?php echo '</script'; ?>
>    
    <!--style-->
    <link rel="stylesheet" type="text/css" href="./styles/resource/css/ingame/main.css">
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
css/navigation.css">
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
css/general.css">
    
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
formate.css">
    <!--game script-->
    <?php echo '<script'; ?>
 type="text/javascript">
        var ServerTimezoneOffset = <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Offset\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
;
        var serverTime 	= new Date(<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[0];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
, <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[1]-1;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
, <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[2];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
, <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[3];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
, <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[4];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
, <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[5];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
);
        var startTime	= serverTime.getTime();
        var localTime 	= serverTime;
        var localTS 	= startTime;
        var Gamename	= document.title;
        var Ready		= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['ready'];?>
";
        var Skin		= "<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
";
        var Lang		= "<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
";
        var head_info	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['fcm_info'];?>
";
        var auth		= <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo (($tmp = @$_smarty_tpl->tpl_vars[\'authlevel\']->value)===null||$tmp===\'\' ? \'0\' : $tmp);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
;
        var days 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['week_day']))===null||$tmp==='' ? '[]' : $tmp);?>
 
        var months 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['months']))===null||$tmp==='' ? '[]' : $tmp);?>
 ;
        var tdformat	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['js_tdformat'];?>
";
        var queryString	= "<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo strtr($_smarty_tpl->tpl_vars[\'queryString\']->value, array("\\\\" => "\\\\\\\\", "\'" => "\\\\\'", "\\"" => "\\\\\\"", "\\r" => "\\\\r", "\\n" => "\\\\n", "</" => "<\\/" ));?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
";
        var isPlayerCardActive	= "<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'isPlayerCardActive\']->value);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
";

        setInterval(function() {
            serverTime.setSeconds(serverTime.getSeconds()+1);
        }, 1000);
	<?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 type="text/javascript" src="./scripts/base/tooltip.js"><?php echo '</script'; ?>
>
	<?php echo '<script'; ?>
 type="text/javascript" src="./scripts/game/base.js"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 type="text/javascript" src="./scripts/game/game.class.js"><?php echo '</script'; ?>
>
    <!--script-->
	<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'scripts\']->value, \'scriptname\');
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'scriptname\']->value) {
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

	<?php echo '<script'; ?>
 type="text/javascript" src="./scripts/game/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'scriptname\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.js"><?php echo '</script'; ?>
>
	<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

	<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_3849536746ab87b8e793d35_16784946', "script");
?>

	<?php echo '<script'; ?>
 type="text/javascript">
	$(function() {
		<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'execscript\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

	});
	<?php echo '</script'; ?>
>
</head>
<body id="<?php echo (($tmp = @htmlspecialchars($_GET['page']))===null||$tmp==='' ? 'overview' : $tmp);?>
" class="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'bodyclass\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" 
    style="
        background: #0B0B0F;
        background: url(<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'background\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
) no-repeat fixed center center #0d0d0d;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        o-background-size: cover;
        background-size: cover;
">
<div id="tooltip" class="tip"></div><?php
}
/* {block "title"} */
class Block_6650384726ab87b8e7869f6_24243245 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_6650384726ab87b8e7869f6_24243245',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['2413373286ab87b8e654f35_38151844'] = true;
?>
 - <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'uni_name\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 - <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'game_name\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
}
}
/* {/block "title"} */
/* {block "script"} */
class Block_3849536746ab87b8e793d35_16784946 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_3849536746ab87b8e793d35_16784946',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.navigation.tpl" =============================*/
function content_6ab87b8e7988c9_32405152 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->compiled->nocache_hash = '2413373286ab87b8e654f35_38151844';
echo '<script'; ?>
 type="text/javascript">
    	setInterval(function() { AJAX() }, 5000);
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 type="text/javascript" src="./scripts/game/json.js"><?php echo '</script'; ?>
>
<div id="left_side">  
	<div id="side_menu_up">
        <div class="img"></div>
    </div>
    <div id="left_menu">
        <div id="touchscreenleft_menu">   
            <div id="indicators">
                <div id="attack" class="indicator <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'ataks\']->value > 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="espionage" class="indicator <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'spio\']->value > 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="destruction" class="indicator <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'unic\']->value > 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="rocket" class="indicator <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'rakets\']->value > 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                    <div class="icoi"></div>
                </div>
            </div>
                        <a class="big_btn btn_menu btn_menu_big"> <div class="servertime oservertime"></div> </a>
            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'bonus_time\']->value < TIMESTAMP) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

            <a class="big_btn blue btn_menu btn_menu_big" href="game.php?page=bonus"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_bonus\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

            <a class="big_btn blue btn_menu btn_menu_big"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'bonus_time_rest\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

             
            <!-- ricerche  tecnologie-->
            <?php if (isModuleAvailable(@constant('MODULE_RESEARCH'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=research" id="munu_research"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_research'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=research"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/research.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- costruzioni risorse-->
            <?php if (isModuleAvailable(@constant('MODULE_BUILDING'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=buildings" id="munu_build"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_buildings'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_RESSOURCE_LIST'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=resources" id="munu_resources" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_resources'];?>
"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/resources.png" class="oimgaltro"></a>
            <?php }?>
            <!-- flotta hangar -->
            <?php if (isModuleAvailable(@constant('MODULE_SHIPYARD_FLEET'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=shipyard&amp;mode=fleet" id="munu_shipyard_fleet"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_shipshard'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=shipyard&amp;mode=fleet" id="munu_fleetable"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/hangar.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- difese -->
            <?php if (isModuleAvailable(@constant('MODULE_SHIPYARD_DEFENSIVE'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=shipyard&amp;mode=defense" id="munu_shipyard_defense"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_defenses'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=shipyard&amp;mode=defense"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/shield.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- Orbita -->
            <a class="nuovomenusinistra" href="game.php?page=fleetTable" id="munu_orbita"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_fleet'];?>
</a>
            <?php if (isModuleAvailable(@constant('MODULE_SIMULATOR'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=battleSimulator" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_battlesim'];?>
"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/target.png" class="oimgaltro"></a>	
            <?php }?>            
            <!-- alleanza-->
            <?php if (isModuleAvailable(@constant('MODULE_ALLIANCE'))) {?>
			<a class="nuovomenusinistra" href="game.php?page=alliance" id="munu_alliance"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_alliance'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=alliance"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/alliance.png" class="imgovernuovo" id="ciaone"></a>	
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_MARKET'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=market"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_market'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=market"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/market.png" class="imgovernuovo"></a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_ARSENAL'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=arsenal"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_ars'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_CONTAINER'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=conteiner" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_container'];?>
"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/arsenal.png" class="oimgaltro"></a>	
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_OFFICIER'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=officier" id="munu_senat"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_officiers'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=officier"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/governatori.png" class="imgovernuovo" id="ciaone"></a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_MINERALS'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=minerals" id="munu_senat"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_minerals'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_DETAILS'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=details" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_details'];?>
"><img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/blackmarket.png" class="oimgaltro"></a>	
            <?php }?>
            <!-- ufficiali governatori -->
            <?php if (isModuleAvailable(@constant('MODULE_GALAXY'))) {?>
            <a class="galassiabott" href="game.php?page=galaxy" id="munu_galaxy"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_galaxy'];?>
</a>
            <?php }?>   
     		<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'authlevel\']->value > 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

            <a  href="admin.php" class="big_btn green btn_menu btn_menu_big"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_administration\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

            <div class="clear"></div>                
    </div>
    </div><!--/left_menu-->
    <div class="menubassoleft">
        <div id="top_nav_parte_sotto"> 
            <?php if (isModuleAvailable(@constant('MODULE_TECHTREE'))) {?>
            <a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_technology'];?>
" href="game.php?page=techtree"><span class="techtree"></span></a>
            <div class="separator_nav"></div>
            <?php }?> 
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_rules'];?>
" href="index.php?page=rules" target="_blank"><span class="rules"></span></a>
            <div class="separator_nav"></div>
            <?php if (isModuleAvailable(@constant('MODULE_BUDDYLIST'))) {?>
            <a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_buddylist'];?>
" href="game.php?page=buddyList"><span class="frend"></span></a>
            <div class="separator_nav"></div>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_RECORDS'))) {?>
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_records'];?>
" href="game.php?page=records"><span class="record"></span></a>
            <div class="separator_nav"></div>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_SUPPORT'))) {?>
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_support'];?>
" href="game.php?page=ticket"><span class="soopart"></span></a>				
			<div class="separator_nav"></div>
            <?php }?>
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_options'];?>
" href="game.php?page=settings"><span class="seting"></span></a>  
			<div class="separator_nav"></div>		
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_logout'];?>
" href="game.php?page=logout"> <span class="exit"></span></a>				  
        </div>
        <div id="side_menu_bottom">
            <div class="img"></div>
        </div>
    </div> 
</div>
<div style="height:0; overflow:hidden;" loop="false;" id="music">
    <audio id="beepataks" preload="auto">
        <source src="./sound/sirena.mp3"></source>
        <source src="./sound/sirena.ogg"></source>
    </audio>
    <audio id="msgaudio" preload="auto">
        <source src="./sound/msg.mp3"></source>
        <source src="./sound/msg.ogg"></source>
    </audio>
    <?php echo '<script'; ?>
 type="text/javascript">
		var ataks = "<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ataks\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
";
		var spio = "<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'spio\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
";
        var unic = "<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'unic\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
";
		var rakets = "<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'rakets\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
";
		var msg = <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'new_message\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
;
		document.getElementById('msgaudio').volume=<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'msgvolume\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
;
		document.getElementById('beepataks').volume=<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'volume\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
;
	<?php echo '</script'; ?>
>
</div><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.navigation.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.topnav.tpl" =============================*/
function content_6ab87b8e7b5cb2_28064072 (Smarty_Internal_Template $_smarty_tpl) {
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php $_smarty_tpl->_checkPlugins(array(0=>array(\'file\'=>\'C:\\\\Users\\\\Ortega\\\\Downloads\\\\OGAME\\\\ogame\\\\includes\\\\libs\\\\Smarty\\\\plugins\\\\function.html_options.php\',\'function\'=>\'smarty_function_html_options\',),));
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
$_smarty_tpl->compiled->nocache_hash = '2413373286ab87b8e654f35_38151844';
?>
<div id="header">
    <div id="top_nav" class="otopnav"> 
        <a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_overview'];?>
" href="game.php?page=overview">
            <img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/general/logo.png" class="game_logo">
        </a>
        <div style="display:none;">					
            <select id="lstPlaneta" name="lstPlaneta" onchange="document.location = $(this).val();">
                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo smarty_function_html_options(array(\'options\'=>$_smarty_tpl->tpl_vars[\'PlanetSelect\']->value,\'selected\'=>$_smarty_tpl->tpl_vars[\'current_pids\']->value),$_smarty_tpl);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

            </select>
        </div>
        <div class="mini_planet_navigation" style="margin: auto;left: 0;right: 0;width: 235px;background: none;top: 46px;position: absolute;">
            <span class="link_back" title="" onclick="eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex-1].value+'\'');"></span>
            <span class="link_next" title="" onclick="eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex+1].value+'\'');"></span>
        </div>
        <div id="planet_select" style="margin: auto;left: 0;right: 0;top:46px;">
            <div class="active_panet">
				<div class="name_palnet" style="padding-left: 1px;width: 96px;"><img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
planeten/planet2d/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetImage\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.png' style="float:left;height: 22px;padding-top:3px;margin-right: 5px;"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetName\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</div> 
                <span class="ico_build"></span>                            
				<div class="coordinates_palnet">[<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetGalaxy\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
:<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetSystem\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
:<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetPlanet\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
]</div>
				<div class="clear"></div>
			</div>
            <div id="list_palnet">
            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'PlanetListing\']->value, \'Element\', false, \'ID\');
$_smarty_tpl->tpl_vars[\'Element\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'ID\']->value => $_smarty_tpl->tpl_vars[\'Element\']->value) {
$_smarty_tpl->tpl_vars[\'Element\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
        
			<div class="separator_h"></div>                   
            <div class="palnet_row <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'ID\']->value) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
active_palnet_row<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
				<div class="fleet_indicators">
                    <img id="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
m1" <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalAttacks\'] == 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_attack.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_1\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" />                                    
                    <img id="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
m12" style="display:none;" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_grab.png" alt="" class="tooltip" data-tooltip-content="Планету захватывают" />
                    <img id="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
m6" <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalSpio\'] == 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_spio.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_2\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
m10" <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalRockets\'] == 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_rocket.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_3\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" />                 
                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'] != 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
  
                    <img id="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
m1" <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalAttackLuna\'] == 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_moon_attack.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_4\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
m6" <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalRocketsLuna\'] == 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_moon_spio.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_5\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" />       
                    <img id="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
m9" style="display:none;" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_destrued.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_6\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
m10" <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalSpioLuna\'] == 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_moon_rocket.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_7\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" />                         
					<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
                
                    <div class="clear"></div>
                </div>	   
                <span class="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'ID\']->value) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
active_urlpalnet<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
urlpalnet<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" url="cp=<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
					<img src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
planeten/planet2d/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'image\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.png' style="float:left;height: 22px;padding-top: 5px;">
                    <span class="name_palnet"  style="padding-top: 5px;padding-left: 5px;width: 70px;"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'name\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>
					<span class="ico_build">
                        <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            <img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_build.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                    <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'id\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.gif' width='35' height='35'></td>
                                    <td><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'id\']];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 (<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'level\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
)</td>
                                    </tr>
                                    <tr><td><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_time($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'timeleft\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 </td></tr>
                                </table>
                            "/>
						<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

						<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            <img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_ship.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                    <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'id\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.gif' width='35' height='35'></td>
                                    <td><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'id\']];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</td>
                                    </tr>
                                    <tr><td><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'level\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</td></tr>
                                </table>
                            "/> 
						<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

						<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

							<img src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/p_select_tech.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                <tr>
                                <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'id\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
.gif' width='35' height='35'></td>
                                <td><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'id\']];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 (<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'level\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
)</td>
                                </tr>
                                <tr><td><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_time($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'timeleft\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 </td></tr>
                                </table> 
                            "/>
						<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

					</span>  			
                    <span class="coordinates_palnet" style="width: 60px;">[<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'galaxy\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
:<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'system\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
:<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'planet\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
]</span>
                </span>
                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'] != 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
                             
                <div class="separator_v"></div>
                <span class="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
active_urlpalnet<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
urlpalnet<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" url="cp=<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                    <span class="moon_select <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\']) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
moon_active<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"></span>
                    <span class="ico_build"><br /></span>
                </span>
                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
                
            </div> 
            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
                       
            </div>
        </div><!--/planet_select-->			
		<img title="" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'foto\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="settingxterium" onclick="return Dialog.Playercard(<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'userID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
, '<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
');">
        <span class="usernameow" onclick="return Dialog.Playercard(<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'userID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
, '<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
');"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>
		<span class="usernamepos"></span>        
        <div id="res_nav">
            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'resourceTable\']->value, \'resouceData\', false, \'resourceID\');
$_smarty_tpl->tpl_vars[\'resouceData\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'resourceID\']->value => $_smarty_tpl->tpl_vars[\'resouceData\']->value) {
$_smarty_tpl->tpl_vars[\'resouceData\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 
                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']))) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php $_tmp_array = isset($_smarty_tpl->tpl_vars[\'resouceData\']) ? $_smarty_tpl->tpl_vars[\'resouceData\']->value : array();
if (!(is_array($_tmp_array) || $_tmp_array instanceof ArrayAccess)) {
settype($_tmp_array, \'array\');
}
$_tmp_array[\'current\'] = $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']+$_smarty_tpl->tpl_vars[\'resouceData\']->value[\'used\'];
$_smarty_tpl->_assignInScope(\'resouceData\', $_tmp_array ,true);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                    <div id="res_block_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="bloc_res tooltip" data-tooltip-content="<span class='colore<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span><div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'RE\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
%">
                        <div class="ico_res"></div>
                        <div class="stock_res">
                            <div class="stock_percentage stock_percentage_left" style="width:<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo abs($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']/2);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
%;<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] > -0.1) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
display:none;<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"></div>
                            <div class="stock_percentage stock_percentage_right" style="width:<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']/2;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
%;<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] < 0.1) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
display:none;<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"></div>
                            <div class="separator_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"></div>
                            <div class="stock_text"><span id="current_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" name="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'used\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</div>
                        </div>
                    </div>
                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'])) || !(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']))) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <div id="res_block_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="bloc_res tooltip" data-tooltip-content="<span class='colore<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span><div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'RE\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
">
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (isModuleAvailable(@constant(\'MODULE_FAIR\'))) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            <a href="game.php?page=fair"><div class="ico_res"></div></a>
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            <div class="ico_res"></div>
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            <div class="stock_res2">
                                <div class="stock_percentage" style="width:100%;"></div>
                                <div class="separator_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"></div>
                                <div class="stock_text"><span class='colore<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
' id="current_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" name="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></div>
                            </div>
                        </div>
                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                        <div id="res_block_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" class="bloc_res tooltip" 
                            data-tooltip-content="
                            <span class='colore<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>
                            <div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div>
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPS\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
: <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'information\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            <br/><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPD\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
: <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'informationd\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            <br/><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPW\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
: <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'informationz\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 
                            <div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'>
                            </div> <span style='color:#999'><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
/<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>">
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (isModuleAvailable(@constant(\'MODULE_TRADER\'))) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            <a href="game.php?page=trader"><div class="ico_res"></div></a>
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                            <div class="ico_res"></div>
                            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                                                        <div class="stock_res">
                                <div class="stock_percentage" style="width:<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
%;"></div>
                                <div class="stock_text">
                                    <span id="current_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" name="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>
                                    (<span class="pricent"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] <= 100) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } else { ?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
100<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span>%)
                                </div>
                            </div>	
                        </div>
                    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
       
        </div>
        <!--/res_nav-->            
    </div><!--/top_nav-->
    <div id="barrasottoover">
		<div id="top_nav_parte_left">
            <?php if (isModuleAvailable(@constant('MODULE_CONTROL'))) {?>
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_control'];?>
" href="game.php?page=control"><span class="imperia"></span></a>
            <div class="separator_nav"></div>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_STATISTICS'))) {?>
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_statistics'];?>
" href="game.php?page=statistics"><span class="stats"></span></a>
			<div class="separator_nav"></div>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_ACHIEVEMENTS'))) {?>
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_achievements'];?>
" href="game.php?page=achievements"><span class="achievv"></span></a>
			<div class="separator_nav"></div>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_BATTLEHALL'))) {?>
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_topkb'];?>
" href="game.php?page=battleHall"><span class="topbk"></span></a>
			<div class="separator_nav"></div>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_CHAT'))) {?>
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_chat'];?>
" href="game.php?page=chat"><span class="chat"></span></a>
            <div class="separator_nav"></div> 
            <?php }?>
            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'hasBoard\']->value)) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

			<a title="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_forums\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" href="game.php?page=board" target="_blank"><span class="forum"></span></a>
			<div class="separator_nav"></div>
            <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

            <?php if (isModuleAvailable(@constant('MODULE_MESSAGES'))) {?>
            <a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_messages'];?>
" href="game.php?page=messages" id="a_mesage">
                <span class="mesages"></span>
                <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'new_message\']->value > 0) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
<span class="new_email"><span id="newmesnum"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'new_message\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</span></span><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

            </a>                                       
            <div class="separator_nav"></div> 
            <?php }?>
        </div>    
        <?php if (isModuleAvailable(@constant('MODULE_STORE'))) {?>
        <div class="premiumbarra">
			<img class="premiumimgbarra" src="<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
img/iconav/ico-account-premium.png">
            <a href="game.php?page=store">  
                <span class="premiumtopbar">
                    <span class="premiumscrittabar"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_store'];?>
</span>
                </span>
            </a>
        </div>
        <?php }?>
	</div>
    <div id="top_menu_bottom">
        <div class="left"></div>
        <div class="mid"></div>
        <div class="right"></div>
    </div>
    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if (!$_smarty_tpl->tpl_vars[\'vmode\']->value) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

		<?php echo '<script'; ?>
 type="text/javascript">
		var viewShortlyNumber	= <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'shortlyNumber\']->value);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
;
		var vacation			= <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'vmode\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
;
        $(function() {
		<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'resourceTable\']->value, \'resourceData\', false, \'resourceID\');
$_smarty_tpl->tpl_vars[\'resourceData\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'resourceID\']->value => $_smarty_tpl->tpl_vars[\'resourceData\']->value) {
$_smarty_tpl->tpl_vars[\'resourceData\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

		<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ((isset($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'production\']))) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

            resourceTicker({
                available: <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'current\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
,
                limit: [0, <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'max\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
],
                production: <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'production\']);?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
,
                valueElem: "current_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceData\']->value[\'name\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
",
                valuePoursent: "bar_<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
"
            }, true);
		<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

		<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

        });
		<?php echo '</script'; ?>
>
        <?php echo '<script'; ?>
 src="scripts/game/topnav.js"><?php echo '</script'; ?>
>
    <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

    <?php echo '<script'; ?>
 type="text/javascript">
		$(document).ready(function(){
			var flag_planet_menu = false;
			$('#planet_select').click(function(){ 
				$(this).toggleClass('active');
				$('#list_palnet').stop(false,false).slideToggle(300);
				flag_planet_menu = true;
			});		
			if(flag_planet_menu)
			{					
				document.body.onclick = function (e) {
					e = e || event;
					target = e.target || e.srcElement;
					if (target.id == "planet_select") {
						return;
					} else {
						$('#list_palnet').hide();
						$('#planet_select').removeClass('active');
						flag_planet_menu = false;
					}
				}
			}
			$('.urlpalnet').click( function(){
				document.location = '?'+queryString+'&'+$(this).attr("url");
			});		
			
			var listener = new window.keypress.Listener();
			listener.simple_combo("shift left", function() {
				eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex-1].value+'\'');
				console.log("You pressed shift and left");
			});
			listener.simple_combo("shift right", function() {
				eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex+1].value+'\'');
				console.log("You pressed shift and right");
			});
		});
	<?php echo '</script'; ?>
>
</div>
<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'closed\']->value) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_closed\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'delete\']->value) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'delete\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'vacation\']->value) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tn_vacation_mode\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'vacation\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

</div><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.topnav.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.full.tpl" =============================*/
function content_6ab87b8e7839f8_80417462 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '2413373286ab87b8e654f35_38151844';
foreach (array('bodyclass'=>"full") as $ik => $iv) {
$_smarty_tpl->tpl_vars[$ik] =  new Smarty_Variable($iv);
}
$_smarty_tpl->_subTemplateRender("file:main.header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array('bodyclass'=>"full"), 0, false, '48e3dc7c81da9ec904a0f8602367542c854e3727', 'content_6ab87b8e785034_15038176');
echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php if ($_smarty_tpl->tpl_vars[\'hasAdminAccess\']->value) {?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

<div class="globalWarning">
<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_1\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
 <a id="drop-admin"><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_link\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
</a><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_2\'];?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

</div>
<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php }?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

<?php
$_smarty_tpl->_subTemplateRender("file:main.navigation.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, '0a851ce8eec566aca67772a206ca7211221f0f83', 'content_6ab87b8e7988c9_32405152');
$_smarty_tpl->_subTemplateRender("file:main.topnav.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, 'ff3b3b778c041f26c841a05fa5174228da36306d', 'content_6ab87b8e7b5cb2_28064072');
?>
<div id="content"><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_15871635176ab87b8e80f5c6_79554584', "content");
?>
</div>
<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'cronjobs\']->value, \'cronjob\');
$_smarty_tpl->tpl_vars[\'cronjob\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'cronjob\']->value) {
$_smarty_tpl->tpl_vars[\'cronjob\']->do_else = false;
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
<img src="cronjob.php?cronjobID=<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php echo $_smarty_tpl->tpl_vars[\'cronjob\']->value;?>
/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>
" alt=""><?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';?>

<?php echo '/*%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/<?php $_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:2413373286ab87b8e654f35_38151844%%*/';
}
/* {block "content"} */
class Block_15871635176ab87b8e80f5c6_79554584 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_15871635176ab87b8e80f5c6_79554584',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.full.tpl" =============================*/
}
