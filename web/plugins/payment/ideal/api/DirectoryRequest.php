<?php
/* ******************************************************************************
 * History: 
 * $Log: DirectoryRequest.php,v $
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
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/DirectoryRequest.php,v $ 
 * ******************************************************************************
 */
 

/**
 * This class encapsulates all data needed for a DirectoryRequest for the iDEAL Payment. To send a Request, an
 * Instance has to be created with "new...". After that, all mandatory properties must be set.
 * When done, processRequest() of class ThinMPI can be called with this request class.
 *
 */

require_once("IdealRequest.php");

class DirectoryRequest extends IdealRequest{

    /**
     * clears all parameters
     */
    function clear() {
        IdealRequest:: clear();
    }

    /**
     * this method checks, whether all mandatory properties are set.
     * @return true if all fields are valid, otherwise returns false
     */
    function checkMandatory () {
        if (IdealRequest::checkMandatory()) {
            return true;
        } else {
            return false;
        }
    }
}

?>