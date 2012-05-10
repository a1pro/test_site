<?php                                                        
/*
*  Misc. user-side AJAX functions are contained in this file
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1640 $)
*
* Please direct bug reports,suggestions or feedbacks to the cgi-central support
* http://www.cgi-central.net/support/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*                                                                                 
*/
require_once './config.inc.php';
require_once ROOT_DIR . '/includes/pear/Services/JSON.php';

function ajaxResponse($resp){
    $j = & new Services_JSON();
    echo $j->encode($resp);
    return true; 
}
function ajaxError($msg){
    ajaxResponse(array('msg' => $msg));
}

function ajaxGetStates($vars){
    return ajaxResponse(db_getStatesForCountry($vars['country']));
}

function ajaxCheckUniqLogin($vars){
    global $db, $config;
    $login = htmlentities($vars['login']);
    $res = array('msg' => '', 'errorCode' => 0);
    do {
        // check for valid login first
        if ($vars['login'] == '') {
            $res['msg'] = sprintf(_SIGNUP_INVALID_USERNAME_2, $config['login_min_length'], $config['login_max_length']);
            $res['errorCode'] = 2;
            break;
        }
        if (strlen($vars['login']) < $config['login_min_length'] || strlen($vars['login']) > $config['login_max_length']){
            $res['msg'] = sprintf(_SIGNUP_INVALID_USERNAME_2, $config['login_min_length'], $config['login_max_length']);
            $res['errorCode'] = 3;
            break;
        }
        if (!preg_match(getLoginRegex(), $vars['login'])){
            $res['msg'] = $config['login_disallow_spaces'] ? _SIGNUP_INVALID_USERNAME : _SIGNUP_INVALID_USERNAME_W_SPACES;
            $res['errorCode'] = 4;
            break;
        }
        // check if it is available
        $r = $db->check_uniq_login($vars['login'], $vars['email'], $vars['pass'], 1);
        if (!$r){
            $res['msg'] = 
                sprintf(_UNIQ_LOGIN_EXSTS_TEXT, htmlentities($login)) . ".<br />" .
                _UNIQ_LOGIN_EXSTS_TEXT_1 . "<br />" .
                sprintf(_UNIQ_LOGIN_EXSTS_TEXT_2, "<a href='member.php' target='blank'>", "</a>");
            $res['errorCode'] = 1;
            break; 
        } else {
            $res['msg'] = sprintf(_UNIQ_LOGIN_FREE_TEXT, htmlentities($login));
            $res['errorCode'] = 0;
            break; 
        }
    } while (false);
    return ajaxResponse($res);    

    
}
function ajaxCheckCoupon($vars){
    global $db, $config;
    $coupon = htmlentities($vars['coupon']);
    $res = array('msg' => '', 'errorCode' => 0);
    $ret = $db->coupon_get($vars['coupon']);
    if (!is_array($ret) || !$ret['coupon_id']){
        $res['msg'] = $ret;
        $res['errorCode'] = 1;
    }
    return ajaxResponse($res);    
}
   

header("Content-type: text/plain; charset=UTF-8");
$vars = get_input_vars();
$vars['do'] = preg_replace('/[^a-zA-Z0-9_.,-]/',  '', $vars['do']);

switch ($vars['do']){
    case 'get_states':
        ajaxGetStates($vars);
        break;
    case 'check_uniq_login':
        ajaxCheckUniqLogin($vars);
        break;
    case 'check_coupon':
        ajaxCheckCoupon($vars);
        break;
    default:
        ajaxError('Unknown Request: ' . $vars['do']);
}

