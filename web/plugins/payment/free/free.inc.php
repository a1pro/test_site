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
*    Release: 3.1.9PRO ($Revision: 4506 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

add_paysystem_to_list(
array(
            'paysys_id' => "free",
            'title'     => _PLUG_PAY_FREE_TITLE,
            'description' => _PLUG_PAY_FREE_DESC,
            'public'    => 1
        )
);

// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_free extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        global $config;
        if ($price > 0) {
             return _PLUG_PAY_FREE_NOTFORFREE;
        }
        $vcode = md5($payment_id . $begin_date . $member_id);
        header("Location: ".$config['root_url']."/plugins/payment/free/thanks.php?payment_id=$payment_id&vcode=$vcode");
        exit();
    }

    function validate_thanks(&$vars){
        return '';
    }

    function signup_moderator_mail($payment_id, $member_id, &$vars){
        global $config, $db;
        $admin_url = $config['root_url'] . '/admin';
        mail_admin("
        New user was signed up today. 
        Please login and check it:
        $admin_url/users.php?action=edit_payment&payment_id=$payment_id&member_id=$member_id
        ", "*** New Signup ***");
    }

    function process_thanks(&$vars){
            global $db, $config;
            $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
            $payment_id = $vars['payment_id'];
            $payment    = $db->get_payment($payment_id);
            $member_id  = $payment['member_id'];
            $begin_date = $payment['begin_date'];
            if ($vars['vcode'] != md5($payment_id . $begin_date . $member_id))
                fatal_error(_PLUG_PAY_FREE_ERROR, 0);
            if ($payment['receipt_id']) {
                $root_url = $config['root_url'];
                fatal_error(
                $this->config['admin_approval'] ?               
                _PLUG_PAY_FREE_MAILSENT :
                sprintf(_PLUG_PAY_FREE_SIGNEDUP,"<a href='$root_url/member.php'>", "</a>")
                ,0,1);
            }
            if ($this->config['mail_admin'])
                $this->signup_moderator_mail($payment_id, $member_id, $vars);
            if ($this->config['admin_approval']) {
                $new_payment = $payment;
                $new_payment['receipt_id'] = $REMOTE_ADDR;
                $db->update_payment($payment_id, $new_payment);
            } else {
                $db->finish_waiting_payment(intval($vars['payment_id']), 
                    'free', $REMOTE_ADDR, '',
                     $vars);
            }
    }
}

?>
