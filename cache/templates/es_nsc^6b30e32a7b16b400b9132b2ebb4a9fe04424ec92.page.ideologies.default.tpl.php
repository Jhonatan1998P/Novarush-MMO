<?php
/* Smarty version 3.1.36, created on 2026-09-27 04:10:18
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.ideologies.default.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab87b0a8bf891_08403616',
  'has_nocache_code' => true,
  'file_dependency' => 
  array (
    '856ff7fdeb8d7bb93b35c92b55394ec56a44c71a' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.ideologies.default.tpl',
      1 => 1642351636,
      2 => 'extends',
    ),
    '9123d3b97ba56493651fe23b04aa1762ce21537a' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.ideologies.default.tpl',
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
function content_6ab87b0a8bf891_08403616 (Smarty_Internal_Template $_smarty_tpl) {
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
	<title>Ideologías - <?php echo $_smarty_tpl->tpl_vars['uni_name']->value;?>
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
	
<script type="text/javascript" src="./scripts/game/price.js?v=6"></script>

	<script type="text/javascript">
	$(function() {
		<?php echo $_smarty_tpl->tpl_vars['execscript']->value;?>

	});
	</script>
</head>
<body id="ideologies" class="<?php echo $_smarty_tpl->tpl_vars['bodyclass']->value;?>
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
</div><div id="content">
<link rel="stylesheet" type="text/css" href="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
css/building.css">
<div id="page">
    <div id="content">
    <?php if ($_smarty_tpl->tpl_vars['partyList']->value) {?>
        <div id="ally_content" class="conteiner" style="width: 100%;">
            <div class="gray_stripe" style="padding-right:0;">
                <?php echo $_smarty_tpl->tpl_vars['LNG']->value['lm_ideologies'];?>
              
            </div>
            <div id="build_elements" class=" gov2">
                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['partyList']->value, 'Element', false, 'ID');
$_smarty_tpl->tpl_vars['Element']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['ID']->value => $_smarty_tpl->tpl_vars['Element']->value) {
$_smarty_tpl->tpl_vars['Element']->do_else = false;
?>
                <div id="ofic_<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
" class="build_box" style="height: 200px">
                    <div class="head">               
                        <?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['ID']->value];?>

                        <font class="gov6"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['bd_lvl'];?>
 <?php echo $_smarty_tpl->tpl_vars['Element']->value['level'];?>
</font>
                    </div>
                    <div class="content_box">
                        <div class="image_mini" style="height: 105px; width: 105px;">
                            <img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
gebaeude/<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
.gif" alt="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['ID']->value];?>
" />
                        </div>
                        <div class="prices_mini" style="margin-left: 113px; height: 115px;">
                            <font color="#096"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['Element']->value['elementBonus'], 'Bonus', false, 'BonusName');
$_smarty_tpl->tpl_vars['Bonus']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['BonusName']->value => $_smarty_tpl->tpl_vars['Bonus']->value) {
$_smarty_tpl->tpl_vars['Bonus']->do_else = false;
if ($_smarty_tpl->tpl_vars['Bonus']->value[0] < 0) {?>-<?php } else { ?>+<?php }
if ($_smarty_tpl->tpl_vars['Bonus']->value[1] == 0) {
echo abs($_smarty_tpl->tpl_vars['Bonus']->value[0]*100);?>
%<?php } else {
echo floatval($_smarty_tpl->tpl_vars['Bonus']->value[0]);
}?> <?php echo $_smarty_tpl->tpl_vars['LNG']->value['bonus'][$_smarty_tpl->tpl_vars['BonusName']->value];?>
<br><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></font>
                            <font color="#096">
                            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['Element']->value['AllTech'], 'requireList', false, 'elementID');
$_smarty_tpl->tpl_vars['requireList']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['elementID']->value => $_smarty_tpl->tpl_vars['requireList']->value) {
$_smarty_tpl->tpl_vars['requireList']->do_else = false;
?>
                                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['requireList']->value, 'NeedLevel', false, 'requireID');
$_smarty_tpl->tpl_vars['NeedLevel']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['requireID']->value => $_smarty_tpl->tpl_vars['NeedLevel']->value) {
$_smarty_tpl->tpl_vars['NeedLevel']->do_else = false;
?>
                                    <?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['requireID']->value];?>
 <?php echo $_smarty_tpl->tpl_vars['NeedLevel']->value['count'];?>
<br>
                                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                            <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                            </font>
                        </div>
                        <div class="clear"></div>
                        <form action="game.php?page=ideologies" method="post" class="build_form" id="Gov">
                            <div class="time_build_mini">
                                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['Element']->value['costResources'], 'RessAmount', false, 'RessID');
$_smarty_tpl->tpl_vars['RessAmount']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['RessID']->value => $_smarty_tpl->tpl_vars['RessAmount']->value) {
$_smarty_tpl->tpl_vars['RessAmount']->do_else = false;
echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['RessID']->value];?>
: 
                                <b><span id="price<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
" style="color:<?php if ($_smarty_tpl->tpl_vars['Element']->value['costOverflow'][$_smarty_tpl->tpl_vars['RessID']->value] == 0) {?>lime<?php } else { ?>red<?php }?>"><?php echo pretty_number($_smarty_tpl->tpl_vars['RessAmount']->value);?>
</span></b>
                                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                            </div>
                            <div class="clear"></div>
                            <div class="btn_build_border">
                            <?php if ($_smarty_tpl->tpl_vars['Element']->value['buyable']) {?>
                                <form action="game.php?page=ideologies" method="post" class="build_form">
                                    <div class="clear"></div>
                                    <input name="id" value="<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
" type="hidden">      
                                    <div class="btn_build_border btn_build_border_left dm_btn_build_border_left">
                                        <label class="max_btn_ship"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['bd_lvl_up'];?>
</label>
                                        <div class="div_text count_ships_dots">
                                            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['Element']->value['costResources'], 'RessAmount', false, 'RessID');
$_smarty_tpl->tpl_vars['RessAmount']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['RessID']->value => $_smarty_tpl->tpl_vars['RessAmount']->value) {
$_smarty_tpl->tpl_vars['RessAmount']->do_else = false;
?>
                                            <input id="amount<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
" class="text" onchange="Price<?php echo $_smarty_tpl->tpl_vars['RessID']->value;?>
('<?php echo $_smarty_tpl->tpl_vars['ID']->value;?>
', <?php echo $_smarty_tpl->tpl_vars['RessAmount']->value;?>
);" name="amount" max="<?php echo $_smarty_tpl->tpl_vars['Element']->value['maxLevel'];?>
" min="1" value="1" type="number">
                                            <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                        </div>
                                    </div>
                                    <div class="btn_build_border btn_build_border_right dm_btn_build_border_right">
                                        <button class="btn_build gubernators_form_submit" type="submit"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['bd_support'];?>
</button>  
                                    </div>                  
                                </form>
                            <?php } else { ?>
                                <span class="btn_build red"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['bd_notres'];?>
</span>
                            <?php }?>
                            </div>
                        </form> 
                        <div class="clear"></div>
                    </div>
                </div>
                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                <div class="clear"></div>
            </div>
        </div>
        <?php }?>
    </div>
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
