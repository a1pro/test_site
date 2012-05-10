<?php
require_once('../../../config.inc.php');
$t = &new_smarty();
require_once('../../../admin/login.inc.php');


$cols = array(
    'email' => 3, 
    'subscr_id' => 4, 
    'status'=> 6
);
if (!$_FILES){
print <<<CUT
       please upload CSV file received from PayPal.
       <form name=request enctype="multipart/form-data" method=post>
       <input type=file name=file>
       <input type=submit value="Update subscriptions">
       </form>
CUT;
} else {
    print "<table>";
    $f = fopen($_FILES['file']['tmp_name'], 'r');
    if (!$f) die("Cannot open uploaded file");
    fgetcsv($f, 4096); //skip titles
    while ($a = fgetcsv($f, 4096, ',', '"')){
        $email = $a[ $cols['email'] ];
        $subscr_id = $a[ $cols['subscr_id'] ];
        $status = $a[ $cols['status'] ];
        if (!preg_match('/^.+?\@.+$/', $email)){
            print _PLUG_PAY_PAYPALR_INVEMAIL."<br />\n";
            continue;
        }
        if ($subscr_id == ''){
            print sprintf(_PLUG_PAY_PAYPALR_IDEMPTY, $email)."<br />\n";
            continue;
        }
        $e = $db->escape($email);
        $s = $db->escape($subscr_id);
        $q = $db->query("SELECT member_id, login 
            FROM {$db->config[prefix]}members
            WHERE email='$e'");
        list($m, $l)= mysql_fetch_row($q);
        if (!$m){
//            print "Cannot find aMember member record for $email ($status) $subscr_id<br />\n";
            /// try to find by name
            list($nf, $nl) = preg_split('/\s+/', $a[1]);
            $nf = $db->escape($nf);
            $nl = $db->escape($nl);
            $q = $db->query("SELECT member_id, login 
                FROM {$db->config[prefix]}members
                WHERE name_f='$nf' AND name_l='$nl'");
            if (mysql_num_rows($q) == 1){
                list($m, $l) = mysql_fetch_row($q);
                print "found by name: $m, $l, $e<br />";
            } else {
              print "<tr><td>$email</td><td>$subscr_id</td><td>$a[1]</td><td>$status</td><td>$a[0]</td></tr>";
              continue;
            }
        }
        $q = $db->query("SELECT *
            FROM {$db->config[prefix]}payments
            WHERE member_id=$m 
            AND completed>0
        ");
        $nr = mysql_num_rows($q);
        if ($nr == 0){
            print _PLUG_PAY_PAYPALR_NOPAYMENTS." $m, $email, $l<br />\n";
        } elseif ($nr > 1) {
            print _PLUG_PAY_PAYPALR_2MUCHREC." $m, $email, $l<br />\n";
        } else { //1
            $db->query($s = "UPDATE {$db->config[prefix]}payments
            SET receipt_id='$s'
            WHERE member_id='$m'
            ");
//            print "OK:...$m,$login,$s,$email<br />\n";
        }
    }
}




?>
