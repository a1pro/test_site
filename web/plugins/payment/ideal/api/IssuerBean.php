<?php
/* ******************************************************************************
 * History: 
 * $Log: IssuerBean.php,v $
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
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/IssuerBean.php,v $ 
 * ******************************************************************************
 */
 
/**
* This bean class represents an issuer as received by a directory response.
*
*/

class IssuerBean {
    var $issuerID = "";
    var $issuerName = "";
    var $issuerList = "";

    /**
     * @returns a readable representation of the IssuerBean
     */
    function toString() {
        return "IssuerBean: issuerID=" . $this->issuerID
        . " issuerName=" . $this->issuerName
        . " issuerList=" . $this->issuerList;
    }
    /**
     * @return Returns the issuerID.
     */
    function getIssuerID() {
        return $this->issuerID;
    }
    /**
     * @param issuerID The issuerID to set.
     */
    function setIssuerID($issuerID) {
        $this->issuerID = $issuerID;
    }
    /**
     * @return Returns the issuerList. ("Short", "Long")
     */
    function getIssuerList() {
        return $this->issuerList;
    }
    /**
     * @param issuerList The issuerList to set.
     */
    function setIssuerList($issuerList) {
        $this->issuerList = $issuerList;
    }
    /**
     * @return Returns the issuerName.
     */
    function getIssuerName() {
        return $this->issuerName;
    }
    /**
     * @param issuerName The issuerName to set.
     */
    function setIssuerName($issuerName) {
        $this->issuerName = $issuerName;
    }
}



?>