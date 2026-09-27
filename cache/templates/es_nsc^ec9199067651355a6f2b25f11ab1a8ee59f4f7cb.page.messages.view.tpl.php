<?php
/* Smarty version 3.1.36, created on 2026-09-27 02:59:15
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\page.messages.view.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab86a63bf1475_11480544',
  'has_nocache_code' => true,
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
  'cache_lifetime' => 604800,
),true)) {
function content_6ab86a63bf1475_11480544 (Smarty_Internal_Template $_smarty_tpl) {
?>
<div class="messagestable">
<form action="game.php?page=messages" method="post">
   <input type="hidden" name="mode" value="action">
   <input type="hidden" name="ajax" value="1">
   <input type="hidden" name="messcat" value="<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
">
   <input type="hidden" name="side" value="<?php echo $_smarty_tpl->tpl_vars['page']->value;?>
">
        <div class="message_page_navigation" style="color: #ccc;">Página: 
            <?php if ($_smarty_tpl->tpl_vars['page']->value != 1) {?><a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value-1;?>
);return false;">&laquo;</a>&nbsp;<?php }?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, 1);return false;"><?php if (1 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page">1</span><?php } else { ?>1<?php }?></a>
            <?php if ($_smarty_tpl->tpl_vars['page']->value-4 > 1) {?> ... <?php }?>   
            <?php if ($_smarty_tpl->tpl_vars['page']->value-3 > 1) {?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value-3;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value-3 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value-3;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value-3;
}?></a><?php }?> 
            <?php if ($_smarty_tpl->tpl_vars['page']->value-2 > 1) {?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value-2;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value-2 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value-2;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value-2;
}?></a><?php }?>   
            <?php if ($_smarty_tpl->tpl_vars['page']->value-1 > 1) {?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value-1;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value-1 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value-1;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value-1;
}?></a><?php }?>
            <?php if ($_smarty_tpl->tpl_vars['page']->value > 1) {?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value;
}?></a><?php }?>
            <?php if ($_smarty_tpl->tpl_vars['page']->value+1 <= $_smarty_tpl->tpl_vars['maxPage']->value) {?>            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value+1;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value+1 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value+1;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value+1;
}?></a><?php }?>
            <?php if ($_smarty_tpl->tpl_vars['page']->value+2 <= $_smarty_tpl->tpl_vars['maxPage']->value) {?>            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value+2;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value+2 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value+2;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value+2;
}?></a><?php }?>
            <?php if ($_smarty_tpl->tpl_vars['page']->value+3 <= $_smarty_tpl->tpl_vars['maxPage']->value) {?>            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value+3;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value+3 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value+3;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value+3;
}?></a><?php }?>  
            <?php if ($_smarty_tpl->tpl_vars['page']->value+4 < $_smarty_tpl->tpl_vars['maxPage']->value) {?> ... <?php }?>   
            <?php if ($_smarty_tpl->tpl_vars['page']->value+4 <= $_smarty_tpl->tpl_vars['maxPage']->value) {?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['maxPage']->value;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['maxPage']->value == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['maxPage']->value;?>
</span><?php } else {
echo $_smarty_tpl->tpl_vars['maxPage']->value;?>
 <?php }?></a>
            <?php }?>      
            <?php if ($_smarty_tpl->tpl_vars['page']->value != $_smarty_tpl->tpl_vars['maxPage']->value) {?>&nbsp;<a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value+1;?>
);return false;">&raquo;</a><?php }?>
        </div>
      <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['MessageList']->value, 'Message');
$_smarty_tpl->tpl_vars['Message']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['Message']->value) {
$_smarty_tpl->tpl_vars['Message']->do_else = false;
?>
    <div class="head_row_msg">
        <div id="message_<?php echo $_smarty_tpl->tpl_vars['Message']->value['id'];?>
" class="message_head<?php if ($_smarty_tpl->tpl_vars['MessID']->value != 999 && $_smarty_tpl->tpl_vars['Message']->value['unread'] == 1) {?> mes_unread<?php }?>">
            <div class="message_time"><?php echo $_smarty_tpl->tpl_vars['Message']->value['time'];?>
</div>
            <div class="message_sender">
                <?php if ($_smarty_tpl->tpl_vars['Message']->value['type'] == 1 && $_smarty_tpl->tpl_vars['MessID']->value != 999) {?>
                <a href="#" onclick="return Dialog.Buddy(<?php echo $_smarty_tpl->tpl_vars['Message']->value['sender'];?>
)" title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_fre'];?>
"><img height="13px" class="messagesnew4" src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/mes_friendd.png"></a> 
                                <a href="#" onclick="return Dialog.PM(<?php echo $_smarty_tpl->tpl_vars['Message']->value['sender'];?>
, Message.CreateAnswer('<?php echo $_smarty_tpl->tpl_vars['Message']->value['subject'];?>
'));" title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_answer_to'];?>
 <?php echo strip_tags($_smarty_tpl->tpl_vars['Message']->value['from']);?>
"><img height="13px" class="messagesnew4" src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/mes_messages.png" border="0"></a>
                                <?php }?>
                <a href="#" onclick="msgArchive(<?php echo $_smarty_tpl->tpl_vars['Message']->value['id'];?>
, <?php echo $_smarty_tpl->tpl_vars['Message']->value['type'];?>
); Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['Message']->value['type'];?>
); return false;" title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_arh'];?>
"><img height="13px" class="messagesnew4" src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/mes_inarchive.png"></a>
                <a href="#" onclick="msgDel(<?php echo $_smarty_tpl->tpl_vars['Message']->value['id'];?>
, <?php echo $_smarty_tpl->tpl_vars['Message']->value['type'];?>
); Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['Message']->value['type'];?>
); return false;" title="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_del'];?>
"><img height="13px" class="messagesnew4" src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/iconav/mes_delmsg.png"></a>
                <div class="message_check">
                    <?php if ($_smarty_tpl->tpl_vars['MessID']->value != 999) {?><input name="messageID[<?php echo $_smarty_tpl->tpl_vars['Message']->value['id'];?>
]" value="1" type="checkbox"><?php }?>                    
                </div>
            </div>
            <div class="message_title">
                <span class="message_recipient_name"><?php echo $_smarty_tpl->tpl_vars['Message']->value['from'];?>
</span>
            </div>
        </div>
        <div class="messages_body">
            <div colspan="4" class="left" style="padding:0;">
                <div class="message_text"><?php echo $_smarty_tpl->tpl_vars['Message']->value['text'];?>
</div>
            </div>
        </div>
	</div>
      <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
      <div class="message_page_navigation" style="color: #ccc;">Página: 
            <?php if ($_smarty_tpl->tpl_vars['page']->value != 1) {?><a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value-1;?>
);return false;">&laquo;</a>&nbsp;<?php }?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, 1);return false;"><?php if (1 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page">1</span><?php } else { ?>1<?php }?></a>
            <?php if ($_smarty_tpl->tpl_vars['page']->value-4 > 1) {?> ... <?php }?>   
            <?php if ($_smarty_tpl->tpl_vars['page']->value-3 > 1) {?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value-3;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value-3 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value-3;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value-3;
}?></a><?php }?> 
            <?php if ($_smarty_tpl->tpl_vars['page']->value-2 > 1) {?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value-2;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value-2 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value-2;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value-2;
}?></a><?php }?>   
            <?php if ($_smarty_tpl->tpl_vars['page']->value-1 > 1) {?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value-1;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value-1 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value-1;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value-1;
}?></a><?php }?>
            <?php if ($_smarty_tpl->tpl_vars['page']->value > 1) {?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value;
}?></a><?php }?>
            <?php if ($_smarty_tpl->tpl_vars['page']->value+1 <= $_smarty_tpl->tpl_vars['maxPage']->value) {?>            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value+1;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value+1 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value+1;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value+1;
}?></a><?php }?>
            <?php if ($_smarty_tpl->tpl_vars['page']->value+2 <= $_smarty_tpl->tpl_vars['maxPage']->value) {?>            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value+2;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value+2 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value+2;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value+2;
}?></a><?php }?>
            <?php if ($_smarty_tpl->tpl_vars['page']->value+3 <= $_smarty_tpl->tpl_vars['maxPage']->value) {?>            
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value+3;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['page']->value+3 == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['page']->value+3;?>
</span>
            <?php } else {
echo $_smarty_tpl->tpl_vars['page']->value+3;
}?></a><?php }?>  
            <?php if ($_smarty_tpl->tpl_vars['page']->value+4 < $_smarty_tpl->tpl_vars['maxPage']->value) {?> ... <?php }?>   
            <?php if ($_smarty_tpl->tpl_vars['page']->value+4 <= $_smarty_tpl->tpl_vars['maxPage']->value) {?>
            <a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['maxPage']->value;?>
);return false;"><?php if ($_smarty_tpl->tpl_vars['maxPage']->value == $_smarty_tpl->tpl_vars['page']->value) {?><span class="active_page"><?php echo $_smarty_tpl->tpl_vars['maxPage']->value;?>
</span><?php } else {
echo $_smarty_tpl->tpl_vars['maxPage']->value;?>
 <?php }?></a>
            <?php }?>      
            <?php if ($_smarty_tpl->tpl_vars['page']->value != $_smarty_tpl->tpl_vars['maxPage']->value) {?>&nbsp;<a href="#" class="messagesnew2 messagesnew3" onclick="Message.getMessages(<?php echo $_smarty_tpl->tpl_vars['MessID']->value;?>
, <?php echo $_smarty_tpl->tpl_vars['page']->value+1;?>
);return false;">&raquo;</a><?php }?>
        </div>
      <?php if ($_smarty_tpl->tpl_vars['MessID']->value != 999) {?>
      <div class="build_band2" style="padding-right:0;">         
       <input class="bottom_band_submit" value="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_confirm'];?>
" type="submit" name="submitBottom">
        <select class="bottom_band_select" name="actionBottom">
               <option value="readmarked"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_read_marked'];?>
</option>
				<option value="readtypeall"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_read_type_all'];?>
</option>
				<option value="readall"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_read_all'];?>
</option>
				<option value="deletemarked"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_delete_marked'];?>
</option>
				<option value="deleteunmarked"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_delete_unmarked'];?>
</option>
				<option value="deletetypeall"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_delete_type_all'];?>
</option>
				<option value="deleteall"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_delete_all'];?>
</option>
               <option value="archivemarked"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['mg_arh_mess'];?>
</option>
        </select>
      </div>
      <?php }?>
</form>

</div>

			</div>
		</div>
	</div>
</div>
</div>
</div>
<?php }
}
