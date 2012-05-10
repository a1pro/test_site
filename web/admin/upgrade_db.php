<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: upgrade DB from ../amember.sql
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5175 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

include "../config.inc.php";
$t = new_smarty();

function admin_accounts_created(){
    global $db, $config;
    $prefix = $db->config['prefix'];
    $q = mysql_query("SELECT COUNT(admin_id)
        FROM {$prefix}admins", $db->conn);
    if (mysql_errno($db->conn))
        return 0;
    else
        return 1;
}
function email_templates_created(){
    global $db, $config;
    $prefix = $db->config['prefix'];
    $q = mysql_query("SELECT COUNT(email_template_id)
        FROM {$prefix}email_templates", $db->conn);
    if (mysql_errno($db->conn))
        return false;
    $r = mysql_fetch_row($q);
    return $r[0] > 0;
}

function rename_send_pending_email(){
    global $db, $config;
    $prefix = $db->config['prefix'];
    $exists = $db->query_one("SELECT email_template_id FROM {$prefix}email_templates
        WHERE name='send_pending_email' AND lang='en'");
    if ($exists) return;
    $q = mysql_query($sql = "UPDATE {$prefix}email_templates
    SET name='send_pending_email' WHERE name='send_pending_mail'
    ", $db->conn);
    print mysql_error($db->conn);
}

function update_members_threads_status(){
    global $db;
    $q = $db->query("SELECT member_id, status FROM {$db->config['prefix']}members");
    while (list($member_id, $user_status) = mysql_fetch_row($q))
        $db->update_member_threads_access($member_id, $user_status);
}


if ($admin_accounts_created = admin_accounts_created()){
    include "login.inc.php";
}


?><html>
<head><title>aMember Database Upgrade</title>
<body>
<h1>aMember Database Upgrade</h1>
<hr />
<?php

function create_mysql_tables(){
    global $config, $db, $create_email_templates, $old_db_version;

    $file = join('', file("../amember.sql"));
    if (strlen($file)<255) amDie("File ../amember.sql corrupted");
    $file = str_replace('@DB_MYSQL_PREFIX@', $config['db']['mysql']['prefix'], $file);
    
    $pfile = preg_split('/^## (\d\d\d|ALL) ##\s*$/ms', $file, -1, PREG_SPLIT_DELIM_CAPTURE);
    array_unshift($pfile, '000');
    $file = array();
    for ($i=0;$i<count($pfile);$i+=2)
    	$file[$pfile[$i]] = $pfile[$i+1];
	foreach ($file as $k => $v){
		if ($k == 'ALL') continue;
		if ($k > $old_db_version) continue;
		unset($file[$k]);    	
	}
	$file = join("\n", $file);
    
    preg_match_all('/(CREATE TABLE\s+(.+?)\s+.+?|.+?);\s*$/ms', $file, $out);
    foreach ($out[0] as $sql){
        if (preg_match('/CREATE TABLE\s+(\w+)/', $sql, $regs)) {
            $tname = $regs[1];
            if (mysql_query("SELECT * FROM $tname LIMIT 1", $db->conn) && !mysql_errno($db->conn)){
                continue; // SKIP TABLE CREATION
            }
            print "Creating table [$tname]<br/>\n";
        } elseif (preg_match("/REPLACE INTO {$db->config[prefix]}config \(name,type,value\) VALUES \('db_version', 0, '(\d+)'\)/", $sql, $rr)){
            $db->config_set('db_version', $rr[1], 0);
            if ($rr[1] != $config['db_version']){
                if ($rr[1] == '250'){// make secure password default
                    $db->config_set('safe_send_pass', 1, 0);
                }
                print "Setting database version to [$rr[1]]<br/>\n";
            }
            continue;
        } elseif ($create_email_templates &&
            preg_match("/INSERT INTO {$db->config[prefix]}email_templates VALUES \(\d+, '(.+?)'/", $sql, $rr)){
            print "Importing e-mail template [$rr[1]].<br />\n";
        } elseif (preg_match('/(INSERT|DELETE|UPDATE|REPLACE)\s+/', $sql)){
            continue;
        } elseif (preg_match('/DROP_FIELD\s+(\w+)/', $sql, $regs)){
            $field = $regs[1];
            $q = mysql_query("SHOW FIELDS FROM $tname", $db->conn);
            $sql = '';
            while (list($f,$t,$null,$index,$add) = mysql_fetch_row($q)){
                if ($f == $field) {
                    $sql = "ALTER TABLE $tname DROP $field";
                    break;
                }
            }
            if (!$sql) continue;
            print "Dropping field [$tname.$field]<br />\n";
        } elseif (preg_match('/MODIFY\s+(\w+)\s+(.+);/', $sql, $regs)){
            $tname = $regs[1];
            $mreq  = $regs[2];
            if (preg_match('/FIELD\s+(\w+)\s+(.+)/', $mreq, $regs)){
                $field = $regs[1];
                $q = mysql_query("SHOW FIELDS FROM $tname", $db->conn);
                $sql = '';
                while (list($f,$t,$null,$index,$add) = mysql_fetch_row($q)){
                    if ($f == $field) {
                        $sql = "ALTER TABLE $tname CHANGE $field $field $regs[2];";
                        break;
                    }
                }
                if (!$sql)
                    $sql = "ALTER TABLE $tname ADD $field $regs[2];";
		//		print "Adding field [$tname.$field]<br />\n";                    
            } elseif (preg_match('/(UNIQUE INDEX|INDEX)\s+(\w+)\s+.+/',
            $mreq, $regs)){
                $index = $regs[2];
                $q = mysql_query("SHOW INDEX FROM $tname", $db->conn);
                while (list($t,$t,$index1) = mysql_fetch_row($q)){
                    if ($index1 != $index) continue;
                    mysql_query("ALTER TABLE $tname DROP INDEX $index", $db->conn);
                }
                $sql = "ALTER TABLE $tname ADD $regs[0]";
				print "Adding index to [$tname]<br />\n"; ob_end_flush();
            } else { // unknown modify request
                print "<font color=red>Unknown modify request: $sql</font><br />\n";
                continue;
            }
        }
        $sql = preg_replace('/;\s*$/s', '', $sql);
        mysql_query($sql, $db->conn);
        if ($err = mysql_error($db->conn)) $errors[] = $err . "<br />SQL: <pre>$sql</pre>";
        if ($errors)
            amDie($errors[0]);
    }
    mysql_query("ALTER TABLE {$config[db][mysql][prefix]}aff_commission
        CHANGE comission_id commission_id int not null", $db->conn);
}


function create_admin_accounts(){
    global $db, $config;
    $db->query("INSERT INTO {$db->config[prefix]}admins
        SET
        login='$config[admin_login]',
        pass='$config[admin_pass]',
        email='$config[admin_email]',
        super_user=1
        ");
}

function check_aff_commission_id(){
    global $db, $config;

    $q = $db->query("EXPLAIN {$db->config[prefix]}aff_commission");
    $x = mysql_fetch_row($q);

    if (!preg_match('/auto_increment/', $x[5])){
        $db->query($s="ALTER TABLE {$db->config[prefix]}aff_commission
            CHANGE commission_id commission_id INT NOT NULL
            auto_increment
        ");
    }
}

function load_countries_from_file(){
	print "Loading countries from file..."; ob_end_flush();
	$d = & amDb();
    $prefix = amConfig('db.mysql.prefix');
    $sql = file_get_contents(ROOT_DIR . '/sql-countries.sql');
    $sql = str_replace('@DB_MYSQL_PREFIX@', $prefix, $sql);
    $d->query($sql);
    $c = $d->selectCell("SELECT COUNT(*) FROM ?_countries");
    print "[$c] imported OK<br />\n"; ob_end_flush();
}

function load_states_from_file(){
	print "Loading states from file..."; ob_end_flush();
	$d = & amDb();
    $prefix = amConfig('db.mysql.prefix');
    $sql = file_get_contents(ROOT_DIR . '/sql-states.sql');
    $sql = str_replace('@DB_MYSQL_PREFIX@', $prefix, $sql);
    $d->query($sql);
    $c = $d->selectCell("SELECT COUNT(*) FROM ?_states");
    print "[$c] imported OK<br />\n"; ob_end_flush();
}

function update_email_templates(){
    global $db,$config;
	print "Updating email templates..."; ob_end_flush();
    $r = $db->query("
        SELECT email_template_id,txt
        FROM {$db->config[prefix]}email_templates
        WHERE txt like '%\$payment.data.TAX_AMOUNT%'
        ");
    while ($row = mysql_fetch_assoc($r)){
        $id = $row['email_template_id'];
        $txt = $row['txt'];
        $txt2 = preg_replace ('/\$payment\.data\.TAX_AMOUNT/i', '$payment.tax_amount', $txt);
        if ($txt2 != $txt){
            $db->query("
                UPDATE {$db->config[prefix]}email_templates
                SET txt='$txt2'
                WHERE email_template_id='$id'
                ");
        }
    }
    print "OK<br />"; ob_end_flush();
}

function tax_fill_from_data(){
    global $db,$config;

    if ($config['use_tax']){
        // check filled new field 'tax_amount'
        $q = $db->query($s = "
            SELECT COUNT(*)
            FROM {$db->config[prefix]}payments
            WHERE tax_amount > 0
            ");
        $r = mysql_fetch_row($q);
        if ($r[0] == 0){
            // fill it from 'data' if no one filled
            $q = $db->query($s = "
                SELECT payment_id
                FROM {$db->config[prefix]}payments
                WHERE tax_amount = 0
                ");
            while ($r = mysql_fetch_assoc($q)){
                $payment_id = $r['payment_id'];
                $p = $db->get_payment($payment_id);
                $tax_amount = $p['data']['TAX_AMOUNT'];
                if ($tax_amount > 0)
                    $q2 = $db->query($s2 = "
                        UPDATE {$db->config['prefix']}payments
                        SET tax_amount = '$tax_amount'
                        WHERE payment_id=$payment_id
                    ");
            }
        }
    }
}

function convert_not_completed_template(){
    global $db, $config;
    if($config['mail_not_completed'] && $config['mail_not_completed_days']){
        print "Convert not_completed_payment template from old style to new..."; ob_end_flush();

        $db->query("UPDATE ".$db->config['prefix']."email_templates
                    SET day='".$config['mail_not_completed_days']."'
                    WHERE name='mail_not_completed' and day IS NULL"
        );

        print "OK<br />"; ob_end_flush();
    }
}

/* ******************************************************************************* *
 *                  M A I N
 */
$old_db_version = $db->query_one("
    SELECT value
    FROM {$db->config[prefix]}config
    WHERE name='db_version'
    ");
print "Current database version: [$old_db_version] <small>(may be different from aMember version number)</small>.\n<br />";
$f = join('', file('../amember.sql'));
if (preg_match("/REPLACE INTO @DB_MYSQL_PREFIX@config \(name,type,value\) VALUES \('db_version', 0, '(\d+)'\)/", $f, $rr))
	print "Version of <strong>amember.sql</strong> file is [$rr[1]].\n<br />";

$create_email_templates = !email_templates_created();
create_mysql_tables();
if (!$admin_accounts_created)
    create_admin_accounts();
check_aff_commission_id();

$d = & amDb();
if ($d->selectCell("SELECT COUNT(*) FROM ?_countries") <= 0)
    load_countries_from_file();
if ($d->selectCell("SELECT COUNT(*) FROM ?_states") <= 0)
    load_states_from_file();

if ($create_email_templates){
    print "<br /><br /><strong>Default e-mail templates were imported (from amember.sql file).<br />
    If you wish to load your templates to database from text files, click the following link:
    <a target='_blank' href='email_templates.php?a=convert'>Import templates from text files</a><br />";
}

if ($old_db_version < '301'){
    tax_fill_from_data();
    update_email_templates();
} 
if ($old_db_version < '307') {
    rename_send_pending_email();
} 
if ($old_db_version < '310')
    update_members_threads_status();

if($old_db_version < '319'){
    convert_not_completed_template();
}
echo "
<br/><strong>Upgrade finished successfully.
Go to </strong><a href='index.php'>aMember Admin CP</a>.

<hr />
</body></html>";