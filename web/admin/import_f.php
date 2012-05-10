<?php                   
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Payments
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1917 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

include "../config.inc.php";

$t = new_smarty();
$t->assign('import_f', 1);
include "login.inc.php";
@set_time_limit(3600);

check_lite();
admin_check_permissions('import');

function display_form(){
    global $t, $db;
    $fields_list = array();
    for ($i=0;$i<16;$i++)
        $fields_list[ 'FIELD-' . $i ] = "Field " . ($i+1);
    $t->assign('fields', $fields_list);
    $t->assign('fields_gen', array('GENERATE' => 'Generate') + $fields_list);
    $t->assign('fields_gen_fixed', array('GENERATE' => 'Generate', 'FIXED' => 'Fixed') + $fields_list);
    $t->assign('fields_emp', array('' => '-- Please select --') + $fields_list);
    $products = array();
    foreach ($db->get_products_list() as $p)
        $products[ $p['product_id'] ] = $p['title'];
    $t->assign('fields_prod', array("" => "-- Please select --", "EMPTY" => "Don't add subscription") + $products + $fields_list);
    $t->assign('fields_emp_fixed', array('' => '-- Please select --', 'FIXED' => 'Fixed - please enter') + $fields_list);

    $ps_list = array();
    foreach (get_paysystems_list() as $k => $ps)
        $ps_list[ $ps['paysys_id'] ] = $ps['title'];
    $t->assign('fields_ps', array('' => '-- Please select --') + $ps_list + $fields_list);
    
    global $member_additional_fields, $input_fields;
    $t->assign('member_additional_fields',$member_additional_fields);
    $t->display('admin/import.html');
}

function check_form(){
    global $t, $vars;
    $err = array();
    if (!$vars['login']) 
        $err[] = "Please select field where login stored in your file";
    if (!$vars['pass']){
        $err[] = 'Please select something for pass';
    } elseif (($vars['pass'] == 'FIXED') && (!$vars['pass_fixed'])) {
        $err[] = "Please enter fixed password";
    }
    if (!$vars['product_id']){
        $err[] = "Please select something for subscription type";
    }
    if (($vars['product_id'] != 'EMPTY') && !$vars['expire_date']){
        $err[] = "Please select something for expire date";
    }
    if (($vars['begin_date'] == 'FIXED') && !$vars['begin_date_fixed']){
        $err[] = "Please enter fixed begin date";
    }
    if (($vars['expire_date'] == 'FIXED') && !$vars['expire_date_fixed']){
        $err[] = "Please enter fixed expired date";
    }
    if (!preg_match('/^(|\d\d\d\d\-\d\d\-\d\d)$/', $vars['expire_date_fixed'])){
        $err[] = "Incorrect expire date format. Use yyyy-mm-dd";
    }
    if (!preg_match('/^(|\d\d\d\d\-\d\d\-\d\d)$/', $vars['begin_date_fixed'])){
        $err[] = "Incorrect begin date format. Use yyyy-mm-dd";
    }
    if ($err) {
        $t->assign('error', $err);
        return 0;
    }
    return 1;
}

$input_fields = array(
    'login', 'pass', 'email', 
    'name_f', 'name_l',
    'street', 'city',
    'state', 'zip', 
    'country', 'is_male',
    'product_id', 'expire_date', 'begin_date',
    'paysys_id', 'receipt_id',
);
foreach ($member_additional_fields as $f){
    if ($f['type'] != 'hidden')
        $input_fields[] = $f['name'];        
}

function get_fields($vars){
    //get vars, return $fields
    global $input_fields;
    $fields = array();
    $max_num = 0;
    foreach ($input_fields as $k){
        if (preg_match('/^FIELD-(\d+)$/', $vars[$k], $regs)){
            $num = intval($regs[1]);
            if (isset($fields[$num])){
                $warn[] = "Field $num selected twice: for <i>$k</i> and for <i>" . $fields[$num] . '</i>. Please return back and FIX IT';
            }
            $fields[$num] = $k;
            if ($num > $max_num) $max_num = $num;
        }
    }
    for ($i=0;$i<=$max_num;$i++){
        if (!isset($fields[$i])) $fields[$i] = 'SKIPPED';
    }
    ksort($fields);
    return $fields;
}
function get_rev_fields($vars){
    //get vars, return $rev_fields
    global $input_fields;
    $fields = array();
    $max_num = 0;
    foreach ($input_fields as $k){
        if (preg_match('/^FIELD-(\d+)$/', $vars[$k], $regs)){
            $fields[$k] = $regs[1];
        }
    }
    return $fields;
}

function display_upload_form(){
    global $t, $vars, $db;

    $fields  = get_fields($vars);

    $format = join($vars['delim'], $fields);
    $t->assign('format', $format);
    $t->assign('warn', $warn);

    $str = '';
    foreach ($vars as $k=>$v){
        if ($k != 'action'){
            $str .= "<input type=hidden name=\"$k\" value=\"$v\">\n";
        }
    }
    $t->assign('hidden', $str);

    $t->display('admin/import_format.html');
}

function convert_date($d){
    if ($d > 1000000) /// timestamp
        return date('Y-m-d', $rec['expire_date']);
    else if (preg_match('/^\d{5}$/', $d)) //from mc_pro
        return date('Y-m-d', $d * 3600 * 24 + mktime(0,0,0,1,1,1970));
    else  // assume mysql yyyy-mm-dd
        return date('Y-m-d', strtotime($d));
}

function get_visible_cc_number($cc){
    $cc = preg_replace('/\D+/', '', $cc);
    return '**** **** **** '.substr($cc, -4);
}

function format_cc_expire($s){
    $s = preg_replace('/\D+/', '', $s);
    switch (strlen($s)){
        case 4: // mmyy 
            return $s;
        case 6: // mmyyyy
            return substr($s, 0, 2) . substr($s, 4, 2);
        default: return $s;
    }
}


function line_to_record($l){
    global $__fields, $vars;
    if ($__fields)
        $fields = $__fields;
    else {
        $fields = get_rev_fields($vars);
        $__fields = $fields;
    }
    $rec = array();
    foreach ($fields as $fn => $nn)
        $rec[$fn] = trim($l[$nn]);
    if ($vars['login'] == 'GENERATE')
        $rec['login'] = generate_login();
    if ($vars['pass'] == 'FIXED')
        $rec['pass'] = $vars['pass_fixed'];
    if ($vars['pass'] == 'GENERATE')
        $rec['pass'] = generate_password();
    if ($rec['cc']){
        $cc = preg_replace('/\D+/', '', $rec['cc']);
        $rec['cc-hidden'] = amember_crypt($cc);
        $rec['cc'] = get_visible_cc_number($cc);
        $cc='';
    }
    if ($rec['cc-expire']){
        $rec['cc-expire'] = format_cc_expire($rec['cc-expire']);
    }
    if ($vars['product_id'] != 'EMPTY'){
        if (intval($vars['product_id'])) 
            $rec['product_id'] = $vars['product_id'];
        if ($vars['expire_date'] == 'FIXED')
            $rec['expire_date'] = $vars['expire_date_fixed'];
        if ($vars['begin_date'] == 'FIXED')
            $rec['begin_date'] = $vars['begin_date_fixed'];
        if (!preg_match('/^FIELD-/', $vars['paysys_id']))
            $rec['paysys_id'] = $vars['paysys_id'];
        if ($vars['receipt_id'] == 'FIXED')
            $rec['receipt_id'] = $vars['receipt_id_fixed'];
        $rec['is_completed'] = intval($vars['is_completed']);
    }
    $rec['begin_date'] = convert_date($rec['begin_date']);
    $rec['expire_date'] = convert_date($rec['expire_date']);

    return $rec; 
}

function get_first_lines($count){
    global $import_filename, $vars;
    $f = fopen($import_filename, 'r');
    if (!$f) die("Cannot open file '$import_filename' for import");
    $res = array();
    while (!feof($f)){
        $s = fgetcsv($f, 8049, $vars['delim']);
        $res[] = line_to_record($s);
        if (count($res) >= $count) 
            break;
    }
    fclose($f);
    return $res;
}

function display_confirm($lines){ 
    global $t, $db;
    global $file_content;
    $products = $db->get_products_list();
    foreach ($products as $k=>$v)
        $pp[$v['product_id']] = $v;
    foreach ($lines as $k=>$v){
        $lines[$k]['product'] = $pp [ $v['product_id']  ]['title'];
    }
    $t->assign('file_content', $lines);
    $t->assign('member_additional_fields', $GLOBALS['member_additional_fields']);
    $t->display('admin/import_confirm.html');
}

function do_import(){
    global $t, $db, $config;
    global $vars, $import_filename, $total_added;

    $config['send_signup_mail'] = 0;
    global $__hooks;
    $__hooks = array(); // disable all things
    
    $f = fopen($import_filename, 'r');
    if (!$f) die("Cannot open file '$import_filename' for import");
    $total_added = 0;
    while (!feof($f)){
        $s = fgetcsv($f, 8049, $vars['delim']);
        $r = line_to_record($s);
        
        if (!$db->check_uniq_login($r['login'])){
            print "Duplicate login: $r[login]. This record won't be imported<br />";
            continue;
        }
        $r['pass0'] = $r['pass'];
        $member_id = $db->add_pending_user($r);
        if ($r['product_id']){
            $pm = array(
                'product_id'  => $r['product_id'],
                'begin_date'  => $r['begin_date'],
                'expire_date' => $r['expire_date'],
                'paysys_id'   => $r['paysys_id'],
                'amount'      => 0,
                'receipt_id'  => $r['receipt_id'],
                'member_id'   => $member_id,
                'completed'   => $r['is_completed']
            );
            $db->add_payment($pm);
        }
        $total_added++;
    }
    fclose($f);
}

/////////////// MAIN //////////////////////
$import_filename = $config['root_dir'] . "/admin/imp.csv";


$vars = get_input_vars();
switch ($vars['action']){
    case 'do_import':
        admin_html_redirect("import_f.php?action=do_real_import", 
            $title='Import...', 
            $text='Import processing may take some time, please be patient');
        break;
    case 'do_real_import':
        $vars = $_SESSION['import_vars'];
        check_demo();
        do_import();
        print "<br /><strong>Import finsihed. $total_added records added</strong><br />
        Please don't forget to remove import file amember/admin/imp.csv";
        break;
    case 'upload':
        check_demo();
        if ($lines = get_first_lines(20)){
            display_confirm($lines);
            $_SESSION['import_vars'] = $vars;
            break;
        }
    case 'check_form':
        check_demo();
        if (!file_exists($import_filename)){
            fatal_error("Please upload CSV file for import: /amember/admin/imp.csv. Please upload file and press 'Refresh' button (F5)", 0);
        }
        if (check_form()){
            display_upload_form();
            break;
        } 
    default: display_form();
}



?>
