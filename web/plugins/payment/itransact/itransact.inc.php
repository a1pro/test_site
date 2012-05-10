<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: itransact Payment Plugin
*    FileName $RCSfile: itransact.inc.php,v $
*    Release: 3.1.9PRO ($Revision: 1.1.2.2 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

class payment_itransact extends amember_payment {
    var $title = "iTransact";
    var $description = "secure credit card payment";
    var $fixed_price=0;
    var $recurring=0;
    var $built_in_trials=0;
    ///
    function do_bill($amount, $title, $products, $u, $invoice){
        global $config;
        $vars = array(  
            'vendor_id'  => $this->config['company_id'],
            'mername'    => $this->config['company_title'],
            'home_page'  => $config['root_url'],
            'ret_addr'   => $config['root_url']."/plugins/payment/itransact/thanks.php",
            '1_desc'     => $title,
            '1_cost'     => sprintf('%.2f', $amount),
            '1_qty'      => 1,
            
            'showcvv'    => 1,
            'acceptcards'   => 1,
            'acceptchecks'  => 1,
            'ret'           => 'post',
            'passback'      => "payment_id",
            'payment_id'    => $invoice,

            'first_name' => $u['name_f'],
            'last_name'  => $u['name_l'],
            'address'   => $u['street'],
            'city'      => $u['city'],
            'zip'       => $u['zip'],
            'state'     => $u['state'],
            'country'   => $u['country'],
            'phone'       => $u['data']['phone'] ? $u['data']['phone'] : $u['phone'],
            'email'       => $u['email']
            
        );

        return $this->html_form("https://secure.paymentclearing.com/cgi-bin/mas/split.cgi", $vars);
    }
    function html_form($url, $vars){
        print "<html><body><center><br><br>";
        print "<form method=post action='$url'>";
        foreach ($vars as $k => $v){
            $v = htmlentities($v);
            print "<input type=hidden name='$k' value='$v'>\n";
        }            
        print "<input type=submit value='Press this button for continue....'>\n";
        print "</form>";
        print "</body></html>";
    }
    function process_postback($vars){
        global $db, $t;

        $payment_id = $vars['payment_id'];
        
//        if (!preg_match('/secure\.paymentclearing\.com/', $r=$_SERVER['HTTP_REFERER'])) 
//            $this->postback_error("Bad Refering URL - $r"); 

        // process payment
        $err = $db->finish_waiting_payment(
            $payment_id, $this->get_plugin_name(), 
            $invoice, $amount='', $vars);

        if ($err) 
            $this->postback_error("finish_waiting_payment error: $err");
            
        $t = & new_smarty();
        $pm = $db->get_payment($payment_id);
        $t->assign('payment', $pm);
        $t->assign('product', $pr);
        $t->assign('member', $db->get_user($pm['member_id']));                
        $t->display("thanks.html");
    }
}

$pl = & instantiate_plugin('payment', 'itransact');

?>
