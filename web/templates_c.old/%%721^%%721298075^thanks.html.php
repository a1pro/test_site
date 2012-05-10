<?php /* Smarty version 2.6.2, created on 2010-11-16 14:38:18
         compiled from thanks.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'date_format', 'thanks.html', 20, false),)), $this); ?>
<?php $this->assign('title', @constant('_TPL_THX_TITLE'));  $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<div id="center">
<div id="d_over_iframe"></div>
<div id="d_over_iframe"></div>

<!--MAIN CONTEINER-->
<div id="main_t" style="width:560px;border:0px solid #ccc;text-align:center;">

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "../plugins/protect/amail_aweber/thanks.amail_aweber.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<strong>#_TPL_THX_ENJOY|<a href="<?php echo $this->_tpl_vars['config']['root_url']; ?>
/login.php">|</a>#</strong>
<br />

<!-- display payment receipt -->
<?php if ($this->_tpl_vars['payment']['amount'] > "0.0"): ?>

#_TPL_THX_PAYPROC# <br />
#_TPL_THX_ORDERREF|<?php echo $this->_tpl_vars['payment']['payment_id']; ?>
|<?php echo $this->_tpl_vars['payment']['receipt_id']; ?>
#<br />
#_TPL_THX_DATETIME|<?php echo ((is_array($_tmp=$this->_tpl_vars['payment']['tm_completed'])) ? $this->_run_mod_handler('date_format', true, $_tmp, $this->_tpl_vars['config']['time_format']) : smarty_modifier_date_format($_tmp, $this->_tpl_vars['config']['time_format'])); ?>
#<br />

<br /><br />

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "receipt.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<br /><br />


<?php endif; ?>
</div>
</div>
</div>

<!-- <p class="powered">#_TPL_POWEREDBY|<a href="http://www.amember.com/">|</a>#</p> -->

<?php if ($this->_tpl_vars['config']['google_analytics']):  $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "google_analytics.js", 'smarty_include_vars' => array('sale' => '1')));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  endif; ?>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>