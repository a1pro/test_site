<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Payments
*    FileName $RCSfile$
*    Release: 4.1.10 ($Revision$)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

class Am_Grid_Filter_Payments extends Am_Grid_Filter_Abstract
{
    protected $dateField = 'dattm';
    public function isFiltered()
    {
        foreach ((array)$this->vars['filter'] as $v)
            if ($v) return true;
    }
    public function setDateField($dateField)
    {
        $this->dateField = $dateField;
    }
    protected function applyFilter()
    {
        class_exists('Am_Form', true);
        $filter = (array)$this->vars['filter'];
        $q = $this->grid->getDataSource();
        $dateField = $this->dateField;
        /* @var Am_Query $q */
        if ($filter['dat1']) 
            $q->addWhere("t.$dateField >= ?", Am_Form_Element_Date::createFromFormat(null, $filter['dat1'])->format('Y-m-d 00:00:00'));
        if ($filter['dat2']) 
            $q->addWhere("t.$dateField <= ?", Am_Form_Element_Date::createFromFormat(null, $filter['dat2'])->format('Y-m-d 23:59:59'));
        if (@$filter['text'])
            switch (@$filter['type'])
            {
                case 'invoice':
                    $q->addWhere('t.invoice_id=?d', $filter['text']);
                    break;
                case 'login':
                    $q->addWhere('login=?', $filter['text']);
                    break;
                case 'receipt':
                    $q->addWhere('receipt_id LIKE ?', '%'.$filter['text'].'%');
                    break;
                
            }
    }
    public function renderInputs()
    {
        $filter = (array)$this->vars['filter'];
        $filter['dat1'] = Am_Controller::escape(@$filter['dat1']);
        $filter['dat2'] = Am_Controller::escape(@$filter['dat2']);
        $filter['text'] = Am_Controller::escape(@$filter['text']);
        
        $options = Am_Controller::renderOptions(array(
            '' => '***', 
            'invoice'    => ___('Invoice'), 
            'login'      => ___('Username'), 
            ), @$filter['type']);
//'receipt'    => ___('Receipt'),         
    
        $start = ___("Start Date");
        $end   = ___("End Date");
        $tfilter = ___("Filter");
        $prefix = $this->grid->getId();
        return <<<CUT
<b>$start</b>        
<input type="text" name="{$prefix}_filter[dat1]" class='datepicker' value="{$filter['dat1']}" />
<b>$end</b>        
<input type="text" name="{$prefix}_filter[dat2]" class='datepicker' value="{$filter['dat2']}" />
<b>$tfilter</b>        
<input type="text" name="{$prefix}_filter[text]" value="{$filter['text']}" />
<select name="{$prefix}_filter[type]">
$options
</select>
CUT;
    }
    
    public function renderStatic()
    {
        return <<<CUT
<script type="text/javascript">
$(function(){
    $(".grid-wrap").ajaxComplete(function(){
        $('input.datepicker').datepicker({
                dateFormat:window.uiDateFormat,
                changeMonth: true,
                changeYear: true
        }).datepicker("refresh");
    });
});
</script>
CUT;
    }
}

class AdminPaymentsController extends Am_Controller_Pages
{
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission('grid_payment');
    }
    public function initPages()
    {
        $this->addPage(array($this, 'createPaymentsPage'), 'index', ___('Payment'));
        $this->addPage(array($this, 'createInvoicesPage'), 'invoices', ___('Invoice'));
    }
    function createPaymentsPage()
    {
        $query = new Am_Query($this->getDi()->invoicePaymentTable);
        $query->leftJoin('?_user', 'm', 'm.user_id=t.user_id')
            ->addField('m.login', 'login')
            ->addField("concat(m.name_f,' ',m.name_l)", 'name');
        $query->setOrder("invoice_payment_id", "desc");
        
        $grid = new Am_Grid_Editable('_payment', ___("Payments"), $query, $this->_request, $this->view);
        $grid->actionsClear();
        $grid->addField(new Am_Grid_Field_Date('dattm', ___('Date/Time')));
        
        $grid->addField('invoice_id', ___('Invoice'))->addDecorator(
            new Am_Grid_Field_Decorator_Link(
                'admin-user-payments/index/user_id/{user_id}#invoice-{invoice_id}', '_blank')
        );
        $grid->addField('receipt_id', ___('Receipt'));
        $grid->addField('paysys_id', ___('Payment System'));
        $grid->addField('amount', ___('Amount'))->setGetFunction(array($this, 'getAmount'));
        $grid->addField('tax', ___('Tax'))->setGetFunction(array($this, 'getTax'));
        $grid->addField('login', ___('Username'), false)->addDecorator(
            new Am_Grid_Field_Decorator_Link(
                'admin-users?_u_a=edit&_u_b={THIS_URL}&_u_id={user_id}', '_blank')
        );
        $grid->addField('name', ___('Name'), false);
        $grid->setFilter(new Am_Grid_Filter_Payments);
        
        $action = new Am_Grid_Action_Export();
        $action->addField(new Am_Grid_Field('receipt_id', ___('Receipt')))
                ->addField(new Am_Grid_Field('paysys_id', ___('Payment System'))) 
                ->addField(new Am_Grid_Field('amount', ___('Amount')))
                ->addField(new Am_Grid_Field('tax', ___('Tax')))
                ->addField(new Am_Grid_Field('login', ___('Username')))
                ->addField(new Am_Grid_Field('name', ___('Name')));
        $grid->actionAdd($action);
        
        return $grid;
    }
    
    function getAmount(InvoicePayment $p)
    {
        return Am_Currency::render($p->amount, $p->currency);
    }
    
    function getTax(InvoicePayment $p)
    {
        return Am_Currency::render($p->tax, $p->currency);
    }
    
    function createInvoicesPage()
    {
        $query = new Am_Query($this->getDi()->invoiceTable);
        $query->leftJoin('?_user', 'm', 'm.user_id=t.user_id')
            ->addField('m.login', 'login')
            ->addField("concat(m.name_f,' ',m.name_l)", 'name');
        $query->setOrder("invoice_id", "desc");
        
        $grid = new Am_Grid_Editable('_invoice', ___("Invoices"), $query, $this->_request, $this->view);
        $grid->actionsClear();
        $grid->addField(new Am_Grid_Field_Date('tm_added', ___('Added')));
        
        $grid->addField('invoice_id', ___('Invoice'))->addDecorator(
            new Am_Grid_Field_Decorator_Link(
                'admin-user-payments/index/user_id/{user_id}#invoice-{invoice_id}', '_blank')
        );
        $grid->addField('status', ___('Status'))->setRenderFunction(array($this, 'renderInvoiceStatus'));
        $grid->addField('paysys_id', ___('Payment System'));
        $grid->addField('_total', ___('Total'))->setGetFunction(array($this, 'getInvoiceTotal'));
        $grid->addField('login', ___('Username'), false)->addDecorator(
            new Am_Grid_Field_Decorator_Link(
                'admin-users?_u_a=edit&_u_b={THIS_URL}&_u_id={user_id}', '_blank')
        );
        $grid->addField('name', ___('Name'), false);
        $filter = new Am_Grid_Filter_Payments;
        $filter->setDateField('tm_added');
        $grid->setFilter($filter);
        
        $action = new Am_Grid_Action_Export();
        $action->addField(new Am_Grid_Field('invoice_id', ___('Invoice').'#'))
                ->addField(new Am_Grid_Field('paysys_id', ___('Payment System'))) 
                ->addField(new Am_Grid_Field('first_total', ___('First Total')))
                ->addField(new Am_Grid_Field('first_tax', ___('First Tax')))
                ->addField(new Am_Grid_Field('login', ___('Username')))
                ->addField(new Am_Grid_Field('name', ___('Name')));
        $grid->actionAdd($action);
        
        return $grid;
    }
    
    public function getInvoiceTotal(Invoice $invoice)
    {
        return $invoice->getTerms();
    } 
    
    public function renderInvoiceStatus(Invoice $invoice)
    {
        return '<td>'.$invoice->getStatusTextColor().'</td>';
    }
    
}