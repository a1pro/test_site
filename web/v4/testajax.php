<?php
header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Content-Type: application/x-javascript");
header('Expires: Tue, 01 Jan 2000 12:12:12 GMT');
$output = Array();
require("bootstrap.php");
$output["priv"] = null;
if (is_array($_SESSION["amember_auth"]["user"])){
        $user = $_SESSION["amember_auth"]["user"];
//      $output["remote_addr"] = $user["remote_addr"];
        $output["logged"] = true;
        $output["name"] = $user["name_f"] . " " . $user["name_l"];
        $output["status"] = $user["status"];
        $output["priv"] = Am_Di::getInstance()->userTable->findFirstByEmail($user['email'])->getActiveProductIds();
} else {
$output["logged"] = false;
}
//var_dump($_SESSION);
//var_dump($user);
//var_dump(Am_Di::getInstance()->userTable->findFirstByEmail($user['email'])->getActiveProductIds());
echo('do_auth(' . json_encode($output) . ', window, document)');
?>

