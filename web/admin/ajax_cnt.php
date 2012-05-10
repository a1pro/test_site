<?php														
/*
*  Misc. user-side AJAX functions are contained in this file
*
*	 Author: Alex Scott
*	  Email: alex@cgi-central.net
*		Web: http://www.cgi-central.net
*	FileName $RCSfile$
*	Release: 3.2.3PRO ($Revision: 1640 $)
*
* Please direct bug reports,suggestions or feedbacks to the cgi-central support
* http://www.cgi-central.net/support/
*																		  
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*																				 
*/
require_once '../config.inc.php';
require "login.inc.php";
require_once $config['root_dir'] . '/includes/pear/Services/JSON.php';

function ajaxResponse($resp)
{
	$j = & new Services_JSON();
	echo $j->encode($resp);
	return true; 
}

function ajaxError($msg)
{
	ajaxResponse(array('msg' => $msg));
}


function get_country($country)
{
  global $db; 
  $query = $db->query($sql="SELECT * FROM {$db->config[prefix]}countries 
  WHERE country='$country';");
  $country=mysql_fetch_array($query);
	ajaxResponse(array('country' => $country['country'], 'title' => $country['title'], 'tag' => $country['tag'], 'errorCode' => 0));
}

function get_state($country)
{
  global $db; 
  $query = $db->query($sql="SELECT * FROM {$db->config[prefix]}states 
  WHERE state='$country';");
  $state=mysql_fetch_array($query);
	ajaxResponse(array('country' => $state['country'], 'state' => $state['state'], 'title' => $state['title'], 'tag' => $state['tag'], 'errorCode' => 0));
}


function NewCountry($country, $title, $tag) {
  global $db; 
  if (!CheckUniqCountry($country)) 
      return 'Country with same country already exist';
  $query = @$db->query($sql="INSERT INTO {$db->config[prefix]}countries (country, title, tag, status) VALUES ('$country', '$title', $tag, 'added');");
  if ($query)
      return 'Country has been created successfully';
      
  return 'Country has not been added';
}

function EditCountry($country, $title, $tag) {
  global $db; 
  
  $query = @$db->query($sql="SELECT * FROM {$db->config[prefix]}countries  WHERE country='$country';");
  $countries=mysql_fetch_array($query);
  $status= ($countries[status]=='added') ? "" : ", status='changed'";
  
  $query = @$db->query($sql="UPDATE {$db->config[prefix]}countries SET title='$title', tag=$tag $status WHERE country='$country';");
  if ($query)
      return "Country has been changed successfully";
      
  return 'Country has not been changed';
}

function CheckUniqCountry($country) {
  global $db; 
  $query = @$db->query($sql="SELECT * FROM {$db->config[prefix]}countries 
  WHERE country='$country';");
  if (mysql_num_rows($query)==0) {
      return true;
  }
  return false;
}

function save_country($country, $title, $tag, $act) {
  global $db;
  $country = preg_replace('/[^a-zA-Z0-9]/',  '', $country);
  $country = strtoupper($country);
  $title = $db->escape($title);
  $country=substr($country, 0, 2);
  settype($tag, 'integer');
  $tag=abs($tag);
  settype($act, 'integer');
  
  do {
      if (!$country) {
          $response='Country require';
          break;
      }
      if (!$title) {
          $response='Title require';
          break;   
      }
      
      switch ($act) {
          case '1': //new country
              $response=NewCountry($country, $title, $tag);
              break;
          case '2':  //edit country
              $response=EditCountry($country, $title, $tag);
              break;
          default :
              $response='Unknown action';
      }
      break;
      
  } while (0);
  ajaxResponse(array('msg' => $response, 'errorCode' => 0));
}

function NewState($country, $state, $title, $tag) {
  global $db; 
  if (!CheckUniqState($country, $state)) 
      return 'State with same country and state already exist';
  $query = @$db->query($sql="INSERT INTO {$db->config[prefix]}states (country, state, title, tag, status) VALUES ('$country', '$state', '$title', $tag, 'added');");
  if ($query)
      return 'State has been created successfully';
      
  return 'State has not been added';
}

function EditState($country, $state, $title, $tag) {
  global $db; 
  
  $query = @$db->query($sql="SELECT * FROM {$db->config[prefix]}states  WHERE state='$country';");
  $states=mysql_fetch_array($query);
  $status= ($states[status]=='added') ? "" : ", status='changed'";
  
  $query = @$db->query($sql="UPDATE {$db->config[prefix]}states SET title='$title', tag=$tag $status WHERE country='$country' AND state='$state';");
  if ($query)
      return 'State has been changed successfully';
      
  return 'State has not been changed';
}


function CheckUniqState($country, $state) {
  global $db; 
  $query = @$db->query($sql="SELECT * FROM {$db->config[prefix]}states 
  WHERE country='$country' AND state='$state';");
  if (mysql_num_rows($query)==0) {
      return true;
  }
  return false;
}

function save_state($country, $state, $title, $tag, $act) {
  global $db;
  $country = preg_replace('/[^a-zA-Z0-9]/',  '', $country);
  $country = strtoupper($country);
  $state = preg_replace('/[^a-zA-Z0-9_-]/',  '', $state);
  $state = strtoupper($state);
  $title = $db->escape($title);
  $country=substr($country, 0, 2);
  $state=substr($state, 0, 12);
  settype($tag, 'integer');
  $tag=abs($tag);
  settype($act, 'integer');
  
  do {
      if (!$country) {
          $response='Country require';
          break;
      }
      if (!$state) {
          $response='State require';
          break;   
      }
      if (!$title) {
          $response='Title require';
          break;   
      }
      
      switch ($act) {
          case '1': //new country
             $response=NewState($country, $state, $title, $tag);
              break;
          case '2':  //edit country
              $response=EditState($country, $state, $title, $tag);
              break;
         default :
          $response='Unknown action';
      }
      break;
  } while (0);
  ajaxResponse(array('msg' => $response, 'errorCode' => 0));
}



function load_countries()
{
  $db = & amDb(); 
  $response='';
  $countries = @$db->selectCol("SELECT country as ARRAY_KEY, title
            FROM ?_countries WHERE tag>=0
            ORDER BY tag DESC, title");
	foreach ($countries as $country=>$title) {
		$response .= "<option value=\"$country\">$title</option>";
	}
	ajaxResponse(array('msg' => $response, 'errorCode' => 0));
}

function load_countries_disabled()
{ 
  $db = & amDb(); 
  $response='';
  $countries = @$db->selectCol("SELECT country as ARRAY_KEY, title
            FROM ?_countries WHERE tag<0
            ORDER BY tag, title");
	foreach ($countries as $country=>$title) {
		$response .= "<option value=\"$country\">$title</option>";
	}
	ajaxResponse(array('msg' => $response, 'errorCode' => 0));
}

function load_states($country)
{
  $db = & amDb(); 
  $response='';
  $states = @$db->selectCol("SELECT state as ARRAY_KEY, title
            FROM ?_states WHERE tag>=0 AND country='$country' 	 
            ORDER BY tag DESC, title");
	foreach ((array)$states as $state=>$title) {
		$response .= "<option value=\"$state\">$title</option>";
	}
	ajaxResponse(array('msg' => $response, 'errorCode' => 0));
}

function load_states_disabled($country)
{ 
  $db = & amDb(); 
  $response='';
  $states = @$db->selectCol("SELECT state as ARRAY_KEY, title
            FROM ?_states WHERE tag<0 AND country='$country' 	 
            ORDER BY tag DESC, title");
	foreach ($states as $state=>$title) {
		$response .= "<option value=\"$state\">$title</option>";
	}
	ajaxResponse(array('msg' => $response, 'errorCode' => 0));
}

//disabled and activate coutries
function disabled_countries($countries)
{
  global $db;
  $countries=preg_replace('/[^a-zA-Z0-9,]/',  '', $countries);
  $countries=explode(',', $countries);
  foreach ($countries as $key=>$value) {
      $countries[$key]="'$value'";
  }
  $countries=join(',', $countries);
  $query=$db->query($sql="UPDATE {$db->config[prefix]}countries SET tag=0-1-tag WHERE country IN ($countries)");   
  $response='OK';
	ajaxResponse(array('msg' => $response, 'errorCode' => 0));
}

//disabled and activate states
function disabled_states($states)
{
  global $db;
  $states = preg_replace('/[^a-zA-Z0-9_,-]/',  '', $states);
  $states=explode(',', $states);
  foreach ($states as $key=>$value) {
      $states[$key]="'$value'";
  }
  $states=join(',', $states);
  $query=$db->query($sql="UPDATE {$db->config[prefix]}states SET tag=0-1-tag WHERE state IN ($states)");   
  $response='OK';
	ajaxResponse(array('msg' => $response, 'errorCode' => 0));
}


function get_expire($product_id, $begin_date)
{
    global $db;
    $pr = get_product($product_id);
    $expire_date = $pr->get_expire($begin_date);
	ajaxResponse(array('expire_date' => $expire_date, 'errorCode' => 0));
}

function calculate_tax($product_id, $member_id, $amount, $incl_tax){
    global $db;
    $pr = $db->get_product($product_id);
    // Not a subject of tax;
    if(!$pr['use_tax']) ajaxResponse(array('tax'=>0,"errorCode"=>0));
    $tax_value = get_member_tax($member_id);
    if(!$tax_value) ajaxResponse(array('tax'=>0, 'errorCode'=>0));
    // Got tax, now apply it to price:
    if($incl_tax==1){
        $tax = $amount - $amount/(1+$tax_value/100);
    }else{
        $tax = $amount*$tax_value/100;
    }
    ajaxResponse(array("tax"=>sprintf("%01.2f",$tax), "errorCode"=>0));
}


function affiliate_search($search){
    global $db;
    $search = $db->escape($search);
    $q = $db->query("select member_id, login, name_f, name_l, email
                    from {$db->config[prefix]}members
                    where is_affiliate>0 and (
                        login like '%".$search."%' or
                        email like '%".$search."%' or
                        member_id like '%".$search."%' or
                        name_f like '%".$search."%' or
                        name_l like '%".$search."%' or
                        concat(name_f, ' ', name_l) like '%".$search."%'
                            )");
   $num_rows = mysql_num_rows($q);
   if($num_rows > 20){
        ajaxResponse(array("msg"=>"Too many results. Please select different criteria",   "errorCode"=>1));
   }else if($num_rows == 0 ){
        ajaxResponse(array("msg"=>"Nothing was found with your search",   "errorCode"=>2));
   }else{
       $ret = array();
       while($r = mysql_fetch_assoc($q)){
           $ret[]= $r;
       }
       ajaxResponse(array("ret"=>$ret, "errorCode"=>0));
   }
}

function change_affiliate($member_id, $aff_id){
    global $db;
    $member = $db->get_user($member_id);
    if(!$member){
       ajaxResponse(array("msg"=>"User not found!", "errorCode"=>1));
    }else{
       $member[aff_id] = $aff_id;
       $db->update_user($member['member_id'], $member);
       ajaxResponse(array("msg"=>"Success", "errorCode"=>0));
    }

}
header("Content-type: text/plain; charset=UTF-8");
$vars = get_input_vars();
$vars['do'] = preg_replace('/[^a-zA-Z0-9_.,-]/',  '', $vars['do']);

switch ($vars['do']){
	case 'disabled_countries':
		disabled_countries($vars['countries']);
		break;
	case 'disabled_states':
		disabled_states($vars['states']);
		break;
	case 'load_countries':
		load_countries();
		break;
	case 'load_countries_disabled':
		load_countries_disabled();
		break;
	case 'load_states':
		load_states($vars['country']);
		break;
	case 'load_states_disabled':
		load_states_disabled($vars['country']);
		break;
	case 'get_country':
		get_country($vars['country']);
		break;
	case 'get_state':
		get_state($vars['state']);
		break;
	case 'save_country':
		save_country($vars['country'], $vars['title'], $vars['tag'], $vars['act']);
		break;
	case 'save_state':
    save_state($vars['country'], $vars['state'], $vars['title'], $vars['tag'], $vars['act']);
		break;
        case 'get_expire':
                get_expire($vars['product_id'], $vars['begin_date']);
                break;
        case 'calculate_tax':
                calculate_tax($vars['product_id'], $vars['member_id'], $vars['amount'], $vars['incl_tax']);
                break;
        case 'affiliate_search':
                affiliate_search($vars['search']);
                break;
        case 'change_affiliate':
                change_affiliate($vars['member_id'], $vars['aff_id']);
                break;
	default:
		ajaxError('Unknown Request');
}

?>
