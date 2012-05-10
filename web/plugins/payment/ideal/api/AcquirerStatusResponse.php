<?php
/* ******************************************************************************
 * History: 
 * $Log: AcquirerStatusResponse.php,v $
 * Revision 1.1.2.6  2005/10/18 09:25:45  mos
 * AW references removed
 *
 * Revision 1.1.2.4  2005/06/28 13:40:00  qro
 * korr transactionID
 * 
 * ****************************************************************************** 
 * Last CheckIn : $Author: mos $ 
 * Date : $Date: 2005/10/18 09:25:45 $ 
 * Revision : $Revision: 1.1.2.6 $ 
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/AcquirerStatusResponse.php,v $ 
 * ******************************************************************************
 */
 
 
/**
 * This class contains all necessary data that can be returned from a iDEAL AcquirerTrxRequest.
 * <p>
 */
require_once("IdealResponse.php");

class AcquirerStatusResponse extends IdealResponse {

    var $authenticated = false;
    var $consumerName = "";
    var $consumerAccountNumber = "";
    var $consumerCity = "";
    var $transactionID = "";

    /**
     * @return Returns true, if the transaction was authenticated, otherwise false.
     */
    function isAuthenticated() {
        return $this->authenticated;
    }
    /**
     * @param authenticated The authenticated flag to be set.
     */
    function setAuthenticated($authenticated) {
        $this->authenticated = $authenticated;
    }
    /**
     * @return Returns the consumerAccountNumber.
     */
    function getConsumerAccountNumber() {
        return $this->consumerAccountNumber;
    }
    /**
     * @param consumerAccountNumber The consumerAccountNumber to set.
     */
    function setConsumerAccountNumber($consumerAccountNumber) {
        $this->consumerAccountNumber = $consumerAccountNumber;
    }
    /**
     * @return Returns the consumerCity.
     */
    function getConsumerCity() {
        return $this->consumerCity;
    }
    /**
     * @param consumerCity The consumerCity to set.
     */
    function setConsumerCity($consumerCity) {
        $this->consumerCity = $consumerCity;
    }
    /**
     * @return Returns the consumerName.
     */
    function getConsumerName() {
        return $this->consumerName;
    }
    /**
     * @param consumerName The consumerName to set.
     */
    function setConsumerName($consumerName) {
        $this->consumerName = $consumerName;
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