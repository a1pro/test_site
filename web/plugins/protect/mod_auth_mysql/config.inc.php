<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'mod_auth_mysql';
config_set_notebook_comment($notebook_page, 'mod_auth_mysql Integration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
    
add_config_field('protect.mod_auth_mysql.db', 'mod_auth_mysql Db and Tablename',
    function_exists('amDb') ? 'dbprefix' : 'text', "database name (if other database) plus mod_auth_mysql table<br />
    name, like <i>mod_auth_mysql.users</i><br />
    here <i>mod_auth_mysql</i> is database, and tables with users named as <i>users</i><br />
    Please backup your table FIRST! aMember will manage it exclusively and<br />
    if user is not present in aMember database, it will be removed from<br />
    mod_auth_mysql table.
    ",
    $notebook_page, 
    'validate_mod_auth_mysql_db');

function validate_mod_auth_mysql_db($field,$vars){
    global $db;
    $v = $vars[$field['name']];
    $v = $db->escape($v);
    mysql_query("SELECT username,passwd,groups FROM {$v} LIMIT 1");    
    if (mysql_errno()) 
        return "$field[title] - incorrect value. Error: " . mysql_error();
}


?>
