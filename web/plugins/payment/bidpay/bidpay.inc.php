<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2624 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


class payment_bidpay extends amember_payment {
    var $title = _PLUG_PAY_BIDPAY_TITLE;
    var $description = _PLUG_PAY_BIDPAY_DESC;
    var $fixed_price = 0;
    var $recurring = 0;
//    var $supports_trial1 = 0;
    var $built_in_trials = 0;

    function do_bill($amount, $title, $products, $u, $invoice){

        global $db, $config;
        $product = $products[0];
        $payment_id = $invoice;

        require_once('lib/nusoap.php');

        if ($this->config['testmode'])
            $url = "https://sandbox.api.bidpay.com/ThirdPartyPayment/v1/ThirdPartyPaymentService.asmx";
        else
            $url = "https://api.bidpay.com/ThirdPartyPayment/v1/ThirdPartyPaymentService.asmx";

        $item = array(
            'ItemNumber'      => $product['product_id'],
            'ItemDescription' => $product['title'],
            'ItemType'        => 'WebsitePurchase',
            'Site'            => $config['site_title'],  //$config['root_url']
            'Amount'          => $amount,
            'ShippingAmount'  => '0'
            );
        $items['Item'] = $item;

        $vars = array(
            'SellerToken'     => $this->config['token'],
            'ReferenceNumber' => $payment_id,
            'AuctionBuyerID'  => $member_id,
            'Items'           => $items,
            'ReturnUrl'       => $config['root_url'] . "/plugins/payment/bidpay/thanks.php"
            );

        $client = new soapclient($url . "?WSDL", true);

        $err = $client->getError();
        if ($err) {
            if ($this->config['testmode']) $db->log_error("BidPay ERROR. Response: " . $client->response);
            if ($this->config['testmode']) echo "<pre>".$client->getDebug()."</pre>";
            return "Error: " . $err;
        }

		$uuidMessageID = NewUuid();
		$uuidTimestamp = NewUuid();
		$uuidSecurityToken = NewUuid();

		$nonce = "RqjH7M+gwhx6vTL/QgJK2A==";

		$now = time();
		$created = GetUtc8601($now);

		$now = time() + 60 * 5;
		$expires = GetUtc8601($now);

        $headers = array();

        $headers[] = "<wsa:Action>http://api.bidpay.com/ThirdPartyPayment/v1/Methods/PaymentRequest</wsa:Action>";
        $headers[] = "<wsa:MessageID>urn:uuid:".$uuidMessageID."</wsa:MessageID>";
        $headers[] = "<wsa:ReplyTo>";
        $headers[] = "<wsa:Address>http://schemas.xmlsoap.org/ws/2004/08/addressing/role/anonymous</wsa:Address>";
        $headers[] = "</wsa:ReplyTo>";
        $headers[] = "<wsa:To>".$url."</wsa:To>";

        $headers[] = "<wsse:Security soap:mustUnderstand=\"1\">";
        $headers[] = "<wsu:Timestamp wsu:Id=\"Timestamp-$uuidTimestamp\">";
        $headers[] = "<wsu:Created>$created</wsu:Created>";
        $headers[] = "<wsu:Expires>$expires</wsu:Expires>";
        $headers[] = "</wsu:Timestamp>";
        $headers[] = "<wsse:UsernameToken xmlns:wsu=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd\" wsu:Id=\"SecurityToken-".$uuidSecurityToken."\">";
        $headers[] = "<wsse:Username>".$this->config['username']."</wsse:Username>";
        $headers[] = "<wsse:Password Type=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText\">"
                      .$this->config['password']."</wsse:Password>";

        //$headers[] = "<wsse:Nonce>".$nonce."</wsse:Nonce>";
        $headers[] = "<wsu:Created>".$created."</wsu:Created>";

        $headers[] = "</wsse:UsernameToken>";
        $headers[] = "</wsse:Security>";

        $headers = implode ("\n", $headers);

        $result = $client->call(
            'ThirdPartyPayment',
            array('thirdPartyPaymentRequest' => $vars),
            '', //'http://api.bidpay.com/ThirdPartyPayment/v1/Messages/ThirdPartyPaymentRequest-1-0',
            '', //'http://api.bidpay.com/ThirdPartyPayment/v1/Messages/ThirdPartyPaymentRequest-1-0',
            $headers
            );

        if ($client->fault) {
            if ($this->config['testmode']) $db->log_error("BidPay ERROR. Response: " . $client->response);
            if ($this->config['testmode']) echo "<pre>".$client->getDebug()."</pre>";
            return "Soap Request Fault [".$client->getError()."]";

        } else {
            $err = $client->getError();
            if ($err) {
                if ($this->config['testmode']) $db->log_error("BidPay ERROR. Response: " . $client->response);
                if ($this->config['testmode']) echo "<pre>".$client->getDebug()."</pre>";
                return "Error " . $err;

            } else {
                /*
                TransactionID This is the ID assigned to the transaction by BidPay.
                ReferenceNumber This is the value supplied by the third party in the request.
                PaymentURL This is the URL to which the buyer should be redirected to complete the checkout process.
                */

            }
        }


    }
    function validate_thanks(&$vars){
        return '';
    }
    function process_thanks(&$vars){
            global $db;
            require_once('lib/nusoap.php');

            if ($this->config['testmode'])
                $url = "https://sandbox.api.bidpay.com/ThirdPartyPayment/v1/ThirdPartyPaymentService.asmx";
            else
                $url = "https://api.bidpay.com/ThirdPartyPayment/v1/ThirdPartyPaymentService.asmx";

            $client = new soapclient($url, true);
            $err = $client->getError();
            if ($err) {
                return "Error: " . $err;
            }


            $vars = array(
                'TransactionID' => ''
            );

            $headers = "";

            $result = $client->call(
                'ThirdPartyPaymentStatus',
                array('ThirdPartyPaymentStatusRequest' => $vars),
                'http://api.bidpay.com/ThirdPartyPayment/v1/Messages/ThirdPartyPaymentStatusRequest-1-0',
                'http://api.bidpay.com/ThirdPartyPayment/v1/Messages/ThirdPartyPaymentStatusRequest-1-0',
                $headers
                );

            if ($client->fault) {

                return "Soap Request Fault [".$client->getError()."]";

            } else {
                $err = $client->getError();
                if ($err) {

                    return "Error " . $err;

                } else {

/*
TransactionID This is the ID assigned to the new status transaction by BidPay.
OriginalPaymentRequestTransactionID This is the Transaction ID that was passed in the request.
OrderID This is the Order ID assigned to the order by BidPay. If there is no Order ID, the buyer never completed the transaction.
OrderStatus This is the status of the order. Values include Pending, Approved, Denied, Cancelled by Request, Cancelled as Duplicate, and Cancelled.
CreditCardChargeStatus This is the status of the buyer’s credit card charge. Values include Pending, Authorized, Declined, Billed, and Cancelled.
AchPaymentStatus This is the status of the ACH payment going out to the seller. Values include In Process, Payment Declined - Waiting on Customer Correction, Payment Completed, Cancelled, and Stop Payment.
CreditCardRefundStatus This is the status of the credit card refund (when applicable). Values include In Process, Refund Completed, and Cancelled.
*/

                    $err = $db->finish_waiting_payment(intval($vars['payment_id']), 'bidpay', $vars['transaction_id'], '', $vars);
                    if ($err)
                        return _PLUG_PAY_BIDPAY_ERROR . $err;

                }
            }

    }

    function init(){
        parent::init();
    }
}


function NewUuid(){

	//got this code from: http://www.soulhuntre.com/items/date/2004/10/29/uuid-guid-in-native-php/
	$rawid = strtoupper(md5(uniqid(rand(), true)));
	$workid = $rawid;

	// hopefully conform to the spec, mark this as a "random" type
	// lets handle the version byte as a number
	$byte = hexdec( substr($workid,12,2) );
	$byte = $byte & hexdec("0f");
	$byte = $byte | hexdec("40");
	$workid = substr_replace($workid, strtoupper(dechex($byte)), 12, 2);

	// hopefully conform to the spec, mark this common variant
	// lets handle the "variant"
	$byte = hexdec( substr($workid,16,2) );
	$byte = $byte & hexdec("3f");
	$byte = $byte | hexdec("80");
	$workid = substr_replace($workid, strtoupper(dechex($byte)), 16, 2);

	// build a human readable version
	$wid = substr($workid, 0, 8).'-' .substr($workid, 8, 4).'-' .substr($workid,12, 4).'-'.substr($workid,16, 4).'-'.substr($workid,20,12);
	return $wid;

}

function GetUtc8601($time) {
    $offset = date("Z");
    $time = $time - $offset;
	return date("Y-m-d\TH:i:s\Z", $time);
}

/*
function GetUtc8601(DateTime $time) {
	$tTime = clone $time;
	$tTime->setTimezone(new DateTimeZone("UTC"));
	return $tTime->format("Y-m-d\TH:i:s\Z");
}
*/


instantiate_plugin('payment', 'bidpay');
?>