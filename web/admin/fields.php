<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: New fields
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5005 $)
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
admin_check_permissions('setup');

$vars = get_input_vars();

function cmp_fields($a, $b){
    return strcmp($a['name'], $b['name']);
}

function get_member_fields(){
    global $member_additional_fields;
    $fl = array();
    foreach ($member_additional_fields as $f){
        if ($f['hidden_anywhere']) continue;
        if (in_array($f['name'], array(
        'cc_city', 'cc_company', 'cc_country', 'cc_name_f', 'cc_name_l', 
        'cc_phone', 'cc_state', 'cc_zip', 'is_locked', 'is_approved',
        'cc_name',
        ))) continue;
        $fl[$f['name']] = $f;
    }
//    usort($fl, 'cmp_fields');
    return $fl;
}

function get_validate_functions(){
    $res = array(
        '' => 'No validation',
        'vf_require' => 'Required value',
        'vf_integer' => "Integer value",
        'vf_number'  => "Numeric value",
        'vf_email' => 'Email value'
    );
    return $res;
}

function get_sql_type_options(){
    $res = array(
        ''              => '** Choose one **',
        'VARCHAR(255)'  => 'String ( VARCHAR(255) )',
        'BLOB'          => 'Blob (unlimited length string/data',
        'INT'           => 'Integer field (only numbers)',
        'DECIMAL(12,2)' => 'Numeric field (DECIMAL(12,2))',
    );
    return $res;
}


function display_add_form($field){
    global $vars, $t, $config, $ff;
    if (!$field['type'])
        $field['type'] = 'text';
    $a = (array)$field['additional_fields'];
    unset($field['additional_fields']);
    $field += $a;

    if (($field['type'] == 'text') || ($field['type'] == 'textarea')){
    } else {
        $s = "";
        foreach ((array)$field['options'] as $k=>$v){
            $s .= "$k|$v";
            $default = is_array($field['default']) ? $field['default'] : array($field['default']);
            if (in_array($k, $default))
                $s .= "|1";
            $s .= "\n";
        }
        $field['values'] = $s;
    }
    $t->assign('f', $field);
    $t->display('admin/field_edit.html');
}

function display_edit_form($field){
    global $vars, $t, $config, $ff;
    
    $a = $field['additional_fields'];
    unset($field['additional_fields']);
    $field += $a;

    if (($field['type'] == 'text') || ($field['type'] == 'textarea')){
    } else {
        $s = "";
        foreach ((array)$field['options'] as $k=>$v){
            $s .= "$k|$v";
            $default = is_array($field['default']) ? $field['default'] : array($field['default']);
            if (in_array($k, $default))
                $s .= "|1";
            $s .= "\n";
        }
        $field['values'] = $s;
    }
    $t->assign('f', $field);
    $t->display('admin/field_edit.html');
}

function get_field_from_form($vars){
    // get default
    if (($vars['type'] == 'text') || ($vars['type'] == 'textarea')){
        $default = $vars['default'];
    } else {
        preg_match_all('/^\s*(.*?)\s*\|\s*(.+?)\s*(|\|(.+?))\s*$/m', $vars['values'], $regs);
        $default = array();
        foreach($regs[1] as $i => $k){
            $value[$k] = $regs[2][$i];
            if ($regs[4][$i] == 1)
                $default[] = $k;
        }
        if ($vars['type'] == 'radio')
            $default = $default[0];
    }
    /// 
    $field = array(
        'name' => strtolower($vars['name']),
        'title' => $vars['title'],
        'type' => $vars['type'],
        'description' => $vars['description'],
        'validate_func' => $vars['validate_func'],
        /// add fields
        'additional_fields' => 
        array(
            'price_group' => array_filter( explode(',', trim($vars['price_group'])) ),
            'sql' => intval($vars['sql']),
            'sql_type' => $vars['sql_type'],
            'size' => $vars['size'],
            'default' => $default,
            'options' => (array)$value,
            'cols' => $vars['cols'],
            'rows' => $vars['rows'],
            'display_signup' => $vars['display_signup'],
            'display_profile' => $vars['display_profile'],
            'display_affiliate_signup' => $vars['display_affiliate_signup'],
            'display_affiliate_profile' => $vars['display_affiliate_profile']
        )
    );
    return $field;
}

function get_field_from_saved($vars){
    // get default
    $field = array(
        'name' => $vars['name'],
        'title' => $vars['title'],
        'type' => $vars['type'],
        'description' => $vars['description'],
        'validate_func' => $vars['validate_func'],
        /// add fields
        'additional_fields' => 
        array(
            'price_group' => implode( ',', (array)$vars['price_group']),
            'sql' => intval($vars['sql']),
            'sql_type' => $vars['sql_type'],
            'size' => $vars['size'],
            'default' => $vars['default'],
            'options' => (array)$vars['options'],
            'cols' => $vars['cols'],
            'rows' => $vars['rows'],
            'display_signup' => $vars['display_signup'],
            'display_profile' => $vars['display_profile'],
            'display_affiliate_signup' => $vars['display_affiliate_signup'],
            'display_affiliate_profile' => $vars['display_affiliate_profile']
        )
    );
    return $field;
}

function validate_edit_form($f, $oldf){
    $err = array();
    if (!preg_match('/.+/', $f['title']))
        $err[] = "Title must be entered";
    if (!preg_match('/.+/', $f['type']))
        $err[] = "Please choose a field type";
    if ($f['sql'] && !$f['sql_type'])
        $err[] = "Please choose a SQL field type";
    return $err;
}

function validate_add_form($f){
    $err = array();
    if (!preg_match('/^[a-z0-9_]+$/', $f['name']))
        $err[] = "Name must be entered and it may contain lowercase letters, underscopes and digits";
    if (!preg_match('/.+/', $f['title']))
        $err[] = "Title must be entered";
    if (!preg_match('/.+/', $f['type']))
        $err[] = "Please choose a field type";
    if ($f['sql'] && !$f['sql_type'])
        $err[] = "Please choose a SQL field type";

    ///
    if (in_array($f['name'], array('login','pass','name_f','name_l','email',
        'street','city','state','zip','country','aff_id', 'status'))){
        $err[] = "Please choose another field name. Name '$f[name]' is already used";
    } else {
        global $member_additional_fields;
        foreach ($member_additional_fields as $x){
            if ($x['name'] == $f['name'])
                $err[] = "Please choose another field name. Name '$f[name]' is already used";
        }
    }
    return $err;
}

function add_sql_field($name, $type){
    global $db;
    $mt = $db->config['prefix'] . 'members';
    $q = $db->query("SELECT * FROM $mt LIMIT 1");
    $i = 0;
    while ($i<mysql_num_fields($q)){
        if ($meta = mysql_fetch_field($q)){
            if (strcasecmp($meta->name, $name) == 0)
                return "Field '$name' is already exists in table $mt";
        } else {
            continue;
        }
        $i++;
    }
    // actually add field
    $db->query($s = "ALTER TABLE $mt ADD $name $type");
    if (mysql_errno()){
        return "Couldn't add field - mysql error: " . mysql_error();
    }
}
function drop_sql_field($name){
    global $db;
    $mt = $db->config['prefix'] . 'members';
    // actually add field
    $db->query($s = "ALTER TABLE $mt DROP $name ");
    if (mysql_errno()){
        return "Couldn't drop field - mysql error: " . mysql_error();
    }
}
function change_sql_field($old_name, $name, $type){
    global $db;
    $mt = $db->config['prefix'] . 'members';
    // actually add field
    $db->query($s = "ALTER TABLE $mt CHANGE $old_name $name $type");
    if (mysql_errno()){
        return "Couldn't change field type - mysql error: " . mysql_error();
    }
}
function convert_field_to_sql($old_name, $name, $type){
    global $db;
    $mt = $db->config['prefix'] . 'members';
    // actually add field
    $err = add_sql_field($name, $type);
    if ($err) return $err;
    ///
    $q = $db->query("SELECT member_id, data FROM $mt");
    while (list($i, $d) = mysql_fetch_row($q)){
        $d = $db->decode_data($d);
        $v = $db->escape($d[$old_name]);
        unset($d[$old_name]);
        $d = $db->escape($db->encode_data($d));
        $db->query($s = "UPDATE $mt
            SET $name='$v', data='$d'
            WHERE member_id = $i
        ");
    }
}
function convert_field_from_sql($old_name, $name){
    global $db;
    $mt = $db->config['prefix'] . 'members';
    ///
    $q = $db->query("SELECT member_id, $old_name, data FROM $mt");
    while (list($i, $v, $d) = mysql_fetch_row($q)){
        $d = $db->decode_data($d);
        $d[$name] = $v;
        $d = $db->escape($db->encode_data($d));
        $db->query($s = "UPDATE $mt
            SET data='$d'
            WHERE member_id = $i
        ");
    }
    $err = drop_sql_field($name, $type);
    if ($err) return $err;
}

function drop_additional_field($name){
    global $db;
    $mt = $db->config['prefix'] . 'members';
    $q = $db->query("SELECT member_id, data 
        FROM $mt");
    while (list($i, $d) = mysql_fetch_row($q)){
        $d = $db->decode_data($d);
        $v = $db->escape($d[$old_name]);
        unset($d[$name]);
        $d = $db->escape($db->encode_data($d));
        $db->query($s = "UPDATE $mt
            SET data='$d'
            WHERE member_id = $i
        ");
    }
}


function add_field($field){
    global $db, $config;
    // handle sql field addition
    if ($field['additional_fields']['sql']){
        if ($err = add_sql_field($field['name'], $field['additional_fields']['sql_type']))
            return array($err);
    }
    $config['member_fields'][] = $field;
    $db->config_set('member_fields', $config['member_fields'], 1);
}

function save_field($field, $old_field){
    if ($old_field['additional_fields']['sql'] != 
        $nt = $field['additional_fields']['sql']){
        // handle change from add to sql and vice-versa
        if ($nt ) {
            $err = convert_field_to_sql($old_field['name'], $field['name'], $field['additional_fields']['sql_type']);
        } else {
            $err = convert_field_from_sql($old_field['name'], $field['name']);
        }
    } elseif ($field['additional_fields']['sql'] && 
        ($old_field['additional_fields']['sql_type'] != 
         $field['additional_fields']['sql_type'])){
        // handle change sql type
        $err = change_sql_field($old_field['name'], $field['name'], $field['additional_fields']['sql_type']);
    }
    if ($err) return array($err);
    
    global $db, $config;
    foreach ($config['member_fields'] as $k=>$v){
        if ($v['name'] == $field['name'])
            $config['member_fields'][$k] = $field;
    }
    $db->config_set('member_fields', $config['member_fields'], 1);
}

function drop_field($field){
    if ($field['sql']){
        $err = drop_sql_field($field['name']);
    } else {
        $err = drop_additional_field($field['name']);
    }
    if ($err) return $err;
    global $db, $config;
    foreach ($config['member_fields'] as $k=>$v){
        if ($v['name'] == $field['name'])
            unset($config['member_fields'][$k]);
    }
    $db->config_set('member_fields', $config['member_fields'], 1);
}

function reorder_cmp($a, $b){
    global $_reorder;
    $av = $_reorder[$a['name']];
    $bv = $_reorder[$b['name']];
    if ($av == $bv) return 0;
    return ($av < $bv) ? -1 : 1;
}


function reorder_fields($o){
    global $db, $config;
    global $_reorder;
    $_reorder = $o;
    usort($config['member_fields'], 'reorder_cmp');
    $db->config_set('member_fields', $config['member_fields'], 1);
}


$ff  = get_member_fields();

$t->assign('validate_functions', get_validate_functions());
$t->assign('sql_type_options', get_sql_type_options());

switch ($vars['action']){
case 'add':
    $field = array();
    if ($vars['save']){
        check_demo();
        $field = get_field_from_form($vars);
        if ($err = validate_add_form($field)){
            $t->assign('error', $err);
        } elseif ($err = add_field($field)){
            $t->assign('error', $err);
        } else {
            admin_log("Additonal Field ($field[name]) inserted");
            admin_html_redirect("fields.php", "Field info added", "Field info added to config");
            break;
        }
    }
    display_add_form($field);
    break;
case 'edit':
    foreach ($ff as $f)
        if ($f['name'] == $vars['name'])
            $old_field = $f;
    $new_field = $old_field = get_field_from_saved($old_field);
    if ($vars['save']){
        check_demo();
        $new_field = get_field_from_form($vars);
        if ($err = validate_edit_form($new_field, $old_field)){
            $t->assign('error', $err);
        } elseif ($err = save_field($new_field, $old_field)){
            $t->assign('error', $err);
        } else {
            admin_log("Additonal Field ($new_field[name]) changed");
            admin_html_redirect("fields.php", "Field info saved", "Field info saved to config");
            break;
        }
    }
    display_edit_form($new_field);
    break;
case 'delete':
    check_demo();
    foreach ($ff as $f)
        if ($f['name'] == $vars['name'])
            $old_field = $f;
    $err = drop_field($old_field);
    if ($err) {
        fatal_error($err, 1);
    } else {
        admin_log("Additonal Field ($old_field[name]) deleted");
        admin_html_redirect("fields.php", "Field has been deleted", "Field has been deleted succesfully");
    }
    break;
case 'reorder':
    reorder_fields($vars['order']);
    admin_html_redirect("fields.php", "Fields order changed", "Field order has been changed");
    break;
default:
    $t->assign('fields', $ff);
    $t->display('admin/fields.html');
}


?>
