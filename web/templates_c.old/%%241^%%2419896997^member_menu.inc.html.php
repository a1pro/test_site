<?php /* Smarty version 2.6.2, created on 2010-11-16 17:36:34
         compiled from member_menu.inc.html */ ?>
<?php 
include_once(dirname(__FILE__).'/../tabs.inc.php');
$tabMenu = new TabMenu($_SESSION['_amember_user']['member_id']);
echo $tabMenu->render();
 ?>