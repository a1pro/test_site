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
            'paysys_id' => 'ematters',
            'title'     => $config['payment']['ematters']['title'] ? $config['payment']['ematters']['title'] : _PLUG_PAY_EMATTERS_TITLE,
            'description' => $config['payment']['ematters']['description'] ? $config['payment']['ematters']['description'] : _PLUG_PAY_EMATTERS_DESC,
            'public'    => 1
        )
);

class payment_ematters extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $product = & get_product($product_id);
        $member = $db->get_user($member_id);

        $title = $product->config['title'];

        $vars = array(  
            '__Click'           => 0,
            'UID'               => $payment_id,
            'CompanyName'       => $this->config['company_name'],
            'Returnemail'       => $config['admin_email'],
            'ReturnHTTP'        => "[$config[root_url]/plugins/payment/ematters/ipn.php?",
            'MerchantID'        => $this->config['merchant_id'],
            'SendeMail'         => $this->config['send_email'],
            'ABN'               => $this->config['abn'],
            'Bank'              => $this->config['bank'],
            'Platform'          => 'ASP',
            'Mode'              => $this->config['testing']?'Test':'Live',
            'readers'           => $this->config['readers'],
            'Desc'              => $title,
            'Name'              => $member['name_f'].' '.$member['name_l'],
            'Email'             => $member['email'],
            'FinalPrice'        => '$'.sprintf('%.2f', $price)
        );

        global $t;
        $t->assign('vars', $vars);
        $t->assign('member', $member);
        $t->display(dirname(__FILE__)."/pay.html");
        exit();
    }
}

?>
