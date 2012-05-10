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
*    Release: 3.1.9PRO ($Revision: 3898 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


add_paysystem_to_list(
array(
            'paysys_id' => 'multicards',
            'title'     => $config['payment']['multicards']['title'] ? $config['payment']['multicards']['title'] : _PLUG_PAY_MULTICARD_TITLE,
            'description' => $config['payment']['multicards']['description'] ? $config['payment']['multicards']['description'] : _PLUG_PAY_MULTICARD_DESCR,
            'public'    => 1,
            'fixed_price' => 1
        )
);

// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_multicards extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $db, $config, $plugin_config;

        $t = & new_smarty();
        $t->template_dir = dirname(__FILE__);
        $page_id = $this->config['page_id'];
        if (isset($vars['action']) && $vars['action'] == 'renew')
           $page_id = $this->config['page_id2'];
        $t->assign(array(
            'header' => $config['root_dir'] . '/templates/header.html',
            'footer' => $config['root_dir'] . '/templates/footer.html',
            'this_config' => $this->config,
            'page_id' => $page_id,
            'member'  => $db->get_user($member_id),
            'payment' => $pm = $db->get_payment($payment_id),
            'product' => $db->get_product($pm['product_id'])
        ));
        $t->display('multicards.html');
        exit();
    }
}

?>
