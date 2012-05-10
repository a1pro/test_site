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
* Release: 3.2.3PRO ($Revision: 5079 $)*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/


require('./config.inc.php');
if (!$config['use_affiliates'])
    fatal_error(_AFF_DISABLED_MESSAGE);


$t = & new_smarty();
$_product_id = array('ONLY_LOGIN');
$affiliates_signup = 1;
require($config['plugins_dir']['protect'] . '/php_include/check.inc.php');
$member_id = $_SESSION['_amember_user']['member_id'];

$u = $db->get_user($member_id);
if ($u['is_affiliate'] == '0') {
    //fatal_error ('Error. Affiliate members area only.');
    $url = $config['root_url'] . "/member.php";
    html_redirect($url, 0, _MEMBER_PLEASE_WAIT, _MEMBER_REDIRECTING);
   exit();
}

if ($member_id <= 0) die(_AFF_INTERNAL_ERROR);

$vars = get_input_vars();
    
if (($_SESSION['_amember_user']['email_verified'] < 0) && ($config['verify_email'])){
    $u = $_SESSION['_amember_user'];
    $v = md5($u['member_id'].$u['login'].$u['email']);
    fatal_error(sprintf(_MEMBER_ERROR_1,"<br />","<br />","<br />","<a href='resend.php?member_id={$_SESSION[_amember_id]}&v=$v'>",
    "</a>"), 1,1);
}


/// redirect to make F5 key (Refresh) working
if (strlen($_POST['amember_pass']) && ($_SERVER["REQUEST_METHOD"] == 'POST')){
    $url = $PHP_SELF;
    srand(time());
    if (!preg_match('/\?/', $url))
        $url .= "?r=". rand(10000,99999);
    html_redirect($url, 0, _MEMBER_PLEASE_WAIT, _MEMBER_REDIRECTING);
    exit();
}

////////////////////////////////////////////////////////////////////////

function display_stats($member_id, $vars){
    global $db, $t,$config;
    
    $y = date('Y');
    $m = date('n');

    $months = array();
    $total = array();
    for ($i=0;$i<12;$i++) {
        //$months[$y."-".$m] = array( 'dat' => date( 'F 0-t, Y', strtotime("{$y}-{$m}-01") ) );
        $months[$y."_".$m] = array( 'dat' => date( 'F, Y', strtotime("{$y}-{$m}-01") ) );
        $m--;
        if ($m <= 0) { $m = 12; $y--; }
    }
    
        
    $m = sprintf('%02d', $m);
    
    $dat1 = "{$y}-{$m}-01"; 
    $dat2 = date('Y-m-t');
    $dattm1 = date('Ym01000000', strtotime($dat1));
    $dattm2 = date('Ymt235959', strtotime($dat2));
    
    // get clicks for the month
    $q = $db->query("SELECT CONCAT(YEAR(ac.time),'_',MONTH(ac.time)) AS point, COUNT(log_id), COUNT(DISTINCT(remote_addr))
        FROM {$db->config[prefix]}aff_clicks ac
        WHERE aff_id=$member_id AND ac.time BETWEEN $dattm1 AND $dattm2
        GROUP BY point
    ");
    while (list($d, $r, $u) = mysql_fetch_row($q)){
        $months[$d]['raw'] = $r;
        $months[$d]['uniq'] = $u;
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
    $q = $db->query("SELECT CONCAT(YEAR(ac.date),'_',MONTH(ac.date)) AS point, COUNT(commission_id),
        SUM(IF(record_type='debit', amount, 0)), 
        SUM(IF(record_type='credit', amount, 0))
        FROM {$db->config[prefix]}aff_commission ac
        WHERE aff_id=$member_id AND ac.date BETWEEN '$dat1' AND '$dat2'
        GROUP BY point
    ");
    while (list($d, $cnt, $deb, $cre) = mysql_fetch_row($q)){
        $months[$d]['trans'] = $cnt;
        $months[$d]['debit'] = $deb != 0 ? -$deb : '';
        $months[$d]['credit'] = $cre;
        if ($deb || $cre)
            //$months[$d]['total'] = $cre + $deb;
            $months[$d]['total'] = $cre - $deb;
        $total['trans'] += $cnt;
        $total['debit'] += $deb;
        $total['credit'] += $cre;
        $total['total'] += $months[$d]['total'];
    }
    $total['debit'] = $total['debit'] != 0 ? -$total['debit'] : '';
    $t->assign('months', $months);
    $t->assign('total', $total);
    
    $q = $db->query("select payout_id, sum(if(record_type='credit', amount, -amount)) as pamount from {$db->config[prefix]}aff_commission  where aff_id=$member_id and payout_id != '' and payout_id is not null group by payout_id order by payout_id desc");
    while($r = mysql_fetch_assoc($q)){
	$d = strtotime($r[payout_id]);
	$r[payout_id] = strftime($config[date_format], $d);
	$payouts[] = $r;
    }
    $t->assign("payouts", $payouts);
    return $t->fetch("aff_month_stats.html");
}


///////////////////////// MAIN /////////////////////////////////////////
$_amember_id = $_SESSION['_amember_id'];
$vars = get_input_vars();

$aff_stats = display_stats($member_id, $vars);

//$member_links = plugin_get_member_links($_SESSION['_amember_user']);
$member_links = array($config['root_url']."/aff.php?action=links" 
            => _AFF_GET_BANS_LINKS,
            $config['root_url']."/aff.php?action=stats" 
            => _AFF_REVIEW_STAT,
            $config['root_url']."/aff.php?action=payout_info" 
            => _AFF_UPDATE_PAYOUT
            );

$t->assign('member_links', $member_links);
$t->assign('aff_stats', $aff_stats);

$t->display('aff_member.html');

?>
