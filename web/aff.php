<?php                          
/**
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Affiliate management routines
*    FileName $RCSfile$
*    Release: 3.2.4PRO ($Revision: 6678 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/
require('./config.inc.php');
$t = & new_smarty();
$_product_id = array('ONLY_LOGIN');
$affiliates_signup = 1;
require($config['plugins_dir']['protect'] . '/php_include/check.inc.php');
$member_id = $_SESSION['_amember_user']['member_id'];
if ($member_id <= 0) die(_AFF_INTERNAL_ERROR);

$vars = get_input_vars();

function enable_aff($member_id){
    global $db, $config;
    if ($config['aff']['signup_type'] == 2)
        return; // signup disabled in config
    $u = $db->get_user($member_id);
    $u['is_affiliate'] = 1;
    $db->update_user($member_id, $u);
    if($config['aff']['mail_signup_user']) check_aff_signup_email_sent($member_id);
    return true;
}

function display_links($member_id){
    global $config, $t, $db;
    ///
    $links = array();
    foreach ((array)$config['aff']['links'] as $k => $l){
        $l['url'] = aff_make_url($l['url'], 'l'.$k, $member_id);
        $l['code'] = "<a href=\"$l[url]\">$l[title]</a>";
        $links[ ] = $l;
    }
    $t->assign('links', $links);
    ///
    $banners = array();
    foreach ((array)$config['aff']['banners'] as $k=>$l){
        $l['url'] = aff_make_url($l['url'], 'b'.$k, $member_id);
        $alt = htmlspecialchars($l[alt]);
        $wc = ($w=$l['width'])  ? "width=$w" : "";
        $hc = ($h=$l['height']) ? "height=$h" : "";
            
        $l['code'] = "<a href=\"$l[url]\"><img src=\"$l[image_url]\" border=0 alt=\"$alt\" $wc $hc></a>";
        $banners[] = $l;
    }
    $t->assign('banners', $banners);
    ///
    $t->display('aff_links.html');    
}


function get_ym_options(){
    $res = array();
    $y = date('Y');
    $m = date('m');
    for ($i=0;$i<36;$i++){
        $res[ $y."_".$m ] = date('F 01-t, Y', strtotime("{$y}-{$m}-01"));
        $m--;
        if ($m <= 0) { $m = 12; $y--; }
    }
    return $res;
}
function get_default_ym(){
    $y = date('Y');
    $m = date('m');
    $m -= 0;
    if ($m <= 0){
        $y--;
        $m = 12 - $m;
    }
    return "{$y}_{$m}";
}

function display_stats($member_id, $vars){
    global $db, $t;
    $t->assign('year_month_options', get_ym_options());
    $t->assign('default_month', get_default_ym());
    
    if ($vars['year_month'] == '')
        $vars['year_month'] = get_default_ym();

        
    list($y, $m) = split('_', $vars['year_month']);
	$y = sprintf('%04d', $y);
    $m = sprintf('%02d', $m);
    $dat1 = "{$y}-{$m}-01"; 
    $dat2 = date('Y-m-t', strtotime($dat1));
    $dattm1 = date('Ymd000000', strtotime($dat1));
    $dattm2 = date('Ymd235959', strtotime($dat2));
    $totaldays = date('t', strtotime($dat1));        
    
    $days = array();
    $total = array();
    for ($i=1;$i<=$totaldays;$i++)
        $days[$i] = array('dat' => sprintf("$y-$m-%02d", $i));
    // get clicks for the month
    $q = $db->query("SELECT DAYOFMONTH(ac.time), COUNT(log_id), COUNT(DISTINCT(remote_addr))
        FROM {$db->config[prefix]}aff_clicks ac
        WHERE aff_id=$member_id AND ac.time BETWEEN $dattm1 AND $dattm2
        GROUP BY DAYOFMONTH(ac.time)
    ");
    while (list($d, $r, $u) = mysql_fetch_row($q)){
        $days[$d]['raw'] = $r;
        $days[$d]['uniq'] = $u;
    }
    
    // get total clicks for the month
    $q = $db->query("SELECT COUNT(log_id), COUNT(DISTINCT(remote_addr))
        FROM {$db->config[prefix]}aff_clicks ac
        WHERE aff_id=$member_id AND ac.time BETWEEN $dattm1 AND $dattm2
    ");
    while (list($r, $u) = mysql_fetch_row($q)){
        $total['raw'] = $r;
        $total['uniq'] = $u;
    }
    
    // get comissions for the month
    $q = $db->query("SELECT DAYOFMONTH(ac.date), COUNT(commission_id),
        SUM(IF(record_type='debit', amount, 0)), 
        SUM(IF(record_type='credit', amount, 0)),
        SUM(IF(record_type='debit', 1, 0))
        FROM {$db->config[prefix]}aff_commission ac
        WHERE aff_id=$member_id AND ac.date BETWEEN '$dat1' AND '$dat2'
        GROUP BY DAYOFMONTH(ac.date)
    ");
    while (list($d, $cnt, $deb, $cre, $deb_count) = mysql_fetch_row($q)){
        $days[$d]['trans'] = $cnt;
        $days[$d]['debit'] = $deb != 0 ? -$deb." ($deb_count)" : '';
        $days[$d]['credit'] = $cre;
        if ($deb || $cre)
            $days[$d]['total'] = $cre - $deb;
        $total['trans'] += $cnt;
        $total['debit'] += $deb;
        $total['credit'] += $cre;
        $total['total'] += $days[$d]['total'];
    }
    $total['debit'] = $total['debit'] != 0 ? -$total['debit'] : '';
    $t->assign('days', $days);
    $t->assign('total', $total);
    
    /// top 20 referrers
    $q = $db->query("SELECT referrer, COUNT(log_id) as clog_id, COUNT(DISTINCT(remote_addr)) as cremote_addr
        FROM {$db->config[prefix]}aff_clicks ac
        WHERE aff_id=$member_id AND referrer > '' AND ac.time BETWEEN $dattm1 AND $dattm2
        GROUP BY referrer
        ORDER BY clog_id DESC, cremote_addr DESC
        LIMIT 0,20
    ");
    $refs = array();
    while (list($ref, $raw, $uniq) = mysql_fetch_row($q)){
        $refs[] = array(
            'raw'  => $raw,
            'uniq' => $uniq,
            'ref'  => $ref
        );
    }
    $t->assign('refs', $refs);
    $t->display("aff_stats.html");
}

function display_payout_info($member_id, $u, $err=array()){
    global $t, $config, $db;
    $t->assign('u', $u);
    $t->assign('error', $err);
    $t->assign('aff_payout_types', $m=aff_get_payout_methods(1));
    $t->display("aff_payout_info.html");
}

function update_payout_info($member_id, $u){
    global $db;
    foreach ($u as $k => $v)
        $u[$k] = str_replace('"', "'", strip_tags($v));
    $err = array();
    $user = $db->get_user($member_id);
    $user['aff_payout_type'] = $u['aff_payout_type'];
    if ($u['aff_payout_type'] == 'paypal'){
        if ($u['aff_paypal_email'] == ''){
            $err[] = _AFF_ERROR_1;
            return $err;
        }            
        $user['data']['aff_paypal_email'] = $u['aff_paypal_email'];
    } elseif ($u['aff_payout_type'] == 'check'){
        if ($u['aff_check_payable_to'] == ''){
            $err[] = _AFF_ERROR_2;
            return $err;
        }            
        $user['data']['aff_check_payable_to'] = $u['aff_check_payable_to'];
        $user['street'] = $u['street'];
        $user['city'] = $u['city'];
        $user['state'] = $u['state'];
        $user['zip'] = $u['zip'];
        $user['country'] = $u['country'];
    } elseif ($u['aff_payout_type'] == 'stormpay'){
        if ($u['aff_stormpay_email'] == ''){
            $err[] = _AFF_ERROR_3;
            return $err;
        }            
        $user['data']['aff_stormpay_email'] = $u['aff_stormpay_email'];
    } elseif ($u['aff_payout_type'] == 'ikobo'){
        if ($u['aff_ikobo_email'] == ''){
            $err[] = _AFF_ERROR_4;
            return $err;
        }            
        $user['data']['aff_ikobo_email'] = $u['aff_ikobo_email'];
    } elseif ($u['aff_payout_type'] == 'moneybookers'){
        if ($u['aff_moneybookers_email'] == ''){
            $err[] = _AFF_ERROR_5;
            return $err;
        }            
        $user['data']['aff_moneybookers_email'] = $u['aff_moneybookers_email'];
    } elseif ($u['aff_payout_type'] == 'egold'){
        if ($u['aff_egold_id'] == ''){
            $err[] = _AFF_ERROR_6;
            return $err;
        }            
        $user['data']['aff_egold_id'] = $u['aff_egold_id'];
    } elseif ($u['aff_payout_type'] == 'safepay'){
        if ($u['aff_safepay_email'] == ''){
            $err[] = _AFF_ERROR_9;
            return $err;
        }            
        $user['data']['aff_safepay_email'] = $u['aff_safepay_email'];

    } elseif ($u['aff_payout_type'] == ''){
        //        
    } else {
        $err[] = _AFF_ERROR_7;
        return; // unknown payout type
    }
    $db->update_user($member_id, $user);
    return $err;
}

switch ($vars['action']){
    case 'enable_aff':
        enable_aff($member_id); 
        display_links($member_id);
        break;
    case 'links':        
        display_links($member_id);
        break;        
    case 'stats':
        display_stats($member_id, $vars);
        break;        
    case 'payout_info':
        if ($vars['save'] && !$vars['type_change']){
            $u = $vars;
            $u['data'] = $vars;
            $err = update_payout_info($member_id, $vars);
            if (!$err) {
                $u = $db->get_user($member_id);
            }                
        } else
            $u = $db->get_user($member_id);
        if ($vars['type_change'])
            $u['aff_payout_type'] = $vars['aff_payout_type'];        
        display_payout_info($member_id, $u, $err);
        break;        
    default: fatal_error(sprintf(_AFF_ERROR_8,$vars[action]), 0); 
}
?>
