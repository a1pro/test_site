<?php

/*
 *
 *
 *     Author: Alex Scott
 *      Email: alex@cgi-central.net
 *        Web: http://www.cgi-central.net
 *    Details: Admin index
 *    FileName $RCSfile$
 *    Release: 4.1.10 ($Revision$)
 *
 * Please direct bug reports,suggestions or feedback to the cgi-central forums.
 * http://www.cgi-central.net/forum/
 *                                                                          
 * aMember PRO is a commercial software. Any distribution is strictly prohibited.
 *
 */

class AdminController extends Am_Controller
{

    public function checkAdminPermissions(Admin $admin)
    {
        return (bool) $admin;
    }

    function getIncomeReport()
    {
        require_once 'Am/Report.php';
        require_once 'Am/Report/Standard.php';
        $r = new Am_Report_Income();
        $r->setInterval('-1 month', 'now');
        $r->setQuantity(new Am_Report_Quant_Day());
        $result = $r->getReport();
        $output = new Am_Report_Graph_Line($result);
        return $output;
    }

    function getUsersReport()
    {
        $res = $this->getDi()->db->select("SELECT status as ARRAY_KEY, COUNT(*) as `count`
            FROM ?_user
            GROUP BY status");
        $total = array_sum($res);
        for ($i = 0; $i <= 2; $i++)
            $res[$i]['count'] = (int) @$res[$i]['count'];
        $active_paid = $this->getDi()->db->selectCell("
            SELECT COUNT(DISTINCT user_id) AS active
            FROM ?_invoice_payment");
        $active_free = $res[1]['count'] - $active_paid;
        $result = new Am_Report_Result;
        $result->setTitle("Users Breakdown");
        $result->addPoint(new Am_Report_Point(0, "Pending"))->addValue(0, (int) $res[0]['count']);
        $result->addPoint(new Am_Report_Point(1, "Active"))->addValue(0, (int) $active_paid);
        $result->addPoint(new Am_Report_Point(4, "Active(free)"))->addValue(0, (int) $active_free);
        $result->addPoint(new Am_Report_Point(2, "Expired"))->addValue(0, (int) $res[2]['count']);
        $result->addLine(new Am_Report_Line(0, "# of users"));

        $output = new Am_Report_Graph_Bar($result);
        $output->setSize(400, 250);
        return $output;
    }

    function getErrorLogCount()
    {
        $time = $this->getDi()->time;
        $tm = date('Y-m-d H:i:s', $time - 24 * 3600);
        return $this->getDi()->db->selectCell(
            "SELECT COUNT(*)
            FROM ?_error_log
            WHERE dattm BETWEEN ? AND ?", 
            $tm, $this->getDi()->sqlDateTime);
    }

    function getAccessLogCount()
    {
        $tm = date('Y-m-d H:i:s', $this->getDi()->time - 24 * 3600);
        return $this->getDi()->db->selectCell(
            "SELECT COUNT(log_id)
            FROM ?_access_log
            WHERE dattm BETWEEN ? AND ?", 
                $tm, 
                $this->getDi()->sqlDateTime);
    }

    function getWarnings()
    {
        $warn = array();
        $setupUrl = REL_ROOT_URL . "/admin-setup";

        if (!$this->getDi()->config->get('maintenance'))
        {
            // cron run
            $t = Am_Cron::getLastRun();
            $diff = time() - $t;
            $tt = $t ? ('at ' . amTime($t)) : "NEVER (oops! no records that it has been running at all!)";
            if ($diff > 24 * 3600)
                $warn[] = "Cron job has been running last time $tt, it is more than 24 hours before.<br />
                Most possible external cron job has been set incorrectly. It may cause very serious problems with the script";
        }
        ////
        if (!$this->getDi()->productTable->count())
            $warn[] = "You have not added any products, your signup forms will not work until you <a href='admin-products'>add at least one product</a>";
        
        
        // @todo email_queue_enabled enabled without external_cron
        

        $db_version = $this->getDi()->store->get('db_version');
        if (empty($db_version))
        {
            $this->getDi()->store->set('db_version', AM_VERSION);
        } elseif ($db_version != AM_VERSION) {
            $url = REL_ROOT_URL . '/admin-upgrade-db';
            $warn[] = "Seems you have upgraded you aMember Pro installation. Please do not forget to ".
                "<a href='$url'>run database upgrade script</a>";
        }
        
        
        
        // load all plugins
        try {
            foreach ($this->getDi()->plugins as $m)
                $m->loadEnabled();
        } catch (Exception $e) {} 
        
        $event = $this->getDi()->hook->call(Am_Event::ADMIN_WARNINGS);
        $warn = array_merge($warn, $event->getReturn());
        
        // return 
        return $warn;
    }

    function hasPermissions($perm, $priv = null)
    {
        return $this->getDi()->authAdmin->getUser()->hasPermission($perm);
    }

    function getSalesStats($start, $stop)
    {
        $row = $this->getDi()->db->selectRow("
            SELECT 
                COUNT(*) AS cnt, 
                SUM(amount) AS total 
            FROM ?_invoice_payment 
            WHERE dattm BETWEEN ? AND ?
            ", sqlTime(strtotime($start)), sqlTime(strtotime($stop)));
        return array((int) $row['cnt'], moneyRound($row['total']));
    }

    function getCancelsStats($start, $stop)
    {
        return $this->getDi()->db->selectCell("
            SELECT COUNT(*) 
            FROM ?_invoice
            WHERE tm_cancelled BETWEEN ? AND ?
            ", sqlTime(strtotime($start)), sqlTime(strtotime($stop)));
    }

    function getPlannedRebills($start, $stop)
    {
        $row = $this->getDi()->db->selectRow("
            SELECT 
                COUNT(*) AS cnt, 
                SUM(second_total) AS total 
            FROM ?_invoice
            WHERE rebill_date BETWEEN DATE(?) AND DATE(?)
            ", sqlTime(strtotime($start)), sqlTime(strtotime($stop)));
        return array((int) $row['cnt'], moneyRound($row['total']));
    }

    function getSignupsCount($start, $stop)
    {
        return $this->getDi()->db->selectCell("
            SELECT 
                COUNT(*) AS cnt
            FROM ?_user
            WHERE added BETWEEN ? AND ?
            ", sqlTime(strtotime($start)), sqlTime(strtotime($stop)));
    }

    function showQuickstart()
    {
        return!$this->getDi()->config->get('quickstart-disable');
    }

    function disableQuickstartAction()
    {
        Am_Config::saveValue('quickstart-disable', true);
        $this->getDi()->config->set('quickstart-disable', true);
        return $this->indexAction();
    }

    function indexAction()
    {
        // Yes, what we do here is a true criminal
        // but it is necessary to give freedom to theme designers
        $this->view->controller = $this;
        $this->view->display('admin/index.phtml');
    }

}