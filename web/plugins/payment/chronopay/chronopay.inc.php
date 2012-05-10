<?php
if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Chronopay payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1785 $)
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
            'paysys_id'   => 'chronopay',
            'title'       => 'chronopay',
            'description' => 'ChronoPay.com',
            'recurring'   => 1,
            'public'      => 1,
            'fixed_price' => 1
        )
);

add_product_field
(
            'is_recurring', 'Recurring Billing',
            'select', 'should user be charged automatically<br />
             when subscription expires',
            '',
            array('options' => array(
                '' => 'No',
                1  => 'Yes'
            ))
);

add_product_field(
            'chronopay_id', 'ChronoPay product ID',
            'text', 'you must create the same product<br />
             in ChronoPay and enter its ID number here',
             ''
);

class payment_chronopay extends payment
{
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars)
    {
        global $config, $db;
        $product = & get_product($product_id);
        $u  = $db->get_user($member_id);
        $q=$db->query("select title from {$db->config[prefix]}products where product_id='$product_id'");
        $pr=mysql_fetch_assoc($q);
        $p  = $db->get_payment($payment_id);
        $vars = array(
            'cb_url'                   => "$config[root_url]/plugins/payment/chronopay/ipn.php",
            'decline_url'              => "$config[root_url]/cancel.php",
            'name'                     => $u['name_f'].' '.$u['name_l'],
            'city'                     => $u['city'],
            'state'                    => ($u['state'] == 'XX') ? 'NA' : $u['state'],
            'street'                   => $u['street'],
            'zip'                      => $u['zip'],
            'country'                  => $u['country'],
            'email'                    => $u['email'],
            'cs1'                      => $payment_id,
            'product_price'            => $price,
            'product_id'               => $product->config['chronopay_id'],
            'product_name'             => $pr['title'],
            'cb_type'                  => 'G',
            'product_price_currency'   => 'USD'
        );
        $vars1 = array();
        foreach ($vars as $kk=>$vv)
        {
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://secure.chronopay.com/index.cgi?$vars");
        exit();
    }
    function get_cancel_link($payment_id)
    {
        return "https://clients.chronopay.com";
    }
}
?>
