<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Products
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4593 $)
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

$vars = get_input_vars();
extract($vars, EXTR_OVERWRITE);

function display_products_list(){
    global $db,$t;
    $pl = $db->get_products_list();
    $t->assign(pl, $pl);
    $t->display("admin/products.html");
}

function display_reorder_list(){
    global $db,$t;
    $pl = $db->get_products_list();
    $t->assign(pl, $pl);
    $t->display("admin/products_order.html");
}

function save_reorder_list(){
    global $db,$t;
    $vars = get_input_vars();
    $arr = array();
    foreach ($vars as $k=>$v)
        if (preg_match('/^(\w+)-(\d+)$/', $k, $regs))
            $arr[$regs[2]][$regs[1]] = $v;
    foreach ($arr as $product_id=>$v){
        $pr = $db->get_product($product_id);
        if (!$pr['product_id']) continue;
        $pr['order'] = $v['order'];
        $pr['price_group'] = $v['price_group'];
        $pr['renewal_group'] = $v['renewal_group'];
        $db->update_product($product_id, $pr);
    }        
    ////
    $pl = $db->get_products_list();
    $t->assign(pl, $pl);
    $t->display("admin/products_order.html");
}

function edit_product($error=array()){
    global $product_id;
    global $t, $db, $vars;
    global $product_additional_fields;
    $t->assign('edit', 1);
    $t->assign('error', $error);
    $p = $db->get_product($product_id);
    if (!$p) {
        fatal_error("Cannot open product #$product_id");
    }
    
    if ($p['terms'] == ''){
    	$pr = & new product($p);
    	$p['terms_default'] = $pr->getSubscriptionTerms();
    }    	
    unconvert_period_fields($p);
    
    $t->assign('p', $vars['action'] == 'edit_save' ? $vars : $p);
    
    $t->assign('product_additional_fields', $product_additional_fields);
    $t->assign('send_signup_mail_tpl', is_product_template_exists('send_signup_mail', $p['product_id'])); 
    $t->assign('mail_autoresponder_field', product_email_days_get('mail_autoresponder', $p['product_id']));
    $t->assign('mail_not_completed_field', product_email_days_get('mail_not_completed', $p['product_id']));
    $t->assign('mail_expire_field', product_email_days_get('mail_expire', $p['product_id'])); 
    $t->assign('dont_mail_expire_options', array(
        '' => 'Use default setting (aMember CP->Setup->E-Mail)',
        1  => 'Do not email expiration notices for this product (regardless of global setting)',
        2  => 'Always email expiration notices for this product (regardless of global setting)',
    ));
    
    $t->display('admin/product.html');
}

function is_product_template_exists($tpl, $product_id){
    global $db;
    $et = & new aMemberEmailTemplate();
    $et->name = $tpl;
    $et->product_id = $product_id;
    return $et->find_exact() ? 1 : 0;
}
function product_email_days_get($tpl, $product_id){
    global $db;
    $field = array();
    $field['name'] = $fname = $tpl;

    $et = & new aMemberEmailTemplate();
    $et->name = $tpl;
    $et->product_id = $product_id;
    foreach ($et->find_days() as $day){
        $edit_link = "email_templates.php?a=edit&tpl=$field[name]&product_id=$product_id&day=$day";
        $del_link  = "email_templates.php?a=del&tpl=$field[name]&product_id=$product_id&day=$day";
        $text_input .= "
        <input type='text' size=3 class='small' value='$day' disabled />
        - <a href='$edit_link'>Edit E-Mail Template</a> / <a href='$del_link' onclick='return confirm(\"Are you sure?\")'>Delete</a><br />";
    }
    $text_input .= <<<CUT
    <input type="text" name='{$fname}_days_add' size=3 class="small" /> 
    <input type="button" onclick="window.location='email_templates.php?a=add&tpl=$field[name]&product_id=$product_id&day='+this.form.{$fname}_days_add.value" value="Add E-Mail Template" />
CUT;
    return "
        <a name='{$fname}'></a>
        $text_input
     ";
    
}

function add_product($error=array()){
    global $product_id, $db;
    global $t, $db, $vars;
    global $product_additional_fields;

    if ($vars['action'] != 'add_save'){
        if ($vars['copy_product_id']){
            $oldp = $db->get_product($vars['copy_product_id']);
            unset($oldp['product_id']);
            unset($oldp['price_group']);
            foreach ($oldp as $k=>$v)
                $vars[$k] = $v;
        }
        
        $next_id = $db->query_one("SELECT 1+MAX(product_id)
            FROM {$db->config[prefix]}products
            ");
        $vars['renewal_group'] = $next_id . " (keep default if not sure)";
    }
    
    if ($p['terms'] == ''){
    	$pr = & new product($vars);
    	$p['terms_default'] = $pr->getSubscriptionTerms();
    }    	
    unconvert_period_fields($p);
    
    $t->assign('add', 1);
    $t->assign('error', $error);
    $t->assign('p', $vars);
    $t->assign('product_additional_fields', $product_additional_fields);
    $t->display('admin/product.html');
}

function unconvert_period_fields(&$p){
    global $product_additional_fields;
    foreach ($product_additional_fields as $f){
        if ($f['type'] == 'period'){
            $v = $p[ $f['name'] ];
            list($c,$u) = parse_period($p[ $f['name'] ]);
            unset($p[$f['name']]);
            if (($u == 'fixed') && ($c == MAX_SQL_DATE)){
                $p[ $f['name'] ]['count'] = '';
                $p[ $f['name'] ]['unit'] = 'lifetime';
            } else {
                $p[ $f['name'] ]['count'] = $c;
                $p[ $f['name'] ]['unit'] = $u;
            }                
        }            
    }
}

function convert_period_fields(&$p){
    global $product_additional_fields;
    foreach ($product_additional_fields as $f){
        if ($f['type'] == 'period'){
            $fname = $f['name'];
            $farr  = $p->config[$fname];
            if ($farr['unit'] == 'lifetime'){
                $farr['count'] = MAX_SQL_DATE;
                $farr['unit'] = '';
            } elseif ($farr['unit'] == 'fixed') {
                $farr['unit'] = '';
            } elseif ($farr['count'] == '') {
                $p->config[$fname] = "";
                continue;
            }
            $p->config[$fname] = $farr['count'] . $farr['unit'];
        }            
    }
}

function validate_product_fields(&$p){
    global $product_additional_fields;
    $error = array();
    foreach ($product_additional_fields as $f){
        if ($f['validate_func'])
            foreach ((array)$f['validate_func'] as $func){
                if (!$func) 
                    continue;
                if (!function_exists($func))
                    $error[] = "Validation function '$func' for field: '$f[name]' not defined. Internal error";
                if ($err = $func($p, $f['name'], $f))
                    $error[] = "Cannot update product: $err";
            }
    }
    return $error;
}

function edit_save(){
    global $db, $t, $vars;

    $p = new product($vars);
    convert_period_fields($p);
    $error = validate_product_fields($p);
    if ($error) {
        edit_product($error);
        return;
    }
    //print_rr($p);
    $err = $db->update_product($vars['product_id'], $p->config);
    if ($err) 
        fatal_error("Cannot update product info: $err", false);
    admin_log("Product updated $vars[product_id]");
    $t->assign('url', "products.php");
    $t->display("admin/product_saved.html");
}

function add_save(){
    global $db, $t;
    $vars = get_input_vars();

    $vars['renewal_group'] = 
        preg_replace('|\(keep default if not sure\)|', '', 
        $vars['renewal_group']);
    
    $p = new product($vars);
    convert_period_fields($p);
    $error = validate_product_fields($p);
    if ($error) {
        add_product($error);
        return false;
    }
    $product_id = $db->add_product($p->config);
    if (!$product_id) {
        $error = "Cannot insert product. INTERNAL ERROR";
    }
    admin_log("Product added $product_id");
    $t->assign('url', "products.php");
    $t->display("admin/product_saved.html");
}

function display_confirm($title, $message){
    global $t, $vars;
    $t->assign('title', $title);
    $t->assign('message', $message);
    $t->assign('vars', $vars);
    $t->display('admin/confirm.html');
    exit();
}


function delete(){
    global $product_id, $vars;
    global $db, $t;
    
    $pr = $db->get_product($product_id);

    $c_act = $db->users_find_by_product_c($product_id, $include_expired=0);
    $c_all  =$db->users_find_by_product_c($product_id, $include_expired=1); 
    $c_exp = $c_all - $c_act;
    $c_exp = $c_exp ? "($c_exp expired)" : "";

    if (($vars['confirm'] != '') && ($vars['confirm'] != 'Yes')){
        header("Location: products.php");
        exit();
    }
    
    if ($c_all && ($vars['confirm'] != 'Yes')){
        display_confirm("Really delete a product?",
        "There are $c_all user(s) $c_exp having subscription
        to this product. It is <b><font color=red>VERY DANGEROUS</font></b> to delete product if
        someone subscribed.
        <br />
        Please delete these customers or re-subscribe them to another product using 
        <a href='mass_subscribe.php'>Mass Subscribe</a> function.<br />       
        <br />
        As immediate workaround, you may hide this product using 'Scope' field<br />
        <br />
        If you anyway want to delete product [$pr[title]], you may click <font color=red>YES</font>,
        please keep in mind that <font color=red>RESTORE WILL BE IMPOSSIBLE</font>.
        ");
        exit();
    }        
        
    if ($vars['confirm'] == 'Yes' || !$c_all){
        $err = $db->delete_product($product_id, $c_all>0);
        if ($err) {
            fatal_error("Cannot delete product: $err");
        }
        admin_log("Product deleted $vars[product_id]");
        $t->assign('msg', 'Product deleted');
        $t->assign('url', "products.php");
        $t->display("admin/product_saved.html");
    }        
}

////////////////////////////////////////////////////////////////////////////
//
//                      M A I N
//
////////////////////////////////////////////////////////////////////////////

$vars = get_input_vars();
if ($vars['product_id'] != "")
    $vars['product_id'] = intval($vars['product_id']);
    
admin_check_permissions('products');
$error = array();
$t->assign('period_options', array(
    'd' => 'Days',
    'm' => 'Months',
    'y' => 'Years',
    'lifetime' => 'Lifetime subscription',
    'fixed' => 'Exact date (yyyy-mm-dd)',
));
$t->assign('trial_period_options', array(
    'd' => 'Days',
    'm' => 'Months',
    'y' => 'Years',
));
switch ($vars['action']){
    case 'edit':
        edit_product();
        break;
    case 'edit_save':
        check_demo();
        edit_save();
        break;
    case 'add':
        add_product();
        break;
    case 'add_save':
        check_demo();
        add_save();
        break;
    case 'delete':
        check_demo();
        delete();
        break;
    case 'reorder':
        display_reorder_list();
        break;
    case 'reorder_save':
        save_reorder_list();
        break;
    case 'browse': case '': 
        display_products_list();
        break;
    default: 
        fatal_error("Unknown action: '$action'");
}


?>
