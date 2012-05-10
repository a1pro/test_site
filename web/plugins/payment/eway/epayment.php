<?php

 /*
  * e_payment.php
  * Electronic Payment XML Interface
  *
  * $Id: epayment.php 1640 2006-06-07 19:29:19Z avp $
  *
  */
 /* Avoid Nested Includes */
 $included_flag = 'INCLUDE_' . basename(__FILE__);

 if (defined($included_flag)) {
     return (TRUE);
 }
 define ($included_flag, TRUE);

 /* Your Temporary Location */
 define (TEMP_DIR,    "/tmp");
 define (TEMP_FILE_BASE, "/tmp.ep");

 /* eWAY Gateway Location (URI) */
 if (!defined(GATEWAY_URL)) {
     define (GATEWAY_URL, "https://www.eway.com.au/gateway/xmlpayment.asp");
 }

 /* Warn if we're running on what seems to be an insecure port */
// if ($SERVER_PORT != "443") {
//     print "<FONT COLOR=RED><B>WARNING:</B></FONT>This transaction was <b>INSECURE</b>\n";
// }

 /* Class Construction */
 class electronic_payment {
     var $parser;
     var $xml_data;
     var $current_tag;

     /* Call back Functions for the XML Parser */
     function ep_xml_element_start ($parser, $tag, $attributes) {
         $this->current_tag = $tag; 
     }

     function ep_xml_element_end ($parser, $tag) {
         $this->current_tag = "";
     }

     function ep_xml_data ($parser, $cdata) {
         $this->xml_data[$this->current_tag] = $cdata;
     }


     /* Public Properties */
     function trxn_error () {
         return $this->xml_data['ewayTrxnError'];
     }

     function trxn_status () {
         return $this->xml_data['ewayTrxnStatus'];
     }

     function trxn_number () {
         return $this->xml_data['ewayTrxnNumber'];
     }
    
     function trxn_reference () {
         return $this->xml_data['ewayTrxnReference'];
     }

     /* Instantiating Function */
     function electronic_payment ($my_customerid, $my_totalamount, $my_firstname, $my_lastname,
                                  $my_email, $my_address, $my_postcode, $my_invoice_description,
                                  $my_invoice_ref, $my_card_name, $my_card_number, $my_card_exp_month,
                                  $my_card_exp_year) {

         /* PHP currently lacks stable XML DOM functions - instead we build our
            XML request manually. */

         $xml_request = "
                <ewaygateway>
                    <ewayCustomerID>$my_customerid</ewayCustomerID>
                    <ewayTotalAmount>$my_totalamount</ewayTotalAmount>
                    <ewayCustomerFirstName>$my_firstname</ewayCustomerFirstName>
                    <ewayCustomerLastName>$my_lastname</ewayCustomerLastName>
                    <ewayCustomerEmail>$my_email</ewayCustomerEmail>
                    <ewayCustomerAddress>$my_address</ewayCustomerAddress>
                    <ewayCustomerPostcode>$my_postcode</ewayCustomerPostcode>
                    <ewayCustomerInvoiceDescription>$my_invoice_description</ewayCustomerInvoiceDescription>
                    <ewayCustomerInvoiceRef>$my_invoice_ref</ewayCustomerInvoiceRef>
                    <ewayCardHoldersName>$my_card_name</ewayCardHoldersName>
                    <ewayCardNumber>$my_card_number</ewayCardNumber>
                    <ewayCardExpiryMonth>$my_card_exp_month</ewayCardExpiryMonth>
                    <ewayCardExpiryYear>$my_card_exp_year</ewayCardExpiryYear>
                    <ewayTrxnNumber>$my_trxn_number</ewayTrxnNumber>
                    <ewayOption1>0</ewayOption1>
                    <ewayOption2>0</ewayOption2>
                    <ewayOption3>0</ewayOption3>
                </ewaygateway>
          ";


/*          
          // Decide on a name for, and then open a temporary file for 
          //   the XML response
          mt_srand(time());
          do {
              $temp_filename = TEMP_DIR . TEMP_FILE_BASE . "." . mt_rand(1,1000);
          } while (file_exists($temp_filename));
          $fh = fopen($temp_filename, "w+");

          // Use CURL to execute XML POST and write output to the temp file 
          $ch = curl_init (GATEWAY_URL);
          curl_setopt ($ch, CURLOPT_POST, 1);
          curl_setopt ($ch, CURLOPT_POSTFIELDS, $xml_request);
          curl_setopt ($ch, CURLOPT_FILE, $fh); 
          curl_exec($ch);
          curl_close($ch);

          // Read data from file into a string buffer 
          rewind ($fh);
          while (!feof($fh)) {
              $xml_response .= fgets($fh, 1024);
          }
*/

          $xml_response = cc_core_get_url(GATEWAY_URL, $xml_request);  
          $this->xml_request = $xml_request; // by Alex Scott - for logging
          $this->xml_response = $xml_response; // by Alex Scott - for logging

          $this->parser = xml_parser_create();

          /* Disable XML tag capitalisation (Case Folding) */
          xml_parser_set_option ($this->parser, XML_OPTION_CASE_FOLDING, FALSE);

          /* Define Callback functions for XML Parsing */
          xml_set_object($this->parser, $this);
          xml_set_element_handler ($this->parser, "ep_xml_element_start", "ep_xml_element_end");
          xml_set_character_data_handler ($this->parser, "ep_xml_data");

          /* Parse the XML response */
          xml_parse($this->parser, $xml_response, TRUE);
          
          /* Clean up after ourselves */
          xml_parser_free ($this->parser);
//          fclose ($fh);
//          unlink ($temp_filename);

     }

 }

?>
