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
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

add_paysystem_to_list(
array(
            'paysys_id' => 'vanco',
            'title'     => $config['payment']['vanco']['title'],
            'description' => $config['payment']['vanco']['description'],
            'public'    => 1,
            'fixed_price' => 1
        )
);

class payment_vanco extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $db, $config, $plugin_config;      
		$pm = $db->get_payment($payment_id);
		$member = $db->get_user($member_id);
		
        $t = & new_smarty();
        $t->assign(array(
			'url' => $config["root_url"],
            'this_config' => $this->config,
            'currency' => $config['currency'],
            'title' => $this->config['title'],
            'description' => $this->config['description'],
            'member'  => $member,
            'member_id' => $member_id,
            'payment' => $pm,
            'product' => $db->get_product($pm['product_id']),
			'start_date' => gmdate('m/d/Y'),
			'first_name' => $member['name_f'],
			'last_name' => $member['name_l'],
			'address' => $member['street'],
			'city' => $member['city'],
			'state' => $member['state'],
			'zip' => $member['zip'],
			'country_code' => $member['country']
		));	
		$t->display(dirname(__FILE__).'/vanco.html');
        exit();	
    }
}

?>
