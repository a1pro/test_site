<?php 
/*
 * @todo more reports!
 * @todo check Am_Query_Quant - compare to mysql calcs check boundaries
 * @todo one page report layout
 *      choose report in scrollable radio list
 *      [ajax loadable form or pre-selected pre-filled form if report chosen]
 *      [[the report output]]
 * 
 * @todo choose date from pre-selected constants
 * @todo save last used reports in admin profile
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

/** Plugin can load own report classes when it is called */

class AdminReportsController extends Am_Controller
{
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission(Am_Auth_Admin::PERM_REPORT);
    }
    function runAction()
    {
        if (!$this->_request->isPost())
            throw new Am_Exception_InputError('Only POST accepted');
        $reportId = $this->getFiltered('report_id');
        if (!$reportId)
            throw new Am_Exception_InternalError("Empty report id passed");
        $r = Am_Report_Abstract::createById($reportId);
        $r->applyConfigForm($this->_request);
        $this->view->form = $r->getForm();
        $this->view->report = $r;
        
        if (!$r->hasConfigErrors()) 
         {
            $result = $r->getReport();
            foreach ($r->getOutput($result) as $output)
                $this->view->content .= $output->render() . "<br /><br />";
            // default
            $default = $r->getForm()->getValue();
            unset($default['_save_']); unset($default['save']);
            $this->getSession()->reportDefaults = $default;
        }
        $this->view->display('admin/report_output.phtml');
    }

    function indexAction()
    {
        $reports = Am_Report_Abstract::getAvailableReports();
        $defaults = @$this->getSession()->reportDefaults;
        if ($defaults)
        {
            foreach ($reports as $r)
            {
                $r->getForm()->setDataSources(array(new HTML_QuickForm2_DataSource_Array($defaults)));
            }
        }
        $this->view->assign('reports', $reports);
        $this->view->display('admin/report.phtml');
    }
    public function preDispatch() 
    {
        class_exists('Am_Report', true);
        require_once 'Am/Report/Standard.php';
    }
}
