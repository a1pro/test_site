<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");


add_paysystem_to_list(
array(
            'paysys_id' => 'passwordcall',
            'title'     => $config['payment']['passwordcall']['title'] ? $config['payment']['passwordcall']['title'] : _PLUG_PAY_PSWDCALL_TITLE,
            'description' => $config['payment']['passwordcall']['description'] ? $config['payment']['passwordcall']['description'] : sprintf(_PLUG_PAY_PSWDCALL_DESC, '<a href="http://www.passwordcall.de" target=_blank>', '</a>'),
            'public'    => 1
        )
);

class payment_passwordcall extends payment {
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
            'transaction_ref'  => $payment_id,
            'wmid'    => $this->config['webmaster_id'],
            'aname1'    => $this->config['aname_id1'],
            'aname2'    => $this->config['aname_id2'],
            'aname3'    => $this->config['aname_id3'],
            'aname4'    => $this->config['aname_id4'],
            'tarif1'    => $this->config['tarif_id1'],
            'tarif2'    => $this->config['tarif_id2'],
            'tarif3'    => $this->config['tarif_id3'],
            'tarif4'    => $this->config['tarif_id4'],
            'prdid1'    => $this->config['product_id1'],
            'prdid2'    => $this->config['product_id2'],
            'prdid3'    => $this->config['product_id3'],
            'prdid4'    => $this->config['product_id4'],
            'agbid1'    => $this->config['angebots_id1'],
            'agbid2'    => $this->config['angebots_id2'],
            'agbid3'    => $this->config['angebots_id3'],
            'agbid4'    => $this->config['angebots_id4'],
            'product_id' => $product_id,
            'member_id' => $member_id,
        );

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: ".$config['root_url']."/plugins/payment/passwordcall/passwordcall.php?".$vars);
        exit();
    }
}

?>