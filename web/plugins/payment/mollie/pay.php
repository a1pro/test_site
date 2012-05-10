<?php
  session_start();
  error_reporting(E_ALL); 
function mollie_header()
{
	echo <<<MOL
<html>
<head>
<title>Betaalpagina</title>
<style type="text/css">
body { margin: 10px; }
body,th,td,p { font:small "Trebuchet MS",Verdana,Arial,Sans-serif; }
a { color: black; }
a:visited { color: black; }
</style>
</head>
<body>
<div align="center">
<h2>Betaalpagina</h2>
Hier moet uiteraard uw website layout komen....<br />Het betaalscherm is slechts de code die u hieronder vind.<br />
<br />
<br />
<div style="background-color: #f2f2f2; border: 1px solid silver; width: 350px; padding: 6px;">
MOL;
};

    require('classes/class.micropayment-mollie.php');
    
    $m = new micropayment();
    
    $m->setPartnerID($_SESSION['mollie_id']); # change this to your partner ID
    if (isset($_GET['c']) and is_numeric($_GET['c'])) $m->setCountry($_GET['c']);
    $m->setAmount(sprintf("%.2f",$_SESSION['mollie_amount'])); # Set payment amount to € 0,50
    
    if (isset($_GET['action']) and $_GET['action'] == 'check' and isset($_SESSION['servicenumber']) and isset($_SESSION['paycode']) and $_SESSION['servicenumber'] and $_SESSION['paycode']) {
        $m->setServicenumber($_SESSION['servicenumber']);
        $m->setPaycode($_SESSION['paycode']);
        $m->checkPayment();
        
        if ($m->payed) {
			$data = unserialize($_SESSION['mollie_data']);
			$_GET['payment_id'] = $data['payment_id'];
			$_GET['member_id'] = $data['member_id'];
			$_GET['product_id'] = $data['product_id'];
			$paysys_id = "mollie";
			include "../../../thanks.php";
			exit;
        }
        else {
            echo '<font color=red><b>Betaling is niet afgerond,<br />volg de onderstaande instructies!</b></font><br /><br />';
        }
    }
    
    if (!$m->payed) {
		mollie_header();
        include('includes/include.paymentscreen.php');
    }
?>
</div>
</div>
</body>
</html>