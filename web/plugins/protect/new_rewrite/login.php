<?php
/*
*
*
*	 Author: Alex Scott
*	  Email: alex@cgi-central.net
*		Web: http://www.cgi-central.net
*	Details: Rewrite login file
*	FileName $RCSfile$
*	Release: 3.1.8PRO ($Revision: 3095 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*																		  
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/
if (!defined('INCLUDED_AMEMBER_CONFIG')){
	require_once("../../../config.inc.php");
}
$this_config = $config['protect']['new_rewrite'];

if ($_GET['l'] && in_array("incremental_content",$plugins["protect"]))
{
	$_GET['l'] = preg_replace('/[^0-9a-zA-Z,-]/', '', $_GET['l']);
	list($cookie, $link_id) = split('-', $_GET['l']);
} elseif ($_GET['v']) {
	$_GET['v'] = preg_replace('/[^0-9a-zA-Z,-]/', '', $_GET['v']);
	list($cookie, $product_id) = split('-', $_GET['v']);
}

if ($link_id != '')
{
	$_SESSION['amember_ln_link'] = $link_id;
	$_SESSION['amember_ln_url'] = $_GET['url'];
}

if ($product_id != '')
{
	$_SESSION['amember_nr_product'] = $product_id;
	$_SESSION['amember_nr_url'] = $_GET['url'];
}

$_product_id = ($_SESSION['amember_nr_product'] == 'any') ? range(1, 256) : split(',', $_SESSION['amember_nr_product']);
if(in_array("incremental_content",$plugins["protect"]))
$_link_id = ($_SESSION['amember_ln_link'] == 'any') ? range(1, 256) : split(',', $_SESSION['amember_ln_link']);

require_once($config['root_dir']."/plugins/protect/php_include/check.inc.php");
amember_nr_set_cookie();
amember_nr_create_files($_COOKIE['amember_nr']);

if ($_SESSION['amember_ln_url'] && in_array("incremental_content",$plugins["protect"]))
$_SESSION['amember_nr_url'] = preg_replace('/\s+/', ' ', $_SESSION['amember_ln_url']);
else
$_SESSION['amember_nr_url'] = preg_replace('/\s+/', ' ', $_SESSION['amember_nr_url']);
header("Location: $_SESSION[amember_nr_url]");

unset($_SESSION['amember_nr_product']);
unset($_SESSION['amember_nr_url']);
unset($_SESSION['amember_ln_link']);
unset($_SESSION['amember_ln_url']);

?>