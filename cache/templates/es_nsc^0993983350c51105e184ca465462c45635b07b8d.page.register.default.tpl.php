<?php
/* Smarty version 3.1.36, created on 2026-09-27 03:23:46
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\page.register.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab87022460993_89269691',
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
  'cache_lifetime' => 604800,
),true)) {
function content_6ab87022460993_89269691 (Smarty_Internal_Template $_smarty_tpl) {
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
    <link rel="manifest" href="./manifest.json">
    <title>Registro - <?php echo $_smarty_tpl->tpl_vars['gameName']->value;?>
</title>	
    <meta name="generator" content="NovaRush">
	<meta name="keywords" content="NovaRush">
	<meta name="description" content="NovaRush Browsergame by LORDA1998">
    <!--favicon-->
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
    <!--goto refresh-->
        <!--content-type-->
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <!--bootstrap-->
	<link rel="stylesheet" type="text/css" href="styles/resource/css/base/bootstrap.min_4.6.0.css">
    <link rel="stylesheet" type="text/css" href="styles/resource/css/base/my_bootstrap.css">
    <!--bootstrap requirements-->
    <script type="text/javascript" src="scripts/base/jquery.js"></script>
    <script type="text/javascript" src="scripts/base/jquery.ui.js"></script>
    <script type="text/javascript" src="scripts/base/popper.min.js"></script>
    <script type="text/javascript" src="scripts/base/bootstrap.min.js"></script>
    <!--bootstrap adaptation to the device-->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!--bootstrap carousel-->
    <script>
        $(function() {
            $('.carousel').each(function(){
                $(this).carousel({
                    interval: false
                });
            });
        });
    </script>
    <!--style-->
    <link rel="stylesheet" type="text/css" href="styles/resource/css/login/login.css">
    <!--fancybox-->
    <link rel="stylesheet" type="text/css" href="styles/resource/css/base/jquery.fancybox_3.5.7.css">
    <script type="text/javascript" src="scripts/base/jquery.fancybox.js"></script>
    <!--jquery cookie-->
    <script type="text/javascript" src="scripts/base/jquery.cookie.js"></script>
    <!--script-->
    <script src="scripts/login/main.js"></script>
	<script></script>
	
<?php if ($_smarty_tpl->tpl_vars['recaptchaEnable']->value) {?>
<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl=<?php echo $_smarty_tpl->tpl_vars['lang']->value;?>
"></script>
<?php }?>
<script type="text/javascript" src="scripts/login/register.js"></script>
	
</head>
<body id="register" class="<?php echo $_smarty_tpl->tpl_vars['bodyclass']->value;?>
">
<div id="tooltip" class="tip"></div><nav class="navbar navbar-expand-md  fixed-top bg-dark navbar-dark">
    <div class="container-xl">
        <a class="navbar-brand" href="#">
            <img src="./styles/resource/images/icons/192.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
            <?php echo $_smarty_tpl->tpl_vars['gameName']->value;?>

        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExample07XL" aria-controls="navbarsExample07XL" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarsExample07XL">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=board">Foro</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=news">Noticias</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=rules">Reglas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=battleHall">Salón de batalla</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=banList">Sala prohibida</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=disclamer">Contactos</a>
                </li>
                <?php if (count($_smarty_tpl->tpl_vars['languages']->value) > 1) {?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdown07XL" data-toggle="dropdown" aria-expanded="false"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['language'];?>
</a>
                    <div class="dropdown-menu" aria-labelledby="dropdown07XL">
                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['languages']->value, 'langName', false, 'langKey');
$_smarty_tpl->tpl_vars['langName']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['langKey']->value => $_smarty_tpl->tpl_vars['langName']->value) {
$_smarty_tpl->tpl_vars['langName']->do_else = false;
?>
                        <a class="dropdown-item" href="?lang=<?php echo $_smarty_tpl->tpl_vars['langKey']->value;?>
" rel="alternate" hreflang="<?php echo $_smarty_tpl->tpl_vars['langKey']->value;?>
" title="<?php echo $_smarty_tpl->tpl_vars['langName']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['langName']->value;?>
</a>
                    <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                    </div>
                </li>
                <?php }?>
            </ul>
        </div>
    </div>
</nav><?php $_smarty_tpl->_checkPlugins(array(0=>array('file'=>'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\includes\\libs\\Smarty\\plugins\\function.html_options.php','function'=>'smarty_function_html_options',),));
?>
<main role="main" class="container">
    <div class="card mb-3">
        <h5 class="card-header">Registro</h5>
        <div class="card-body">
			<form id="registerForm" method="post" action="index.php?page=register" data-action="index.php?page=register">
				<input type="hidden" value="send" name="mode">
				<input type="hidden" value="<?php echo $_smarty_tpl->tpl_vars['externalAuth']->value['account'];?>
" name="externalAuth[account]">
				<input type="hidden" value="<?php echo $_smarty_tpl->tpl_vars['externalAuth']->value['method'];?>
" name="externalAuth[method]">
				<input type="hidden" value="<?php echo $_smarty_tpl->tpl_vars['referralData']->value['id'];?>
" name="referralID">
				<div class="form-group">
					<label for="universe">chose_a_uni</label>
					<select name="uni" id="universe" class="form-control changeAction"><?php echo smarty_function_html_options(array('options'=>$_smarty_tpl->tpl_vars['universeSelect']->value,'selected'=>$_smarty_tpl->tpl_vars['UNI']->value),$_smarty_tpl);?>
</select>
									</div>
				<div class="form-group">
					<label for="username">Nombre de usuario</label>
					<input type="text" class="form-control" name="username" id="username" placeholder="Nombre de usuario">
										<span class="inputDesc"><small>Su nombre de usuario debe tener al menos 3 caracteres y no más de 25. Puede consistir en números, letras, _, -,. y solo espacios. </small></span>
				</div>
				<div class="form-group">
					<label for="password">Contraseña</label>
					<input type="password" class="form-control" name="password" id="password" placeholder="Contraseña">
										<span class="inputDesc"><small>Su contraseña debe tener al menos 6 caracteres.</small></span>
				</div>
				<div class="form-group">
					<label for="passwordReplay">Confirmar contraseña</label>
					<input type="password" class="form-control" name="passwordReplay" id="passwordReplay" placeholder="Confirmar contraseña">
										<span class="inputDesc"><small>Repita su contraseña.</small></span>
				</div>
				<div class="form-group">
					<label for="email">Correo electrónico</label>
					<input type="email" class="form-control" name="email" id="email" placeholder="Correo electrónico">
										<span class="inputDesc"><small>Ingrese su dirección de correo electrónico.</small></span>
				</div>
				<div class="form-group">
					<label for="emailReplay">Confirmar correo electrónico</label>
					<input type="email" class="form-control" name="emailReplay" id="emailReplay" placeholder="Confirmar correo electrónico">
										<span class="inputDesc"><small>Por favor ingrese su dirección de correo electrónico una vez más por seguridad.</small></span>
				</div>
				<?php if (count($_smarty_tpl->tpl_vars['languages']->value) > 1) {?>
					<div class="form-group">
					<label for="language"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['language'];?>
</label>
					<select name="lang" id="language" class="form-control"><?php echo smarty_function_html_options(array('options'=>$_smarty_tpl->tpl_vars['languages']->value,'selected'=>$_smarty_tpl->tpl_vars['lang']->value),$_smarty_tpl);?>
</select>
					<?php if (!empty($_smarty_tpl->tpl_vars['error']->value['language'])) {?><span class="error errorLanguage"></span><?php }?>
				</div>
				<?php }?>
				<?php if (!empty($_smarty_tpl->tpl_vars['referralData']->value['name'])) {?>
				<div class="form-group">
					<label for="username"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerReferral'];?>
</label>
					<span class="inputDesc"><?php echo $_smarty_tpl->tpl_vars['referralData']->value['name'];?>
</span>
				</div>
				<?php }?>
				<?php if ($_smarty_tpl->tpl_vars['recaptchaEnable']->value) {?>
                <div class="rowForm" id="captchaRow">
					<div>
						<label><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerCaptcha'];?>
</label>
						<!--<span class="inputDesc"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['registerCaptchaDesc'];?>
</span>-->
						<div class="g-recaptcha" data-sitekey="<?php echo $_smarty_tpl->tpl_vars['recaptchaPublicKey']->value;?>
"></div>
					</div>
				</div>
				<?php }?>
				<div class="form-group checkbox">
					<input type="checkbox" name="rules" id="rules" value="1"> <?php echo $_smarty_tpl->tpl_vars['registerRulesDesc']->value;?>

									</div>
				<div style="text-align: right;">
					<button type="submit" class="btn btn-danger">¡Regístrese ahora!</button>
				</div>
			</form>
        </div>
    </div>
</main>  

<footer>
    <div class="container">
		<p class="float-right"><a href="#">Volver al principio</a></p>
		<p>© 2026 <?php echo $_smarty_tpl->tpl_vars['gameName']->value;?>
 por LORDA1998</p>
	</div>
</footer>
<div id="dialog" style="display:none;"></div>
<script>
var LoginConfig = {
    'isMultiUniverse': <?php echo json_encode($_smarty_tpl->tpl_vars['isMultiUniverse']->value);?>
,
	'unisWildcast': <?php echo json_encode($_smarty_tpl->tpl_vars['unisWildcast']->value);?>
,
	'referralEnable' : <?php echo json_encode($_smarty_tpl->tpl_vars['referralEnable']->value);?>
,
	'basePath' : <?php echo json_encode($_smarty_tpl->tpl_vars['basepath']->value);?>

};
</script>
<?php if ($_smarty_tpl->tpl_vars['analyticsEnable']->value) {?>
<script type="text/javascript" src="http://www.google-analytics.com/ga.js"></script>
<script type="text/javascript">
try{
var pageTracker = _gat._getTracker("<?php echo $_smarty_tpl->tpl_vars['analyticsUID']->value;?>
");
pageTracker._trackPageview();
} catch(err) {}</script>
<?php }?>
</body>
</html><?php }
}
