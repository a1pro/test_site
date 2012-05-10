<?php
/* ******************************************************************************
 * History: 
 * $Log: LoadConf.php,v $
 * Revision 1.1.2.5  2005/10/18 09:25:45  mos
 * AW references removed
 *
 * Revision 1.1.2.3  2005/06/28 08:59:18  mike
 * switch to Ascii
 * 
 * ****************************************************************************** 
 * Last CheckIn : $Author: mos $ 
 * Date : $Date: 2005/10/18 09:25:45 $ 
 * Revision : $Revision: 1.1.2.5 $ 
 * Repository File : $Source: /cvs/as/WLP_NEW/src_php/Attic/LoadConf.php,v $ 
 * ******************************************************************************
 */

/*
* load the config file and save the data into an array
* @return array (with configfile data)
*/

function LoadConfiguration() {
    global $config;
    
    $this_config = $config['payment']['ideal'];

    $conf_data=array();

    // This section defines the variables used to create your own RSA private key and the certificate based on this key
    // Default values enables you to test the example demoshop
    // Do not change AuthenticationType unless you have specific reasons to do so
    
    // priv.pem
    if (is_file($this_config['private_key']))
        $conf_data['PRIVATEKEY']            = $this_config['private_key'];
    else
        $conf_data['PRIVATEKEY']            = $config['root_dir']."/plugins/payment/ideal/security/" . $this_config['private_key'];
    
    $conf_data['PRIVATEKEYPASS']        = $this_config['private_pass']; // passwd

    // cert.cer
    if (is_file ($this_config['cert_file']))
        $conf_data['PRIVATECERT']           = $this_config['cert_file'];
    else
        $conf_data['PRIVATECERT']           = $config['root_dir']."/plugins/payment/ideal/security/" . $this_config['cert_file'];
    
    $conf_data['AUTHENTICATIONTYPE']    = 'SHA1_RSA';

    // Certificate0 contains the signing certificate of your acquirer
    // This field should not be changed
    $conf_data['CERTIFICATE0']          = $config['root_dir']."/plugins/payment/ideal/security/" . 'ideal.cer';
    
    // Address of the iDEAL acquiring server
    // Use ssl://idealtest.secure-ing.com:443/ideal/iDeal during integration/test
    // Use ssl://ideal.secure-ing.com:443/ideal/iDeal only for production
    
    if ($this_config['testing'])
        $conf_data['ACQUIRERURL']       = 'ssl://idealtest.secure-ing.com:443/ideal/iDeal';
    else
        $conf_data['ACQUIRERURL']       = 'ssl://ideal.secure-ing.com:443/ideal/iDeal';
  
    // Do not change AcquirerTimeout unless you have specific reasons to do so
    $conf_data['ACQUIRERTIMEOUT']       = '10';
    
    // Default MerchantID '005010700' enables you to test the example demoshop
    // Your own Merchant ID can be retrieved via the iDEAL Dashboard
    $conf_data['MERCHANTID']            = $this_config['merchant_id'];
    $conf_data['SUBID']                 = $this_config['sub_id'];
    
    // MerchantReturnURL is the URL on your system that the customer is redirected to after the iDEAL payment.
    // This page should carry out the Status Request
    $conf_data['MERCHANTRETURNURL']     = $config['root_url']."/plugins/payment/ideal/thanks.php";
    
    $conf_data['CURRENCY']              = $this_config['currency'];
    
    // ExpirationPeriod is the timeframe during which the transaction is allowed to take place
    // Notation PnYnMnDTnHnMnS, where every n indicates the number of years, months,
    // days, hours, minutes and seconds respectively. E.g. PT1H indicates an expiration period of 1 hour.
    // PT3M30S indicates a period of 3 and a half minutes. Maximum allowed is PT1H (1 hour); minimum allowed is PT1M.
    $conf_data['EXPIRATIONPERIOD']      = 'PT10M';
    
    $conf_data['LANGUAGE']              = $this_config['language'];
    
    // Default description
    // Used when you do not want to use transaction specific descriptions
    $conf_data['DESCRIPTION']         = 'default description';
    
    // Default EntranceCode
    // Used when you do not want to use transaction specific entrance codes
    // See documentation for more info
    $conf_data['ENTRANCECODE']        = '15674022';
    
    // Remark the following line if you do not want a logfile
    //$conf_data['LOGFILE']             = $config['root_url'].'/plugins/payment/ideal/thinmpi.log';
    
    // Proxy settings (set the proxy like this: URL:PORT)
    //$conf_data['PROXY']               = 'proxy.at.int.atosorigin.com:8080';
    // The full path to the Acquirer
    //$conf_data['PROXYACQURL']         = 'https://www.acrm.de/ideal/iDeal';

    /*
    $file = fopen("config.conf", 'r');
    if($file) {
        while(!feof($file)) {
            $buffer = fgets($file);
            $buffer = trim($buffer);
            if(!empty( $buffer ) ) {
                $pos = strpos($buffer, '=');
                if($pos > 0 ) {
                  $dumb = trim(substr($buffer, 0, $pos));
                  if( !empty($dumb)  ) {
                        $conf_data[ strtoupper( substr($buffer, 0 , $pos) ) ] = substr($buffer, $pos +1);
                  }
                }
            }
        }
    }
    fclose($file);
    */
    
    return $conf_data;
}

?>