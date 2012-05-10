<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Product class and functions
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5228 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/
define('MAX_SQL_DATE',  '2037-12-31');
define('RECURRING_SQL_DATE',  '2012-12-31');

global $product_fields;
$product_fields = array(
    array(
        'name'         => 'product_id',
        'title'        => 'Product #',
        'type'         => 'text'
    ),
    array(
        'name'         => 'title',
        'title'        => 'Title <font color=red>*</font>',
        'type'         => 'text'
    ),
    array(
        'name'         => 'description',
        'title'        => 'Description <font color=red>*</font>',
        'type'         => 'text'
    ),
    array(
        'name'         => 'price',
        'title'        => 'Price <font color=red>*</font>',
        'type'         => 'money'
    )
);

global $product_additional_fields;
$product_additional_fields = array(
    array(
        'name'         => 'expire_days',
        'title'        => 'Duration <font color=red>*</font>',
        'type'         => 'period',
        'description'  => "Please enter subscription period for this product<br />
        it can also be set to lifetime, or to fixed date",
        'validate_func'=> 'validate_period'
    )
);

function add_product_field(
        $name, $title, $type,
        $description='',
        $validate_func='',
        $additional_fields=NULL){
    settype($additional_fields, 'array');
    global $product_additional_fields;
    // make special order for some fields
    switch ($name){
        case 'is_recurring': 
            $additional_fields['insert_after'] = 'expire_days'; break;
        case 'trial1_price': 
            $additional_fields['insert_after'] = 'expire_days'; break;
        case 'trial1_days': 
            $additional_fields['insert_after'] = 'expire_days'; break;
        case 'trial2_price': 
            $additional_fields['insert_after'] = 'trial1_days'; break;
        case 'trial2_days': 
            $additional_fields['insert_after'] = 'trial1_days'; break;
        case 'trial_group': 
            $additional_fields['insert_before'] = '##11'; break;
        case 'rebill_times': 
            $additional_fields['insert_before'] = '##11'; break;
        case 'use_tax': 
            $additional_fields['insert_before'] = '##11'; break;
    }
    if (preg_match('/.+_currency$/', $name)){
            $additional_fields['insert_before'] = '##11'; 
    }
    //
    foreach ($product_additional_fields as $k=>$v){
        if ($v['name'] == $name) {
            if ($v['validate_func'] &&
                ($v['validate_func'] != $validate_func)){
                $product_additional_fields[$k]['validate_func'] =
                        (array)$v['validate_func'];
                $product_additional_fields[$k]['validate_func'][] =
                    $validate_func;
            }
            return;
        }
    }
    $to_add = array_merge(
        $additional_fields,
        array(
            'name'          => $name,
            'title'         => $title,
            'type'          => $type,
            'description'   => $description,
            'validate_func' => $validate_func
        )
    );
    if ($to_add['insert_after'] || $to_add['insert_before']){
        $found_key = -1;
        $search = $to_add['insert_after'] ? $to_add['insert_after'] :
            $to_add['insert_before'];
        foreach ($product_additional_fields as $k=>$v)
            if ($v['name'] == $search)
                $found_key = $k;
        if ($found_key<0)
            $found_key = count($product_additional_fields)-1;
        $replacement = $to_add['insert_after'] ?
            array($product_additional_fields[$found_key], $to_add) :
            array($to_add, $product_additional_fields[$found_key]);            
        array_splice($product_additional_fields, 
          $found_key,1,
          $replacement);
    } else
        $product_additional_fields[] = $to_add;
}

add_product_field('start_date', 'Fixed subscription start date',
    'text', "By default, aMember calculates subscription start date<br />
    according to current date, but you may set it to fixed date here<br />
    Please enter date in format yyyy-mm-dd (for example 2006-02-28)<br />
    IN MOST CASES THIS FIELD SHOULD BE KEPT EMPTY
    ", '', array('insert_after ' => 'expire_days'));
add_product_field('##11', 'Product URLs', 'header');
add_product_field('url',
    'Product URL',
    'text',
    "Please enter URL of protected area.<br />
     For example: <i>/mydir/protected_dir1/</i><br />
     or <i>http://area.yoursite.com/xxx/</i><br />
     or <i>xxx/</i> or <i>../xxx/</i> -<br />
     last case case it is considered as relative<br />
     to configured aMember Pro root URL<br />
     PLEASE NOTE - folder will not become protected<br />
     if you just enter it here. Please read<br />
     aMember Pro manual about
      <a target=_blank href='http://manual.amember.com/Protection_Methods'>Protection Plugins</a>.
     ",
    'validate_product_url');

add_product_field('add_urls',
    'Additional URLs',
    'textarea',
    'Enter additional URLs using this format:<br />
    /some_url/|URL title to display<br /><br />
    These URLs will be displayed on the member.php page<br />
    in addition to the Product URL field',
    '');

if ($GLOBALS['config']['use_tax'])
    add_product_field('use_tax',
    'Add Tax',
    'checkbox',
    'add tax value to price',
    '');

class product {
    // should contain keys:
    // product_id, title, description, price
    // expire_days, resources (list)
    var $config;
    function product($config){
        $this->config = $config;
    }
    function get_price(){
        return round($this->config['price'], 2);
    }
    /**
     * Function return expiration date for product,
     * calculated from given $field
     * In case of error it returns error message as string
     * @param int member_id to check existing subscriptions
     * @return string date in format yyyy-mm-dd
     */
    function get_start($member_id=null){
        global $db;
	    $date = date('Y-m-d');
        if (preg_match('/^\d\d\d\d-\d\d-\d\d$/', $this->config['start_date']))
            return $this->config['start_date']; /// fixed start date
	    if ($this->config['renewal_group'] < 0)
	        return $date;
        if ($member_id > 0){            
		    $payments = & $db->get_user_payments(intval($member_id), 1);
		    foreach ($payments as $p){
		        $pr = $db->get_product($p['product_id']);
		        if ((($p['product_id'] == $this->config['product_id']) || 
		             ($pr['renewal_group'] == $this->config['renewal_group'])) &&
		             ($p['expire_date'] > $date)
		            ){ 
		            $date = $p['expire_date'];
		        }
		    }
        }
	    list($y,$m,$d) = split('-', $date);
	    $date = date('Y-m-d', mktime(0,0,0,$m, $d, $y));
	    return $date;
    }
    /**
     * Function return expiration date for product,
     * calculated from given $field
     * In case of error it returns error message as string
     * @param string begin_date yyyy-mm-dd
     * @param string field field name: expire_days, trial1_days or trial2_days
     * @return string date in format yyyy-mm-dd or error message
     */
    function get_expire($begin_date, $field='expire_days', $terms=null){
        /// get start calculation date
        list($y,$m,$d) = split('-', $begin_date);
        $tm = mktime(0,0,0, $m, $d, $y);
        // COUPON_NOT_USED functionality removed 
        $koef = 1.0;
        /// get days value from field, and handle special
		if ($terms)
			switch ($pf = $terms->usedPriceFields[0]){
				case 'trial1_price' : $field = 'trial1_days' ; break;
				case 'trial2_price' : $field = 'trial2_days' ; break;
				case 'price':         $field = 'expire_days' ; break;
				default:              fatal_error('Unknown price field used: [' . $pf . ']');
			}

		$days = $this->config[$field];
			
        list($count, $unit) = parse_period($days);
        if ($unit == 'error')
            return "Incorrect value for [$field] in product #".$this->config['product_id'];

        if ($unit == 'fixed')
            return $count;

        switch ($unit){
            case 'd':
                $tm2 = $this->expire_date_add($tm, mktime(0,0,0, $m, $d+$count, $y), $koef);
                break;
            case 'm':
                $tm2 = $this->expire_date_add($tm, mktime(0,0,0, $m + $count, $d, $y), $koef);
                break;            
            case 'y':                
                $tm2 = $this->expire_date_add($tm, mktime(0,0,0, $m, $d, $y + $count), $koef);
                break;
        }
        if ($tm2 < $tm) // overflow, assign fixed "lifetime" date
            return MAX_SQL_DATE;
        return date('Y-m-d', $tm2);
    }

    function expire_date_add($tm1, $tm2, $koef){
        $diff = $tm2 - $tm1;
        $new_diff = $diff * $koef;
        return $tm1 + $new_diff;
    }



    function _getTextPeriod($days, $skip_one_c){
        list($c, $u) = parse_period($days);
        switch ($u){
            case 'd': $uu = $c==1 ? 'day': 'days'; break;
            case 'm': $uu = $c==1 ? 'month' : 'months'; break;
            case 'y': $uu = $c==1 ? 'year' : 'years'; break;
        }
        $cc = $c;
        if ($c == 1) $cc = $skip_one_c ? '' : 'one';
        return "$cc $uu";
    }
    function _getTextPrice($number, $currency, $startWithUpperCase=false){
        if ($number == 0.0) return $startWithUpperCase ? "Free" : "free";
        return "$currency$number";
    }
    /**
     * Function returns product subscription terms as text
     * @return string
     * @see price, expire_days, (is_recurring, trial1_price, trial1_days) - if enabled
     *      start_date, (currency, rebill_times) - if enabled
     */
    function getSubscriptionTerms(){
        extract($this->config);
        global $config;
        
        if (!$currency) $currency = ($config['currency']) ? $config['currency'] : '$'; // @todo product currency
        $ret = "";
        if (is_null($price) || !strlen($expire_days)) 
            return "";
        if ($config['terms_is_price']) return $this->_getTextPrice($price, $currency, $ret == "");
        /// START_DATE
        if ($start_date){ // trials ignored
            $pr = $this->_getTextPrice($price, $currency, $ret == "");
            if ($is_recurring && $price) $each = "each ";
            $sd = strftime($config['date_format'], strtotime($start_date));
            $ed = strftime($config['date_format'], strtotime($this->get_expire($start_date)));
            $ret .= "$pr for period $sd - $ed";
        } elseif (MAX_SQL_DATE == $expire_days){ 
            $pr = $this->_getTextPrice($price, $currency, $ret == "");
            if ($price == 0)
                $ret .= "Free lifetime subscription";
            else
                $ret .= "$pr for lifetime subscription";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expire_days)){ 
            $ed = strftime($config['date_format'], strtotime($expire_days));
            $pr = $this->_getTextPrice($price, $currency, $ret == "");
            $ret .= "$pr for period up to $ed";
        } else {
            if ($trial1_days){
                $pr = $this->_getTextPrice($trial1_price, $currency, $ret == "");
                $pp = $this->_getTextPeriod($trial1_days, 1);
                $ret .= "$pr for the first $pp. Then ";        
            }
            $pr = $this->_getTextPrice($price, $currency, $ret == "");
            if ($is_recurring && $price) $each = "each ";
            $ret .= "$pr for $each".$this->_getTextPeriod($expire_days, $is_recurring);
            if ($is_recurring && $rebill_times) {
                if ($trial1_days) $rebill_times--;
                $ret .= ", for $rebill_times installments";
            }
        }            
        return preg_replace('/[ ]+/', ' ', $ret); 
    }
}

function get_product($product_id){
    if (!$GLOBALS['db']) $GLOBALS['db'] = & instantiate_db();
    global $db;
    $conf = $db->get_product($product_id); //return database record for product
    return new product($conf);
}

function get_products_options() {
    $result = array();
    foreach ($GLOBALS['db']->get_products_list() as $product) {
        $result[$product['product_id']] = $product['title'];
    }

    return $result;
}

////////////////////// validate functions //////////////////////////////
function validate_period(&$p, $field_name, $field){
    if (($p->config[$field_name] == '') && ($field_name != 'expire_days'))
        return ""; // allow empty values for trial fields

    list($c,$u) = parse_period($p->config[$field_name]);
    
    if ($u == 'error')
        return "Wrong format of \"$field[title]\" field";
}

function validate_product_url(&$p, $field){
    $url = $p->config['url'];
}

function product_sort_cmp($a, $b){
    $c = intval($a['order']) - intval($b['order']);
    if ($c) return $c;
    return strcmp($a['title'], $b['title']);
}

class PaymentTerms {
    var $lines = array();
    var $subtotal;
    var $tax;
    var $discount;
    var $total;
    var $usedPriceFields;
    function calculate(){
        $this->subtotal = $this->discount = $this->tax = $this->total = 0.0;
        foreach (array_keys($this->lines) as $k){
            $l = & $this->lines[$k];
            $l->calculate();
            $this->subtotal += $l->subtotal;
            $this->discount += $l->discount;
            $this->tax      += $l->tax;
            $this->total    += $l->total;
        }
        $this->subtotal = round($this->subtotal, 2);
        $this->discount = round($this->discount, 2);
        $this->tax = round($this->tax, 2);
        $this->total = round($this->total, 2);
    }
    function setUsedPriceFields($fields){
    	$this->usedPriceFields = $fields;  
    }
}

class PaymentTermsLine {
    var $product_id; // product id
    var $title;      // product title
    var $price;      // original price
    var $qty = 1;    // quantity
    var $tax = 0.0;  // tax amount
    var $discount = 0.0; // discount value
    var $subtotal;   // price * qty
    var $total;      // line total
    function PaymentTermsLine($product_id, $title, $price, $qty=1){
        $this->product_id = $product_id;
        $this->title = $title;
        $this->price = $price;
        $this->qty = $qty;
    }
    function calculate(){
        $this->subtotal = round($this->price * $this->qty, 2);
        $this->total    = round($this->subtotal + $this->tax - $this->discount, 2);
    }
}


class PriceCalculator {
    /// input variables
    var $_items = array();
    var $_tax = null;
    var $_couponDiscount = 0.0;
    var $_couponProducts = null;
    var $_priceField = array('price'); 

    /**
     * add Product for calculation
     */
    function addProduct($product_id, $qty=1){
        $this->_items[$product_id] = $qty;
    }
    /**
     * add Product from array with qty = 1
     * @param array
     */
    function addProducts($product_ids){
        foreach ((array)$product_ids as $pid)
            if (intval($pid) > 0)
                $this->addProduct(intval($pid), 1);
    }
    function emptyProducts(){
        $this->_items = array();
    }
    
    /**
     * Set product fields, it defines where class will seek for product price
     * @param array Array of field names, class will try from first to last
     * and use first where value is set
     * @example $pc->setPriceField('price'); 
     * @example $pc->setPriceField('trial1_price', 'price');
     */
    function setPriceFields($field){
        $this->_priceField = (array)$field;
    }
    /**
     * Function sets price fields according to paysystem type
     * @param string paysys_id
     */
    function setPriceFieldsByPaysys($paysys_id){
        $built_in_trials = false;
        if ($paysys_id && $paysys = get_paysystem($paysys_id))
            $built_in_trials = $paysys['built_in_trials'];
        if ($built_in_trials){
            $this->setPriceFields('price');
        } else {
            $this->setPriceFields(array('trial1_price', 'price'));
        }
    }
    
    function setTax($tax){
        $this->_tax = $tax;
    }

    function setCouponDiscount($couponDiscount, $couponProducts = null){
        $this->_couponDiscount = trim($couponDiscount);
        $couponProducts = count($couponProducts) ? array_filter($couponProducts) : null;
        $this->_couponProducts = $couponProducts;
    }
    /**
     * Function calculates PaymentTerms according to given params
     * @return PaymentTerms payment terms object
     */
    function & calculate(){
        global $db;
        $terms = & new PaymentTerms();
        $products = array();
        $usedPriceFields = array();
        // get product prices
        foreach ($this->_items as $pid=>$qty){
            $p = $products[$pid] = $db->get_product($pid);
            $price = 0.0;
            foreach ($this->_priceField as $f)
                if ($p[$f] != '') { $price = $p[$f]; $usedPriceFields[] = $f; break; }
            $terms->lines[$pid] = & new PaymentTermsLine($pid, $p['title'], $price, $qty); 
        }
        $terms->calculate();
        // calculate discounts
        if (preg_match('/^(\d+(\.\d+)*)\s*%$/', $this->_couponDiscount, $regs)){
            $discount = doubleval($regs[1]);
            if ($discount > 100.00) $discount = 100.00;
            foreach ($terms->lines as $pid => $line)
                if ($this->_couponIncludesProduct($pid))
                    $terms->lines[$pid]->discount = round($line->subtotal * $discount / 100, 2);
        } elseif ($this->_couponDiscount > 0.0){
            $discount = doubleval($this->_couponDiscount);
            $this->_calculateAbsoluteDiscount($terms, $discount);
        }
        $terms->calculate();
        // calculate taxes
        if ($this->_tax > 0){
            foreach ($terms->lines as $pid => $line){
                if ($products[$pid]['use_tax'])
                    $terms->lines[$pid]->tax = round($terms->lines[$pid]->total * $this->_tax / 100, 2);
            }
        }

        $terms->calculate();
        $terms->setUsedPriceFields($usedPriceFields);
        return $terms;
    }
    
    function _couponIncludesProduct($pid){
        return empty($this->_couponProducts) || in_array($pid, (array)$this->_couponProducts);
    }

    function _calculateAbsoluteDiscount(& $terms, $discount){
        $total_discountable = 0;
        
        $couponProducts = $this->_couponProducts;
        if ($couponProducts) 
            $couponProducts = array_intersect($couponProducts, array_keys($this->_items)); 
        else
            $couponProducts = array_keys($this->_items);
        if (!$couponProducts) return;
        
        foreach ($couponProducts as $pid)
            if ($this->_couponIncludesProduct($pid))
                $total_discountable += $terms->lines[$pid]->total;
        if ($total_discountable > 0)                
            $k = $discount / $total_discountable;
        else
            $k = 0;
        $last_pid = array_pop($couponProducts);
        $to_discount = $discount;
        foreach ($couponProducts as $pid){
            $terms->lines[$pid]->discount = round($terms->lines[$pid]->total * $k, 2);
            $to_discount -= $terms->lines[$pid]->discount;
        }
        // set last discount
        $terms->lines[$last_pid]->discount = min(round($to_discount, 2), $terms->lines[$last_pid]->total); 
        
    }

}

/**
 * Functions checks if first product from the list has trial
 * if yes, it returns "trial1_days" value, if not, it returns null
 * @param array|int Products ID array or single value
 * @return string|null Trial1_days value or null if empty 
 */
function product_get_trial($product_id){
    global $db;
    if (is_array($product_id)) $product_id = $product_id[0];
    $p = $db->get_product($product_id);
    return $p['trial1_days'];
}

function display_agreement($data){
    global $t, $error;
    $t->assign('data', $data);
    $t->assign('error', $error);
    $t->display('agreement.html');
}

function get_product_requirements_for_member($member_id){
    global $db;
    $dat = date('Y-m-d');
    $res = array();
    foreach ($db->get_user_payments($member_id, 1) as $p){
        if (($p['begin_date'] < $dat) && ($p['expire_date'] < $dat))
            $res['EXPIRED-'.$p['product_id']]++;
        elseif (($p['begin_date'] <= $dat) && ($p['expire_date'] >= $dat))
            $res['ACTIVE-'.$p['product_id']]++;
    }
    return array_keys($res);
}

function check_product_requirements($product_ids, $have=''){
    global $db;
    $error = array();

    $will_have = $have ? (array)$have : array();
    foreach ($product_ids as $product_id)
        $will_have[] = 'ACTIVE-'.$product_id;
    $will_have = array_unique($will_have);

    foreach ($product_ids as $product_id){
        $pr = $db->get_product($product_id);
        if ($pr['require_other']){
            $ro = array();
            foreach ($pr['require_other'] as $s)
                if ($s) $ro[] = $s; // skip empty requirement
            if ($ro && !array_intersect($ro, $will_have)) {
                $titles = array();
                foreach ($ro as $s){
                    if (preg_match('/^ACTIVE-(\d+)$/', $s, $args)){
                        $prt = $db->get_product($args[1]);
                        $titles[] = '"'. $prt['title'] . '"';
                    }
                }
                if ($titles){
                    $error[] = "\"{$pr[title]}\" can be ordered ".
                           "along with these products/subscriptons only: " .
                           join(', ', $titles);
                    continue;
                }
                $titles = array();
                foreach ($ro as $s){
                    if (preg_match('/^EXPIRED-(\d+)$/', $s, $args)){
                        $prt = $db->get_product($args[1]);
                        $titles[] = '"'. $prt['title'] . '"';
                    }
                }
                if ($titles){
                    $error[] = "\"{$pr[title]}\" can be ordered ".
                           "only if you have expired subscription ".
                           "for these products: " .
                           join(', ', $titles);
                    continue;
                }
            }
        }
        if ($pr['prevent_if_other']){
            $ro = array(); // list of disallowances
            foreach ($pr['prevent_if_other'] as $s)
                if ($s) $ro[] = $s; // skip empty requirements
            if ($ro && ($problems=array_intersect((array)$ro, (array)$have))) {
                /// here is disallowance, lets display it
                $titles = array();
                foreach ($problems as $s){
                    if (preg_match('/^ACTIVE-(\d+)$/', $s, $args)){
                        $prt = $db->get_product($args[1]);
                        $titles[] = '"'. $prt['title'] . '"';
                    }
                }
                if ($titles){
                    $error[] = "\"{$pr[title]}\" cannot be ordered ".
                           "because you already have active subscription(s) to: " .
                           join(', ', $titles);
                }
                $titles = array();
                foreach ($problems as $s){
                    if (preg_match('/^EXPIRED-(\d+)$/', $s, $args)){
                        $prt = $db->get_product($args[1]);
                        $titles[] = '"'. $prt['title'] . '"';
                    }
                }
                if ($titles){
                    $error[] = "\"{$pr[title]}\" cannot be ordered ".
                           "because you already have expired subscription(s) ".
                           "for: " .
                           join(', ', $titles);
                }
            }
        }
    }
    return $error;
}

add_product_field('##12', 'Product Availability/Visibilty', 'header');
add_product_field('scope', 'Scope',
    'select', "Limits who can order this product<br />
    (Does not affect existing customers)",
    '',
    array('options'=>array(
        ''         => 'Visible for all',
        'disabled' => 'Disabled (hidden both on signup.php and member.php)',
        'signup'   => 'Only Signup (hide from member.php page)',
        'member'   => 'Only Members having completed subscriptions (hide from signup page)'
    )));

add_product_field('order', 'Sorting order',
    'text', "This is a numeric field. Products<br />
    will be sorted according to this number, then alphabetically
    ");
add_product_field('price_group', 'Price Group ID',
    'text', "This is a numeric field. Products with a negative price_group<br />
    will not be displayed on the default Signup page. You can link to<br />
    an alternate Signup page like this 'signup.php?price_group=-1'<br /> to display
    products ONLY from Price Group -1. You can enter<br />
    comma-separated ',' lists of pricegroups as well
    ");
add_product_field('renewal_group', 'Renewal Group',
    'text', "Value in this field defines how aMember will calculate<br />
    subscription start date when user renews his membership.<br />
    Please read <a href='http://manual.amember.com/Product_Renewal_Group_Explained' target=_blank>this article</a> to get idea of this feature.<br />
    In short - set the same values for products offering the same level of access<br />
    - set different values for products offering different level of access
    ");
add_product_field('need_agreement', 'Display Agreement',
    'checkbox', "Specify whether the user must agree to your Customer<br />
    Agreement before being allowed to proceed with payment.<br />
    Please put your agreement text into template:<br />
    <em>templates/agreement.html</em>"
    ,'');


add_product_field('##13', 'Additional Options', 'header');
if (!is_lite()){
add_product_field('autoresponder_renew', 'Send Automatic Emails after Renewal',
    'select', "When user renews subscription, aMember can<br />
    resend days counter and start emails again from first one<br />
    or aMember can continue old mailing cycle.
    ", '', array('options' => array(
        ''  => 'Continue old mailing cycle',
        '1' => 'Reset days counter to zero, start new cycle'
    )));
 add_product_field('dont_mail_expire', 'Do not send expiration e-mail for this product',
    'hidden', "if you set this, expiration e-mail will not be send<br />
    for subscriptions to this product.
    ", '', array('options' => array(
        ''  => 'Send expiration e-mail (default)',
        '1' => 'Do not send expiration e-mail',
        '2' => 'Always send expiration e-mail'
        )));

}
add_product_field('terms', 'Custom Subscription Terms to Display',
    'text', "this text will be displayed to customer near the<br />
    product title on signup.php and member.php pages<br /> 
    to explain payment terms<br />
    if you keep it empty, it will be generated automatically<br />
    if that is filled-in, you have to change it manually when<br />
    you change product price or other parameters
    ", '', array('insert_before' => '##11', 'size' => 60));

unset($GLOBALS['COUPON_NOT_USED']);
unset($GLOBALS['COUPON_DISCOUNT']);
unset($GLOBALS['TAX_AMOUNT']);
unset($GLOBALS['TAXES']);
unset($GLOBALS['PAYMENT_TERMS']);
