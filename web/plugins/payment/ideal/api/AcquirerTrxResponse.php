<?php

/* ******************************************************************************
 * History: 
 * $Log: AcquirerTrxResponse.php,v $
 * Revision 1.1.2.6  2005/10/18 09:25:45  mos
 * AW references removed
 *
 * Revision 1.1.2.4  2005/06/28 08:59:19  mike
 * switch to Ascii
 * 
 * ****************************************************************************** 
 * Last CheckIn : $Author: mos $ 
 * Date : $Date: 2005/10/18 09:25:45 $ 
 * Revision : $Revision: 1.1.2.6 $ 
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/AcquirerTrxResponse.php,v $ 
 * ******************************************************************************
 */

require_once("IdealResponse.php");

class AcquirerTrxResponse extends IdealResponse {



    // fields for xml answer
    var $acquirerID;
    var $issuerAuthenticationURL;
    var $transactionID;



    /**
     * @return Returns the acquirerID.
     */
    function getAcquirerID() {
        return $this->acquirerID;
    }
    /**
     * @param acquirerID The acquirerID to set. (mandatory)
     */
    function setAcquirerID($acquirerID) {
        $this->acquirerID = $acquirerID;
    }
    /**
     * @return Returns the issuerAuthenticationURL.
     */
    function getIssuerAuthenticationURL() {
        return $this->issuerAuthenticationURL;
    }
    /**
     * @param issuerAuthenticationURL The issuerAuthenticationURL to set.
     */
    function setIssuerAuthenticationURL($issuerAuthenticationURL) {
        $this->issuerAuthenticationURL = $issuerAuthenticationURL;
    }
   
    /**
     * @return Returns the transactionID.
     */
    function getTransactionID() {
        return $this->transactionID;
    }
    /**
     * @param transactionID The transactionID to set.
     */
    function setTransactionID($transactionID) {
        $this->transactionID = $transactionID;
    }

}


?>