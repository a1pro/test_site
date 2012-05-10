<?php
/* ******************************************************************************
 * History: 
 * $Log: AcquirerStatusRequest.php,v $
 * Revision 1.1.2.5  2005/10/18 09:25:45  mos
 * AW references removed
 *
 * Revision 1.1.2.3  2005/06/28 08:57:23  mike
 * switch to Ascii
 * 
 * ****************************************************************************** 
 * Last CheckIn : $Author: mos $ 
 * Date : $Date: 2005/10/18 09:25:45 $ 
 * Revision : $Revision: 1.1.2.5 $ 
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/AcquirerStatusRequest.php,v $ 
 * ******************************************************************************
 */
 
 
/**
 * This class encapsulates all data needed for a AcquirerStatusRequest for the iDEAL Payment. To send a Request, an
 * Instance has to be created with "new...". After that, all mandatory properties must be set.
 * When done, processRequest() of class ThinMPI can be called with this request class.
 */
require_once("IdealRequest.php");

class AcquirerStatusRequest extends IdealRequest{
    var $transactionID = "";
    
    /**
     * rests all input data to empty strings
     */
    function clear() {
        IdealRequest::clear();
        $this->transactionID = "";
    }
    /**
     * this method checks, wheather all mandatory properties were set.
     * If done so, true is returned, otherwise false.
     * @return If done so, true is returned, otherwise false.
     */

    function checkMandatory () {

        if (IdealRequest::checkMandatory()
            && strlen( $this->transactionID ) > 0
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
        return IdealRequest::toString() . " AcquirerStatusRequest: transactionID = " . $this->transactionID;
    }

    /**
     * @return Returns the transactionID.
     */
    function getTransactionID() {
        return $this->transactionID;
    }
    /**
     * @param transactionID The transactionID of the corresponding transaction. (mandatory)
     */
    function setTransactionID($transactionID) {
        $this->transactionID = $transactionID;
    }
}


?>