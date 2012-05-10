<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'plugin_template';
config_set_notebook_comment($notebook_page, 'plugin_template Integration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
    
add_config_field('protect.plugin_template.cfg_field', 'It is a simple config field',
    'text', "this field will be available as \$this_config['cfg_field'] in 
    the plugin code",
    $notebook_page
    );

add_config_field('protect.plugin_template.db', 'plugin_template Db and Tablename',
    'text', "AN EXAMPLE OF COMPLEX FIELD WITH VALIDATION<br />
    Database name (if other database) plus tables prefix<br />
    , like <i>plugin_template.invisionboard_</i><br />
    here <i>plugin_template</i> is a database name,<br />
    and tables prefix is <i>invisionboard_</i><br />
    ",
    $notebook_page, 
    'validate_plugin_template_db');

function validate_plugin_template_db($field,$vars){
    global $db;
    $v = $vars[$field['name']];
    $v = $db->escape($v);
    mysql_query("SELECT username FROM {$v} LIMIT 1");    
    if (mysql_errno()) 
        return "$field[title] - incorrect value. Error: " . mysql_error();
}


?>
