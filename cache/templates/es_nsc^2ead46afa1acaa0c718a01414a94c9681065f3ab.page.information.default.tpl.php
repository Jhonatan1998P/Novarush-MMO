<?php
/* Smarty version 3.1.36, created on 2026-09-27 03:04:56
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.information.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab86bb8e7fa73_64808040',
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
  'cache_lifetime' => 604800,
),true)) {
function content_6ab86bb8e7fa73_64808040 (Smarty_Internal_Template $_smarty_tpl) {
?><!DOCTYPE html>

<!--[if lt IE 7 ]> <html lang="<?php echo $_smarty_tpl->tpl_vars['lang']->value;?>
" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="<?php echo $_smarty_tpl->tpl_vars['lang']->value;?>
" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?php echo $_smarty_tpl->tpl_vars['lang']->value;?>
" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?php echo $_smarty_tpl->tpl_vars['lang']->value;?>
" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo $_smarty_tpl->tpl_vars['lang']->value;?>
" class="no-js"> <!--<![endif]-->
<head>
    <!--title-->
	<title>Información - <?php echo $_smarty_tpl->tpl_vars['uni_name']->value;?>
 - <?php echo $_smarty_tpl->tpl_vars['game_name']->value;?>
</title>
	<meta name="generator" content="NovaRush">
	<meta name="keywords" content="NovaRush">
	<meta name="description" content="NovaRush Browsergame by LORDA1998">
    <!--favicon-->
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
    <!--goto refresh-->
	    <!--content-type-->
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <!--keypress-->
    <script type="text/javascript" src="./scripts/base/keypress.js"></script>
    <!--jquery-->
    <link rel="stylesheet" type="text/css" href="./styles/resource/css/base/jquery_1.8.18.css">
    <script type="text/javascript" src="./scripts/base/jquery.js"></script>
    <script type="text/javascript" src="./scripts/base/jquery.ui.js"></script>
	<script type="text/javascript" src="./scripts/base/jquery.cookie.js"></script>
    <!--fancybox-->
    <link rel="stylesheet" type="text/css" href="./styles/resource/css/base/jquery.fancybox_3.5.7.css">
    <script type="text/javascript" src="./scripts/base/jquery.fancybox.js"></script>    
    <!--style-->
    <link rel="stylesheet" type="text/css" href="./styles/resource/css/ingame/main.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
css/navigation.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
css/general.css">
    
    <link rel="stylesheet" type="text/css" href="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
formate.css">
    <!--game script-->
    <script type="text/javascript">
        var ServerTimezoneOffset = <?php echo $_smarty_tpl->tpl_vars['Offset']->value;?>
;
        var serverTime 	= new Date(<?php echo $_smarty_tpl->tpl_vars['date']->value[0];?>
, <?php echo $_smarty_tpl->tpl_vars['date']->value[1]-1;?>
, <?php echo $_smarty_tpl->tpl_vars['date']->value[2];?>
, <?php echo $_smarty_tpl->tpl_vars['date']->value[3];?>
, <?php echo $_smarty_tpl->tpl_vars['date']->value[4];?>
, <?php echo $_smarty_tpl->tpl_vars['date']->value[5];?>
);
        var startTime	= serverTime.getTime();
        var localTime 	= serverTime;
        var localTS 	= startTime;
        var Gamename	= document.title;
        var Ready		= "ready";
        var Skin		= "<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
";
        var Lang		= "<?php echo $_smarty_tpl->tpl_vars['lang']->value;?>
";
        var head_info	= "Información";
        var auth		= <?php echo (($tmp = @$_smarty_tpl->tpl_vars['authlevel']->value)===null||$tmp==='' ? '0' : $tmp);?>
;
        var days 		= ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"] 
        var months 		= ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"] ;
        var tdformat	= "[M] [D] [d] [H]:[i]:[s]";
        var queryString	= "<?php echo strtr($_smarty_tpl->tpl_vars['queryString']->value, array("\\" => "\\\\", "'" => "\\'", "\"" => "\\\"", "\r" => "\\r", "\n" => "\\n", "</" => "<\/" ));?>
";
        var isPlayerCardActive	= "<?php echo json_encode($_smarty_tpl->tpl_vars['isPlayerCardActive']->value);?>
";

        setInterval(function() {
            serverTime.setSeconds(serverTime.getSeconds()+1);
        }, 1000);
	</script>
    <script type="text/javascript" src="./scripts/base/tooltip.js"></script>
	<script type="text/javascript" src="./scripts/game/base.js"></script>
    <script type="text/javascript" src="./scripts/game/game.class.js"></script>
    <!--script-->
	<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['scripts']->value, 'scriptname');
$_smarty_tpl->tpl_vars['scriptname']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['scriptname']->value) {
$_smarty_tpl->tpl_vars['scriptname']->do_else = false;
?>
	<script type="text/javascript" src="./scripts/game/<?php echo $_smarty_tpl->tpl_vars['scriptname']->value;?>
.js"></script>
	<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
	
	<script type="text/javascript">
	$(function() {
		<?php echo $_smarty_tpl->tpl_vars['execscript']->value;?>

	});
	</script>
</head>
<body id="information" class="<?php echo $_smarty_tpl->tpl_vars['bodyclass']->value;?>
" 
    style="
        background: #0B0B0F;
        background: url(<?php echo $_smarty_tpl->tpl_vars['background']->value;?>
) no-repeat fixed center center #0d0d0d;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        o-background-size: cover;
        background-size: cover;
">
<div id="tooltip" class="tip"></div>
<link rel="stylesheet" type="text/css" href="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
css/information.css">
<body id="information" class="popup" style="overflow-x: hidden;">
    <div id="body">
        <div id="popup_conteirer">
            <div id="content">
                <div id="ally_content" class="conteiner" style="width:auto;">
                    <div class="gray_stripo">
                        <span class="academy_info_text_h"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['elementID']->value];?>
</span>         
                    </div> 
                    <div class="info_elements">
                        <div class="content_box ">
                            <div class="image">
                                <img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
gebaeude/<?php echo $_smarty_tpl->tpl_vars['elementID']->value;?>
.gif" alt="">
                            </div>
                            <div class="prices info_description">
                                <p class="info15"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['longDescription'][$_smarty_tpl->tpl_vars['elementID']->value];?>

                                    <?php if (!empty($_smarty_tpl->tpl_vars['Bonus']->value)) {?>
                                    <br><b><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_bonus'];?>
</b><br>
                                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['Bonus']->value, 'elementBouns', false, 'BonusName');
$_smarty_tpl->tpl_vars['elementBouns']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['BonusName']->value => $_smarty_tpl->tpl_vars['elementBouns']->value) {
$_smarty_tpl->tpl_vars['elementBouns']->do_else = false;
if ($_smarty_tpl->tpl_vars['elementBouns']->value[0] < 0) {?>-<?php } else { ?>+<?php }
if ($_smarty_tpl->tpl_vars['elementBouns']->value[1] == 0) {
echo abs($_smarty_tpl->tpl_vars['elementBouns']->value[0]*100);?>
%<?php } else {
echo floatval($_smarty_tpl->tpl_vars['elementBouns']->value[0]);
}?> <?php echo $_smarty_tpl->tpl_vars['LNG']->value['bonus'][$_smarty_tpl->tpl_vars['BonusName']->value];?>
<br><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                    <?php }?>
                                </p>
                            </div>
                        </div>
                        <div class="clear"></div>
                        <?php if (!empty($_smarty_tpl->tpl_vars['productionTable']->value['production'])) {?>
                        <?php $_smarty_tpl->_subTemplateRender("file:shared.information.production.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
                        <?php }?>
                        <?php if (!empty($_smarty_tpl->tpl_vars['productionTable']->value['storage'])) {?>
                        <?php $_smarty_tpl->_subTemplateRender("file:shared.information.storage.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
                        <?php }?>
                        <?php if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value)) {?>
                        <?php $_smarty_tpl->_subTemplateRender("file:shared.information.shipInfo.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
                        <?php }?>
                        <?php if (!empty($_smarty_tpl->tpl_vars['gateData']->value)) {?>
                        <?php $_smarty_tpl->_subTemplateRender("file:shared.information.gate.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
                        <?php }?>
                        <?php if (!empty($_smarty_tpl->tpl_vars['MissileList']->value)) {?>
                        <?php $_smarty_tpl->_subTemplateRender("file:shared.information.missiles.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
                        <?php }?>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

<?php $_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
