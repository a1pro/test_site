<?php


require "../config.inc.php";
$t = new_smarty();
require "login.inc.php";
admin_check_permissions('super_user');
check_demo();

$words = array(
    'onabort', 'onactivate',
    'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy',
    'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste',
    'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce',
    'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect',
    'oncopy', 'oncut', 'ondataavaible', 'ondatasetchanged', 'ondatasetcomplete',
    'ondblclick', 'ondeactivate', 'ondrag', 'ondragdrop', 'ondragend', 'ondragenter',
    'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate',
    'onfilterupdate', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown',
    'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown',
    'onmouseenter', 'onmouseleave', 'onmousemove', 'onmoveout', 'onmouseover', 'onmouseup',
    'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange',
    'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowexit',
    'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart',
    'onstart', 'onstop', 'onsubmit', 'onunload');
$badWords = '(<script|[[:<:]](' . join('|', $words) . ')[[:>:]])';

function rplFirst($s)
{
    return '-' . substr($s[1], 1);
}

function doFix($member_id)
{
    global $db, $words;
    $u = $db->get_user($member_id);
    $regexp = '/(<script|\b'.join('|', $words) .'\b)/';
    foreach ($u as $k => $v)
    {
        if (!is_array($v))
            $u[$k] = preg_replace_callback($regexp, 'rplFirst', $v);
    }
    $db->update_user($member_id, $u);
//
    foreach ($db->get_user_payments($member_id) as $p)
    {
        foreach ($p['data'] as $kk => $vv)
        {
            if (!is_array($vv))
                $p['data'][$kk] = @preg_replace_callback($regexp, 'rplFirst', $vv);
            else
                foreach ($vv as $k => $v)
                    $p['data'][$kk][$k] = @preg_replace_callback($regexp, 'rplFirst', $v);
        }
        $db->update_payment($p['payment_id'], $p);
    }
}
function doDel($member_id)
{
    $db->delete_user($member_id);
}


if ($do = (array)$_POST['do'])
{
    foreach ($do as $member_id => $action)
    {
        if ($action == 'fix')
            doFix($member_id);
        else
            doDel($member_id);
    }
    print "Records fixed/deleted by your request. <a href='fix.php'>Check again, should be no records in list</a>";
    exit();
}





$prefix = $db->config['prefix'];
$q = $db->query("
SELECT m.*
FROM {$prefix}members m LEFT JOIN {$prefix}payments p USING (member_id)
WHERE
    m.name_f RLIKE '$badWords'  OR
    m.name_l RLIKE '$badWords'  OR
    m.email RLIKE '$badWords'   OR
    m.country RLIKE '$badWords' OR
    m.state RLIKE '$badWords'   OR
    m.street RLIKE '$badWords'  OR
    m.city RLIKE '$badWords'    OR
    m.zip RLIKE '$badWords'     OR
    m.data   RLIKE '$badWords'  OR
    p.data RLIKE '$badWords' 
    ");

if (!mysql_num_rows($q))
{
    print "<b>No injected rows found, ALL OK. Seems nobody has tried to hack your website, or you have already fixed all records.
 Go to <a href='index.php'>Admin CP</a>.
`</b>";
    exit();
}



print "
<html><head><title>Fix aMember records</title></head>
<body>
<form method=post action='fix.php'>
<table border=1 style='border-collapse: collapse'>
<tr>
    <th>Username</th>
    <th>First Name</th>
    <th>Last Name</th>
    <th>Action</th>
</tr>

";
while ($r = mysql_fetch_assoc($q))
{
    printf( "<tr>
            <td>%s</td><td>%s</td><td>%s</td>
            <td><select size=1 name='do[%d]'><option value='fix'>Fix
            <option value='del'>Delete</select></td>
            </tr>\n",
            htmlentities($r['login']),
            htmlentities($r['name_f']),
            htmlentities($r['name_l']),
            $r['member_id']
            );
}
print "
</table>
<br>
<b>If something is strange, DO NOT PRESS BUTTON, contact <a href='https://www.amember.com/support/'>CGI-Central support</a> instead.</b>
<br><br>
<input type='submit' name='fix' value='Process' >
</form>

</body></html>";
