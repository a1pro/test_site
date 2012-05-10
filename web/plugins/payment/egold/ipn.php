<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1785 $)
*
*    Modified by admin@dwarfs-inc.biz (MD5 Hash Value added)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/


include "../../../config.inc.php";

$this_config = $plugin_config['payment']['egold'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function egold_error($msg){
    global $txn_id, $invoice;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_EGOLD_ERROR,$msg,$vars[PAYMENT_BATCH_NUM],$vars[PAYMENT_ID],'<br />')."\n".get_dump($vars));
}

// read post from E-Gold
$vars = get_input_vars();

$passhash = strtoupper(md5 ($this_config['secret_id']));
$hash = strtoupper(md5 ("$vars[PAYMENT_ID]:$vars[PAYEE_ACCOUNT]:$vars[PAYMENT_AMOUNT]:$vars[PAYMENT_UNITS]:$vars[PAYMENT_METAL_ID]:$vars[PAYMENT_BATCH_NUM]:$vars[PAYER_ACCOUNT]:$passhash:$vars[ACTUAL_PAYMENT_OUNCES]:$vars[USD_PER_OUNCE]:$vars[FEEWEIGHT]:$vars[TIMESTAMPGMT]"));

// check that receiver is me
if ($vars['PAYEE_ACCOUNT'] != $this_config['merchant_id'])
    egold_error(
    _PLUG_PAY_EGOLD_ERROR2.$this_config["merchant_id"]);

if ($vars['PAYMENT_UNITS'] != $this_config['units'])
    egold_error(
    sprintf(_PLUG_PAY_EGOLD_ERROR3,$vars[PAYMENT_UNITS],$this_config[units]));

if ($vars['V2_HASH'] != $hash)
    egold_error(
    sprintf(_PLUG_PAY_EGOLD_ERROR4,$vars[V2_HASH],$hash));

// process payment
$err = $db->finish_waiting_payment($vars['PAYMENT_ID'], 'egold',
        $vars['PAYMENT_BATCH_NUM'], $vars['PAYMENT_AMOUNT'], $vars);

if ($err)
    egold_error("finish_waiting_payment error: $err");

?>
