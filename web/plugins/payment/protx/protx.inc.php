<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: protx payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4903 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/


add_product_field(
            'protx_currency', 'protx Currency',
            'select', 'currency for protx gateway',
            '',
            array('options' => array(
                ''     => 'USD',
                'GBP'  => 'GBP',
                'EUR'  => 'EUR',
                'JPY'  => 'JPY'
            ))
);

add_paysystem_to_list(
array(
            'paysys_id'   => 'protx',
            'title'       => $config['payment']['protx']['title'] ? $config['payment']['protx']['title'] : _PLUG_PAY_PROTX_TITLE,
            'description' => $config['payment']['protx']['description'] ? $config['payment']['protx']['description'] : _PLUG_PAY_PROTX_DESC,
            'public'      => 1
        )
);

/*  The SimpleXor encryption algorithm                                                                                **
**  NOTE: This is a placeholder really.  Future releases of VSP Form will use AES or TwoFish.  Proper encryption      **
**          This simple function and the Base64 will deter script kiddies and prevent the "View Source" type tampering    **
**          It won't stop a half decent hacker though, but the most they could do is change the amount field to something **
**          else, so provided the vendor checks the reports and compares amounts, there is no harm done.  It's still      **
**          more secure than the other PSPs who don't both encrypting their forms at all                                  */

function protx_simple_xor($InString, $Key) {
    // Initialise key array
    $KeyList = array();
    // Initialise out variable
    $output = "";
    
    // Convert $Key into array of ASCII values
    for($i = 0; $i < strlen($Key); $i++){
        $KeyList[$i] = ord(substr($Key, $i, 1));
    }

    // Step through string a character at a time
    for($i = 0; $i < strlen($InString); $i++) {
        // Get ASCII code from string, get ASCII code from key (loop through with MOD), XOR the two, get the character from the result
        // % is MOD (modulus), ^ is XOR
        $output.= chr(ord(substr($InString, $i, 1)) ^ ($KeyList[$i % strlen($Key)]));
    }
    // Return the result
    return $output;
}

class payment_protx extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];
        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];
        $u  = $db->get_user($member_id);
        $vars = array(
            'VPSProtocol' => '2.22',
            'TxType'      => 'PAYMENT',
            'Vendor'      => $this->config['login'],
        );
        $varsc = array(
            'VendorTxCode'   => $payment_id . 'AMEMBER',
            'Amount'   => number_format($price, 2, '.', ''),
            'Currency' => 'USD',
            'Description'     => $product->config['title'],
            'SuccessURL'      => $config['root_url'] . "/plugins/payment/protx/thanks.php",
            'FailureURL'      => $config['root_url'] . "/cancel.php",
            'CustomerEmail'   => $u['email'],
            'VendorEmail'     => $config['admin_email'],
            'CustomerName'       => $u['name_f'] . ' ' . $u['name_l'],
        );
        if ($u['street'] != '' && $u['zip'] != ''){
            $varsc['BillingAddress'] = $u['street'];
            $varsc['BillingPostCode'] = $u['zip'];
        }
        if ($product->config['protx_currency'])
            $varsc['Currency'] = $product->config['protx_currency'];
        $s=array();
        foreach ($varsc as $k=>$v)
            $s[]="$k=$v";
        $s = join('&', $s);
//        $s = "VendorTxCode=Test3855660123&Amount=10.00&Currency=GBP&Description=Items from the Test Vendor Site&SuccessURL=http://localhost/protx/completed.php&FailureURL=http://localhost/protx/notcompleted.php&EMailMessage=You can put your own message in here. Check the source code of the submit3 page!";
//        print $s;
        $vars['Crypt'] = base64_encode(protx_simple_xor($s, $this->config['pass']));

        if ($this->config['testing'])
            $url = "https://test.sagepay.com/gateway/service/vspform-register.vsp";
        else 
            $url = "https://live.sagepay.com/gateway/service/vspform-register.vsp";
        global $t;
        $t->assign('url', $url);
        $t->assign('vars', $vars);
        $t->display(str_replace("c:\\", '/', dirname(__FILE__).'/form.html'));
        exit();
    }
    //
    function validate_thanks(&$vars){
        $s = $vars['crypt'];
        $s = base64_decode(str_replace(" ", "+", $s));
        $s = protx_simple_xor($s, $this->config['pass']);
        parse_str($s, $vars);
        return $vars['Status'] == 'OK' ? '' : 'Status is not OK';
    }
    //
    function process_thanks(&$vars){
            global $db;
            $err = $db->finish_waiting_payment(intval($vars['VendorTxCode']), 
                    'protx', $vars['VPSTxID'], $vars['Amount'],
                     $vars);
            if ($err) 
                return "finish_waiting_payment error: $err";
    }
}

?>
