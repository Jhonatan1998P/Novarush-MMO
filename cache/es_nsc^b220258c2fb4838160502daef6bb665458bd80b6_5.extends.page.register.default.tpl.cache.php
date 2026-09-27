<?php
/* Smarty version 3.1.36, created on 2026-09-27 03:23:45
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\page.register.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab87021c06444_35047223',
  'has_nocache_code' => true,
  'file_dependency' => 
  array (
    'b220258c2fb4838160502daef6bb665458bd80b6' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\login\\page.register.default.tpl',
      1 => 1642351636,
      2 => 'extends',
    ),
    'ee87ccd5e59a968aa6f51a5710cc690770ff47a3' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\login\\page.register.default.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
    'f36216d1ed784d747782b825f2cb68023653525e' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\login\\layout.normal.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
    '0347b68329ccec2ab68d53725d81da040d74308a' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\login\\main.header.tpl',
      1 => 1790387708,
      2 => 'file',
    ),
    '06128c7e205a4024a4ca44f3c78cf06e8e8869d6' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\login\\main.navigation.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
    '8e88b826d977dfa60924d930f718aa24f9b963f5' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\login\\main.footer.tpl',
      1 => 1790387177,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:page.register.default.tpl' => 1,
    'file:layout.normal.tpl' => 1,
    'file:main.header.tpl' => 1,
    'file:main.navigation.tpl' => 1,
    'file:main.footer.tpl' => 1,
  ),
),false)) {
function content_6ab87021c06444_35047223 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
$_smarty_tpl->compiled->nocache_hash = '6757476586ab87021b8a875_91361282';
$_smarty_tpl->_subTemplateRender('file:page.register.default.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, 'ee87ccd5e59a968aa6f51a5710cc690770ff47a3', 'content_6ab87021bb30d3_52101886');
$_smarty_tpl->inheritance->endChild($_smarty_tpl);
$_smarty_tpl->_subTemplateRender('file:layout.normal.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, 'f36216d1ed784d747782b825f2cb68023653525e', 'content_6ab87021be0717_17797572');
}
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\page.register.default.tpl" =============================*/
function content_6ab87021bb30d3_52101886 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '6757476586ab87021b8a875_91361282';
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_11456979766ab87021bb9d45_00926925', "title");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_19481993596ab87021bc0169_55351894', "content");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_1244435936ab87021bdebc0_14787038', "script");
}
/* {block "title"} */
class Block_11456979766ab87021bb9d45_00926925 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_11456979766ab87021bb9d45_00926925',
  ),
);
public $prepend = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['LNG']->value['nav_register'];
}
}
/* {/block "title"} */
/* {block "content"} */
class Block_19481993596ab87021bc0169_55351894 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_19481993596ab87021bc0169_55351894',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php $_smarty_tpl->_checkPlugins(array(0=>array(\'file\'=>\'C:\\\\Users\\\\Ortega\\\\Downloads\\\\OGAME\\\\ogame\\\\includes\\\\libs\\\\Smarty\\\\plugins\\\\function.html_options.php\',\'function\'=>\'smarty_function_html_options\',),));
?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';
$_smarty_tpl->cached->hashes['6757476586ab87021b8a875_91361282'] = true;
?>

<main role="main" class="container">
    <div class="card mb-3">
        <h5 class="card-header"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_register'];?>
</h5>
        <div class="card-body">
			<form id="registerForm" method="post" action="index.php?page=register" data-action="index.php?page=register">
				<input type="hidden" value="send" name="mode">
				<input type="hidden" value="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'externalAuth\']->value[\'account\'];?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
" name="externalAuth[account]">
				<input type="hidden" value="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'externalAuth\']->value[\'method\'];?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
" name="externalAuth[method]">
				<input type="hidden" value="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'referralData\']->value[\'id\'];?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
" name="referralID">
				<div class="form-group">
					<label for="universe"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['chose_a_uni'];?>
</label>
					<select name="uni" id="universe" class="form-control changeAction"><?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo smarty_function_html_options(array(\'options\'=>$_smarty_tpl->tpl_vars[\'universeSelect\']->value,\'selected\'=>$_smarty_tpl->tpl_vars[\'UNI\']->value),$_smarty_tpl);?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
</select>
					<?php if (!empty($_smarty_tpl->tpl_vars['error']->value['uni'])) {?><span class="error errorUni"></span><?php }?>
				</div>
				<div class="form-group">
					<label for="username"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerUsername'];?>
</label>
					<input type="text" class="form-control" name="username" id="username" placeholder="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerUsername'];?>
">
					<?php if (!empty($_smarty_tpl->tpl_vars['error']->value['username'])) {?><span class="error errorUsername"></span><?php }?>
					<span class="inputDesc"><small><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerUsernameDesc'];?>
</small></span>
				</div>
				<div class="form-group">
					<label for="password"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerPassword'];?>
</label>
					<input type="password" class="form-control" name="password" id="password" placeholder="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerPassword'];?>
">
					<?php if (!empty($_smarty_tpl->tpl_vars['error']->value['password'])) {?><span class="error errorPassword"></span><?php }?>
					<span class="inputDesc"><small><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerPasswordDesc'];?>
</small></span>
				</div>
				<div class="form-group">
					<label for="passwordReplay"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerPasswordReplay'];?>
</label>
					<input type="password" class="form-control" name="passwordReplay" id="passwordReplay" placeholder="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerPasswordReplay'];?>
">
					<?php if (!empty($_smarty_tpl->tpl_vars['error']->value['passwordReplay'])) {?><span class="error errorPasswordReplay"></span><?php }?>
					<span class="inputDesc"><small><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerPasswordReplayDesc'];?>
</small></span>
				</div>
				<div class="form-group">
					<label for="email"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerEmail'];?>
</label>
					<input type="email" class="form-control" name="email" id="email" placeholder="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerEmail'];?>
">
					<?php if (!empty($_smarty_tpl->tpl_vars['error']->value['password'])) {?><span class="error errorEmail"></span><?php }?>
					<span class="inputDesc"><small><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerEmailDesc'];?>
</small></span>
				</div>
				<div class="form-group">
					<label for="emailReplay"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerEmailReplay'];?>
</label>
					<input type="email" class="form-control" name="emailReplay" id="emailReplay" placeholder="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerEmailReplay'];?>
">
					<?php if (!empty($_smarty_tpl->tpl_vars['error']->value['emailReplay'])) {?><span class="error errorEmailReplay"></span><?php }?>
					<span class="inputDesc"><small><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerEmailReplayDesc'];?>
</small></span>
				</div>
				<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php if (count($_smarty_tpl->tpl_vars[\'languages\']->value) > 1) {?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

					<div class="form-group">
					<label for="language"><?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'language\'];?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
</label>
					<select name="lang" id="language" class="form-control"><?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo smarty_function_html_options(array(\'options\'=>$_smarty_tpl->tpl_vars[\'languages\']->value,\'selected\'=>$_smarty_tpl->tpl_vars[\'lang\']->value),$_smarty_tpl);?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
</select>
					<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'error\']->value[\'language\'])) {?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
<span class="error errorLanguage"></span><?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php }?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

				</div>
				<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php }?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

				<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php if (!empty($_smarty_tpl->tpl_vars[\'referralData\']->value[\'name\'])) {?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

				<div class="form-group">
					<label for="username"><?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'registerReferral\'];?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
</label>
					<span class="inputDesc"><?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'referralData\']->value[\'name\'];?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
</span>
				</div>
				<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php }?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

				<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php if ($_smarty_tpl->tpl_vars[\'recaptchaEnable\']->value) {?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

                <div class="rowForm" id="captchaRow">
					<div>
						<label><?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'registerCaptcha\'];?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
</label>
						<!--<span class="inputDesc"><?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'registerCaptchaDesc\'];?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
</span>-->
						<div class="g-recaptcha" data-sitekey="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'recaptchaPublicKey\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
"></div>
					</div>
				</div>
				<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php }?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

				<div class="form-group checkbox">
					<input type="checkbox" name="rules" id="rules" value="1"> <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'registerRulesDesc\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

					<?php if (!empty($_smarty_tpl->tpl_vars['error']->value['rules'])) {?><span class="error errorRules"></span><?php }?>
				</div>
				<div style="text-align: right;">
					<button type="submit" class="btn btn-danger"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['buttonRegister'];?>
</button>
				</div>
			</form>
        </div>
    </div>
</main>  
<?php
}
}
/* {/block "content"} */
/* {block "script"} */
class Block_1244435936ab87021bdebc0_14787038 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_1244435936ab87021bdebc0_14787038',
  ),
);
public $append = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['6757476586ab87021b8a875_91361282'] = true;
?>

<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php if ($_smarty_tpl->tpl_vars[\'recaptchaEnable\']->value) {?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

<?php echo '<script'; ?>
 type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl=<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
"><?php echo '</script'; ?>
>
<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php }?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

<?php echo '<script'; ?>
 type="text/javascript" src="scripts/login/register.js"><?php echo '</script'; ?>
>
<?php
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\page.register.default.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.header.tpl" =============================*/
function content_6ab87021be2517_68912532 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '6757476586ab87021b8a875_91361282';
?>
<!DOCTYPE html>

<!--[if lt IE 7 ]> <html lang="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
" class="no-js"> <!--<![endif]-->
<head>
    <!--title-->
    <link rel="manifest" href="./manifest.json">
    <title><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_2529540176ab87021be4574_52292164', "title");
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
    <!--bootstrap-->
	<link rel="stylesheet" type="text/css" href="styles/resource/css/base/bootstrap.min_4.6.0.css">
    <link rel="stylesheet" type="text/css" href="styles/resource/css/base/my_bootstrap.css">
    <!--bootstrap requirements-->
    <?php echo '<script'; ?>
 type="text/javascript" src="scripts/base/jquery.js"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 type="text/javascript" src="scripts/base/jquery.ui.js"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 type="text/javascript" src="scripts/base/popper.min.js"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 type="text/javascript" src="scripts/base/bootstrap.min.js"><?php echo '</script'; ?>
>
    <!--bootstrap adaptation to the device-->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!--bootstrap carousel-->
    <?php echo '<script'; ?>
>
        $(function() {
            $('.carousel').each(function(){
                $(this).carousel({
                    interval: false
                });
            });
        });
    <?php echo '</script'; ?>
>
    <!--style-->
    <link rel="stylesheet" type="text/css" href="styles/resource/css/login/login.css">
    <!--fancybox-->
    <link rel="stylesheet" type="text/css" href="styles/resource/css/base/jquery.fancybox_3.5.7.css">
    <?php echo '<script'; ?>
 type="text/javascript" src="scripts/base/jquery.fancybox.js"><?php echo '</script'; ?>
>
    <!--jquery cookie-->
    <?php echo '<script'; ?>
 type="text/javascript" src="scripts/base/jquery.cookie.js"><?php echo '</script'; ?>
>
    <!--script-->
    <?php echo '<script'; ?>
 src="scripts/login/main.js"><?php echo '</script'; ?>
>
	<?php echo '<script'; ?>
><?php if ((isset($_smarty_tpl->tpl_vars['code']->value))) {?>var loginError = <?php echo json_encode($_smarty_tpl->tpl_vars['code']->value);?>
;<?php }
echo '</script'; ?>
>
	<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_14640637906ab87021beafd3_07092119', "script");
?>
	
</head>
<body id="<?php echo (($tmp = @htmlspecialchars($_GET['page']))===null||$tmp==='' ? 'overview' : $tmp);?>
" class="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'bodyclass\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
">
<div id="tooltip" class="tip"></div><?php
}
/* {block "title"} */
class Block_2529540176ab87021be4574_52292164 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_2529540176ab87021be4574_52292164',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['6757476586ab87021b8a875_91361282'] = true;
?>
 - <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'gameName\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';
}
}
/* {/block "title"} */
/* {block "script"} */
class Block_14640637906ab87021beafd3_07092119 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_14640637906ab87021beafd3_07092119',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.header.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.navigation.tpl" =============================*/
function content_6ab87021bf2668_59960648 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->compiled->nocache_hash = '6757476586ab87021b8a875_91361282';
?>
<nav class="navbar navbar-expand-md  fixed-top bg-dark navbar-dark">
    <div class="container-xl">
        <a class="navbar-brand" href="#">
            <img src="./styles/resource/images/icons/192.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
            <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'gameName\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExample07XL" aria-controls="navbarsExample07XL" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarsExample07XL">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_index'];?>
</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=board"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_forum'];?>
</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=news"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_news'];?>
</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=rules"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_rules'];?>
</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=battleHall"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_battlehall'];?>
</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=banList"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_banlist'];?>
</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=disclamer"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_disclamer'];?>
</a>
                </li>
                <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php if (count($_smarty_tpl->tpl_vars[\'languages\']->value) > 1) {?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdown07XL" data-toggle="dropdown" aria-expanded="false"><?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'language\'];?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
</a>
                    <div class="dropdown-menu" aria-labelledby="dropdown07XL">
                    <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'languages\']->value, \'langName\', false, \'langKey\');
$_smarty_tpl->tpl_vars[\'langName\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'langKey\']->value => $_smarty_tpl->tpl_vars[\'langName\']->value) {
$_smarty_tpl->tpl_vars[\'langName\']->do_else = false;
?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

                        <a class="dropdown-item" href="?lang=<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'langKey\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
" rel="alternate" hreflang="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'langKey\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
" title="<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'langName\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
"><?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'langName\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
</a>
                    <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

                    </div>
                </li>
                <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php }?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

            </ul>
        </div>
    </div>
</nav><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.navigation.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.footer.tpl" =============================*/
function content_6ab87021c01830_85109955 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->compiled->nocache_hash = '6757476586ab87021b8a875_91361282';
?>
<footer>
    <div class="container">
		<p class="float-right"><a href="#"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['footer_up'];?>
</a></p>
		<p>© 2026 <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'gameName\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
 por LORDA1998</p>
	</div>
</footer>
<div id="dialog" style="display:none;"></div>
<?php echo '<script'; ?>
>
var LoginConfig = {
    'isMultiUniverse': <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'isMultiUniverse\']->value);?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
,
	'unisWildcast': <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'unisWildcast\']->value);?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
,
	'referralEnable' : <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'referralEnable\']->value);?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
,
	'basePath' : <?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'basepath\']->value);?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

};
<?php echo '</script'; ?>
>
<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php if ($_smarty_tpl->tpl_vars[\'analyticsEnable\']->value) {?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

<?php echo '<script'; ?>
 type="text/javascript" src="http://www.google-analytics.com/ga.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 type="text/javascript">
try{
var pageTracker = _gat._getTracker("<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php echo $_smarty_tpl->tpl_vars[\'analyticsUID\']->value;?>
/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>
");
pageTracker._trackPageview();
} catch(err) {}<?php echo '</script'; ?>
>
<?php echo '/*%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/<?php }?>/*/%%SmartyNocache:6757476586ab87021b8a875_91361282%%*/';?>

</body>
</html><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.footer.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\layout.normal.tpl" =============================*/
function content_6ab87021be0717_17797572 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '6757476586ab87021b8a875_91361282';
$_smarty_tpl->_subTemplateRender("file:main.header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, '0347b68329ccec2ab68d53725d81da040d74308a', 'content_6ab87021be2517_68912532');
$_smarty_tpl->_subTemplateRender("file:main.navigation.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, '06128c7e205a4024a4ca44f3c78cf06e8e8869d6', 'content_6ab87021bf2668_59960648');
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_18596496346ab87021c001b2_57939299', "content");
?>

<?php
$_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, '8e88b826d977dfa60924d930f718aa24f9b963f5', 'content_6ab87021c01830_85109955');
}
/* {block "content"} */
class Block_18596496346ab87021c001b2_57939299 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_18596496346ab87021c001b2_57939299',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\layout.normal.tpl" =============================*/
}
