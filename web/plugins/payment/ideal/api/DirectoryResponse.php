<?php

/* ******************************************************************************
 * History: 
 * $Log: DirectoryResponse.php,v $
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
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/DirectoryResponse.php,v $ 
 * ******************************************************************************
 */

/**
 * This class contains all necessary data that can be returned from a iDEAL DirectoryRequest.
 */


require_once("IdealResponse.php");
require_once("IssuerBean.php");

class DirectoryResponse extends IdealResponse {
    var $acquirerID = "";
    var $issuerList = array();

    /**
     * @return Returns a list if IssuerBean objects.
     * The List contains all Issuers that were send by the acquirer System during the Directory Request.
     * The Issuers are stored as IssuerBean objects.
     */
    function getIssuerList() {
        return $this->issuerList;
    }
    /**
     * @return Returns the acquirerID from the answer XML message.
     */
    function getAcquirerID() {
        return $this->acquirerID;
    }
    
    /**
     * @param sets the acquirerID 
     */
    function setAcqirerID($acquirerID) {
        $this->acquirerID = $acquirerID;
    }
    /**
     * adds an Issuer to the IssuerList
     */
    function addIssuer($bean) {
        if(is_a( $bean, "IssuerBean")) {
            array_push( $this->issuerList, $bean );
        }
        
    }
}

?>