<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Affiliate commission
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2029 $)
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

admin_check_permissions('affiliates');

$vars = get_input_vars();

function display_banners(){
    global $db,  $t, $config;
    /////
    $links = array();
    foreach ((array)$config['aff']['links'] as $i=>$l){
        $l['url'] = aff_make_url($l['url'], 'l' . $i, -1);
        $l['code'] = "<a href=\"$l[url]\">$l[title]</a>";
        $links[$i] = $l;
    }
    $t->assign('links', $links);
    /////////
    foreach ((array)$config['aff']['banners'] as $i=>$l){
        $l['url'] = aff_make_url($l['url'], 'b'. $i, -1);
        $alt = htmlspecialchars($l['alt']);
        $wc = ($w=$l['width'])  ? "width=$w" : "";
        $hc = ($h=$l['height']) ? "height=$h" : "";
        $l['code'] = "<a href=\"$l[url]\"><img src=\"$l[image_url]\" border=0 alt=\"$alt\" $wc $hc></a>";        
        $banners[$i] = $l;
    }
    $t->assign('banners', $banners);
    $t->display("admin/aff_banners.html");
}

function display_banner_form($b, $err=array()){
    global $t;
    $t->assign('b', $b);
    $t->assign('errors', $err);
    $t->display('admin/aff_banners_banner.html'); 
}

function validate_banner_form($b){
    $err = array();
    if ($b['url'] == '') {
        $err[] = "Please enter URL";
    } 
    if ($b['alt'] == '') $err[] = "Please enter Alt. Text";

    if ($b['image_url'] == '') {
        $err[] = "Please enter Image URL";
    } 
    return $err;
}

function add_banner($b){
    global $config, $db;
    $err = array();
    unset($b['save']);
    unset($b['banner_id']);
    $config['aff']['banners'][] = $b;
    $db->config_set('aff.banners', $config['aff']['banners'], 1);
    return $err;
}

function edit_banner($b, $k){
    global $config, $db;
    $err = array();
    unset($b['save']);
    unset($b['banner_id']);
    $config['aff']['banners'][$k] = $b;
    $db->config_set('aff.banners', $config['aff']['banners'], 1);
    return $err;
}

function del_banner($k){
    global $config, $db;
    $err = array();
    unset($config['aff']['banners'][$k]);
    $db->config_set('aff.banners', $config['aff']['banners'], 1);
    return $err;
}

function display_link_form($b, $err=array()){
    global $t;
    $t->assign('b', $b);
    $t->assign('errors', $err);
    $t->display('admin/aff_banners_link.html'); 
}

function validate_link_form($b){
    $err = array();
    if ($b['url'] == '') {
        $err[] = "Please enter URL";
    } 
    if ($b['title'] == '') $err[] = "Please enter Link Title";
    
    return $err;
}

function add_link($b){
    global $config, $db;
    $err = array();
    unset($b['save']);
    unset($b['banner_id']);
    $config['aff']['links'][] = $b;
    $db->config_set('aff.links', $config['aff']['links'], 1);
    return $err;
}

function edit_link($b, $k){
    global $config, $db;
    $err = array();
    unset($b['save']);
    unset($b['banner_id']);
    $config['aff']['links'][$k] = $b;
    $db->config_set('aff.links', $config['aff']['links'], 1);
    return $err;
}

function del_link($k){
    global $config, $db;
    $err = array();
    unset($config['aff']['links'][$k]);
    $db->config_set('aff.links', $config['aff']['links'], 1);
    return $err;
}

switch ($vars['action']){
// banners    
    case 'add_banner':
        if ($vars['save']){
            $err = validate_banner_form($vars);
            if (!$err) {
                $err = add_banner($vars);
                if (!$err){
                    display_banners();
                    break;
                }
            }
        }
        display_banner_form($vars, $err);
        break;
    case 'edit_banner':
        if ($vars['save']){
            $err = validate_banner_form($vars);
            if (!$err) {
                $err = edit_banner($vars, $vars['banner_id']);
                if (!$err){
                    display_banners();
                    break;
                } else {
                    $b = $vars;
                }
            } else {
                $b = $vars;
            }
        } else {
            $b = $config['aff']['banners'][$vars['banner_id']];
        }
        display_banner_form($b, $err);
        break;
    case 'del_banner':
        del_banner($vars['banner_id']);
        display_banners();
        break;
/// links        
    case 'add_link':
        if ($vars['save']){
            $err = validate_link_form($vars);
            if (!$err) {
                $err = add_link($vars);
                if (!$err){
                    display_banners();
                    break;
                }
            }
        }
        display_link_form($vars, $err);
        break;
    case 'edit_link':
        if ($vars['save']){
            $err = validate_link_form($vars);
            if (!$err) {
                $err = edit_link($vars, $vars['banner_id']);
                if (!$err){
                    display_banners();
                    break;
                } else {
                    $b = $vars;
                }
            } else {
                $b = $vars;
            }
        } else {
            $b = $config['aff']['links'][$vars['banner_id']];
        }
        display_link_form($b, $err);
        break;
    case 'del_link':
        del_link($vars['banner_id']);
        display_banners();
        break;
    default: 
        display_banners();
     
}

?>