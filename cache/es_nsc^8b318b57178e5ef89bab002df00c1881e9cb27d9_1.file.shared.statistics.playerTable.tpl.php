<?php
/* Smarty version 3.1.36, created on 2026-10-07 04:02:29
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\shared.statistics.playerTable.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ac5a835f19ae3_86101480',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '8b318b57178e5ef89bab002df00c1881e9cb27d9' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\shared.statistics.playerTable.tpl',
      1 => 1791337899,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6ac5a835f19ae3_86101480 (Smarty_Internal_Template $_smarty_tpl) {
?><tr class="barraclass">
	<th class="th-pos" colspan="2"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['st_position'];?>
</th>
	<th class="th-player"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['st_player'];?>
</th>
	<th class="th-act"></th>
	<th class="th-ally"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['st_alliance'];?>
</th>
	<th class="th-pts"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['st_points'];?>
</th>
</tr>
<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['RangeList']->value, 'RangeInfo', false, NULL, 'RangeList', array (
));
$_smarty_tpl->tpl_vars['RangeInfo']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['RangeInfo']->value) {
$_smarty_tpl->tpl_vars['RangeInfo']->do_else = false;
?>
<tr class="classificabarra player-stat-row">
    <td id="<?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['rank'];?>
" class="classstat1"><?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['rank'];?>
</td>
    <td class="classstat2"><?php if ($_smarty_tpl->tpl_vars['RangeInfo']->value['ranking'] == 0) {?><span style='color:#87CEEB'>*</span><?php } elseif ($_smarty_tpl->tpl_vars['RangeInfo']->value['ranking'] < 0) {?><span style='color:red'><?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['ranking'];?>
</span><?php } elseif ($_smarty_tpl->tpl_vars['RangeInfo']->value['ranking'] > 0) {?><span style='color:green'>+<?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['ranking'];?>
</span><?php }?></td>
    <td class="classstat3">
        <div class="barracla classstat4"></div>		
        <a href="#" class="fbox-s-name classstat5" onclick="return Dialog.Playercard(<?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['id'];?>
, '<?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['name'];?>
');">
            <?php if (isModuleAvailable(@constant('MODULE_RACE'))) {?>
            <img class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['RangeInfo']->value['race']];?>
" src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
gebaeude/<?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['race'];?>
.gif" width="18" height="18" style="vertical-align:middle;">
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_FORMGOVERNMENT'))) {?>
            <img class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['RangeInfo']->value['formgovernment']];?>
" src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
gebaeude/<?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['formgovernment'];?>
.png" width="18" height="18" style="vertical-align:middle;">
            <?php }?>
            <?php if (isModuleAvailable(@constant('MODULE_ETHICS'))) {?>
            <img class="tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['RangeInfo']->value['ethics']];?>
" src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
gebaeude/<?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['ethics'];?>
.png" width="18" height="18" style="vertical-align:middle;">
            <?php }?>
            <span class="player-name-text"<?php if ($_smarty_tpl->tpl_vars['RangeInfo']->value['id'] == $_smarty_tpl->tpl_vars['CUser_id']->value) {?> style="color:lime"<?php }?>><?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['name'];?>
</span>	
            <?php if (!empty($_smarty_tpl->tpl_vars['RangeInfo']->value['class'])) {?><span class="classstat6"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['RangeInfo']->value['class'], 'class');
$_smarty_tpl->tpl_vars['class']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['class']->value) {
$_smarty_tpl->tpl_vars['class']->do_else = false;
?><span class='galaxy-short-<?php echo $_smarty_tpl->tpl_vars['class']->value;?>
 galaxy-short'><?php echo $_smarty_tpl->tpl_vars['ShortStatus']->value[$_smarty_tpl->tpl_vars['class']->value];?>
 </span><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></span><?php }?>
        </a>		
    </td>
    <td class="classstat7">
        <span class="stat-actions-box">
            <?php if (!empty($_smarty_tpl->tpl_vars['canSpectate']->value) && $_smarty_tpl->tpl_vars['RangeInfo']->value['id'] != $_smarty_tpl->tpl_vars['CUser_id']->value) {?>
                <a href="game.php?page=spectate&amp;target=<?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['id'];?>
" class="tooltip spectate-action-link" data-tooltip-content="👁️ Espectear a <?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['name'];?>
" title="👁️ Espectear en 1ª persona">
                    <svg viewBox="0 0 24 24" width="15" height="15" class="spectate-svg-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" fill="none" stroke="#00e5ff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3.5" fill="#00e5ff"/></svg>
                </a>
            <?php }?>
            <?php if ($_smarty_tpl->tpl_vars['RangeInfo']->value['id'] != $_smarty_tpl->tpl_vars['CUser_id']->value) {?>
                <a href="#" onclick="return Dialog.PM(<?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['id'];?>
);" class="tooltip pm-action-link" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['st_write_message'];?>
"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/mesages.png" title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['st_write_message'];?>
" alt="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['st_write_message'];?>
" class="stat-pm-icon"></a>
            <?php }?>
        </span>
    </td>
    <td class="classstat8"><?php if ($_smarty_tpl->tpl_vars['RangeInfo']->value['allyid'] != 0) {?><a href="game.php?page=alliance&amp;mode=info&amp;id=<?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['allyid'];?>
"><?php if ($_smarty_tpl->tpl_vars['RangeInfo']->value['allyid'] == $_smarty_tpl->tpl_vars['CUser_ally']->value) {?><span style="color:#33CCFF"><?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['allyname'];?>
</span><?php } else {
echo $_smarty_tpl->tpl_vars['RangeInfo']->value['allyname'];
}?></a><?php } else { ?>-<?php }?><div class="barracla" style=""></div></td>
    <td class="classstat10"><?php echo $_smarty_tpl->tpl_vars['RangeInfo']->value['points'];?>
</td>
</tr>
<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
}
}
