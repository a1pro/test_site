<?php /* Smarty version 2.6.2, created on 2010-11-16 17:36:34
         compiled from member.html */ ?>
<?php $this->assign('title', @constant('_TPL_MEMBER_TITLE'));  $_smarty_tpl_vars = $this->_tpl_vars;
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
  if ($_GET['tab'] == 'add_renew'): ?>
    <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "member_add_renew.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  elseif ($_GET['tab'] == 'payment_history'): ?>
    <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "member_payment_history.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  elseif ($_GET['tab'] == 'newslatter_archive'): ?>
    <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "member_newslatter_archive.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  else: ?>
    <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "member_main.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  endif; ?>
</div>
<br /><br />
<!-- <p class="powered">#_TPL_POWEREDBY|<a href="http://www.amember.com/">|</a>#</p> -->
</div>
</div>
</div>
<script type="text/javascript" src="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/includes/jquery/jquery.js?smarty"></script><!--<?php echo ' '; ?>
-->
<script type="text/javascript" src="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/includes/jquery/jquery.select.js?smarty"></script><!--<?php echo ' '; ?>
-->
<script type="text/javascript" src="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/includes/jquery/jquery.metadata.min.js?smarty"></script><!--<?php echo ' '; ?>
-->
<script type="text/javascript" src="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/includes/jquery/jquery.validate.pack.js?smarty"></script><!--<?php echo ' '; ?>
-->
<!--<?php echo ' --><script type="text/javascript">
$(document).ready(function(){
    $("form#payment").validate({
    rules: {
		';  if (! $this->_tpl_vars['config']['member_select_multiple_products']):  echo '
		product_id: "required", 
		';  else:  echo '
		"product_id[]": "required", 
		';  endif;  echo '
    	paysys_id: "required"
	},
  	errorPlacement: function(error, element) {
		error.appendTo( element.parent());
	}
    });
});
</script><!--{literal} '; ?>
-->

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>