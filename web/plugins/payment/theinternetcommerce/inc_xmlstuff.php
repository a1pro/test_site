<?php

switch ($actionrequest) { 

############### action newrequest
#################################
############### .: prepare newrequest xml code
############### .: open socket and post
############### .: retrieve reply
############### .: return success or error code
#################################
        case "newrequest":
    $today = date("Ymd:Hms");

    $MerchantRef = "$today".":"."$REMOTE_ADDR";

    $Amount = trim($Amount);
    $Amount = ereg_replace (",", ".", $Amount);
    $Amount = number_format($Amount, 2 , '.' , '' );
    $Amount = ereg_replace ("\.", "", $Amount);

    $CCN = ereg_replace (" ", "", $CCN);
    $CCN = ereg_replace ("-", "", $CCN);
    $CNN = trim($CCN);
    $CVCCVV = trim($CVCCVV);

    $arr['APACScommand'] = 'NewRequest';
    $arr['Data'] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
    <JProxyPayLink>
    <Message>
    <Type>PreAuth</Type>
    <Authentication>
    <MerchantID>$MerchantID</MerchantID>
    <Password>$Password</Password>
    </Authentication>
    <OrderInfo>
    <Amount>$Amount</Amount>
    <MerchantRef>$MerchantRef</MerchantRef>
    <MerchantDesc>$MerchantDesc</MerchantDesc>
    <Currency>978</Currency>
    <CustomerEmail>$CustomerEmail</CustomerEmail>
    <Var1>$Var1</Var1>
    <Var2>$Var2</Var2>
    <Var3>$Var3</Var3>
    <Var4>$Var4</Var4>
    <Var5>$Var5</Var5>
    <Var6>$Var6</Var6>
    <Var7>$Var7</Var7>
    <Var8>$Var8</Var8>
    <Var9>$Var9</Var9>
    </OrderInfo>
    <PaymentInfo>
    <CCN>$CCN</CCN>
    <Expdate>$Expdate</Expdate>
    <CVCCVV>$CVCCVV</CVCCVV>
    <InstallmentOffset>$InstallmentOffset</InstallmentOffset>
    <InstallmentPeriod>$InstallmentPeriod</InstallmentPeriod>
    </PaymentInfo>
    </Message>
    </JProxyPayLink>";


//    print_r($arr);
    $postresult = postxml($arr);
    print_r($postresult);
    $postresult_headers = substr($postresult, 0, strpos($postresult, "<?xml"));
    $postresult_xml = substr($postresult, strpos($postresult, "<?xml"), strlen($postresult));

    if(strstr($postresult_xml, '<?xml')) {
        $XML_ERRORCODE = parse_xmltag($postresult_xml, "ERRORCODE");
        $XML_ERRORMESSAGE = parse_xmltag($postresult_xml, "ERRORMESSAGE");
        $XML_PROXYPAYREF = parse_xmltag($postresult_xml, "PROXYPAYREF");
        $XML_SEQUENCE = parse_xmltag($postresult_xml, "SEQUENCE");
        $XML_REFERENCE = parse_xmltag($postresult_xml, "REFERENCE");
    } else {
        $XML_ERRORCODE = -1;
        $XML_ERRORMESSAGE = "Error while connecting/posting to server";
    }
        break;

############### action capture
#################################
############### .: prepare capture xml code 
############### .: open socket and post
############### .: retrieve reply
############### .: return success or error code
#################################
        case "capture":
    $arr['APACScommand'] = 'NewRequest';
        $arr['Data'] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <JProxyPayLink>
        <Message>
        <Type>Capture</Type>
        <Authentication>
        <MerchantID>$MerchantID</MerchantID>
        <Password>$Password</Password>
        </Authentication>
        <OrderInfo>
        <Amount>$Amount</Amount> 
        <MerchantRef>$MerchantRef</MerchantRef>
        <MerchantDesc /> 
        <Currency />
        <CustomerEmail />
        <Var1 />
        <Var2 />
        <Var3 />
        <Var4 />
        <Var5 />
        <Var6 />
        <Var7 />
        <Var8 />
        <Var9 />
        </OrderInfo>
        </Message>
        </JProxyPayLink>";


        $postresult = postxml($arr);
        $postresult_headers = substr($postresult, 0, strpos($postresult, "<?xml"));
        $postresult_xml = substr($postresult, strpos($postresult, "<?xml"), strlen($postresult));

        if(strstr($postresult_xml, '<?xml')) {
                $XML_ERRORCODE = parse_xmltag($postresult_xml, "ERRORCODE");
                $XML_ERRORMESSAGE = parse_xmltag($postresult_xml, "ERRORMESSAGE");
                $XML_PROXYPAYREF = parse_xmltag($postresult_xml, "PROXYPAYREF");
                $XML_SEQUENCE = parse_xmltag($postresult_xml, "SEQUENCE");
                $XML_REFERENCE = parse_xmltag($postresult_xml, "REFERENCE");
        } else {
                $XML_ERRORCODE = -1;
                $XML_ERRORMESSAGE = "Error while connecting/posting to server";
        }
        break;

############### unknown request
        default:
    $XML_ERRORCODE = -1;
    $XML_ERRORMESSAGE = "I am not programmed to perform this <font color=navy>$actionrequest</font> action!";
        break;
}

?>
