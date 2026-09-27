<?php
/* Smarty version 3.1.36, created on 2026-09-27 02:59:05
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\page.index.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab86a5995a649_58113203',
  'has_nocache_code' => true,
  'file_dependency' => 
  array (
    '24b40b57473c4a0f43eedbccdd2c4b06bb36a03e' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\login\\page.index.default.tpl',
      1 => 1760141872,
      2 => 'extends',
    ),
    'eea3800bc8a4ab241607598200e7714af5a3b4c7' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\login\\page.index.default.tpl',
      1 => 1760141872,
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
function content_6ab86a5995a649_58113203 (Smarty_Internal_Template $_smarty_tpl) {
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
    <title>Home - <?php echo $_smarty_tpl->tpl_vars['gameName']->value;?>
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
	<script><?php if ((isset($_smarty_tpl->tpl_vars['code']->value))) {?>var loginError = <?php echo json_encode($_smarty_tpl->tpl_vars['code']->value);?>
;<?php }?></script>
	
<script><?php if ($_smarty_tpl->tpl_vars['code']->value) {?>alert(<?php echo json_encode($_smarty_tpl->tpl_vars['code']->value);?>
);<?php }?></script>
	
</head>
<body id="overview" class="<?php echo $_smarty_tpl->tpl_vars['bodyclass']->value;?>
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
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=board">Forum</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=news">News</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=rules">Rules</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=battleHall">Battle Hall</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=banList">Banned Hall</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=disclamer">Contacts</a>
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
    <div class="jumbotron mb-3">
        <h1><?php echo $_smarty_tpl->tpl_vars['descHeader']->value;?>
</h1>
        <p class="lead"><?php echo $_smarty_tpl->tpl_vars['descText']->value;?>
</p>
        <a class="btn btn-lg btn-primary" href="./index.php?page=register" role="button">Register Now!</a>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <form id="login" name="login" action="./index.php?page=login" data-action="index.php?page=login" method="post">
                        <div class="form-group">
                            <label for="universe">Universe</label>
                            <select name="uni" id="universe" class="form-control changeAction"  style="color:gray" ><?php echo smarty_function_html_options(array('options'=>$_smarty_tpl->tpl_vars['universeSelect']->value,'selected'=>$_smarty_tpl->tpl_vars['UNI']->value),$_smarty_tpl);?>
</select>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" name="username" id="username" placeholder="Username">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" name="password" id="password" placeholder="Password">
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    <?php if ($_smarty_tpl->tpl_vars['facebookEnable']->value) {?><a href="#" data-href="index.php?page=externalAuth&method=facebook" class="fb_login"><img src="./styles/resource/images/facebook/fb-connect-large.png" alt=""></a><?php }?><!-- http://b.static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif -->
				    <div style="text-align: center">
						<a href="./index.php?page=register">Register Now!</a> <?php if ($_smarty_tpl->tpl_vars['mailEnable']->value) {?>- <a href="./index.php?page=lostPassword"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['buttonLostPassword'];?>
</a><?php }?>
						<br>
						<span class="small"><?php echo $_smarty_tpl->tpl_vars['loginInfo']->value;?>
</span>
					</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
               
                <div class="card-body">
                    <h5 class="card-title">infose1<?php echo $_smarty_tpl->tpl_vars['infogame']->value;?>
</h5>
                    <div class="list-group overflow-auto" style="max-height: 415px;">
                         <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['infoList']->value, 'infoRow');
$_smarty_tpl->tpl_vars['infoRow']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['infoRow']->value) {
$_smarty_tpl->tpl_vars['infoRow']->do_else = false;
?>
                       <?php echo $_smarty_tpl->tpl_vars['LNG']->value['infose2'];?>
<span style="color:orange;"><b><?php echo $_smarty_tpl->tpl_vars['infoRow']->value['name'];?>
</span></b>
                             <?php echo $_smarty_tpl->tpl_vars['LNG']->value['infose3'];?>
 <?php if ($_smarty_tpl->tpl_vars['infoRow']->value['disable'] == 1) {?><span style="color:lime;"><b>ONLINE</b></span><?php } else { ?><span style="color:red;"><b>OFFLINE</b></span><?php }?>
                            <?php echo $_smarty_tpl->tpl_vars['LNG']->value['infose4'];?>
<span style="color:red;"><b><?php echo $_smarty_tpl->tpl_vars['infoRow']->value['player2'];?>
/<?php echo $_smarty_tpl->tpl_vars['infoRow']->value['player'];?>
</b></span>
                          -------------------------------<br>
                          <?php
}
if ($_smarty_tpl->tpl_vars['infoRow']->do_else) {
?>
        <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                       </div>
                    <p class="card-text">infose5</p>
                    <div style="text-align: right">
                        <a href="/index.php?page=screens">
                            <button type="button" class="btn btn-success">Screenshots</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div id="carousel_2" class="carousel slide carousel-fade img-thumbnail bg-primary border border-primary" data-ride="carousel">
                    <ol class="carousel-indicators">
                        <li data-target="#carousel_2" data-slide-to="0" class="active"></li>
                        <li data-target="#carousel_2" data-slide-to="1"></li>
                        <li data-target="#carousel_2" data-slide-to="2"></li>
                        <li data-target="#carousel_2" data-slide-to="3"></li>
                    </ol>
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="./styles/resource/images/login/carousel/5.jpg" class="d-block w-100" alt="...">
                        </div>
                        <div class="carousel-item">
                            <img src="./styles/resource/images/login/carousel/6.jpg" class="d-block w-100" alt="...">
                        </div>
                        <div class="carousel-item">
                            <img src="./styles/resource/images/login/carousel/7.jpg" class="d-block w-100" alt="...">
                        </div>
                        <div class="carousel-item">
                            <img src="./styles/resource/images/login/carousel/8.jpg" class="d-block w-100" alt="...">
                        </div>
                    </div>
                    <a class="carousel-control-prev" href="#carousel_2" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only"></span>
                    </a>
                    <a class="carousel-control-next" href="#carousel_2" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only"></span>
                    </a>
                </div>
                <div class="card-body">
                    <h5 class="card-title">News</h5>
                    <?php if ($_smarty_tpl->tpl_vars['is_news']->value) {?><p class="card-text"><?php echo $_smarty_tpl->tpl_vars['news']->value;?>
 <?php echo $_smarty_tpl->tpl_vars['ver']->value;?>
</p><?php }?>
                    <div style="text-align: right">
                        <a href="/index.php?page=news">
                            <button type="button" class="btn btn-success">News</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>


<footer>
    <div class="container">
		<p class="float-right"><a href="#">Back to the top</a></p>
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
