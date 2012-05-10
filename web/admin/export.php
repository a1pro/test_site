<?php
ini_set('zlib.output_compression', 1);
ini_set('zlib.output_compression_level', 9);

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Payments
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4723 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

include "../config.inc.php";
$t = new_smarty();
include "login.inc.php";

check_lite();
@set_time_limit(0);
admin_check_permissions('export');

$user_fields = array(
    'login' => 'Username',
    'pass'  => 'Password',
    'email'  => 'Email',
    'name'  => 'Name (first and last)',
    'name_f' => 'First Name',
    'name_l' => 'Last Name',
    'street' => 'Street Address',
    'city' => 'City',
    'state' => 'State',
    'zip' => 'ZIP',
    'country' => 'Country',
    'is_male' => 'Male-1/Female-0',
    'member_id' => 'Internal Member#',
    'remote_addr' => 'IP Address',
    'unsubscribed' => 'Unsubscribed',
    'aff_id' => 'Affiliate ID',
);

foreach ($member_additional_fields as $f){
    if ($f['sql'])
    $user_fields[$f['name']] = $f['title'];
    else
    $user_fields['data.'. $f['name']] = $f['title'];
}

$payment_fields = array(
    'product_name' => 'Product/Subscription Type Name',
    'product_id' => 'Product/Subscription Type #',
    'begin_date' => 'Begin Date',
    'expire_date' => 'Expire Date',
    'amount' => 'Amount',
    'completed' => 'Completed',
    'paysys_id' => 'Payment System',
    'receipt_id' => 'Receipt #',
    'time' => 'Last Updated Time - usually payment date',
    'payment_id' => 'Internal Payment#',
    'member_id' => 'Internal Member#'
);

$subscription_types = array(
    'any' => 'All subscriptions',
    'active' => 'Active subscriptions (non-expired and completed)',
    'not_completed' => 'Not-Completed Subscriptions',
    'completed' => 'Completed Subscriptions only',
    'expired' => 'Expired Subscriptions only',
    'expired_users' => 'Expired Members only(without subscription fields)'
);

$multi_types = array(
    'discard' => 'Discard additional subscriptions, use
                       first one',
    'rows' => 'Output additional rows for every subscription',
    'cols' => 'Output additional columns for every subscription'
);


global $db;
$prod_names=array();
$q=$db->query("SELECT * FROM {$db->config[prefix]}products");
while ($row = mysql_fetch_assoc($q)){
    $prod_names[$row['product_id']]=$row['title'];
}

function display_form(){
    global $t, $vars;
    global $user_fields, $payment_fields;
    global $subscription_types, $multi_types;
    global $prod_names;
    $t->assign('user_fields', $user_fields);
    $t->assign('payment_fields', $payment_fields);
    $t->assign('subscription_types', $subscription_types);
    $t->assign('multi_types', $multi_types);
    $t->assign('prod_names', $prod_names);

    $t->assign('date_check', $vars['date_check']);
    $t->assign('user_field', $vars['user_field']);
    $t->assign('payment_field', $vars['payment_field']);
    $t->assign('product_name', $vars['product_name']);
    $t->assign('subscr_type', $vars['subscr_type'] ? $vars['subscr_type'] : 'active');
    $t->assign('multi_type', $vars['multi_type'] ? $vars['multi_type'] : 'discard');
    $t->assign('delim', $vars['delim'] ? $vars['delim'] : ';');

    $t->display('admin/export.html');
}

function check_form(){
    global $t, $vars;
    $err = array();
    if (!$vars['user_field'] && !$vars['payment_field'])
        fatal_error("Please select at least one user or payment field", 0);

    return !$err;
}


function print_header_item($f){
        global $vars;
        //we got list, then format it
        $delim = $vars['delim'];
        $quote = $vars['quote'];
        if (strstr($f,$delim) || strstr($f,'"'))
            $f = '"' . str_replace('"', '""', $f) . '"';
        $result = $f . $delim;
        print $result;
}

function print_line($o)
{
        global $vars;
        //we got list, then format it
        $delim = $vars['delim'];
        $quote = $vars['quote'];
        $result = '';
        foreach ($o as $f){
            $f = str_replace("\n", "", $f);
            $f = str_replace("\r", "", $f);
            if (strstr($f,$delim) || strstr($f,'"'))
                $f = '"' . str_replace('"', '""', $f) . '"';
            $result .= $f . $delim;
        }
        $result .= "\n";
        print $result;
}
function get_field_value($v){
    if (is_array($v)){
        foreach ($v as $kk=>$vv)
            if ($vv == "") unset($v[$kk]);
        return join('|', $v);
    } else {
        return $v;
    }
}

function print_rows(&$members)
{
    global $vars;
    global $db;
    $list = array(); //try to free memory
    // now we got list of customers
    // then create array with output records
    settype($vars['user_field'], 'array');
    settype($vars['payment_field'], 'array');
    settype($vars['product_name'], 'array');
    $user_add_fields = array();
    foreach ($vars['user_field'] as $k=>$v)
        if (preg_match('/^data\.(.+)$/', $v, $regs))
            $user_add_fields[] = $regs[1];

    $products = array();
    foreach ($db->get_products_list() as $p)
        $products[$p['product_id']] = $p['title'];
    // print header
    foreach ($vars['user_field'] as $k)
        print_header_item($GLOBALS['user_fields'][$k]);
    $max = 1;
    if ( !in_array($vars['multi_type'], array('discard', 'rows')) ) {
      foreach ($members as $u)
          if (count($u['PAYMENTS'])>$max) $max = count($u['PAYMENTS']);
    }
    for ($i=0;$i<$max;$i++){
        foreach ($vars['payment_field'] as $k)
            print_header_item($GLOBALS['payment_fields'][$k]);
    }
    print "\n";
    //--
    switch ($vars['multi_type'])
    {
        case 'discard':
            foreach ($members as $u)
            {
                $o = array();
                $u['name'] = $u['name_f'] . ' ' . $u['name_l'];

                foreach ($vars['user_field'] as $k) {
                    if (strpos($k, "data.")!==0){
                        $o[] = $u[$k];
                    } else {
                        preg_match('/^data\.(.+)$/', $k, $regs);
                        $k2 = $regs[1];
                        $o[] = get_field_value($u['data'][$k2]);
                    }
                }
//                foreach ($user_add_fields as $k)
//                    $o[] = get_field_value($u['data'][$k]);

                foreach ((array)@$u['PAYMENTS'] as $p)
                {
                    $p['product_name'] = $products[ $p['product_id'] ];
                    foreach ($vars['payment_field'] as $k)
                        $o[] = $p[$k];
                    break;
                }
                print_line($o);
            }
            break;
        case 'cols':
            foreach ($members as $u){
                $o = array();
                $u['name'] = $u['name_f'] . ' ' . $u['name_l'];

                foreach ($vars['user_field'] as $k) {
                    if (strpos($k, "data.")!==0){
                        $o[] = $u[$k];
                    } else {
                        preg_match('/^data\.(.+)$/', $k, $regs);
                        $k2 = $regs[1];
                        $o[] = get_field_value($u['data'][$k2]);
                    }
                }
//                foreach ($user_add_fields as $k)
//                    $o[] = get_field_value($u['data'][$k]);

                foreach ($u['PAYMENTS'] as $p){
                    $p['product_name'] = $products[ $p['product_id'] ];
                    foreach ($vars['payment_field'] as $k)
                        $o[] = $p[$k];
                }
                print_line($o);
            }
            break;
        case 'rows':
            foreach ($members as $u){
                foreach ((array)@$u['PAYMENTS'] as $p){
                    $p['product_name'] = $products[ $p['product_id'] ];
                    $u['name'] = $u['name_f'] . ' ' . $u['name_l'];
                    $o = array();

                foreach ($vars['user_field'] as $k) {
                    if (strpos($k, "data.")!==0){
                        $o[] = $u[$k];
                    } else {
                        preg_match('/^data\.(.+)$/', $k, $regs);
                        $k2 = $regs[1];
                        $o[] = get_field_value($u['data'][$k2]);
                    }
                }
//                foreach ($user_add_fields as $k)
//                    $o[] = get_field_value($u['data'][$k]);

                    foreach ($vars['payment_field'] as $k)
                        $o[] = $p[$k];
                    print_line($o);
                }
            }
            break;
    }
}

function do_export()
{
    global $vars;
    global $db;
    if ($vars['date_check']=="on")
    {
        $dt=get_input_vars();
        $start_date=set_date_from_smarty('range_start',$dt);
        $end_date=set_date_from_smarty('range_end',$dt);
    }
    else
    {
        $start_date='0000-00-00';
        $end_date='2099-12-31';
    }
    $members = array();
    if ($vars['send_file']){
	header('Cache-Control: maxage=3600');
	header('Pragma: public');
        header("Content-type: application/csv");
        $dat = date('Ymd');
        header("Content-Disposition: attachment; filename=amember-$dat.csv");
    } else {
        header("Content-type: text/plain");
    }

    define('REC_LIMIT', 1024);
    if (count($vars['product_name'])>0)
        $prod = join(",", $vars['product_name']);
    else
        $prod='';

    foreach ($vars['subscr_type'] as $subscr_type){
    switch ($subscr_type){
        case 'any' : if ($subscr_type == 'any') $cmpl = 0;
        case 'completed' : if ($subscr_type == 'completed') $cmpl = 1;
        case 'not_completed' : if ($subscr_type == 'not_completed') $cmpl = -1;
            list($count, $sumx) = $db->get_payments_c($start_date, $end_date, $cmpl, 'add', $prod);
            $used_member_id = array();
            for ($i=0;$i<$count;$i+=REC_LIMIT){
              $list1 = $db->get_payments($start_date, $end_date, $cmpl, $i, REC_LIMIT ,'add', $prod);
              foreach ($list1 as $l){
                  if (($vars['multi_type'] == 'discard') &&
                    $used_member_id[ $l['member_id'] ]++ ) continue;
                  if (!isset($members[ $l['member_id'] ]))
                      $members[ $l['member_id'] ] =
                           $db->get_user($l['member_id']);
                  $members[ $l['member_id']]['PAYMENTS'][ $l['payment_id'] ]
                       = $l;
              }
              print_rows($members);
              $members = array();
            }
            break;
        case 'active':
            $yesterday = date('Y-m-d', time() - 3600 * 24);
            $count = $db->users_find_by_date_c($yesterday,  'date_range', $start_date, $end_date, $prod);
            for ($i=0;$i<$count;$i+=REC_LIMIT){
              $members1 = $db->users_find_by_date($yesterday,  'date_range', $i, REC_LIMIT, $start_date, $end_date, $prod);
              $dat = date('Y-m-d');
              foreach ($members1 as $u)
              {
                $members[$u['member_id']] = $u;
                $list = $db->get_user_payments($u['member_id'], 1);
                foreach ($list as $l)
                {
                    $tmadd=explode(' ',$l['tm_added']);
                    if (($l['begin_date'] > $dat) || ($l['expire_date'] < $dat)||
                          ($tmadd[0]<$start_date) || ($tmadd[0]>$end_date))
                        continue;
                    if (count($vars['product_name'])>0 &&
                        !in_array($l['product_id'], $vars['product_name']))
                            continue;
                    $members[$u['member_id'] ]['PAYMENTS'][$l['payment_id']] =
                        $l;
                }
              }
              print_rows($members);
              $members = array();
            }
            break;
        case 'expired':
            $today = date('Y-m-d', time());
            $count = $db->users_find_by_date_c($today,  'expire_date_range', $start_date, $end_date, $prod);
            for ($i=0;$i<$count;$i+=REC_LIMIT){
              $members1 = $db->users_find_by_date($today,  'expire_date_range', $i, REC_LIMIT, $start_date, $end_date, $prod);
              $dat = date('Y-m-d');
              foreach ($members1 as $u)
              {
                $members[$u['member_id']] = $u;
                $list = $db->get_user_payments($u['member_id'], 1);
                foreach ($list as $l)
                {
                    $tmadd=explode(' ',$l['tm_added']);

                    if (($l['begin_date'] > $dat) || ($l['expire_date'] > $dat) || ($tmadd[0]<$start_date) || ($tmadd[0]>$end_date)){
                        continue;
                    }
                    if (count($vars['product_name'])>0 &&
                        !in_array($l['product_id'], $vars['product_name']))
                            continue;
                    $members[$u['member_id'] ]['PAYMENTS'][$l['payment_id']] = $l;
                }
              }
              print_rows($members);
              $members = array();
            }
            break;
        case 'expired_users':
            $count = $db->get_users_list_c("%", 2);
            for ($i=0;$i<$count;$i+=REC_LIMIT){
              $members1 = $db->get_users_list("%", 2, $i, $REC_LIMIT);
              foreach ($members1 as $u)
                $members[$u['member_id']] = $u;
              print_rows($members);
              $members = array();
            }
            break;
        default: fatal_error("Unknown Subscription Type: Please select one", 0);
    }
    if ($subscr_type == 'all') break;
    }
}

//////// main
$vars = get_input_vars();
if ($_POST['export']){
    if (check_form()){
        //do actions
        check_demo();
        do_export();
        admin_log("Export users");
        exit();
    }
}
display_form();

?>
