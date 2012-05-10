<?php
/* ******************************************************************************
 * History: 
 * $Log: AcquirerTrxRequest.php,v $
 * Revision 1.1.2.5  2005/10/18 09:25:45  mos
 * AW references removed
 *
 * Revision 1.1.2.3  2005/06/28 08:59:19  mike
 * switch to Ascii
 * 
 * ****************************************************************************** 
 * Last CheckIn : $Author: mos $ 
 * Date : $Date: 2005/10/18 09:25:45 $ 
 * Revision : $Revision: 1.1.2.5 $ 
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/AcquirerTrxRequest.php,v $ 
 * ******************************************************************************
 */
 
 
/**
 * This class encapsulates all data needed for a AcquirerTrxRequest for the iDEAL Payment. To send a Request, an
 * Instance has to be created with "new...". After that, all mandatory properties must be set.
 * When done, processRequest() of class ThinMPI can be called with this request class.
 */
require_once("IdealRequest.php");

class AcquirerTrxRequest extends IdealRequest {

    // fields for request xml message
    var $issuerID = "";
    var $merchantReturnURL = "";
    var $purchaseID = "";
    var $amount = "";
    var $currency = "";
    var $expirationPeriod = "";
    var $language = "";
    var $description = "";
    var $entranceCode = "";

    /**
     * @return Returns the amount.
     */
    function getAmount() {
        return $this->amount;
    }
    /**
     * @param amount The amount to set. (mandatory)
     */
    function setAmount($amount) {
        $this->amount = $amount;
    }

    /**
     * @return Returns the currency.
     */
    function getCurrency() {
        return $this->currency;
    }
    /**
     * @param currency The currency to set, e.g. "EUR". (mandatory)
     */
    function setCurrency($currency) {
        $this->currency = $currency;
    }
    /**
     * @return Returns the payment description.
     */
    function getDescription() {
        return $this->description;
    }
    /**
     * @param description The payment description to set. (optional)
     */
    function setDescription($description) {
        if ($description != null)
           $this->description = $description;
    }
    /**
     * @return Returns the entranceCode.
     */
    function getEntranceCode() {
        return $this->entranceCode;
    }
    /**
     * @param entranceCode The entranceCode to set. (mandatory)
     */
    function setEntranceCode($entranceCode) {
        $this->entranceCode = $entranceCode;
    }
    /**
     * @return Returns the expirationPeriod.
     */
    function getExpirationPeriod() {
        return $this->expirationPeriod;
    }
    /**
     * @param expirationPeriod The expirationPeriod to set. (mandatory)
     */
    function setExpirationPeriod($expirationPeriod) {
        $this->expirationPeriod = $expirationPeriod;
    }
    /**
     * @return Returns the issuerID.
     */
    function getIssuerID() {
        return $this->issuerID;
    }
    /**
     * @param issuerID The issuerID to set. (mandatory)
     */
    function setIssuerID($issuerID) {
        $this->issuerID = $issuerID;
    }
    /**
     * @return Returns the language.
     */
    function getLanguage() {
        return $this->language;
    }
    /**
     * @param language The language to set, e.g "nl". (mandatory)
     */
    function setLanguage($language) {
        $this->language = $language;
    }
    /**
     * @return Returns the merchantReturnURL.
     */
    function getMerchantReturnURL() {
        return $this->merchantReturnURL;
    }
    /**
     * @param merchantReturnURL The merchantReturnURL to set. (mandatory)
     */
    function setMerchantReturnURL($merchantReturnURL) {
        $this->merchantReturnURL = $merchantReturnURL;
    }
    /**
     * @return Returns the purchaseID.
     */
    function getPurchaseID() {
        return $this->purchaseID;
    }
    /**
     * @param purchaseID The purchaseID to set. (mandatory)
     */
    function setPurchaseID($purchaseID) {
        $this->purchaseID = $purchaseID;
    }

    function clear() {
        IdealRequest::clear();
        $this->issuerID = "";
        $this->merchantReturnURL = "";
        $this->purchaseID = "";
        $this->amount = "";
        $this->currency = "";
        $this->expirationPeriod = "";
        $this->language = "";
        $this->description = "";
        $this->entranceCode = "";
    }
    
    /**
     * this method checks, whether all mandatory properties were set.
     * If done so, true is returned, otherwise false.
     * @return If done so, true is returned, otherwise false.
     */
    function checkMandatory () {

        if ((IdealRequest::checkMandatory() == true)
            && (strlen( $this->issuerID ) > 0)
            && (strlen( $this->merchantReturnURL ) > 0)
            && (strlen( $this->purchaseID ) > 0)
            && (strlen( $this->amount ) > 0)
            && (strlen( $this->currency ) > 0)
            && (strlen( $this->expirationPeriod ) > 0)
            && (strlen( $this->language ) > 0)
            && (strlen( $this->entranceCode ) > 0)
            && (strlen( $this->description ) > 0)
            ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @returns a readable representation of the Class
     */
    function toString() {
        return IdealRequest::toString() . " AcquirerTrxRequest: issuerID = " . $this->issuerID
        . " merchantReturnURL = " . $this->merchantReturnURL
        . " purchaseID = " . $this->purchaseID
        . " amount = " . $this->amount
        . " currency = " . $this->currency
        . " expirationPeriod = " . $this->expirationPeriod
        . " language = " . $this->language
        . " entranceCode = " . $this->entranceCode
        . " description = " . $this->description;
    }

}

?>