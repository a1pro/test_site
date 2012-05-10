<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 


function check_setup_plugin_template(){
    global $plugin_config, $config, $db;
    
    $this_config = $plugin_config['protect']['plugin_template'];
    $nuke_db = $this_config['db'];
    if (!$nuke_db) {
        $error = "Error. Please configure 'plugin_template' plugin at aMember CP -> Setup -> plugin_template";
        if (!$_SESSION['check_setup_plugin_template_error']) $db->log_error ($error);
        $_SESSION['check_setup_plugin_template_error'] = $error;
        return $error;
    }
    return '';
}

if (!check_setup_plugin_template()){
    setup_plugin_hook('subscription_added',   'plugin_template_added');
    setup_plugin_hook('subscription_updated', 'plugin_template_updated');
    setup_plugin_hook('subscription_deleted', 'plugin_template_deleted');
    setup_plugin_hook('subscription_removed', 'plugin_template_removed');
    setup_plugin_hook('subscription_rebuild', 'plugin_template_rebuild');
}

function plugin_template_rebuild(&$members){
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['plugin_template'];
    /// some actions when admin click aMember CP -> Rebuild Db
    /// it should compare all records in your third-party
    /// database with aMember supplied-list ($members)
    /// Or you may just skip this hook
}
    
function plugin_template_added($member_id, $product_id,
    $member){
    $this_config = $plugin_config['protect']['plugin_template'];
    /// It's a most important function - when user subscribed to 
    /// new product (and his subscription status changed to ACTIVE
    /// for this product), this function will be called
    /// In fact, you should add user to database here or update
    /// his record if it is already exists (it is POSSIBLE that
    /// record exists)
}

function plugin_template_updated($member_id, $oldmember,
    $newmember){
    $this_config = $plugin_config['protect']['plugin_template'];
    /// this function will be called when member updates
    /// his profile. If user profile is exists in your 
    /// database, you should update his profile with 
    /// data from $newmember variable. You should use
    /// $oldmember variable to get old user profile - 
    /// it will allow you to find original user record.
    /// Don't forget - login can be changed too! (by admin)
}

function plugin_template_deleted($member_id, $product_id,
    $member){
    $this_config = $plugin_config['protect']['plugin_template'];
    /// This function will be called when user subscriptions
    /// status for $product_id become NOT-ACTIVE. It may happen
    /// if user payment expired, marked as "not-paid" or deleted
    /// by admin
    /// Be careful here - user may have active subscriptions for 
    /// another products and he may be should still in your 
    /// database - check $member['data']['status'] variable
}

function plugin_template_removed($member_id, 
    $member){
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['plugin_template'];
    /// This function will be called when member profile 
    /// deleted from aMember. Your plugin should delete 
    /// user profile from database (if your application allows it!), 
    /// or it should just disable member access if your application
    /// if application doesn't allow profiles deletion
}

?>