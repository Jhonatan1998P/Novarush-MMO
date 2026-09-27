<?php
/* Smarty version 3.1.36, created on 2026-09-27 03:31:23
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.playerCard.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab871ebb331d4_63857771',
  'has_nocache_code' => true,
  'file_dependency' => 
  array (
    '2cb37f56a3b4e0ced367b6df64fbde7a36a9c53f' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.playerCard.default.tpl',
      1 => 1642351636,
      2 => 'extends',
    ),
    'ef31a3fa8817650c3a875473023fcb018c12626c' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.playerCard.default.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
    '653f9dc6ea68a2892e74d015fd5cfb76e674e878' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\layout.popup.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
    '48e3dc7c81da9ec904a0f8602367542c854e3727' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\main.header.tpl',
      1 => 1790387714,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:page.playerCard.default.tpl' => 1,
    'file:layout.popup.tpl' => 1,
    'file:main.header.tpl' => 1,
    'file:main.footer.tpl' => 1,
  ),
),false)) {
function content_6ab871ebb331d4_63857771 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
$_smarty_tpl->compiled->nocache_hash = '7799109046ab871eba75d97_27730138';
$_smarty_tpl->_subTemplateRender('file:page.playerCard.default.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, 'ef31a3fa8817650c3a875473023fcb018c12626c', 'content_6ab871ebac18b4_74283396');
$_smarty_tpl->inheritance->endChild($_smarty_tpl);
$_smarty_tpl->_subTemplateRender('file:layout.popup.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, '653f9dc6ea68a2892e74d015fd5cfb76e674e878', 'content_6ab871ebaf9d00_51311683');
}
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.playerCard.default.tpl" =============================*/
function content_6ab871ebac18b4_74283396 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '7799109046ab871eba75d97_27730138';
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_14781843386ab871ebacc547_70838517', "title");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_18673435766ab871ebad6e43_34616154', "content");
}
/* {block "title"} */
class Block_14781843386ab871ebacc547_70838517 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_14781843386ab871ebacc547_70838517',
  ),
);
public $prepend = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['LNG']->value['lm_playercard'];
}
}
/* {/block "title"} */
/* {block "content"} */
class Block_18673435766ab871ebad6e43_34616154 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_18673435766ab871ebad6e43_34616154',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['7799109046ab871eba75d97_27730138'] = true;
?>

<link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
css/playerCard.css">
<body id="playerCard" class="popup" style="overflow-x: hidden;">
    <div id="body">
        <div id="popup_conteirer">
            <div id="content">
                <div id="ally_content" class="conteiner player_card" style="width:auto;">
                    <div class="gray_stripo"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['op_user_info'];?>
</div> 
                    <div class="row" style="padding: 7px">
                        <div class="col-md-12">
                            <div class="card background-border-black-gray shadow"> 
                                <div class="card-body">
                                    <div class="row" style="align-items: center; ">
                                        <div class="col-2">
                                            <img src="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'ava\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
" class="img-fluid float-start opacity-70 border-radius-50">
                                        </div>
                                        <div class="col-10">
                                            <div class="card">
                                                <div class="card-body">
                                                    <p class="card-title" style="font-size: 20px;">
                                                        <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'name\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
 
                                                        <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php if ($_smarty_tpl->tpl_vars[\'allyname\']->value) {?>/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
[<a href="#" onclick="parent.location = 'game.php?page=alliance&amp;mode=info&amp;id=<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'allyid\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
';return false;" class="playercrd17"><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'allyname\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</a><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php } else { ?>/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';
echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php }?>/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
]
                                                    </p>
                                                    <p class="card-title">
                                                        <a href="#" onclick="parent.location = 'game.php?page=galaxy&amp;galaxy=<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
&amp;system=<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
';return false;"><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'homeplanet\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
 [<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'galaxy\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
:<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'system\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
:<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'planet\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
]</a>
                                                    </p>
                                                    <p class="card-title"><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'timezone\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card mt-1 background-border-black-gray shadow"> 
                                <div class="card-body">
                                    <div class="row" style="align-items: center; ">
                                        <div class="col-6">
                                            <div class="card mr-1 background-border-black-blue shadow"> 
                                                <div class="card-body">
                                                    <p class="card-title text-align-center"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_statistics'];?>
</p>
                                                    <table class="tablesorter ally_ranks playercard_tables">
                                                        <tbody>
                                                            <tr class="playercrd2">
                                                                <td style="text-align: left;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_builds'];?>
</td>
                                                                <td><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'build_points\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</td>
                                                                <td style="font-weight:bold">#<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'build_rank\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</td>
                                                            </tr>
                                                            <tr class="playercrd2">
                                                                <td style="text-align: left;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_tech'];?>
</td>
                                                                <td><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'tech_points\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</td>	
                                                                <td style="font-weight:bold">#<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'tech_rank\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</td>	
                                                            </tr>
                                                            <tr class="playercrd2">
                                                                <td style="text-align: left;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_fleet'];?>
</td>
                                                                <td><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'fleet_points\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</td>
                                                                <td style="font-weight:bold">#<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'fleet_rank\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</td>		
                                                            </tr>
                                                            <tr class="playercrd2"> 
                                                                <td style="text-align: left;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_def'];?>
</td>
                                                                <td><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'defs_points\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</td>	
                                                                <td style="font-weight:bold">#<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'defs_rank\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</td>	
                                                            </tr>
                                                            <tr class="playercrd2">
                                                                <td style="text-align: left;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_total'];?>
</td>
                                                                <td><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'total_points\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</td>	
                                                                <td style="font-weight:bold">#<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'total_rank\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="card background-border-black-blue shadow"> 
                                                <div class="card-body">
                                                    <p class="card-title text-align-center"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_fightstats'];?>
</p>
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <p class="alleanza_zone"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_fightwon'];?>
<br><span class="card-text"><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'wons\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
 (<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'siegprozent\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
%)</span></p>
                                                        </div>
                                                        <div class="col-4">
                                                            <p class="alleanza_zone"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_fightlose'];?>
<br><span class="card-text"><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'loos\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
 (<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'loosprozent\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
%)</span></p>
                                                        </div>
                                                        <div class="col-4">
                                                            <p class="alleanza_zone"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_fightdraw'];?>
<br><span class="card-text"><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'draws\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
 (<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'drawsprozent\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
%)</span></p>
                                                        </div>
                                                    </div>
                                                    <p class="card-text"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_totalfight'];?>
 <span style="float: right;"><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'totalfights\']->value);?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</span></p>
                                                    <p class="card-text mb-3"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['op_damage_coef'];?>
 <span style="float: right;"><?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'damageCoef\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
</span></p>
                                                    <div class="alleanza12 tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_unitsshot'];?>
 <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'desunits\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
" style="width:<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'damageDes\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
%"></div>
                                                    <div class="alleanza13 tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['pl_unitslose'];?>
 <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'lostunits\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
" style="width:<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'damageLost\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>   
                    </div>
                </div>
            </div>
        </div>
    </div>         
</body>
<?php
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.playerCard.default.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
function content_6ab871ebafcda6_26747853 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '7799109046ab871eba75d97_27730138';
?>
<!DOCTYPE html>

<!--[if lt IE 7 ]> <html lang="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
" class="no-js"> <!--<![endif]-->
<head>
    <!--title-->
	<title><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_13606334996ab871ebb001d1_46778530', "title");
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
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
css/navigation.css">
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
css/general.css">
    
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
formate.css">
    <!--game script-->
    <?php echo '<script'; ?>
 type="text/javascript">
        var ServerTimezoneOffset = <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'Offset\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
;
        var serverTime 	= new Date(<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[0];?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
, <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[1]-1;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
, <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[2];?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
, <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[3];?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
, <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[4];?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
, <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[5];?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
);
        var startTime	= serverTime.getTime();
        var localTime 	= serverTime;
        var localTS 	= startTime;
        var Gamename	= document.title;
        var Ready		= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['ready'];?>
";
        var Skin		= "<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
";
        var Lang		= "<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
";
        var head_info	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['fcm_info'];?>
";
        var auth		= <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo (($tmp = @$_smarty_tpl->tpl_vars[\'authlevel\']->value)===null||$tmp===\'\' ? \'0\' : $tmp);?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
;
        var days 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['week_day']))===null||$tmp==='' ? '[]' : $tmp);?>
 
        var months 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['months']))===null||$tmp==='' ? '[]' : $tmp);?>
 ;
        var tdformat	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['js_tdformat'];?>
";
        var queryString	= "<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo strtr($_smarty_tpl->tpl_vars[\'queryString\']->value, array("\\\\" => "\\\\\\\\", "\'" => "\\\\\'", "\\"" => "\\\\\\"", "\\r" => "\\\\r", "\\n" => "\\\\n", "</" => "<\\/" ));?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
";
        var isPlayerCardActive	= "<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'isPlayerCardActive\']->value);?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
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
	<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'scripts\']->value, \'scriptname\');
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'scriptname\']->value) {
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = false;
?>/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>

	<?php echo '<script'; ?>
 type="text/javascript" src="./scripts/game/<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'scriptname\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
.js"><?php echo '</script'; ?>
>
	<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>

	<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_10306979346ab871ebb27668_88919963', "script");
?>

	<?php echo '<script'; ?>
 type="text/javascript">
	$(function() {
		<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'execscript\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>

	});
	<?php echo '</script'; ?>
>
</head>
<body id="<?php echo (($tmp = @htmlspecialchars($_GET['page']))===null||$tmp==='' ? 'overview' : $tmp);?>
" class="<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'bodyclass\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
" 
    style="
        background: #0B0B0F;
        background: url(<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'background\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
) no-repeat fixed center center #0d0d0d;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        o-background-size: cover;
        background-size: cover;
">
<div id="tooltip" class="tip"></div><?php
}
/* {block "title"} */
class Block_13606334996ab871ebb001d1_46778530 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_13606334996ab871ebb001d1_46778530',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['7799109046ab871eba75d97_27730138'] = true;
?>
 - <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'uni_name\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';?>
 - <?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php echo $_smarty_tpl->tpl_vars[\'game_name\']->value;?>
/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';
}
}
/* {/block "title"} */
/* {block "script"} */
class Block_10306979346ab871ebb27668_88919963 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_10306979346ab871ebb27668_88919963',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.popup.tpl" =============================*/
function content_6ab871ebaf9d00_51311683 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '7799109046ab871eba75d97_27730138';
foreach (array('bodyclass'=>"popup") as $ik => $iv) {
$_smarty_tpl->tpl_vars[$ik] =  new Smarty_Variable($iv);
}
$_smarty_tpl->_subTemplateRender("file:main.header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array('bodyclass'=>"popup"), 0, false, '48e3dc7c81da9ec904a0f8602367542c854e3727', 'content_6ab871ebafcda6_26747853');
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_3564036276ab871ebb31000_57993043', "content");
?>

<?php echo '/*%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/<?php $_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:7799109046ab871eba75d97_27730138%%*/';
}
/* {block "content"} */
class Block_3564036276ab871ebb31000_57993043 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_3564036276ab871ebb31000_57993043',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.popup.tpl" =============================*/
}
