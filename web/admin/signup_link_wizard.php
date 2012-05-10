<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Products
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2289 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/
require "../config.inc.php";
$t = new_smarty();
require "login.inc.php";

$t = new_smarty();

$price_groups = $products_list;
foreach ($db->get_products_list() as $p){
    $products_list[$p['product_id']] = $p['title'];
    if ($p['price_group'] != '')
        $price_groups[ $p['price_group']] = $p['price_group'];
}
$t->assign('price_groups', $price_groups);
$t->assign('products_list', $products_list);

$paysys_list = array();
foreach (get_paysystems_list() as $p){
    if ($p['paysys_id'] != 'manual') 
        $paysys_list[ $p['paysys_id'] ] = $p['title'];
}
$t->assign('paysys_list', $paysys_list);

$t->display('admin/signup_link_wizard.html');
