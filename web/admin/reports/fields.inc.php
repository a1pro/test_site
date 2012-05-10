<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");


@set_time_limit(300);

add_report('fields', 'Customers Demography');


function get_report_fields(){
    $fields = array(
    'city'  => 'City',
    'state' => 'State',
    'zip'   => 'ZIP',
    'country' => 'Country'
    );
    global $member_additional_fields;
    foreach ($member_additional_fields as $f){
        if ($f['hidden_anywhere']) continue;
        $fields[ 'data.' . $f['name'] ] = $f['title'];
    }
    return $fields;
}


function fields_display_params_dialog($vars, $err=''){
    global $t;
    $t->assign('title', 'Report Parameters');
    $t->assign('report_title', 'Customer Demographics');    

    set_date_from_smarty('beg_date', $vars);
    set_date_from_smarty('end_date', $vars);
    if ($vars['beg_date'] == '0000-00-00') $vars['beg_date'] = date('Y-01-01');
    if ($vars['end_date'] == '0000-00-00') $vars['end_date'] = date('Y-m-d');

    foreach ($vars as $k=>$v)
        $t->assign($k,$v);

    $t->assign('fields', get_report_fields());

    $t->assign('error', $err);
    ///////////////////////////////////////
    $t->display('admin/header.inc.html');
    $otd = $t->template_dir ;
    $t->template_dir = dirname(__FILE__);
    $t->display('fields.inc.html');
    $t->template_dir = $otd;
    $t->display('admin/footer.inc.html');
}


function fields_check_params(&$vars){
    $err = array();
    set_date_from_smarty('beg_date', $vars);
    set_date_from_smarty('end_date', $vars);
    if (!count($vars['fields'])) 
        $err[] = "Please select one or more fields to continue";
    settype($vars['max_values'], 'integer');
    if ($vars['max_values'] <= 0) 
        $err[] = "Please enter a valid integer for 'Max Values' field";
    return $err;
}

function fields_display_report($vars){
    global $t,$db,$config, $member_additional_fields;

    $main_fields = $add_fields = array();
    $maf = array();
    foreach ($vars['fields'] as $f){
        if (preg_match('/^data\.(.+)$/', $f, $regs)) {
            foreach ($member_additional_fields as $ff){
                if ($ff['name'] == $regs[1]) 
                    if ($ff['sql']) {
                        $f = preg_replace('/^data\./', '', $f);
                        $main_fields[$f] = $f;
                    } else {
                        $maf[$f] = $ff;
                        $add_fields[$f] = $regs[1];
                    }
            }
        } else
            $main_fields[$f] = $f;
    }
    ////////////////////////////////////////////////////////////////
    $q = $db->query("SELECT * FROM {$db->config[prefix]}members");
    while ($m = mysql_fetch_assoc($q)){
        $m['data'] = unserialize($m['data']);
        foreach ($main_fields as $k => $f)
            $stat[$k][ $m[$f] ] += 1;
        foreach ($add_fields as $k => $f) {
            if (is_array($arr = $m['data'][$f]))
                foreach ($arr as $kk => $vv) {
                    if ($vv) $stat[$k][ $kk ] += 1;
                }                    
            else            
                $stat[$k][ $m['data'][$f] ] += 1;
        }            
    }
    ///////////////////////////////////////////////////////////////
    $fnames = get_report_fields();
    foreach ($stat as $field => $vals){
        unset($stat[$field]);
        arsort($vals, SORT_NUMERIC);
        $total_sum = array_sum($vals);
        // $vals = array_slice($vals, 0, $vars['max_values']);
        // mantain keys
        $new_vals = array();
        $i = 0;
        foreach ($vals as $k=>$v){
            if (++$i > $vars['max_values']) break;
            $new_vals[$k] = $v;
        }
        $vals = $new_vals;            
        /////////////////////////////////////////////////////
        $other_sum = $total_sum - array_sum($vals);
        if ($other_sum) 
            $vals['<b>Other values</b>'] = $other_sum;
        $max = max($vals);

        $options = $maf[$field]['options'];
        $has_options = is_array($options) && count($options);

        foreach ($vals as $k=>$v){
            $p = round(100*$v/$total_sum, 2);
            $x = round(100*$v/$max);
            $vals[$has_options ? $options[$k] : $k] = array('count' => $v, 'percent'=> 
            $p ? "
            <table align=left width=$x cellpadding=0 cellspacing=0 style='font-size: 5pt;' height=6><tr><td bgcolor=red style='background-color: red;'></td></tr></table>
            &nbsp;($p%)
            " : '');
            if ($has_options && $options[$k] != $k) unset($vals[$k]); // to avoid deleting values
        }
        $stat[ $fnames[$field] ] = $vals;
    }
    ///////////////////////////////////////////////////////////////
    $t->assign('title', 'Customer Demographics');
	if(@count($stat['State'])){
		$state = array();
		foreach($stat['State'] as $name => $value){
			$real_name=$db->query_one("SELECT title from {$db->config['prefix']}states where state='$name'");
			if($real_name)
				$state[$real_name]=$value;
			else
				$state[$name]=$value;
		}
		$stat['State'] = $state;
	}

    $t->assign('report', $stat);
    $t->display('admin/header.inc.html');
    $otd = $t->template_dir ;
    $t->template_dir = dirname(__FILE__);
    $t->display('fields_result.inc.html');
    $t->template_dir = $otd;
    $t->display('admin/footer.inc.html');
    print "<br /><br /><font size=1>Creation date: ".strftime($config['time_format'])."</font>";
}

?>
