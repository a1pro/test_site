<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Members handling functions
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1640 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/

global $payment_additional_fields;
$payment_additional_fields = array(
);

function add_payment_field($name, $title, 
                            $type, $description='', $validate_func='',
                            $additional_fields=NULL){
    settype($additional_fields, 'array');
    global $payment_additional_fields;
    foreach ($payment_additional_fields as $k=>$v){
        if ($v['name'] == $name) {
            if ($v['validate_func'] && 
                ($v['validate_func'] != $validate_func)){
                $payment_additional_fields[$k]['validate_func'] = 
                        (array)$v['validate_func'];
                $payment_additional_fields[$k]['validate_func'][] = 
                    $validate_func;
            }
            return;
        }
    }    
    $payment_additional_fields[] = array_merge(
        $additional_fields,
        array(
            'name'          => $name,
            'title'         => $title,
            'type'          => $type,
            'description'   => $description,
            'validate_func' => $validate_func
        )
    );
}

?>
