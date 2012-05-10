<?php /* Smarty version 2.6.2, created on 2010-11-16 17:36:42
         compiled from profile.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'profile.html', 13, false),array('function', 'country_options', 'profile.html', 80, false),array('function', 'html_options', 'profile.html', 95, false),)), $this); ?>
<?php $this->assign('title', @constant('_TPL_PROFILE_TITLE'));  $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<div id="center">

<div id="d_over_iframe"></div>
<!--MAIN CONTEINER-->
<div id="main_t">
    <div id="main_l">

<div class="backend-wrapper">
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "member_menu.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "error.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<form name="signup" method="post" action="<?php echo ((is_array($_tmp=$_SERVER['PHP_SELF'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<table class="vedit" width="100%">

<?php if ($this->_tpl_vars['fields_to_change']['login']): ?>
<tr>
    <th style='width: 40%'>#_TPL_PROFILE_USERNAME#<br />
    <div class="small">#_TPL_PROFILE_ANOTHERUSRNM#</div></th>
    <td>
        <input type="text" name="login" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['login'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" size="15" />
    </td>
</tr>
<?php endif; ?>

<?php if ($this->_tpl_vars['fields_to_change']['name_f']): ?>
<tr>
    <th style='width: 40%'>#_TPL_PROFILE_FLNAME#<br /></th>
    <td nowrap="nowrap">
       <input type="text" name="name_f" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['name_f'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" size="15" />
       <input type="text" name="name_l" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['name_l'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" size="15" />
    </td>
</tr>
<?php endif; ?>

<?php if ($this->_tpl_vars['fields_to_change']['email']): ?>
<tr>
    <th style='width: 40%'>#_TPL_PROFILE_EMAIL#</th>
    <td><input type="text" name="email" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['email'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" size="25" />
    </td>
</tr>
<?php endif; ?>

<?php if ($this->_tpl_vars['fields_to_change']['pass0']): ?>
<tr>
    <th>#_TPL_PROFILE_CHPWD#<br />
    <div class="small">#_TPL_PROFILE_BLANK#</div></th>
    <td><input type="password" name="pass0" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['pass0'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" size="15" />
    </td>
</tr>

<tr>
    <th><b>#_TPL_PROFILE_CONFPWD#</b><br />
    <div class="small">#_TPL_PROFILE_BLANK#</div></th>
    <td><input type="password" name="pass1" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['pass1'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" size="15" />
    </td>
</tr>
<?php endif; ?>

<?php echo $this->_tpl_vars['additional_fields_html']; ?>


<?php if ($this->_tpl_vars['fields_to_change']['street']): ?>
<tr>
    <th>#_TPL_PROFILE_STREET#</th>
    <td><input type="text" name="street" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['street'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" size="30" />
    </td>
</tr>
<?php endif;  if ($this->_tpl_vars['fields_to_change']['city']): ?>
<tr>
    <th>#_TPL_PROFILE_CITY#</th>
    <td><input type="text" name="city" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['city'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" size="15" />
    </td>
</tr>
<?php endif;  if ($this->_tpl_vars['fields_to_change']['country']): ?>
<tr>
    <th>#_TPL_PROFILE_COUNTRY#</th>
    <td><select name="country" id="f_country" size="1">
         <?php echo smarty_function_country_options(array('selected' => $this->_tpl_vars['user']['country']), $this);?>

    </select></td>
</tr>
<?php endif; ?>    
<?php if ($this->_tpl_vars['fields_to_change']['state']): ?>
<tr>
    <th>#_TPL_PROFILE_STATE#</th>
    <td>
    <input type="text" name="state" id="t_state" size="30"
        value="<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['state'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"
        <?php if (count ( $this->_tpl_vars['state_options'] ) > 1): ?>disabled="true" style='display: none;' <?php endif; ?>
        />
    <select name="state" id="f_state" size="1"
        <?php if (count ( $this->_tpl_vars['state_options'] ) <= 1): ?>disabled="true" style='display: none;'<?php endif; ?>
        >        
    <?php echo smarty_function_html_options(array('options' => $this->_tpl_vars['state_options'],'selected' => $this->_tpl_vars['user']['state']), $this);?>

    </select>
    
    </td>
</tr>
<?php endif;  if ($this->_tpl_vars['fields_to_change']['zip']): ?>
<tr>
    <th>#_TPL_PROFILE_ZIP#</th>
    <td><input type="text" name="zip" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['zip'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" size="8" />
    </td>
</tr>
<?php endif; ?>

<?php if ($this->_tpl_vars['fields_to_change']['unsubscribed']): ?>
<tr>
    <th><b>#_TPL_PROFILE_UNSUBSCR#</b><br />
    <div class="small">#_TPL_PROFILE_NOEMAIL#</div><br />
    </th>
    <td>
    <input type="hidden"   name="unsubscribed" value="0" />
    <input type="checkbox" name="unsubscribed" value="1" <?php if ($this->_tpl_vars['user']['unsubscribed'] == '1'): ?>checked<?php endif; ?> />
    #_TPL_PROFILE_CHECKBOX#
    </td>
</tr>
<?php endif; ?>    

</table>
<br />
<input type="hidden" name="do_save" value="1" />
<div class="centered">
<input type="submit" value="#_TPL_PROFILE_SAVEBUT#" />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="button" value="&nbsp;&nbsp;&nbsp;&nbsp;#_TPL_PROFILE_BACKBUT#&nbsp;&nbsp;&nbsp;&nbsp;" onclick="window.location.href='<?php echo $this->_tpl_vars['config']['root_url']; ?>
/member.php'" />
</div>
</form>
</div>
<script type="text/javascript" src="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/includes/jquery/jquery.js?smarty"></script><!--<?php echo ' '; ?>
-->
<script type="text/javascript" src="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/includes/jquery/jquery.select.js?smarty"></script><!--<?php echo ' '; ?>
-->

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "js.country_state.js", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<br /><br />
<!-- <p class="powered">#_TPL_POWEREDBY|<a href="http://www.amember.com/">|</a>#</p> -->
</div>
</div>
</div>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>