<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayFlow Link Payment Plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3856 $)
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
            'paysys_id' => 'authorize',
            'title'     => $config['payment']['authorize']['title'] ? $config['payment']['authorize']['title'] : _PLUG_PAY_AUTHORIZE_TITLE,
            'description' => $config['payment']['authorize']['description'] ? $config['payment']['authorize']['description'] : sprintf(_PLUG_PAY_AUTHORIZE_DESCR, "<a href=\"http://www.authorize.net\">", "</a>"),
            'public'    => 1
        )
);


class payment_authorize extends payment {
/*    function hmac ($key, $data) {
    #    print "$key=$data<br />";
        return (bin2hex (mhash(MHASH_MD5, $data, $key)));
    } 
*/
    function hmac ($key, $data)
    {
       // RFC 2104 HMAC implementation for php.
       // Creates an md5 HMAC.
       // Eliminates the need to install mhash to compute a HMAC
       // Hacked by Lance Rushing

       $b = 64; // byte length for md5
       if (strlen($key) > $b) {
           $key = pack("H*",md5($key));
       }
       $key  = str_pad($key, $b, chr(0x00));
       $ipad = str_pad('', $b, chr(0x36));
       $opad = str_pad('', $b, chr(0x5c));
       $k_ipad = $key ^ $ipad ;
       $k_opad = $key ^ $opad;

       return md5($k_opad  . pack("H*",md5($k_ipad . $data)));
    }

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $product = & get_product($product_id);
        if (count($product_id)>1)
            $product->config['title'] = $config['multi_title'];

	$member = $db->get_user($member_id);

        $vars = array(  
            'x_Version'   => '3.0',
            'x_Login'     => $this->config['login'],
            'x_Test_Request' => $this->config['testing'] ? 'TRUE' : 'FALSE',
            'x_Show_Form' => 'PAYMENT_FORM',
            'x_Amount'    => $price = sprintf('%.2f', $price),
            'x_Relay_Response' => 'True',
            'x_Receipt_Link_URL' => 
                $config['root_url'] . '/thanks.php',
            'x_Relay_URL'   => $config['root_url'] .  
                '/plugins/payment/authorize/ipn.php',
            'x_Invoice_num'   => $payment_id,
            'x_Cust_ID'       => $member_id,
            'x_Description'   => $product->config['title'],

            'x_fp_sequence' => $payment_id,
            'x_fp_timestamp' => $tstamp = time(),

	    'x_address' => $member['street'],
	    'x_city' => $member['city'],
	    'x_country' => $member['country'],
	    'x_state' => $member['state'],
	    'x_zip' => $member['zip'],
	    'x_email' => $member['email'],
	    'x_first_name' => $member['name_f'],
	    'x_last_name' => $member['name_l']

        );
        $data = $this->config['login']."^".$payment_id."^".$tstamp."^".$price."^";
        $vars['x_fp_hash'] = $this->hmac($this->config['tkey'], $data);
#        print_r($vars);
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars2 = join('&', $vars1);
        header("Location: https://secure.authorize.net/gateway/transact.dll?$vars2");
        exit();
    }
}


?>
