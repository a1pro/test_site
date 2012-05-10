<?php
/* ******************************************************************************
 * Atos Origin / Atos Worldline
 * Technologiestrasse 8 / D
 * Telefon +43-1-60543
 * A-1120 Wien
 *
 * all rights reserved
 * ****************************************************************************** 
 * History: 
 * $Log: Security.php,v $
 * Revision 1.1.2.6  2005/10/21 09:28:04  qja
 * delete all spaces before signing
 *
 * Revision 1.1.2.3  2005/06/28 08:59:19  mike
 * switch to Ascii
 * 
 * ****************************************************************************** 
 * Last CheckIn : $Author: qja $ 
 * Date : $Date: 2005/10/21 09:28:04 $ 
 * Revision : $Revision: 1.1.2.6 $ 
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/Security.php,v $ 
 * ******************************************************************************
 */
 
/**
* This class handles the security that has to be made
* Creating Fingerprint, signing and verifying the signature
*/

class Security {

    /**
    *  reads in a certificate file and creates a fingerprint
    *  @param Filename of the certificate
    *  @return fingerprint
    */
    function createCertFingerprint($filename) {
        $fp = fopen($filename, "r");
        
        if(!$fp) {
            return false;
        }
        $cert = fread($fp, 8192);
        fclose($fp);

        $data = openssl_x509_read($cert);

        if(!openssl_x509_export($data, $data)) {

            return false;
        }
        
        $data = str_replace("-----BEGIN CERTIFICATE-----", "", $data);
        $data = str_replace("-----END CERTIFICATE-----", "", $data);

        $data = base64_decode($data);

        $fingerprint = sha1($data);
        
        $fingerprint = strtoupper( $fingerprint );
        
        return $fingerprint;
        
    }
    
    /**
    * function to sign a message
    * @param filename of the private key
    * @param message to sign
    * @return signature
    */
    
    function signMessage($priv_keyfile, $key_pass, $data) {
    	$data = preg_replace("/\s/","",$data);
        $fp = fopen( $priv_keyfile , "r");
        $priv_key = fread($fp, 8192);
        fclose($fp);
        $pkeyid = openssl_get_privatekey($priv_key, $key_pass);

        // compute signature
        openssl_sign($data, $signature, $pkeyid);

        // free the key from memory
        openssl_free_key($pkeyid);

        return $signature;

    }
    
    /**
    * function to verify a message
    * @param filename of the public key to decrypt the signature
    * @param message to verify
    * @param sent signature
    * @return signature
    */
    
    function verifyMessage($certfile, $data, $signature) {

        // $data and $signature are assumed to contain the data and the     signature
        $ok=0;
        // fetch public key from certificate and ready it
        $fp = fopen( $certfile, "r");
        
        if(!$fp) {
          return false;
        }
        
        $cert = fread($fp, 8192);
        fclose($fp);
        $pubkeyid = openssl_get_publickey($cert);

        // state whether signature is okay or not
        $ok = openssl_verify($data, $signature, $pubkeyid);
        
        // free the key from memory
        openssl_free_key($pubkeyid);

        return $ok;

    }
    
    /**
    * @param fingerprint that´s been sent
    * @param the configuration file loaded in as an array
    * @return the filename of the certificate with this fingerprint
    */
    
    function getCertificateName($fingerprint, $config) {
        $count = 0;
        $certFilename = $config["CERTIFICATE" . $count];
        
        while( isset($certFilename) ) {
            $buff = $this->createCertFingerprint($certFilename);
            
            if( $fingerprint == $buff ) {
                return $certFilename;
            }
            
            $count+=1;
            $certFilename = $config["CERTIFICATE" . $count];
        }
        
        return false;

    }

}



?>