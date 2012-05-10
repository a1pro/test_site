<?php

$INCREDIBLE_CLEARANCE_STATUS = "";      // should be set to 0 on success, or 1 on failure
$INCREDIBLE_CLEARANCE_DEBUGMESSAGE = "";
$timestamp = date("Y-m-d H:i:s", time());

## Include Socket Functions
###########################
include ("inc_socketstuff.php");

#############################################################
######## Variable for making a newRequest - Not to be changed
#############################################################
/*
$MerchantID = "XXXXXXXXXXXX";
$Password = "XXXXXXXXXXX";

#############################################################

$Amount = $amount;
$MerchantDesc = "$comments";
$CustomerEmail = $Email;
$Var1 = "$name";
$Var2 = "$Email";
$Var3 = "$description";
$Var4 = "$address $city $zip $country";
$Var5 = "$comments";
$Var6 = "$keeplog";
$Var7 = "Server Time: $timestamp";
$Var8 = "IP: $REMOTE_ADDR";
$Var9 = "Host: $REMOTE_HOST";
$CCN = "$cc_number";
$Expdate = "$card_month$card_year";
#$CVCCVV = "123";
$CVCCVV = "$credit_card_verification";
$InstallmentOffset = 0;
$InstallmentPeriod = 0;
*/

###### Do not edit after this point
#############################################################


$actionrequest = "newrequest";
include ("inc_xmlstuff.php");

if ($XML_ERRORCODE != 0) {
    #### newrequest failed. setup some variables for debugging purposes.
    $tempvar = $arr['Data'];
    $INCREDIBLE_CLEARANCE_STATUS = 1;
    $INCREDIBLE_CLEARANCE_ERROR = "<font color=red>Error</font>: $XML_ERRORCODE ($XML_ERRORMESSAGE)";
    $INCREDIBLE_CLEARANCE_DEBUGMESSAGE = "  Error while executing action $actionrequest:
                        $XML_ERRORCODE ($XML_ERRORMESSAGE)<br /><br />
                        Full xml response from bank server:<br />
                        <hr><xmp>$postresult</xmp><hr>
                        Our xml posted looked like this:<br />
                        <hr><xmp>$tempvar</xmp><hr>";
} else {
    $actionrequest = "capture";
    include ("inc_xmlstuff.php");
    if ($XML_ERRORCODE != 0) {
        #### capture failed. setup some variables for debugging purposes.
        $tempvar = $arr['Data'];
        $INCREDIBLE_CLEARANCE_STATUS = 1;
        $INCREDIBLE_CLEARANCE_ERROR = "<font color=red>Error</font>: $XML_ERRORCODE ($XML_ERRORMESSAGE)";
        $INCREDIBLE_CLEARANCE_DEBUGMESSAGE = "  Action newrequest executed successfully but we got an error while executing action $actionrequest:
                            $XML_ERRORCODE ($XML_ERRORMESSAGE)<br /><br />
                            Full xml response from bank server:<br />
                            <hr><xmp>$postresult</xmp><hr>
                            Our xml posted looked like this:<br />
                            <hr><xmp>$tempvar</xmp><hr>";
    } else {
        ###############
        ############### We got here because everything went ok !
        ###############
        $INCREDIBLE_CLEARANCE_STATUS = 0;
        $INCREDIBLE_CLEARANCE_DEBUGMESSAGE = "no errors";
    }
} // end else
?>
