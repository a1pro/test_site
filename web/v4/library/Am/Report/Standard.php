<?php

/**
 * Commonly used reports
 */
class Am_Report_Income extends Am_Report_Abstract
{
    public function __construct()
    {
        $this->title = ___("Income Report - payments minus refunds");
        $this->description = "";
    }
    
    public function getPointField() {
        return 'p.dattm';
    }
    // we have a VERY complex query here, so we will run it directly
    // without using Am_Query
    // Simulate FULL OUTER JOIN - not implemened in MYSQL
    // Usually it is better to avoid it!
    protected function runQuery() {
        $expra = $this->quantity->getSqlExpr('p.dattm');
        $exprb = $this->quantity->getSqlExpr('r.dattm');
        $this->stmt = $this->getDi()->db->queryResultOnly("
            SELECT $expra AS point, ROUND(IFNULL(SUM(p.amount/p.base_currency_multi),0),2) AS amt
                FROM ?_invoice_payment p
                WHERE p.dattm BETWEEN ? AND ?
                GROUP BY $expra
            UNION ALL
            SELECT $exprb AS point, ROUND(SUM(-r.amount/r.base_currency_multi),2) AS amt
                FROM ?_invoice_refund r
                WHERE 
                r.dattm BETWEEN ? AND ?
                GROUP BY $exprb
        ", $this->start, $this->stop,
           $this->start, $this->stop
        );
    }

    function getLines()
    {
        return array(
            new Am_Report_Line("amt", "Payments Amount, " . Am_Currency::getDefault(), "#ff00cc"),
        );
    }
}

class Am_Report_Paysystems extends Am_Report_Abstract
{
    public function __construct()
    {
        $this->title = ___("Payments by payment system breakdown");
        $this->description = "";
    }
    
    public function getPointField() {
        return 'p.dattm';
    }
    /** @return Am_Query */
    public function getQuery()
    {
        $q = new Am_Query(new InvoicePaymentTable, 'p');
        $q->clearFields();
        
        foreach ($this->getPaysystems() as $k => $ps)
        {
            $ps = $q->escape($ps);
            $q
              ->addField("ROUND(SUM(IF(p.paysys_id=$ps, p.amount/p.base_currency_multi, 0)),2)\n", 'amt_' . $k);
        }
        return $q;
    }
    
    function getPaysystems()
    {
        static $cache;
        if (!$cache)
            $cache = $this->getDi()->db->selectCol("SELECT DISTINCT paysys_id FROM ?_invoice_payment");
        return $cache;
    }
    
    function getLines()
    {
        $ret = array();
        foreach ($this->getPaysystems() as $k => $ps)
        {
            $ret[] = new Am_Report_Line('amt_' . $k, ucfirst($ps));
        }
        return $ret;
    }
}

class Am_Report_Products extends Am_Report_Abstract
{
    public function __construct()
    {
        $this->title = ___("Payments by products breakdown");
        $this->description = "";
    }
    
    public function _initConfigForm(Am_Form $form)
    {
        $sel = $form->addMagicSelect('products')->setLabel(___("Products\nkeep empty to report all products"));
        $sel->loadOptions($this->getDi()->productTable->getOptions());
    }
    
    protected function runQuery()
    {
        $expra = $this->quantity->getSqlExpr('p.dattm');
        
        $fields = array();
        foreach ($this->getProducts() as $k => $v)
        {
            $fields[] = "ROUND(SUM((SELECT p.amount * LEAST(1, IF(p.is_first, ii.first_total/p.invoice_total, ii.second_total/p.invoice_total)) FROM ?_invoice_item ii WHERE p.invoice_id=ii.invoice_id AND item_id=$k)),2) AS amt_$k";
        }
        
        $db = $this->getDi()->db;
        $db->query("DROP TEMPORARY TABLE IF EXISTS ?_invoice_payment_report_tmp");
        $db->query("CREATE TEMPORARY TABLE ?_invoice_payment_report_tmp (
            dattm DATETIME not null,
            invoice_id int not null,
            is_first smallint,
            amount decimal(12,2),
            invoice_total decimal(12,2)
        )    
        ");
        $db->query("
            INSERT INTO ?_invoice_payment_report_tmp
            SELECT p.dattm, p.invoice_id
                ,i.first_total > 0 && 
                    NOT EXISTS (SELECT * FROM ?_invoice_payment pp
                        WHERE pp.invoice_id=p.invoice_id AND pp.invoice_payment_id < p.invoice_payment_id)
                    AS is_first
                ,p.amount
                ,(SELECT(IF(is_first, i.first_total, i.second_total))) AS invoice_total
            FROM ?_invoice_payment p
                LEFT JOIN ?_invoice i USING (invoice_id)
            WHERE dattm BETWEEN ? AND ? AND amount > 0
            HAVING invoice_total > 0
        ", $this->start, $this->stop);
        
        $fields = "\n,".implode("\n,", $fields);
        $this->stmt = $this->getDi()->db->queryResultOnly("
            SELECT 
                $expra as point
                $fields
            FROM ?_invoice_payment_report_tmp p
            GROUP BY $expra
            ", $this->start, $this->stop);
    }
    
    function getProducts()
    {
        static $cache;
        if (!$cache)
        {
            $vars = $this->form->getValue();
            $cache = $this->getDi()->db->selectCol("SELECT 
                DISTINCT product_id as ARRAY_KEY, title 
                FROM ?_product
                {WHERE product_id IN (?a)}", !empty($vars['products']) ? (array)$vars['products'] : DBSIMPLE_SKIP);
        }
        return $cache;
    }
    
    function getLines()
    {
        $ret = array();
        foreach ($this->getProducts() as $k => $ps)
        {
            $ret[] = new Am_Report_Line('amt_' . $k, ucfirst($ps));
        }
        return $ret;
    }
}

class Am_Report_NewVsExisting extends Am_Report_Abstract
{
    public function __construct()
    {
        $this->title = ___("Payments by New vs Existing members");
        $this->description = "";
    }
    
    protected function runQuery()
    {
        $expra = $this->quantity->getSqlExpr('p.dattm');
        $exprpp = $this->quantity->getSqlExpr('pp.dattm');
        
        $this->stmt = $this->getDi()->db->queryResultOnly("
            SELECT 
                $expra as point,
                SUM(p.amount) as total,
                SUM(IF(
                    EXISTS (SELECT * FROM ?_invoice_payment pp WHERE user_id=p.user_id AND $exprpp < point)
                , 0, p.amount)) as new,
                SUM(IF(
                    EXISTS (SELECT * FROM ?_invoice_payment pp WHERE user_id=p.user_id AND $exprpp < point)
                , p.amount, 0)) as existing
            FROM ?_invoice_payment p
            WHERE dattm BETWEEN ? AND ? AND amount > 0
            GROUP BY $expra
            ", $this->start, $this->stop);
    }
    
    function getLines()
    {
        $ret = array();
        $ret[] = new Am_Report_Line('total', ___("Payments total"));
        $ret[] = new Am_Report_Line('existing', ___("Payments from existing customers"));
        $ret[] = new Am_Report_Line('new', ___("Payments from new customers")); // who did not pay earlier in the point period
        return $ret;
    }
}

class Am_Report_SignupsCount extends Am_Report_Abstract
{
    public function __construct()
    {
        $this->title = ___("Count of user signups");
        $this->description = "including pending records";
    }
    
    protected function runQuery()
    {
        $expra = $this->quantity->getSqlExpr('u.added');
        
        $this->stmt = $this->getDi()->db->queryResultOnly("
            SELECT 
                $expra as point,
                COUNT(user_id) as cnt
            FROM ?_user u
            WHERE added BETWEEN ? AND ?
            GROUP BY $expra
            ", $this->start, $this->stop);
    }
    
    function getLines()
    {
        $ret = array();
        $ret[] = new Am_Report_Line('cnt', ___("Count of signups"));
        return $ret;
    }
}