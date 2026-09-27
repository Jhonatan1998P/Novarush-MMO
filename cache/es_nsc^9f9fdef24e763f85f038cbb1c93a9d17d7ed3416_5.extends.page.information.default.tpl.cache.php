<?php
/* Smarty version 3.1.36, created on 2026-09-27 03:04:56
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.information.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab86bb88945b0_93929143',
  'has_nocache_code' => true,
  'file_dependency' => 
  array (
    '9f9fdef24e763f85f038cbb1c93a9d17d7ed3416' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.information.default.tpl',
      1 => 1642351636,
      2 => 'extends',
    ),
    '2667818a31ab66f03b11efb08e6228d04db80b9f' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.information.default.tpl',
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
    'file:page.information.default.tpl' => 1,
    'file:shared.information.production.tpl' => 1,
    'file:shared.information.storage.tpl' => 1,
    'file:shared.information.shipInfo.tpl' => 1,
    'file:shared.information.gate.tpl' => 1,
    'file:shared.information.missiles.tpl' => 1,
    'file:layout.popup.tpl' => 1,
    'file:main.header.tpl' => 1,
    'file:main.footer.tpl' => 1,
  ),
),false)) {
function content_6ab86bb88945b0_93929143 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
$_smarty_tpl->compiled->nocache_hash = '13626962376ab86bb882c289_65288195';
$_smarty_tpl->_subTemplateRender('file:page.information.default.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, '2667818a31ab66f03b11efb08e6228d04db80b9f', 'content_6ab86bb88538b3_49345200');
$_smarty_tpl->inheritance->endChild($_smarty_tpl);
$_smarty_tpl->_subTemplateRender('file:layout.popup.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, '653f9dc6ea68a2892e74d015fd5cfb76e674e878', 'content_6ab86bb8875e21_56390481');
}
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.information.default.tpl" =============================*/
function content_6ab86bb88538b3_49345200 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '13626962376ab86bb882c289_65288195';
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_5105854266ab86bb885a159_90864024', "title");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_16148062456ab86bb8860033_82433137', "content");
}
/* {block "title"} */
class Block_5105854266ab86bb885a159_90864024 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_5105854266ab86bb885a159_90864024',
  ),
);
public $prepend = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['LNG']->value['lm_info'];
}
}
/* {/block "title"} */
/* {block "content"} */
class Block_16148062456ab86bb8860033_82433137 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_16148062456ab86bb8860033_82433137',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['13626962376ab86bb882c289_65288195'] = true;
?>

<link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
css/information.css">
<body id="information" class="popup" style="overflow-x: hidden;">
    <div id="body">
        <div id="popup_conteirer">
            <div id="content">
                <div id="ally_content" class="conteiner" style="width:auto;">
                    <div class="gray_stripo">
                        <span class="academy_info_text_h"><?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'elementID\']->value];?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
</span>         
                    </div> 
                    <div class="info_elements">
                        <div class="content_box ">
                            <div class="image">
                                <img src="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'elementID\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
.gif" alt="">
                            </div>
                            <div class="prices info_description">
                                <p class="info15"><?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'longDescription\'][$_smarty_tpl->tpl_vars[\'elementID\']->value];?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                                    <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'Bonus\']->value)) {?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                                    <br><b><?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'in_bonus\'];?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
</b><br>
                                    <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'Bonus\']->value, \'elementBouns\', false, \'BonusName\');
$_smarty_tpl->tpl_vars[\'elementBouns\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'BonusName\']->value => $_smarty_tpl->tpl_vars[\'elementBouns\']->value) {
$_smarty_tpl->tpl_vars[\'elementBouns\']->do_else = false;
?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';
echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php if ($_smarty_tpl->tpl_vars[\'elementBouns\']->value[0] < 0) {?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
-<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php } else { ?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
+<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php }?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';
echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php if ($_smarty_tpl->tpl_vars[\'elementBouns\']->value[1] == 0) {?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';
echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo abs($_smarty_tpl->tpl_vars[\'elementBouns\']->value[0]*100);?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
%<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php } else { ?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';
echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo floatval($_smarty_tpl->tpl_vars[\'elementBouns\']->value[0]);?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';
echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php }?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
 <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'bonus\'][$_smarty_tpl->tpl_vars[\'BonusName\']->value];?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
<br><?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                                    <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php }?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                                </p>
                            </div>
                        </div>
                        <div class="clear"></div>
                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'productionTable\']->value[\'production\'])) {?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php $_smarty_tpl->_subTemplateRender("file:shared.information.production.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php }?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'productionTable\']->value[\'storage\'])) {?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php $_smarty_tpl->_subTemplateRender("file:shared.information.storage.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php }?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'FleetInfo\']->value)) {?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php $_smarty_tpl->_subTemplateRender("file:shared.information.shipInfo.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php }?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'gateData\']->value)) {?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php $_smarty_tpl->_subTemplateRender("file:shared.information.gate.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php }?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'MissileList\']->value)) {?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php $_smarty_tpl->_subTemplateRender("file:shared.information.missiles.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php }?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

                        <div class="clear"></div>
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
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.information.default.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
function content_6ab86bb8877624_66627008 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '13626962376ab86bb882c289_65288195';
?>
<!DOCTYPE html>

<!--[if lt IE 7 ]> <html lang="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
" class="no-js"> <!--<![endif]-->
<head>
    <!--title-->
	<title><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_20434952596ab86bb8879557_68992977', "title");
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
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
css/navigation.css">
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
css/general.css">
    
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
formate.css">
    <!--game script-->
    <?php echo '<script'; ?>
 type="text/javascript">
        var ServerTimezoneOffset = <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'Offset\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
;
        var serverTime 	= new Date(<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[0];?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
, <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[1]-1;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
, <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[2];?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
, <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[3];?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
, <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[4];?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
, <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[5];?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
);
        var startTime	= serverTime.getTime();
        var localTime 	= serverTime;
        var localTS 	= startTime;
        var Gamename	= document.title;
        var Ready		= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['ready'];?>
";
        var Skin		= "<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
";
        var Lang		= "<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
";
        var head_info	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['fcm_info'];?>
";
        var auth		= <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo (($tmp = @$_smarty_tpl->tpl_vars[\'authlevel\']->value)===null||$tmp===\'\' ? \'0\' : $tmp);?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
;
        var days 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['week_day']))===null||$tmp==='' ? '[]' : $tmp);?>
 
        var months 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['months']))===null||$tmp==='' ? '[]' : $tmp);?>
 ;
        var tdformat	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['js_tdformat'];?>
";
        var queryString	= "<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo strtr($_smarty_tpl->tpl_vars[\'queryString\']->value, array("\\\\" => "\\\\\\\\", "\'" => "\\\\\'", "\\"" => "\\\\\\"", "\\r" => "\\\\r", "\\n" => "\\\\n", "</" => "<\\/" ));?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
";
        var isPlayerCardActive	= "<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'isPlayerCardActive\']->value);?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
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
	<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'scripts\']->value, \'scriptname\');
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'scriptname\']->value) {
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = false;
?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

	<?php echo '<script'; ?>
 type="text/javascript" src="./scripts/game/<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'scriptname\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
.js"><?php echo '</script'; ?>
>
	<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

	<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_13494395386ab86bb888ded4_88440300', "script");
?>

	<?php echo '<script'; ?>
 type="text/javascript">
	$(function() {
		<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'execscript\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>

	});
	<?php echo '</script'; ?>
>
</head>
<body id="<?php echo (($tmp = @htmlspecialchars($_GET['page']))===null||$tmp==='' ? 'overview' : $tmp);?>
" class="<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'bodyclass\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
" 
    style="
        background: #0B0B0F;
        background: url(<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'background\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
) no-repeat fixed center center #0d0d0d;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        o-background-size: cover;
        background-size: cover;
">
<div id="tooltip" class="tip"></div><?php
}
/* {block "title"} */
class Block_20434952596ab86bb8879557_68992977 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_20434952596ab86bb8879557_68992977',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['13626962376ab86bb882c289_65288195'] = true;
?>
 - <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'uni_name\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';?>
 - <?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php echo $_smarty_tpl->tpl_vars[\'game_name\']->value;?>
/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';
}
}
/* {/block "title"} */
/* {block "script"} */
class Block_13494395386ab86bb888ded4_88440300 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_13494395386ab86bb888ded4_88440300',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.popup.tpl" =============================*/
function content_6ab86bb8875e21_56390481 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '13626962376ab86bb882c289_65288195';
foreach (array('bodyclass'=>"popup") as $ik => $iv) {
$_smarty_tpl->tpl_vars[$ik] =  new Smarty_Variable($iv);
}
$_smarty_tpl->_subTemplateRender("file:main.header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array('bodyclass'=>"popup"), 0, false, '48e3dc7c81da9ec904a0f8602367542c854e3727', 'content_6ab86bb8877624_66627008');
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_13221728926ab86bb8893410_09565779', "content");
?>

<?php echo '/*%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/<?php $_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:13626962376ab86bb882c289_65288195%%*/';
}
/* {block "content"} */
class Block_13221728926ab86bb8893410_09565779 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_13221728926ab86bb8893410_09565779',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.popup.tpl" =============================*/
}
