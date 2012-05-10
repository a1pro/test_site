<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: payflow_pro payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;
$_title = $config['payment']['anylink']['title'];
if ($_title == '') $_title = _PLUG_PAY_ANYLINK_TITLE;

add_paysystem_to_list(
array(
            'paysys_id'   => 'anylink',
            'title'       => $_title,
            'description' => $config['payment']['anylink']['description'] ? $config['payment']['authorize']['description'] : _PLUG_PAY_ANYLINK_CCACCEPTED,
            'recurring'   => 0,
            'public'      => 1,
            'fixed_price' => 1
        )
);

add_product_field(
            'anylink_url', 'AnyLink URL',
            'text', 'URL where user will be redirected to make payment<br />
            if he chooses "anylink" payment option',
             'validate_anylink_url'
);

class payment_anylink extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        global $config, $db;
        $p = $db->get_payment($payment_id);
        $pr = $db->get_product($p['product_id']);
        $u = $db->get_user($member_id);
        $url = $pr['anylink_url'];
        foreach ($u as $k=>$v)
            $url = str_replace('{$member.'.$k.'}', $v, $url);
        $url = str_replace('{$payment_id}', $payment_id, $url);
        if ($url == '') 
            fatal_error(sprintf(_PLUG_PAY_ANYLINK_REDIR_ERROR,$p[product_id]));
        html_redirect("$url", 0, _PLUG_PAY_REDIRECT_WAIT, _PLUG_PAY_REDIRECT_REDIRECT);
        exit();
    }
}

function validate_anylink_url(&$p, $field){  
    if ($p->config[$field] == '') 
        return "Please enter AnyLink URL for this product. If you don't like this requirement, disable AnyLink payment plugin";
    if (!preg_match('|http(s*)://.+/.+|', $p->config[$field])) 
        return "AnyLink URL must start from http:// or https:// and be valid absolute URL";
    return '';
}

?>
