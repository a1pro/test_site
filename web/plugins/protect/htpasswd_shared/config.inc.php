<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'htpasswd_shared';
config_set_notebook_comment($notebook_page, '');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('protect.htpasswd_shared.htpasswd', 'Add another .htpasswd',
    'text', "add content of another .htpasswd file to generated file",
    $notebook_page, 'validate_htpasswd_shared'
);
$opt = array(); 
global $db;
foreach ($db->get_products_list() as $pr)
    $opt[$pr['product_id']] = $pr['title'];

add_config_field('protect.htpasswd_shared.products', 'Products to add',
    'multi_select', "if user is subscribed to one from these products,<br />
    he will be added to .htpasswd, else he will be ignored<br />
    If no products selected, then any ACTIVE subscription will<br />
    cause user addition to .htpasswd file.<br />
    ",
    $notebook_page, '', '', '',
    array('options' => $opt, 'store_type' => 1)
);

function validate_htpasswd_shared($field,$vars){
    global $db;
    $v = $vars[$field['name']];
    if ($v == ''){
        return "You must specify filename for .htpasswd file. Or just disable this plugin";
    }
    if (!file_exists($v))
        return "File is not exists - $v. Please create an empty file in this location and chmod it 666, then press Save button again";
    if (!is_writeable($v))
        return "File is not writeable. Please chmod file to 666, then press Save button again";
}



?>
