<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 2256 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*/


/**
 * Class represent 2checkout API
 * http://www.2checkout.com/documentation/api/
 *
 */

class TwocheckoutAPI {
    var $_username, $_password;
    var $_apiBase = "https://www.2checkout.com/api/";
    var $_encoder;
    var $_sheme = null;
    var $_lastError = null;
    const METHOD_POST = 1;
    const METHOD_GET  = 2;

    function __construct($username, $password)
    {
        require_once INC_DIR . '/pear/Services/JSON.php';
        $this->_encoder = & new Services_JSON();

        $this->_username  = $username;
        $this->_password  = $password;

        $this->init();
    }

    /**
     * Return last accured error
     *
     */

    function getLastError() {
        $result = '';
        if (is_string($this->_lastError)) {
            $result = $this->_lastError;
        } elseif (is_array($this->_lastError)) {
            foreach ($this->_lastError as $error) {
                $result .= $error->code . ':' . $error->message . ';';
            }
        }
        return $result;
    }

    /**
     * Execute API call
     *
     * @param array $arguments parametrs of API call
     * @return mixed stdClass|false
     */

    function __call($name, $arguments)
    {
        if (!isset($this->_sheme[$name])) {
            fatal_error("Attempt to execute unspecified API call"); // throw new Exception();
        }

        $apiCall = $this->_sheme[$name];
        $apiURL  = $this->_apiBase . $apiCall->type . '/' . $apiCall->name;

        $ch = curl_init($apiURL);

        $headers = array (
            'Accept: application/json'
        );

        //curl configuration
        $options = array(
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 3, //sec
            CURLOPT_TIMEOUT        => 5, //sec CURLOPT_TIMEOUT_MS for ms
            CURLOPT_USERPWD        => $this->_username . ':' . $this->_password,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $arguments[0]
        );

        if (self::METHOD_POST == $apiCall->type) {
            $options[CURLOPT_POST] = true; //GET is default state
        }

        curl_setopt_array($ch, $options);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response) {
            $this->_lastError = "Can't connect to API url";
            return false;
        }
        //DEBUG
        $GLOBALS['db']->log_error('DEBUG twocheckuot_r:' . $response);
        $response = $this->_encoder->decode($response);

        if ($http_code>=400) {
            $this->_lastError = $response->errors;
            return false;
        }

        return $response;
    }

    function addAPICall($type, $name, $method=self::METHOD_POST)
    {
        $apiCall = new stdClass();
        
        $apiCall->name   = $name;
        $apiCall->type   = $type;
        $apiCall->method = $method;

        $this->_sheme[$name] = $apiCall; //name is unique
        return $this;
    }

    function init()
    {
        // description of IP can be found here
        // http://www.2checkout.com/documentation/api/
        $this->addAPICall('sales', 'detail_sale', self::METHOD_GET)
             ->addAPICall('sales', 'stop_lineitem_recurring', self::METHOD_POST)
             ->addAPICall('products', 'detail_product', self::METHOD_GET)
             ->addAPICall('acct', 'detail_contact_info', self::METHOD_GET);
    }
}