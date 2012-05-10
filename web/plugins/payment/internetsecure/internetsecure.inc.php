<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Internetsecure Payment Plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1781 $)
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
            'paysys_id' => 'internetsecure',
            'title'     => $config['payment']['internetsecure']['title'] ? $config['payment']['internetsecure']['title'] : _PLUG_PAY_INTERSEC_TITLE,
            'description' => $config['payment']['internetsecure']['description'] ? $config['payment']['internetsecure']['description'] : _PLUG_PAY_INTERSEC_DESC,
            'public'    => 1
        )
);

class payment_internetsecure extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $product = & get_product($product_id);
        $member = $db->get_user($member_id);

        $vars = array(  
            'MerchantNumber'       => $this->config['merchant_id'],
            'Products'             => "$price::1::999::".$product->config['title']."::", 
            'ReturnCGI'            => $config['root_url']."/plugins/payment/internetsecure/thanks.php?payment_id=$payment_id",
            'xxxName'              => $member[name_f]." ".$member[name_l],
            'xxxAddress'           => $member[street],
            'xxxCity'              => $member[city],
            'xxxProvince'          => $member[state],
            'xxxCountry'           => $member[contry],
            'xxxPostal'            => $member[zip],
            'xxxEmail'             => $member[email]
        );
        if ($this->config['testing']) 
            $vars['Products'] .= "{TEST}";
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://secure.internetsecure.com/process.cgi?$vars");
        exit();
    }
    function validate_thanks(&$vars){
        if ($vars['ApprovalCode'] && $vars['receiptnumber'] && $_GET['payment_id']){
            return '';
        } else {
            $s = '';
            foreach ($vars as $k=>$v)
                $s .= "$k => $v<br />\n";
            global $db;
            $db->log_error("internetsecure problem: _GET[payment_id]='".
                $_GET['payment_id'] . "'<br />" . $s
                );
            return _PLUG_PAY_INTERSEC_ERROR;
        }
    }
    function process_thanks(&$vars){
            global $db;
            $err = $db->finish_waiting_payment(intval($_GET['payment_id']), 
                    'internetsecure', $vars['receiptnumber'], $vars['amount'],
                     $vars);
            if ($err) 
                return "finish_waiting_payment error: $err";
    }
}

?>
