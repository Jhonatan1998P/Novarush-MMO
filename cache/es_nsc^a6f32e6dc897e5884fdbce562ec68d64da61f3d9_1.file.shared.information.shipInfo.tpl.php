<?php
/* Smarty version 3.1.36, created on 2026-09-27 13:31:09
  from 'C:\Users\Ortega\Downloads\OGAME\ogame\styles\theme\nsc\templates\game\shared.information.shipInfo.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.36',
  'unifunc' => 'content_6ab8fe7d2608d7_64891784',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'a6f32e6dc897e5884fdbce562ec68d64da61f3d9' => 
    array (
      0 => 'C:\\Users\\Ortega\\Downloads\\OGAME\\ogame\\styles\\theme\\nsc\\templates\\game\\shared.information.shipInfo.tpl',
      1 => 1642351636,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6ab8fe7d2608d7_64891784 (Smarty_Internal_Template $_smarty_tpl) {
if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value['fleetgun'])) {?>
<div class="row background-border-black-gray m-1">
    <?php if ($_smarty_tpl->tpl_vars['FleetInfo']->value['fleetgun'] == 'notype') {?>
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/notype.png)"></div> 
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_attack_pt'];?>
</p>
                <p class="card-text text-align-right gradient-gray"><?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['attack']);?>
</p>
            </div>
        </div>
    </div>
    <?php } else { ?>
    <?php if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value['fleetgun']['laser']['attack'])) {?>
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/laser.jpg)"></div> 
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_attack_laser'];?>
</p>
                <p class="card-text text-align-right gradient-gray"><?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['fleetgun']['laser']['attack']);?>
</p>
            </div>
        </div>
    </div>
    <?php }?>
    <?php if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value['fleetgun']['ion']['attack'])) {?>
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/ion.jpg)"></div> 
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_attack_ionic'];?>
</p>
                <p class="card-text text-align-right gradient-gray"><?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['fleetgun']['ion']['attack']);?>
</p>
            </div>
        </div>
    </div>
    <?php }?>
    <?php if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value['fleetgun']['plasma']['attack'])) {?>
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/plasma.jpg)"></div> 
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_attack_buster'];?>
</p>
                <p class="card-text text-align-right gradient-gray"><?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['fleetgun']['plasma']['attack']);?>
</p>
            </div>
        </div>
    </div>        
    <?php }?>
    <?php if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value['fleetgun']['gravity']['attack'])) {?>
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/gravity.jpg)"></div> 
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_attack_graviton'];?>
</p>
                <p class="card-text text-align-right gradient-gray"><?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['fleetgun']['gravity']['attack']);?>
</p>
            </div>
        </div>
    </div>
    <?php }?>
    <?php }?>
</div>
<?php }?>
<div class="row background-border-black-gray m-1">
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/d_<?php echo $_smarty_tpl->tpl_vars['FleetInfo']->value['info']['class_defend'];?>
.png)"></div> 
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value["in_armor_".((string)$_smarty_tpl->tpl_vars['FleetInfo']->value['info']['class_defend'])];?>
</p>
                <p class="card-text text-align-right gradient-gray"><?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['structure']);?>
</p>
            </div>
        </div>
    </div>
    <?php if ($_smarty_tpl->tpl_vars['FleetInfo']->value['info']['class_shield'] != 's_none') {?>
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/s_<?php echo $_smarty_tpl->tpl_vars['FleetInfo']->value['info']['class_shield'];?>
.png)"></div> 
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value["in_shield_".((string)$_smarty_tpl->tpl_vars['FleetInfo']->value['info']['class_shield'])];?>
</p>
                <p class="card-text text-align-right gradient-gray"><?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['shield']);?>
</p>
            </div>
        </div>
    </div>
    <?php }?>
</div>
<?php if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value['tech']) && !empty($_smarty_tpl->tpl_vars['FleetInfo']->value['speed1'])) {?>
<div class="row background-border-black-gray m-1">
    <div class="col-4">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <?php if ($_smarty_tpl->tpl_vars['FleetInfo']->value['tech'] == 1 || $_smarty_tpl->tpl_vars['FleetInfo']->value['tech'] == 4) {?>
                <div class="card-img-left opacity-70 tooltip" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/comb.png)" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][115];?>
"></div> 
                <?php } elseif ($_smarty_tpl->tpl_vars['FleetInfo']->value['tech'] == 2 || $_smarty_tpl->tpl_vars['FleetInfo']->value['tech'] == 5) {?>
                <div class="card-img-left opacity-70 tooltip" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/imp.png)" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][117];?>
"></div> 
                <?php } else { ?>
                <div class="card-img-left opacity-70 tooltip" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/hyper.png)" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][118];?>
"></div> 
                <?php }?> 
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_engine'];?>
</p>
                <p class="card-text text-align-right gradient-gray"><?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['speed1']);?>
</p>
            </div>
        </div>
    </div>
    <?php if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value['consumption1'])) {?>
    <div class="col-4">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/consumption.png)"></div> 
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_consumption'];?>
</p>
                <p class="card-text text-align-right gradient-gray"><?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['consumption1']);
if ($_smarty_tpl->tpl_vars['FleetInfo']->value['consumption1'] != $_smarty_tpl->tpl_vars['FleetInfo']->value['consumption2']) {?>(<?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['consumption2']);?>
)<?php }?></p>
            </div>
        </div>
    </div>
    <?php }?>
    <?php if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value['capacity'])) {?>
    <div class="col-4">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70" style="background:url(<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
img/information/capacity.png)"></div> 
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_capacity'];?>
</p>
                <p class="card-text text-align-right gradient-gray"><?php echo pretty_number($_smarty_tpl->tpl_vars['FleetInfo']->value['capacity']);?>
</p>
            </div>
        </div>
    </div>
    <?php }?>
</div>
<?php }?>
<div class="row background-border-black-gray m-1">
    <?php if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value['rapidfire']['to'])) {?>
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_rf_again'];?>
</p>
                <div class="row">
                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['FleetInfo']->value['rapidfire']['to'], 'shoots', false, 'rapidfireID');
$_smarty_tpl->tpl_vars['shoots']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['rapidfireID']->value => $_smarty_tpl->tpl_vars['shoots']->value) {
$_smarty_tpl->tpl_vars['shoots']->do_else = false;
?>
                    <div class="col-2">
                        <div class="card background-border-black-blue m-1"> 
                            <span class="card-img-text background-color-blue opacity-70 tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_number'];?>
"><?php echo pretty_number($_smarty_tpl->tpl_vars['shoots']->value);?>
</span>
                            <img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
gebaeude/<?php echo $_smarty_tpl->tpl_vars['rapidfireID']->value;?>
.gif" alt="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['rapidfireID']->value];?>
" class="opacity-70 tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['rapidfireID']->value];?>
">
                        </div>
                    </div>
                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                </div>
            </div>
        </div>
    </div>
    <?php }?>
    <?php if (!empty($_smarty_tpl->tpl_vars['FleetInfo']->value['rapidfire']['from'])) {?> 
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <p class="card-title text-align-right"><?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_rf_from'];?>
</p>
                <div class="row">
                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['FleetInfo']->value['rapidfire']['from'], 'shoots', false, 'rapidfireID');
$_smarty_tpl->tpl_vars['shoots']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['rapidfireID']->value => $_smarty_tpl->tpl_vars['shoots']->value) {
$_smarty_tpl->tpl_vars['shoots']->do_else = false;
?>
                    <div class="col-2">
                        <div class="card background-border-black-blue m-1"> 
                            <span class="card-img-text background-color-red opacity-70 tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['in_number'];?>
"><?php echo pretty_number($_smarty_tpl->tpl_vars['shoots']->value);?>
</span>
                            <img src="<?php echo $_smarty_tpl->tpl_vars['dpath']->value;?>
gebaeude/<?php echo $_smarty_tpl->tpl_vars['rapidfireID']->value;?>
.gif" alt="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['rapidfireID']->value];?>
" class="opacity-70 tooltip" data-tooltip-content="<?php echo $_smarty_tpl->tpl_vars['LNG']->value['tech'][$_smarty_tpl->tpl_vars['rapidfireID']->value];?>
">
                        </div>
                    </div>
                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                </div>
            </div>
        </div>
    </div>
    <?php }?>
</div><?php }
}
