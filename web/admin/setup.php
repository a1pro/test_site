<?php              
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Configuration
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4872 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/
require "../config.inc.php";
$t = new_smarty();
require "login.inc.php";

$_default_notebook = 'Global';
$_notebooks = array();
$_config_fields = array();

function config_set_notebook_comment($nb, $comment){
    global $_notebooks;
    $_notebooks[$nb]['comment'] = $comment;
}
function config_set_readme($nb, $readme){
    global $_notebooks;
    $_notebooks[$nb]['readme'] = $readme;
}
function add_config_field($name, $title,
    $type, $desc, $nb,
    $validate_func='',
    $get_func='', $set_func='',
    $params=''){
    global $_notebooks, $_config_fields;
    
    if (isset($_config_fields[$name])){
        
        //echo "Warning: config field '$name' added more than once!";
        //
        // Move field with same name to bottom
        //
        // Add new notebook name
        $notebooks = $_config_fields[$name]['notebook'];
        if (!is_array($notebooks)) $notebooks = array($notebooks);
        if (!in_array($nb, $notebooks)){
            $_config_fields[$name]['notebook'][] = $nb;
        
            // Store old notebook values, unset and add it again
            $store = $_config_fields[$name];
            unset ($_config_fields[$name]);
            $_config_fields[$name] = $store;
        }
        
    } else {
        
        $_config_fields[$name] = array(
            'name'  => $name,
            'title' => $title,
            'type'  => $type,
            'desc'  => $desc,
            'notebook' => array($nb),
            'validate_func' => $validate_func,
            'get_func'      => $get_func,
            'set_func'      => $set_func,
            'params'        => $params
        );
        
    }
    return 1;
}
function show_config_edit_field(&$field, &$vars){
    $fname = $field['name'];
    $val   = $vars[$fname];
    if ($func = $field['get_func']){
        $field['edit'] = $func($field, $vars);
        $field['special_edit']++;
        return;
    }
    switch ($ftype = $field['type']){
        case 'text': 
        case 'integer':
            if ($ftype == 'integer'){
                $size=5;
            } else {
                $size=30;
                if ($field['params']['size']) $size = $field['params']['size'];
            }
            if (!strlen($val)) $val = $field['params']['default'];
            $val = htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); 
            $field['edit'] = "
             <input type=text name=\"$fname\" 
                value=\"$val\" size=$size maxlength=255>
             ";
        break;
        case 'dbprefix': 
            $r = split('\.', $field['name']);
            $field['edit'] = "";
            if (!strlen($val)) $val = $field['params']['default'];
            $val = htmlspecialchars($val, ENT_QUOTES); 
            $hideDbText = '';
            if ($r[0] == 'protect' && $r[1] && class_exists($class='protect_'.$r[1])){
                $obj = & new $class(amConfig('protect.'.$r[1]));
                $options = "";
                foreach ($dbs = $obj->guess_db_settings() as $s){
                    $sel = ($val == $s)  ? 'selected' : '';
                    if ($val == $s) $hideDbText = true;
                    $options .= "<option $sel>" . htmlentities($s) . "</option>\n";
                }
                $user = amConfig('db.mysql.user');
                $field['edit'] = <<<CUT
<b>Auto-detected values for the field:</b><br />
<small>if there are no choices, it means that your third-party<br />
script database is unaccessible with aMember MySQL settings. You<br />
can fix it by going to Webhosting Control Panel -> MySQL Databases<br />
and allowing access to your third-party script table for aMember's <br />
Mysql user (<b>$user</b>), or your can specify MySQL database user,<br /> 
hostname and password on this page specially for use with <br />
the integration plugin and press <b>Save</b> button to see new choices.<br />
</small>
<select id='s_db' name="$fname" onchange="this.selectedIndex ? \$('#f_db').hide().attr('disabled', 1) : \$('#f_db').show().attr('disabled', 0)">
<option value=''>** Use Text Field **</option>
$options
</select>
<br /><br />
CUT;
            }
            if ($hideDbText)
                $hideDbText = 'style="display: none;" disabled="disabled"';
            $field['edit'] .= "
             <input type=text name=\"$fname\" id='f_db' $hideDbText
                value=\"$val\" size=$size maxlength=255>
             ";
        break;
        case 'color': 
            if ($ftype == 'integer'){
                $size=5;
            } 
            if (!strlen($val)) $val = $field['params']['default'];
            $val = htmlspecialchars($val, ENT_QUOTES); 
            $field['edit'] = "
             <input type=text name=\"$fname\" style='behavior: url(ColorPick.htc)'
                value=\"$val\" size=$size maxlength=255
                onchange=\"document.getElementById('$fname'+'span').style.background=this.value\"
                onkeyup=\"document.getElementById('$fname'+'span').style.background=this.value\"
                >
                &nbsp;&nbsp;
             <span id='{$fname}span' style='font-size: 16pt; background-color: $val'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
             ";
        break;
        case 'textarea': 
            $cols=40; $rows=4;
            if ($field['params']['rows']) $rows = $field['params']['rows'];
            if ($field['params']['cols']) $cols = $field['params']['cols'];
            if (!strlen($val)) $val = $field['params']['default'];
            $val = htmlspecialchars($val, ENT_QUOTES); 
            $field['edit'] = "
             <textarea name=\"$fname\" cols=$cols rows=$rows>$val</textarea>
             ";
        break;
        case 'password': 
        case 'password_c':
            $size=10;
            $field['edit'] = "
             <input type=password name=\"$fname\" 
                 size=$size maxlength=255>
             <input type=password name=\"{$fname}_confirm\" 
                 size=$size maxlength=255>
             <br /><small>enter password and confirmation><br />
             keep empty if you don't want change it</small>
             ";
        break;
        case 'select': 
        case 'multi_select': 
            if ($ftype == 'multi_select') $multi=1;
            if (!isset($vars[$fname])) 
                $val = $field['params']['default'];
            $options = "";
            foreach ($field['params']['options'] as $k=>$v){
                $k = htmlspecialchars($k, ENT_QUOTES); 
                $v = htmlspecialchars($v, ENT_QUOTES); 
                $sel = ($multi ? in_array($k, (array)$val) : $val == $k) ? 'selected' : '';
                $options .= "<option value=\"$k\" $sel>$v";
            }
            $multiple = $multi ? 'multiple' : '';
            $fname = $multi ? $fname."[]" : $fname;
            $size = $multi ? min(10, count($field['params']['options'])) : 1;
            $field['edit'] = "<select name=\"$fname\" size=$size $multiple>
            $options
            </select>
             ";
        break;
        case 'checkbox': 
            if (!isset($vars[$fname])) 
                $val = $field['params']['default'];
            $checked = $val ? 'checked' : '';
            $field['edit'] = "<input type='hidden' name='$fname' value='' />
        <input style='border-width: 0px;' type='checkbox' name='$fname' value='1' $checked />
        ";
        break;
        case 'multi_checkbox': 
            if (!isset($vars[$fname])) 
                $val = $field['params']['default'];
            $size = $field['params']['size'];
            if (!$size)
                $size = '5em';
            $field['edit'] = "<div class='checkbox_list' style='height: $size;'>
            <table class='checkbox_list'>
            \n";
            $i = -1;
            foreach ($field['params']['options'] as $k=>$v){
                $i++;
                $k = htmlspecialchars($k, ENT_QUOTES); 
                $v = htmlspecialchars($v, ENT_QUOTES); 
                $sel = in_array($k, (array)$val) ? 'checked' : '';
                $class = $sel ? 'sel' : '';
                $field['edit'] .= "
                <tr><td class='$class' nowrap='nowrap' id='td_{$fname}_$i'><label for='{$fname}_$i'>
                <input type='checkbox' id='{$fname}_$i' name='{$fname}[]' value='$k' $sel
                onclick='document.getElementById(\"td_{$fname}_$i\").className = this.checked ? \"sel\" : \"\";'>
                $v</label></td></tr>
                ";
            }
            $field['edit'] .= "</table></div>";
        break;
    };
}


function save_config_edit_field(&$field, &$vars, &$db_vars){
    $fname = $field['name'];
    $val   = $vars[str_replace('.', '_', $fname)];
    global $error;
    if ($func = $field['set_func']){
        $func($field,$vars,$db_vars);
        return;
    }
    if ($vf = $field['validate_func']){
        $err = call_user_func($vf, $field, $vars);

        if ($err) { $error[] = $err; return; }
    }
    switch ($ftype = $field['type']){
        case 'password':
            if ($val) 
                $db_vars[$fname] = crypt($val);
        break;
        case 'password_c':
            if ($val) 
                $db_vars[$fname] = $val;
        break;
        default:
            $db_vars[$fname] = $val;
    };
}
function show_config_notebook($notebook, $vars){
    global $t, $error;
    global $_notebooks, $_config_fields;
    read_plugins_configs();
    $t->assign('notebooks', $_notebooks);
    $t->assign('notebook', $notebook);
    $t->assign('error', $error);
    $fields = array();
    foreach ($_config_fields as $f){
        
        $notebooks = $f['notebook'];
        if (!is_array($notebooks)) $notebooks = array($notebooks);
        if (!in_array($notebook, $notebooks)) continue;
        
        //if ($f['notebook'] != $notebook) continue;
        $tname = str_replace('.', '_', $f['name']);
        if (!isset($vars[$f['name']]) && isset($vars[$tname]))
            $vars[$f['name']] = $vars[$tname];
        show_config_edit_field($f, $vars);
        $fields[] = $f;
    }
    $t->assign('fields', $fields);
    /// readme
    if ($rm = $_notebooks[$notebook]['readme']) {
        $f = join('', file($rm));
        $f=preg_replace_callback('/\{\$config\.(.+?)\}/', 
            'smarty_prefilter_put_config_cb',$f);
        $f = preg_replace("/(http(s?):\/\/)([\S\.]+)/i", '<a href="\\1\\3" target="_blank">\\1\\3</a>', $f);        
        $t->assign('readme', $f);
    }
    $t->display('admin/setup.html');
}

function show_config_countries($notebook){
    global $t;
    global $_notebooks, $_config_fields;
    read_plugins_configs();
    $t->assign('notebooks', $_notebooks);
    $t->assign('notebook', $notebook);
    $t->display('admin/countries.html');
}

function save_config_notebook($notebook, $vars){
    global $t, $error, $db, $config;
    global $_notebooks, $_config_fields;
    $db_vars = array();
    read_plugins_configs();
    foreach ($_config_fields as $f){

        $notebooks = $f['notebook'];
        if (!is_array($notebooks)) $notebooks = array($notebooks);
        if (!in_array($notebook, $notebooks)) continue;
        //if ($f['notebook'] != $notebook) continue;
        
        $tname = str_replace('.', '_', $f['name']);
        if (!isset($vars[$f['name']]) && isset($vars[$tname]))
            $vars[$f['name']] = $vars[$tname];
        save_config_edit_field($f, $vars, $db_vars);
    }
    if (!$error){
        $db->config_update($_config_fields, $db_vars);
        admin_log("Config changed ($vars[notebook])");
        header("Location:".$config['root_url']."/admin/setup.php?notebook=".urlencode($notebook));
        exit();
    }
    if ($notebook == 'Plugins'){
        global $config;
        $config['plugins']['payment'] = $db_vars['plugins.payment'];
        $config['plugins']['protect'] = $db_vars['plugins.protect'];
    }
    show_config_notebook($notebook, $vars);
}

function apply_plain_cf($k, $v, &$vars){
    foreach ($v as $kk=>$vv){
        if (is_array($vv) && ($keys = array_keys($vv)) && !is_integer($keys[0])){ // apply
            apply_plain_cf($k . '.' . $kk, $vv, $vars);
        } else { //next iteration
            $vars[ $k.'.'.$kk ] = $vv;
        }
    }
}

function config_to_vars($config){
    $vars = array();
    foreach ($config as $k=>$v){
        if (is_array($v) && ($keys = array_keys($v)) && !is_integer($keys[0])){
            apply_plain_cf($k, $v, $vars);
        } else {
            $vars[$k] = $v;
        }
    }
#    print "<pre>";print_r($vars);
    return $vars;
}

function read_plugins_configs(){
    global $config;
    // find all plugins and read their config
    foreach ((array)$config['plugins']['payment'] as $p)
        if (file_exists($fn="$config[root_dir]/plugins/payment/$p/config.inc.php"))
            include_once($fn);
    foreach ((array)$config['plugins']['protect'] as $p)
        if (file_exists($fn="$config[root_dir]/plugins/protect/$p/config.inc.php"))
            include_once($fn);
}

function tpl_display_notebooks(){
$cnt = 0;
$rowc = 8;
$_notebooks = $GLOBALS['_notebooks'];

foreach ($_notebooks as $name=>$nb){
  if ($cnt % $rowc == 0) { // first column
    echo "<tr>\n";    
  } 
  $colspan=1;
  $xx = $_POST['notebook'] ? $_POST['notebook'] : $_GET['notebook'];
  $cl = ($name == $xx) ? 'sel' : 'notsel';
  $href = "setup.php?notebook=" . urlencode($name);
  $comment = htmlentities($nb['comment']);
  echo "<td colspan=$colspan class=$cl><a href=\"$href\" title=\"$comment\">$name</a></td>\n";
  if ($cnt % $rowc == ($rowc - 1)){
    echo "</tr>\n";
  }
  $cnt++;
}
if ($ost = ($cnt % $rowc)) {
    $ost = $rowc - $ost;
    echo "<td colspan=$ost class=notsel>&nbsp;</td></tr>";
}

echo "
";
}
#####################################################
require "$config[root_dir]/admin/config.inc.php";

admin_check_permissions('setup');

$vars = get_input_vars();
$error = array();

if (!$vars['notebook']) $vars['notebook'] = $_default_notebook;
$vars['notebook'] = preg_replace('[\\\/]', '', $vars['notebook']);
if ($vars['save']){
   check_demo();
   save_config_notebook($vars['notebook'], $vars);
} else {
   if ($vars['notebook'] == 'License') check_demo();
   if ($vars['notebook'] == 'Countries') {
      show_config_countries($vars['notebook']);
   } else {
      show_config_notebook($vars['notebook'], config_to_vars($config));
   }
}



?>
