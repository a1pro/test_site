<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: WorldPay payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3481 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

class payment_worldpay extends amember_payment {
    var $title = _PLUG_PAY_WORLDPAY_TITLE;
    var $description = _PLUG_PAY_WORLDPAY_DESC;
    var $fixed_price=0;
    var $recurring=1;
//    var $supports_trial1=1;
//    var $built_in_trials=1;

    function do_bill($amount, $title, $products, $u, $invoice){
        global $config, $db;
        $product = $products[0];
        //$u  = $db->get_user($member_id);

//        list($a, $p, $t, $rebill_times) = $this->build_subscription_params($products, $amount, $u, $invoice);

        $vars = array(
			'instId'			=> $this->config['installation_id'],
            'cartId'           => $invoice,
            'currency'         => $product['worldpay_currency'] ? $product['worldpay_currency'] : 'USD',
            'desc'             => $title,
            'email'            => $u['email'],
            'name'             => $u['name_f'] . ' ' . $u['name_l'],
            'address'          => $u['street'],
            'city'             => $u['city'],
            'state'            => $u['state'],
            'country'          => $u['country'],
            'postcode'         => $u['zip'],
            'MC_callback'      => str_replace('http://', '', $config['root_url']."/plugins/payment/worldpay/ipn.php"),
            'amount'           => $amount,
        );
        if ($this->config['testing'])
            $vars['testMode'] = 100;

        $count_recurring = 0;
        foreach ($products as $p)
            if ($p['is_recurring']) $count_recurring++;
        if ($count_recurring > 1) fatal_error(_PLUG_PAY_PAYPALR_FERROR8);

        if ($product['is_recurring']){
            list($c3, $u3) = $this->parse_period($product['expire_days'], 'expire_days');
            $vars += array(
                'futurePayType' => 'regular',
                'intervalUnit'  => $u3,
                'intervalMult'  => $c3,
                'normalAmount'  => $amount,
                'option'        => 0
            );

            if ($product['trial1_days'] != ''){
                list($c1, $u1) = $this->parse_period($product['trial1_days'], 'trial1_days');
                $vars['startDelayUnit'] = $u1;
                $vars['startDelayMult'] = $c1;
                // calculate regular price, because amount is now set to trial price

                $pc = & new PriceCalculator();
                $pc->setTax(get_member_tax($u['member_id']));
                $pc->setPriceFields(array('price'));

                $p = $db->get_payment($invoice);
                $coupon_code = $p['data'][0]['COUPON_CODE'];
                if ($config['use_coupons'] && $coupon_code != ''){
                    $coupon = $db->coupon_get($coupon_code);
                    if ( $coupon['coupon_id'] && $coupon['is_recurring'])
                        $pc->setCouponDiscount($coupon['discount'], split(',',trim($coupon['product_id'])));
                    else
                        $coupon = array();
                }
                foreach ($products as $pr)
                    if ($pr['is_recurring'])
                        $pc->addProduct($pr['product_id']);
                $terms = $pc->calculate();
                $vars['normalAmount'] = $terms->total;

                $p = $db->get_payment($invoice);
                $p['data']['regular_tax_amount'] = $terms->tax;

                $vars['amount'] = $product['trial1_price'];
                $p['amount'] = $vars['amount'];

                $db->update_payment($invoice, $p);
            } else {
                $vars['startDelayUnit'] = $u3;
                $vars['startDelayMult'] = $c3;
            }
        }
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        $url = "https://select.worldpay.com/wcc/purchase";

//new
//http://www.worldpay.com/support/upgrade/content.php?level=segment&s=5&t=36&contents=95
//
        if ($this->config['testing'])
            $url = "https://select-test.worldpay.com/wcc/purchase";

        header("Location: ".$url."?$vars");
        exit();
    }
    function get_days($orig_period){
        if (preg_match('/^\s*(\d+)\s*([y|Y|m|M|w|W|d|D]{0,1})\s*$/',
                $orig_period, $regs)){
            $period = $regs[1];
            $period_unit = $regs[2];
            if (!strlen($period_unit)) $period_unit = 'd';
            $period_unit = strtoupper($period_unit);
            switch ($period_unit){
                case 'Y':
                    if (($period < 1) or ($period > 5))
                        fatal_error(_PLUG_PAY_PAYPALR_FERROR);
                    break;
                case 'M':
                    if (($period < 1) or ($period > 24))
                        fatal_error(_PLUG_PAY_PAYPALR_FERROR2);
                    break;
                case 'W':
                    if (($period < 1) or ($period > 52))
                        fatal_error(_PLUG_PAY_PAYPALR_FERROR3);
                    break;
                case 'D':
                    if (($period < 1) or ($period > 90))
                         fatal_error(_PLUG_PAY_PAYPALR_FERROR4);
                    break;
                default:
                    fatal_error(sprintf(_PLUG_PAY_PAYPALR_FERROR5, $period_unit));
            }
        } else {
            fatal_error(_PLUG_PAY_PAYPALR_FERROR6.$orig_period);
        }
        return array($period, $period_unit);
    }
    function build_subscription_params($products, $total_price, $u, $invoice){
        global $config, $db;
        
        $a = $p = $t = array(1 => '', 2 => '', 3 => '');
        $was_recurring = 0;
        
        $pc = & new PriceCalculator();
        $pc->setTax(get_member_tax($u['member_id']));
        
        $payment = $db->get_payment($invoice);
        $coupon_code = $payment['data'][0]['COUPON_CODE'];
        
        $coupon = array();
        if ($config['use_coupons'] && $coupon_code != ''){
            $coupon = $db->coupon_get($coupon_code);
            if ( $coupon['coupon_id'] > 0 )
                $pc->setCouponDiscount($coupon['discount'], split(',',trim($coupon['product_id'])));
            else
                $coupon = array();                
        }
        $rebill_times = $products[0]['rebill_times'];
        foreach ($products as $pr){
            $pp = $pt = array(1 => '', 2 => '', 3 => '');
            if ($pr['trial1_days'] != '')
                list($pp[1], $pt[1]) = $this->get_days($pr['trial1_days']);
            if ($pr['expire_days'] != '')
                list($pp[3], $pt[3]) = $this->get_days($pr['expire_days']);
            if (!$pr['is_recurring']){
                //fatal_error(_PLUG_PAY_PAYPALR_FERROR7);
                //$a[1] += $pa[3];
                // there is at least one recurring product if we went here
            } else { // recurring
                if ($was_recurring){ // check if it was compatible
                    if (array_diff($p, $pp) || array_diff($t, $pt))
                        fatal_error(_PLUG_PAY_PAYPALR_FERROR8);
                    if ($pr['rebill_times'] != $rebill_times)
                        fatal_error(_PLUG_PAY_PAYPALR_FERROR8);
                }
                $p[1] =$pp[1]; $p[3] =$pp[3];
                $t[1] =$pt[1]; $t[3] =$pt[3];
                $was_recurring++;
            }
        }
        
        // calculate first trial - add both recurring, and non-recurring products here
        $pc->setPriceFields(array('trial1_price', 'price'));
        $need_trial1 = false;
        foreach ($products as $pr){
            if ($pr['trial1_price'] || !$pr['is_recurring'] || ($coupon['coupon_id'] && !$coupon['is_recurring'])) 
                $need_trial1 = true;
            $pc->addProduct($pr['product_id']);
        }
        if ($need_trial1){
            $terms[1] = $pc->calculate();
            $a[1] = $terms[1]->total;
            
            if (!$p[1]){ // we added trial because of discount or non-recurring product
                if ($rebill_times) $rebill_times--; // lets decrease rebill_times then!
            }
        }

        // calculate regular rate
        $pc->emptyProducts();    
        $pc->setPriceFields(array('price'));
        foreach ($products as $pr){
            if ($pr['is_recurring'])
                $pc->addProduct($pr['product_id']);
            if (!$coupon['is_recurring']) $pc->setCouponDiscount(null);
        }
        $terms[3] = $pc->calculate();
        $a[3] = $terms[3]->total;
                
        if ($a[1] && !$p[1]){ // trial1 price set, but trial 1 period did not 
            $p[1] = $p[3];
            $t[1] = $t[3];
        }
        $taxes = array();
        foreach (array(1,2,3) as $k)
            if ($terms[$k])
                $taxes[$k] = $terms[$k]->tax;
        return array($a, $p, $t, $rebill_times, $taxes);
    }

    function init(){
        parent::init();
        add_product_field(
            'worldpay_currency', 'WorldPay Currency',
            'select', 'currency for WorldPay gateway',
            '',
            array('options' => array(
                ''     => 'USD',
                'GBP'  => 'GBP',
                'EUR'  => 'EUR',
                'JPY'  => 'JPY',
                'AUD'  => 'AUD',
            ))
        );
    }
    function parse_period($days, $field=''){
        list($c, $u) = parse_period($days);
        if ($u == 'error') fatal_error(sprintf(_PLUG_PAY_WORLDPAY_FERROR4, $field, $days));
        if ($u == 'fixed') fatal_error(sprintf(_PLUG_PAY_WORLDPAY_FERROR4, $field, $days));

        $tr = array('d' => 1, 'm' => 3, 'y' => 4);
        return array($c, $tr[$u]);
    }
}

instantiate_plugin('payment', 'worldpay');