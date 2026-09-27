<?php
/* Smarty version 3.1.36, created on 2026-09-27 03:31:24
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.playerCard.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab871ec33d3b0_06362434',
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
  'cache_lifetime' => 604800,
),true)) {
function content_6ab871ec33d3b0_06362434 (Smarty_Internal_Template $_smarty_tpl) {
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
	<title>Perfil del jugador - <?php echo $_smarty_tpl->tpl_vars['uni_name']->value;?>
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
<body id="playerCard" class="<?php echo $_smarty_tpl->tpl_vars['bodyclass']->value;?>
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
css/playerCard.css">
<body id="playerCard" class="popup" style="overflow-x: hidden;">
    <div id="body">
        <div id="popup_conteirer">
            <div id="content">
                <div id="ally_content" class="conteiner player_card" style="width:auto;">
                    <div class="gray_stripo">Acerca del jugador</div> 
                    <div class="row" style="padding: 7px">
                        <div class="col-md-12">
                            <div class="card background-border-black-gray shadow"> 
                                <div class="card-body">
                                    <div class="row" style="align-items: center; ">
                                        <div class="col-2">
                                            <img src="<?php echo $_smarty_tpl->tpl_vars['ava']->value;?>
" class="img-fluid float-start opacity-70 border-radius-50">
                                        </div>
                                        <div class="col-10">
                                            <div class="card">
                                                <div class="card-body">
                                                    <p class="card-title" style="font-size: 20px;">
                                                        <?php echo $_smarty_tpl->tpl_vars['name']->value;?>
 
                                                        <?php if ($_smarty_tpl->tpl_vars['allyname']->value) {?>[<a href="#" onclick="parent.location = 'game.php?page=alliance&amp;mode=info&amp;id=<?php echo $_smarty_tpl->tpl_vars['allyid']->value;?>
';return false;" class="playercrd17"><?php echo $_smarty_tpl->tpl_vars['allyname']->value;?>
</a><?php } else {
}?>]
                                                    </p>
                                                    <p class="card-title">
                                                        <a href="#" onclick="parent.location = 'game.php?page=galaxy&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
';return false;"><?php echo $_smarty_tpl->tpl_vars['homeplanet']->value;?>
 [<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
]</a>
                                                    </p>
                                                    <p class="card-title"><?php echo $_smarty_tpl->tpl_vars['timezone']->value;?>
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
                                                    <p class="card-title text-align-center">Estadísticas</p>
                                                    <table class="tablesorter ally_ranks playercard_tables">
                                                        <tbody>
                                                            <tr class="playercrd2">
                                                                <td style="text-align: left;">Edificios</td>
                                                                <td><?php echo $_smarty_tpl->tpl_vars['build_points']->value;?>
</td>
                                                                <td style="font-weight:bold">#<?php echo $_smarty_tpl->tpl_vars['build_rank']->value;?>
</td>
                                                            </tr>
                                                            <tr class="playercrd2">
                                                                <td style="text-align: left;">Investigación</td>
                                                                <td><?php echo $_smarty_tpl->tpl_vars['tech_points']->value;?>
</td>	
                                                                <td style="font-weight:bold">#<?php echo $_smarty_tpl->tpl_vars['tech_rank']->value;?>
</td>	
                                                            </tr>
                                                            <tr class="playercrd2">
                                                                <td style="text-align: left;">Flota</td>
                                                                <td><?php echo $_smarty_tpl->tpl_vars['fleet_points']->value;?>
</td>
                                                                <td style="font-weight:bold">#<?php echo $_smarty_tpl->tpl_vars['fleet_rank']->value;?>
</td>		
                                                            </tr>
                                                            <tr class="playercrd2"> 
                                                                <td style="text-align: left;">Defensa</td>
                                                                <td><?php echo $_smarty_tpl->tpl_vars['defs_points']->value;?>
</td>	
                                                                <td style="font-weight:bold">#<?php echo $_smarty_tpl->tpl_vars['defs_rank']->value;?>
</td>	
                                                            </tr>
                                                            <tr class="playercrd2">
                                                                <td style="text-align: left;">Total</td>
                                                                <td><?php echo $_smarty_tpl->tpl_vars['total_points']->value;?>
</td>	
                                                                <td style="font-weight:bold">#<?php echo $_smarty_tpl->tpl_vars['total_rank']->value;?>
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
                                                    <p class="card-title text-align-center">Estadísticas de lucha</p>
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <p class="alleanza_zone">Ganó<br><span class="card-text"><?php echo $_smarty_tpl->tpl_vars['wons']->value;?>
 (<?php echo $_smarty_tpl->tpl_vars['siegprozent']->value;?>
%)</span></p>
                                                        </div>
                                                        <div class="col-4">
                                                            <p class="alleanza_zone">Perdido<br><span class="card-text"><?php echo $_smarty_tpl->tpl_vars['loos']->value;?>
 (<?php echo $_smarty_tpl->tpl_vars['loosprozent']->value;?>
%)</span></p>
                                                        </div>
                                                        <div class="col-4">
                                                            <p class="alleanza_zone">Dibujado<br><span class="card-text"><?php echo $_smarty_tpl->tpl_vars['draws']->value;?>
 (<?php echo $_smarty_tpl->tpl_vars['drawsprozent']->value;?>
%)</span></p>
                                                        </div>
                                                    </div>
                                                    <p class="card-text">Total de peleas <span style="float: right;"><?php echo pretty_number($_smarty_tpl->tpl_vars['totalfights']->value);?>
</span></p>
                                                    <p class="card-text mb-3">Coeficiente de daño <span style="float: right;"><?php echo $_smarty_tpl->tpl_vars['damageCoef']->value;?>
</span></p>
                                                    <div class="alleanza12 tooltip" data-tooltip-content="Unidades destruidas <?php echo $_smarty_tpl->tpl_vars['desunits']->value;?>
" style="width:<?php echo $_smarty_tpl->tpl_vars['damageDes']->value;?>
%"></div>
                                                    <div class="alleanza13 tooltip" data-tooltip-content="Unidades perdidas <?php echo $_smarty_tpl->tpl_vars['lostunits']->value;?>
" style="width:<?php echo $_smarty_tpl->tpl_vars['damageLost']->value;?>
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

<?php $_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
