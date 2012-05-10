<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: upgrade DB from ../amember.sql
*    FileName $RCSfile$
*    Release: 4.1.10 ($Revision$)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

class AdminUpgradeDbController extends Am_Controller
{
    protected $db_version;
    public function checkAdminPermissions(Admin $admin)
    {
        check_demo();
        return $admin->isSuper();
    }
    function convert_to_new_keys()
    {
        return;
        if (!$this->getDi()->modules->isEnabled('cc'))
            return;
        if (!file_exists(APPLICATION_PATH . '/configs/key.php')) return;
        $key = require_once APPLICATION_PATH . '/configs/key.php';
        
        $cryptNew = $this->getDi()->crypt;
        if ($cryptNew->compareKeySignatures() == 0) return;
        if (!file_exists(APPLICATION_PATH . '/configs/key-old.inc.php')) {
            print "
    <div style='color: red'><br />To convert your encrypted values to use new keystring,
    please copy old file <i>amember/application/confgigs/key.php</i> to
    <i>amember/application/confgigs/key-old.inc.php</i> and run this 'Upgrade Db' utility again.
    <br /><br />
    <b>It is also required to make backup of your database before conversion. GGI-Central is not responsible for any damage
    the conversion may result to if you have no backup saved before conversion. Please make backup first, then go back here for conversion.</b>
    <br />
    <br /> Once you made backup of the database and key file, please click <a href='admin-upgrade-db?refresh=".time()."'>this link</a> to run upgrade script again.
    </div>
            " ;
            return false;
        }
        $cryptOld = new Am_Crypt_Strong(require APPLICATION_PATH . '/configs/key-old.inc.php');
        $q = $this->getDi()->db->queryResultOnly("SELECT * FROM ?_cc");
        // dry run
        print "<br />Checking CC Records with old key..."; ob_flush();
        $count = 0;
        while ($r = mysql_fetch_assoc($q)){
            $cc = $this->getDi()->ccRecordRecord;
            $cc->setCrypt($cryptOld);
            $cc->fromRow($r);
            if (preg_match('/[^\s\d-]/', $cc->cc_number)) {
                print "<div style='color: red'>Problem with converting to new encryption key:</br>
                    cc record# {$cc->cc_id} could not be converted, it seems the old key has been specified incorrectly. Conversion cancelled.</div>";
                return;
            }
            $count++;
        }
        print "OK ($counts)\n<br />";
        print "Converting CC records with new key..."; ob_flush();
        // real run
        $q = $this->getDi()->db->queryResultOnly("SELECT * FROM ?_cc");
        $count = 0;
        while ($r = mysql_fetch_assoc($q)){
            $cc = $this->getDi()->ccRecordRecord;
            $cc->setCrypt($cryptOld);
            $cc->fromRow($r);
            if (preg_match('/[^\s\d-]/', $cc->cc_number)) {
                print "<div style='color: red'>Problem with converting to new encryption key:</br>
                    cc record# {$cc->cc_id} could not be converted, it seems the old key has been specified incorrectly. Conversion cancelled.</div>";
                return;
            }
            $cc->setCrypt($cryptNew);
            $cc->update();
            $count++;
        }
        $cryptNew->saveKeySigunature();

        print "OK ($count)\n<br />"; ob_flush();
        $this->getDi()->db->query("OPTIMIZE TABLE ?_cc"); // to remove stalled records
    }
    function indexAction()
    {
        $this->getDi()->db->setLogger(false);

        $t = new Am_View;
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        $this->db_version = $this->getDi()->store->get('db_version');
        
        if (defined('AM_DEBUG')) ob_start();
        ?><html>
        <head><title>aMember Database Upgrade</title>
        <body>
        <h1>aMember Database Upgrade</h1>
        <hr />
        <?php


        /* ******************************************************************************* *
         *                  M A I N
         */
        $this->getDi()->app->dbSync(true);
        $this->convert_to_new_keys();
        $this->checkInvoiceItemTotals();

        echo "
        <br/><strong>Upgrade finished successfully.
        Go to </strong><a href='".REL_ROOT_URL."/admin/'>aMember Admin CP</a>.
        <hr />
        </body></html>";
    }
    function checkInvoiceItemTotals()
    {
        if (version_compare($this->db_version, '4.1.8') < 0)
        {
            echo "Update invoice_item.total columns...";     
            ob_end_flush();
            $this->getDi()->db->query("
                UPDATE ?_invoice_item
                SET 
                    first_total = first_price*qty - first_discount + first_shipping + first_tax,
                    second_total = second_price*qty - second_discount + second_shipping + second_tax
                WHERE 
                    ((first_total IS NULL OR first_total = 0) AND first_price > 0)
                OR 
                    ((second_total IS NULL OR second_total = 0) AND second_price > 0)
                ");
            echo "Done<br>\n";
        }
    }
}

