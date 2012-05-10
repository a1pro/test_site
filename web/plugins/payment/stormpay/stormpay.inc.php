<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");


add_paysystem_to_list(
array(
            'paysys_id' => 'stormpay',
            'title'     => $config['payment']['stormpay']['title'] ? $config['payment']['stormpay']['title'] : _PLUG_PAY_STORMPAY_TITLE,
            'description' => $config['payment']['stormpay']['description'] ? $config['payment']['stormpay']['description'] : sprintf(_PLUG_PAY_STORMPAY_DESC, '<a href="http://www.stormpay.com" target=_blank>', '</a>'),
            'public'    => 1
        )
);

class payment_stormpay extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $products = $product_id;
        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];

        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];

        $member = $db->get_user($member_id);

        $vars = array(
            'payee_email'    => $this->config['business'],
            'payer_email'  => $member['email'],
            'transaction_ref'  => $payment_id,
            'return_URL'      =>
               sprintf("%s/thanks.php?member_id=%d&product_id=%d",
                $config['root_url'],
                $member_id, $product_id),
            'cancel_URL' => $config['root_url']."/cancel.php",
            'notify_URL'  => $config['root_url']."/plugins/payment/stormpay/ipn.php",
            'amount'      => sprintf('%.2f', $price),
            'product_name' => $product->config['title'],
            'generic' => 1,
            'require_IPN' => 1,
        );

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        html_redirect("https://www.stormpay.com/stormpay/handle_gen.php?$vars",
            '', 'Please wait', 'Please wait');
        exit();
    }
}

?>
