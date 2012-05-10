<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: List of possible admin permissions
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1640 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

function get_admin_permissions_list(){
    return array(
        'browse_users' => 'Can browse user profiles',
        'add_users'    => 'Can add users',
        'edit_users'   => 'Can edit users',
        'delete_users' => 'Can delete users',
        'list_payments' => 'Can view list of payments/subscriptions',
        'manage_payments' => 'Can view/manage user payments/subscriptions',
        'setup' => 'Can change configuration settings',
        'products' => 'Can manage products',
        'protect_folders' => 'Can protect folders',
        'backup_restore' => 'Can download backup / restore from backup',
        'report' => 'Review reports',
        'import' => 'Can run import',
        'export' => 'Can run export',
        'email' => 'Can send e-mail messages',
        'manage_coupons' => 'Can manage coupons',
        'affiliates' => 'Can see affiliate info/make payouts'
    );
}

?>