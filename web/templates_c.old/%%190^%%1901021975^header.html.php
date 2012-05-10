<?php /* Smarty version 2.6.2, created on 2010-08-09 23:20:08
         compiled from header.html */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo $this->_tpl_vars['title']; ?>
</title>
    <link rel="stylesheet" type="text/css" 
        href="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/templates/css/reset.css" />
    <link rel="stylesheet" type="text/css" 
        href="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/templates/css/amember.css" />
    <link rel="stylesheet" type="text/css" 
        href="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/templates/css/site.css" />
</head>
<body>

<?php if ($this->_tpl_vars['config']['lang']['display_choice']): ?><div style='width: 100%; text-align: right;'>
<?php echo display_lang_choice(); ?>
</div>
<?php endif; ?>
<br /><br />
<div class="centered">

<h1><?php echo $this->_tpl_vars['title']; ?>
</h1>
<hr />
<br />