<?php

function postxml($dataArray) {
####### Vars
    $url = "ep.eurocommerce.gr";
    $uri = "/proxypay/apacsonline";
    $port = 443;

    $responseBody = '';
    $requestBody = prepareRequestBody($dataArray);
    $contentLength = strlen($requestBody);
        

####### Prepare post request headers and data
        $request = "POST $uri HTTP/1.1\r\n".
                   "Host: $url\r\n".
                   "User-Agent: Incredible\r\n".
                   "Connection: Keep-Alive\r\n".
                   "Content-Type: application/x-www-form-urlencoded\r\n".
                   "Content-Length: $contentLength\r\n\r\n".
                   "$requestBody\r\n";

####### Open socket
        $curl_handle = curl_init ();
        # curl_setopt ($curl_handle, CURLOPT_URL, $http_method . '://', $hostname . $cgi);
        curl_setopt ($curl_handle, CURLOPT_URL, 'http://' . $url . $uri);
        
        curl_setopt ($curl_handle, CURLOPT_SSLVERSION, 3);
        curl_setopt ($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($curl_handle, CURLOPT_POST, 1);
        curl_setopt ($curl_handle, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt ($curl_handle, CURLOPT_VERBOSE, 1);
                   
        $result = curl_exec ($curl_handle);
        curl_close ($curl_handle);

        return $result;
}


function post_xml($dataArray) {
####### Vars
    $url = "ep.eurocommerce.gr";
    $uri = "/proxypay/apacsonline";
    $port = 80;

    $responseBody = '';
    $requestBody = prepareRequestBody($dataArray);
    $contentLength = strlen($requestBody);

        
####### Prepare post request headers and data
        $request = "POST $uri HTTP/1.1\r\n".
                   "Host: $url\r\n".
                   "User-Agent: Incredible\r\n".
                   "Connection: Keep-Alive\r\n".
                   "Content-Type: application/x-www-form-urlencoded\r\n".
                   "Content-Length: $contentLength\r\n\r\n".
                   "$requestBody\r\n";

####### Open socket
    $socket = @fsockopen($url, $port, $errno, $errstr);
    if(!$socket) {
        return "socket error ($errno $errstr)";
    }

####### Send Post Data
        fputs($socket, $request);


####### read output from server
        while (!feof($socket)) {
                $line = @fgets($socket, 1024);
                $responseBody .= $line;
        }
        fclose($socket);
        return $responseBody;
}


function prepareRequestBody(&$array,$index=''){
        foreach($array as $key => $val) {
        if(is_array($val)){
        if($index){
                    $body[] = $this->prepareRequestBody($val,$index.'['.$key.']');
                }
                else {
                    $body[] = $this->prepareRequestBody($val,$key);
                }
            }
            else {
                if($index){
                    $body[] = $index.'['.$key.']='.urlencode($val);
                }
                else {
                    $body[] = $key.'='.urlencode($val);
                }
            }
    }
    return implode('&',$body);
}


function parse_xmltag ($postresult_xml, $tag) {
    $xmltag = ""; 
    $preg="/<".$tag.">(.*?)<\/".$tag.">/si";
    preg_match_all($preg, $postresult_xml, $tmpxmltag);
        foreach ($tmpxmltag[1] as $tmpcont){
            $xmltag .= $tmpcont;
            }
    return $xmltag;
}
?>
