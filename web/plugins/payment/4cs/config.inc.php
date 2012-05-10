<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = '4CS';
config_set_notebook_comment($notebook_page, '4CS plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.4cs.merchkey', 'Your 4CS merchant key',
    'text', "A unique assigned code identifying the<br />
	merchant under which transactions will<br />
	be processed",
    $notebook_page);
add_config_field('payment.4cs.tranpage', 'Your 4CS transaction URL',
    'text', "You will be provided with a URL <br />
	for testing and live transaction processing by the processor when<br />
	your merchant accounts have been configured.",
    $notebook_page);
add_config_field('payment.4cs.currency', 'Currency',
    'text', "ISO alpha order currency code, <br/>
	for example: EUR, USD, GBP, CHF, :",
    $notebook_page);

?>
