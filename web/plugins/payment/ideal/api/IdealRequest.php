<?php
/* ******************************************************************************
 * History: 
 * $Log: IdealRequest.php,v $
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
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/IdealRequest.php,v $ 
 * ******************************************************************************
 */
 
/**
 * This is a base class for all Ideal Requests and should not be instantiated directly.
 * It contains some fields that are used by all Requests in iDEAL payment.
 */


class IdealRequest {
    var $merchantID = "";
    var $subID = "";
    var $authentication = "";


    /**
     * clears all parameters
     */
    function clear() {
        $this->merchantID = "";
        $this->subID = "";
        $this->authentication = "";
    }

    /**
     * @returns a readable representation of the Class
     */
    function toString() {
        return "IdealRequest: merchantID = " . $this->merchantID
        . " subID = " . $this->subID
        . " authentication = " . $this->authentication;
    }

    /**
     * this method checks, whether all mandatory properties are set.
     * @return true if all fields are valid, otherwise returns false
     */
     function checkMandatory () {

        if (   strlen( $this->merchantID ) > 0
            && strlen( $this->subID ) > 0
            && strlen( $this->authentication ) > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return Returns the authentication.
     */
    function getAuthentication() {
        return $this->authentication;
    }
    /**
     * @param authentication The type of authentication to set.
     * Currently only "RSA_SHA1" is implemented. (mandatory)
     */
    function setAuthentication($authentication) {
        $this->authentication = trim($authentication);
    }
    /**
     * @return Returns the merchantID.
     */
    function getMerchantID() {
        return $this->merchantID;
    }
    /**
     * @param merchantID The merchantID to set. (mandatory)
     */
    function setMerchantID($merchantID) {
        $this->merchantID = trim($merchantID);
    }
    
    /**
     * @return Returns the subID.
     */
    function getSubID() {
        return $this->subID;
    }
    /**
     * @param subID The subID to set. (mandatory)
     */
    function setSubID($subID) {
        $this->subID = trim($subID);
    }
}

?>