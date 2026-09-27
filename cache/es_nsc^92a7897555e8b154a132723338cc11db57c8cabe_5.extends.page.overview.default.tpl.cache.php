<?php
/* Smarty version 3.1.36, created on 2026-09-27 03:09:06
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.overview.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab86cb2bcbcc4_97043305',
  'has_nocache_code' => true,
  'file_dependency' => 
  array (
    '92a7897555e8b154a132723338cc11db57c8cabe' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.overview.default.tpl',
      1 => 1642351636,
      2 => 'extends',
    ),
    '07db28706ff6484065f750ab8ee4cddd4e87b752' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.overview.default.tpl',
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
    'file:page.overview.default.tpl' => 1,
    'file:layout.full.tpl' => 1,
    'file:main.header.tpl' => 1,
    'file:main.navigation.tpl' => 1,
    'file:main.topnav.tpl' => 1,
    'file:main.footer.tpl' => 1,
  ),
),false)) {
function content_6ab86cb2bcbcc4_97043305 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
$_smarty_tpl->compiled->nocache_hash = '6365314326ab86cb2ac9dd4_41379934';
$_smarty_tpl->_subTemplateRender('file:page.overview.default.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, '07db28706ff6484065f750ab8ee4cddd4e87b752', 'content_6ab86cb2aed247_34356022');
$_smarty_tpl->inheritance->endChild($_smarty_tpl);
$_smarty_tpl->_subTemplateRender('file:layout.full.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, 'd63d6432ef23500a7b3d46d973fb3cfb4eab4e48', 'content_6ab86cb2b2c378_34308917');
}
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.overview.default.tpl" =============================*/
function content_6ab86cb2aed247_34356022 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '6365314326ab86cb2ac9dd4_41379934';
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_14277091736ab86cb2af2809_35447036', "title");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_6255030736ab86cb2af7cf5_60234110', "script");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_15777515706ab86cb2af8624_07085643', "content");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_20863604416ab86cb2b2b1d4_45907936', "script");
}
/* {block "title"} */
class Block_14277091736ab86cb2af2809_35447036 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_14277091736ab86cb2af2809_35447036',
  ),
);
public $prepend = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['LNG']->value['lm_overview'];
}
}
/* {/block "title"} */
/* {block "script"} */
class Block_6255030736ab86cb2af7cf5_60234110 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_6255030736ab86cb2af7cf5_60234110',
  ),
);
public $append = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "script"} */
/* {block "content"} */
class Block_15777515706ab86cb2af8624_07085643 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_15777515706ab86cb2af8624_07085643',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['6365314326ab86cb2ac9dd4_41379934'] = true;
?>

<link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
css/overview.css">
<div id="page">
    <div id="content">
        <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'fleets\']->value)) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

        <div id="ally_content" class="conteiner conteinership">
            <div class="fleettab10"></div>   
            <div class="gray_flettab">
                <div class="transparent">
                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'fl_fleets\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'activeFleetSlots\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 / <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'maxFleetSlots\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                    <div class="transparent" style="text-align:right; float:right;color: #b1b1b1;font-size: 13px;">
                        <span style="color: #8b99b0;font-weight: bold;"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'activeExpedition\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 / <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'maxExpedition\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'fl_expeditions\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span>
                    </div> 
                </div>
            </div> 
            <div class="fleet_log">
                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'fleets\']->value, \'fleet\', false, \'index\');
$_smarty_tpl->tpl_vars[\'fleet\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'index\']->value => $_smarty_tpl->tpl_vars[\'fleet\']->value) {
$_smarty_tpl->tpl_vars[\'fleet\']->do_else = false;
?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                <div class="fleet_time">
                    <div id="fleettime_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'index\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" class="fleets" data-fleet-end-time="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'fleet\']->value[\'returntime\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" data-fleet-time="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'fleet\']->value[\'resttime\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php ob_start();
echo $_smarty_tpl->tpl_vars[\'fleet\']->value[\'resttime\'];
$_prefixVariable1 = ob_get_clean();
echo pretty_fly_time($_prefixVariable1);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</div>
                    <div class="tooltip fleet_static_time" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'fleet\']->value[\'resttime1\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'fleet\']->value[\'resttime1\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</div>
                </div>
                <div class="fleet_text">
                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'fleet\']->value[\'text\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                    <div class="clear"></div>
                </div>     
                <div class="separator"></div>
                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
       
            </div>
        </div>
        <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

        <div id="ally_content" class="conteiner">
            <div class="gray_flettab">
                <div id="online_user">
                <?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_online_users'];?>
 <span><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'UsersOnline\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span>
                </div>
                <div id="gm_linck">
                    <a title="" href="game.php?page=ticket" class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_ticket_tooltip'];?>
"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_ticket'];?>
</a>  
                </div>
            </div> 
            <div class="row" style="padding: 7px">
                <div class="col-8">
                    <div class="card mr-1 background-border-black-blue shadow"> 
                        <div id="big_panet" style="background: url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/title/control_room.png) no-repeat, url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
planeten/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetimage\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.jpg) top center no-repeat; background-size:cover;">
                            <div class="palnet_pianeta_titoloa palnet_pianeta_titolo">    
                                <a href="game.php?page=planet">
                                    <span class="planetname"><a href="game.php?page=planet" title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_planet'];?>
"><span class="planetname"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetname\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span></a>
                                        <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/pencil-over.png" class="palnet_imgopa">
                                    </span>  
                                </a>
                            </div>
                            <div class="palnet_block_info palnet_luna_planeto">
                                <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
planeten/planet2d/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetimage\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.png" height="100" width="100">
                            </div>
                            <marquee behavior="alternate" direction="left" scrollamount="1" onmouseover="this.stop();" onmouseout="this.start();" style="height: 15px;width: 445px;position: absolute;bottom: 1px;font-size: 10px;left: 6px;color: #b2b2b2;text-shadow: 0px 1px 0px rgba(0,0,0,0.6);">Hello my friends!</marquee>
                            <div class="palnet_block_info palnet_big_info"> 
                                <div class="left_part">
                                    <a href="game.php?page=planet" style="color:#b2b2b2"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/diametr.png" class="overvieew6"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_diameter'];?>
</a>
                                </div>
                                <div class="right_part">
                                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet_diameter\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 <?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_distance_unit'];?>
 (<span title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_developed_fields'];?>
"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet_field_current\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span> / <span title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_max_developed_fields'];?>
"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet_field_max\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span> <?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_fields'];?>
)
                                </div>
                                <div class="left_part" style="top: 20px;">
                                    <a href="game.php?page=planet" style="color:#b2b2b2"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/temp.png" class="overvieew6"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_temperature'];?>
</a>
                                </div>
                                <div class="right_part" style="top: 20px;">
                                    <?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_aprox'];?>
 <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet_temp_min\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';
echo $_smarty_tpl->tpl_vars['LNG']->value['ov_temp_unit'];?>
 <?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_to'];?>
 <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet_temp_max\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';
echo $_smarty_tpl->tpl_vars['LNG']->value['ov_temp_unit'];?>

                                </div>
                                <div class="left_part" style="top: 40px;">
                                    <a href="game.php?page=planet" style="color:#b2b2b2"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/position.png" class="overvieew6"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_position'];?>
</a>
                                </div>
                                <div class="right_part" style="top: 40px;">
                                    <a href="game.php?page=galaxy&amp;galaxy=<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">[<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
:<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
:<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
]</a>
                                </div>
                                <div class="clear"></div>
                                <div class="left_part" style="top: 60px;">
                                    <a href="game.php?page=overview" style="color:#b2b2b2"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/stat.png" class="overvieew6"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_points'];?>
</a>
                                </div>
                                <div class="right_part" style="top: 60px;"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'rankInfo\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</div>
                            </div>	
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card background-border-black-gray shadow">       
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card background-border-black-blue">                     
                                    <div class="gray_latdes overvieew2" style="background: url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/content/control_roompi.png) no-repeat, url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/content/mondpi.jpg) no-repeat ; background-size:cover;">
                                    <?php if (isModuleAvailable(@constant('MODULE_CREATE_MOON'))) {?>
                                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'planet_type\']->value == 1) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
	
                                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Moon\']->value) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                        <div>
                                            <a href="game.php?page=overview&amp;cp=<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Moon\']->value[\'id\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
&amp;re=0"><span class="overvieew5"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Moon\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span></a>
                                            <a href="game.php?page=overview&amp;cp=<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Moon\']->value[\'id\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
&amp;re=0" class=" ">
                                                <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/content/moon.png" class="overvieew4 tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Moon\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" style="opacity:0.8">
                                            </a>
                                        </div>
                                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                        <div>
                                            <a href="game.php?page=createMoon"><span class="overvieew5"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_create_moon\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span></a>
                                            <a href="game.php?page=createMoon" class=" ">
                                                <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/content/moon.png" class="overvieew4 tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_create_moon\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" style="opacity:0.8">
                                            </a>
                                        </div>
                                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                    <?php }?>
                                    </div>
                                </div>
                            </div>    
                            <div class="col-md-6">
                                <div class="card background-border-black-blue">  
                                    <div class="gray_latdes overvieew2" style="background: url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/content/control_roompi.png) no-repeat, url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/content/control_acc.png) no-repeat ; background-size:cover;">
                                        <div>
                                            <a href="game.php?page=market"><span class="overvieew5"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_market'];?>
</span></a>
                                            <a href="game.php?page=market" class=" ">
                                                <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/content/market.png" class="overvieew4 tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_market'];?>
" style="opacity:0.8">
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card background-border-black-gray">
                                    <div class="ricerche">
                                        <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/ov_tech.png" class="overvieew27">
                                        <span class="overvieew9">
                                            <a href="game.php?page=research">
                                                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'tech\']) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                    <span class="timer" data-time="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'tech\'][\'timeleft\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">??:??:??</span>
                                                    - <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'tech\'][\'id\']];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                    <span class="level">(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'tech\'][\'level\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
)</span>
                                                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_free\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card background-border-black-gray">
                                    <div class="costruzioni">
                                        <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/ov_build.png" class="overvieew27">
                                        <span class="overvieew9">
                                            <a href="game.php?page=buildings">
                                                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'buildings\']) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                    <span class="timer" data-time="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'buildings\'][\'timeleft\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">??:??:??</span>
                                                    - <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'buildings\'][\'id\']];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                    <span class="level">(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'buildings\'][\'level\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
)</span>
                                                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_free\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card background-border-black-gray">
                                    <div class="flotte">
                                        <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/ov_fleet.png" class="overvieew27">
                                        <span class="overvieew9">
                                            <a href="game.php?page=shipyard">
                                                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'fleet\']) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                    <span class="timer" data-time="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'fleet\'][\'timeleft\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">??:??:??</span>
                                                    - <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'fleet\'][\'id\']];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                    <span class="level">(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'buildInfo\']->value[\'fleet\'][\'level\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
)</span>
                                                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_free\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card mt-1 background-border-black-blue shadow"> 
                        <div class="card-body">
                            <p class="card-title"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_panel_root'];?>
</p>
                            <div class="row">      
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        <?php if (isModuleAvailable(@constant('MODULE_RACE'))) {?>
                                        <a href="game.php?page=race"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'race\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.gif) no-repeat center; background-size: 50px;" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'race\']->value];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"></div></a>
                                        <?php }?>
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        <?php if (isModuleAvailable(@constant('MODULE_FORMGOVERNMENT'))) {?>
                                        <a href="game.php?page=formgovernment"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'formgovernment\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.png) no-repeat center; background-size: 50px;" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'formgovernment\']->value];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"></div></a>
                                        <?php }?>
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        <?php if (isModuleAvailable(@constant('MODULE_ETHICS'))) {?>
                                        <a href="game.php?page=ethics"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'ethics\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.png) no-repeat center; background-size: 50px;" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'ethics\']->value];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"></div></a>
                                        <?php }?>
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        <?php if (isModuleAvailable(@constant('MODULE_INFO_BONUS'))) {?>
                                        <a href="game.php?page=infobonus"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/title/infobonus.png) no-repeat center; background-size: 50px;" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_infobonus'];?>
"></div></a>
                                        <?php }?> 
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        <?php if (isModuleAvailable(@constant('MODULE_PARTY'))) {?>
                                        <a href="game.php?page=party"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/title/party.png) no-repeat center; background-size: 50px;" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_party'];?>
"></div></a>
                                        <?php }?> 
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        <?php if (isModuleAvailable(@constant('MODULE_IDEOLOGIES'))) {?>
                                        <a href="game.php?page=ideologies"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/title/ideologies.png) no-repeat center; background-size: 50px;" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_ideologies'];?>
"></div></a>
                                        <?php }?>  
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        <?php if (isModuleAvailable(@constant('MODULE_OFFICIER'))) {?>
                                        <a href="game.php?page=officier"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/title/officier.png) no-repeat center; background-size: 50px;" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_officiers'];?>
"></div></a>
                                        <?php }?>  
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'is_news\']->value) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                <div class="col-12">
                    <div class="card mt-1 background-border-black-blue shadow"> 
                        <div class="card-body">
                            <p class="card-title"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_news\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</p>
                            <p class="card-text overflow-auto" style="max-height: 50px;"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'news\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</p>
                        </div>
                    </div>
                </div>
                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

            </div>
        </div>
    </div>
</div>
<?php
}
}
/* {/block "content"} */
/* {block "script"} */
class Block_20863604416ab86cb2b2b1d4_45907936 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_20863604416ab86cb2b2b1d4_45907936',
  ),
);
public $append = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

    <?php echo '<script'; ?>
 src="scripts/game/overview.js"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="scripts/game/buildlist.js"><?php echo '</script'; ?>
>
<?php
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.overview.default.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
function content_6ab86cb2b2e4d5_48843143 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '6365314326ab86cb2ac9dd4_41379934';
?>
<!DOCTYPE html>

<!--[if lt IE 7 ]> <html lang="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" class="no-js"> <!--<![endif]-->
<head>
    <!--title-->
	<title><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_21060762546ab86cb2b304a4_97890262', "title");
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
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
css/navigation.css">
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
css/general.css">
    
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
formate.css">
    <!--game script-->
    <?php echo '<script'; ?>
 type="text/javascript">
        var ServerTimezoneOffset = <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Offset\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
;
        var serverTime 	= new Date(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[0];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
, <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[1]-1;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
, <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[2];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
, <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[3];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
, <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[4];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
, <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[5];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
);
        var startTime	= serverTime.getTime();
        var localTime 	= serverTime;
        var localTS 	= startTime;
        var Gamename	= document.title;
        var Ready		= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['ready'];?>
";
        var Skin		= "<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
";
        var Lang		= "<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
";
        var head_info	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['fcm_info'];?>
";
        var auth		= <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo (($tmp = @$_smarty_tpl->tpl_vars[\'authlevel\']->value)===null||$tmp===\'\' ? \'0\' : $tmp);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
;
        var days 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['week_day']))===null||$tmp==='' ? '[]' : $tmp);?>
 
        var months 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['months']))===null||$tmp==='' ? '[]' : $tmp);?>
 ;
        var tdformat	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['js_tdformat'];?>
";
        var queryString	= "<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo strtr($_smarty_tpl->tpl_vars[\'queryString\']->value, array("\\\\" => "\\\\\\\\", "\'" => "\\\\\'", "\\"" => "\\\\\\"", "\\r" => "\\\\r", "\\n" => "\\\\n", "</" => "<\\/" ));?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
";
        var isPlayerCardActive	= "<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'isPlayerCardActive\']->value);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
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
	<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'scripts\']->value, \'scriptname\');
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'scriptname\']->value) {
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = false;
?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

	<?php echo '<script'; ?>
 type="text/javascript" src="./scripts/game/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'scriptname\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.js"><?php echo '</script'; ?>
>
	<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

	<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_11335329446ab86cb2b463f7_02261009', "script");
?>

	<?php echo '<script'; ?>
 type="text/javascript">
	$(function() {
		<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'execscript\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

	});
	<?php echo '</script'; ?>
>
</head>
<body id="<?php echo (($tmp = @htmlspecialchars($_GET['page']))===null||$tmp==='' ? 'overview' : $tmp);?>
" class="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'bodyclass\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" 
    style="
        background: #0B0B0F;
        background: url(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'background\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
) no-repeat fixed center center #0d0d0d;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        o-background-size: cover;
        background-size: cover;
">
<div id="tooltip" class="tip"></div><?php
}
/* {block "title"} */
class Block_21060762546ab86cb2b304a4_97890262 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_21060762546ab86cb2b304a4_97890262',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['6365314326ab86cb2ac9dd4_41379934'] = true;
?>
 - <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'uni_name\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 - <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'game_name\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';
}
}
/* {/block "title"} */
/* {block "script"} */
class Block_11335329446ab86cb2b463f7_02261009 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_11335329446ab86cb2b463f7_02261009',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.navigation.tpl" =============================*/
function content_6ab86cb2b4c5b5_08535131 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->compiled->nocache_hash = '6365314326ab86cb2ac9dd4_41379934';
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
                <div id="attack" class="indicator <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'ataks\']->value > 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="espionage" class="indicator <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'spio\']->value > 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="destruction" class="indicator <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'unic\']->value > 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="rocket" class="indicator <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'rakets\']->value > 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">
                    <div class="icoi"></div>
                </div>
            </div>
                        <a class="big_btn btn_menu btn_menu_big"> <div class="servertime oservertime"></div> </a>
            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'bonus_time\']->value < TIMESTAMP) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

            <a class="big_btn blue btn_menu btn_menu_big" href="game.php?page=bonus"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_bonus\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

            <a class="big_btn blue btn_menu btn_menu_big"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'bonus_time_rest\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

             
            <!-- ricerche  tecnologie-->
            <?php if (isModuleAvailable(@constant('MODULE_RESEARCH'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=research" id="munu_research"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_research'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=research"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/research.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- costruzioni risorse-->
            <?php if (isModuleAvailable(@constant('MODULE_BUILDING'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=buildings" id="munu_build"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_buildings'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_RESSOURCE_LIST'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=resources" id="munu_resources" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_resources'];?>
"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/resources.png" class="oimgaltro"></a>
            <?php }?>
            <!-- flotta hangar -->
            <?php if (isModuleAvailable(@constant('MODULE_SHIPYARD_FLEET'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=shipyard&amp;mode=fleet" id="munu_shipyard_fleet"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_shipshard'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=shipyard&amp;mode=fleet" id="munu_fleetable"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/hangar.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- difese -->
            <?php if (isModuleAvailable(@constant('MODULE_SHIPYARD_DEFENSIVE'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=shipyard&amp;mode=defense" id="munu_shipyard_defense"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_defenses'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=shipyard&amp;mode=defense"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/shield.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- Orbita -->
            <a class="nuovomenusinistra" href="game.php?page=fleetTable" id="munu_orbita"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_fleet'];?>
</a>
            <?php if (isModuleAvailable(@constant('MODULE_SIMULATOR'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=battleSimulator" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_battlesim'];?>
"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/target.png" class="oimgaltro"></a>	
            <?php }?>            
            <!-- alleanza-->
            <?php if (isModuleAvailable(@constant('MODULE_ALLIANCE'))) {?>
			<a class="nuovomenusinistra" href="game.php?page=alliance" id="munu_alliance"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_alliance'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=alliance"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/alliance.png" class="imgovernuovo" id="ciaone"></a>	
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_MARKET'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=market"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_market'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=market"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/market.png" class="imgovernuovo"></a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_ARSENAL'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=arsenal"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_ars'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_CONTAINER'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=conteiner" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_container'];?>
"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/arsenal.png" class="oimgaltro"></a>	
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_OFFICIER'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=officier" id="munu_senat"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_officiers'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=officier"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/governatori.png" class="imgovernuovo" id="ciaone"></a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_MINERALS'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=minerals" id="munu_senat"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_minerals'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_DETAILS'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=details" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_details'];?>
"><img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/blackmarket.png" class="oimgaltro"></a>	
            <?php }?>
            <!-- ufficiali governatori -->
            <?php if (isModuleAvailable(@constant('MODULE_GALAXY'))) {?>
            <a class="galassiabott" href="game.php?page=galaxy" id="munu_galaxy"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_galaxy'];?>
</a>
            <?php }?>   
     		<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'authlevel\']->value > 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

            <a  href="admin.php" class="big_btn green btn_menu btn_menu_big"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_administration\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

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
		var ataks = "<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'ataks\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
";
		var spio = "<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'spio\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
";
        var unic = "<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'unic\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
";
		var rakets = "<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'rakets\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
";
		var msg = <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'new_message\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
;
		document.getElementById('msgaudio').volume=<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'msgvolume\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
;
		document.getElementById('beepataks').volume=<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'volume\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
;
	<?php echo '</script'; ?>
>
</div><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.navigation.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.topnav.tpl" =============================*/
function content_6ab86cb2b6d923_99991017 (Smarty_Internal_Template $_smarty_tpl) {
echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php $_smarty_tpl->_checkPlugins(array(0=>array(\'file\'=>\'C:\\\\Users\\\\Ortega\\\\Downloads\\\\OGAME\\\\ogame\\\\includes\\\\libs\\\\Smarty\\\\plugins\\\\function.html_options.php\',\'function\'=>\'smarty_function_html_options\',),));
?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';
$_smarty_tpl->compiled->nocache_hash = '6365314326ab86cb2ac9dd4_41379934';
?>
<div id="header">
    <div id="top_nav" class="otopnav"> 
        <a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_overview'];?>
" href="game.php?page=overview">
            <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/general/logo.png" class="game_logo">
        </a>
        <div style="display:none;">					
            <select id="lstPlaneta" name="lstPlaneta" onchange="document.location = $(this).val();">
                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo smarty_function_html_options(array(\'options\'=>$_smarty_tpl->tpl_vars[\'PlanetSelect\']->value,\'selected\'=>$_smarty_tpl->tpl_vars[\'current_pids\']->value),$_smarty_tpl);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

            </select>
        </div>
        <div class="mini_planet_navigation" style="margin: auto;left: 0;right: 0;width: 235px;background: none;top: 46px;position: absolute;">
            <span class="link_back" title="" onclick="eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex-1].value+'\'');"></span>
            <span class="link_next" title="" onclick="eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex+1].value+'\'');"></span>
        </div>
        <div id="planet_select" style="margin: auto;left: 0;right: 0;top:46px;">
            <div class="active_panet">
				<div class="name_palnet" style="padding-left: 1px;width: 96px;"><img src='<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
planeten/planet2d/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetImage\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.png' style="float:left;height: 22px;padding-top:3px;margin-right: 5px;"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetName\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</div> 
                <span class="ico_build"></span>                            
				<div class="coordinates_palnet">[<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetGalaxy\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
:<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetSystem\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
:<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetPlanet\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
]</div>
				<div class="clear"></div>
			</div>
            <div id="list_palnet">
            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'PlanetListing\']->value, \'Element\', false, \'ID\');
$_smarty_tpl->tpl_vars[\'Element\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'ID\']->value => $_smarty_tpl->tpl_vars[\'Element\']->value) {
$_smarty_tpl->tpl_vars[\'Element\']->do_else = false;
?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
        
			<div class="separator_h"></div>                   
            <div class="palnet_row <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'ID\']->value) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
active_palnet_row<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">
				<div class="fleet_indicators">
                    <img id="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
m1" <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalAttacks\'] == 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_attack.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_1\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" />                                    
                    <img id="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
m12" style="display:none;" src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_grab.png" alt="" class="tooltip" data-tooltip-content="Планету захватывают" />
                    <img id="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
m6" <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalSpio\'] == 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_spio.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_2\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
m10" <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalRockets\'] == 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_rocket.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_3\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" />                 
                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'] != 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
  
                    <img id="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
m1" <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalAttackLuna\'] == 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_moon_attack.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_4\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
m6" <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalRocketsLuna\'] == 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_moon_spio.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_5\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" />       
                    <img id="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
m9" style="display:none;" src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_destrued.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_6\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
m10" <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalSpioLuna\'] == 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_moon_rocket.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_7\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" />                         
					<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
                
                    <div class="clear"></div>
                </div>	   
                <span class="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'ID\']->value) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
active_urlpalnet<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
urlpalnet<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" url="cp=<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">
					<img src='<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
planeten/planet2d/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'image\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.png' style="float:left;height: 22px;padding-top: 5px;">
                    <span class="name_palnet"  style="padding-top: 5px;padding-left: 5px;width: 70px;"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span>
					<span class="ico_build">
                        <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\']) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                            <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_build.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                    <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'id\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.gif' width='35' height='35'></td>
                                    <td><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'id\']];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 (<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'level\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
)</td>
                                    </tr>
                                    <tr><td><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_time($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'timeleft\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 </td></tr>
                                </table>
                            "/>
						<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

						<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\']) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                            <img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_ship.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                    <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'id\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.gif' width='35' height='35'></td>
                                    <td><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'id\']];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</td>
                                    </tr>
                                    <tr><td><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'level\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</td></tr>
                                </table>
                            "/> 
						<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

						<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\']) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

							<img src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
img/iconav/p_select_tech.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                <tr>
                                <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'id\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
.gif' width='35' height='35'></td>
                                <td><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'id\']];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 (<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'level\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
)</td>
                                </tr>
                                <tr><td><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_time($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'timeleft\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 </td></tr>
                                </table> 
                            "/>
						<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

					</span>  			
                    <span class="coordinates_palnet" style="width: 60px;">[<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'galaxy\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
:<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'system\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
:<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'planet\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
]</span>
                </span>
                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'] != 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
                             
                <div class="separator_v"></div>
                <span class="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\']) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
active_urlpalnet<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
urlpalnet<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" url="cp=<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">
                    <span class="moon_select <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\']) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
moon_active<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"></span>
                    <span class="ico_build"><br /></span>
                </span>
                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
                
            </div> 
            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
                       
            </div>
        </div><!--/planet_select-->			
		<img title="" src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'foto\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" class="settingxterium" onclick="return Dialog.Playercard(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'userID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
, '<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
');">
        <span class="usernameow" onclick="return Dialog.Playercard(<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'userID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
, '<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
');"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span>
		<span class="usernamepos"></span>        
        <div id="res_nav">
            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'resourceTable\']->value, \'resouceData\', false, \'resourceID\');
$_smarty_tpl->tpl_vars[\'resouceData\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'resourceID\']->value => $_smarty_tpl->tpl_vars[\'resouceData\']->value) {
$_smarty_tpl->tpl_vars[\'resouceData\']->do_else = false;
?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 
                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if (!(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']))) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php $_tmp_array = isset($_smarty_tpl->tpl_vars[\'resouceData\']) ? $_smarty_tpl->tpl_vars[\'resouceData\']->value : array();
if (!(is_array($_tmp_array) || $_tmp_array instanceof ArrayAccess)) {
settype($_tmp_array, \'array\');
}
$_tmp_array[\'current\'] = $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']+$_smarty_tpl->tpl_vars[\'resouceData\']->value[\'used\'];
$_smarty_tpl->_assignInScope(\'resouceData\', $_tmp_array ,true);?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                    <div id="res_block_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" class="bloc_res tooltip" data-tooltip-content="<span class='colore<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
'><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span><div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'RE\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
%">
                        <div class="ico_res"></div>
                        <div class="stock_res">
                            <div class="stock_percentage stock_percentage_left" style="width:<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo abs($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']/2);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
%;<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] > -0.1) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
display:none;<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"></div>
                            <div class="stock_percentage stock_percentage_right" style="width:<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']/2;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
%;<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] < 0.1) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
display:none;<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"></div>
                            <div class="separator_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"></div>
                            <div class="stock_text"><span id="current_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" name="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'used\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span>/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</div>
                        </div>
                    </div>
                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if (!(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'])) || !(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']))) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                        <div id="res_block_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" class="bloc_res tooltip" data-tooltip-content="<span class='colore<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
'><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span><div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'RE\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
">
                            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if (isModuleAvailable(@constant(\'MODULE_FAIR\'))) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                            <a href="game.php?page=fair"><div class="ico_res"></div></a>
                            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                            <div class="ico_res"></div>
                            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                            <div class="stock_res2">
                                <div class="stock_percentage" style="width:100%;"></div>
                                <div class="separator_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"></div>
                                <div class="stock_text"><span class='colore<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
' id="current_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" name="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span></div>
                            </div>
                        </div>
                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                        <div id="res_block_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" class="bloc_res tooltip" 
                            data-tooltip-content="
                            <span class='colore<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
'><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span>
                            <div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div>
                            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPS\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
: <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'information\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                            <br/><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPD\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
: <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'informationd\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                            <br/><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPW\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
: <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'informationz\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 
                            <div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'>
                            </div> <span style='color:#999'><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
/<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span>">
                            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if (isModuleAvailable(@constant(\'MODULE_TRADER\'))) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                            <a href="game.php?page=trader"><div class="ico_res"></div></a>
                            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                            <div class="ico_res"></div>
                            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                                                        <div class="stock_res">
                                <div class="stock_percentage" style="width:<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
%;"></div>
                                <div class="stock_text">
                                    <span id="current_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" name="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span>
                                    (<span class="pricent"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] <= 100) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';
echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';
echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } else { ?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
100<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span>%)
                                </div>
                            </div>	
                        </div>
                    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
       
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
            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'hasBoard\']->value)) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

			<a title="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_forums\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" href="game.php?page=board" target="_blank"><span class="forum"></span></a>
			<div class="separator_nav"></div>
            <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

            <?php if (isModuleAvailable(@constant('MODULE_MESSAGES'))) {?>
            <a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_messages'];?>
" href="game.php?page=messages" id="a_mesage">
                <span class="mesages"></span>
                <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'new_message\']->value > 0) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
<span class="new_email"><span id="newmesnum"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'new_message\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</span></span><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

            </a>                                       
            <div class="separator_nav"></div> 
            <?php }?>
        </div>    
        <?php if (isModuleAvailable(@constant('MODULE_STORE'))) {?>
        <div class="premiumbarra">
			<img class="premiumimgbarra" src="<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
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
    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if (!$_smarty_tpl->tpl_vars[\'vmode\']->value) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

		<?php echo '<script'; ?>
 type="text/javascript">
		var viewShortlyNumber	= <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'shortlyNumber\']->value);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
;
		var vacation			= <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'vmode\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
;
        $(function() {
		<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'resourceTable\']->value, \'resourceData\', false, \'resourceID\');
$_smarty_tpl->tpl_vars[\'resourceData\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'resourceID\']->value => $_smarty_tpl->tpl_vars[\'resourceData\']->value) {
$_smarty_tpl->tpl_vars[\'resourceData\']->do_else = false;
?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

		<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ((isset($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'production\']))) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

            resourceTicker({
                available: <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'current\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
,
                limit: [0, <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'max\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
],
                production: <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'production\']);?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
,
                valueElem: "current_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceData\']->value[\'name\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
",
                valuePoursent: "bar_<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
"
            }, true);
		<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

		<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

        });
		<?php echo '</script'; ?>
>
        <?php echo '<script'; ?>
 src="scripts/game/topnav.js"><?php echo '</script'; ?>
>
    <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

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
<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'closed\']->value) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_closed\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'delete\']->value) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'delete\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'vacation\']->value) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tn_vacation_mode\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 <?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'vacation\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

</div><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.topnav.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.full.tpl" =============================*/
function content_6ab86cb2b2c378_34308917 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '6365314326ab86cb2ac9dd4_41379934';
foreach (array('bodyclass'=>"full") as $ik => $iv) {
$_smarty_tpl->tpl_vars[$ik] =  new Smarty_Variable($iv);
}
$_smarty_tpl->_subTemplateRender("file:main.header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array('bodyclass'=>"full"), 0, false, '48e3dc7c81da9ec904a0f8602367542c854e3727', 'content_6ab86cb2b2e4d5_48843143');
echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php if ($_smarty_tpl->tpl_vars[\'hasAdminAccess\']->value) {?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

<div class="globalWarning">
<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_1\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
 <a id="drop-admin"><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_link\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
</a><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_2\'];?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

</div>
<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php }?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

<?php
$_smarty_tpl->_subTemplateRender("file:main.navigation.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, '0a851ce8eec566aca67772a206ca7211221f0f83', 'content_6ab86cb2b4c5b5_08535131');
$_smarty_tpl->_subTemplateRender("file:main.topnav.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, 'ff3b3b778c041f26c841a05fa5174228da36306d', 'content_6ab86cb2b6d923_99991017');
?>
<div id="content"><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_5016076016ab86cb2bc87f6_92337429', "content");
?>
</div>
<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'cronjobs\']->value, \'cronjob\');
$_smarty_tpl->tpl_vars[\'cronjob\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'cronjob\']->value) {
$_smarty_tpl->tpl_vars[\'cronjob\']->do_else = false;
?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
<img src="cronjob.php?cronjobID=<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php echo $_smarty_tpl->tpl_vars[\'cronjob\']->value;?>
/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>
" alt=""><?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';?>

<?php echo '/*%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/<?php $_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:6365314326ab86cb2ac9dd4_41379934%%*/';
}
/* {block "content"} */
class Block_5016076016ab86cb2bc87f6_92337429 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_5016076016ab86cb2bc87f6_92337429',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.full.tpl" =============================*/
}
