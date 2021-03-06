<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty assign_smarty_interface core plugin
 *
 * Type:     core<br />
 * Name:     assign_smarty_interface<br />
 * Purpose:  assign the $smarty interface variable
 * @param array Format: null
 * @param Smarty
 */
function smarty_core_assign_smarty_interface($params, &$smarty)
{
        if (isset($smarty->_smarty_vars) && isset($smarty->_smarty_vars['request'])) {
            return;
        }

        $_globals_map = array('g'  => '_GET',
                             'p'  => '_POST',
                             'c'  => '_COOKIE',
                             's'  => '_SERVER',
                             'e'  => '_ENV');

        $_smarty_vars_request  = array();

        foreach (preg_split('!!', strtolower($smarty->request_vars_order)) as $_c) {
            if (isset($_globals_map[$_c])) {
                $var = $_globals_map[$_c];
                $_smarty_vars_request = array_merge($_smarty_vars_request, $$var);
            }
        }
        $_smarty_vars_request = @array_merge($_smarty_vars_request, $_SESSION);

        $smarty->_smarty_vars['request'] = $_smarty_vars_request;
}

/* vim: set expandtab: */

?>
