<?php

/*
 * Smarty plugin
 * alex@cgi-central.net
 * -------------------------------------------------------------
 * Type:     function
 * Name:     lookup
 * Purpose:  lookup a value in array
 * -------------------------------------------------------------
 */
function smarty_function_lookup($params, &$smarty)
{
    extract($params);

    if (!is_array($arr)) {
        $smarty->trigger_error("assign: missing 'arr' parameter or isn't array");
        return;
    }

//    if (empty($key)) {
//        $smarty->trigger_error("assign: missing 'key' parameter");
//        return;
//    }

    print $arr[$key];
}

/* vim: set expandtab: */

?>
