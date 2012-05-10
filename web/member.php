<?php
/*
*   Members page. Used to renew subscription.
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Member display page
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5171 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
* =============================================================================
*
*	Revision History:
*	-----------------
*	2010-10-24	K.Gary	v3.2.3	Updated update_subscriptions to toggle unsubscribe flag.
*
* =============================================================================
*/


include('./config.inc.php');
$t = & new_smarty();
$_product_id = array('ONLY_LOGIN');

require($config['plugins_dir']['protect'] . '/php_include/check.inc.php');

if (($_SESSION['_amember_user']['email_verified'] < 0) && ($config['verify_email'])){
    $u = $_SESSION['_amember_user'];
    $v = md5($u['member_id'].$u['login'].$u['email']);
    fatal_error(sprintf(_MEMBER_ERROR_1,"<br />","<br />","<br />","<a href='resend.php?member_id={$_SESSION[_amember_id]}&v=$v'>",
    "</a>"), 1,1);
}


/// redirect to make F5 key (Refresh) working
if (strlen($_POST['amember_pass']) && ($_SERVER["REQUEST_METHOD"] == 'POST')){
    $url = $_SERVER['REQUEST_URI'];
    srand(time());
    $r = (!preg_match('/\?/', $url))? '?r=' : '&r=';
    $url .= $r. rand(10000,99999);
    html_redirect($url, 0, 'Redirect', _MEMBER_REDIRECTING);
    exit();
}

////////////////////////////////////////////////////////////////////////

function rcmp_begin_date($a, $b){
    return strcmp($b['begin_date'], $a['begin_date']);
}

function check_product_scope($product_id, $member_id){
    // return '' if allowed
    // return error message if denied
    global $db;
    $product = $db->get_product($product_id);
    if (!$product['scope']) return;
    if ($product['scope'] == 'member'){
        //check that customer paid
        if (count($db->get_user_payments($member_id,1)))
            return;
        else
            return _MEMBER_ONLY4_PAID;
    }
    if ($product['scope'] == 'signup'){
        //check that customer paid
        if (!count($db->get_user_payments($member_id,1)))
            return;
        else
            return _MEMBER_ONLY4_NEW;
    }
    return _MEMBER_NOT4_ORDER;
}


function do_renew(){
    global $_SESSION;
    global $_amember_id;
    global $config, $db, $t, $vars, $plugins, $error;

    $member_id = intval($_amember_id);

    $error = array_merge((array)$error, (array)plugin_validate_member_form($vars));
    if (count($error)){
        $t->assign('error', $error);
        return;
    }

    $vars['product_id'] = is_array($vars['product_id']) ?
        array_filter(array_map('intval',$vars['product_id'])) :
        intval($vars['product_id']);

    if (!$vars['product_id']) {
        $t->assign('error', _MEMBER_SELECT_PRODUCT);
        return;
    }

    if (($vars['coupon']!='') && $config['use_coupons']){
        $coupon = $db->coupon_get($vars['coupon'],$_SESSION[_amember_id]);
        if (is_string($coupon)){
            $t->assign('error', $coupon) ;
            return;
        }
    }

    $pc = & new PriceCalculator();
    $pc->addProducts($vars['product_id']);
    if ($config['use_coupons'] && $vars['coupon'] != ''){
        $coupon = $db->coupon_get($vars['coupon'],$_SESSION[_amember_id]);
        if ($coupon['coupon_id'])
            $pc->setCouponDiscount($coupon['discount'], split(',',trim($coupon['product_id'])));
    }
    $pc->setPriceFieldsByPaysys($vars['paysys_id']);
    $pc->setTax(get_member_tax($member_id));
    $terms = & $pc->calculate();
    $price = $terms->total;


    if (($price == 0) && !product_get_trial($vars['product_id']) &&
	(($terms->discount > 0 && !$coupon['is_recurring']) || !$terms->discount)
            && in_array('free', $plugins['payment']))
        $vars['paysys_id'] = 'free';

    if ($config['product_paysystem']){
        $pr = get_product(is_array($vars['product_id'])?$vars['product_id'][0]:$vars['product_id']);
        $vars['paysys_id'] = $pr->config['paysys_id'];
    }
    if (!$vars['paysys_id']) {
        $t->assign('error', _MEMBER_SELECT_PAYMENT);
        return;
    }

    //check for agreement
    $display_agreement = 0;
    foreach ((array)$vars['product_id'] as $pid){
        $product = $db->get_product($pid);
        if ($product['need_agreement'])
            $display_agreement++;
    }

    $member = $db->get_user($member_id);

    if ($display_agreement && !$member['data']['i_agree'] && !$vars['i_agree']){
        display_agreement(serialize($vars)); // defined in the  product.inc.php
        exit();
    }

    if ($vars['i_agree'] && !$member['data']['i_agree']){
        $member['data']['i_agree']++;
        $db->update_user($member_id, $member);
    }

    ///
    do { // for easy exit using break;
        $paysys_id = $vars['paysys_id'];
        $product_id = $vars['product_id'];
        if (!is_array($product_id)) $product_id = array($product_id);
        foreach ((array)$vars['product_id'] as $pid){
            $error = check_product_scope($pid, $_amember_id);
            if ($error) break;
        }

        if ($error = check_product_requirements($product_id,
                get_product_requirements_for_member($_amember_id)))
            break;

//        if ($terms->discount > 0)
            $vars['COUPON_CODE'] = $vars['coupon'];

        global $payment_additional_fields;
        $additional_values = array();
        foreach ($payment_additional_fields as $f){
            $fname = $f['name'];
            if (isset($vars[$fname]))
                $additional_values[$fname] = $vars[$fname];
        }
        $additional_values['COUPON_DISCOUNT'] = $terms->discount;
        $additional_values['TAX_AMOUNT'] = $terms->tax;
        $taxes = $prices = array();
        foreach ($terms->lines as $pid => $line){
            $prices[$pid] = $line->total;
            if ($line->tax)
                $taxes[$pid] = $line->tax;
        }
        $additional_values['TAXES'] = $taxes;

        $product       = & get_product($product_id[0]);
        $begin_date    = $product->get_start($member_id);
        $expire_date   = $product->get_expire($begin_date, null, $terms); //yyyy-mm-dd
        // add payment
        $payment_id    = $db->add_waiting_payments($member_id, $product_id,
            $paysys_id, $price, $prices, $begin_date, $expire_date, $vars,
            $additional_values);

        $error = plugin_do_payment($paysys_id, $payment_id, $member_id,
            is_array($product_id) ? $product_id[0] : $product_id,
            $price, $begin_date, $expire_date, $vars);
        if ($error) {
            $db->delete_payment($payment_id);
            break;
        }
        exit();
    } while (0);
    //if we here, error was occured
    $t->assign('error', $error);
    return ;
}

function check_renewal_allowed($product, $products_active){
    global $config, $db;
    switch ($config['limit_renewals']){
        case 0:// don't check
            return 1;
        case 1: // check if the same product
            return !in_array($product['product_id'], $products_active);
        case 2: // check if the same group
            foreach ($products_active as $i){
                $pr = $db->get_product($i);
                if ($pr['renewal_group'] == $product['renewal_group'])
                    return 0;
            }
            return 1;
        case 3: // check if any active
            return !$products_active;
    }
    return 0;
}


function update_subscriptions () {
    global $config, $_product_id, $t, $db, $vars;
    $_amember_id = $_SESSION['_amember_id'];
    $member_id = intval($_amember_id);

    $db->delete_member_threads($member_id);
    if (!$vars['unsubscribe']){

        $q = $db->query($s = "
            UPDATE {$db->config['prefix']}members
            SET unsubscribed=0
            WHERE member_id=$member_id
        ");
        $db->add_member_threads($member_id, $vars['threads']);

    } else {

        $q = $db->query($s = "
            UPDATE {$db->config['prefix']}members
            SET unsubscribed=1
            WHERE member_id=$member_id
        ");

    }

          //
          // Begin Mod for aMail Plugin...
          //
          $newmember = $db->get_user($member_id);
          $oldmember = $newmember;
          $oldmember['unsubscribed'] = ($newmember['unsubscribed']) ? 0 : 1;
          plugin_subscription_updated($member_id,$oldmember,$newmember);
          //
          // End Mod for aMail Plugin
          //

        html_redirect("member.php", false,
            _TPL_NEWSLETTER_INFO_SAVED, _TPL_NEWSLETTER_INFO_UPDATED);
        exit;
}


///////////////////////// MAIN /////////////////////////////////////////
unset($GLOBALS['_trial_days']); // trial handling
$_amember_id = $_SESSION['_amember_id'];
$vars = get_input_vars();

if ($vars['action'] == 'get_invoice' && $vars['id'] > 0){

    $id = intval($vars['id']);
    if ($config['send_pdf_invoice']){
        require_once("$config[root_dir]/includes/fpdf/fpdf.php");
        $invoice = get_pdf_invoice($id, $_amember_id);

        header('Cache-Control: maxage=3600');
        header('Pragma: public');
        header("Cache-control: private");
        header("Content-type: application/pdf");
        header("Content-Length: ".strlen ($invoice['string']));
        header("Content-Disposition: attachment; filename=amember-invoice-$id.pdf");
        print $invoice['string'];
        exit;
    }

}

if ($vars['action'] == 'renew'){
    do_renew();
} elseif ($vars['action'] == 'cancel_recurring'){
    $p = $db->get_payment($vars['payment_id']);
    if ($p['member_id'] != $_amember_id)
        die(_MEMBER_ID_NOT_MATCH);
    $p['data']['CANCELLED']++;
    $db->update_payment($vars['payment_id'], $p);
    $t->assign('title', _MEMBER_SUBSCR_CANCELLED);
    $t->assign('msg', _MEMBER_RSUB_CANCELLED);
    $t->display("msg_close.html");
    if ($config['send_cancel_admin']){
        $u = $_SESSION['_amember_user'];
        mail_admin(sprintf(_MEMBER_MAIL_ADMIN,$u[login],$vars[payment_id]),
        _MEMBER_MAIL_THEME);
    }
    exit();
} elseif ($vars['do_agreement']) {

    if (!$vars['i_agree']){
        global $error;
        $error[] = _MEMBER_ERROR;
        display_agreement($vars['data']);
        exit();
    }
    $vars = unserialize($vars['data']);
    $vars['i_agree']++;
    do_renew();
}

// common processing
// get product list (to fill $_product_id also)
$products = & $db->get_products_list();
$pp = array();
$_product_id = array();
foreach ($products as $p)   {
    $pp[ $p['product_id'] ] = $p['title'] ;
    $_product_id[] = $p['product_id'];
}
$t->assign('products', $pp);

$payments = & $db->get_user_payments(intval($_amember_id), 1);
usort($payments, 'rcmp_begin_date');
$now = date('Y-m-d');
$member_active = $member_paid = 0;
foreach ($payments as $k=>$v){
    $payments[$k]['is_active'] =
    (($v['expire_date'] >= $now) && ($v['begin_date'] <= $now))? 1 : 0;
    if ($payments[$k]['is_active']) $member_active++;
    if ($v['completed']) $member_paid++;
    // try to display "Cancel" Link
    if ($payments[$k]['expire_date'] >= date('Y-m-d')){
        $paysys = get_paysystem($v['paysys_id']);
        $product = $db->get_product($v['product_id']);
        if ($paysys['recurring']
            && ($pay_plugin = &instantiate_plugin('payment', $v['paysys_id']))
            && $product['is_recurring']
            && method_exists($pay_plugin, 'get_cancel_link')){
            $payments[$k]['cancel_url'] =
                $pay_plugin->get_cancel_link($v['payment_id']);
        }

    }
}
$t->assign('payments', $payments);
///////////////////////////////////////////////////
$member_products = $_SESSION['_amember_products'];
foreach ((array)$member_products as $k => $pr){
    $member_products[$k]['url'] = add_password_to_url($pr['url']);
    foreach ((array)$pr['add_urls'] as $u=>$kk){
        $uu=add_password_to_url($u, $member_login_pw);
        unset($member_products[$k]['add_urls'][$u]);
        $member_products[$k]['add_urls'][$uu] = $kk;
    }
}

$t->assign('member_products', $member_products);


if ($member_paid)
    $member_scope_allowed = array('', 'member');
else // signup
    $member_scope_allowed = array('', 'signup');

$products_to_renew = $products;
$products_active = array();
$dat = date('Y-m-d');
foreach ($db->get_user_payments(intval($_amember_id), 1) as $p)
    if (($p['begin_date'] <= $dat) && ($p['expire_date'] >= $dat))
        $products_active[] = $p['product_id'];
foreach ($products_to_renew as $k=>$v){
    if (!in_array($v['scope'], $member_scope_allowed))
        unset($products_to_renew[$k]);

    if(is_array($_GET['price_group'])){
            if(!array_intersect($_GET['price_group'], split(',',$v['price_group'])))
                unset($products_to_renew[$k]);
    }elseif ($_GET['price_group']){
        if (!in_array($_GET['price_group'], split(',',$v['price_group'])) )
            unset($products_to_renew[$k]);
    } elseif ($v['price_group'] < 0){
        unset($products_to_renew[$k]);
    } elseif (!check_renewal_allowed($v, $products_active)){
        unset($products_to_renew[$k]);
    }
    if ($err = check_product_requirements(array($v['product_id']),
            get_product_requirements_for_member($_amember_id))){
        unset($products_to_renew[$k]);
    }

    if ($products_to_renew[$k] && ($products_to_renew[$k]['terms'] == '')){
    	$pr = & new Product($products_to_renew[$k]);
    	$products_to_renew[$k]['terms'] = $pr->getSubscriptionTerms();
    }
}
$t->assign('products_to_renew', $products_to_renew);

$paysystems = get_paysystems_list();
$pp = array();
foreach ($paysystems as $p)
    if ($p['public'])
        $pp[ $p['paysys_id'] ] = $p['title'] ;
$t->assign('paysystems', $pp);

$pp1 = $pp;
//remove free paysystem from select
if (count($pp1) > 1)
    foreach ($pp1 as $k=>$p)
        if ($k == 'free') unset($pp1[$k]);
$t->assign('paysystems_select', $pp1);


// newsletters form
if ($vars['action'] == 'newsletters_update'){
    update_subscriptions ();
}
$m = $db->get_user($_amember_id);
$unsubscribed = $m['unsubscribed'];
//$threads_count = $db->get_threads_list_c($_amember_id);
//$threads_list = $db->get_threads_list(0, $db->get_threads_list_c(), $_amember_id);
$threads_list = array();
$threads_list2 = $db->get_threads_list(0, $db->get_threads_list_c());
foreach ($threads_list2 as $k=>$v){
	if ($db->is_subscription_possible($m['member_id'], $m['status'], $v['thread_id']))
	    $threads_list[] = $v;
}
$threads_count = count($threads_list);

$threads = $db->get_member_threads($_amember_id);
while (list($thread_id, ) = each ($threads)){
    if (!$unsubscribed)
        $threads[$thread_id] = '1';
    else
        $threads[$thread_id] = '0';
}
$t->assign('threads_list', $threads_list);
$t->assign('threads', $threads);
$t->assign('unsubscribed', $unsubscribed);

//newsletters archive
if (isset($vars['start'])) $start = $vars['start'];
//$db->delete_old_newsletters();
$all_count = $db->get_archive_list_c($vars['thread_id'], $_amember_id);
$count = 5;
$al = & $db->get_archive_list($start, $count, $vars['thread_id'], $_amember_id);
$t->assign('al', $al);


//Member Coupons
$member_coupons = array();
if( $config[ 'use_coupons' ] )
{
	$xcc = $db->get_coupons( 'member', $_amember_id );
	foreach( $xcc as $cc )
	{
	// Coupon Get does all the checking so it is better this way....
		$onecc = $db->coupon_get( $cc[ 'code' ], $_amember_id );
		if( ! is_array( $onecc ) )
		{
			continue;
		}
		if( ! strpos( $onecc[ 'discount' ], "%" ) )
		{
			$onecc[ 'discount' ] = "$" . $onecc[ 'discount' ];
		}
		$onecc[ 'product_id' ] = explode( ",", $onecc[ 'product_id' ] );
		$member_coupons[] = $onecc;
	}
}
$t->assign( 'member_coupons', $member_coupons );


$member_links = plugin_get_member_links($_SESSION['_amember_user']);

$t->assign('member_links', $member_links);
$left_member_links = plugin_get_left_member_links($_SESSION['_amember_user']);
$t->assign('left_member_links', $left_member_links);
$t->assign('user', $_SESSION['_amember_user']);
$t->display('member.html');
?>