<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PaySbuy payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1866 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

function paysbuy_get_dump($var){
//dump of array
$s = "";
foreach ((array)$var as $k=>$v)
    $s .= "$k => $v<br />\n";
return $s;
}

add_paysystem_to_list(
array(
            'paysys_id' => 'paysbuy',
            'title'     => $config['payment']['paysbuy']['title'] ? $config['payment']['paysbuy']['title'] : _PLUG_PAY_PAYSBUY_TITLE,
            'description' => $config['payment']['paysbuy']['description'] ? $config['payment']['paysbuy']['description'] : _PLUG_PAY_PAYSBUY_DESC,
            'public'    => 1,
            'fixed_price' => 0
        )
);


class payment_paysbuy extends payment {
    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){

        global $config, $db;

        $product = $db->get_product($product_id);

        $vars = array(
            'psb'     => 'psb',
            'biz'     => $this->config['merchant_id'], // char(50)
            'inv'     => $payment_id, // char(50)
            'itm'     => substr($product['title'], 0, 200), // char(200)
            'amt'     => $price,
            'reqURL'  => $config['root_url'] . "/plugins/payment/paysbuy/ipn.php", // char(50)
            'postURL' => $config['root_url'] . "/plugins/payment/paysbuy/thanks.php" // char(50)
        );

        if ($this->config['currency'])
            $vars['currencyCode'] = $this->config['currency'];

        //$db->log_error("PaySbuy SENT: " . paysbuy_get_dump($vars));

        $t = & new_smarty();
        $t->template_dir = dirname(__FILE__);
        $t->assign('vars', $vars);
        $t->display('paysbuy.html');
        exit();

    }

    function validate_thanks(&$vars){
        $vars['payment_id'] = intval(substr($vars['result'], 2));
        return '';
    }

    function process_thanks(&$vars){
            global $db;
            $result = substr($vars['result'], 0, 2);

            if ($result != '00')
                return "PaySbuy ERROR: " . $result;

            $payment_id = intval(substr($vars['result'], 2));
            $pnref = $vars['apCode'];
            $amount = $vars['amt'];

// disabled
//            $pm = $db->get_payment($payment_id);
//            if (!$pm['completed'])
//                return "PaySbuy ERROR: IPN callback hasn't been received yet";

            return ''; // do nothing
    }

}
?>
