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
            'paysys_id' => 'qchex',
            'title'     => $config['payment']['qchex']['title'] ? $config['payment']['qchex']['title'] : _PLUG_PAY_QCHEX_TITLE,
            'description' => $config['payment']['qchex']['description'] ? $config['payment']['qchex']['description'] : sprintf(_PLUG_PAY_QCHEX_DESC, '<a href="http://www.qchex.com" target=_blank>', '</a>'),
            'public'    => 1
        )
);

class payment_qchex extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $product = & get_product($product_id);
        $u = $db->get_user($member_id);
        
        $vars = array(
            'MerchantID' => $this->config['merchant_id'],
            'Amount'     => $price,
            'RefNo'      => 'AMEMBER-'.$payment_id,
            'PaymentCurrency' => 'USD',
            'PayourName' => $u['name_f'] . ' ' . $u['name_l'],
            'PayourAddress' => $u['street'],
            'PayourCity' => $u['city'],
            'PayourState' => $u['state'],
            'PayourZipcode' => $u['zip'],
            'Memo'       => 'Bank-Country='.$this->config['country'],
            'ReturnTo'      => 
               sprintf("%s/plugins/payment/qchex/thanks.php?payment_id=%d&member_id=%d&product_id=%d",
                $config['root_url'],
                $payment_id, $member_id, $product_id),
            'SendBackAuthCode' => 1,   
            'SendBackMethod'   => 'P'
        );
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://www.qchex.com/pay.asp?$vars");
        exit();
    }
    function validate_thanks(&$vars){
        if (!$vars['QchexApprovalCode']) 
            return 'Transaction failure';
        if (!$vars['QchexRefNo']) 
            return 'Transaction failure';
        return '';
    }
    function process_thanks(&$vars){
            global $db;
            $payment_id = str_replace('AMEMBER-', '', $vars['QchexRefNo']);
            $err = $db->finish_waiting_payment($payment_id, 
                    'qchex', $vars['QchexApprovalCode'], '',
                     $vars);
            if ($err) 
                return "finish_waiting_payment error: $err";
    }
}

?>
