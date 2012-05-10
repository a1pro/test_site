<?php
session_start();

// if aMember cookies are set, but user is not logged in into aMember
// (cookies are not set), this script will do the following:
// handle customer login and reload current page 

// USAGE: include this file into TOP of your PHP file where you display
// Welcome, ... prompt

if (($_SERVER['REQUEST_METHOD'] != 'POST') &&
    ($_COOKIE['_amember_ru'] != '') && 
    !$_SESSION['_amember_reload_called'] &&
    ($_SESSION['_amember_user']['login'] == '')){

    $_SESSION['_amember_reload_called'] = true; // avoid endless cycle
    
    // include login code:
    global $_product_id;
    $_product_id = array('ONLY_LOGIN');
    require_once(dirname(__FILE__)."/check.inc.php");   
    // make redirect to the same page
    header("Location: $_SERVER[REQUEST_URI]");
    exit();
    
}

?>