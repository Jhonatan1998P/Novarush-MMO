<?php
/* Smarty version 3.1.36, created on 2026-10-07 04:00:56
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\main.footer.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ac5a7d8d43362_06522497',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'c0ef8ab0eb55aa9a3f9becc77323c0c3fac9e47c' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\main.footer.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6ac5a7d8d43362_06522497 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="clear"></div>
<div id="footer">
	<?php if ($_smarty_tpl->tpl_vars['ga_active']->value) {?>
	<?php echo '<script'; ?>
 type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', '<?php echo $_smarty_tpl->tpl_vars['ga_key']->value;?>
']);
	_gaq.push(['_trackPageview']);

	(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();
	<?php echo '</script'; ?>
>
	<?php }?>
	<?php if ($_smarty_tpl->tpl_vars['debug']->value == 1) {?>
	<?php echo '<script'; ?>
 type="text/javascript">
	onerror = handleErr;
	<?php echo '</script'; ?>
>
	<?php }?>
</div>
</body>
</html><?php }
}
