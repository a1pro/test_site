<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");
/**
* MySQL database plugin
* implements class db
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4912 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

/**
 * @return DbSimple_MySQL
 */
function & amDb(){
    static $v;
    if (!$v) {
        $am_db = & connectMysql($GLOBALS['config']['db']['mysql']);
        $v = array(0 => & $am_db);
    }
    return $v[0];
}

function & connectMysql($conf) {
    global $config;
    require_once $config['root_dir'].'/includes/dbsimple/Generic.php';
    require_once $config['root_dir'].'/includes/dbsimple/Mysql.php';
    extract($conf);
    $database = & DbSimple_Generic::connect(
		array('scheme'=>'mysql',
			'user'=>$user,
			'pass'=>$pass,
			'host'=>$host,
			'path'=>$db));
    $database->setErrorHandler('defaultDatabaseErrorHandler');
    $database->setIdentPrefix($prefix);
    return $database;
}
function defaultDatabaseErrorHandler($message, $info){
    if (!error_reporting()) return;
    if (defined('AM_DEBUG'))
        print_rr($info, 'MySQL Error Details');
    $GLOBALS['db']->log_error("MYSQL ERROR" . "<br />\n".
        nl2br(print_r($info, true)));
    fatal_error($s = "MYSQL Error happened, script stopped. Website admin
        can find more details about the problem at CP -> Error Log".$s, false);
    exit();
}

#register_shutdown_function('mysql_disconnect');

class db_mysql extends amember_db {
    function db_mysql(& $config){
        $this->db( $config);
        if ($GLOBALS['config']['use_mysql_connect']){
            if (!$this->conn = @mysql_connect(
                $this->config['host'],
                $this->config['user'],
                $this->config['pass'],
                $new_link = 1
            )) die("Cannot connect to MySQL: " . mysql_error());
        } else {
            if (!$this->conn = @mysql_pconnect(
                $this->config['host'],
                $this->config['user'],
                $this->config['pass'],
                $new_link = 1
            )) die("Cannot connect to MySQL: " . mysql_error());
        };
        if (!@mysql_select_db($this->config['db'], $this->conn))
           die("Cannot select MySQL db: " . mysql_error());

        if ($this->config['charset'])
            mysql_set_charset($this->config['charset'], $this->conn);

        // set not strict @@sql_mode on MySQL5
        $this->mysql_version = $this->query_one("SELECT VERSION()");
        if ($this->mysql_version[0] > '4'){
            mysql_query("SET @@session.sql_mode='MYSQL40'", $this->conn);
        }

    }

    function get_limit_exp($start, $limit){
        settype($start, 'integer');
        settype($limit, 'integer');
        if ($limit > 0) {
            return " LIMIT $start, $limit ";
        }
    }

    //////////////////// USERS FUNCTIONS /////////////////////////////////

    function add_pending_user($vars){
        _amember_get_iconf_d();
        $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
        global $member_additional_fields;
        $data = array();

        if (!strlen($vars['pass']))
            $vars['pass'] = $vars['pass0'];

        $fields_to_update = array(
            'login',  'pass',   'email',
            'name_f',  'name_l',
            'street', 'city', 'state',
            'zip', 'country', 'is_male', 'is_affiliate',
            'aff_payout_type', 'unsubscribed',
            'email_verified',
        );


        foreach ($member_additional_fields as $field){
            $k = $field['name'];
            $default = ((!$field['display_signup']) && ($field['default'] != '') && !isset($vars[$k])) ?
                $field['default'] : null;
            if (!is_null($default)) $vars[$k] = $default;
            if ($field['sql']) {
                $fields_to_update[] = $k;
            } else {
                if (isset($vars[$k])){
                    $data[$k] = $vars[$k];
                }
            }
        }

        $data = & $this->encode_data($data);
        $data = $this->escape($data);

        $v = & $this->escape_array($vars);
        $insert_statement1 = $insert_statement2 = "";
        foreach ($fields_to_update as $k){
            $insert_statement1 .= "$k,";
            $insert_statement2 .= "'" . $v[$k] . "', ";
        }
        $this->query($s="INSERT INTO {$this->config['prefix']}members
        ($insert_statement1 added, remote_addr, data, aff_id
        )
        VALUES
        ($insert_statement2 NOW(), '$REMOTE_ADDR', '$data', '$v[aff_id]'
        )
        ");
        $member_id = mysql_insert_id($this->conn);
        plugin_update_users($member_id);

        return $member_id;
    }

    function check_uniq_login($login, $email='', $pass='', $check_type=0){
        // check_type can be:
        // 0 - usual check - report any existing members
        // 1 - easy check
        // 2 - uniq user - return member_id if user exists, -1 if no and 0 if email/password failed
        global $config;
        $login = $this->escape($login);
        $email = $this->escape($email);
        $pass  = $this->escape($pass);
        $q = $this->query($s = "SELECT login,
            (email='$email'), member_id, pass='$pass'
            FROM {$this->config['prefix']}members
            WHERE login='$login'
        ");
        list($login_x, $same_email,$member_id, $same_pass) = mysql_fetch_row($q);
        switch ($check_type){
            case 0:
                if (!$member_id &&
                    plugin_subscription_check_uniq_login($login, $email, $pass)){
                    return -1;
                } else {
                    return 0;
                }
            break;
            case 1:
                if ( $member_id ) {
                    if ($config['generate_pass']) {
                        if (!$same_email) return 0; //check email
                    } else {
                        if (!$same_pass || !$same_email) return 0; //check email&pass
                    }
                    if ($config['allow_second_signup'] && $this->get_user_payments($member_id, 1)) return $member_id;
                } else $member_id = -1;
                if (plugin_subscription_check_uniq_login($login, $email, $pass)){
                    return $member_id;
                } else {
                    return 0;
                }
            break;
            case 2:
                if ( $member_id ) {
                    if ($config['generate_pass']) {
                        if (!$same_email) return 0; //check email
                    } else {
                        if (!$same_pass || !$same_email) return 0; //check email&pass
                    }
                } else $member_id = -1;
                return $member_id;
            break;
            default:
                fatal_error("Unknown check_type in check_uniq_login()");
        }
        return false; ///dummy
    }

    function check_remote_access($login, $password, $product_ids, $ip, $url, $referer){
        global $config;
        if (!is_array($product_ids)) $product_ids = (array)$product_ids;

        $b = & new BruteforceProtector(BRUTEFORCE_PROTECT_USER, $this, $config['bruteforce_count'], $config['bruteforce_delay']);
        $b->setIP($ip);
        $left = null; // how long secs to wait if login is not allowed
        if (!$b->loginAllowed($left)){
            $min = ceil($left / 60);
            return 5; //sprintf(_LOGIN_WAIT_BEFORE_NEXT_ATTEMPT, $min);
        }

        $amember_id = '';
        if (!$this->check_login($login, $password, $amember_id, $accept_md5=1)){
            $b->reportFailedLogin();
            return 2; //_LOGIN_INCORRECT;
        }
        if (!$this->check_access($login, $product_ids)){
            return 4; //sprintf(_LOGIN_ACCESS_NOT_ALLOWED, "<a href=\"{$config['root_url']}/member.php\">", "</a>");
        }

        $u  = $this->get_user($amember_id);
        if ($u['data']['is_locked']>0)
            return 3; //_LOGIN_ACCOUNT_DISABLED;

        $this->log_remote_access($amember_id, $ip, $url, $referer);
        return 1; //OK Access Allowed
    }

    function clear_incomplete_users($date){
        $date = $this->escape($date);
        $q = $this->query($s = "SELECT
                m.member_id,
                COUNT(p.payment_id) AS pc
            FROM {$this->config['prefix']}members m LEFT JOIN {$this->config['prefix']}payments p ON
                (p.member_id = m.member_id AND p.completed > 0)
            WHERE added < '$date' AND (m.is_affiliate IS NULL OR m.is_affiliate = 0)
            GROUP BY m.member_id
            HAVING pc = 0
        ");
        $list = array(0);
        while (list($member_id, $c) = mysql_fetch_row($q)) {
            $list[] = $member_id;
        }
        $list = join(',', $list);
        $this->query("DELETE FROM {$this->config['prefix']}payments WHERE member_id IN ($list)");
        $this->query("DELETE FROM {$this->config['prefix']}members WHERE member_id IN ($list)");
        plugin_update_users();
    }

    function get_users_list($pattern='%', $status=-1, $start=0, $limit=-1){
        $where = $having = "";
        if ($pattern)
            $where = " AND u.login LIKE '" . $this->escape($pattern) . "' ";
        if (strlen($status)){
            $status = intval($status);
            if ($status >= 0)
                $where .= " AND status = $status ";
        }
        $limit_exp = $this->get_limit_exp($start, $limit);
        $q = $this->query($s = "SELECT u.*,
            SUM(if(p.completed, 1, 0)) AS count_of_completed,
            SUM(if(p.completed, p.amount,0)) as summa_of_completed
            FROM {$this->config['prefix']}members u LEFT JOIN {$this->config['prefix']}payments p ON (p.member_id=u.member_id)
            WHERE 1 $where
            GROUP BY u.member_id
            HAVING 1 $having
            ORDER BY u.login
            $limit_exp
        ");
        $rows = array();
        while ($r = mysql_fetch_assoc($q)){
            if ($r['data'])
                $r['data'] = $this->decode_data($r['data']);
            $rows[] = $r;
        }
        return $rows;
    }

    function get_users_list_c($pattern='%', $status=-1){
        $where = $having = "";
        if ($pattern)
            $where = " AND u.login LIKE '" . $this->escape($pattern) . "' ";
        if (strlen($status)){
            $status = intval($status);
            if ($status >= 0)
                $where .= " AND status = $status ";
        }
        $q = $this->query($s = "SELECT COUNT(DISTINCT u.member_id)
            FROM {$this->config['prefix']}members u
            LEFT JOIN {$this->config['prefix']}payments p ON (p.member_id=u.member_id)
            WHERE 1 $where
            HAVING 1 $having
        ");
        $c = mysql_fetch_row($q);
        return $c[0];
    }

    function get_user($member_id){
        settype($member_id, 'integer');
        $q = $this->query($s = "SELECT u.*
            FROM {$this->config['prefix']}members u
            WHERE u.member_id = $member_id
        ");
        if (! $r = mysql_fetch_assoc($q)){
            $this->log_error("User not found: #$member_id");
            $r = array();
        }
        if ($r['data'])
            $r['data'] = $this->decode_data($r['data']);
        return $r;
    }

    function update_user($member_id, &$v){
        settype($member_id, 'integer');
        if (!$member_id) return "member_id empty or 0";

        global $member_additional_fields;
        $fields_to_update = array(
            'login',  'pass',   'email',
            'name_f',  'name_l',
            'street', 'city', 'state',
            'zip', 'country', 'is_male', 'is_affiliate',
            'aff_payout_type', 'unsubscribed',
            'email_verified',
            'security_code', 'securitycode_expire',
        );

        $oldmember = $this->get_user($member_id);
        $data = $oldmember['data'];
        foreach ($member_additional_fields as $field){
            if ($field['sql']) {
                $fields_to_update[] = $field['name'];
            } else {
                $k = $field['name'];
                if (isset($v['data'][$k]))
                    $data[$k] = $v['data'][$k];
            }
        }
        $data = & $this->encode_data($data);
        $data = $this->escape($data);

        $vals = $this->escape_array($v);
        if (isset($v['aff_id'])) $fields_to_update[] = 'aff_id';
        foreach ($fields_to_update as $k){
            $update_statement .= "$k = '" . $vals[$k] . "', ";
        }
        $update_statement .= " data='$data', member_id=member_id";
        $q = $this->query($s = "UPDATE
        {$this->config['prefix']}members
        SET  $update_statement
        WHERE member_id=$member_id
        ");
        if (array_sum((array)$oldmember['data']['status']) > 0) // only if has subscriptions
            plugin_subscription_updated($member_id, $oldmember, $v);
        plugin_update_users($member_id);

        return '';
    }

    function update_user_status($member_id, $status){
        settype($member_id, 'integer');
        if (!$member_id) return "member_id empty or 0";

        $q = $this->query("SELECT data
        FROM {$this->config['prefix']}members
        WHERE member_id=$member_id");
        list($data) = mysql_fetch_row($q);
        if ($data)
            $data = $this->decode_data($data);
        settype($data, 'array');
        $data['status'] = (array)$status;
        $data['is_active'] = array_sum($data['status']) ? 1 : 0;
        $data = $this->escape($this->encode_data($data));
	
	$dat = date('Y-m-d');

        $user_status = intval($this->query_one("SELECT
        	CASE WHEN SUM(IF(expire_date>='$dat', 1, 0)) THEN 1
        		 WHEN SUM(IF(expire_date< '$dat', 1, 0)) THEN 2
				 ELSE 0
			END
        	FROM {$this->config[prefix]}payments
        	WHERE member_id=$member_id AND completed>0
        "));
        $q = $this->query($s = "UPDATE
        {$this->config['prefix']}members
        SET  data='$data', status=$user_status
        WHERE member_id=$member_id
        ");

        $this->update_member_threads_access($member_id, $user_status);

        return $status;
    }

    function delete_user($member_id){
        settype($member_id, 'integer');
        if (!$member_id) fatal_error("member_id empty or 0");
        $member = $this->get_user($member_id);
        $this->query("DELETE FROM {$this->config['prefix']}payments WHERE member_id=$member_id");
        $this->check_subscriptions($member_id);
        plugin_subscription_removed($member_id, $member);
        $this->query("DELETE FROM {$this->config['prefix']}access_log WHERE member_id=$member_id");
        $this->query("DELETE FROM {$this->config['prefix']}members WHERE member_id=$member_id");
        plugin_update_users($member_id);
        return '';
    }

    function users_find_by_string($q, $q_where, $exact=0){
        global $member_additional_fields;

        $sql_fields = array();
        foreach ($member_additional_fields as $f)
            if ($f['sql']) $sql_fields[$f['name']]++;

        $q = $this->escape($q);
        if (preg_match('/^additional:(.+)$/', $q_where, $regs)){
            $fname = $regs[1];
            if ($sql_fields[$fname]){
                $q_where = "field";
            } else {
                $q_where = 'skip';
                $search = $q;
                $additional_field = $fname;
            }
        }
        if (in_array($q_where, array('city','state','country','zip','street'))) {
            $fname = $q_where;
            $q_where = 'field';

        };
        switch ($q_where){
            case 'field':
                if ($exact)
                    $where_exp = " AND u.$fname LIKE '$q'";
                else
                    $where_exp = " AND u.$fname LIKE '%$q%'";
                break;
            case 'skip':
                $where_exp = "";
                break;
            case 'login':
                if ($exact)
                    $where_exp = " AND u.login LIKE '$q'";
                else
                    $where_exp = " AND u.login LIKE '%$q%'";
                break;
            case 'name':
                if ($exact)
                    $where_exp =
                    " AND (u.name_f LIKE '$q' OR name_l LIKE '$q')";
                else
                    $where_exp =
                    " AND (u.name_f LIKE '%$q%' OR name_l LIKE '%$q%')";
                break;
            case 'email':
                if ($exact)
                    $where_exp = " AND (u.email LIKE '$q')";
                else
                    $where_exp = " AND (u.email LIKE '%$q%')";
                break;
            default: $where_exp = " AND
                (u.login LIKE '%$q%') OR
                (u.name_f LIKE '%$q%') OR
                (u.name_l LIKE '%$q%') OR
                (u.email LIKE '%$q%') OR
                (u.street LIKE '%$q%') OR
                (u.city LIKE '%$q%') OR
                (u.state LIKE '%$q%') OR
                (u.zip LIKE '%$q%') OR
                (u.country LIKE '%$q%') OR
                (u.remote_addr LIKE '%$q%')
                 ";
        }
        $q = $this->query($s = "SELECT u.*,
            SUM(if(p.completed, 1, 0)) AS count_of_completed,
            SUM(if(p.completed, p.amount,0)) as summa_of_completed
            FROM {$this->config['prefix']}members u
                LEFT JOIN {$this->config['prefix']}payments p
                    ON (p.member_id=u.member_id)
            WHERE 1 $where_exp
            GROUP BY u.member_id
            ORDER BY u.login
        ");
        $rows = array();
        while ($r = mysql_fetch_assoc($q)){
            if ($r['data'])
                $r['data'] = $this->decode_data($r['data']);
            if (($q_where == 'skip') &&
                !(!strlen($search) && !strlen($r['data'][$additional_field]))
               ){
		if ( ($exact == 0) && (@strpos($r['data'][$additional_field], $search)===FALSE) && (!in_array($search, (array)$r['data'][$additional_field])))
                	continue;
		if ( ($exact == 1) && ($r['data'][$additional_field] != $search) )
                	continue;
	    }
            $rows[] = $r;
        }
        return $rows;
    }

    function users_find_by_product($product_id, $include_expired, $start=0, $count=-1){
//        settype($product_id, 'integer');
        settype($include_expired, 'integer');
        $limit_exp = $this->get_limit_exp($start, $count);
        $where_exp = ($include_expired) ? '' : ' AND p.expire_date >= NOW() ';
        if (is_array($product_id)) $product_id = join(',', $product_id);
        $q = $this->query($s = "SELECT DISTINCT u.*,
            SUM(if(p.completed, 1, 0)) AS count_of_completed,
            SUM(if(p.completed, p.amount,0)) as summa_of_completed
            FROM {$this->config['prefix']}members u
                LEFT JOIN {$this->config['prefix']}payments p
                ON (p.member_id=u.member_id)
            WHERE p.completed > 0 AND p.product_id IN (-111,$product_id) $where_exp
            GROUP BY u.member_id
            ORDER BY u.login
            $limit_exp
        ");
        $rows = array();
        while ($r = mysql_fetch_assoc($q)){
            if ($r['data'])
                $r['data'] = $this->decode_data($r['data']);
            $rows[] = $r;
        }
        return $rows;
    }
    function users_find_by_product_c($product_id, $include_expired){
        settype($include_expired, 'integer');

        $where_exp = ($include_expired) ? '' : ' AND p.expire_date >= NOW() ';
        if (is_array($product_id)) $product_id = join(',', $product_id);
        $q = $this->query($s = "SELECT COUNT(DISTINCT u.member_id)
            FROM {$this->config['prefix']}members u
                LEFT JOIN {$this->config['prefix']}payments p
                ON (p.member_id=u.member_id)
            WHERE p.completed > 0 AND p.product_id IN (-111,$product_id) $where_exp
        ");
        $c = mysql_fetch_row($q);
        return $c[0];
    }

    function users_find_by_date_c($date, $search_type, $range_start='0000-00-00', $range_end='2013-12-31',
                                    $prod_search='')
    {
        $range_start=$this->escape($range_start);
        $range_end=$this->escape($range_end);
        $date        = $this->escape($date);
        $search_type = $this->escape($search_type);

        switch ($search_type){
            case 'begin_date_before':
                $where_exp = " p.begin_date < '$date'";
                break;
            case 'begin_date':
                $where_exp = " p.begin_date = '$date'";
                break;
            case 'begin_date_after':
                $where_exp = " p.begin_date > '$date'";
                break;
            case 'expire_date_before':
                $where_exp = " p.expire_date < '$date'";
                break;
            case 'expire_date':
                $where_exp = " p.expire_date = '$date'";
                break;
            case 'expire_date_after':
                $where_exp = " p.expire_date > '$date'";
                break;
            case 'date_range':
                $where_exp = " p.expire_date >= '$date' AND DATE_FORMAT(p.tm_added,'%Y-%m-%d') >= '$range_start' AND DATE_FORMAT(p.tm_added,'%Y-%m-%d') <= '$range_end'";
                break;
            case 'expire_date_range':
                $where_exp = " p.expire_date < '$date' AND DATE_FORMAT(p.tm_added,'%Y-%m-%d') >= '$range_start' AND DATE_FORMAT(p.tm_added,'%Y-%m-%d') <= '$range_end'";
                break;
            default: fatal_error("Unknown search type");
        }
        if ($prod_search)
        {
            $pr=explode(',',$prod_search);
            if (sizeof($pr)==1)
                $src_pr=" AND p.product_id = $prod_search";
            else
            {
                $src_pr=" AND p.product_id in (";
                for ($i=0; $i<sizeof($pr); $i++)
                    if ($i+1<sizeof($pr))
                        $src_pr.=$pr[$i].',';
                    else
                        $src_pr.=$pr[$i];
                $src_pr.=')';
            }
        }
        $q = $this->query($s = "SELECT COUNT(DISTINCT u.member_id)
            FROM {$this->config['prefix']}members u
                LEFT JOIN {$this->config['prefix']}payments p
                ON (p.member_id=u.member_id)
            WHERE p.completed > 0 AND $where_exp $src_pr
        ");
        list($rows) = mysql_fetch_row($q);
        return $rows;
    }

    function users_find_by_date($date, $search_type, $start=0, $count=-1, $range_start='0000-00-00', $range_end='2013-12-31',
                                $prod_search='')
    {
        $range_start=$this->escape($range_start);
        $range_end=$this->escape($range_end);
        $limit_exp = $this->get_limit_exp($start, $count);
        $date        = $this->escape($date);
        $search_type = $this->escape($search_type);

        switch ($search_type){
            case 'begin_date_before':
                $where_exp = " p.begin_date < '$date'";
                break;
            case 'begin_date':
                $where_exp = " p.begin_date = '$date'";
                break;
            case 'begin_date_after':
                $where_exp = " p.begin_date > '$date'";
                break;
            case 'expire_date_before':
                $where_exp = " p.expire_date < '$date'";
                break;
            case 'expire_date':
                $where_exp = " p.expire_date = '$date'";
                break;
            case 'expire_date_after':
                $where_exp = " p.expire_date > '$date'";
                break;
            case 'date_range':
                $where_exp = " p.expire_date >= '$date' AND DATE_FORMAT(p.tm_added,'%Y-%m-%d') >= '$range_start' AND DATE_FORMAT(p.tm_added,'%Y-%m-%d') <= '$range_end'";
                break;
            case 'expire_date_range':
                $where_exp = " p.expire_date < '$date' AND DATE_FORMAT(p.tm_added,'%Y-%m-%d') >= '$range_start' AND DATE_FORMAT(p.tm_added,'%Y-%m-%d') <= '$range_end'";
                break;
            default: fatal_error("Unknown search type");
        }
        if ($prod_search)
        {
            $pr=explode(',',$prod_search);
            if (sizeof($pr)==1)
                $src_pr=" AND p.product_id = $prod_search";
            else
            {
                $src_pr=" AND p.product_id in (";
                for ($i=0; $i<sizeof($pr); $i++)
                    if ($i+1<sizeof($pr))
                        $src_pr.=$pr[$i].',';
                    else
                        $src_pr.=$pr[$i];
                $src_pr.=')';
            }
        }
        $q = $this->query($s = "SELECT u.*,
            SUM(if(p.completed, 1, 0)) AS count_of_completed,
            SUM(if(p.completed, p.amount,0)) as summa_of_completed
            FROM {$this->config['prefix']}members u
                LEFT JOIN {$this->config['prefix']}payments p
                ON (p.member_id=u.member_id)
            WHERE p.completed > 0 AND $where_exp $src_pr
            GROUP BY u.member_id
            ORDER BY u.login
            $limit_exp
        ");

        $rows = array();
        while ($r = mysql_fetch_assoc($q)){
            if ($r['data'])
                $r['data'] = $this->decode_data($r['data']);
            $rows[] = $r;
        }
        return $rows;
    }

    function get_allowed_users(){
        global $config;
        $q = $this->query("SELECT
            p.product_id, m.login, m.pass, m.data
            FROM {$this->config['prefix']}payments p
                LEFT JOIN {$this->config['prefix']}members m USING (member_id)
            WHERE p.begin_date <= now() AND concat(p.expire_date, ' 23:59:59') >= now()
                AND p.completed > 0
        ");
        $res = array();
        while (list($product_id, $l, $p, $data) = mysql_fetch_row($q)) {
            if ($data)
                $data = $this->decode_data($data);
            if ($data['is_locked'] > 0) continue; //auto-locking
            if ($config['manually_approve'] && !$data['is_approved']) continue;
            $res[$product_id][$l] = $p;
        }
        return $res;
    }

    function check_login($login, $pass_o, &$member_id, $accept_md5=0){
        global $config;
        $login = $this->escape($login);
        $pass  = $this->escape($pass_o);
        $q = $this->query("SELECT pass,member_id
        FROM {$this->config['prefix']}members WHERE login='$login'");
        $member_id = 0;
        list($p, $member_id) = mysql_fetch_row($q);
        if (!strcmp($p, $pass_o)){
            return 1;
        } elseif ($config['accept_md5'] && ((md5($pass_o)==$p))){
            $oldmember = $this->get_user($member_id);
            $this->query("UPDATE
            {$this->config['prefix']}members
            SET pass='$pass'
            WHERE login='$login'
            ");
            $newmember = $this->get_user($member_id);
            plugin_subscription_updated($member_id, $oldmember, $newmember);
            plugin_update_users($member_id);
            return 1;
        } elseif ($config['accept_md5_plus_username'] && ((md5($pass_o.$login)==$p))){
            $oldmember = $this->get_user($member_id);
            $this->query("UPDATE
            {$this->config['prefix']}members
            SET pass='$pass'
            WHERE login='$login'
            ");
            $newmember = $this->get_user($member_id);
            plugin_subscription_updated($member_id, $oldmember, $newmember);
            plugin_update_users($member_id);
            return 1;
        } elseif ($config['accept_crypt'] && (crypt($pass_o, $p)==$p)){
            $oldmember = $this->get_user($member_id);
            $this->query("UPDATE
            {$this->config['prefix']}members
            SET pass='$pass'
            WHERE login='$login'
            ");
            $newmember = $this->get_user($member_id);
            plugin_subscription_updated($member_id, $oldmember, $newmember);
            plugin_update_users($member_id);
            return 1;
        } elseif ($accept_md5 && (md5($p)==$pass_o)){
            return 1;
        }
        return 0;
    }

    function check_access($login, $product_ids){
        if (!is_array($product_ids)) $product_ids = array($product_ids);
        $product_ids[] = -999999; // to avoid sql error with empty list
        $login = $this->escape($login);

        $product_ids = array_map('intval', $product_ids);
        $product_ids  = join(',', $this->escape_array($product_ids));
	$now = date('Y-m-d');
        $q = $this->query($s = "SELECT COUNT(*)
            FROM {$this->config['prefix']}payments p
                LEFT JOIN {$this->config['prefix']}members m USING (member_id)
            WHERE m.login='$login'
                AND p.begin_date <= '$now'
                AND p.expire_date >= '$now'
                AND p.completed > 0
                AND p.product_id IN ($product_ids)
        ");
        list($r) = mysql_fetch_row($q);
        return ($r > 0);
    }

    function clear_expired_users($date){
        $date = $this->escape($date);
        $q = $this->query($s = "SELECT m.member_id FROM
        {$this->config['prefix']}members m LEFT JOIN
        {$this->config['prefix']}payments p USING (member_id)
        WHERE (m.is_affiliate IS NULL OR m.is_affiliate = 0) and m.status=2
        GROUP BY m.member_id
        HAVING SUM(p.expire_date > '$date' AND p.completed > 0) = 0
        ");
        while (list($member_id) = mysql_fetch_row($q)){
            $this->delete_user($member_id);
        }
    }

    //////////////////// PAYMENTS FUNCTIONS ////////////////////////////

    function add_waiting_payment($member_id, $product_id, $paysys_id,
            $price, $begin_date, $expire_date, $vars, $additional_values=false){

        global $config;
        if (isset($vars['pass'])) $vars['pass'] = '******';
        if (isset($vars['pass0'])) $vars['pass0'] = '******';
        if (isset($vars['pass1'])) $vars['pass1'] = '******';

        _amember_get_iconf_d();
        $member_id  = intval($member_id);
        if (!$member_id)
            fatal_error('member_id is null in add_waiting_payment');

        $paysys_id     = $this->escape($paysys_id);
        if (!$paysys_id)
            fatal_error('paysys is null in add_waiting_payment');

        $tax_amount = 0;
        if ($config['use_tax'] && is_array($additional_values) && isset($additional_values['TAX_AMOUNT'])){
            $tax_amount = $additional_values['TAX_AMOUNT'];
        }
        $vars1    = is_array($additional_values) ? $additional_values : array();
        $vars1[0] = $vars;
        $data = $this->escape($this->encode_data($vars1));

        $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
        $this->query($s = "INSERT INTO {$this->config['prefix']}payments
        (member_id, product_id, begin_date, expire_date,
            paysys_id, amount,
            completed, remote_addr, data, time, tm_added, tax_amount)
        VALUES
        ('$member_id', '$product_id', '$begin_date', '$expire_date',
         '$paysys_id', '$price',
          0, '$REMOTE_ADDR', '$data', NOW(), NOW(), '$tax_amount')
        ");
        $payment_id = mysql_insert_id($this->conn);

        /// check for send pending email
        global $config;
        if (@in_array($paysys_id, (array)$config['send_pending_email']) ||
            @in_array('ALL', (array)$config['send_pending_email'])){
            mail_pending_user($member_id, $payment_id);
        }
        if (@in_array($paysys_id, (array)$config['send_pending_admin']) ||
            @in_array('ALL', (array)$config['send_pending_admin'])){
            mail_pending_admin($member_id, $payment_id);
        }
        plugin_update_payments($payment_id, $member_id);
        return $payment_id;
    }

    function add_waiting_payments($member_id, $product_id, $paysys_id,
            $price, $prices, $begin_date, $expire_date, $vars,
            $additional_values=false){
        $pid = $product_id[0];
        $vars['BASKET_PRODUCTS'] = $product_id;
        $vars['BASKET_PRICES']   = $prices;
        $payment_id = $this->add_waiting_payment($member_id, $pid,
            $paysys_id, $price, $begin_date, $expire_date, $vars,
            $additional_values);
        return $payment_id;
    }

    function finish_waiting_payment($payment_id, $paysys_id,
            $receipt_id, $price='', $vars, $payer_id=''){

        global $config;

        settype($payment_id, 'integer');
        settype($price,      'double');
        $paysys_id = $this->escape($paysys_id);
        $receipt_id = $this->escape($receipt_id);
        $payer_id = $this->escape($payer_id);

        $oldp = $this->get_payment($payment_id);

        // check for multiple payments
        $products = $oldp['data'][0]['BASKET_PRODUCTS'];
        $prices   = $oldp['data'][0]['BASKET_PRICES'];
        $coupon   = $oldp['data'][0]['COUPON_CODE'];

        // add history
        $data = (array)$oldp['data'];
        if (is_array($vars))
            $data[] = $vars;
        $data = & $this->encode_data($data);
        $data = $this->escape($data);

        //get old payment and check it
        if (!$oldp['payment_id'])
            return "Payment not found with such ID#";
        if ($price && (doubleval($oldp['amount']) != doubleval($price)))
            return "Incorrect amount for payment";
        if ($oldp['paysys_id'] != $paysys_id)
            return "Incorrect payment system";
        if ($oldp['completed'])
            return "Payment is already completed";

        $this->query($sql = "UPDATE {$this->config['prefix']}payments
            SET completed=1, data='$data', receipt_id='$receipt_id',
            payer_id='$payer_id',
            tm_completed = IF(tm_completed, tm_completed, NOW())
            WHERE payment_id = $payment_id
              AND ($price=0 OR amount=$price)
              AND completed=0
              AND paysys_id='$paysys_id'
        ");
        $rows = mysql_affected_rows($this->conn);

        if (!$rows)
            return "Payment not found. Possible reasons: payment already processed, incorrect payment_id, payment system or there is amount mistake";
        else {
            // process coupon
            if ($coupon){
                $this->coupon_used($coupon, $payment_id, $oldp['product_id'],
                    $oldp['member_id']);
            }
            // if multiple payments
            if (count($products)>1) {
                global $payment_additional_fields;
                $additional_values = array();
                foreach ($payment_additional_fields as $f){
                    $fname = $f['name'];
                    if (isset($oldp['data'][$fname]))
                        $additional_values[$fname] = $oldp['data'][$fname];
                }

                $additional_values['TAXES']         = $oldp['data']['TAXES'];
                $additional_values['TAX_AMOUNT']    = $oldp['data']['TAX_AMOUNT'];

                $taxes = $additional_values['TAXES'];

                $user_payments = $this->get_user_payments($oldp['member_id'], 1);
                for ($i=1;$i<count($products);$i++){
                    $product = & get_product($products[$i]);
                    $begin_date  = $product->get_start($oldp['member_id']);
                    $expire_date = $product->get_expire($begin_date);

                    if ($config['use_tax']){
                        $additional_values['TAX_AMOUNT'] = ($taxes[$products[$i]]) ? $taxes[$products[$i]] : 0;
                    }

                    $pid = $this->add_waiting_payment($oldp['member_id'],
                        $products[$i], $paysys_id, $prices[$products[$i]],
                        $begin_date, $expire_date,
                        array('ORIG_ID' => $payment_id), $additional_values);
                    $this->finish_waiting_payment($pid, $paysys_id,
                        $receipt_id, $prices[$products[$i]], $vars, $payer_id);
                }
                /// update main payment to original price
                $p = $this->get_payment($payment_id);
                $p['amount']        = $prices[$p['product_id']];
                $p['tax_amount']    = $taxes[$p['product_id']];
                $p['data']['TAX_AMOUNT']    = $taxes[$p['product_id']];
                $this->update_payment($payment_id, $p);
            }
        }
        $this->check_subscription_added($payment_id);
        plugin_finish_waiting_payment($payment_id, $member_id);
        plugin_update_payments($payment_id, $member_id);

        $p = $this->get_payment($payment_id);
        $member_id = $p['member_id'];
        $product_id = $p['product_id'];

        $this->subscribe_new_purchase($member_id, $product_id);

        return '';
    }

    function check_subscription_added($payment_id){
        $p = $this->get_payment($payment_id);
        $member_id = $p['member_id'];
        $date = date('Y-m-d');
        if (!(($p['begin_date'] <= $date) && ($p['expire_date'] >= $date)
            && $p['completed']))
            return;
        $product_id = $p['product_id'];
        $m = $this->get_user($p['member_id']);
        if ($m['data']['status'][$product_id] != 1){
            $m['data']['status'][$product_id] = 1;
            $this->update_user_status($p['member_id'], $m['data']['status']);
            plugin_subscription_added($member_id,$product_id,$m);
        }
    }

    function check_subscriptions_for_all(){
        // first check all active payment records
        $dat = date('Y-m-d');
        $status = array();
        $q = $this->query("SELECT member_id, product_id
            FROM {$this->config[prefix]}payments p
            WHERE p.completed > 0
                AND p.begin_date <= '$dat' AND p.expire_date >= '$dat'
			ORDER BY member_id, product_id
            ");
        while (list($m, $p) = mysql_fetch_row($q))
        	$status[$m][$p] = 1;
        // get information for $user['status'] field
        $q = $this->query("SELECT
        	member_id,
        	CASE WHEN SUM(IF(expire_date>='$dat', 1, 0)) THEN 1
        		 WHEN SUM(IF(expire_date< '$dat', 1, 0)) THEN 2
				 ELSE 0
			END
        	FROM {$this->config[prefix]}payments
        	WHERE completed>0
        	GROUP BY member_id");
        while (list($m, $s) = mysql_fetch_row($q))
        	$user_status[$m] = $s;
        // compare with member records
		$q = $this->query("SELECT member_id, data, status
			FROM {$this->config[prefix]}members u");
		while ($u = mysql_fetch_assoc($q)){
			$u['data'] = unserialize($u['data']);
			$st = array_filter((array)$u['data']['status']);
			$st1 = (array)$status[$u['member_id']];
			foreach ($st as $pid => $v)
				if (array_key_exists($pid, $st1)){
					unset($st[$pid]); unset($st1[$pid]);
				}
			if (count($st) || count($st1)) {
				$this->check_subscriptions($u['member_id']);
			} elseif (($newst = intval($user_status[$u['member_id']])) != intval($u['status'])) {
                $this->query($s = "UPDATE {$this->config[prefix]}members
                    SET status = $newst WHERE member_id=$u[member_id]");
            }
		}
    }

    function check_subscriptions($member_id){
        $pl = $this->get_user_active_payments($member_id, date('Y-m-d'));
        $al = array();
        $product_ids = array();
        foreach ((array)$pl as $p) {
            $al[ $p['product_id'] ] = 1;
            $product_ids[$p['product_id']] = 1;
        }
        $m = $this->get_user($member_id);
        if (!$m['member_id']) return false; // no user record in db
        foreach ((array)$m['data']['status'] as $product_id=>$status)
            $product_ids[$product_id] = 1;
        $changes = 0;
        foreach (array_keys($product_ids) as $product_id){
            $status = $m['data']['status'][$product_id];
            // status 0 - not active, 1 - active
            if ($al[$product_id] != $status){
                if ($al[$product_id]) { // active
                    $m['data']['status'][$product_id] = intval($al[$product_id]);
                    plugin_subscription_added($member_id,$product_id,$m);
                } else { // not active
                    $m['data']['status'][$product_id] = intval($al[$product_id]);
                    plugin_subscription_deleted($member_id,$product_id,$m);
                }
                $m['data']['status'][$product_id] = intval($al[$product_id]);
                $changes++;
            }
        }
        if ($changes)
            $this->update_user_status($member_id, $m['data']['status']);
    }

    function add_payment(&$v){
        $member_id = $v['member_id'];
        settype($member_id, 'integer');
        if (!$member_id) return "member_id empty or 0";
        $v['data'] = $this->encode_data($v['data']);
        $vals = $this->escape_array($v);
        $fields_to_update = array(
            'member_id',
            'product_id', 'begin_date', 'expire_date',
            'paysys_id',  'receipt_id', 'amount',
            'completed', 'data',
        );
        $tm_completed = $vals['completed'] ? 'NOW()' : 'NULL';
        foreach ($fields_to_update as $k){
            $fields[] = $k;
            $values[] = "'".$vals[$k]."'";
        }
        $fields = join(',', $fields);
        $values = join(',', $values);
        $q = $this->query("INSERT INTO {$this->config['prefix']}payments
        ($fields, time, tm_added, tm_completed)
        VALUES
        ($values, NOW(), NOW(), $tm_completed)
        ");
        $payment_id = mysql_insert_id($this->conn);
        $GLOBALS['_amember_added_payment_id'] = $payment_id;
        if ($v['completed']>0) {
            $this->check_subscription_added($payment_id);
            plugin_finish_waiting_payment($payment_id, $member_id);
        }
        plugin_update_payments($payment_id, $member_id);

        $p = $this->get_payment($payment_id);
        $member_id = $p['member_id'];
        $product_id = $p['product_id'];

        $this->subscribe_new_purchase($member_id, $product_id);

        return '';
    }

    function update_payment($payment_id, &$v){
        settype($payment_id, 'integer');
        if (!$payment_id) return "payment_id empty or 0";
        $vals = $this->escape_array($v);
        $fields_to_update = array(
            'product_id', 'begin_date', 'expire_date',
            'paysys_id',  'receipt_id', 'amount',
            'completed'
        );
        foreach ($fields_to_update as $k){
            $update_statement .= "$k = '" . $vals[$k] . "', ";
        }
        if (isset($vals['tax_amount']))
            $update_statement .= "tax_amount = '" . $vals['tax_amount'] . "', ";
        $oldp = $this->get_payment($payment_id);
        if (isset($v['data'])){ // update only special fields
            $data = $oldp['data'];
            foreach ($v['data'] as $k=>$vv)
                $data[$k] = $vv;
            $data = $this->escape($this->encode_data($data));
            $update_statement .= " data='$data', ";
        }
        if (isset($v['member_id']))
            $update_statement .= " member_id = $v[member_id], ";
        $update_statement .= " payment_id=payment_id";
        $old = $this->get_payment($payment_id);
        if ($v['completed']){
            $completed_expr = ' ,tm_completed=IF(tm_completed,tm_completed,NOW()) ';
        }
        $q = $this->query($s = "UPDATE {$this->config['prefix']}payments
        SET  $update_statement
        $completed_expr
        WHERE payment_id=$payment_id
        ");
        $this->check_subscriptions($v['member_id']);
        if (($v['completed']>0) && !$old['completed'])
            plugin_finish_waiting_payment($payment_id, $member_id);
        plugin_update_payments($payment_id);
        return '';
    }

    function delete_payment($payment_id){
        settype($payment_id, 'integer');
        if (!$payment_id) return "payment_id empty or 0";

        $payment = $this->get_payment($payment_id);
        $member_id = $payment['member_id'];

        $this->query("DELETE FROM {$this->config['prefix']}payments WHERE payment_id=$payment_id");
        $this->check_subscriptions($payment['member_id']);
        plugin_update_payments($payment_id, $member_id);
        return '';
    }

    function get_user_payments($member_id, $only_completed=0){
        $where_add = "";
        settype($member_id, 'integer');
        settype($only_completed, 'integer');
        if ($only_completed)
            $where_add = " AND completed > 0";
        $q = $this->query($s = "SELECT p.*
            FROM {$this->config['prefix']}payments p
            WHERE p.member_id = $member_id $where_add
            ORDER BY p.begin_date DESC, p.expire_date DESC, p.completed
            DESC
        ");
        $rows = array();
        while ($row = mysql_fetch_assoc($q)){
            $row['data'] = & $this->decode_data($row['data']);
            $rows[] = $row;
        }
        return $rows;
    }

    function get_payments($beg_date, $end_date, $only_completed, $start=0, $limit=-1, $list_by='',
            $prod_search='', $is_search=0, $q='', $q_where='', $only_coupons=0){
        $q_orig = $q;
        $rows = array();

            $coupon_join = "";
            $coupon_fields = "";

        $beg_date = $this->escape($beg_date);
        $end_date = $this->escape($end_date);
        $end_date = "$end_date 23:59:59";
        settype($only_completed, 'integer');
        if ($only_completed>0) $where_add = " AND p.completed > 0";
        if ($only_completed<0) $where_add = " AND p.completed = 0";
        if ($only_coupons != 0) {
                $where_add = " AND p.coupon_id != ''";
                $coupon_join = "LEFT JOIN {$this->config['prefix']}coupon c ON (c.coupon_id = p.coupon_id)";
            $coupon_fields = ", c.discount, c.batch_id, c.code";
        }
        $limit_exp = $this->get_limit_exp($start, $limit);
        switch ($list_by){
            case 'add':
                $time_field = 'p.tm_added'; break;
            case 'complete':
                $time_field = 'p.tm_completed'; break;
            case '': default:
                $time_field = 'p.time';
        }

        if (!$is_search){
            $search_add = " $time_field >= '$beg_date'
                        AND $time_field <= '$end_date' ";
        } else {

            $q = $this->escape($q);
            switch ($q_where){
                case 'receipt_id': $search_add = "p.receipt_id LIKE '%$q%'";
                break;
                case 'payment_id': $search_add = "p.payment_id = '$q'";
                break;
                case 'login':      $search_add = "m.login = '$q'";
                break;
                case 'login_part': $search_add = "m.login LIKE '%$q%'";
                break;
                case 'amount':     $search_add = "p.amount='$q'";
                break;
                case 'remote_addr':$search_add = "p.remote_addr='$q'";
                break;
                case 'product':    $search_add = "pr.title like '%$q%'";
                break;
                case 'coupon_code': $search_add = "c.code like '%$q%'";
                                            $coupon_join = "LEFT JOIN {$this->config['prefix']}coupon c ON (c.coupon_id = p.coupon_id)";
                                            $coupon_fields = ", c.discount, c.batch_id, c.code";
                break;
                case '': default:
                    $search_add = "(p.receipt_id LIKE '%$q%'
                                 OR p.payment_id LIKE '%$q%'
                                 OR (p.amount <> 0.0 AND p.amount = '$q')
                                 OR p.remote_addr LIKE '$q'
                                 OR p.data LIKE '%$q%'
                                )";
            }
        }
        if ($prod_search)
        {
            $pr=explode(',',$prod_search);
            if (sizeof($pr)==1)
                $src_pr=" AND pr.product_id = $prod_search";
            else
            {
                $src_pr=" AND pr.product_id in (";
                for ($i=0; $i<sizeof($pr); $i++)
                    if ($i+1<sizeof($pr))
                        $src_pr.=$pr[$i].',';
                    else
                        $src_pr.=$pr[$i];
                $src_pr.=')';
            }
        }
        $q = $this->query($s = "SELECT
            p.*, m.login as member_login,
            pr.title as product_title
            $coupon_fields
            FROM {$this->config['prefix']}payments p
                LEFT JOIN {$this->config['prefix']}members m USING (member_id)
                LEFT JOIN {$this->config['prefix']}products pr ON (p.product_id = pr.product_id)
                $coupon_join
            WHERE $search_add $where_add $src_pr
            ORDER BY p.begin_date DESC
            $limit_exp
            ");
        while ($r = mysql_fetch_assoc($q)){
            $r['data'] = $this->decode_data($r['data']);
            $rows[] = $r;
        }
        return $rows;
    }
    function get_payments_c($beg_date, $end_date, $only_completed, $list_by='',
            $prod_search='', $is_search=0, $q='', $q_where='', $only_coupons=0){
        $rows = array();
        $beg_date = $this->escape($beg_date);
        $end_date = $this->escape($end_date);
        $end_date = "$end_date 23:59:59";
        settype($only_completed, 'integer');
        if ($only_completed>0) $where_add = " AND p.completed > 0";
        if ($only_completed<0) $where_add = " AND p.completed = 0";
        if ($only_coupons != 0) $where_add = " AND p.coupon_id != ''";
        $limit_exp = $this->get_limit_exp($start, $limit);
        switch ($list_by){
            case 'add':
                $time_field = 'p.tm_added'; break;
            case 'complete':
                $time_field = 'p.tm_completed'; break;
            case '': default:
                $time_field = 'p.time';
        }

        if (!$is_search){
            $search_add = " $time_field >= '$beg_date'
                        AND $time_field <= '$end_date' ";
        } else {
            $q = $this->escape($q);
            switch ($q_where){
                case 'receipt_id': $search_add = "p.receipt_id LIKE '%$q%'";
                break;
                case 'payment_id': $search_add = "p.payment_id = '$q'";
                break;
                case 'login':      $search_add = "m.login = '$q'";
                break;
                case 'login_part': $search_add = "m.login LIKE '%$q%'";
                break;
                case 'amount':     $search_add = "p.amount='$q'";
                break;
                case 'remote_addr':$search_add = "p.remote_addr='$q'";
                break;
                case 'product':    $search_add = "pr.title like '%$q%'";
                break;
                case '': default:
                    $search_add = "(p.receipt_id LIKE '%$q%'
                                 OR p.payment_id LIKE '%$q%'
                                 OR (p.amount <> 0.0 AND p.amount = '$q')
                                 OR p.remote_addr LIKE '$q'
                                 OR p.data LIKE '%$q%'
                                )";
            }
        }
        if ($prod_search)
        {
            $pr=explode(',',$prod_search);
            if (sizeof($pr)==1)
                $src_pr=" AND pr.product_id = $prod_search";
            else
            {
                $src_pr=" AND pr.product_id in (";
                for ($i=0; $i<sizeof($pr); $i++)
                    if ($i+1<sizeof($pr))
                        $src_pr.=$pr[$i].',';
                    else
                        $src_pr.=$pr[$i];
                $src_pr.=')';
            }
        }
        $q = $this->query($s = "SELECT COUNT(p.payment_id), SUM(p.amount)
            FROM {$this->config['prefix']}payments p
                LEFT JOIN {$this->config['prefix']}members m USING (member_id)
                LEFT JOIN {$this->config['prefix']}products pr ON (p.product_id = pr.product_id)
            WHERE $search_add $where_add $src_pr
            ");
        list($c, $s) = mysql_fetch_row($q);
        return array($c, $s);
    }

    function get_user_active_payments($member_id, $date){
        settype($member_id, 'integer');
        $date = $this->escape($date);
        $q = $this->query($s = "SELECT
            p.*, m.login as member_login,
            pr.title as product_title
            FROM {$this->config['prefix']}payments p
                LEFT JOIN {$this->config['prefix']}members m USING (member_id)
                LEFT JOIN {$this->config['prefix']}products pr ON (p.product_id = pr.product_id)
            WHERE
                    p.member_id = $member_id
                AND p.begin_date <= '$date'
                AND p.expire_date >= '$date'
                AND p.completed > 0
            ORDER BY p.begin_date DESC
            ");
        while ($r = mysql_fetch_assoc($q)){
            $r['data'] = $this->decode_data($r['data']);
            $rows[] = $r;
        }
        return $rows;
    }

    function get_expired_payments($beg_date, $end_date, $paysys_id='', $product_makes_sense=1, $product_id = null){
        $beg_date = $this->escape($beg_date);
        $end_date = $this->escape($end_date);
        $where_add = "";
        if ($paysys_id)
            $where_add .= " AND p.paysys_id = '" . $this->escape($paysys_id)."' ";
        if ($product_makes_sense)
            $product_add = "p.product_id = p1.product_id AND";
        if ($product_id > 0)
            $product_id_add = " AND p.product_id = " . intval($product_id);
        $q = $this->query($s = "SELECT
            p.*, m.login as member_login,
            pr.title as product_title,
            p1.payment_id as next_payment_id
            FROM {$this->config['prefix']}payments p
                LEFT JOIN {$this->config['prefix']}members m USING (member_id)
                LEFT JOIN {$this->config['prefix']}products pr ON (p.product_id = pr.product_id)
                LEFT JOIN {$this->config['prefix']}payments p1 ON
                    (p.member_id  = p1.member_id AND
                     $product_add
                     p1.completed > 0 AND
                     p.expire_date     < p1.expire_date)
            WHERE
                    p.expire_date >= '$beg_date'
                AND p.expire_date <= '$end_date'
                AND p.completed > 0
                $product_id_add
                $where_add
            HAVING next_payment_id IS NULL
            ORDER BY p.begin_date DESC
            ");
        $rows = array();
        while ($r = mysql_fetch_assoc($q)){
            $r['data'] = $this->decode_data($r['data']);
            $rows[] = $r;
        }
        return $rows;
    }


    function get_payment($payment_id){
        settype($payment_id, 'integer');
        $q = $this->query($s = "SELECT
            p.*, m.login as member_login,
            pr.title as product_title
            FROM {$this->config['prefix']}payments p
                LEFT JOIN {$this->config['prefix']}members m USING (member_id)
                LEFT JOIN {$this->config['prefix']}products pr ON (p.product_id = pr.product_id)
            WHERE
                p.payment_id = $payment_id
            ");
        $r = mysql_fetch_assoc($q);
        if ($r)
            $r['data'] = $this->decode_data($r['data']);
        return $r;
    }

    function get_payment_by_data($data_name = '', $data_value = ''){
        $q = $this->query($s = "SELECT
            payment_id, data
            FROM {$this->config['prefix']}payments
            ");
        $payment_id = 0;
        while ($r = mysql_fetch_assoc($q)){
            $r['data'] = $this->decode_data($r['data']);
            if ($r['data'][$data_name] == $data_value){
                $payment_id = $r['payment_id'];
                break; // payment found. break while loop
            }
        }
        return $payment_id;
    }

    function clear_incomplete_payments($date){
        $date = $this->escape($date);
        $this->query($s = "DELETE FROM {$this->config['prefix']}payments
            WHERE time < '$date' AND completed = 0");
    }

    /////////////////// PRODUCTS FUNCTIONS //////////////////////////////////

    function get_product($product_id){
        global $__amember_product_cache;
        if (isset($__amember_product_cache[$product_id])){
            return $__amember_product_cache[$product_id];
        }
        settype($product_id, 'integer');
        $q = $this->query("SELECT * FROM {$this->config['prefix']}products
            WHERE product_id=$product_id
        ");
        $row = mysql_fetch_assoc($q);
        $data = $row['data']; unset($row['data']);
        $vals = & $this->decode_data( $data);
        foreach ($vals as $k=>$v)
            $row[$k] = $v;
        $__amember_product_cache[$product_id] = $row;
        return $row;
    }

    function get_products_list(){
        //_amember_get_iconf_d();
        $q = $this->query("SELECT * FROM {$this->config['prefix']}products
            ORDER BY title");
        $rows = array();
        while ($row = mysql_fetch_assoc($q)){
            $data = $row['data']; unset($row['data']);
            $vals = & $this->decode_data( $data);
            foreach ($vals as $k=>$v)
                $row[$k] = $v;
            $rows[] = $row;
        }
        usort($rows, 'product_sort_cmp');
        return $rows;
    }

    function update_product($product_id, &$vars){
        _amember_get_iconf_d();
        settype($product_id, 'integer');
        if (!$product_id) return "product_id empty or 0";

        global $product_fields;
        global $product_additional_fields;

        foreach ($product_fields as $ff){
            $k = $ff['name'];
            $update_statement .= "$k = '" . $this->escape($vars[$k]) . "', ";
        }
        foreach ($product_additional_fields as $ff){
            $k = $ff['name'];
            $data[$k] = $vars[$k];
        }
        $data_s = $this->escape($this->encode_data($data));
        $update_statement .= " data = '$data_s' ";

        $q = $this->query($s = "UPDATE {$this->config['prefix']}products
        SET  $update_statement
        WHERE product_id=$product_id
        ");
        return '';
    }

    function add_product(&$vars){
        _amember_get_iconf_d();
        $f = array();

        global $product_fields;
        global $product_additional_fields;
        foreach ($product_fields as $ff){
            $k = $ff['name'];
            $f[$k] = "'" . $this->escape( $vars[$k] )  . "'";
        }
        foreach ($product_additional_fields as $ff){
            $k = $ff['name'];
            $data[$k] = $vars[$k];
        }
        if ($f['product_id'] == "''") $f['product_id'] = "NULL";
        $f['data'] = "'" . $this->escape($this->encode_data($data)) . "'" ;


        $fields = join(',', array_keys($f));
        $values = join(',', array_values($f));
        $q = $this->query($s = "INSERT INTO {$this->config['prefix']}products
            ($fields) VALUES ($values)
        ");
        return mysql_insert_id($this->conn);
    }

    function delete_product($product_id, $remove_related_records=0){
        _amember_get_iconf_d();
        settype($product_id, 'integer');
        if (!$product_id) return "product_id empty or 0";

        if ($remove_related_records){
            $q = $this->query("SELECT payment_id, member_id
            FROM {$this->config[prefix]}payments
            WHERE product_id = $product_id
            ");
            while (list($payment_id, $member_id) = mysql_fetch_row($q)){
                $this->delete_payment($payment_id);
            }
        }


        $this->query("DELETE FROM {$this->config['prefix']}products
            WHERE product_id=$product_id");
        return '';
    }

    ////////////////////////// ERROR LOG ////////////////////////////

    function get_error_log($start, $count){
        settype($start, 'integer');
        settype($count, 'integer');
        $q = $this->query("SELECT *
            FROM {$this->config['prefix']}error_log
            ORDER BY time DESC, log_id DESC
            LIMIT $start,$count");
        while ($r = mysql_fetch_assoc($q)){
            $rows[] = $r;
        }
        return $rows;
    }

    function get_error_log_c(){
        $q = $this->query("SELECT COUNT(*)
            FROM {$this->config['prefix']}error_log ");
        $res = mysql_fetch_row($q);
        return intval($res[0]);
    }

    function log_error($error){
        global $db;
        extract($_SERVER);
        $error = $this->escape($error);
        $this->query("INSERT INTO {$this->config['prefix']}error_log
        (member_id, time, remote_addr, url, referrer, error)
        VALUES
        (NULL, NOW(), '$REMOTE_ADDR',
        '".$this->escape($REQUEST_URI)."', '".$this->escape($HTTP_REFERER)."',
        '$error')
        ");
    }

    function clear_error_log($date){
        $date = $this->escape($date);
        $this->query($s = "DELETE FROM {$this->config['prefix']}error_log
            WHERE time < '$date 00:00:00'");
    }

    ////////////////////////// ACCESS LOG ////////////////////////////

    function log_access($member_id){
        global $db;
        extract($_SERVER);
//        if (preg_match('/proxy\.aol\.com$/', gethostbyaddr($REMOTE_ADDR)))
//            return;
        settype($member_id, 'integer');
        $this->query("INSERT INTO {$this->config['prefix']}access_log
        (member_id, remote_addr, url, referrer)
        VALUES
        ($member_id, '$REMOTE_ADDR', '".$this->escape($REQUEST_URI)."', '".$this->escape($HTTP_REFERER)."')
        ");
    }

    function log_remote_access($member_id, $ip, $url, $referer){

        if (preg_match('/proxy\.aol\.com$/', gethostbyaddr($ip)))
            return;
        settype($member_id, 'integer');
        $this->query("INSERT INTO {$this->config['prefix']}access_log
        (member_id, remote_addr, url, referrer)
        VALUES
        ($member_id, '".$this->escape($ip)."', '".$this->escape($url)."', '".$this->escape($referer)."')
        ");
    }

    function log_aff_click($aff_id, $url){
        global $db;
        extract($_SERVER);
        settype($aff_id, 'integer');
        $REMOTE_ADDR = $this->escape($REMOTE_ADDR);
        $HTTP_REFERER = $this->escape($HTTP_REFERER);
        $url = $this->escape($url);
        $this->query("INSERT INTO {$this->config['prefix']}aff_clicks
        (aff_id, remote_addr, url, referrer)
        VALUES
        ($aff_id, '$REMOTE_ADDR', '$url', '$HTTP_REFERER')
        ");
    }

    function get_access_log($member_id=0, $start=0, $count=20, $order='a.time DESC'){
        settype($start, 'integer');
        settype($count, 'integer');
        settype($member_id, 'integer');
        if ($member_id) $where_add = " AND a.member_id = $member_id ";
        $q = $this->query("SELECT a.*, m.login
            FROM {$this->config['prefix']}access_log a
                LEFT JOIN {$this->config['prefix']}members m USING (member_id)
            WHERE 1 $where_add
            ORDER BY $order
            LIMIT $start,$count");
        while ($r = mysql_fetch_assoc($q)){
            $rows[] = $r;
        }
        return $rows;
    }

    function get_aff_clicks($aff_id=0, $start=0, $count=20, $order='a.time DESC'){
        settype($start, 'integer');
        settype($count, 'integer');
        settype($aff_id, 'integer');
        if ($aff_id) $where_add = " AND a.aff_id = $aff_id ";
        $q = $this->query("SELECT a.*, m.login
            FROM {$this->config['prefix']}aff_clicks a
                LEFT JOIN {$this->config['prefix']}members m
                    ON m.member_id = a.aff_id
            WHERE 1 $where_add
            ORDER BY $order
            LIMIT $start,$count");
        while ($r = mysql_fetch_assoc($q)){
            $rows[] = $r;
        }
        return $rows;
    }

    function get_aff_clicks_distinct(){
        $q = $this->query("
            SELECT DISTINCT a.aff_id, m.login
            FROM {$this->config['prefix']}aff_clicks a
            LEFT JOIN {$this->config['prefix']}members m
            ON m.member_id = a.aff_id
            ");
        while ($r = mysql_fetch_assoc($q)){
            $aff_id = $r['aff_id'];
            $rows[$aff_id] = $r['login'];
        }
        return $rows;
    }

    function get_access_log_c($member_id=0){
        settype($member_id, 'integer');
        if ($member_id > 0)
            $where = "WHERE member_id = $member_id ";
        $q = $this->query("SELECT COUNT(*)
            FROM {$this->config['prefix']}access_log
            $where");
        $res = mysql_fetch_row($q);
        return intval($res[0]);
    }

    function get_aff_clicks_c($aff_id=0){
        settype($aff_id, 'integer');
        if ($aff_id > 0)
            $where = "WHERE aff_id = $aff_id ";
        $q = $this->query("SELECT COUNT(*)
            FROM {$this->config['prefix']}aff_clicks
            $where");
        $res = mysql_fetch_row($q);
        return intval($res[0]);
    }

    function clear_access_log($date){
        $date = $this->escape($date);
        $this->query($s = "DELETE FROM {$this->config['prefix']}access_log
            WHERE time < '$date 00:00:00'");
    }

    function clear_admin_log($date){
        $date = $this->escape($date);
        $this->query($s = "DELETE FROM {$this->config['prefix']}admin_log
            WHERE dattm < '$date 00:00:00'");
    }

    function clear_aff_clicks($date){
        $date = $this->escape($date);
        $this->query($s = "DELETE FROM {$this->config['prefix']}aff_clicks
            WHERE time < '$date 00:00:00'");
    }

    function check_multiple_ip($member_id, $max_ip_count, $max_ip_period, $ip){
        settype($member_id, 'integer');
        settype($max_ip_count, 'integer');
        settype($max_ip_period, 'integer');
        $begtm = date('Y-m-d H:i:s', time() - $max_ip_period * 60);
        $endtm = date('Y-m-d H:i:s', time());
        $ip = $this->escape($ip);
        $q = $this->query("SELECT COUNT(DISTINCT remote_addr)
        FROM {$this->config['prefix']}access_log
        WHERE member_id=$member_id
        AND time BETWEEN '$begtm' AND '$endtm'
            AND remote_addr <> '$ip'
        ");
        list($ip_count) = mysql_fetch_row($q);
        return $ip_count >= $max_ip_count;
    }

    ////////////////////// CRON FUNCTIONS ////////////////////////////////////

    function save_cron_time($id){
        settype($id, 'integer');
        $this->query("REPLACE INTO {$this->config['prefix']}cron_run (id,time)
            VALUES ($id,now())");
    }

    function load_cron_time($id){
        settype($id, 'integer');
        $q = $this->query($s="SELECT UNIX_TIMESTAMP(time)
            FROM {$this->config['prefix']}cron_run
            WHERE id=$id");
        list($time) = mysql_fetch_row($q);
        return $time;
    }

    //////////////////// COUPONS   ////////////////////////////////////////
    function get_coupon_batches(){
        $q = $this->query("SELECT DISTINCT batch_id,
            COUNT(*) AS coun,
            MIN(begin_date) AS begin_date1,
            MAX(begin_date) AS begin_date2,
            MIN(expire_date) AS expire_date1,
            MAX(expire_date) AS expire_date2,
            SUM(use_count) AS use_count,
            SUM(IFNULL(used_count,0)) AS used_count,
            SUM(locked) AS locked_count
            FROM {$this->config['prefix']}coupon
            GROUP BY batch_id
            ORDER BY batch_id
            ");
        $rows = array();
        while ($r = mysql_fetch_assoc($q)){
            $rows[] = $r;
        }
        return $rows;
    }

    function get_coupons($where, $what){
        $where_add = '';
        $what = $this->escape($what);
        switch ($where){
            case 'coupon_id': $where_add = "AND coupon_id = '$what'";
            break;
            case 'batch_id': $where_add = "AND batch_id = '$what'";
            break;
            case 'code': $where_add = "AND code='$what'";
            break;
            case 'member': $where_add = "AND member_id='$what'";
            break;

        }
        $q = $this->query($s = "SELECT *
            FROM {$this->config['prefix']}coupon
            WHERE 1 $where_add
            ");
        $rows = array();
        while ($r = mysql_fetch_assoc($q)){
            $r['data'] = $this->decode_data($r['data']);
            $rows[] = $r;
        }
        return $rows;
    }

    function generate_coupons($vars){
        $count = intval($vars['count']);
        $use_count = intval($vars['use_count']);
        $member_use_count = intval($vars['member_use_count']);
        $code_len  = intval($vars['code_len']);
        $discount  = $this->escape($vars['discount']);
        $comment  =  $this->escape($vars['comment']);
        $begin_date = $this->escape($vars['begin_date']);
        $expire_date = ( isset($vars['expire_date']) ) ? $this->escape($vars['expire_date']) : MAX_SQL_DATE;
        $locked      = intval($vars['locked']);
        $product_id  = $this->escape((join(',',(array)$vars['product_id'])));
        $is_recurring = intval($vars['is_recurring']);
        ////
        $q = $this->query("SELECT MAX(batch_id)
            FROM {$this->config['prefix']}coupon");
        list($batch_id) = mysql_fetch_row($q);
        $batch_id++;

        ////
        list($usec, $sec) = explode(' ', microtime());
        srand((float) $sec + ((float) $usec * 100000));
        for ($i=0;$i<$count;$i++){
            do {
                $cc = strtoupper(md5(uniqid('', 1)));
                $cc = substr($cc, 0, $code_len);
                $q = $this->query("SELECT COUNT(*)
                    FROM {$this->config['prefix']}coupon
                    WHERE code = '$cc' ");
                list($exists) = mysql_fetch_row($q);
            } while ($exists);
            $this->query("INSERT INTO {$this->config['prefix']}coupon
            (batch_id, code, comment, discount, begin_date, expire_date,
            locked, product_id, use_count, member_use_count, is_recurring)
            VALUES
            ($batch_id, '$cc', '$comment', '$discount', '$begin_date', '$expire_date',
            $locked, '$product_id', $use_count, $member_use_count, $is_recurring)
            ");
        }
        ////
        return $batch_id;
    }

    function coupons_batch_edit($batch_id, $vars){
        $use_count = intval($vars['use_count']);
        $member_use_count = intval($vars['member_use_count']);
        $discount  = $vars['discount'];
        $comment  =  $vars['comment'];
        $begin_date = $vars['begin_date'];
        $expire_date = ( isset($vars['expire_date']) ) ? $vars['expire_date'] : MAX_SQL_DATE;
        $locked      = intval($vars['locked']);
        $product_id  = join(',',(array)$vars['product_id']);
        $is_recurring = intval($vars['is_recurring']);
        ////
        $this->query("UPDATE {$this->config['prefix']}coupon
        SET use_count=$use_count, member_use_count=$member_use_count,
        discount='$discount',
        comment='$comment', begin_date='$begin_date',
        expire_date='$expire_date', product_id='$product_id',
        locked=$locked,
        is_recurring=$is_recurring
        WHERE batch_id=$batch_id
        ");
    }

    function coupon_edit($coupon_id, $vars){
        $used_count = intval($vars['used_count']);
        $code  = $vars['code'];
        ////
        $this->query($s = "UPDATE {$this->config['prefix']}coupon
        SET used_count=$used_count, code='$code'
        WHERE coupon_id=$coupon_id
        ");
    }

    function coupons_batch_delete($batch_id){
        if ($batch_id <= 0) fatal_error("Cannot delete: Batch ID=0", 0);
        ////
        $this->query("DELETE FROM {$this->config['prefix']}coupon
        WHERE batch_id=$batch_id
        ");
    }

    function coupons_batch_join($from_batch_id, $to_batch_id){
        $this->query("UPDATE {$this->config['prefix']}coupon
        SET batch_id=$to_batch_id
        WHERE batch_id=$from_batch_id
        ");
    }

    function delete_coupon($coupon_id){
        if ($coupon_id <= 0) fatal_error("Cannot delete: Coupon ID=0", 0);
        ////
        $this->query("DELETE FROM {$this->config['prefix']}coupon
        WHERE coupon_id=$coupon_id
        ");
    }

    /**
    * Function seeks for a given coupon in database and returns
    * an error string or if coupon is allowed to use, it returns
    * coupon record as array
    * @param string code
    * @param int member_id
    * @return string|array Error string, or coupon record array
    */
    function coupon_get($code, $member_id = null, $locked_ignore=0){
        $coupons = $this->get_coupons('code', $code);
        if (!count($coupons))
            return "Coupon not found, please check coupon code";
        $coupon = $coupons[0];
		if ( $coupon[ 'member_id' ] > 0 AND $coupon[ 'member_id' ] != $member_id )
            return "Coupon not found, please check coupon code*";
        if ($statement = ($locked_ignore) ? 0 : $coupon['locked'])
            return "Coupon locked, please contact webmaster";
        if ($coupon['used_count'] >= $coupon['use_count'])
            return "Coupon is already used";
        if ($coupon['used_count'] >= $coupon['use_count'])
            return "Coupon is already used";
        if ($coupon['begin_date'] && ($coupon['begin_date'] != '0000-00-00')
            && ($coupon['begin_date'] > date('Y-m-d')))
            return "Coupon cannot be used yet";
        if ($coupon['expire_date'] && ($coupon['expire_date'] != '0000-00-00')
            && ($coupon['expire_date'] < date('Y-m-d')))
            return "Coupon expired";

        if ($member_id > 0){
            $member_used_count = 0;
            if ($coupon['member_use_count'] && $member_id){
                foreach (split(';', $coupon['used_for']) as $s){
                    list($p,$pr,$m) = split(',',$s);
                    if ($m == $member_id) $member_used_count++;
                }
                if ($member_used_count >= $coupon['member_use_count']){
                    return "Coupon is already used";
                }
            }
        }

        return $coupon;
    }

    function coupon_used($code, $payment_id, $product_id, $member_id){
        $code = $this->escape($code);
        settype($payment_id, 'integer');
        settype($product_id, 'integer');
        settype($member_id, 'integer');
        $used_for = "$payment_id,$product_id,$member_id;";
        $q = $this->query("SELECT coupon_id
        FROM {$this->config['prefix']}coupon
        WHERE code='$code'");
        list($coupon_id) = mysql_fetch_row($q);
        if ($coupon_id) {
            $this->query("UPDATE {$this->config['prefix']}coupon
            SET used_for=concat(ifnull(used_for, ''), '$used_for'),
            used_count=ifnull(used_count,0)+1
            WHERE coupon_id='$coupon_id'
            ");
            $this->query($s = "UPDATE {$this->config['prefix']}payments
            SET coupon_id='$coupon_id'
            WHERE payment_id='$payment_id'
            ");
        }
    }

    /////////////////////////// CONFIG FUNCTIONS /////////////////////////
    function config_update($fields, $db_vars){
        foreach ($db_vars as $k=>$v){
            $store_type = 0;
            if ($field = $fields[$k])
                $store_type = $field['params']['store_type'];
            settype($store_type, 'integer');
            $this->config_set($k, $v, $store_type);
        }
    }
    function config_set($k,$v,$store_type){
        switch ($store_type){
            case 0: $v = $v;  //text
            break;
            case 1: $bv = serialize($v); $v = '';// serialize
            break;
            case 2: $bv = $v; $v = ''; // blob
            break;
            case 3: $v = amember_crypt($v); // crypt
            break;
            case 4: $v = $v; //eval
            break;
            default: fatal_error("Unknown store_type");
        }
        $v  = $this->escape($v);
        $bv = strlen($bv) ?  "'". $this->escape($bv) . "'" : 'NULL';
        $this->query("REPLACE INTO {$this->config[prefix]}config
        (name, type,value,blob_value)
        VALUES
        ('$k', $store_type, '$v', $bv)
        ");
    }

    /////////////////////////// AFFILIATES ///////////////////////////////
    function aff_add_commission($aff_id, $commission, $payment_id,
        $receipt_id, $product_id, $is_first, $skip_receipt_check=false, $tier=1){
        /// payment_id+$receipt_id is unique key
        settype($payment_id, 'integer');
        settype($aff_id, 'integer');
        settype($tier, 'integer');
        if (!$skip_receipt_check){
            $q = $this->query("SELECT *
                FROM {$this->config[prefix]}aff_commission
                WHERE payment_id=$payment_id AND tier=$tier");
            $oldc = mysql_fetch_assoc($q);
            if ($oldc['payment_id']) { // found old record
                if ($oldc['receipt_id'] == $receipt_id) return; //already added
                if ($oldc['receipt_id'] == '') return; // if was empty, don't double commission too
                if ($receipt_id == '') return; // if new record is empty, don't double too
            }
        }
        ///
        $q = $this->query("INSERT INTO {$this->config[prefix]}aff_commission
        (aff_id, date, amount, record_type,
         payment_id, receipt_id, product_id,
         is_first, tier)
        VALUES
        ($aff_id,SYSDATE(), '$commission','credit',
        '$payment_id', '$receipt_id', '$product_id',
        '$is_first', $tier)
        ");
        global $config;
        if ($config['aff']['mail_sale_admin']){
            mail_aff_sale_admin($payment_id, $aff_id, $commission, $receipt_id, $product_id, $tier);
        }
        if ($config['aff']['mail_sale_user']){
            mail_aff_sale_user($payment_id, $aff_id, $commission, $receipt_id, $product_id, $tier);
        }
        return mysql_insert_id($this->conn);
    }
    /////////////////////////// ADMIN FUNCTIONS ///////////////////////////
    function get_admin($admin_id){
        settype($admin_id, 'integer');
        $q = $this->query($s = "SELECT a.*
            FROM {$this->config['prefix']}admins a
            WHERE a.admin_id = $admin_id
        ");
        if ($r = mysql_fetch_assoc($q))
            $r['perms'] = unserialize($r['perms']);
        else
            $r = array();
        return $r;
    }
    function get_admins_list(){
        $q = $this->query($s = "SELECT a.*
            FROM {$this->config['prefix']}admins a
        ");
        $list = array();
        while ($r = mysql_fetch_assoc($q)) {
            $r['perms'] = unserialize($r['perms']);
            $list[] = $r;
        }
        return $list;
    }
    function add_admin($rec){
        $pass = $this->escape(crypt($rec['pass']));
        $rec['perms'] = serialize($rec['perms']);
        foreach ($rec as $k=>$v)
            $rec[$k] = $this->escape($v);
        if ($this->query_one("SELECT admin_id
            FROM {$this->config['prefix']}admins
            WHERE login='$rec[login]'")){
            return "Admin '$rec[login] is already exists, please choose another username";
        }
        $this->query("INSERT INTO {$this->config['prefix']}admins
            SET
            login='$rec[login]',
            pass='$pass',
            email='$rec[email]',
            super_user='$rec[super_user]',
            perms='$rec[perms]'
            ");
    }
    function get_admin_super_users_count(){
        return $this->query_one("SELECT COUNT(admin_id)
            FROM {$this->config['prefix']}admins
            WHERE super_user > 0
            ");
    }
    function update_admin($admin_id, $rec){
        $rec['perms'] = serialize($rec['perms']);
        $pass = $this->escape(crypt($rec['pass']));
        foreach ($rec as $k=>$v)
            $rec[$k] = $this->escape($v);
        if ($this->query_one("SELECT admin_id
            FROM {$this->config['prefix']}admins
            WHERE login='$rec[login]'
              AND admin_id <> $rec[admin_id] ")){
            return "Admin '$rec[login] is already exists, please choose another username";
        }
        $prec = $this->get_admin($admin_id);
        if ($prec['super_user'] && !$rec['super_user'])
            if ($this->get_admin_super_users_count() == 1)
                return "This admin is a super-user, there must be at least one super-user in database";
        if ($rec['pass'] != '')
            $pass_string = " pass='$pass', ";
        $this->query($s = "UPDATE {$this->config['prefix']}admins
            SET
            login='$rec[login]',
            $pass_string
            email='$rec[email]',
            super_user='$rec[super_user]',
            perms='$rec[perms]'
            WHERE admin_id = $rec[admin_id]
            ");
    }
    function delete_admin($admin_id){
        settype($admin_id, 'integer');
        $prec = $this->get_admin($admin_id);
        if ($prec['super_user'])
            if ($this->get_admin_super_users_count() == 1)
                return "This admin is a super-user, there must be at least one super-user in database";
        $this->query($s = "UPDATE {$this->config['prefix']}admin_log
            SET admin_login = '$prec[login]', admin_id = 0
            WHERE admin_id=$admin_id
        ");
        $q = $this->query($s = "DELETE
            FROM {$this->config['prefix']}admins
            WHERE admin_id = $admin_id
        ");
    }
    function admin_update_login_info($admin_id){
        settype($admin_id, 'integer');
        $ip = $_SERVER['REMOTE_ADDR'];
        foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_PROXY_USER') as $k){
            if ($v=$_SERVER[$k]) {
                $ip .= " ($v)";
                break;
            }
        };
        $sid = session_id();
        $ip = $this->escape($ip);
        $sid = $this->escape($sid);
        $q = $this->query($s = "UPDATE
            {$this->config['prefix']}admins
            SET
                last_login = NOW(),
                last_ip = '$ip',
                last_session='$sid'
            WHERE admin_id = $admin_id
        ");
    }
    function check_admin_password($login, $pass){
        $l = $this->escape($login);
        list($admin_id, $db_pass) = $this->query_row($s = "
            SELECT admin_id, pass
            FROM {$this->config['prefix']}admins
            WHERE login='$l'
        ");
        return (($admin_id > 0) && (crypt($pass, $db_pass) == $db_pass)) ?
            $this->get_admin($admin_id) : array();
    }
    /// ADMIN LOG
    function admin_log($message, $tablename='', $record_id='', $admin_id=0){
        $ip = $_SERVER['REMOTE_ADDR'];
        foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_PROXY_USER') as $k){
            if ($v=$_SERVER[$k]) {
                $ip .= " ($v)";
                break;
            }
        };
        $message = $this->escape($message);
        if (!$admin_id)
            $admin_id = intval($_SESSION['amember_admin']['admin_id']);
        $this->query("INSERT INTO {$this->config['prefix']}admin_log
        SET
        dattm=NOW(),
        admin_id='$admin_id',
        ip = '$ip',
        tablename='$tablename',
        record_id='$record_id',
        message='$message'
        ");
    }


    /////////////////////////// NEWSLETTER THREADS FUNCTIONS ///////////////////////////

    function get_threads_list($start, $limit, $member_id='', $amember_cp=false) {
        $limit_exp = $this->get_limit_exp($start, $limit);
        settype($member_id, 'integer');
        $q = $this->query($s = "
            SELECT thread_id,title,description,is_active,blob_available_to AS available_to,blob_auto_subscribe AS auto_subscribe
            FROM {$this->config['prefix']}newsletter_thread
            ORDER BY title
            $limit_exp
            ");

        $rows = array();
        while ($r = mysql_fetch_assoc($q)){
                if ($r['thread_id']) {
                    if ($_SESSION['_admin_login'] && $_SESSION['amember_admin'] && $amember_cp){ // only for aMember CP -> Email Users -> Newsletter Threads
                    //get subscribers
                    $subscribers = "";
                    $guests_list_c = $this->get_guests_list_c('', array($r['thread_id']));
                    if ($guests_list_c > 0)
                        $subscribers .= "<a href=\"newsletter_view_guests.php?tr=".$r['thread_id']."\">Guests: " . $guests_list_c . "</a>";
                    else
                        $subscribers .= "Guests: " . $guests_list_c;
                    $members_list_c = $this->get_members_list_c('', array($r['thread_id']));
                    if ($members_list_c > 0)
                        $subscribers .= ", <a href=\"newsletter_view_members.php?tr=".$r['thread_id']."\">Members: " . $members_list_c . "</a>";
                    else
                        $subscribers .= ", Members: " . $members_list_c;

                    $r['subscribers'] = $subscribers;
                    }
                }
            if (!$member_id || $this->is_thread_available_to_member($r['thread_id'], $member_id))
                $rows[] = $r;
        }
        return $rows;
    }

    function get_threads_list_c($member_id='') {
        settype($member_id, 'integer');
        if (!$member_id){
            $q = $this->query($s = "SELECT COUNT(*)
                FROM {$this->config['prefix']}newsletter_thread
                ");

            $c = mysql_fetch_row($q);
            return $c[0];
        } else {
            $q = $this->query($s = "
                SELECT thread_id
                FROM {$this->config['prefix']}newsletter_thread
                ");
            $c = 0;
            while ($r = mysql_fetch_assoc($q)){
                if ($this->is_thread_available_to_member($r['thread_id'], $member_id))
                    $c++;
            }
            return $c;
        }
    }

    function get_thread($thread_id = '') {
        settype($thread_id, 'integer');
        $q = $this->query($s = "SELECT title AS thread_title,description AS thread_description,is_active,
            blob_available_to AS available_to,blob_auto_subscribe AS auto_subscribe
            FROM {$this->config['prefix']}newsletter_thread
            WHERE thread_id = '$thread_id'
            ");

        if (! $r = mysql_fetch_assoc($q)){
            $this->log_error("Newsletter thread not found: #$thread_id");
            $r = array();
        }
        return $r;
    }

    function delete_thread($thread_id){
        settype($thread_id, 'integer');
        if (!$thread_id) fatal_error("thread_id empty or 0");
        $this->query("DELETE FROM {$this->config['prefix']}newsletter_thread WHERE thread_id=$thread_id");
        $this->query("DELETE FROM {$this->config['prefix']}newsletter_member_subscriptions WHERE thread_id=$thread_id");
        $this->query("DELETE FROM {$this->config['prefix']}newsletter_guest_subscriptions WHERE thread_id=$thread_id");
        return '';
    }


        function get_newsletter_threads($member_id=''){
            global $config;
            settype($member_id, 'integer');
            $res = array();
            $q = $this->query($s = "
                 SELECT thread_id, title
                 FROM {$this->config['prefix']}newsletter_thread
                 WHERE is_active = 1
                 ORDER BY title
                ");
            while ($tr = mysql_fetch_assoc($q)){
                $thread_id = $tr['thread_id'];
                if (!$member_id || $this->is_thread_available_to_member($thread_id, $member_id))
                    $res[$thread_id] = $tr['title'];
            }
            return $res;
        }

        function get_signup_threads_c($is_affiliate = '0'){
            global $config;

            if ($config['use_affiliates'] && $is_affiliate == '2')
                $auto = 'aff'; //only affiliates
            else
                $auto = 'all'; //only members

            $res = array();
            $q = $this->query($s = "
                 SELECT thread_id, blob_available_to AS available_to, blob_auto_subscribe AS auto_subscribe
                 FROM {$this->config['prefix']}newsletter_thread
                 WHERE is_active = 1
                ");
            while ($tr = mysql_fetch_assoc($q)){
                $thread_id = $tr['thread_id'];

                $available_to = $tr['available_to']; $available_to = explode (",", $available_to);
                $auto_subscribe = $tr['auto_subscribe']; $auto_subscribe = explode (",", $auto_subscribe);

                //only available to active members
                //if (in_array("active", $available_to) && in_array($auto, $auto_subscribe))
                if (in_array($auto, $auto_subscribe))
                    $res[] = $thread_id;

            }
            return count($res);
        }

        function test_autosubscribe($available_to = array(), $auto_subscribe = array()){

            $res = array();
            if (count($available_to) > 0 && count($auto_subscribe) > 0) {
                if (in_array("all", $auto_subscribe) && in_array("guest", $available_to) && count($available_to) == 1)
                    $res[] = "Cannot subscribe 'All members' to 'Guest' subscription";
                if (in_array("aff", $auto_subscribe) && in_array("guest", $available_to) && count($available_to) == 1)
                    $res[] = "Cannot subscribe 'All affiliates' to 'Guest' subscription";

                $product_ids = array();
                foreach (array_unique((array)$auto_subscribe) as $autos){
                    if (preg_match('/^purchase_product-(\d+)$/', $autos, $regs)){
                        $product_ids[] = $regs[1];
                    }
                }
                foreach ($product_ids as $pr_id) {
                    if (!in_array("active_product-".$pr_id, $available_to) && !in_array("active", $available_to)){
                        $p = $this->get_product($pr_id);
                        $res[] = "Product '".$p['title']."' not in 'Available to' list";
                    }
                }

            }
            return $res;

        }

    //////////////////////// MEMBERS NEWSLETTERS ////////////////
        function get_member_threads($member_id){
            global $config;
            settype($member_id, 'integer');
            $res = array();
            $q = $this->query($s = "
                 SELECT nms.thread_id, nt.title
                 FROM {$this->config['prefix']}newsletter_member_subscriptions AS nms
                 LEFT JOIN {$this->config['prefix']}newsletter_thread AS nt
                 ON nms.thread_id = nt.thread_id
                 WHERE nms.member_id = $member_id
                ");
            while ($tr = mysql_fetch_assoc($q)){
                $thread_id = $tr['thread_id'];
                $res[$thread_id] = $tr['title'];
            }
            return $res;
        }

        function add_member_threads($member_id, $threads){
            global $config;
            settype($member_id, 'integer');

            $u = $this->get_user($member_id);
            if (!$u['unsubscribed']){

                $curr_threads = array_keys($this->get_member_threads($member_id));

                if (count($threads) > 0)
                while ( list(, $thread_id) = each ($threads) ) {
                     settype($thread_id, 'integer');

                     if (!in_array($thread_id, $curr_threads)) {
                        $q = $this->query($s = "
                             INSERT INTO {$this->config['prefix']}newsletter_member_subscriptions
                             (member_subscription_id,member_id,thread_id)
                             VALUES (null, $member_id, $thread_id)
                            ");
                     }
                 }
            }

        }

        function delete_member_threads($member_id){
            global $config;
            settype($member_id, 'integer');
           if (!$member_id) fatal_error("member_id empty or 0");
           $this->query("DELETE FROM {$this->config['prefix']}newsletter_member_subscriptions WHERE member_id=$member_id");
           return '';
        }

        function subscribe_member ($member_id = '', $is_affiliate = '0') {
            global $config;
            settype($member_id, 'integer');
            if (!$member_id) fatal_error("member_id empty or 0");

            $threads = array();
            $q = $this->query($s = "
                SELECT thread_id,blob_available_to AS available_to,blob_auto_subscribe AS auto_subscribe
                FROM {$this->config['prefix']}newsletter_thread
                WHERE is_active = 1
               ");
            while ($tr = mysql_fetch_assoc($q)) {
                $available_to = $tr['available_to']; $available_to = explode (",", $available_to);
                if (in_array("active", $available_to)){

                    //only available to active members

                    $auto_subscribe = $tr['auto_subscribe']; $auto_subscribe = explode (",", $auto_subscribe);
                    switch ($is_affiliate) {
                        case '0':
                            //only members
                            if (in_array('all', $auto_subscribe)) $threads[] = $tr['thread_id'];
                            break;
                        case '1':
                            //members & affiliates
                            if (in_array('all', $auto_subscribe)) $threads[] = $tr['thread_id'];
                            if ($config['use_affiliates'] && in_array('aff', $auto_subscribe)) $threads[] = $tr['thread_id'];
                            break;
                        case '2':
                            //only affiliates
                            if ($config['use_affiliates'] && in_array('aff', $auto_subscribe)) $threads[] = $tr['thread_id'];
                            break;
                    }

                }

            }
            $this->add_member_threads($member_id, $threads);
        }

        function subscribe_new_purchase ($member_id = '', $product_id = '') {
            global $config;
            settype($member_id, 'integer');
            settype($product_id, 'integer');
            if (!$member_id) fatal_error("member_id empty or 0");
            if (!$product_id) fatal_error("product_id empty or 0");

            $u = $this->get_user($member_id);
            if (!$u['unsubscribed']){

                $threads = array();
                $q = $this->query($s = "
                    SELECT thread_id,blob_available_to AS available_to,blob_auto_subscribe AS auto_subscribe
                    FROM {$this->config['prefix']}newsletter_thread
                    WHERE is_active = 1
                    ");
                while ($tr = mysql_fetch_assoc($q)) {
                    $available_to   = $tr['available_to'];      $available_to   = explode (",", $available_to);
                    $auto_subscribe = $tr['auto_subscribe'];    $auto_subscribe = explode (",", $auto_subscribe);
                    if ( in_array('purchase_product-'.$product_id, $auto_subscribe) &&
                        (in_array("active", $available_to) || in_array("active_product-".$product_id, $available_to)) )
                        $threads[] = $tr['thread_id'];
                }
                $this->add_member_threads($member_id, $threads);

            }
        }


        function get_members_list($start, $limit, $string='', $threads=array()) {
            if (!is_array($threads)) $threads = array($threads);
            $limit_exp = $this->get_limit_exp($start, $limit);
            $where = "";
            $leftjoin = "";
            if ($string != '') {
                $string = $this->escape($string);
                $where .= " AND (m.name_f like '%$string%' OR m.name_l like '%$string%' OR m.email like '%$string%')";
            }
            if (count($threads) > 0){
                $threads_list = implode (",", $threads);
                $threads_list = $this->escape(trim ($threads_list));
                $leftjoin .= "
                LEFT JOIN {$this->config['prefix']}newsletter_member_subscriptions AS ms
                ON m.member_id = ms.member_id
                ";
                $where .= " AND ms.thread_id IN ($threads_list)";
            }
            $q = $this->query($s = "SELECT m.member_id,m.name_f,m.name_l,m.email,m.unsubscribed
                FROM {$this->config['prefix']}members AS m
                $leftjoin
                WHERE 1
                $where
                ORDER BY m.name_f
                ");//$limit_exp

            $rows = array();
            $counter = 0;
            while ($r = mysql_fetch_assoc($q)){

                $is_available = false;

                $threads_list = $this->get_member_threads ($r['member_id']);
                $threads_list = implode (", ", $threads_list);
                $r['threads'] = $threads_list;

                if (count($threads) > 0) {

                    foreach ($threads as $thread_id){

                        if ($this->is_thread_available_to_member ($thread_id, $r['member_id'])){
                            $is_available = true;
                            break;
                        }
                    }

                } else {
                    $is_available = true;
                }

                if ($is_available && $counter >= $start) $rows[] = $r;
                if ($is_available) $counter++;
                if (count($rows) >= $limit) break; // LIMIT

            }
            return $rows;
        }

        function get_members_list_c($string='', $threads=array()) {
            if (!is_array($threads)) $threads = array($threads);
            $where = "";
            $leftjoin = "";
            if ($string != '') {
                $string = $this->escape($string);
                $where .= " AND (m.name_f like '%$string%' OR m.name_l like '%$string%' OR m.email like '%$string%')";
            }
            if (count($threads) > 0){
                $threads_list = implode (",", $threads);
                $threads_list = $this->escape(trim ($threads_list));
                $leftjoin .= "
                LEFT JOIN {$this->config['prefix']}newsletter_member_subscriptions AS ms
                ON m.member_id = ms.member_id
                ";
                $where .= " AND ms.thread_id IN ($threads_list)";
            }
            $q = $this->query($s = "SELECT m.member_id
                FROM {$this->config['prefix']}members AS m
                $leftjoin
                WHERE 1
                $where
                ");

            $cnt = 0;
            while ($r = mysql_fetch_assoc($q)){
                if (count($threads) > 0) {

                    foreach ($threads as $thread_id){
                        if ($this->is_thread_available_to_member ($thread_id, $r['member_id'])){
                            $cnt++;
                            break;
                        }
                    }

                } else {
                    $cnt++;
                }
            }
            return $cnt;
        }


    //////////////////////// GUESTS NEWSLETTERS ////////////////
        function get_guest_threads($guest_id){
            global $config;
            settype($guest_id, 'integer');
            $res = array();
            $unix_timestamp = time();
            $q = $this->query($s = "
                SELECT nms.thread_id, nt.title
                FROM {$this->config['prefix']}newsletter_guest_subscriptions AS nms
                LEFT JOIN {$this->config['prefix']}newsletter_thread AS nt
                ON nms.thread_id = nt.thread_id
                WHERE nms.guest_id = $guest_id
                AND (nms.security_code = '' OR nms.security_code IS NULL)
                ");

            while ($tr = mysql_fetch_assoc($q)){
                $thread_id = $tr['thread_id'];
                $res[$thread_id] = $tr['title'];
            }
            return $res;
        }

        function is_thread_available_to_guests ($thread_id) {
            global $config;
            $result = false;
            settype($thread_id, 'integer');
            $q = $this->query($s = "
                SELECT blob_available_to AS available_to
                FROM {$this->config['prefix']}newsletter_thread
                WHERE thread_id = $thread_id
            ");

         $tr = mysql_fetch_assoc($q);
            $available_to = $tr['available_to'];
            $available_to = explode (",", $available_to);
            if (in_array("guest", $available_to))
                $result = true;

         return $result;
        }

        function update_member_threads_access($member_id, $user_status){

            global $config;
            settype($member_id, 'integer');
            settype($user_status, 'integer');

            if (!$user_status)
                return;


            $q = $this->query($s = "
                SELECT nms.thread_id, nms.status, nt.is_active, nt.blob_available_to AS available_to
                FROM {$this->config['prefix']}newsletter_member_subscriptions AS nms
                LEFT JOIN {$this->config['prefix']}newsletter_thread AS nt
                ON nt.thread_id = nms.thread_id
                WHERE nms.member_id = '".$member_id."'
            ");

            while ($r = mysql_fetch_assoc($q)){
                $thread_id = $r['thread_id'];
                $result = 0;

                $tr = $r;

                if ($tr['is_active']){

                    $available_to = $tr['available_to'];
                    $available_to = explode (",", $available_to);

                    if ($user_status == 1 && in_array("active", $available_to))
                        $result = 1;
                    if ($user_status == 2 && in_array("expired", $available_to))
                        $result = 1;

                    $dat = date('Y-m-d');
                    $q2 = $this->query("SELECT
                    	product_id,
                    	CASE WHEN SUM(IF(expire_date >= '$dat', 1, 0)) THEN 1
                    		 WHEN SUM(IF(expire_date < '$dat', 1, 0)) THEN 2
            				 ELSE 0
            			END
                    	FROM {$this->config[prefix]}payments
                    	WHERE member_id = '".$member_id."'
                    	AND begin_date <= '$dat'
                    	AND completed > 0
                    	GROUP BY product_id");

                    while (list($product_id, $payment_status) = mysql_fetch_row($q2)){
                        if ($payment_status == 1 && in_array("active_product-".$product_id, $available_to)) // is active
                            $result = 1;
                        if ($payment_status == 2 && in_array("expired_product-".$product_id, $available_to)) // is expired
                            $result = 1;
                    }


                } // endif is thread active

                $q3 = $this->query($s = "
                    UPDATE {$this->config['prefix']}newsletter_member_subscriptions
                    SET status = '$result'
                    WHERE member_id = '$member_id'
                    AND thread_id = '$thread_id'
                ");

            } // end while

        }

        function is_subscription_possible($member_id, $user_status, $thread_id){

            global $config;
            settype($member_id, 'integer');
            settype($user_status, 'integer');
            settype($thread_id, 'integer');

            if (!$user_status) return;


            $q = $this->query($s = "
                SELECT is_active, blob_available_to AS available_to
                FROM {$this->config['prefix']}newsletter_thread
                WHERE thread_id = '".$thread_id."'
            ");

            $result = 0;
            $tr = mysql_fetch_assoc($q);

            if ($tr['is_active']){

                $available_to = $tr['available_to'];
                $available_to = explode (",", $available_to);

                if ($user_status == 1 && in_array("active", $available_to))
                    $result = 1;
                if ($user_status == 2 && in_array("expired", $available_to))
                    $result = 1;

                $dat = date('Y-m-d');
                $q2 = $this->query("SELECT
                	product_id,
                	CASE WHEN SUM(IF(expire_date >= '$dat', 1, 0)) THEN 1
                		 WHEN SUM(IF(expire_date < '$dat', 1, 0)) THEN 2
        				 ELSE 0
        			END
                	FROM {$this->config[prefix]}payments
                	WHERE member_id = '".$member_id."'
                	AND begin_date <= '$dat'
                	AND completed > 0
                	GROUP BY product_id");

                while (list($product_id, $payment_status) = mysql_fetch_row($q2)){
                    if ($payment_status == 1 && in_array("active_product-".$product_id, $available_to)) // is active
                        $result = 1;
                    if ($payment_status == 2 && in_array("expired_product-".$product_id, $available_to)) // is expired
                        $result = 1;
                }


            } // endif is thread active

            return $result;
        }


        function is_thread_available_to_member ($thread_id, $member_id) {
            global $config;
            settype($thread_id, 'integer');
            settype($member_id, 'integer');

            $is_active = $this->query_one($s = "
                SELECT is_active
                FROM {$this->config['prefix']}newsletter_thread
                WHERE thread_id = $thread_id
            ");
            if (!$is_active)
                return false; // thread is inactive - not available

            $status = $this->query_one($s = "
                SELECT status
                FROM {$this->config['prefix']}newsletter_member_subscriptions
                WHERE thread_id = $thread_id
                AND member_id = $member_id
            ");

            if ($status)
                return true;
            else
                return false;

        }

        function add_guest_threads($guest_id, $threads, $security_code='', $securitycode_expire=''){
            global $config;
            settype($guest_id, 'integer');

            if (count($threads) > 0)
            while ( list(, $thread_id) = each ($threads) ) {
                 settype($thread_id, 'integer');
                 if ($this->is_thread_available_to_guests($thread_id))
                    $q = $this->query($s = "
                         INSERT INTO {$this->config['prefix']}newsletter_guest_subscriptions
                         (guest_subscription_id,guest_id,thread_id,security_code,securitycode_expire)
                         VALUES (null, $guest_id, $thread_id,'".$this->escape($security_code)."','".$this->escape($securitycode_expire)."')
                        ");
             }
            return;
        }

        function delete_guest_threads($guest_id){
            global $config;
            settype($guest_id, 'integer');
           //if (!$guest_id) fatal_error("guest_id empty or 0");
           if ($guest_id) $this->query("DELETE FROM {$this->config['prefix']}newsletter_guest_subscriptions WHERE guest_id=$guest_id");
           return '';
        }

        function get_guests_list($start, $limit, $string='', $threads=array()) {
            $limit_exp = $this->get_limit_exp($start, $limit);
            $where = "";
            $leftjoin = "";
            if ($string != '') {
                $string = $this->escape($string);
                $where .= " AND (g.guest_name like '%$string%' OR g.guest_email like '%$string%')";
            } else {
                if (count($threads) > 0) {
                    $threads = implode (",", $threads);
                    $threads = $this->escape(trim ($threads));
                    $leftjoin .= "
                    LEFT JOIN {$this->config['prefix']}newsletter_guest_subscriptions AS gs
                    ON g.guest_id = gs.guest_id
                    ";
                    $where .= " AND gs.thread_id IN ($threads)";
                }
            }
            $unix_timestamp = time();
            $q = $this->query($s = "SELECT g.guest_id,g.guest_name,g.guest_email
                FROM {$this->config['prefix']}newsletter_guest AS g
                $leftjoin
                WHERE 1
                AND (g.security_code = '' OR g.security_code IS NULL)
                $where
                ORDER BY g.guest_name
                $limit_exp
                ");

            $rows = array();
            while ($r = mysql_fetch_assoc($q)){
                if ($r['guest_id']) {
                    $threads = $this->get_guest_threads ($r['guest_id']);
                    $threads = implode (", ", $threads);
                    $r['threads'] = $threads;
                }
                $rows[] = $r;
            }
            return $rows;
        }

        function get_guests_list_c($string='', $threads=array()) {
            $where = "";
            $leftjoin = "";
            if ($string != '') {
                $string = $this->escape($string);
                $where .= " AND (g.guest_name like '%$string%' OR g.guest_email like '%$string%')";
            } else {
                if (count($threads) > 0) {
                    $threads = implode (",", $threads);
                    $threads = $this->escape(trim ($threads));
                    $leftjoin .= "
                    LEFT JOIN {$this->config['prefix']}newsletter_guest_subscriptions AS gs
                    ON g.guest_id = gs.guest_id
                    ";
                    $where .= " AND gs.thread_id IN ($threads)";
                }
            }
            $unix_timestamp = time();
            $q = $this->query($s = "SELECT COUNT(*)
                FROM {$this->config['prefix']}newsletter_guest AS g
                $leftjoin
                WHERE 1
                AND (g.security_code = '' OR g.security_code IS NULL)
                $where
                ");

            $c = mysql_fetch_row($q);
            return $c[0];
        }

        function get_guest($guest_id = '') {
            settype($guest_id, 'integer');
            $q = $this->query($s = "SELECT guest_name,guest_email
                FROM {$this->config['prefix']}newsletter_guest
                WHERE guest_id = '$guest_id'
                ");


          if (! $r = mysql_fetch_assoc($q)){
                $this->log_error("Guest not found: #$guest_id");
             $r = array();
          }
            return $r;
        }

        function get_guest_by_email($email = '') {
            $email = $this->escape($email);
            $q = $this->query($s = "SELECT guest_id,guest_name,guest_email
                FROM {$this->config['prefix']}newsletter_guest
                WHERE guest_email = '$email'
                ");

          if (! $r = mysql_fetch_assoc($q)){
              $r = array();
          }
            return $r;
        }

        function delete_guest($guest_id){
            settype($guest_id, 'integer');
            if (!$guest_id) fatal_error("guest_id empty or 0");
            $this->query("DELETE FROM {$this->config['prefix']}newsletter_guest WHERE guest_id=$guest_id");
            return '';
        }

        function delete_expired_guests(){
            $unix_timestamp = time();
            $q = $this->query($s = "
                SELECT guest_id
                FROM {$this->config[prefix]}newsletter_guest
                WHERE IFNULL(security_code, '') != ''
                AND (UNIX_TIMESTAMP(securitycode_expire) - $unix_timestamp) <= 0
                ");

            while ($r = mysql_fetch_assoc($q)){
                $q2 = $this->query("
                    DELETE FROM {$this->config[prefix]}newsletter_guest_subscriptions
                    WHERE guest_id='$r[guest_id]'
                    ");
                $q2 = $this->query("
                    DELETE FROM {$this->config[prefix]}newsletter_guest
                    WHERE guest_id='$r[guest_id]'
                    ");
            }

        }
        function delete_expired_threads(){
            $unix_timestamp = time();
            $q = $this->query("
                DELETE FROM {$this->config[prefix]}newsletter_guest_subscriptions
                WHERE IFNULL(security_code, '') != ''
                AND (UNIX_TIMESTAMP(securitycode_expire) - $unix_timestamp) <= 0
                ");
        }

    /////////////////////////// NEWSLETTER ARCHIVE FUNCTIONS ///////////////////////////

    function get_archive_list($start, $limit, $thread_id='', $member_id='') {
        $limit_exp = $this->get_limit_exp($start, $limit);
        settype($thread_id, 'integer');
        settype($member_id, 'integer'); // equals -1 if is guest
        $where = "";
        if ($thread_id > 0) {
            $where = " AND threads like '%,$thread_id,%'";
        }
        $q = $this->query($s = "SELECT *
            FROM {$this->config['prefix']}newsletter_archive
            WHERE 1
            $where
            ORDER BY add_date DESC
            ");//$limit_exp

        $rows = array();
        $counter = 0;
        while ($r = mysql_fetch_assoc($q)){
            if ($r['message']) {
                $r['message'] = trim($r['message']);
            }
            $is_available = true;
            if ($r['threads']) {
                $threads = trim ($r['threads'], ",");
                if ($threads != '') $threads = "," . $threads . ",";
                $threads = explode (",", $threads);
                $thread_titles = array();
                while ( list (, $thread_id) = each ($threads) ) {
                    if ($thread_id > 0){
                        $tr = & $this->get_thread($thread_id);
                        $thread_titles[$thread_id] = $tr['thread_title'];

                        if ($member_id > 0 && !$this->is_thread_available_to_member($thread_id, $member_id)){
                            //$this->log_error("Newsletter not available to member: #$r[archive_id]");
                            $is_available = false;
                        }
                        if ($member_id == -1 && !$this->is_thread_available_to_guests($thread_id)){
                            //$this->log_error("Newsletter not available to guest: #$r[archive_id]");
                            $is_available = false;
                        }
                    }
                }
                $r['threads'] = $thread_titles;
            }
            if ($is_available && $counter >= $start) $rows[] = $r;
            if ($is_available) $counter++;
            if (count($rows) >= $limit) break; // LIMIT
        }
        return $rows;
    }

    function get_archive_list_c($thread_id='', $member_id='') {
        settype($thread_id, 'integer');
        settype($member_id, 'integer'); // equals -1 if is guest
        $where = "";
        if ($thread_id > 0) {
            $where = " AND threads like '%,$thread_id,%'";
        }
        $q = $this->query($s = "SELECT archive_id,threads
            FROM {$this->config['prefix']}newsletter_archive
            WHERE 1
            $where
            ");

        $c = 0;
        while ($r = mysql_fetch_assoc($q)){
            $is_available = true;
            if ($r['threads']) {
                $threads = trim ($r['threads'], ",");
                if ($threads != '') $threads = "," . $threads . ",";
                $threads = explode (",", $threads);
                while ( list (, $thread_id) = each ($threads) ) {
                    if ($thread_id > 0){
                        if ($member_id > 0 && !$this->is_thread_available_to_member($thread_id, $member_id)){
                            //$this->log_error("Newsletter not available to member: #$r[archive_id]");
                            $is_available = false;
                        }
                        if ($member_id == -1 && !$this->is_thread_available_to_guests($thread_id)){
                            //$this->log_error("Newsletter not available to guest: #$r[archive_id]");
                            $is_available = false;
                        }
                    }
                }
            }
            if ($is_available) $c++;
        }

        return $c;
    }

    function get_newsletter($archive_id = '', $member_id='') {
        settype($archive_id, 'integer');
        settype($member_id, 'integer'); // equals -1 if is guest
        $q = $this->query($s = "SELECT *
            FROM {$this->config['prefix']}newsletter_archive
            WHERE archive_id = '$archive_id'
            ");

        if (! $r = mysql_fetch_assoc($q)){
            $this->log_error("Newsletter2 not found: #$archive_id");
            $r = array();
        } else {
            if ($r['message']) {
                $r['message'] = trim($r['message']);
            }
            if ($r['threads']) {
                $threads = trim ($r['threads'], ",");
                if ($threads != '') $threads = "," . $threads . ",";
                $threads = explode (",", $threads);
                $thread_titles = array();
                while ( list (, $thread_id) = each ($threads) ) {
                    if ($thread_id > 0){
                        $tr = & $this->get_thread($thread_id);
                        $thread_titles[$thread_id] = $tr['thread_title'];
                        if ($member_id > 0 && !$this->is_thread_available_to_member($thread_id, $member_id)){
                            //$this->log_error("Newsletter not available to member: #$archive_id");
                            return array();
                        }
                        if ($member_id == -1 && !$this->is_thread_available_to_guests($thread_id)){
                            //$this->log_error("Newsletter not available to guest: #$archive_id");
                            return array();
                        }
                    }
                }
                $r['threads'] = $thread_titles;
            }
        }
        return $r;
    }

    function delete_newsletter($archive_id){
        settype($archive_id, 'integer');
        if (!$archive_id) fatal_error("archive_id empty or 0");
        $this->query("DELETE FROM {$this->config['prefix']}newsletter_archive WHERE archive_id=$archive_id");
        return '';
    }

    function delete_old_newsletters(){
        global $config;
        $months = $config['keep_messages_online'];
        settype($months, 'integer');
        if ($months <= 0) {
            $this->config_set('keep_messages_online', 12, 0);
            $months = 12;
        }
        $this->query("DELETE FROM {$this->config['prefix']}newsletter_archive WHERE DATE_ADD(add_date, Interval ".$months." month) <= NOW()");
        return '';
    }


    /////////////////////////// MISC FUNCTIONS ///////////////////////////

    function escape($s){
        // If array was passed, use correct function;
        if(is_array($s)) return $this->escape_array($s);

        // because all additional slashes was stripped
        // Use mysql_real_escape_string if exists (for PHP > 4.3.0);
        if(function_exists("mysql_real_escape_string")){
            return mysql_real_escape_string($s, $this->conn);
        }else{
            return mysql_escape_string($s);
        }
    }
    function query($s, $ignore_error=0){
        if ($this->debug_sql)
            print "<br /><pre>$s</pre>";
        if (defined('AM_SQL_PROFILE')) tmUsage('before_query', false, true);
        if ($res = mysql_query($s, $this->conn)){
            if (defined('AM_SQL_PROFILE')) tmUsage("QUERY:\n<br \>$s\n<br \>", false, true);
            return $res;
        } else {
            if ($ignore_error){
                print "<font color=red>MYSQL ERROR:<br />" . mysql_error($this->conn) .
                "<br />in query:<br />$s</font>";
            } else {
                fatal_error("MYSQL ERROR:<br />" . mysql_error($this->conn) .
                "<br />in query:<br />".$s,1,1);
            }
        }
    }
    function query_one($s, $ignore_error=0){
        $q = $this->query($s, $ignore_error);
        $x = mysql_fetch_row($q);
        return $x[0];
    }
    function query_first($s, $ignore_error=0){
        $q = $this->query($s, $ignore_error);
        $x = mysql_fetch_assoc($q);
        return $x;
    }
    function query_row($s, $ignore_error=0){
        $q = $this->query($s, $ignore_error);
        $x = mysql_fetch_row($q);
        return $x;
    }
    function query_all($s, $ignore_error=0){
        $res = array();
        $q = $this->query($s, $ignore_error);
        while ($a = mysql_fetch_assoc($q)){
            $res[] = $a;
        }
        return $res;
    }
    function lock_tables($tables){
        register_shutdown_function('mysql_unlock_tables');
        return $this->query($s = "LOCK TABLES $tables");
    }
    function unlock_tables(){
        return $this->query("UNLOCK TABLES");
    }
    function dump($q){
        print "<br />QUERY RESULT<br /><table border=1>";
        while ($r = mysql_fetch_row($q)){
            print "<tr>";
            foreach ($r as $k=>$v)
                print "<td>$v</td>";
            print "</tr>\n";
        }
        print "</table>\n";
    }

}

function _amember_get_iconf_d(){
    if ($msg = _amember_get_iconf())
        fatal_error($msg, 0,1);
}

function mysql_unlock_tables(){
    mysql_query("UNLOCK TABLES");
}

class aMemberEmailTemplate {
    var $email_template_id;
    var $name;
    var $lang;
    var $format;
    var $subject;
    var $txt;
    var $plain_txt;
    var $attachments;
    var $product_id;
    var $day;
    function aMemberEmailTemplate($id=null){
    }
    function load($id){
        global $db;
        $id = intval($id);
        $r = $db->query_first("SELECT * FROM {$db->config[prefix]}email_templates WHERE email_template_id='$id'");
        if (!$r) return false;
        foreach ($r as $k=>$v)
            $this->$k = $v;
        return true;
    }
    function save(){
        global $db;
        $v = $db->escape_array($vv=(array)$this);
        $v['day'] = ($v['day'] == '') ? 'NULL' : intval($v['day']);
        $v['product_id'] = ($v['product_id'] == '') ? 'NULL' : intval($v['product_id']);
        if ($this->email_template_id > 0){
            $db->query("UPDATE {$db->config[prefix]}email_templates
            SET name='$v[name]',
            lang='$v[lang]',
            format='$v[format]',
            subject='$v[subject]',
            txt='$v[txt]',
            plain_txt='$v[plain_txt]',
            attachments='$v[attachments]',
            product_id=$v[product_id],
            day=$v[day]
            WHERE email_template_id=$v[email_template_id]");
            return $this->email_template_id;
        } else {
            $db->query($s = "INSERT INTO {$db->config[prefix]}email_templates
            (name,lang,format,subject,txt,plain_txt,attachments, product_id,day)
            VALUES
            ('$v[name]','$v[lang]', '$v[format]', '$v[subject]',
            '$v[txt]', '$v[plain_txt]', '$v[attachments]', $v[product_id], $v[day])");
            return mysql_insert_id();
        }
    }
    function delete_all(){
        global $db;
        $v = $db->escape_array($vv=(array)$this);
        if ($v['product_id'] > 0)
            $where_product_id = " AND product_id = '$v[product_id]' ";
        if ($v['day'] != '')
            $where_day = " AND day = '$v[day]' ";
        if ($v['lang'] != '')
            $where_lang = " AND lang = '$v[lang]' ";
        return $db->query("DELETE
        FROM {$db->config[prefix]}email_templates
        WHERE name='$v[name]'
        $where_lang
        $where_product_id
        $where_day
        ");
    }
    function find_exact(){
        global $db;
        $v = $db->escape_array($vv=(array)$this);
        if ($v['product_id'] > 0)
            $where_product_id = " AND product_id = '$v[product_id]' ";
        if ($v['day'] != '')
            $where_day = " AND day = '$v[day]' ";
        if ($v['lang'] != '')
            $where_lang = " AND lang = '$v[lang]' ";
        $id = $db->query_one($s = "SELECT email_template_id
        FROM {$db->config[prefix]}email_templates
        WHERE name='$v[name]'
        $where_lang
        $where_product_id
        $where_day
        ");
        if ($id){
            return $this->load($id);
        } else
            return false;
    }
    function find_applicable(){
        global $db;
        $v = $db->escape_array($vv=(array)$this);
        if ($v['product_id'] > 0)
            $where_product_id = " AND ((product_id = '$v[product_id]') OR (IFNULL(product_id, 0) = 0))";
        else
            $where_product_id = " AND IFNULL(product_id, 0) = 0 ";
        if ($v['day'] != '')
            $where_day = " AND day = '$v[day]' ";
        else
            $where_day = " AND day is NULL ";
        $def_lang = get_default_lang();
        if ($v['lang'] != '')
            $where_lang = " AND (lang = '$v[lang]' OR lang = '$def_lang')";
        $id = $db->query_one($s = "SELECT email_template_id
        FROM {$db->config[prefix]}email_templates
        WHERE name='$v[name]'
        $where_lang
        $where_product_id
        $where_day
        ORDER BY product_id DESC, lang='$v[lang]' DESC
        ");
        if ($id){
            return $this->load($id);
        } else
            return false;
    }
    function find_languages(){
        global $db;
        $v = $db->escape_array($vv=(array)$this);
        if ($v['product_id'] > 0)
            $where_product_id = " AND product_id = '$v[product_id]' ";
        if ($v['day'] != '')
            $where_day = " AND day = '$v[day]' ";
        $list = $db->query_all("SELECT lang
        FROM {$db->config[prefix]}email_templates
        WHERE name='$v[name]'
        $where_product_id
        $where_day
        ");
        $ret = array();
        foreach ($list as $a){
            $id = $a['lang'];
            $ret[$id] = $id;
        }
        return $ret;
    }
    function find_days(){
        global $db;
        $v = $db->escape_array($vv=(array)$this);
        if ($v['product_id'] > 0)
            $where_product_id = " AND product_id = '$v[product_id]' ";
        else
            $where_product_id = " AND IFNULL(product_id, 0) = 0 ";
        $list = $db->query_all("SELECT DISTINCT day
        FROM {$db->config[prefix]}email_templates
        WHERE name='$v[name]' AND day IS NOT NULL
        $where_product_id
        ORDER by day
        ");
        $ret = array();
        foreach ($list as $a){
            $ret[] = $a['day'];
        }
        return $ret;
    }
    function get_smarty_template(){
        $mail  = "Subject: {$this->subject}\n";
        $mail .= "Format: {$this->format}\n";
        if (($charset=$GLOBALS['_LANG'][$this->lang]['encoding']) != '')
            $mail .= "Charset: {$charset}\n";

        foreach (preg_split("/\n/", trim($this->attachments)) as $a)
            if ($a != '')
                $mail .= "Attachment: $a\n";
        $mail .= $this->txt . "\n";

        if ($this->format == 'multipart'){
            $mail .= "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
            $mail .= $this->plain_txt;
        }
        return $mail;
    }
}


/*
 * $db-> methods transformed to functions at it should be
 */
function admin_log($message, $tablename='', $record_id='', $admin_id=0){
   $ip = $_SERVER['REMOTE_ADDR'];
   foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_PROXY_USER') as $k)
       if ($v=$_SERVER[$k]) {
           $ip .= " ($v)"; break;
       }
   $admin_id = $admin_id ? $admin_id : $_SESSION['amember_admin']['admin_id'];
   global $db;
   $db->query("INSERT INTO {$db->config[prefix]}admin_log
   SET dattm=NOW(),
   admin_id=".intval($admin_id).",ip = '".addslashes($ip).
   "',tablename='".addslashes($tablename).
   "',record_id='".addslashes($record_id).
   "',message='".addslashes($message)."'",
   $admin_id, $ip, $tablename, $record_id, $message);
}

/**
 * Function returns list of states for given country
 * @param string $country
 * @return array List of state codes and assotiated titles
 */
function db_getStatesForCountry($country, $add_empty=false){
    $db = & amDb();
    //if admin show all states, if user show only active states
    $tag = ( isset($_SESSION[amember_admin]) ) ? '' : 'AND tag>=0';

    $res = @$db->selectCol("SELECT state as ARRAY_KEY,
                CASE WHEN tag<0 THEN CONCAT(title, ' (disabled)') ELSE title END
                FROM ?_states WHERE country=? $tag
                ORDER BY tag DESC, title",$country);
    if ($res && $add_empty)
        $res = array_merge(array('' => _TPL_COMMON_SELECT_STATE),$res);
    return $res;
}
function db_getStateByCode($country, $state_code){
    $db = & amDb();
    $res = @$db->selectCell("SELECT title
            FROM ?_states
            WHERE country=? AND state=?",$country, $state_code);
    return $res;
}
function db_getCountryList($add_empty=false){
    $db = & amDb();
    //if admin show all countries, if user show only active countries
    $where = ( isset($_SESSION[amember_admin]) ) ? '' : 'WHERE tag>=0';

    $res = @$db->selectCol("SELECT country as ARRAY_KEY,
            CASE WHEN tag<0 THEN CONCAT(title, ' (disabled)') ELSE title END
            FROM ?_countries $where
            ORDER BY tag DESC, title");
    if ($res && $add_empty)
        $res = array_merge(array('' => _TPL_COMMON_SELECT_COUNTRY),$res);
    return $res;
}
function db_getCountryByCode($country){
    $db = & amDb();
    $res = @$db->selectCol("SELECT title
            FROM ?_countries
            WHERE country=?", $country);
    return $res;
}

function amDb_setLogger(){
    function amDb_logger($db, $sql)
    {
      $caller = $db->findLibraryCaller();
      $tip = "at ".@$caller['file'].' line '.@$caller['line'];
      // ���⠥� ����� (����筮, Debug_HackerConsole ����)
      echo "<xmp title=\"$tip\">"; print_r($sql); echo "</xmp>";
    }
    $db = & amDb();
    $db->setLogger('amDb_logger');
}

unset($GLOBALS['_amember_added_payment_id']);
