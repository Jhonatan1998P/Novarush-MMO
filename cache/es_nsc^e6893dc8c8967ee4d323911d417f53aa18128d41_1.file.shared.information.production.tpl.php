<?php
/* Smarty version 3.1.36, created on 2026-09-27 03:04:57
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\shared.information.production.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab86bb929d126_20316412',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'e6893dc8c8967ee4d323911d417f53aa18128d41' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\shared.information.production.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6ab86bb929d126_20316412 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_assignInScope('count', count($_smarty_tpl->tpl_vars['productionTable']->value['usedResource']) ,true);?>
<table class="tablesorter ally_ranks tabagg">
    <tbody>
        <tr>
            <th class="gray_stripo info1" colspan="1"></th>
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['productionTable']->value['usedResource'], 'resourceID');
$_smarty_tpl->tpl_vars['resourceID']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['resourceID']->value) {
$_smarty_tpl->tpl_vars['resourceID']->do_else = false;
?>
                <th class="gray_stripo info1 colore<?php echo $_smarty_tpl->tpl_vars['resourceID']->value;?>
" colspan="2"><img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/resources/<?php echo $_smarty_tpl->tpl_vars['resourceID']->value;?>
f.png"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['resourceID']->value];?>
</th>
            <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
        </tr>
        <tr>
            <th class="gray_stripo info1"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_level'];?>
</th>
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['productionTable']->value['usedResource'], 'resourceID');
$_smarty_tpl->tpl_vars['resourceID']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['resourceID']->value) {
$_smarty_tpl->tpl_vars['resourceID']->do_else = false;
?>
                <th class="gray_stripo info1"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_prod_p_hour'];?>
</th>
                <th class="gray_stripo info1"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_difference'];?>
</th>
            <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
        </tr>
        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['productionTable']->value['production'], 'productionData', false, 'elementLevel');
$_smarty_tpl->tpl_vars['productionData']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['elementLevel']->value => $_smarty_tpl->tpl_vars['productionData']->value) {
$_smarty_tpl->tpl_vars['productionData']->do_else = false;
?>
		<tr>
			<td><span<?php if ($_smarty_tpl->tpl_vars['CurrentLevel']->value == $_smarty_tpl->tpl_vars['elementLevel']->value) {?> style="color:#ff0000"<?php }?>><?php echo $_smarty_tpl->tpl_vars['elementLevel']->value;?>
</span></td>
			<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['productionData']->value, 'production', false, 'resourceID');
$_smarty_tpl->tpl_vars['production']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['resourceID']->value => $_smarty_tpl->tpl_vars['production']->value) {
$_smarty_tpl->tpl_vars['production']->do_else = false;
?>
			<?php $_smarty_tpl->_assignInScope('productionDiff', $_smarty_tpl->tpl_vars['production']->value-$_smarty_tpl->tpl_vars['productionTable']->value['production'][$_smarty_tpl->tpl_vars['CurrentLevel']->value][$_smarty_tpl->tpl_vars['resourceID']->value] ,true);?>
			<td><span style="color:<?php if ($_smarty_tpl->tpl_vars['production']->value > 0) {?>#08c708<?php } elseif ($_smarty_tpl->tpl_vars['production']->value < 0) {?>#ff4343<?php } else { ?>#ccc<?php }?>"><?php echo pretty_number($_smarty_tpl->tpl_vars['production']->value);?>
</span></td>
			<td><span style="color:<?php if ($_smarty_tpl->tpl_vars['productionDiff']->value > 0) {?>#08c708<?php } elseif ($_smarty_tpl->tpl_vars['productionDiff']->value < 0) {?>#ff4343<?php } else { ?>#ccc<?php }?>"><?php echo pretty_number($_smarty_tpl->tpl_vars['productionDiff']->value);?>
</span></td>
			<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
		</tr>
		<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?> 
    </tbody>
</table><?php }
}
