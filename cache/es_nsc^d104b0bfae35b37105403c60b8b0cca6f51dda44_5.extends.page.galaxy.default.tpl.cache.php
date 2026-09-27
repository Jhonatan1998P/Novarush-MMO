<?php
/* Smarty version 3.1.36, created on 2026-09-27 03:30:02
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.galaxy.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab8719ad540b3_45348056',
  'has_nocache_code' => true,
  'file_dependency' => 
  array (
    'd104b0bfae35b37105403c60b8b0cca6f51dda44' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.galaxy.default.tpl',
      1 => 1642351636,
      2 => 'extends',
    ),
    '5848eb2b18dc253bad82d8af14bcb0fada7e9350' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.galaxy.default.tpl',
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
    'file:page.galaxy.default.tpl' => 1,
    'file:layout.full.tpl' => 1,
    'file:main.header.tpl' => 1,
    'file:main.navigation.tpl' => 1,
    'file:main.topnav.tpl' => 1,
    'file:main.footer.tpl' => 1,
  ),
),false)) {
function content_6ab8719ad540b3_45348056 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
$_smarty_tpl->compiled->nocache_hash = '11217832916ab8719ac24722_03919977';
$_smarty_tpl->_subTemplateRender('file:page.galaxy.default.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, '5848eb2b18dc253bad82d8af14bcb0fada7e9350', 'content_6ab8719ac49de5_70669642');
$_smarty_tpl->inheritance->endChild($_smarty_tpl);
$_smarty_tpl->_subTemplateRender('file:layout.full.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, 'd63d6432ef23500a7b3d46d973fb3cfb4eab4e48', 'content_6ab8719acd0152_75337351');
}
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.galaxy.default.tpl" =============================*/
function content_6ab8719ac49de5_70669642 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '11217832916ab8719ac24722_03919977';
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_4459630946ab8719ac501a1_51695875', "title");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_20688075026ab8719ac55ae5_60724324', "content");
}
/* {block "title"} */
class Block_4459630946ab8719ac501a1_51695875 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_4459630946ab8719ac501a1_51695875',
  ),
);
public $prepend = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['LNG']->value['lm_galaxy'];
}
}
/* {/block "title"} */
/* {block "content"} */
class Block_20688075026ab8719ac55ae5_60724324 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_20688075026ab8719ac55ae5_60724324',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php $_smarty_tpl->_checkPlugins(array(0=>array(\'file\'=>\'C:\\\\Users\\\\Ortega\\\\Downloads\\\\OGAME\\\\ogame\\\\includes\\\\libs\\\\Smarty\\\\plugins\\\\function.html_options.php\',\'function\'=>\'smarty_function_html_options\',),));
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
$_smarty_tpl->cached->hashes['11217832916ab8719ac24722_03919977'] = true;
?>
 
<link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
css/galactic.css">
<div id="page">
    <div id="content">
        <div id="galactic_block_1">
            <div style="position:absolute; width:0; height:0;">
                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'action\']->value == \'sendMissle\') {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                <form action="?page=fleetMissile" method="post">
                    <input type="hidden" name="galaxy" value="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
                    <input type="hidden" name="system" value="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
                    <input type="hidden" name="planet" value="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
                    <input type="hidden" name="type" value="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'type\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
                    <table class="table569" style='width: 715px !important;'>
                        <tr>
                            <th colspan="2"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_missil_launch\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 [<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
]</th>
                        </tr>
                        <tr>
                            <td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'missile_count\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <input type="text" name="SendMI" size="2" maxlength="7"></td>
                            <td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_objective\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
: 
                                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo smarty_function_html_options(array(\'name\'=>\'Target\',\'options\'=>$_smarty_tpl->tpl_vars[\'missileSelector\']->value),$_smarty_tpl);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            </td>
                        </tr>
                        <tr>
                            <th colspan="2" style="text-align:center;"><input type="submit" value="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_missil_launch_action\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></th>
                        </tr>
                    </table>
                </form>
                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                <div id="galactic_block_1">
                    <div id="galactic_header" style="position:relative;">
                        <form action="?page=galaxy" method="post" id="galaxy_form">
                            <input id="auto" value="dr" type="hidden">
                            <div class="gal_nazv" style="margin-left:15px;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_galaxy'];?>
:</div>
                            <div id="nav_1">
                                <input class="prev" name="galaxyeft" onclick="galaxy_submit('galaxyLeft')" type="button">
                                <input class="gal_p3" name="galaxy" value="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" size="5" maxlength="4" tabindex="2" type="text">
                                <input class="next" name="galaxyRight" onclick="galaxy_submit('galaxyRight')" type="button">
                            </div>
                            <div class="gal_p4"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_solar_system'];?>
:</div>
                            <div id="nav_2">
                                <input type="button" class="prev" name="systemLeft" onclick="galaxy_submit('systemLeft')">
                                <input class="gal_p3" type="text" name="system" value="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" size="5" maxlength="4" tabindex="2">
                                <input type="button" class="next" name="systemRight" onclick="galaxy_submit('systemRight')">
                            </div>
                            <div class="gal_sep"></div>
                            <input value="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_show'];?>
" id="galactic_show" type="submit">
                        </form>
                    </div>
                    <!--/galactic_header-->
                    <div id="galactic_status">
                        <div class="gal_p5"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_pos'];?>
</div>
                        <div class="status_sep"></div>
                        <div class="gal_p6"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_name_activity'];?>
</div>
                        <div class="status_sep"></div>
                        <div class="gal_p7"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_moon'];?>
</div>
                        <div class="status_sep"></div>
                        <div class="gal_p8"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_debris'];?>
</div>
                        <div class="status_sep"></div>
                        <div class="gal_p9"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_player_estate'];?>
</div>
                        <div class="status_sep"></div>
                        <div class="gal_p10"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_alliance'];?>
</div>
                        <div class="status_sep"></div>
                        <div class="gal_p11"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_actions'];?>
</div>
                    </div>
                    <!--/galactic_status-->
                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_smarty_tpl->tpl_vars[\'planet\'] = new Smarty_Variable(null, $_smarty_tpl->isRenderingCache);$_smarty_tpl->tpl_vars[\'planet\']->step = 1;$_smarty_tpl->tpl_vars[\'planet\']->total = (int) ceil(($_smarty_tpl->tpl_vars[\'planet\']->step > 0 ? $_smarty_tpl->tpl_vars[\'max_planets\']->value+1 - (1) : 1-($_smarty_tpl->tpl_vars[\'max_planets\']->value)+1)/abs($_smarty_tpl->tpl_vars[\'planet\']->step));
if ($_smarty_tpl->tpl_vars[\'planet\']->total > 0) {
for ($_smarty_tpl->tpl_vars[\'planet\']->value = 1, $_smarty_tpl->tpl_vars[\'planet\']->iteration = 1;$_smarty_tpl->tpl_vars[\'planet\']->iteration <= $_smarty_tpl->tpl_vars[\'planet\']->total;$_smarty_tpl->tpl_vars[\'planet\']->value += $_smarty_tpl->tpl_vars[\'planet\']->step, $_smarty_tpl->tpl_vars[\'planet\']->iteration++) {
$_smarty_tpl->tpl_vars[\'planet\']->first = $_smarty_tpl->tpl_vars[\'planet\']->iteration === 1;$_smarty_tpl->tpl_vars[\'planet\']->last = $_smarty_tpl->tpl_vars[\'planet\']->iteration === $_smarty_tpl->tpl_vars[\'planet\']->total;?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!(isset($_smarty_tpl->tpl_vars[\'GalaxyRows\']->value[$_smarty_tpl->tpl_vars[\'planet\']->value]))) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                    <div class="gal_user <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'planet\']->value != 1 && $_smarty_tpl->tpl_vars[\'planet\']->value != 3 && $_smarty_tpl->tpl_vars[\'planet\']->value != 5 && $_smarty_tpl->tpl_vars[\'planet\']->value != 7 && $_smarty_tpl->tpl_vars[\'planet\']->value != 9 && $_smarty_tpl->tpl_vars[\'planet\']->value != 11 && $_smarty_tpl->tpl_vars[\'planet\']->value != 13 && $_smarty_tpl->tpl_vars[\'planet\']->value != 15) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
second<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">   
                        <div class="gal_number">
                            <a href="game.php?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                        <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/position.png'></td>
                                        <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_sp\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</td>
                                    </tr>
                                    <tr>
                                        <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/diametr.png'></td>
                                        <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_fields\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetData\']->value[$_smarty_tpl->tpl_vars[\'planet\']->value][\'fields\']*$_smarty_tpl->tpl_vars[\'planet_factor\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span></td>
                                    </tr>
                                    <tr>
                                        <td class='reducefleet_img_ship'><img src='<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/temp.png'></td>
                                        <td class='reducefleet_name_ship'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_temperature\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <span class='reducefleet_count_ship'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetData\']->value[$_smarty_tpl->tpl_vars[\'planet\']->value][\'temp\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span></td>
                                    </tr>
                                </table>
                            ">
                            <span style="color:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetData\']->value[$_smarty_tpl->tpl_vars[\'planet\']->value][\'color\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span></a>
                        </div>
                        <div class="gal_player_cont" style="float:right">
                            <a class="ico_coloni ico_animation" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1&amp;target_mission=7"></a>    
                        </div>       
                    </div> 
                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'GalaxyRows\']->value[$_smarty_tpl->tpl_vars[\'planet\']->value] === false) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                    <div class="gal_user <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'planet\']->value != 1 && $_smarty_tpl->tpl_vars[\'planet\']->value != 3 && $_smarty_tpl->tpl_vars[\'planet\']->value != 5 && $_smarty_tpl->tpl_vars[\'planet\']->value != 7 && $_smarty_tpl->tpl_vars[\'planet\']->value != 9 && $_smarty_tpl->tpl_vars[\'planet\']->value != 11 && $_smarty_tpl->tpl_vars[\'planet\']->value != 13 && $_smarty_tpl->tpl_vars[\'planet\']->value != 15) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
second<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">   
                        <div class="gal_number">
                            <span style="color:#efbf13"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>
                        </div>
                        <div class="gal_planet_name"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_planet_destroyed\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</div>
                    </div><!--/gal_user-->     
                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } else { ?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                    <div class="gal_user <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'planet\']->value != 1 && $_smarty_tpl->tpl_vars[\'planet\']->value != 3 && $_smarty_tpl->tpl_vars[\'planet\']->value != 5 && $_smarty_tpl->tpl_vars[\'planet\']->value != 7 && $_smarty_tpl->tpl_vars[\'planet\']->value != 9 && $_smarty_tpl->tpl_vars[\'planet\']->value != 11 && $_smarty_tpl->tpl_vars[\'planet\']->value != 13 && $_smarty_tpl->tpl_vars[\'planet\']->value != 15) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
second<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" style="
                        background-image:url(<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
planeten/panel/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'GalaxyRows\']->value[$_smarty_tpl->tpl_vars[\'planet\']->value][\'planet\'][\'image\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
.png);
                        background-repeat: no-repeat;
                        background-position: top left;
                    ">   
                        <div class="gal_number">
                            <a href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1">
                            <span style="color:#0abd00"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span></a>
                        </div>
                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php $_smarty_tpl->_assignInScope(\'currentPlanet\', $_smarty_tpl->tpl_vars[\'GalaxyRows\']->value[$_smarty_tpl->tpl_vars[\'planet\']->value] ,true);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                        <span id="p_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'planet\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" class="tooltip gal_img_planet" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_planet\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'planet\'][\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 [<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
]">
                            <div class="gl-actions">
                                <table class="gl-actions-t">	
                                    <tbody>	
                                        <tr>
                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][1]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis1 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1&amp;target_mission=1" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_1\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][3]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis3 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1&amp;target_mission=3" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_3\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][4]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis4 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1&amp;target_mission=4" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_4\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][5]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis5 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1&amp;target_mission=5" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_5\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][10]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis10 tooltip" href="?page=galaxy&amp;action=sendMissle&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_10\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
            
                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][6]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis6 tooltip" href='javascript:doit(6,<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'planet\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
);' data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_6\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'class\'], \'class\');
$_smarty_tpl->tpl_vars[\'class\']->index = -1;
$_smarty_tpl->tpl_vars[\'class\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'class\']->value) {
$_smarty_tpl->tpl_vars[\'class\']->do_else = false;
$_smarty_tpl->tpl_vars[\'class\']->index++;
$_smarty_tpl->tpl_vars[\'class\']->first = !$_smarty_tpl->tpl_vars[\'class\']->index;
$__foreach_class_0_saved = $_smarty_tpl->tpl_vars[\'class\'];
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'class\']->value != \'vacation\' && $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'planet\'][\'phalanx\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <td><a class="gl-actions-i ri i-mis17 tooltip" href='javascript:OpenPopup(&quot;?page=phalanx&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1&quot;, &quot;&quot;, 640, 510);' data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_phalanx\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td>
                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_smarty_tpl->tpl_vars[\'class\'] = $__foreach_class_0_saved;
}
if ($_smarty_tpl->tpl_vars[\'class\']->do_else) {
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'planet\'][\'phalanx\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <td><a class="gl-actions-i ri i-mis17 tooltip" href='javascript:OpenPopup(&quot;?page=phalanx&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1&quot;, &quot;&quot;, 640, 510);' data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_phalanx\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td>
                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
planeten/planet2d/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'planet\'][\'image\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
.png" alt="">
                            		
                        </span>
                        <div class="gal_planet_name"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'planet\'][\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'lastActivity\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 </div>
                        <div class="gal_ico_moon ico_animation">
                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'moon\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <div class="ico_moon tooltip" data-tooltip-content="
                                <table class='tooltip_class_table'>
                                    <tr>
                                        <th colspan='2'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_moon\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 [<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
]</th>
                                    </tr>
                                    <tr>
                                        <td class='tooltip_class_table_text_left'><span><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_diameter\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span> <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'moon\'][\'diameter\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</td>
                                    </tr>
                                    <tr>
                                        <td class='tooltip_class_table_text_left'><span><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_temperature\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span> <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'moon\'][\'temp_min\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</td>
                                    </tr>
                                </table>
                            "></div>
                            <div class="gl-actions" style="top: 5px;z-index: 2;padding-left: 0px;left: 44px;">
                                <table class="gl-actions-t">	
                                    <tbody>
                                        <tr>
                                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][1]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis1 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=3&amp;target_mission=1" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_1\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][3]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis3 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=3&amp;target_mission=3" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_3\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][4]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis4 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=3&amp;target_mission=4" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_4\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][5]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis5 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=3&amp;target_mission=5" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_5\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
	            
                                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][6]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis6 tooltip" href='javascript:doit(6,<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'moon\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
);' data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_6\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'missions\'][9]) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<td><a class="gl-actions-i ri i-mis9 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=3&amp;target_mission=9" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_9\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a></td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
	            
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                        </div>
                        <div class="gal_ico_trash">
                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <div class="ico_trash_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\'][\'metal\']+$_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\'][\'crystal\'] > 225000000000) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
big<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\'][\'metal\']+$_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\'][\'crystal\'] < 225000000000 && $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\'][\'metal\']+$_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\'][\'crystal\'] > 7500000000) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
medium<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\'][\'metal\']+$_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\'][\'crystal\'] < 7500000000) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
small<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 ico_animation
                            tooltip_sticky" data-tooltip-content="
                                <table class='tooltip_class_table'>
                                    <tbody>
                                        <tr>
                                            <th><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_debris_field\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 [<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
]</th>
                                        </tr>
                                        <tr>
                                            <td class='tooltip_class_table_text_left'>
                                                <span><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][901];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>: <span class='tooltip_class_901'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\'][\'metal\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span><br>
                                                <span><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][902];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>: <span class='tooltip_class_902'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'debris\'][\'crystal\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>
                                            </td>
                                        </tr>            
                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='javascript:doit(8, <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'planet\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
);'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'type_mission_8\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</a>
                                            </td>
                                        </tr>            
                                    </tbody>
                                </table>">
                            </div>
                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                        </div>	
                        <div class="gal_player_name">
                            <a class="tooltip_sticky" data-tooltip-content="
                                <table class='tooltip_class_table'>
                                    <tbody>
                                        <tr>
                                            <th><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'playerrank\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</th>
                                        </tr>
                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!$_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'ownPlanet\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'isBuddy\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='#' onclick='return Dialog.Buddy(<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
)'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_buddy_request\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</a>
                                            </td>
                                        </tr>
                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='#' onclick='return Dialog.Playercard(<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
);'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_playercard\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</a>
                                            </td>
                                        </tr>
                                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='?page=statistics&amp;who=1&amp;start=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'rank\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_see_on_stats\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'class\'], \'class\');
$_smarty_tpl->tpl_vars[\'class\']->index = -1;
$_smarty_tpl->tpl_vars[\'class\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'class\']->value) {
$_smarty_tpl->tpl_vars[\'class\']->do_else = false;
$_smarty_tpl->tpl_vars[\'class\']->index++;
$_smarty_tpl->tpl_vars[\'class\']->first = !$_smarty_tpl->tpl_vars[\'class\']->index;
$__foreach_class_1_saved = $_smarty_tpl->tpl_vars[\'class\'];
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!$_smarty_tpl->tpl_vars[\'class\']->first) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<span class='galaxy-short-<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'class\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 galaxy-short'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'ShortStatus\']->value[$_smarty_tpl->tpl_vars[\'class\']->value];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_smarty_tpl->tpl_vars[\'class\'] = $__foreach_class_1_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</td>
                                        </tr>                                        
                                    </tbody>
                                </table>">
                                <span class="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'class\'], \'class\');
$_smarty_tpl->tpl_vars[\'class\']->index = -1;
$_smarty_tpl->tpl_vars[\'class\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'class\']->value) {
$_smarty_tpl->tpl_vars[\'class\']->do_else = false;
$_smarty_tpl->tpl_vars[\'class\']->index++;
$_smarty_tpl->tpl_vars[\'class\']->first = !$_smarty_tpl->tpl_vars[\'class\']->index;
$__foreach_class_2_saved = $_smarty_tpl->tpl_vars[\'class\'];
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!$_smarty_tpl->tpl_vars[\'class\']->first) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
galaxy-username-<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'class\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_smarty_tpl->tpl_vars[\'class\'] = $__foreach_class_2_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
  galaxy-username" <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!$_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'isBuddy\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
style='color:#eae45c'<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'username\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>
                                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'class\'])) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                <span>(</span><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'class\'], \'class\');
$_smarty_tpl->tpl_vars[\'class\']->index = -1;
$_smarty_tpl->tpl_vars[\'class\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'class\']->value) {
$_smarty_tpl->tpl_vars[\'class\']->do_else = false;
$_smarty_tpl->tpl_vars[\'class\']->index++;
$_smarty_tpl->tpl_vars[\'class\']->first = !$_smarty_tpl->tpl_vars[\'class\']->index;
$__foreach_class_3_saved = $_smarty_tpl->tpl_vars[\'class\'];
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!$_smarty_tpl->tpl_vars[\'class\']->first) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<span class="galaxy-short-<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'class\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 galaxy-short"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'ShortStatus\']->value[$_smarty_tpl->tpl_vars[\'class\']->value];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_smarty_tpl->tpl_vars[\'class\'] = $__foreach_class_3_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<span>)</span>
                                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            </a>
                        </div>
                        <div class="gal_ally_name">
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'alliance\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <a class="tooltip_sticky" data-tooltip-content="
                                <table class='tooltip_class_table'>
                                    <tbody>
                                        <tr>
                                            <th><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_alliance\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'alliance\'][\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'alliance\'][\'member\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='?page=alliance&amp;mode=info&amp;id=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'alliance\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_alliance_page\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='?page=statistics&amp;start=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'alliance\'][\'rank\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;who=2'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_see_on_stats\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>">
                                <span class="galaxy-alliance <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'alliance\'][\'class\'], \'class\');
$_smarty_tpl->tpl_vars[\'class\']->index = -1;
$_smarty_tpl->tpl_vars[\'class\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'class\']->value) {
$_smarty_tpl->tpl_vars[\'class\']->do_else = false;
$_smarty_tpl->tpl_vars[\'class\']->index++;
$_smarty_tpl->tpl_vars[\'class\']->first = !$_smarty_tpl->tpl_vars[\'class\']->index;
$__foreach_class_4_saved = $_smarty_tpl->tpl_vars[\'class\'];
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!$_smarty_tpl->tpl_vars[\'class\']->first) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'class\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_smarty_tpl->tpl_vars[\'class\'] = $__foreach_class_4_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'class\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'alliance\'][\'tag\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>
                            </a>
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
	
                        </div>
                        <div class="gal_player_cont" style="float:right">
                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'action\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'action\'][\'esp\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                <a class="ico_watch ico_animation" title="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_spy\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" href="javascript:doit(6,<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'planet\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
,<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo htmlspecialchars(json_encode($_smarty_tpl->tpl_vars[\'spyShips\']->value), ENT_QUOTES, \'UTF-8\', true);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
)"></a>	
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'action\'][\'message\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 
                                <a href="#" class="ico_post ico_animation" onclick="return Dialog.PM(<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
)" title="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'write_message\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a>				      
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'action\'][\'buddy\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
	
                                <a href="#" class="ico_friend ico_animation" onclick="return Dialog.Buddy(<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'currentPlanet\']->value[\'user\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
)" title="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'gl_buddy_request\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></a>				
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
	               
                        </div> 
                    </div>
                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }
}
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                    <div id="gal_block_1_footer">
                        <a id="dali" class="dali btn_galassia" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'max_planets\']->value+1;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1&amp;target_mission=15"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/g_expedition.png"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_out_space'];?>
</a>
                        <a id="expedition" class="expedition btn_galassia1" href="?page=fleetTable&amp;galaxy=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planet=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'max_planets\']->value+1;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
&amp;planettype=1&amp;target_mission=18"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/g_hostile.png"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_18'];?>
</a>
                    </div>
                </div>
                <div id="send_zond">
                    <table>
                        <tr style="display: none;" id="fleetstatusrow"></tr>
                    </table>
                </div>
                <div id="galactic_block_2">
                    <div id="block_status">
                        <div class="gal_stat_1">
                            <div class="gal_text_1"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetcount\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</div>
                            <div class="gal_text_2"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_populed_planets'];?>
</div>
                        </div>
                        <div class="gal_stat_2">
                            <div class="gal_text_1"><span id="grecyclers"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'grecyclers\']->value);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span></div>
                            <div class="gal_text_2"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_avaible_recyclers'];?>
</div>
                        </div>
                        <div class="gal_stat_3">
                            <div class="gal_text_1"><span id="slots"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'maxfleetcount\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'fleetmax\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</div>
                            <div class="gal_text_2"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_fleets'];?>
</div>
                        </div>
                        <div class="gal_stat_4">
                            <div class="gal_text_1"><span id="probes"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'spyprobes\']->value);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span></div>
                            <div class="gal_text_2"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_avaible_spyprobes'];?>
</div>
                        </div>
                        <div class="gal_stat_5">
                            <div class="gal_text_1"><span id="recyclers"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'grecyclers\']->value);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span> </div>
                            <div class="gal_text_2"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_avaible_grecyclers'];?>
</div>
                        </div>
                        <div class="gal_stat_6">
                            <div class="gal_text_1"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'currentmip\']->value);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</div>
                            <div class="gal_text_2"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_avaible_missiles'];?>
</div>
                        </div>
                    </div>   
                                    </div>
            </div>
        </div>
    </div>
	<?php echo '<script'; ?>
 type="text/javascript">
		status_ok		= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_ajax_status_ok'];?>
';
		status_fail		= '<?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_ajax_status_fail'];?>
';
		MaxFleetSetting = <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'settings_fleetactions\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
;
	<?php echo '</script'; ?>
>
</div>
<?php
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.galaxy.default.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
function content_6ab8719acd1c28_12211513 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '11217832916ab8719ac24722_03919977';
?>
<!DOCTYPE html>

<!--[if lt IE 7 ]> <html lang="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" class="no-js"> <!--<![endif]-->
<head>
    <!--title-->
	<title><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_2208733276ab8719acd3a27_41187922', "title");
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
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
css/navigation.css">
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
css/general.css">
    
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
formate.css">
    <!--game script-->
    <?php echo '<script'; ?>
 type="text/javascript">
        var ServerTimezoneOffset = <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Offset\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
;
        var serverTime 	= new Date(<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[0];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
, <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[1]-1;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
, <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[2];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
, <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[3];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
, <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[4];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
, <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[5];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
);
        var startTime	= serverTime.getTime();
        var localTime 	= serverTime;
        var localTS 	= startTime;
        var Gamename	= document.title;
        var Ready		= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['ready'];?>
";
        var Skin		= "<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
";
        var Lang		= "<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
";
        var head_info	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['fcm_info'];?>
";
        var auth		= <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo (($tmp = @$_smarty_tpl->tpl_vars[\'authlevel\']->value)===null||$tmp===\'\' ? \'0\' : $tmp);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
;
        var days 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['week_day']))===null||$tmp==='' ? '[]' : $tmp);?>
 
        var months 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['months']))===null||$tmp==='' ? '[]' : $tmp);?>
 ;
        var tdformat	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['js_tdformat'];?>
";
        var queryString	= "<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo strtr($_smarty_tpl->tpl_vars[\'queryString\']->value, array("\\\\" => "\\\\\\\\", "\'" => "\\\\\'", "\\"" => "\\\\\\"", "\\r" => "\\\\r", "\\n" => "\\\\n", "</" => "<\\/" ));?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
";
        var isPlayerCardActive	= "<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'isPlayerCardActive\']->value);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
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
	<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'scripts\']->value, \'scriptname\');
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'scriptname\']->value) {
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = false;
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

	<?php echo '<script'; ?>
 type="text/javascript" src="./scripts/game/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'scriptname\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
.js"><?php echo '</script'; ?>
>
	<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

	<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_6313282136ab8719acdfea4_76788354', "script");
?>

	<?php echo '<script'; ?>
 type="text/javascript">
	$(function() {
		<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'execscript\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

	});
	<?php echo '</script'; ?>
>
</head>
<body id="<?php echo (($tmp = @htmlspecialchars($_GET['page']))===null||$tmp==='' ? 'overview' : $tmp);?>
" class="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'bodyclass\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" 
    style="
        background: #0B0B0F;
        background: url(<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'background\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
) no-repeat fixed center center #0d0d0d;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        o-background-size: cover;
        background-size: cover;
">
<div id="tooltip" class="tip"></div><?php
}
/* {block "title"} */
class Block_2208733276ab8719acd3a27_41187922 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_2208733276ab8719acd3a27_41187922',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['11217832916ab8719ac24722_03919977'] = true;
?>
 - <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'uni_name\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 - <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'game_name\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
}
}
/* {/block "title"} */
/* {block "script"} */
class Block_6313282136ab8719acdfea4_76788354 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_6313282136ab8719acdfea4_76788354',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.navigation.tpl" =============================*/
function content_6ab8719ace6914_33803165 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->compiled->nocache_hash = '11217832916ab8719ac24722_03919977';
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
                <div id="attack" class="indicator <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'ataks\']->value > 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="espionage" class="indicator <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'spio\']->value > 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="destruction" class="indicator <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'unic\']->value > 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="rocket" class="indicator <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'rakets\']->value > 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
                    <div class="icoi"></div>
                </div>
            </div>
                        <a class="big_btn btn_menu btn_menu_big"> <div class="servertime oservertime"></div> </a>
            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'bonus_time\']->value < TIMESTAMP) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

            <a class="big_btn blue btn_menu btn_menu_big" href="game.php?page=bonus"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_bonus\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } else { ?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

            <a class="big_btn blue btn_menu btn_menu_big"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'bonus_time_rest\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

             
            <!-- ricerche  tecnologie-->
            <?php if (isModuleAvailable(@constant('MODULE_RESEARCH'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=research" id="munu_research"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_research'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=research"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/research.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- costruzioni risorse-->
            <?php if (isModuleAvailable(@constant('MODULE_BUILDING'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=buildings" id="munu_build"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_buildings'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_RESSOURCE_LIST'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=resources" id="munu_resources" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_resources'];?>
"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/resources.png" class="oimgaltro"></a>
            <?php }?>
            <!-- flotta hangar -->
            <?php if (isModuleAvailable(@constant('MODULE_SHIPYARD_FLEET'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=shipyard&amp;mode=fleet" id="munu_shipyard_fleet"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_shipshard'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=shipyard&amp;mode=fleet" id="munu_fleetable"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/hangar.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- difese -->
            <?php if (isModuleAvailable(@constant('MODULE_SHIPYARD_DEFENSIVE'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=shipyard&amp;mode=defense" id="munu_shipyard_defense"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_defenses'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=shipyard&amp;mode=defense"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/shield.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- Orbita -->
            <a class="nuovomenusinistra" href="game.php?page=fleetTable" id="munu_orbita"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_fleet'];?>
</a>
            <?php if (isModuleAvailable(@constant('MODULE_SIMULATOR'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=battleSimulator" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_battlesim'];?>
"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/target.png" class="oimgaltro"></a>	
            <?php }?>            
            <!-- alleanza-->
            <?php if (isModuleAvailable(@constant('MODULE_ALLIANCE'))) {?>
			<a class="nuovomenusinistra" href="game.php?page=alliance" id="munu_alliance"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_alliance'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=alliance"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/alliance.png" class="imgovernuovo" id="ciaone"></a>	
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_MARKET'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=market"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_market'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=market"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/market.png" class="imgovernuovo"></a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_ARSENAL'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=arsenal"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_ars'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_CONTAINER'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=conteiner" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_container'];?>
"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/arsenal.png" class="oimgaltro"></a>	
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_OFFICIER'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=officier" id="munu_senat"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_officiers'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=officier"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/governatori.png" class="imgovernuovo" id="ciaone"></a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_MINERALS'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=minerals" id="munu_senat"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_minerals'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_DETAILS'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=details" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_details'];?>
"><img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/blackmarket.png" class="oimgaltro"></a>	
            <?php }?>
            <!-- ufficiali governatori -->
            <?php if (isModuleAvailable(@constant('MODULE_GALAXY'))) {?>
            <a class="galassiabott" href="game.php?page=galaxy" id="munu_galaxy"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_galaxy'];?>
</a>
            <?php }?>   
     		<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'authlevel\']->value > 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

            <a  href="admin.php" class="big_btn green btn_menu btn_menu_big"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_administration\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

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
		var ataks = "<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'ataks\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
";
		var spio = "<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'spio\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
";
        var unic = "<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'unic\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
";
		var rakets = "<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'rakets\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
";
		var msg = <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'new_message\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
;
		document.getElementById('msgaudio').volume=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'msgvolume\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
;
		document.getElementById('beepataks').volume=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'volume\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
;
	<?php echo '</script'; ?>
>
</div><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.navigation.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.topnav.tpl" =============================*/
function content_6ab8719ad03924_76179131 (Smarty_Internal_Template $_smarty_tpl) {
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php $_smarty_tpl->_checkPlugins(array(0=>array(\'file\'=>\'C:\\\\Users\\\\Ortega\\\\Downloads\\\\OGAME\\\\ogame\\\\includes\\\\libs\\\\Smarty\\\\plugins\\\\function.html_options.php\',\'function\'=>\'smarty_function_html_options\',),));
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
$_smarty_tpl->compiled->nocache_hash = '11217832916ab8719ac24722_03919977';
?>
<div id="header">
    <div id="top_nav" class="otopnav"> 
        <a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_overview'];?>
" href="game.php?page=overview">
            <img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/general/logo.png" class="game_logo">
        </a>
        <div style="display:none;">					
            <select id="lstPlaneta" name="lstPlaneta" onchange="document.location = $(this).val();">
                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo smarty_function_html_options(array(\'options\'=>$_smarty_tpl->tpl_vars[\'PlanetSelect\']->value,\'selected\'=>$_smarty_tpl->tpl_vars[\'current_pids\']->value),$_smarty_tpl);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

            </select>
        </div>
        <div class="mini_planet_navigation" style="margin: auto;left: 0;right: 0;width: 235px;background: none;top: 46px;position: absolute;">
            <span class="link_back" title="" onclick="eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex-1].value+'\'');"></span>
            <span class="link_next" title="" onclick="eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex+1].value+'\'');"></span>
        </div>
        <div id="planet_select" style="margin: auto;left: 0;right: 0;top:46px;">
            <div class="active_panet">
				<div class="name_palnet" style="padding-left: 1px;width: 96px;"><img src='<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
planeten/planet2d/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetImage\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
.png' style="float:left;height: 22px;padding-top:3px;margin-right: 5px;"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetName\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</div> 
                <span class="ico_build"></span>                            
				<div class="coordinates_palnet">[<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetGalaxy\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetSystem\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetPlanet\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
]</div>
				<div class="clear"></div>
			</div>
            <div id="list_palnet">
            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'PlanetListing\']->value, \'Element\', false, \'ID\');
$_smarty_tpl->tpl_vars[\'Element\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'ID\']->value => $_smarty_tpl->tpl_vars[\'Element\']->value) {
$_smarty_tpl->tpl_vars[\'Element\']->do_else = false;
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
        
			<div class="separator_h"></div>                   
            <div class="palnet_row <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'ID\']->value) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
active_palnet_row<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
				<div class="fleet_indicators">
                    <img id="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
m1" <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalAttacks\'] == 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_attack.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_1\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" />                                    
                    <img id="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
m12" style="display:none;" src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_grab.png" alt="" class="tooltip" data-tooltip-content="Планету захватывают" />
                    <img id="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
m6" <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalSpio\'] == 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_spio.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_2\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
m10" <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalRockets\'] == 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_rocket.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_3\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" />                 
                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'] != 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
  
                    <img id="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
m1" <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalAttackLuna\'] == 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_moon_attack.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_4\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
m6" <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalRocketsLuna\'] == 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_moon_spio.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_5\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" />       
                    <img id="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
m9" style="display:none;" src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_destrued.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_6\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
m10" <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalSpioLuna\'] == 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_moon_rocket.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_7\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" />                         
					<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
                
                    <div class="clear"></div>
                </div>	   
                <span class="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'ID\']->value) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
active_urlpalnet<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } else { ?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
urlpalnet<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" url="cp=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
					<img src='<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
planeten/planet2d/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'image\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
.png' style="float:left;height: 22px;padding-top: 5px;">
                    <span class="name_palnet"  style="padding-top: 5px;padding-left: 5px;width: 70px;"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>
					<span class="ico_build">
                        <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_build.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                    <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
.gif' width='35' height='35'></td>
                                    <td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'id\']];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 (<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'level\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
)</td>
                                    </tr>
                                    <tr><td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_time($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'timeleft\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 </td></tr>
                                </table>
                            "/>
						<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

						<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_ship.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                    <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
.gif' width='35' height='35'></td>
                                    <td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'id\']];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</td>
                                    </tr>
                                    <tr><td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'level\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</td></tr>
                                </table>
                            "/> 
						<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

						<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

							<img src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
img/iconav/p_select_tech.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                <tr>
                                <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'id\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
.gif' width='35' height='35'></td>
                                <td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'id\']];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 (<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'level\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
)</td>
                                </tr>
                                <tr><td><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_time($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'timeleft\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 </td></tr>
                                </table> 
                            "/>
						<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

					</span>  			
                    <span class="coordinates_palnet" style="width: 60px;">[<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'galaxy\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'system\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'planet\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
]</span>
                </span>
                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'] != 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
                             
                <div class="separator_v"></div>
                <span class="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
active_urlpalnet<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } else { ?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
urlpalnet<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" url="cp=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
                    <span class="moon_select <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\']) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
moon_active<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></span>
                    <span class="ico_build"><br /></span>
                </span>
                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
                
            </div> 
            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
                       
            </div>
        </div><!--/planet_select-->			
		<img title="" src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'foto\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" class="settingxterium" onclick="return Dialog.Playercard(<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'userID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
, '<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
');">
        <span class="usernameow" onclick="return Dialog.Playercard(<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'userID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
, '<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
');"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>
		<span class="usernamepos"></span>        
        <div id="res_nav">
            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'resourceTable\']->value, \'resouceData\', false, \'resourceID\');
$_smarty_tpl->tpl_vars[\'resouceData\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'resourceID\']->value => $_smarty_tpl->tpl_vars[\'resouceData\']->value) {
$_smarty_tpl->tpl_vars[\'resouceData\']->do_else = false;
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 
                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']))) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php $_tmp_array = isset($_smarty_tpl->tpl_vars[\'resouceData\']) ? $_smarty_tpl->tpl_vars[\'resouceData\']->value : array();
if (!(is_array($_tmp_array) || $_tmp_array instanceof ArrayAccess)) {
settype($_tmp_array, \'array\');
}
$_tmp_array[\'current\'] = $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']+$_smarty_tpl->tpl_vars[\'resouceData\']->value[\'used\'];
$_smarty_tpl->_assignInScope(\'resouceData\', $_tmp_array ,true);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                    <div id="res_block_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" class="bloc_res tooltip" data-tooltip-content="<span class='colore<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span><div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'RE\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
%">
                        <div class="ico_res"></div>
                        <div class="stock_res">
                            <div class="stock_percentage stock_percentage_left" style="width:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo abs($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']/2);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
%;<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] > -0.1) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
display:none;<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></div>
                            <div class="stock_percentage stock_percentage_right" style="width:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']/2;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
%;<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] < 0.1) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
display:none;<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></div>
                            <div class="separator_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></div>
                            <div class="stock_text"><span id="current_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" name="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'used\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</div>
                        </div>
                    </div>
                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } else { ?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'])) || !(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']))) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                        <div id="res_block_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" class="bloc_res tooltip" data-tooltip-content="<span class='colore<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span><div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'RE\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
">
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (isModuleAvailable(@constant(\'MODULE_FAIR\'))) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <a href="game.php?page=fair"><div class="ico_res"></div></a>
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } else { ?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <div class="ico_res"></div>
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <div class="stock_res2">
                                <div class="stock_percentage" style="width:100%;"></div>
                                <div class="separator_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"></div>
                                <div class="stock_text"><span class='colore<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
' id="current_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" name="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span></div>
                            </div>
                        </div>
                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } else { ?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                        <div id="res_block_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" class="bloc_res tooltip" 
                            data-tooltip-content="
                            <span class='colore<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>
                            <div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div>
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPS\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
: <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'information\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <br/><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPD\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
: <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'informationd\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <br/><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPW\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
: <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'informationz\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 
                            <div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'>
                            </div> <span style='color:#999'><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
/<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>">
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (isModuleAvailable(@constant(\'MODULE_TRADER\'))) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <a href="game.php?page=trader"><div class="ico_res"></div></a>
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } else { ?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                            <div class="ico_res"></div>
                            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                                                        <div class="stock_res">
                                <div class="stock_percentage" style="width:<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
%;"></div>
                                <div class="stock_text">
                                    <span id="current_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" name="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>
                                    (<span class="pricent"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] <= 100) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } else { ?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
100<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span>%)
                                </div>
                            </div>	
                        </div>
                    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
       
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
            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'hasBoard\']->value)) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

			<a title="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_forums\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" href="game.php?page=board" target="_blank"><span class="forum"></span></a>
			<div class="separator_nav"></div>
            <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

            <?php if (isModuleAvailable(@constant('MODULE_MESSAGES'))) {?>
            <a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_messages'];?>
" href="game.php?page=messages" id="a_mesage">
                <span class="mesages"></span>
                <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'new_message\']->value > 0) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<span class="new_email"><span id="newmesnum"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'new_message\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</span></span><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

            </a>                                       
            <div class="separator_nav"></div> 
            <?php }?>
        </div>    
        <?php if (isModuleAvailable(@constant('MODULE_STORE'))) {?>
        <div class="premiumbarra">
			<img class="premiumimgbarra" src="<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
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
    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if (!$_smarty_tpl->tpl_vars[\'vmode\']->value) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

		<?php echo '<script'; ?>
 type="text/javascript">
		var viewShortlyNumber	= <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'shortlyNumber\']->value);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
;
		var vacation			= <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'vmode\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
;
        $(function() {
		<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'resourceTable\']->value, \'resourceData\', false, \'resourceID\');
$_smarty_tpl->tpl_vars[\'resourceData\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'resourceID\']->value => $_smarty_tpl->tpl_vars[\'resourceData\']->value) {
$_smarty_tpl->tpl_vars[\'resourceData\']->do_else = false;
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

		<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ((isset($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'production\']))) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

            resourceTicker({
                available: <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'current\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
,
                limit: [0, <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'max\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
],
                production: <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'production\']);?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
,
                valueElem: "current_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceData\']->value[\'name\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
",
                valuePoursent: "bar_<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
"
            }, true);
		<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

		<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

        });
		<?php echo '</script'; ?>
>
        <?php echo '<script'; ?>
 src="scripts/game/topnav.js"><?php echo '</script'; ?>
>
    <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

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
<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'closed\']->value) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_closed\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'delete\']->value) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'delete\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'vacation\']->value) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tn_vacation_mode\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'vacation\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

</div><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.topnav.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.full.tpl" =============================*/
function content_6ab8719acd0152_75337351 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '11217832916ab8719ac24722_03919977';
foreach (array('bodyclass'=>"full") as $ik => $iv) {
$_smarty_tpl->tpl_vars[$ik] =  new Smarty_Variable($iv);
}
$_smarty_tpl->_subTemplateRender("file:main.header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array('bodyclass'=>"full"), 0, false, '48e3dc7c81da9ec904a0f8602367542c854e3727', 'content_6ab8719acd1c28_12211513');
echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php if ($_smarty_tpl->tpl_vars[\'hasAdminAccess\']->value) {?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

<div class="globalWarning">
<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_1\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
 <a id="drop-admin"><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_link\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
</a><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_2\'];?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

</div>
<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php }?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

<?php
$_smarty_tpl->_subTemplateRender("file:main.navigation.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, '0a851ce8eec566aca67772a206ca7211221f0f83', 'content_6ab8719ace6914_33803165');
$_smarty_tpl->_subTemplateRender("file:main.topnav.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, 'ff3b3b778c041f26c841a05fa5174228da36306d', 'content_6ab8719ad03924_76179131');
?>
<div id="content"><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_15509447946ab8719ad511e0_36895501', "content");
?>
</div>
<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'cronjobs\']->value, \'cronjob\');
$_smarty_tpl->tpl_vars[\'cronjob\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'cronjob\']->value) {
$_smarty_tpl->tpl_vars[\'cronjob\']->do_else = false;
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
<img src="cronjob.php?cronjobID=<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php echo $_smarty_tpl->tpl_vars[\'cronjob\']->value;?>
/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>
" alt=""><?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';?>

<?php echo '/*%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/<?php $_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:11217832916ab8719ac24722_03919977%%*/';
}
/* {block "content"} */
class Block_15509447946ab8719ad511e0_36895501 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_15509447946ab8719ad511e0_36895501',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.full.tpl" =============================*/
}
