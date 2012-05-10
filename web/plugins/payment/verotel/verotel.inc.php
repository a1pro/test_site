<?php 


add_product_field(
            'is_recurring', 'Recurring Billing',
            'select', 'should user be charged automatically<br />
             when subscription expires',
            '',
            array('options' => array(
                '' => 'No',
                1  => 'Yes'
            ))
);

add_paysystem_to_list(
array(
            'paysys_id' => 'verotel',
            'title'     => $config['payment']['verotel']['title'] ? $config['payment']['verotel']['title'] : _PLUG_PAY_VEROTEL_TITLE,
            'description' => $config['payment']['verotel']['description'] ? $config['payment']['verotel']['description'] : _PLUG_PAY_VEROTEL_DESC,
            'public'    => 1,
            'recurring'   => 1,
            'fixed_price' => 1
        )
);

add_product_field(
            'verotel_id', 'VeroTel Site ID',
            'text', ''
);

function verotel_vsf(&$vars){
    $err = array();
    if (preg_match('/^.*_+.*$/', $vars['login'])){
        $err[] = "Verotel does not accept usernames with underscores";
    }
    return $err;
}

setup_plugin_hook('validate_signup_form', 'verotel_vsf');

class payment_verotel extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $product = & get_product($product_id);
        $member  = $db->get_user($member_id);

        $c_product_id = $product->config['verotel_id'];

        if (!$c_product_id)
            fatal_error("Verotel Product ID empty for Product# $product_id");

        $vars = array(
            'verotel_id'    => $this->config['merchant_id'],
            'verotel_product' => $c_product_id,
	    'verotel_website'     => $c_product_id,
            'verotel_usercode'  => $member['login'],
            'verotel_passcode'  => $member['pass'],
            'verotel_custom1'      => $payment_id,
            'verotel_custom2'      => $member_id,
            'verotel_custom3'      => $product_id
        );
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://secure.verotel.com/cgi-bin/vtjp.pl?$vars");
        exit();
    }
}

?>
