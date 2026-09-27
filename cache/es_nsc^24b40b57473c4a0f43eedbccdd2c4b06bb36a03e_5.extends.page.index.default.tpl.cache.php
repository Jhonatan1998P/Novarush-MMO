<?php
/* Smarty version 3.1.36, created on 2026-09-27 02:59:03
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\page.index.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab86a57f26aa4_61399708',
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
  'includes' => 
  array (
    'file:page.index.default.tpl' => 1,
    'file:layout.normal.tpl' => 1,
    'file:main.header.tpl' => 1,
    'file:main.navigation.tpl' => 1,
    'file:main.footer.tpl' => 1,
  ),
),false)) {
function content_6ab86a57f26aa4_61399708 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
$_smarty_tpl->compiled->nocache_hash = '11582316736ab86a57eb7b45_99869611';
$_smarty_tpl->_subTemplateRender('file:page.index.default.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, 'eea3800bc8a4ab241607598200e7714af5a3b4c7', 'content_6ab86a57ede337_88589461');
$_smarty_tpl->inheritance->endChild($_smarty_tpl);
$_smarty_tpl->_subTemplateRender('file:layout.normal.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, 'f36216d1ed784d747782b825f2cb68023653525e', 'content_6ab86a57f0c150_48374593');
}
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\page.index.default.tpl" =============================*/
function content_6ab86a57ede337_88589461 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '11582316736ab86a57eb7b45_99869611';
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_11548900376ab86a57ee4778_98829834', "title");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_16312887166ab86a57eea315_86048913', "content");
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_4108818676ab86a57f06bf2_74092857', "script");
?>


<?php
}
/* {block "title"} */
class Block_11548900376ab86a57ee4778_98829834 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_11548900376ab86a57ee4778_98829834',
  ),
);
public $prepend = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['LNG']->value['nav_index'];
}
}
/* {/block "title"} */
/* {block "content"} */
class Block_16312887166ab86a57eea315_86048913 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_16312887166ab86a57eea315_86048913',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php $_smarty_tpl->_checkPlugins(array(0=>array(\'file\'=>\'C:\\\\Users\\\\Ortega\\\\Downloads\\\\OGAME\\\\ogame\\\\includes\\\\libs\\\\Smarty\\\\plugins\\\\function.html_options.php\',\'function\'=>\'smarty_function_html_options\',),));
?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';
$_smarty_tpl->cached->hashes['11582316736ab86a57eb7b45_99869611'] = true;
?>

<main role="main" class="container">
    <div class="jumbotron mb-3">
        <h1><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'descHeader\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</h1>
        <p class="lead"><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'descText\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</p>
        <a class="btn btn-lg btn-primary" href="./index.php?page=register" role="button"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['buttonRegister'];?>
</a>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <form id="login" name="login" action="./index.php?page=login" data-action="index.php?page=login" method="post">
                        <div class="form-group">
                            <label for="universe"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['universe'];?>
</label>
                            <select name="uni" id="universe" class="form-control changeAction"  style="color:gray" ><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo smarty_function_html_options(array(\'options\'=>$_smarty_tpl->tpl_vars[\'universeSelect\']->value,\'selected\'=>$_smarty_tpl->tpl_vars[\'UNI\']->value),$_smarty_tpl);?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</select>
                        </div>
                        <div class="form-group">
                            <label for="username"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['loginUsername'];?>
</label>
                            <input type="text" class="form-control" name="username" id="username" placeholder="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['loginUsername'];?>
">
                        </div>
                        <div class="form-group">
                            <label for="password"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['loginPassword'];?>
</label>
                            <input type="password" class="form-control" name="password" id="password" placeholder="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['loginPassword'];?>
">
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" class="btn btn-primary"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['loginButton'];?>
</button>
                        </div>
                    </form>
                    <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php if ($_smarty_tpl->tpl_vars[\'facebookEnable\']->value) {?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
<a href="#" data-href="index.php?page=externalAuth&method=facebook" class="fb_login"><img src="./styles/resource/images/facebook/fb-connect-large.png" alt=""></a><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php }?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
<!-- http://b.static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif -->
				    <div style="text-align: center">
						<a href="./index.php?page=register"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['buttonRegister'];?>
</a> <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php if ($_smarty_tpl->tpl_vars[\'mailEnable\']->value) {?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
- <a href="./index.php?page=lostPassword"><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'buttonLostPassword\'];?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</a><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php }?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

						<br>
						<span class="small"><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'loginInfo\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</span>
					</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
               
                <div class="card-body">
                    <h5 class="card-title"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['infose1'];
echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'infogame\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</h5>
                    <div class="list-group overflow-auto" style="max-height: 415px;">
                         <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'infoList\']->value, \'infoRow\');
$_smarty_tpl->tpl_vars[\'infoRow\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'infoRow\']->value) {
$_smarty_tpl->tpl_vars[\'infoRow\']->do_else = false;
?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

                       <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'infose2\'];?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
<span style="color:orange;"><b><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'infoRow\']->value[\'name\'];?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</span></b>
                             <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'infose3\'];?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
 <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php if ($_smarty_tpl->tpl_vars[\'infoRow\']->value[\'disable\'] == 1) {?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
<span style="color:lime;"><b>ONLINE</b></span><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php } else { ?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
<span style="color:red;"><b>OFFLINE</b></span><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php }?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

                            <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'infose4\'];?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
<span style="color:red;"><b><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'infoRow\']->value[\'player2\'];?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
/<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'infoRow\']->value[\'player\'];?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</b></span>
                          -------------------------------<br>
                          <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php
}
if ($_smarty_tpl->tpl_vars[\'infoRow\']->do_else) {
?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

        <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

                       </div>
                    <p class="card-text"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['infose5'];?>
</p>
                    <div style="text-align: right">
                        <a href="/index.php?page=screens">
                            <button type="button" class="btn btn-success"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_screens'];?>
</button>
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
                        <span class="sr-only"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['previous_s'];?>
</span>
                    </a>
                    <a class="carousel-control-next" href="#carousel_2" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['next_s'];?>
</span>
                    </a>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_news'];?>
</h5>
                    <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php if ($_smarty_tpl->tpl_vars[\'is_news\']->value) {?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
<p class="card-text"><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'news\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
 <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'ver\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</p><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php }?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

                    <div style="text-align: right">
                        <a href="/index.php?page=news">
                            <button type="button" class="btn btn-success"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['nav_news'];?>
</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
}
}
/* {/block "content"} */
/* {block "script"} */
class Block_4108818676ab86a57f06bf2_74092857 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_4108818676ab86a57f06bf2_74092857',
  ),
);
public $append = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['11582316736ab86a57eb7b45_99869611'] = true;
?>

<?php echo '<script'; ?>
><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php if ($_smarty_tpl->tpl_vars[\'code\']->value) {?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
alert(<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'code\']->value);?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
);<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php }?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';
echo '</script'; ?>
>
<?php
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\page.index.default.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.header.tpl" =============================*/
function content_6ab86a57f0ddf2_17294210 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '11582316736ab86a57eb7b45_99869611';
?>
<!DOCTYPE html>

<!--[if lt IE 7 ]> <html lang="<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'lang\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
" class="no-js"> <!--<![endif]-->
<head>
    <!--title-->
    <link rel="manifest" href="./manifest.json">
    <title><?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_21080116756ab86a57f0fea3_94851129', "title");
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
><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php if ((isset($_smarty_tpl->tpl_vars[\'code\']->value))) {?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
var loginError = <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'code\']->value);?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
;<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php }?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';
echo '</script'; ?>
>
	<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_12423398986ab86a57f12cd9_53119560', "script");
?>
	
</head>
<body id="<?php echo (($tmp = @htmlspecialchars($_GET['page']))===null||$tmp==='' ? 'overview' : $tmp);?>
" class="<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'bodyclass\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
">
<div id="tooltip" class="tip"></div><?php
}
/* {block "title"} */
class Block_21080116756ab86a57f0fea3_94851129 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'title' => 
  array (
    0 => 'Block_21080116756ab86a57f0fea3_94851129',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['11582316736ab86a57eb7b45_99869611'] = true;
?>
 - <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'gameName\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';
}
}
/* {/block "title"} */
/* {block "script"} */
class Block_12423398986ab86a57f12cd9_53119560 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'script' => 
  array (
    0 => 'Block_12423398986ab86a57f12cd9_53119560',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "script"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.header.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.navigation.tpl" =============================*/
function content_6ab86a57f19167_56274361 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->compiled->nocache_hash = '11582316736ab86a57eb7b45_99869611';
?>
<nav class="navbar navbar-expand-md  fixed-top bg-dark navbar-dark">
    <div class="container-xl">
        <a class="navbar-brand" href="#">
            <img src="./styles/resource/images/icons/192.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
            <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'gameName\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

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
                <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php if (count($_smarty_tpl->tpl_vars[\'languages\']->value) > 1) {?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdown07XL" data-toggle="dropdown" aria-expanded="false"><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'language\'];?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</a>
                    <div class="dropdown-menu" aria-labelledby="dropdown07XL">
                    <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'languages\']->value, \'langName\', false, \'langKey\');
$_smarty_tpl->tpl_vars[\'langName\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'langKey\']->value => $_smarty_tpl->tpl_vars[\'langName\']->value) {
$_smarty_tpl->tpl_vars[\'langName\']->do_else = false;
?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

                        <a class="dropdown-item" href="?lang=<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'langKey\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
" rel="alternate" hreflang="<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'langKey\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
" title="<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'langName\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
"><?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'langName\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
</a>
                    <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

                    </div>
                </li>
                <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php }?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

            </ul>
        </div>
    </div>
</nav><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.navigation.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.footer.tpl" =============================*/
function content_6ab86a57f22290_96768200 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->compiled->nocache_hash = '11582316736ab86a57eb7b45_99869611';
?>
<footer>
    <div class="container">
		<p class="float-right"><a href="#"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['footer_up'];?>
</a></p>
		<p>© 2026 <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'gameName\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
 por LORDA1998</p>
	</div>
</footer>
<div id="dialog" style="display:none;"></div>
<?php echo '<script'; ?>
>
var LoginConfig = {
    'isMultiUniverse': <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'isMultiUniverse\']->value);?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
,
	'unisWildcast': <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'unisWildcast\']->value);?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
,
	'referralEnable' : <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'referralEnable\']->value);?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
,
	'basePath' : <?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo json_encode($_smarty_tpl->tpl_vars[\'basepath\']->value);?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

};
<?php echo '</script'; ?>
>
<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php if ($_smarty_tpl->tpl_vars[\'analyticsEnable\']->value) {?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

<?php echo '<script'; ?>
 type="text/javascript" src="http://www.google-analytics.com/ga.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 type="text/javascript">
try{
var pageTracker = _gat._getTracker("<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php echo $_smarty_tpl->tpl_vars[\'analyticsUID\']->value;?>
/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>
");
pageTracker._trackPageview();
} catch(err) {}<?php echo '</script'; ?>
>
<?php echo '/*%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/<?php }?>/*/%%SmartyNocache:11582316736ab86a57eb7b45_99869611%%*/';?>

</body>
</html><?php
}
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\main.footer.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\layout.normal.tpl" =============================*/
function content_6ab86a57f0c150_48374593 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '11582316736ab86a57eb7b45_99869611';
$_smarty_tpl->_subTemplateRender("file:main.header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, '0347b68329ccec2ab68d53725d81da040d74308a', 'content_6ab86a57f0ddf2_17294210');
$_smarty_tpl->_subTemplateRender("file:main.navigation.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, '06128c7e205a4024a4ca44f3c78cf06e8e8869d6', 'content_6ab86a57f19167_56274361');
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_17782754336ab86a57f20ee9_68837626', "content");
?>

<?php
$_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 0, false, '8e88b826d977dfa60924d930f718aa24f9b963f5', 'content_6ab86a57f22290_96768200');
}
/* {block "content"} */
class Block_17782754336ab86a57f20ee9_68837626 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_17782754336ab86a57f20ee9_68837626',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\login\layout.normal.tpl" =============================*/
}
