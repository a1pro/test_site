<?php

/**
note2self : SECURE THIS FILE FOR ACCESS ONLY BY DJANGO SERVER!!!!
*/

$sessid = $_GET['sessid'];
$path = session_save_path() . '/' . $sessid;
$contents=file_get_contents($path);
session_start();
session_decode($contents);
echo(json_encode($_SESSION));
session_unset();


?>