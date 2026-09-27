<?php
/* Smarty version 3.1.36, created on 2026-09-27 02:59:14
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.messages.view.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab86a62c267e7_93697727',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '4b77de269f708a015ff0acd2c5af964cb76fe954' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.messages.view.tpl',
      1 => 1642351636,
      2 => 'extends',
    ),
    'cff0012cba7175cc51f45181e6ab8eae57196829' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\page.messages.view.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
    '36163629d633c24ba5681a2eb2a817e511501ae5' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\layout.ajax.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:page.messages.view.tpl' => 1,
    'file:layout.ajax.tpl' => 1,
  ),
),false)) {
function content_6ab86a62c267e7_93697727 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
$_smarty_tpl->compiled->nocache_hash = '3399957216ab86a62ba5087_91534579';
$_smarty_tpl->_subTemplateRender('file:page.messages.view.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, 'cff0012cba7175cc51f45181e6ab8eae57196829', 'content_6ab86a62bcda72_31509399');
$_smarty_tpl->inheritance->endChild($_smarty_tpl);
$_smarty_tpl->_subTemplateRender('file:layout.ajax.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 9999, $_smarty_tpl->cache_lifetime, array(), 2, false, '36163629d633c24ba5681a2eb2a817e511501ae5', 'content_6ab86a62c249c2_38726113');
}
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.messages.view.tpl" =============================*/
function content_6ab86a62bcda72_31509399 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '3399957216ab86a62ba5087_91534579';
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_16842573866ab86a62bd3773_26603078', "content");
}
/* {block "content"} */
class Block_16842573866ab86a62bd3773_26603078 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_16842573866ab86a62bd3773_26603078',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->cached->hashes['3399957216ab86a62ba5087_91534579'] = true;
?>

<div class="messagestable">
<form action="game.php?page=messages" method="post">
   <input type="hidden" name="mode" value="action">
   <input type="hidden" name="ajax" value="1">
   <input type="hidden" name="messcat" value="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
">
   <input type="hidden" name="side" value="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
">
        <div class="message_page_navigation" style="color: #ccc;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_page'];?>
: 
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value != 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;">&laquo;</a>&nbsp;<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, 1);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if (1 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page">1</span><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
1<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-4 > 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
 ... <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
   
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-3 > 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-3 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
 
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-2 > 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-2 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
   
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-1 > 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-1 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value > 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+1 <= $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+1 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+2 <= $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+2 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+3 <= $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+3 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
  
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+4 < $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
 ... <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
   
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+4 <= $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'maxPage\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'maxPage\']->value == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'maxPage\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'maxPage\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
 <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
      
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value != $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
&nbsp;<a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;">&raquo;</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

        </div>
      <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars[\'MessageList\']->value, \'Message\');
$_smarty_tpl->tpl_vars[\'Message\']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars[\'Message\']->value) {
$_smarty_tpl->tpl_vars[\'Message\']->do_else = false;
?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

    <div class="head_row_msg">
        <div id="message_<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'id\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
" class="message_head<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'MessID\']->value != 999 && $_smarty_tpl->tpl_vars[\'Message\']->value[\'unread\'] == 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
 mes_unread<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
">
            <div class="message_time"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'time\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</div>
            <div class="message_sender">
                <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'Message\']->value[\'type\'] == 1 && $_smarty_tpl->tpl_vars[\'MessID\']->value != 999) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

                <a href="#" onclick="return Dialog.Buddy(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'sender\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
)" title="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_fre\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
"><img height="13px" class="messagesnew4" src="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
img/iconav/mes_friendd.png"></a> 
                                <a href="#" onclick="return Dialog.PM(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'sender\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, Message.CreateAnswer('<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'subject\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
'));" title="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_answer_to\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
 <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo strip_tags($_smarty_tpl->tpl_vars[\'Message\']->value[\'from\']);?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
"><img height="13px" class="messagesnew4" src="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
img/iconav/mes_messages.png" border="0"></a>
                                <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

                <a href="#" onclick="msgArchive(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'id\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'type\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
); Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'type\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
); return false;" title="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_arh\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
"><img height="13px" class="messagesnew4" src="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
img/iconav/mes_inarchive.png"></a>
                <a href="#" onclick="msgDel(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'id\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'type\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
); Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'type\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
); return false;" title="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_del\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
"><img height="13px" class="messagesnew4" src="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'dpath\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
img/iconav/mes_delmsg.png"></a>
                <div class="message_check">
                    <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'MessID\']->value != 999) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<input name="messageID[<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'id\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
]" value="1" type="checkbox"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
                    
                </div>
            </div>
            <div class="message_title">
                <span class="message_recipient_name"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'from\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            </div>
        </div>
        <div class="messages_body">
            <div colspan="4" class="left" style="padding:0;">
                <div class="message_text"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'Message\']->value[\'text\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</div>
            </div>
        </div>
	</div>
      <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

      <div class="message_page_navigation" style="color: #ccc;"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_page'];?>
: 
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value != 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;">&laquo;</a>&nbsp;<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, 1);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if (1 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page">1</span><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
1<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-4 > 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
 ... <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
   
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-3 > 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-3 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
 
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-2 > 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-2 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
   
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-1 > 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value-1 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value-1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value > 1) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+1 <= $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+1 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+2 <= $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+2 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+2;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+3 <= $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+3 == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+3;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
  
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+4 < $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
 ... <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
   
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value+4 <= $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'maxPage\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'maxPage\']->value == $_smarty_tpl->tpl_vars[\'page\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
<span class="active_page"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'maxPage\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</span><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php } else { ?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';
echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'maxPage\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
 <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</a>
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
      
            <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'page\']->value != $_smarty_tpl->tpl_vars[\'maxPage\']->value) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
&nbsp;<a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'MessID\']->value;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
, <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'page\']->value+1;?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
);return false;">&raquo;</a><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

        </div>
      <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php if ($_smarty_tpl->tpl_vars[\'MessID\']->value != 999) {?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

      <div class="build_band2" style="padding-right:0;">         
       <input class="bottom_band_submit" value="<?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_confirm\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
" type="submit" name="submitBottom">
        <select class="bottom_band_select" name="actionBottom">
               <option value="readmarked"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_read_marked\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</option>
				<option value="readtypeall"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_read_type_all\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</option>
				<option value="readall"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_read_all\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</option>
				<option value="deletemarked"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_delete_marked\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</option>
				<option value="deleteunmarked"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_delete_unmarked\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</option>
				<option value="deletetypeall"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_delete_type_all\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</option>
				<option value="deleteall"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_delete_all\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</option>
               <option value="archivemarked"><?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php echo $_smarty_tpl->tpl_vars[\'LNG\']->value[\'mg_arh_mess\'];?>
/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>
</option>
        </select>
      </div>
      <?php echo '/*%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/<?php }?>/*/%%SmartyNocache:3399957216ab86a62ba5087_91534579%%*/';?>

</form>

</div>

			</div>
		</div>
	</div>
</div>
</div>
</div>
<?php
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.messages.view.tpl" =============================*/
/* Start inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.ajax.tpl" =============================*/
function content_6ab86a62c249c2_38726113 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, false);
$_smarty_tpl->compiled->nocache_hash = '3399957216ab86a62ba5087_91534579';
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_4575819376ab86a62c25da7_55019329', "content");
}
/* {block "content"} */
class Block_4575819376ab86a62c25da7_55019329 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_4575819376ab86a62c25da7_55019329',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block "content"} */
/* End inline template "C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\layout.ajax.tpl" =============================*/
}
