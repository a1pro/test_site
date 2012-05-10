<?php

/**
 *
 * Copyright (C) 2010 Kencinnus, LLC. All rights reserved.
 *
 * This file may not be distributed by anyone outside of
 * Kencinnus, LLC or authorized contractors as specified.
 *
 * Purchasers of this plugin can modify it for the site
 * it is installed on.
 *
 * This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
 * THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE.
 *
 * All Support Questions regarding this plugin should be sent to http://www.kencinnus.com/contact
 *
 * (See amail_aweber.inc.php for revision history.)
 *
 **/

require '../../../config.inc.php';
$t = &new_smarty();
$vars = get_input_vars();
$t->assign('vars', $vars);
$t->display(dirname(__FILE__) . '/thanks.html');
?>

