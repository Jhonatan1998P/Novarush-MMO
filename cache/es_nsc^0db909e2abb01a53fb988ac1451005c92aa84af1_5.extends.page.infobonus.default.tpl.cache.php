<?php
/* Smarty version 3.1.36, created on 2026-09-27 04:09:00
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.infobonus.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab87abc11e7b0_14788505',
  'has_nocache_code' => true,
  'file_dependency' => 
  array (
    '0db909e2abb01a53fb988ac1451005c92aa84af1' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.infobonus.default.tpl',
      1 => 1642351636,
      2 => 'extends',
    ),
    '5d2cf7bd709dfc24b3ef15e517e31b11c33300a1' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.infobonus.default.tpl',
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
    'file:page.infobonus.default.tpl' => 1,
    'file:layout.full.tpl' => 1,
    'file:main.header.tpl' => 1,
    'file:main.navigation.tpl' => 1,
    'file:main.topnav.tpl' => 1,
    'file:main.footer.tpl' => 1,
  ),
),false)) {
function content_6ab87abc11e7b0_14788505 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
$_smarty_tpl->compiled->nocache_hash = '20871226266ab87abc0040c0_79284733';
$_smarty_tpl->_subTemplateRender('file:page.infobonus.default.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, '5d2cf7bd709dfc24b3ef15e517e31b11c33300a1', 'content_6ab87abc027832_11740423');
$_smarty_tpl->inheritance->endChild($_smarty_tpl);
$_smarty_tpl->_subTemplateRender('file:layout.full.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, 'd63d6432ef23500a7b3d46d973fb3cfb4eab4e48', 'content_6ab87abc075a51_51526852');
}
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.infobonus.default.tpl" =============================*/
function content_6ab87abc027832_11740423 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '20871226266ab87abc0040c0_79284733';
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_4560908516ab87abc0559b6_69367251', "title");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_1717023236ab87abc05c396_12039985', "content");
}
/* {block "title"} */
class Block_4560908516ab87abc0559b6_69367251 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_4560908516ab87abc0559b6_69367251',
  ),
);
public $prepend = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['LNG']->value['lm_infobonus'];
}
}
/* {/block "title"} */
/* {block "content"} */
class Block_1717023236ab87abc05c396_12039985 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_1717023236ab87abc05c396_12039985',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['20871226266ab87abc0040c0_79284733'] = true;
?>

<link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
css/statistics.css">
<div id="page">
	<div id="content">
        <div id="ally_content" class="conteinership" style="margin-bottom: 15px;">
            <div class="gray_flettab" style="padding-right:0;">
                <?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_infobonus'];?>

                <a href="game.php?page=overview" class="tornaindietroa"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_overview'];?>
</a> 
            </div>
            <div class="fleettab8" style="margin-bottom: 0;"></div>
            <table class="tablesorter ally_ranks tabstatistica" style="margin-bottom:5px">
                <tbody>
                    <tr class="torneo4">
                        <th style="width: 35px;min-width: 35px;">#</th>
                        <th style="width: 85px;min-width: 85px;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['inb_percent'];?>
</th>
                        <th style="text-align: center;min-width: 75px;width: 75px;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['inb_units'];?>
</th>
                        <th style="text-align:center;width: 465px;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['inb_name'];?>
</th>
                    </tr>
                    <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'bonus\']->value, \'Element\');
$_smarty_tpl->tpl_vars[\'Element\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'Element\']->value) {
$_smarty_tpl->tpl_vars[\'Element\']->do_else = false;
?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                    <tr class="day0 week0 classificabarra">
                        <td class="classstat1" style="text-align: center;width: 35px;max-width: 35px;min-width: 35px;">
                            <div class="barracla" style=""></div>
                            <div class="info_bonus_img"><img height="18" width="18" src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
gebaeude/bonus/<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
.gif"></div>
                        </td>
                        <td class="classstat1" style="text-align: center;width: 65px;max-width: 65px;min-width: 65px;">
                            <font color="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'USER\']->value[\'factor\'][$_smarty_tpl->tpl_vars[\'Element\']->value] < 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
#db374b<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } else { ?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
#096<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number(($_smarty_tpl->tpl_vars[\'USER\']->value[\'factor\'][$_smarty_tpl->tpl_vars[\'Element\']->value]*100));?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
%</font>
                        </td>
                        <td class="classstat1" style="text-align: center;width: 100px;max-width: 100px;min-width: 100px;">
                            <font color="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'USER\']->value[\'factor\'][$_smarty_tpl->tpl_vars[\'Element\']->value] < 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
#db374b<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } else { ?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
#096<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number(($_smarty_tpl->tpl_vars[\'USER\']->value[\'factor\'][$_smarty_tpl->tpl_vars[\'Element\']->value]));?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</font>
                            <div class="barracla" style="float: left;"></div>
                            <div class="barracla" style=""></div>
                        </td>
                        <td class="classstat10" style="text-align: right;width: 405px;min-width: 405px;"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'bonus\'][$_smarty_tpl->tpl_vars[\'Element\']->value];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</td>
                    </tr>
                    <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.infobonus.default.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
function content_6ab87abc077743_59686227 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '20871226266ab87abc0040c0_79284733';
?>
<!DOCTYPE html>

<!--[if lt IE 7 ]> <html lang="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" class="no-js"> <!--<![endif]-->
<head>
    <!--title-->
	<title><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_8969702276ab87abc079959_07511286', "title");
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
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
css/navigation.css">
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
css/general.css">
    
    <link rel="stylesheet" type="text/css" href="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
formate.css">
    <!--game script-->
    <?php echo '<script'; ?>
 type="text/javascript">
        var ServerTimezoneOffset = <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Offset\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
;
        var serverTime 	= new Date(<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[0];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
, <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[1]-1;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
, <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[2];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
, <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[3];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
, <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[4];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
, <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'date\']->value[5];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
);
        var startTime	= serverTime.getTime();
        var localTime 	= serverTime;
        var localTS 	= startTime;
        var Gamename	= document.title;
        var Ready		= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['ready'];?>
";
        var Skin		= "<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
";
        var Lang		= "<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
";
        var head_info	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['fcm_info'];?>
";
        var auth		= <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo (($tmp = @$_smarty_tpl->tpl_vars[\'authlevel\']->value)===null||$tmp===\'\' ? \'0\' : $tmp);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
;
        var days 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['week_day']))===null||$tmp==='' ? '[]' : $tmp);?>
 
        var months 		= <?php echo (($tmp = @json_encode($_smarty_tpl->tpl_vars['LNG']->value['months']))===null||$tmp==='' ? '[]' : $tmp);?>
 ;
        var tdformat	= "<?php echo $_smarty_tpl->tpl_vars['LNG']->value['js_tdformat'];?>
";
        var queryString	= "<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo strtr($_smarty_tpl->tpl_vars[\'queryString\']->value, array("\\\\" => "\\\\\\\\", "\'" => "\\\\\'", "\\"" => "\\\\\\"", "\\r" => "\\\\r", "\\n" => "\\\\n", "</" => "<\\/" ));?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
";
        var isPlayerCardActive	= "<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'isPlayerCardActive\']->value);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
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
	<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'scripts\']->value, \'scriptname\');
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'scriptname\']->value) {
$_smarty_tpl->tpl_vars[\'scriptname\']->do_else = false;
?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

	<?php echo '<script'; ?>
 type="text/javascript" src="./scripts/game/<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'scriptname\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
.js"><?php echo '</script'; ?>
>
	<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

	<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_19736375896ab87abc08c846_13776616', "script");
?>

	<?php echo '<script'; ?>
 type="text/javascript">
	$(function() {
		<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'execscript\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

	});
	<?php echo '</script'; ?>
>
</head>
<body id="<?php echo (($tmp = @htmlspecialchars($_GET['page']))===null||$tmp==='' ? 'overview' : $tmp);?>
" class="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'bodyclass\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" 
    style="
        background: #0B0B0F;
        background: url(<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'background\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
) no-repeat fixed center center #0d0d0d;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        o-background-size: cover;
        background-size: cover;
">
<div id="tooltip" class="tip"></div><?php
}
/* {block "title"} */
class Block_8969702276ab87abc079959_07511286 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_8969702276ab87abc079959_07511286',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['20871226266ab87abc0040c0_79284733'] = true;
?>
 - <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'uni_name\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 - <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'game_name\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';
}
}
/* {/block "title"} */
/* {block "script"} */
class Block_19736375896ab87abc08c846_13776616 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_19736375896ab87abc08c846_13776616',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.header.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.navigation.tpl" =============================*/
function content_6ab87abc095109_83190952 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->compiled->nocache_hash = '20871226266ab87abc0040c0_79284733';
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
                <div id="attack" class="indicator <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'ataks\']->value > 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="espionage" class="indicator <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'spio\']->value > 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="destruction" class="indicator <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'unic\']->value > 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
">
                    <div class="icoi"></div>
                </div>
                <div id="rocket" class="indicator <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'rakets\']->value > 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
active_indicator<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
">
                    <div class="icoi"></div>
                </div>
            </div>
                        <a class="big_btn btn_menu btn_menu_big"> <div class="servertime oservertime"></div> </a>
            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'bonus_time\']->value < TIMESTAMP) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

            <a class="big_btn blue btn_menu btn_menu_big" href="game.php?page=bonus"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_bonus\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } else { ?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

            <a class="big_btn blue btn_menu btn_menu_big"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'bonus_time_rest\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

             
            <!-- ricerche  tecnologie-->
            <?php if (isModuleAvailable(@constant('MODULE_RESEARCH'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=research" id="munu_research"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_research'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=research"><img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/research.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- costruzioni risorse-->
            <?php if (isModuleAvailable(@constant('MODULE_BUILDING'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=buildings" id="munu_build"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_buildings'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_RESSOURCE_LIST'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=resources" id="munu_resources" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_resources'];?>
"><img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/resources.png" class="oimgaltro"></a>
            <?php }?>
            <!-- flotta hangar -->
            <?php if (isModuleAvailable(@constant('MODULE_SHIPYARD_FLEET'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=shipyard&amp;mode=fleet" id="munu_shipyard_fleet"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_shipshard'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=shipyard&amp;mode=fleet" id="munu_fleetable"><img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/hangar.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- difese -->
            <?php if (isModuleAvailable(@constant('MODULE_SHIPYARD_DEFENSIVE'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=shipyard&amp;mode=defense" id="munu_shipyard_defense"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_defenses'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=shipyard&amp;mode=defense"><img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/shield.png" class="imgovernuovo"></a>
            <?php }?>
            <!-- Orbita -->
            <a class="nuovomenusinistra" href="game.php?page=fleetTable" id="munu_orbita"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_fleet'];?>
</a>
            <?php if (isModuleAvailable(@constant('MODULE_SIMULATOR'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=battleSimulator" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_battlesim'];?>
"><img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/target.png" class="oimgaltro"></a>	
            <?php }?>            
            <!-- alleanza-->
            <?php if (isModuleAvailable(@constant('MODULE_ALLIANCE'))) {?>
			<a class="nuovomenusinistra" href="game.php?page=alliance" id="munu_alliance"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_alliance'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=alliance"><img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/alliance.png" class="imgovernuovo" id="ciaone"></a>	
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_MARKET'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=market"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_market'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=market"><img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/market.png" class="imgovernuovo"></a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_ARSENAL'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=arsenal"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_ars'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_CONTAINER'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=conteiner" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_container'];?>
"><img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/arsenal.png" class="oimgaltro"></a>	
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_OFFICIER'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=officier" id="munu_senat"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_officiers'];?>
</a>
            <a class="nuovomenudestra" href="game.php?page=officier"><img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/governatori.png" class="imgovernuovo" id="ciaone"></a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_MINERALS'))) {?>
            <a class="nuovomenusinistra" href="game.php?page=minerals" id="munu_senat"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_minerals'];?>
</a>
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_DETAILS'))) {?>
            <a class="nuovomenudestra tooltip" href="game.php?page=details" id="munu_fleetable" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_details'];?>
"><img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/blackmarket.png" class="oimgaltro"></a>	
            <?php }?>
            <!-- ufficiali governatori -->
            <?php if (isModuleAvailable(@constant('MODULE_GALAXY'))) {?>
            <a class="galassiabott" href="game.php?page=galaxy" id="munu_galaxy"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_galaxy'];?>
</a>
            <?php }?>   
     		<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'authlevel\']->value > 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

            <a  href="admin.php" class="big_btn green btn_menu btn_menu_big"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_administration\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

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
		var ataks = "<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'ataks\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
";
		var spio = "<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'spio\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
";
        var unic = "<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'unic\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
";
		var rakets = "<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'rakets\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
";
		var msg = <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'new_message\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
;
		document.getElementById('msgaudio').volume=<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'msgvolume\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
;
		document.getElementById('beepataks').volume=<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'volume\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
;
	<?php echo '</script'; ?>
>
</div><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.navigation.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.topnav.tpl" =============================*/
function content_6ab87abc0b7ef6_78370314 (Smarty_Internal_Template $_smarty_tpl) {
echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php $_smarty_tpl->_checkPlugins(array(0=>array(\'file\'=>\'C:\\\\Users\\\\Ortega\\\\Downloads\\\\OGAME\\\\ogame\\\\includes\\\\libs\\\\Smarty\\\\plugins\\\\function.html_options.php\',\'function\'=>\'smarty_function_html_options\',),));
?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';
$_smarty_tpl->compiled->nocache_hash = '20871226266ab87abc0040c0_79284733';
?>
<div id="header">
    <div id="top_nav" class="otopnav"> 
        <a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_overview'];?>
" href="game.php?page=overview">
            <img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/general/logo.png" class="game_logo">
        </a>
        <div style="display:none;">					
            <select id="lstPlaneta" name="lstPlaneta" onchange="document.location = $(this).val();">
                <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo smarty_function_html_options(array(\'options\'=>$_smarty_tpl->tpl_vars[\'PlanetSelect\']->value,\'selected\'=>$_smarty_tpl->tpl_vars[\'current_pids\']->value),$_smarty_tpl);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

            </select>
        </div>
        <div class="mini_planet_navigation" style="margin: auto;left: 0;right: 0;width: 235px;background: none;top: 46px;position: absolute;">
            <span class="link_back" title="" onclick="eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex-1].value+'\'');"></span>
            <span class="link_next" title="" onclick="eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex+1].value+'\'');"></span>
        </div>
        <div id="planet_select" style="margin: auto;left: 0;right: 0;top:46px;">
            <div class="active_panet">
				<div class="name_palnet" style="padding-left: 1px;width: 96px;"><img src='<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
planeten/planet2d/<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetImage\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
.png' style="float:left;height: 22px;padding-top:3px;margin-right: 5px;"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetName\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</div> 
                <span class="ico_build"></span>                            
				<div class="coordinates_palnet">[<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetGalaxy\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
:<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetSystem\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
:<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'planetPlanet\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
]</div>
				<div class="clear"></div>
			</div>
            <div id="list_palnet">
            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'PlanetListing\']->value, \'Element\', false, \'ID\');
$_smarty_tpl->tpl_vars[\'Element\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'ID\']->value => $_smarty_tpl->tpl_vars[\'Element\']->value) {
$_smarty_tpl->tpl_vars[\'Element\']->do_else = false;
?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
        
			<div class="separator_h"></div>                   
            <div class="palnet_row <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'ID\']->value) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
active_palnet_row<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
">
				<div class="fleet_indicators">
                    <img id="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
m1" <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalAttacks\'] == 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_attack.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_1\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" />                                    
                    <img id="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
m12" style="display:none;" src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_grab.png" alt="" class="tooltip" data-tooltip-content="Планету захватывают" />
                    <img id="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
m6" <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalSpio\'] == 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_spio.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_2\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
m10" <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalRockets\'] == 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_rocket.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_3\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" />                 
                    <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'] != 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
  
                    <img id="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
m1" <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalAttackLuna\'] == 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_moon_attack.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_4\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
m6" <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalRocketsLuna\'] == 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_moon_spio.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_5\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" />       
                    <img id="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
m9" style="display:none;" src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_destrued.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_6\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" />
                    <img id="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
m10" <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'totalSpioLuna\'] == 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
style="display:none;"<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_moon_rocket.png" alt="" class="tooltip" data-tooltip-content="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'pla_attack_7\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" />                         
					<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
                
                    <div class="clear"></div>
                </div>	   
                <span class="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'ID\']->value) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
active_urlpalnet<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } else { ?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
urlpalnet<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" url="cp=<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'ID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
">
					<img src='<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
planeten/planet2d/<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'image\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
.png' style="float:left;height: 22px;padding-top: 5px;">
                    <span class="name_palnet"  style="padding-top: 5px;padding-left: 5px;width: 70px;"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'name\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span>
					<span class="ico_build">
                        <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\']) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                            <img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_build.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                    <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'id\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
.gif' width='35' height='35'></td>
                                    <td><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'id\']];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 (<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'level\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
)</td>
                                    </tr>
                                    <tr><td><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_time($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'buildings\'][\'timeleft\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 </td></tr>
                                </table>
                            "/>
						<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

						<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\']) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                            <img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_ship.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                    <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'id\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
.gif' width='35' height='35'></td>
                                    <td><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'id\']];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</td>
                                    </tr>
                                    <tr><td><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'fleet\'][\'level\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</td></tr>
                                </table>
                            "/> 
						<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

						<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\']) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

							<img src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
img/iconav/p_select_tech.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                <tr>
                                <td rowspan='2'><img alt='' src='<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
gebaeude/<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'id\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
.gif' width='35' height='35'></td>
                                <td><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'id\']];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 (<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'level\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
)</td>
                                </tr>
                                <tr><td><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_time($_smarty_tpl->tpl_vars[\'Element\']->value[\'buildInfo\'][\'tech\'][\'timeleft\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 </td></tr>
                                </table> 
                            "/>
						<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

					</span>  			
                    <span class="coordinates_palnet" style="width: 60px;">[<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'galaxy\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
:<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'system\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
:<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'planet\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
]</span>
                </span>
                <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'] != 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
                             
                <div class="separator_v"></div>
                <span class="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\']) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
active_urlpalnet<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } else { ?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
urlpalnet<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" url="cp=<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
">
                    <span class="moon_select <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'current_pid\']->value == $_smarty_tpl->tpl_vars[\'Element\']->value[\'luna\']) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
moon_active<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"></span>
                    <span class="ico_build"><br /></span>
                </span>
                <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
                
            </div> 
            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
                       
            </div>
        </div><!--/planet_select-->			
		<img title="" src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'foto\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" class="settingxterium" onclick="return Dialog.Playercard(<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'userID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
, '<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
');">
        <span class="usernameow" onclick="return Dialog.Playercard(<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'userID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
, '<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
');"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'username\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span>
		<span class="usernamepos"></span>        
        <div id="res_nav">
            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'resourceTable\']->value, \'resouceData\', false, \'resourceID\');
$_smarty_tpl->tpl_vars[\'resouceData\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'resourceID\']->value => $_smarty_tpl->tpl_vars[\'resouceData\']->value) {
$_smarty_tpl->tpl_vars[\'resouceData\']->do_else = false;
?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 
                <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if (!(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']))) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                    <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php $_tmp_array = isset($_smarty_tpl->tpl_vars[\'resouceData\']) ? $_smarty_tpl->tpl_vars[\'resouceData\']->value : array();
if (!(is_array($_tmp_array) || $_tmp_array instanceof ArrayAccess)) {
settype($_tmp_array, \'array\');
}
$_tmp_array[\'current\'] = $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']+$_smarty_tpl->tpl_vars[\'resouceData\']->value[\'used\'];
$_smarty_tpl->_assignInScope(\'resouceData\', $_tmp_array ,true);?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                    <div id="res_block_<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" class="bloc_res tooltip" data-tooltip-content="<span class='colore<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
'><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span><div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'RE\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
%">
                        <div class="ico_res"></div>
                        <div class="stock_res">
                            <div class="stock_percentage stock_percentage_left" style="width:<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo abs($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']/2);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
%;<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] > -0.1) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
display:none;<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"></div>
                            <div class="stock_percentage stock_percentage_right" style="width:<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\']/2;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
%;<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] < 0.1) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
display:none;<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"></div>
                            <div class="separator_<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"></div>
                            <div class="stock_text"><span id="current_<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" name="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'used\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span>/<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</div>
                        </div>
                    </div>
                <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } else { ?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                    <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if (!(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'])) || !(isset($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']))) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                        <div id="res_block_<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" class="bloc_res tooltip" data-tooltip-content="<span class='colore<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
'><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span><div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'RE\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
">
                            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if (isModuleAvailable(@constant(\'MODULE_FAIR\'))) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                            <a href="game.php?page=fair"><div class="ico_res"></div></a>
                            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } else { ?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                            <div class="ico_res"></div>
                            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                            <div class="stock_res2">
                                <div class="stock_percentage" style="width:100%;"></div>
                                <div class="separator_<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"></div>
                                <div class="stock_text"><span class='colore<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
' id="current_<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" name="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span></div>
                            </div>
                        </div>
                    <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } else { ?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                        <div id="res_block_<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" class="bloc_res tooltip" 
                            data-tooltip-content="
                            <span class='colore<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
'><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tech\'][$_smarty_tpl->tpl_vars[\'resourceID\']->value];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span>
                            <div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div>
                            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPS\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
: <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'information\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                            <br/><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPD\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
: <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'informationd\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                            <br/><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'PPW\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
: <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'informationz\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 
                            <div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'>
                            </div> <span style='color:#999'><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
/<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'max\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span>">
                            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if (isModuleAvailable(@constant(\'MODULE_TRADER\'))) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                            <a href="game.php?page=trader"><div class="ico_res"></div></a>
                            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } else { ?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                            <div class="ico_res"></div>
                            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                                                        <div class="stock_res">
                                <div class="stock_percentage" style="width:<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
%;"></div>
                                <div class="stock_text">
                                    <span id="current_<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'name\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" name="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo pretty_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" data-real="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo shortly_number($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'current\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span>
                                    (<span class="pricent"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'] <= 100) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';
echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resouceData\']->value[\'percent\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';
echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } else { ?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
100<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span>%)
                                </div>
                            </div>	
                        </div>
                    <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

                <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
       
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
            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'hasBoard\']->value)) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

			<a title="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'lm_forums\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" href="game.php?page=board" target="_blank"><span class="forum"></span></a>
			<div class="separator_nav"></div>
            <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

            <?php if (isModuleAvailable(@constant('MODULE_MESSAGES'))) {?>
            <a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_messages'];?>
" href="game.php?page=messages" id="a_mesage">
                <span class="mesages"></span>
                <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'new_message\']->value > 0) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
<span class="new_email"><span id="newmesnum"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'new_message\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</span></span><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

            </a>                                       
            <div class="separator_nav"></div> 
            <?php }?>
        </div>    
        <?php if (isModuleAvailable(@constant('MODULE_STORE'))) {?>
        <div class="premiumbarra">
			<img class="premiumimgbarra" src="<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
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
    <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if (!$_smarty_tpl->tpl_vars[\'vmode\']->value) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

		<?php echo '<script'; ?>
 type="text/javascript">
		var viewShortlyNumber	= <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'shortlyNumber\']->value);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
;
		var vacation			= <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'vmode\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
;
        $(function() {
		<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'resourceTable\']->value, \'resourceData\', false, \'resourceID\');
$_smarty_tpl->tpl_vars[\'resourceData\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'resourceID\']->value => $_smarty_tpl->tpl_vars[\'resourceData\']->value) {
$_smarty_tpl->tpl_vars[\'resourceData\']->do_else = false;
?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

		<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ((isset($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'production\']))) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

            resourceTicker({
                available: <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'current\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
,
                limit: [0, <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'max\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
],
                production: <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'resourceData\']->value[\'production\']);?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
,
                valueElem: "current_<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceData\']->value[\'name\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
",
                valuePoursent: "bar_<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'resourceID\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
"
            }, true);
		<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

		<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

        });
		<?php echo '</script'; ?>
>
        <?php echo '<script'; ?>
 src="scripts/game/topnav.js"><?php echo '</script'; ?>
>
    <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

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
<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'closed\']->value) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'ov_closed\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'delete\']->value) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'delete\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php } elseif ($_smarty_tpl->tpl_vars[\'vacation\']->value) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

<div class="infobox"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'tn_vacation_mode\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 <?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'vacation\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</div>
<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

</div><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.topnav.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.full.tpl" =============================*/
function content_6ab87abc075a51_51526852 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '20871226266ab87abc0040c0_79284733';
foreach (array('bodyclass'=>"full") as $ik => $iv) {
$_smarty_tpl->tpl_vars[$ik] =  new Smarty_Variable($iv);
}
$_smarty_tpl->_subTemplateRender("file:main.header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array('bodyclass'=>"full"), 0, false, '48e3dc7c81da9ec904a0f8602367542c854e3727', 'content_6ab87abc077743_59686227');
echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php if ($_smarty_tpl->tpl_vars[\'hasAdminAccess\']->value) {?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

<div class="globalWarning">
<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_1\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
 <a id="drop-admin"><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_link\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
</a><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'admin_access_2\'];?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

</div>
<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php }?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

<?php
$_smarty_tpl->_subTemplateRender("file:main.navigation.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, '0a851ce8eec566aca67772a206ca7211221f0f83', 'content_6ab87abc095109_83190952');
$_smarty_tpl->_subTemplateRender("file:main.topnav.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, 'ff3b3b778c041f26c841a05fa5174228da36306d', 'content_6ab87abc0b7ef6_78370314');
?>
<div id="content"><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_10732274566ab87abc11ac80_03654755', "content");
?>
</div>
<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'cronjobs\']->value, \'cronjob\');
$_smarty_tpl->tpl_vars[\'cronjob\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'cronjob\']->value) {
$_smarty_tpl->tpl_vars[\'cronjob\']->do_else = false;
?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
<img src="cronjob.php?cronjobID=<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php echo $_smarty_tpl->tpl_vars[\'cronjob\']->value;?>
/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>
" alt=""><?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';?>

<?php echo '/*%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/<?php $_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>/*/%%SmartyNocache:20871226266ab87abc0040c0_79284733%%*/';
}
/* {block "content"} */
class Block_10732274566ab87abc11ac80_03654755 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_10732274566ab87abc11ac80_03654755',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.full.tpl" =============================*/
}
