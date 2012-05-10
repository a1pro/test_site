<?php
/* ******************************************************************************
 * History: 
 * $Log: IdealResponse.php,v $
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
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/IdealResponse.php,v $ 
 * ******************************************************************************
 */

class IdealResponse {

    var $ok = false;
    var $errorMessage = "";
    var $errorCode = "";
    var $errorDetail = "";
    var $suggestedAction = "";
    var $suggestedExpirationPeriod = "";
    var $consumerMessage = "";

    /**
     * @return If an error has ocurred during the previous Request, this method returns a detailed
     * message about what went wrong. isOk() returnes false in that case.
     */
    function getErrorMessage() {
        return $this->errorMessage;
    }
    /**
     * sets the error string
     * @param errorMessage The errorMessage to set.
     */
    function setErrorMessage($errorMessage) {
        $this->errorMessage = $errorMessage;
    }
    
    function setErrorCode( $errorCode ) {
      $this->errorCode = $errorCode;
    }
    
    function getErrorCode() {
      return $this->errorCode;
    }
    
    function setErrorDetail( $errorDetail ) {
      $this->errorDetail = $errorDetail;
    }
    
    function getErrorDetail() {
      return $this->errorDetail;
    }
    
    function setSuggestedAction( $suggestedAction ) {
      $this->suggestedAction = $suggestedAction;
    }
    
    function getSuggestedAction() {
      return $this->suggestedAction;
    }
    
    function setSuggestedExpirationPeriod( $suggestedExpirationPeriod ) {
      $this->suggestedExpirationPeriod = $suggestedExpirationPeriod;
    }
    
    function getSuggestedExpirationPeriod() {
      return $this->suggestedExpirationPeriod;
    }
    
    function setConsumerMessage( $consumerMessage ) {
      $this->consumerMessage = $consumerMessage;
    }
    
    function getConsumerMessage() {
      return $this->consumerMessage;
    }
    
    
    /**
     * @return true, if the request was processed successfully, otherwise false. If
     * false, additional information can be received calling getErrorMessage()
     */
    function isOk() {
        return $this->ok;
    }

    /**
     * @param ok sets the OK flag
     */
    function setOk($ok) {
        $this->ok = $ok;
    }
}

?>