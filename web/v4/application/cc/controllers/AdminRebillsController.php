<?php

class Cc_AdminRebillsController extends Am_Controller_Grid
{
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission('cc');
    }
    public function emptyZero($v)
    {
        return $v ? $v : '';
    }
    protected function createAdapter()
    {
        $q = new Am_Query(new CcRebillTable);
        $q->clearFields();
        $q->groupBy('rebill_date');
        $q->addField('rebill_date');
        $q->addField('COUNT(t.rebill_date)', 'total');
        $q->addField('SUM(IF(t.status=0, 1, 0))', 'status_0');
        $q->addField('SUM(IF(t.status=1, 1, 0))', 'status_1');
        $q->addField('SUM(IF(t.status=2, 1, 0))', 'status_2');
        $q->addField('SUM(IF(t.status=3, 1, 0))', 'status_3');
        $q->addField('SUM(IF(t.status=4, 1, 0))', 'status_4');
        $u = new Am_Query(new InvoiceTable, 'i');
        $u->groupBy('rebill_date');
        $u->clearFields()->addField('i.rebill_date');
        for ($i=0;$i<6;$i++)
            $u->addField('(NULL)');
        $u->leftJoin('?_cc_rebill', 't', 't.rebill_date=i.rebill_date');
        $u->addWhere('i.rebill_date IS NOT NULL');
        $u->addWhere('t.rebill_date IS NULL');
        $q->addUnion($u);
        $q->addOrder('rebill_date');
        return $q;
    }
    public function createGrid()
    {
        $grid = new Am_Grid_ReadOnly('_r', 'Rebills by Date', $this->createAdapter(), $this->_request, $this->view);
        $grid->setPermissionId('cc');
        $grid->addField('rebill_date', 'Date', true)->setRenderFunction(array($this, 'renderDate'));
        $grid->addField('status_0', 'Processing Not Finished', true)->setFormatFunction(array($this, 'emptyZero'));
        $grid->addField('status_1', 'No CC Saved', true)->setFormatFunction(array($this, 'emptyZero'));
        $grid->addField('status_2', 'Error', true)->setFormatFunction(array($this, 'emptyZero'));
        $grid->addField('status_3', 'Success', true)->setFormatFunction(array($this, 'emptyZero'));
        $grid->addField('status_4', 'Exception!', true)->setFormatFunction(array($this, 'emptyZero'));
        $grid->addField('total', 'Total Records', true)->setFormatFunction(array($this, 'emptyZero'));
        $grid->addField('_action', '', true)->setRenderFunction(array($this, 'renderLink'));
        return $grid;
    }
    public function renderDate(CcRebill $obj)
    {
        $raw = $obj->rebill_date;
        $d = amDate($raw);
        return $this->renderTd("$d<input type='hidden' name='raw-date' value='$raw' />", false);
    }
    public function renderLink(CcRebill $obj)
    {
        return $this->renderTd("<a href='javascript:' class='run' id='run-{$obj->rebill_date}'>run</a>", false);
    }
    public function getInvoiceLink(CcRebill $record)
    {
        return $this->renderTd($record->invoice_id, false);
    }
    public function init()
    {
        parent::init();
        
        $this->view->headScript()->appendScript($this->getJs());
        $this->view->placeholder('after-content')->append(
            "<br /><br />" .
            "<div id='detail'></div>" .
            "<div id='run-form' style='display:none'>" . (string)$this->createRunForm() . "</div>");
    }
    
    public function getJs()
    {
        return <<<CUT
$(function(){
    $("#grid-r a.run").click(function(event){
        event.stopPropagation();
        var date = $(this).attr("id").replace(/^run-/, '');
        $("#detail").load(window.rootUrl + "/cc/admin-rebills/run", { date : date});
        
    });
    $("#grid-r tr").click(function(){
        var date = $("input[name='raw-date']", this).val();
        if (!date) return; // header ?
        $("tr.selected").removeClass("selected");
        $(this).addClass("selected");
        $("#detail").load(window.rootUrl + "/cc/admin-rebills/detail", { date : date}, function(){
            $(".grid-wrap").ngrid();
        });
    });
    $("#detail form").live('submit', function(){
        $(this).ajaxSubmit({target: '#detail'});
        return false;
    });
});
CUT;
    }
    public function renderRun()
    {
        return (string)$form;
    }
    public function createRunForm()
    {
        $form = new Am_Form;
        $form->setAction($this->getUrl(null, 'run'));
        
        $s = $form->addSelect('paysys_id')->setLabel('Choose a plugin');
        $s->addRule('required', 'This field is required');
        foreach ($this->getModule()->getPlugins() as $p)
            $s->addOption($p->getTitle(), $p->getId());
        $form->addDate('date')->setLabel('Run Rebill Manually')->addRule('required', 'This field is required');
        $form->addSubmit('run', array('value'=>'Run'));
        $form->setWidth('450px');
        return $form;
    }
    public function detailAction()
    {
        $date = $this->getFiltered('date');
        if (!$date) throw new Am_Exception_InputError("Wrong date");
        $grid = $this->createDetailGrid($date);
        $grid->isAjax(false);
        $grid->runWithLayout('admin/layout.phtml');
    }
    protected function createDetailGrid($date)
    {
    //    public $textNoRecordsFound = "No rebills today - most possible cron job was not running.";
        $q = new Am_Query($this->getDi()->ccRebillTable);
        $q->addWhere('rebill_date=?', $date);
        
        $grid = new Am_Grid_ReadOnly('_dd', "Detailed Rebill Report For [$date]", $q, $this->_request, $this->view);
        $grid->setPermissionId('cc');
        $grid->addField(new Am_Grid_Field_Date('tm_added', 'Started', true));
        $grid->addField('invoice_id', 'Invoice#', true);
        $grid->addField(new Am_Grid_Field_Date('rebill_date', 'Date', true))->setFormatDate();
        $grid->addField('status', 'Status', true)->setFormatFunction(array('CcRebill', 'getStatusText'));
        $grid->addField('status_msg', 'Message');
        return $grid;
    }
    public function runAction()
    {
        $date = $this->getFiltered('date');
        if (!$date) throw new Am_Exception_InputError("Wrong date");
        
        $form = $this->createRunForm();
        if ($form->isSubmitted() && $form->validate())
        {
            $value = $form->getValue();
            return $this->doRun($value['paysys_id'], $value['date']);
        } else {
            echo $form;
        }
    }
    public function doRun($paysys_id, $date)
    {
        $this->getDi()->plugins_payment->load($paysys_id);
        $p = $this->getDi()->plugins_payment->get($paysys_id);
        $p->ccRebill($date);
        echo "Done for $date";
    }
}
