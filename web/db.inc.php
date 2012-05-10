<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/**
* Database base class
* should be parent class for all database plugins
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Database base class
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1640 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/


class amember_db {
    var $config;
    function db(& $config){
        $this->config = $config;
    }
    function escape_array(&$arr){
        if (!is_array($arr)) return array();
        $v = array();
        foreach ($arr as $k=>$vv)
            $v[$k] = $this->escape($vv);
        return $v;
    }
    function encode_data(& $data){
        return serialize((array)$data);
    }
    function decode_data(& $data){
        return (array)unserialize($data);
    }

}

?>
