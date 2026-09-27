<?php
/* Smarty version 3.1.36, created on 2026-09-27 03:30:04
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.galaxy.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab8719c5c3d76_28432591',
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
  'cache_lifetime' => 604800,
),true)) {
function content_6ab8719c5c3d76_28432591 (Smarty_Internal_Template $_smarty_tpl) {
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
	<title>Galaxy - <?php echo $_smarty_tpl->tpl_vars['uni_name']->value;?>
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
<body id="galaxy" class="<?php echo $_smarty_tpl->tpl_vars['bodyclass']->value;?>
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
<div id="tooltip" class="tip"></div><?php if ($_smarty_tpl->tpl_vars['hasAdminAccess']->value) {?>
<div class="globalWarning">
<?php echo $_smarty_tpl->tpl_vars['LNG']->value['admin_access_1'];?>
 <a id="drop-admin"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['admin_access_link'];?>
</a><?php echo $_smarty_tpl->tpl_vars['LNG']->value['admin_access_2'];?>

</div>
<?php }?>
<script type="text/javascript">
    	setInterval(function() { AJAX() }, 5000);
</script>
<script type="text/javascript" src="./scripts/game/json.js"></script>
<div id="left_side">  
	<div id="side_menu_up">
        <div class="img"></div>
    </div>
    <div id="left_menu">
        <div id="touchscreenleft_menu">   
            <div id="indicators">
                <div id="attack" class="indicator <?php if ($_smarty_tpl->tpl_vars['ataks']->value > 0) {?>active_indicator<?php }?>">
                    <div class="icoi"></div>
                </div>
                <div id="espionage" class="indicator <?php if ($_smarty_tpl->tpl_vars['spio']->value > 0) {?>active_indicator<?php }?>">
                    <div class="icoi"></div>
                </div>
                <div id="destruction" class="indicator <?php if ($_smarty_tpl->tpl_vars['unic']->value > 0) {?>active_indicator<?php }?>">
                    <div class="icoi"></div>
                </div>
                <div id="rocket" class="indicator <?php if ($_smarty_tpl->tpl_vars['rakets']->value > 0) {?>active_indicator<?php }?>">
                    <div class="icoi"></div>
                </div>
            </div>
                        <a class="big_btn btn_menu btn_menu_big"> <div class="servertime oservertime"></div> </a>
            <?php if ($_smarty_tpl->tpl_vars['bonus_time']->value < TIMESTAMP) {?>
            <a class="big_btn blue btn_menu btn_menu_big" href="game.php?page=bonus"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_bonus'];?>
</a>
            <?php } else { ?>
            <a class="big_btn blue btn_menu btn_menu_big"><?php echo $_smarty_tpl->tpl_vars['bonus_time_rest']->value;?>
</a>
            <?php }?>
             
            <!-- ricerche  tecnologie-->
                        <a class="nuovomenusinistra" href="game.php?page=research" id="munu_research">Investigación</a>
            <a class="nuovomenudestra" href="game.php?page=research"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/research.png" class="imgovernuovo"></a>
                        <!-- costruzioni risorse-->
                        <a class="nuovomenusinistra" href="game.php?page=buildings" id="munu_build">Edificios</a>
                                    <a class="nuovomenudestra tooltip" href="game.php?page=resources" id="munu_resources" data-tooltip-content="Recursos"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/resources.png" class="oimgaltro"></a>
                        <!-- flotta hangar -->
                        <a class="nuovomenusinistra" href="game.php?page=shipyard&amp;mode=fleet" id="munu_shipyard_fleet">Hangar</a>
            <a class="nuovomenudestra" href="game.php?page=shipyard&amp;mode=fleet" id="munu_fleetable"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/hangar.png" class="imgovernuovo"></a>
                        <!-- difese -->
                        <a class="nuovomenusinistra" href="game.php?page=shipyard&amp;mode=defense" id="munu_shipyard_defense">Defensas</a>
            <a class="nuovomenudestra" href="game.php?page=shipyard&amp;mode=defense"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/shield.png" class="imgovernuovo"></a>
                        <!-- Orbita -->
            <a class="nuovomenusinistra" href="game.php?page=fleetTable" id="munu_orbita">Flota</a>
                        <a class="nuovomenudestra tooltip" href="game.php?page=battleSimulator" id="munu_fleetable" data-tooltip-content="Simulador"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/target.png" class="oimgaltro"></a>	
                        
            <!-- alleanza-->
            			<a class="nuovomenusinistra" href="game.php?page=alliance" id="munu_alliance">Alianza</a>
            <a class="nuovomenudestra" href="game.php?page=alliance"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/alliance.png" class="imgovernuovo" id="ciaone"></a>	
                                    <a class="nuovomenusinistra" href="game.php?page=market">Mercado</a>
            <a class="nuovomenudestra" href="game.php?page=market"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/market.png" class="imgovernuovo"></a>
                                    <a class="nuovomenusinistra" href="game.php?page=arsenal">Arsenal</a>
                                    <a class="nuovomenudestra tooltip" href="game.php?page=conteiner" id="munu_fleetable" data-tooltip-content="Сontainers"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/arsenal.png" class="oimgaltro"></a>	
                                    <a class="nuovomenusinistra" href="game.php?page=officier" id="munu_senat">Oficiales</a>
            <a class="nuovomenudestra" href="game.php?page=officier"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/governatori.png" class="imgovernuovo" id="ciaone"></a>
                                    <a class="nuovomenusinistra" href="game.php?page=minerals" id="munu_senat">Minerales</a>
                                    <a class="nuovomenudestra tooltip" href="game.php?page=details" id="munu_fleetable" data-tooltip-content="Detalles"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/blackmarket.png" class="oimgaltro"></a>	
                        <!-- ufficiali governatori -->
                        <a class="galassiabott" href="game.php?page=galaxy" id="munu_galaxy">Galaxy</a>
               
     		<?php if ($_smarty_tpl->tpl_vars['authlevel']->value > 0) {?>
            <a  href="admin.php" class="big_btn green btn_menu btn_menu_big"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_administration'];?>
</a>
            <?php }?>
            <div class="clear"></div>                
    </div>
    </div><!--/left_menu-->
    <div class="menubassoleft">
        <div id="top_nav_parte_sotto"> 
                        <a title="Tecnologías" href="game.php?page=techtree"><span class="techtree"></span></a>
            <div class="separator_nav"></div>
             
			<a title="Reglas" href="index.php?page=rules" target="_blank"><span class="rules"></span></a>
            <div class="separator_nav"></div>
                        <a title="Amistades" href="game.php?page=buddyList"><span class="frend"></span></a>
            <div class="separator_nav"></div>
                        			<a title="Registros" href="game.php?page=records"><span class="record"></span></a>
            <div class="separator_nav"></div>
                        			<a title="Soporte" href="game.php?page=ticket"><span class="soopart"></span></a>				
			<div class="separator_nav"></div>
            			<a title="Opciones" href="game.php?page=settings"><span class="seting"></span></a>  
			<div class="separator_nav"></div>		
			<a title="Cerrar sesión" href="game.php?page=logout"> <span class="exit"></span></a>				  
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
    <script type="text/javascript">
		var ataks = "<?php echo $_smarty_tpl->tpl_vars['ataks']->value;?>
";
		var spio = "<?php echo $_smarty_tpl->tpl_vars['spio']->value;?>
";
        var unic = "<?php echo $_smarty_tpl->tpl_vars['unic']->value;?>
";
		var rakets = "<?php echo $_smarty_tpl->tpl_vars['rakets']->value;?>
";
		var msg = <?php echo $_smarty_tpl->tpl_vars['new_message']->value;?>
;
		document.getElementById('msgaudio').volume=<?php echo $_smarty_tpl->tpl_vars['msgvolume']->value;?>
;
		document.getElementById('beepataks').volume=<?php echo $_smarty_tpl->tpl_vars['volume']->value;?>
;
	</script>
</div><?php $_smarty_tpl->_checkPlugins(array(0=>array('file'=>'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\includes\\libs\\Smarty\\plugins\\function.html_options.php','function'=>'smarty_function_html_options',),));
?><div id="header">
    <div id="top_nav" class="otopnav"> 
        <a title="Descripción general" href="game.php?page=overview">
            <img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/general/logo.png" class="game_logo">
        </a>
        <div style="display:none;">					
            <select id="lstPlaneta" name="lstPlaneta" onchange="document.location = $(this).val();">
                <?php echo smarty_function_html_options(array('options'=>$_smarty_tpl->tpl_vars['PlanetSelect']->value,'selected'=>$_smarty_tpl->tpl_vars['current_pids']->value),$_smarty_tpl);?>

            </select>
        </div>
        <div class="mini_planet_navigation" style="margin: auto;left: 0;right: 0;width: 235px;background: none;top: 46px;position: absolute;">
            <span class="link_back" title="" onclick="eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex-1].value+'\'');"></span>
            <span class="link_next" title="" onclick="eval('location=\''+document.getElementById('lstPlaneta').options[document.getElementById('lstPlaneta').selectedIndex+1].value+'\'');"></span>
        </div>
        <div id="planet_select" style="margin: auto;left: 0;right: 0;top:46px;">
            <div class="active_panet">
				<div class="name_palnet" style="padding-left: 1px;width: 96px;"><img src='<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
planeten/planet2d/<?php echo $_smarty_tpl->tpl_vars['planetImage']->value;?>
.png' style="float:left;height: 22px;padding-top:3px;margin-right: 5px;"><?php echo $_smarty_tpl->tpl_vars['planetName']->value;?>
</div> 
                <span class="ico_build"></span>                            
				<div class="coordinates_palnet">[<?php echo $_smarty_tpl->tpl_vars['planetGalaxy']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['planetSystem']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['planetPlanet']->value;?>
]</div>
				<div class="clear"></div>
			</div>
            <div id="list_palnet">
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['PlanetListing']->value, 'Element', false, 'ID');
$_smarty_tpl->tpl_vars['Element']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['ID']->value => $_smarty_tpl->tpl_vars['Element']->value) {
$_smarty_tpl->tpl_vars['Element']->do_else = false;
?>        
			<div class="separator_h"></div>                   
            <div class="palnet_row <?php if ($_smarty_tpl->tpl_vars['current_pid']->value == $_smarty_tpl->tpl_vars['ID']->value) {?>active_palnet_row<?php }?>">
				<div class="fleet_indicators">
                    <img id="<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
m1" <?php if ($_smarty_tpl->tpl_vars['Element']->value['totalAttacks'] == 0) {?>style="display:none;"<?php }?> src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_attack.png" alt="" class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['pla_attack_1'];?>
" />                                    
                    <img id="<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
m12" style="display:none;" src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_grab.png" alt="" class="tooltip" data-tooltip-content="Планету захватывают" />
                    <img id="<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
m6" <?php if ($_smarty_tpl->tpl_vars['Element']->value['totalSpio'] == 0) {?>style="display:none;"<?php }?> src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_spio.png" alt="" class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['pla_attack_2'];?>
" />
                    <img id="<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
m10" <?php if ($_smarty_tpl->tpl_vars['Element']->value['totalRockets'] == 0) {?>style="display:none;"<?php }?> src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_rocket.png" alt="" class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['pla_attack_3'];?>
" />                 
                    <?php if ($_smarty_tpl->tpl_vars['Element']->value['luna'] != 0) {?>  
                    <img id="<?php echo $_smarty_tpl->tpl_vars['Element']->value['luna'];?>
m1" <?php if ($_smarty_tpl->tpl_vars['Element']->value['totalAttackLuna'] == 0) {?>style="display:none;"<?php }?> src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_moon_attack.png" alt="" class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['pla_attack_4'];?>
" />
                    <img id="<?php echo $_smarty_tpl->tpl_vars['Element']->value['luna'];?>
m6" <?php if ($_smarty_tpl->tpl_vars['Element']->value['totalRocketsLuna'] == 0) {?>style="display:none;"<?php }?> src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_moon_spio.png" alt="" class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['pla_attack_5'];?>
" />       
                    <img id="<?php echo $_smarty_tpl->tpl_vars['Element']->value['luna'];?>
m9" style="display:none;" src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_destrued.png" alt="" class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['pla_attack_6'];?>
" />
                    <img id="<?php echo $_smarty_tpl->tpl_vars['Element']->value['luna'];?>
m10" <?php if ($_smarty_tpl->tpl_vars['Element']->value['totalSpioLuna'] == 0) {?>style="display:none;"<?php }?> src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_moon_rocket.png" alt="" class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['pla_attack_7'];?>
" />                         
					<?php }?>                
                    <div class="clear"></div>
                </div>	   
                <span class="<?php if ($_smarty_tpl->tpl_vars['current_pid']->value == $_smarty_tpl->tpl_vars['ID']->value) {?>active_urlpalnet<?php } else { ?>urlpalnet<?php }?>" url="cp=<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
">
					<img src='<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
planeten/planet2d/<?php echo $_smarty_tpl->tpl_vars['Element']->value['image'];?>
.png' style="float:left;height: 22px;padding-top: 5px;">
                    <span class="name_palnet"  style="padding-top: 5px;padding-left: 5px;width: 70px;"><?php echo $_smarty_tpl->tpl_vars['Element']->value['name'];?>
</span>
					<span class="ico_build">
                        <?php if ($_smarty_tpl->tpl_vars['Element']->value['buildInfo']['buildings']) {?>
                            <img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_build.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                    <td rowspan='2'><img alt='' src='<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
gebaeude/<?php echo $_smarty_tpl->tpl_vars['Element']->value['buildInfo']['buildings']['id'];?>
.gif' width='35' height='35'></td>
                                    <td><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['Element']->value['buildInfo']['buildings']['id']];?>
 (<?php echo pretty_number($_smarty_tpl->tpl_vars['Element']->value['buildInfo']['buildings']['level']);?>
)</td>
                                    </tr>
                                    <tr><td><?php echo pretty_time($_smarty_tpl->tpl_vars['Element']->value['buildInfo']['buildings']['timeleft']);?>
 </td></tr>
                                </table>
                            "/>
						<?php }?>
						<?php if ($_smarty_tpl->tpl_vars['Element']->value['buildInfo']['fleet']) {?>
                            <img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_ship.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                    <td rowspan='2'><img alt='' src='<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
gebaeude/<?php echo $_smarty_tpl->tpl_vars['Element']->value['buildInfo']['fleet']['id'];?>
.gif' width='35' height='35'></td>
                                    <td><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['Element']->value['buildInfo']['fleet']['id']];?>
</td>
                                    </tr>
                                    <tr><td><?php echo pretty_number($_smarty_tpl->tpl_vars['Element']->value['buildInfo']['fleet']['level']);?>
</td></tr>
                                </table>
                            "/> 
						<?php }?>
						<?php if ($_smarty_tpl->tpl_vars['Element']->value['buildInfo']['tech']) {?>
							<img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/p_select_tech.png" alt="" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                <tr>
                                <td rowspan='2'><img alt='' src='<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
gebaeude/<?php echo $_smarty_tpl->tpl_vars['Element']->value['buildInfo']['tech']['id'];?>
.gif' width='35' height='35'></td>
                                <td><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['Element']->value['buildInfo']['tech']['id']];?>
 (<?php echo pretty_number($_smarty_tpl->tpl_vars['Element']->value['buildInfo']['tech']['level']);?>
)</td>
                                </tr>
                                <tr><td><?php echo pretty_time($_smarty_tpl->tpl_vars['Element']->value['buildInfo']['tech']['timeleft']);?>
 </td></tr>
                                </table> 
                            "/>
						<?php }?>
					</span>  			
                    <span class="coordinates_palnet" style="width: 60px;">[<?php echo $_smarty_tpl->tpl_vars['Element']->value['galaxy'];?>
:<?php echo $_smarty_tpl->tpl_vars['Element']->value['system'];?>
:<?php echo $_smarty_tpl->tpl_vars['Element']->value['planet'];?>
]</span>
                </span>
                <?php if ($_smarty_tpl->tpl_vars['Element']->value['luna'] != 0) {?>                             
                <div class="separator_v"></div>
                <span class="<?php if ($_smarty_tpl->tpl_vars['current_pid']->value == $_smarty_tpl->tpl_vars['Element']->value['luna']) {?>active_urlpalnet<?php } else { ?>urlpalnet<?php }?>" url="cp=<?php echo $_smarty_tpl->tpl_vars['Element']->value['luna'];?>
">
                    <span class="moon_select <?php if ($_smarty_tpl->tpl_vars['current_pid']->value == $_smarty_tpl->tpl_vars['Element']->value['luna']) {?>moon_active<?php }?>"></span>
                    <span class="ico_build"><br /></span>
                </span>
                <?php }?>                
            </div> 
            <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>                       
            </div>
        </div><!--/planet_select-->			
		<img title="" src="<?php echo $_smarty_tpl->tpl_vars['foto']->value;?>
" class="settingxterium" onclick="return Dialog.Playercard(<?php echo $_smarty_tpl->tpl_vars['userID']->value;?>
, '<?php echo $_smarty_tpl->tpl_vars['username']->value;?>
');">
        <span class="usernameow" onclick="return Dialog.Playercard(<?php echo $_smarty_tpl->tpl_vars['userID']->value;?>
, '<?php echo $_smarty_tpl->tpl_vars['username']->value;?>
');"><?php echo $_smarty_tpl->tpl_vars['username']->value;?>
</span>
		<span class="usernamepos"></span>        
        <div id="res_nav">
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['resourceTable']->value, 'resouceData', false, 'resourceID');
$_smarty_tpl->tpl_vars['resouceData']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['resourceID']->value => $_smarty_tpl->tpl_vars['resouceData']->value) {
$_smarty_tpl->tpl_vars['resouceData']->do_else = false;
?> 
                <?php if (!(isset($_smarty_tpl->tpl_vars['resouceData']->value['current']))) {?>
                    <?php $_tmp_array = isset($_smarty_tpl->tpl_vars['resouceData']) ? $_smarty_tpl->tpl_vars['resouceData']->value : array();
if (!(is_array($_tmp_array) || $_tmp_array instanceof ArrayAccess)) {
settype($_tmp_array, 'array');
}
$_tmp_array['current'] = $_smarty_tpl->tpl_vars['resouceData']->value['max']+$_smarty_tpl->tpl_vars['resouceData']->value['used'];
$_smarty_tpl->_assignInScope('resouceData', $_tmp_array ,true);?>
                    <div id="res_block_<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['name'];?>
" class="bloc_res tooltip" data-tooltip-content="<span class='colore<?php echo $_smarty_tpl->tpl_vars['resourceID']->value;?>
'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['resourceID']->value];?>
</span><div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div><?php echo $_smarty_tpl->tpl_vars['LNG']->value['RE'];?>
 <?php echo pretty_number($_smarty_tpl->tpl_vars['resouceData']->value['percent']);?>
%">
                        <div class="ico_res"></div>
                        <div class="stock_res">
                            <div class="stock_percentage stock_percentage_left" style="width:<?php echo abs($_smarty_tpl->tpl_vars['resouceData']->value['percent']/2);?>
%;<?php if ($_smarty_tpl->tpl_vars['resouceData']->value['percent'] > -0.1) {?>display:none;<?php }?>"></div>
                            <div class="stock_percentage stock_percentage_right" style="width:<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['percent']/2;?>
%;<?php if ($_smarty_tpl->tpl_vars['resouceData']->value['percent'] < 0.1) {?>display:none;<?php }?>"></div>
                            <div class="separator_<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['name'];?>
"></div>
                            <div class="stock_text"><span id="current_<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['name'];?>
" name="<?php echo pretty_number($_smarty_tpl->tpl_vars['resouceData']->value['current']);?>
" data-real="<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['current'];?>
"><?php echo shortly_number($_smarty_tpl->tpl_vars['resouceData']->value['used']);?>
</span>/<?php echo shortly_number($_smarty_tpl->tpl_vars['resouceData']->value['max']);?>
</div>
                        </div>
                    </div>
                <?php } else { ?>
                    <?php if (!(isset($_smarty_tpl->tpl_vars['resouceData']->value['current'])) || !(isset($_smarty_tpl->tpl_vars['resouceData']->value['max']))) {?>
                        <div id="res_block_<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['name'];?>
" class="bloc_res tooltip" data-tooltip-content="<span class='colore<?php echo $_smarty_tpl->tpl_vars['resourceID']->value;?>
'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['resourceID']->value];?>
</span><div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div><?php echo $_smarty_tpl->tpl_vars['LNG']->value['RE'];?>
 <?php echo pretty_number($_smarty_tpl->tpl_vars['resouceData']->value['current']);?>
">
                            <?php if (isModuleAvailable(@constant('MODULE_FAIR'))) {?>
                            <a href="game.php?page=fair"><div class="ico_res"></div></a>
                            <?php } else { ?>
                            <div class="ico_res"></div>
                            <?php }?>
                            <div class="stock_res2">
                                <div class="stock_percentage" style="width:100%;"></div>
                                <div class="separator_<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['name'];?>
"></div>
                                <div class="stock_text"><span class='colore<?php echo $_smarty_tpl->tpl_vars['resourceID']->value;?>
' id="current_<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['name'];?>
" name="<?php echo pretty_number($_smarty_tpl->tpl_vars['resouceData']->value['current']);?>
" data-real="<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['current'];?>
"><?php echo shortly_number($_smarty_tpl->tpl_vars['resouceData']->value['current']);?>
</span></div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div id="res_block_<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['name'];?>
" class="bloc_res tooltip" 
                            data-tooltip-content="
                            <span class='colore<?php echo $_smarty_tpl->tpl_vars['resourceID']->value;?>
'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['resourceID']->value];?>
</span>
                            <div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'></div>
                            <?php echo $_smarty_tpl->tpl_vars['LNG']->value['PPS'];?>
: <?php echo $_smarty_tpl->tpl_vars['resouceData']->value['information'];?>

                            <br/><?php echo $_smarty_tpl->tpl_vars['LNG']->value['PPD'];?>
: <?php echo $_smarty_tpl->tpl_vars['resouceData']->value['informationd'];?>

                            <br/><?php echo $_smarty_tpl->tpl_vars['LNG']->value['PPW'];?>
: <?php echo $_smarty_tpl->tpl_vars['resouceData']->value['informationz'];?>
 
                            <div style='border-bottom:1px dashed #666; margin:7px 0 4px 0;'>
                            </div> <span style='color:#999'><?php echo pretty_number($_smarty_tpl->tpl_vars['resouceData']->value['current']);?>
/<?php echo pretty_number($_smarty_tpl->tpl_vars['resouceData']->value['max']);?>
</span>">
                            <?php if (isModuleAvailable(@constant('MODULE_TRADER'))) {?>
                            <a href="game.php?page=trader"><div class="ico_res"></div></a>
                            <?php } else { ?>
                            <div class="ico_res"></div>
                            <?php }?>
                                                        <div class="stock_res">
                                <div class="stock_percentage" style="width:<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['percent'];?>
%;"></div>
                                <div class="stock_text">
                                    <span id="current_<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['name'];?>
" name="<?php echo pretty_number($_smarty_tpl->tpl_vars['resouceData']->value['current']);?>
" data-real="<?php echo $_smarty_tpl->tpl_vars['resouceData']->value['current'];?>
"><?php echo shortly_number($_smarty_tpl->tpl_vars['resouceData']->value['current']);?>
</span>
                                    (<span class="pricent"><?php if ($_smarty_tpl->tpl_vars['resouceData']->value['percent'] <= 100) {
echo $_smarty_tpl->tpl_vars['resouceData']->value['percent'];
} else { ?>100<?php }?></span>%)
                                </div>
                            </div>	
                        </div>
                    <?php }?>
                <?php }?>
            <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>       
        </div>
        <!--/res_nav-->            
    </div><!--/top_nav-->
    <div id="barrasottoover">
		<div id="top_nav_parte_left">
            			<a title="Сontrol" href="game.php?page=control"><span class="imperia"></span></a>
            <div class="separator_nav"></div>
                        			<a title="Estadísticas" href="game.php?page=statistics"><span class="stats"></span></a>
			<div class="separator_nav"></div>
                        			<a title="Logros" href="game.php?page=achievements"><span class="achievv"></span></a>
			<div class="separator_nav"></div>
                        			<a title="Salón de la fama" href="game.php?page=battleHall"><span class="topbk"></span></a>
			<div class="separator_nav"></div>
                        			<a title="Chat" href="game.php?page=chat"><span class="chat"></span></a>
            <div class="separator_nav"></div> 
                        <?php if (!empty($_smarty_tpl->tpl_vars['hasBoard']->value)) {?>
			<a title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_forums'];?>
" href="game.php?page=board" target="_blank"><span class="forum"></span></a>
			<div class="separator_nav"></div>
            <?php }?>
                        <a title="Mensajes" href="game.php?page=messages" id="a_mesage">
                <span class="mesages"></span>
                <?php if ($_smarty_tpl->tpl_vars['new_message']->value > 0) {?><span class="new_email"><span id="newmesnum"><?php echo $_smarty_tpl->tpl_vars['new_message']->value;?>
</span></span><?php }?>
            </a>                                       
            <div class="separator_nav"></div> 
                    </div>    
                <div class="premiumbarra">
			<img class="premiumimgbarra" src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/ico-account-premium.png">
            <a href="game.php?page=store">  
                <span class="premiumtopbar">
                    <span class="premiumscrittabar">Tienda</span>
                </span>
            </a>
        </div>
        	</div>
    <div id="top_menu_bottom">
        <div class="left"></div>
        <div class="mid"></div>
        <div class="right"></div>
    </div>
    <?php if (!$_smarty_tpl->tpl_vars['vmode']->value) {?>
		<script type="text/javascript">
		var viewShortlyNumber	= <?php echo json_encode($_smarty_tpl->tpl_vars['shortlyNumber']->value);?>
;
		var vacation			= <?php echo $_smarty_tpl->tpl_vars['vmode']->value;?>
;
        $(function() {
		<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['resourceTable']->value, 'resourceData', false, 'resourceID');
$_smarty_tpl->tpl_vars['resourceData']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['resourceID']->value => $_smarty_tpl->tpl_vars['resourceData']->value) {
$_smarty_tpl->tpl_vars['resourceData']->do_else = false;
?>
		<?php if ((isset($_smarty_tpl->tpl_vars['resourceData']->value['production']))) {?>
            resourceTicker({
                available: <?php echo json_encode($_smarty_tpl->tpl_vars['resourceData']->value['current']);?>
,
                limit: [0, <?php echo json_encode($_smarty_tpl->tpl_vars['resourceData']->value['max']);?>
],
                production: <?php echo json_encode($_smarty_tpl->tpl_vars['resourceData']->value['production']);?>
,
                valueElem: "current_<?php echo $_smarty_tpl->tpl_vars['resourceData']->value['name'];?>
",
                valuePoursent: "bar_<?php echo $_smarty_tpl->tpl_vars['resourceID']->value;?>
"
            }, true);
		<?php }?>
		<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
        });
		</script>
        <script src="scripts/game/topnav.js"></script>
    <?php }?>
    <script type="text/javascript">
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
	</script>
</div>
<?php if ($_smarty_tpl->tpl_vars['closed']->value) {?>
<div class="infobox"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_closed'];?>
</div>
<?php } elseif ($_smarty_tpl->tpl_vars['delete']->value) {?>
<div class="infobox"><?php echo $_smarty_tpl->tpl_vars['delete']->value;?>
</div>
<?php } elseif ($_smarty_tpl->tpl_vars['vacation']->value) {?>
<div class="infobox"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tn_vacation_mode'];?>
 <?php echo $_smarty_tpl->tpl_vars['vacation']->value;?>
</div>
<?php }?>
</div><div id="content"><?php $_smarty_tpl->_checkPlugins(array(0=>array('file'=>'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\includes\\libs\\Smarty\\plugins\\function.html_options.php','function'=>'smarty_function_html_options',),));
?> 
<link rel="stylesheet" type="text/css" href="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
css/galactic.css">
<div id="page">
    <div id="content">
        <div id="galactic_block_1">
            <div style="position:absolute; width:0; height:0;">
                <?php if ($_smarty_tpl->tpl_vars['action']->value == 'sendMissle') {?>
                <form action="?page=fleetMissile" method="post">
                    <input type="hidden" name="galaxy" value="<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
">
                    <input type="hidden" name="system" value="<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
">
                    <input type="hidden" name="planet" value="<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
">
                    <input type="hidden" name="type" value="<?php echo $_smarty_tpl->tpl_vars['type']->value;?>
">
                    <table class="table569" style='width: 715px !important;'>
                        <tr>
                            <th colspan="2"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_missil_launch'];?>
 [<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
]</th>
                        </tr>
                        <tr>
                            <td><?php echo $_smarty_tpl->tpl_vars['missile_count']->value;?>
 <input type="text" name="SendMI" size="2" maxlength="7"></td>
                            <td><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_objective'];?>
: 
                                <?php echo smarty_function_html_options(array('name'=>'Target','options'=>$_smarty_tpl->tpl_vars['missileSelector']->value),$_smarty_tpl);?>

                            </td>
                        </tr>
                        <tr>
                            <th colspan="2" style="text-align:center;"><input type="submit" value="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_missil_launch_action'];?>
"></th>
                        </tr>
                    </table>
                </form>
                <?php }?>
                <div id="galactic_block_1">
                    <div id="galactic_header" style="position:relative;">
                        <form action="?page=galaxy" method="post" id="galaxy_form">
                            <input id="auto" value="dr" type="hidden">
                            <div class="gal_nazv" style="margin-left:15px;">Galaxy:</div>
                            <div id="nav_1">
                                <input class="prev" name="galaxyeft" onclick="galaxy_submit('galaxyLeft')" type="button">
                                <input class="gal_p3" name="galaxy" value="<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
" size="5" maxlength="4" tabindex="2" type="text">
                                <input class="next" name="galaxyRight" onclick="galaxy_submit('galaxyRight')" type="button">
                            </div>
                            <div class="gal_p4">Sistema:</div>
                            <div id="nav_2">
                                <input type="button" class="prev" name="systemLeft" onclick="galaxy_submit('systemLeft')">
                                <input class="gal_p3" type="text" name="system" value="<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
" size="5" maxlength="4" tabindex="2">
                                <input type="button" class="next" name="systemRight" onclick="galaxy_submit('systemRight')">
                            </div>
                            <div class="gal_sep"></div>
                            <input value="Ver" id="galactic_show" type="submit">
                        </form>
                    </div>
                    <!--/galactic_header-->
                    <div id="galactic_status">
                        <div class="gal_p5">№</div>
                        <div class="status_sep"></div>
                        <div class="gal_p6">Nombre (Actividad)</div>
                        <div class="status_sep"></div>
                        <div class="gal_p7">Luna</div>
                        <div class="status_sep"></div>
                        <div class="gal_p8">Escombros</div>
                        <div class="status_sep"></div>
                        <div class="gal_p9">Jugador (Estado)</div>
                        <div class="status_sep"></div>
                        <div class="gal_p10">Alianza</div>
                        <div class="status_sep"></div>
                        <div class="gal_p11">Acciones</div>
                    </div>
                    <!--/galactic_status-->
                    <?php
$_smarty_tpl->tpl_vars['planet'] = new Smarty_Variable(null, $_smarty_tpl->isRenderingCache);$_smarty_tpl->tpl_vars['planet']->step = 1;$_smarty_tpl->tpl_vars['planet']->total = (int) ceil(($_smarty_tpl->tpl_vars['planet']->step > 0 ? $_smarty_tpl->tpl_vars['max_planets']->value+1 - (1) : 1-($_smarty_tpl->tpl_vars['max_planets']->value)+1)/abs($_smarty_tpl->tpl_vars['planet']->step));
if ($_smarty_tpl->tpl_vars['planet']->total > 0) {
for ($_smarty_tpl->tpl_vars['planet']->value = 1, $_smarty_tpl->tpl_vars['planet']->iteration = 1;$_smarty_tpl->tpl_vars['planet']->iteration <= $_smarty_tpl->tpl_vars['planet']->total;$_smarty_tpl->tpl_vars['planet']->value += $_smarty_tpl->tpl_vars['planet']->step, $_smarty_tpl->tpl_vars['planet']->iteration++) {
$_smarty_tpl->tpl_vars['planet']->first = $_smarty_tpl->tpl_vars['planet']->iteration === 1;$_smarty_tpl->tpl_vars['planet']->last = $_smarty_tpl->tpl_vars['planet']->iteration === $_smarty_tpl->tpl_vars['planet']->total;?>
                    <?php if (!(isset($_smarty_tpl->tpl_vars['GalaxyRows']->value[$_smarty_tpl->tpl_vars['planet']->value]))) {?>
                    <div class="gal_user <?php if ($_smarty_tpl->tpl_vars['planet']->value != 1 && $_smarty_tpl->tpl_vars['planet']->value != 3 && $_smarty_tpl->tpl_vars['planet']->value != 5 && $_smarty_tpl->tpl_vars['planet']->value != 7 && $_smarty_tpl->tpl_vars['planet']->value != 9 && $_smarty_tpl->tpl_vars['planet']->value != 11 && $_smarty_tpl->tpl_vars['planet']->value != 13 && $_smarty_tpl->tpl_vars['planet']->value != 15) {?>second<?php }?>">   
                        <div class="gal_number">
                            <a href="game.php?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=1" class="tooltip" data-tooltip-content="
                                <table class='reducefleet_table'>
                                    <tr>
                                        <td class='reducefleet_img_ship'><img src='<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/position.png'></td>
                                        <td class='reducefleet_name_ship'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_sp'];?>
</td>
                                    </tr>
                                    <tr>
                                        <td class='reducefleet_img_ship'><img src='<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/diametr.png'></td>
                                        <td class='reducefleet_name_ship'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_fields'];?>
 <span class='reducefleet_count_ship'><?php echo $_smarty_tpl->tpl_vars['planetData']->value[$_smarty_tpl->tpl_vars['planet']->value]['fields']*$_smarty_tpl->tpl_vars['planet_factor']->value;?>
</span></td>
                                    </tr>
                                    <tr>
                                        <td class='reducefleet_img_ship'><img src='<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/temp.png'></td>
                                        <td class='reducefleet_name_ship'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['ov_temperature'];?>
 <span class='reducefleet_count_ship'><?php echo $_smarty_tpl->tpl_vars['planetData']->value[$_smarty_tpl->tpl_vars['planet']->value]['temp'];?>
</span></td>
                                    </tr>
                                </table>
                            ">
                            <span style="color:<?php echo $_smarty_tpl->tpl_vars['planetData']->value[$_smarty_tpl->tpl_vars['planet']->value]['color'];?>
"><?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
</span></a>
                        </div>
                        <div class="gal_player_cont" style="float:right">
                            <a class="ico_coloni ico_animation" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=1&amp;target_mission=7"></a>    
                        </div>       
                    </div> 
                    <?php } elseif ($_smarty_tpl->tpl_vars['GalaxyRows']->value[$_smarty_tpl->tpl_vars['planet']->value] === false) {?>
                    <div class="gal_user <?php if ($_smarty_tpl->tpl_vars['planet']->value != 1 && $_smarty_tpl->tpl_vars['planet']->value != 3 && $_smarty_tpl->tpl_vars['planet']->value != 5 && $_smarty_tpl->tpl_vars['planet']->value != 7 && $_smarty_tpl->tpl_vars['planet']->value != 9 && $_smarty_tpl->tpl_vars['planet']->value != 11 && $_smarty_tpl->tpl_vars['planet']->value != 13 && $_smarty_tpl->tpl_vars['planet']->value != 15) {?>second<?php }?>">   
                        <div class="gal_number">
                            <span style="color:#efbf13"><?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
</span>
                        </div>
                        <div class="gal_planet_name"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_planet_destroyed'];?>
</div>
                    </div><!--/gal_user-->     
                    <?php } else { ?>
                    <div class="gal_user <?php if ($_smarty_tpl->tpl_vars['planet']->value != 1 && $_smarty_tpl->tpl_vars['planet']->value != 3 && $_smarty_tpl->tpl_vars['planet']->value != 5 && $_smarty_tpl->tpl_vars['planet']->value != 7 && $_smarty_tpl->tpl_vars['planet']->value != 9 && $_smarty_tpl->tpl_vars['planet']->value != 11 && $_smarty_tpl->tpl_vars['planet']->value != 13 && $_smarty_tpl->tpl_vars['planet']->value != 15) {?>second<?php }?>" style="
                        background-image:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
planeten/panel/<?php echo $_smarty_tpl->tpl_vars['GalaxyRows']->value[$_smarty_tpl->tpl_vars['planet']->value]['planet']['image'];?>
.png);
                        background-repeat: no-repeat;
                        background-position: top left;
                    ">   
                        <div class="gal_number">
                            <a href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=1">
                            <span style="color:#0abd00"><?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
</span></a>
                        </div>
                        <?php $_smarty_tpl->_assignInScope('currentPlanet', $_smarty_tpl->tpl_vars['GalaxyRows']->value[$_smarty_tpl->tpl_vars['planet']->value] ,true);?>
                        <span id="p_<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['planet']['id'];?>
" class="tooltip gal_img_planet" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_planet'];?>
 <?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['planet']['name'];?>
 [<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
]">
                            <div class="gl-actions">
                                <table class="gl-actions-t">	
                                    <tbody>	
                                        <tr>
                                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][1]) {?><td><a class="gl-actions-i ri i-mis1 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=1&amp;target_mission=1" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_1'];?>
"></a></td><?php }?>
                                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][3]) {?><td><a class="gl-actions-i ri i-mis3 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=1&amp;target_mission=3" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_3'];?>
"></a></td><?php }?>
                                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][4]) {?><td><a class="gl-actions-i ri i-mis4 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=1&amp;target_mission=4" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_4'];?>
"></a></td><?php }?>
                                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][5]) {?><td><a class="gl-actions-i ri i-mis5 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=1&amp;target_mission=5" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_5'];?>
"></a></td><?php }?>
                                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][10]) {?><td><a class="gl-actions-i ri i-mis10 tooltip" href="?page=galaxy&amp;action=sendMissle&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_10'];?>
"></a></td><?php }?>            
                                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][6]) {?><td><a class="gl-actions-i ri i-mis6 tooltip" href='javascript:doit(6,<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['planet']['id'];?>
);' data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_6'];?>
"></a></td><?php }?>
                                        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['class'], 'class');
$_smarty_tpl->tpl_vars['class']->index = -1;
$_smarty_tpl->tpl_vars['class']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['class']->value) {
$_smarty_tpl->tpl_vars['class']->do_else = false;
$_smarty_tpl->tpl_vars['class']->index++;
$_smarty_tpl->tpl_vars['class']->first = !$_smarty_tpl->tpl_vars['class']->index;
$__foreach_class_0_saved = $_smarty_tpl->tpl_vars['class'];
?>
                                        <?php if ($_smarty_tpl->tpl_vars['class']->value != 'vacation' && $_smarty_tpl->tpl_vars['currentPlanet']->value['planet']['phalanx']) {?>
                                        <td><a class="gl-actions-i ri i-mis17 tooltip" href='javascript:OpenPopup(&quot;?page=phalanx&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=1&quot;, &quot;&quot;, 640, 510);' data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_phalanx'];?>
"></a></td>
                                        <?php }?>
                                        <?php
$_smarty_tpl->tpl_vars['class'] = $__foreach_class_0_saved;
}
if ($_smarty_tpl->tpl_vars['class']->do_else) {
?>
                                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['planet']['phalanx']) {?>
                                        <td><a class="gl-actions-i ri i-mis17 tooltip" href='javascript:OpenPopup(&quot;?page=phalanx&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=1&quot;, &quot;&quot;, 640, 510);' data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_phalanx'];?>
"></a></td>
                                        <?php }?>
                                        <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
planeten/planet2d/<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['planet']['image'];?>
.png" alt="">
                            		
                        </span>
                        <div class="gal_planet_name"><?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['planet']['name'];?>
 <?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['lastActivity'];?>
 </div>
                        <div class="gal_ico_moon ico_animation">
                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['moon']) {?>
                            <div class="ico_moon tooltip" data-tooltip-content="
                                <table class='tooltip_class_table'>
                                    <tr>
                                        <th colspan='2'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_moon'];?>
 [<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
]</th>
                                    </tr>
                                    <tr>
                                        <td class='tooltip_class_table_text_left'><span><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_diameter'];?>
</span> <?php echo pretty_number($_smarty_tpl->tpl_vars['currentPlanet']->value['moon']['diameter']);?>
</td>
                                    </tr>
                                    <tr>
                                        <td class='tooltip_class_table_text_left'><span><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_temperature'];?>
</span> <?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['moon']['temp_min'];?>
</td>
                                    </tr>
                                </table>
                            "></div>
                            <div class="gl-actions" style="top: 5px;z-index: 2;padding-left: 0px;left: 44px;">
                                <table class="gl-actions-t">	
                                    <tbody>
                                        <tr>
                                            <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][1]) {?><td><a class="gl-actions-i ri i-mis1 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=3&amp;target_mission=1" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_1'];?>
"></a></td><?php }?>
                                            <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][3]) {?><td><a class="gl-actions-i ri i-mis3 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=3&amp;target_mission=3" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_3'];?>
"></a></td><?php }?>
                                            <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][4]) {?><td><a class="gl-actions-i ri i-mis4 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=3&amp;target_mission=4" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_4'];?>
"></a></td><?php }?>
                                            <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][5]) {?><td><a class="gl-actions-i ri i-mis5 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=3&amp;target_mission=5" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_5'];?>
"></a></td><?php }?>	            
                                            <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][6]) {?><td><a class="gl-actions-i ri i-mis6 tooltip" href='javascript:doit(6,<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['moon']['id'];?>
);' data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_6'];?>
"></a></td><?php }?>
                                            <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['missions'][9]) {?><td><a class="gl-actions-i ri i-mis9 tooltip" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
&amp;planettype=3&amp;target_mission=9" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_9'];?>
"></a></td><?php }?>	            
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php }?>
                        </div>
                        <div class="gal_ico_trash">
                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['debris']) {?>
                            <div class="ico_trash_<?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['debris']['metal']+$_smarty_tpl->tpl_vars['currentPlanet']->value['debris']['crystal'] > 225000000000) {?>big<?php } elseif ($_smarty_tpl->tpl_vars['currentPlanet']->value['debris']['metal']+$_smarty_tpl->tpl_vars['currentPlanet']->value['debris']['crystal'] < 225000000000 && $_smarty_tpl->tpl_vars['currentPlanet']->value['debris']['metal']+$_smarty_tpl->tpl_vars['currentPlanet']->value['debris']['crystal'] > 7500000000) {?>medium<?php } elseif ($_smarty_tpl->tpl_vars['currentPlanet']->value['debris']['metal']+$_smarty_tpl->tpl_vars['currentPlanet']->value['debris']['crystal'] < 7500000000) {?>small<?php }?> ico_animation
                            tooltip_sticky" data-tooltip-content="
                                <table class='tooltip_class_table'>
                                    <tbody>
                                        <tr>
                                            <th><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_debris_field'];?>
 [<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['planet']->value;?>
]</th>
                                        </tr>
                                        <tr>
                                            <td class='tooltip_class_table_text_left'>
                                                <span><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][901];?>
</span>: <span class='tooltip_class_901'><?php echo pretty_number($_smarty_tpl->tpl_vars['currentPlanet']->value['debris']['metal']);?>
</span><br>
                                                <span><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][902];?>
</span>: <span class='tooltip_class_902'><?php echo pretty_number($_smarty_tpl->tpl_vars['currentPlanet']->value['debris']['crystal']);?>
</span>
                                            </td>
                                        </tr>            
                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='javascript:doit(8, <?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['planet']['id'];?>
);'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['type_mission_8'];?>
</a>
                                            </td>
                                        </tr>            
                                    </tbody>
                                </table>">
                            </div>
                        <?php }?>
                        </div>	
                        <div class="gal_player_name">
                            <a class="tooltip_sticky" data-tooltip-content="
                                <table class='tooltip_class_table'>
                                    <tbody>
                                        <tr>
                                            <th><?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['playerrank'];?>
</th>
                                        </tr>
                                        <?php if (!$_smarty_tpl->tpl_vars['currentPlanet']->value['ownPlanet']) {?>
                                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['user']['isBuddy']) {?>
                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='#' onclick='return Dialog.Buddy(<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['id'];?>
)'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_buddy_request'];?>
</a>
                                            </td>
                                        </tr>
                                        <?php }?>
                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='#' onclick='return Dialog.Playercard(<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['id'];?>
);'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_playercard'];?>
</a>
                                            </td>
                                        </tr>
                                        <?php }?>
                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='?page=statistics&amp;who=1&amp;start=<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['rank'];?>
'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_see_on_stats'];?>
</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['class'], 'class');
$_smarty_tpl->tpl_vars['class']->index = -1;
$_smarty_tpl->tpl_vars['class']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['class']->value) {
$_smarty_tpl->tpl_vars['class']->do_else = false;
$_smarty_tpl->tpl_vars['class']->index++;
$_smarty_tpl->tpl_vars['class']->first = !$_smarty_tpl->tpl_vars['class']->index;
$__foreach_class_1_saved = $_smarty_tpl->tpl_vars['class'];
if (!$_smarty_tpl->tpl_vars['class']->first) {
}?><span class='galaxy-short-<?php echo $_smarty_tpl->tpl_vars['class']->value;?>
 galaxy-short'><?php echo $_smarty_tpl->tpl_vars['ShortStatus']->value[$_smarty_tpl->tpl_vars['class']->value];?>
</span><?php
$_smarty_tpl->tpl_vars['class'] = $__foreach_class_1_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></td>
                                        </tr>                                        
                                    </tbody>
                                </table>">
                                <span class="<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['class'], 'class');
$_smarty_tpl->tpl_vars['class']->index = -1;
$_smarty_tpl->tpl_vars['class']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['class']->value) {
$_smarty_tpl->tpl_vars['class']->do_else = false;
$_smarty_tpl->tpl_vars['class']->index++;
$_smarty_tpl->tpl_vars['class']->first = !$_smarty_tpl->tpl_vars['class']->index;
$__foreach_class_2_saved = $_smarty_tpl->tpl_vars['class'];
if (!$_smarty_tpl->tpl_vars['class']->first) {?> <?php }?>galaxy-username-<?php echo $_smarty_tpl->tpl_vars['class']->value;
$_smarty_tpl->tpl_vars['class'] = $__foreach_class_2_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>  galaxy-username" <?php if (!$_smarty_tpl->tpl_vars['currentPlanet']->value['user']['isBuddy']) {?>style='color:#eae45c'<?php }?>><?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['username'];?>
</span>
                                <?php if (!empty($_smarty_tpl->tpl_vars['currentPlanet']->value['user']['class'])) {?>
                                <span>(</span><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['class'], 'class');
$_smarty_tpl->tpl_vars['class']->index = -1;
$_smarty_tpl->tpl_vars['class']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['class']->value) {
$_smarty_tpl->tpl_vars['class']->do_else = false;
$_smarty_tpl->tpl_vars['class']->index++;
$_smarty_tpl->tpl_vars['class']->first = !$_smarty_tpl->tpl_vars['class']->index;
$__foreach_class_3_saved = $_smarty_tpl->tpl_vars['class'];
if (!$_smarty_tpl->tpl_vars['class']->first) {
}?><span class="galaxy-short-<?php echo $_smarty_tpl->tpl_vars['class']->value;?>
 galaxy-short"><?php echo $_smarty_tpl->tpl_vars['ShortStatus']->value[$_smarty_tpl->tpl_vars['class']->value];?>
</span><?php
$_smarty_tpl->tpl_vars['class'] = $__foreach_class_3_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?><span>)</span>
                                <?php }?>
                            </a>
                        </div>
                        <div class="gal_ally_name">
                            <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['alliance']) {?>
                            <a class="tooltip_sticky" data-tooltip-content="
                                <table class='tooltip_class_table'>
                                    <tbody>
                                        <tr>
                                            <th><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_alliance'];?>
 <?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['alliance']['name'];?>
 <?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['alliance']['member'];?>
</th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='?page=alliance&amp;mode=info&amp;id=<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['alliance']['id'];?>
'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_alliance_page'];?>
</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <a class='tooltip_class_a_bigbtn' href='?page=statistics&amp;start=<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['alliance']['rank'];?>
&amp;who=2'><?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_see_on_stats'];?>
</a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>">
                                <span class="galaxy-alliance <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['currentPlanet']->value['alliance']['class'], 'class');
$_smarty_tpl->tpl_vars['class']->index = -1;
$_smarty_tpl->tpl_vars['class']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['class']->value) {
$_smarty_tpl->tpl_vars['class']->do_else = false;
$_smarty_tpl->tpl_vars['class']->index++;
$_smarty_tpl->tpl_vars['class']->first = !$_smarty_tpl->tpl_vars['class']->index;
$__foreach_class_4_saved = $_smarty_tpl->tpl_vars['class'];
if (!$_smarty_tpl->tpl_vars['class']->first) {?> <?php }
echo $_smarty_tpl->tpl_vars['class']->value;
$_smarty_tpl->tpl_vars['class'] = $__foreach_class_4_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>"<?php echo $_smarty_tpl->tpl_vars['class']->value;?>
><?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['alliance']['tag'];?>
</span>
                            </a>
                            <?php }?>	
                        </div>
                        <div class="gal_player_cont" style="float:right">
                        <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['action']) {?>
                            <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['action']['esp']) {?>
                                <a class="ico_watch ico_animation" title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_spy'];?>
" href="javascript:doit(6,<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['planet']['id'];?>
,<?php echo htmlspecialchars(json_encode($_smarty_tpl->tpl_vars['spyShips']->value), ENT_QUOTES, 'UTF-8', true);?>
)"></a>	
                            <?php }?>
                            <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['action']['message']) {?> 
                                <a href="#" class="ico_post ico_animation" onclick="return Dialog.PM(<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['id'];?>
)" title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['write_message'];?>
"></a>				      
                            <?php }?>
                            <?php if ($_smarty_tpl->tpl_vars['currentPlanet']->value['action']['buddy']) {?>	
                                <a href="#" class="ico_friend ico_animation" onclick="return Dialog.Buddy(<?php echo $_smarty_tpl->tpl_vars['currentPlanet']->value['user']['id'];?>
)" title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['gl_buddy_request'];?>
"></a>				
                            <?php }?>
                        <?php }?>	               
                        </div> 
                    </div>
                    <?php }?>
                    <?php }
}
?>
                    <div id="gal_block_1_footer">
                        <a id="dali" class="dali btn_galassia" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['max_planets']->value+1;?>
&amp;planettype=1&amp;target_mission=15"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/g_expedition.png">Área profunda de la galaxia</a>
                        <a id="expedition" class="expedition btn_galassia1" href="?page=fleetTable&amp;galaxy=<?php echo $_smarty_tpl->tpl_vars['galaxy']->value;?>
&amp;system=<?php echo $_smarty_tpl->tpl_vars['system']->value;?>
&amp;planet=<?php echo $_smarty_tpl->tpl_vars['max_planets']->value+1;?>
&amp;planettype=1&amp;target_mission=18"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/g_hostile.png">Expedición al Sector Nebulosa</a>
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
                            <div class="gal_text_1"><?php echo $_smarty_tpl->tpl_vars['planetcount']->value;?>
</div>
                            <div class="gal_text_2">% d planetas existentes</div>
                        </div>
                        <div class="gal_stat_2">
                            <div class="gal_text_1"><span id="grecyclers"><?php echo pretty_number($_smarty_tpl->tpl_vars['grecyclers']->value);?>
</span></div>
                            <div class="gal_text_2">Reciclador</div>
                        </div>
                        <div class="gal_stat_3">
                            <div class="gal_text_1"><span id="slots"><?php echo $_smarty_tpl->tpl_vars['maxfleetcount']->value;?>
</span>/<?php echo $_smarty_tpl->tpl_vars['fleetmax']->value;?>
</div>
                            <div class="gal_text_2">Ranuras de flota</div>
                        </div>
                        <div class="gal_stat_4">
                            <div class="gal_text_1"><span id="probes"><?php echo pretty_number($_smarty_tpl->tpl_vars['spyprobes']->value);?>
</span></div>
                            <div class="gal_text_2">Sondas espía</div>
                        </div>
                        <div class="gal_stat_5">
                            <div class="gal_text_1"><span id="recyclers"><?php echo pretty_number($_smarty_tpl->tpl_vars['grecyclers']->value);?>
</span> </div>
                            <div class="gal_text_2">Giga Recycler</div>
                        </div>
                        <div class="gal_stat_6">
                            <div class="gal_text_1"><?php echo pretty_number($_smarty_tpl->tpl_vars['currentmip']->value);?>
</div>
                            <div class="gal_text_2">Misiles disponibles</div>
                        </div>
                    </div>   
                                    </div>
            </div>
        </div>
    </div>
	<script type="text/javascript">
		status_ok		= 'Listo';
		status_fail		= 'Error';
		MaxFleetSetting = <?php echo $_smarty_tpl->tpl_vars['settings_fleetactions']->value;?>
;
	</script>
</div>
</div>
<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['cronjobs']->value, 'cronjob');
$_smarty_tpl->tpl_vars['cronjob']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['cronjob']->value) {
$_smarty_tpl->tpl_vars['cronjob']->do_else = false;
?><img src="cronjob.php?cronjobID=<?php echo $_smarty_tpl->tpl_vars['cronjob']->value;?>
" alt=""><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
$_smarty_tpl->_subTemplateRender("file:main.footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
