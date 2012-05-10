<?php
/*
=======================================================================
 File      : pay.php
 Author    : Mollie B.V.
 Version   : 1.1 (Oct 2007)
 More information? Go to www.mollie.nl
========================================================================
*/


/* below we'll fetch the payment information to present it to the user

   in case user is sent back to this payment-screen because payment failed,
   we don't need to refetch a new servicenumber and paycode, how smart ;-) */

if ($m->servicenumber and $m->paycode) {
    $gotpayinfo = true;
} else {
    $gotpayinfo = $m->getPayInfo();
}

if ($gotpayinfo) {
    $cur = '';
    if ($m->currency == 'eur') {
        $cur = '&euro;'; #  €;
    } elseif ($m->currency == 'dollar') {
        $cur = '$';
    } elseif ($m->currency == 'gbp') {
        $cur = '&pound;'; #  £;
    }
    
    $_SESSION['servicenumber'] = $m->servicenumber;
    $_SESSION['paycode'] = $m->paycode;
    
    # landen keuze
    ?>
    <small>Kies land voor de betaling:</small><br />
    <table>
    <tr>
    <td><a href="./pay.php?c=31"><img src="./images/flag-31.gif" width="20" height="12" border="" alt="flag 31" style="border: 1px solid black" /></a></td>
    <td><a href="./pay.php?c=31">Nederland</a></td>
    <td width="10"> </td>
    <td><a href="./pay.php?c=32"><img src="./images/flag-32.gif" width="20" height="12" border="" alt="flag 31" style="border: 1px solid black" /></a></td>
    <td><a href="./pay.php?c=32">Belgi&euml;</a></td>
    </tr>
    </table>
    <br />
    <?
    echo 'Om ' . $cur . number_format($m->amount, 2, ',', '.') . ' af te rekenen moet je het volgende doen:<br /><br />';
    echo '<font size="4"><b>Bel ' . $m->servicenumber . '</b></font><br />';
    
    echo '<small>';
    if ($m->mode == 'ppc') {
        echo $cur . number_format($m->costpercall, 2, ',', '.') .' per gesprek';
    } elseif ($m->mode == 'ppm') {
        echo $cur . number_format($m->costperminute, 2, ',', '.') .' per minuut, c.a. ' . $m->duration . ' seconden';
        
        # place an iframe for live display of the payment-progress ?
    }
    echo '</small>';
    
    echo '<br />';
    echo 'en toets de volgende code in: <font size="4"><b>' . $m->paycode . '</b></font><br /><br />';
    echo '<form method="get" action="./pay.php">
            <input type="hidden" name="action" value="check" />
            <input type="submit" value="Klik hier na het betalen!">
          </form>';
} else {
    echo 'Kon betaalinformatie niet ophalen.';
}

?>