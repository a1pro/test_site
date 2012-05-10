<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3915 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

setup_plugin_hook('update_users', 'htpasswd_update');
setup_plugin_hook('update_payments', 'htpasswd_update');
setup_plugin_hook('daily', 'htpasswd_update');
setup_plugin_hook('subscription_rebuild', 'htpasswd_update');

function htpasswd_update($payment_id=0, $member_id=0){
    global $config, $plugin_config;
    $this_config = $plugin_config['protect']['htpasswd'];
    global $db;
    $ul = $db->get_allowed_users(); // should return array[product_id][user_login]=password
    $users = array();
    foreach ($ul as $product_id => $user){
        foreach ($user as $l => $p)
            $users[$l] = $p;
    }

    $f = @fopen($fn = "$config[data_dir]/.htpasswd", 'w');

    foreach ((array)$this_config['add_htpasswd'] as $fname) {
        $fname = trim($fname);
        if (!$fname) continue;
        $f1 = file($fname);
        foreach ($f1 as $l) fwrite($f, trim($l)."\n");
    };

    if (!$f) fatal_error("Cannot open $fn for write. Make directory $config[data_dir] and this file writeable for PHP scripts.<br /><a href='https://www.cgi-central.net/support/faq.php?do=article&articleid=28'>Please read FAQ item about this issue</a>",1,1);
    foreach ($users as $l => $p) {
        if ($plugin_config['protect']['htpasswd']['use_plain_text'] ||
            (substr(php_uname(), 0, 7) == "Windows")
        )
            $pw = $p;
        else
            $pw = crypt($p, md5(rand()));
        if (!fwrite($f, "$l:$pw\n")) fatal_error("Cannot write to $fn");
    }
    if (!fclose($f)) fatal_error("Cannot close $fn");

    $f = @fopen($fn = "$config[data_dir]/.htgroup", 'w');
    if (!$f) fatal_error("Cannot open $fn for write. Make directory $config[data_dir] and this file writeable for PHP scripts.<br /><a href='https://www.cgi-central.net/support/faq.php?do=article&articleid=28'>Please read FAQ item about this issue</a>",1,1);
    foreach ($ul as $product_id => $user){
        fwrite($f, $s = "PRODUCT_$product_id: ");
        $len = strlen($s);
        foreach ($user as $l => $p){
            $len += strlen($l) + 1;
            if ($len > 7 * 1024){
                fwrite($f, $s = "\nPRODUCT_$product_id: ");
                $len = strlen($s);
            }
            fwrite($f, $l . " ");
        }            
        fwrite($f, "\n");
    }
    fclose($f);    
}


?>
