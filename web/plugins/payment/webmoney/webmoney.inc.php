<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: WebMoney payment plugin
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

function webmoney_get_dump($var){
//dump of array
$s = "";
foreach ((array)$var as $k=>$v)
    $s .= "$k => $v<br />\n";
return $s;
}

add_paysystem_to_list(
array(
            'paysys_id' => 'webmoney',
            'title'     => $config['payment']['webmoney']['title'] ? $config['payment']['webmoney']['title'] : _PLUG_PAY_WEBMONEY_TITLE,
            'description' => $config['payment']['webmoney']['description'] ? $config['payment']['webmoney']['description'] : _PLUG_PAY_WEBMONEY_DESC,
            'public'    => 1,
            'fixed_price' => 0
        )
);


class payment_webmoney extends payment {
    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){

        global $config, $db;

        $product = $db->get_product($product_id);
	
	$price = 0 + $price;

        $vars = array(
            'url'     => urlencode($config['root_url'] . "/plugins/payment/webmoney/thanks.php"),
            'purse'   => $this->config['purse'],
            'amount'  => $price,
            'method'  => 'GET',
            'desc'    => urlencode($payment_id . ": " . $product['title']),
            'mode'    => $this->config['testing'] ? 'test' : ''
        );

        $pm = $db->get_payment($payment_id);
        $pm['data']['wm_vars'] = $vars;
        $db->update_payment($pm['payment_id'], $pm);

        $db->log_error("WebMoney SENT: " . webmoney_get_dump($vars));

        $url = "https://light.wmtransfer.com/pci.aspx";
        switch ($this->config['interface']){
	    	case 'rus':
        	    $url = "https://light.webmoney.ru/pci.aspx";
        	    break;
        	case 'eng':
        	    $url = "https://light.wmtransfer.com/pci.aspx";
        	    break;
        	case 'keeper':
        	    $url = "wmk:paylink";
		    $vars['url'] = "<".$vars['url'].">";
        	    break;
        }

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $vars1[] = "$kk=$vv";
        }
        $vars = join('&', $vars1);
        //header("Location: $url?$vars");
	html_redirect ($url . "?" . $vars, 0, 'Please wait', 'Please wait');
        exit();
    }

    function validate_thanks(&$vars){    	global $db;

        $desc = $vars['pci_desc'];
        preg_match("/^(\d+): (.*)/i", $desc, $matches);
        $payment_id = intval($matches[1]);
        $vars['payment_id'] = $payment_id;

        $pm = $db->get_payment($payment_id);
        $wm_vars = $pm['data']['wm_vars'];

        $sign = md5($vars['pci_wmtid'] . $vars['WMID'] .
            md5(strtoupper($wm_vars['url'] . $wm_vars['purse'] . $wm_vars['amount'] . $wm_vars['desc'] . $wm_vars['mode'])) .
            $vars['pci_pursesrc'] . $vars['pci_pursedest'] . $vars['pci_amount'] . $vars['pci_desc'] . $vars['pci_datecrt'] .
            $vars['pci_mode'] . md5($this->config['password']));

        if ($vars['pci_marker'] != $sign)
            return 'WebMoney callback validation error';
        else
            return '';
    }

    function process_thanks(&$vars){
            global $db;

            $desc = $vars['pci_desc'];
            preg_match("/^(\d+): (.*)/i", $desc, $matches);
            $payment_id = intval($matches[1]);
            $pm = $db->get_payment($payment_id);

            if ($pm['amount'] != $vars['pci_amount'])
                return 'WebMoney ERROR: Wrong amount';

            if ($this->config['purse'] != $vars['pci_pursedest'])
                return 'WebMoney ERROR: Wrong Purse';

            if ($this->config['testing'] != $vars['pci_mode'])
                return 'WebMoney ERROR: Wrong Mode';

            $pnref  = $vars['pci_wmtid'];
            $amount = $vars['pci_amount'];

            $err = $db->finish_waiting_payment($payment_id, 'webmoney', $pnref, '', $vars);
            if ($err)
                return "WebMoney ERROR" . $err;

            return ''; // do nothing
    }

}
?>