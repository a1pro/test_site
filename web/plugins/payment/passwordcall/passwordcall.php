<?php
import_request_variables("G");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html><head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<meta http-equiv="imagetoolbar" content="no" />
<title>SOFORTZUGANG MIT PASSWORTCALL.DE</title>
<style type="text/css">
<!--
#pwc {margin:5px auto; text-align:center; font:normal 10pt Verdana;}
#pwc h1  {font:bold 24pt Verdana; background-color:#C0B9C0; padding:3px;}
#pwc h2  {font:bold 12pt Verdana; background-color:#E0E0E0; padding:3px;}
table.pwc {margin:0 auto;}
td.pwctd {text-align:center; vertical-align:top; padding:5px;}
-->
</style>
</head>
<body text="#000000" bgcolor="#FFFFFF">

<div id="pwc">
<table cellspacing=0 class="pwc">
<tr>
<?
if($product_id==$prdid1){
  printf("<td class=\"pwctd\"><h1>%s</h1>",$aname1);
  if($aname1=="Test Abo") $tabo=1;
  if($tarif1=="T4"){
    $t4=1;
    echo"<h2>Schweiz und &Ouml;sterreich</h2><script type=\"text/javascript\" src=\"http://box.passwortcall.de/gatejava.php?wmid=$wmid&amp;agbid=$agbid1&amp;member_id=$member_id&amp;product_id=$product_id&amp;transaction_ref=$transaction_ref\"></script></td>";
    }
  elseif($tarif1=="T5"){
    echo"<h2>Deutschland</h2><script type=\"text/javascript\" src=\"http://box.passwortcall.de/t5gatejava.php?wmid=$wmid&amp;agbid=$agbid1&amp;member_id=$member_id&amp;product_id=$product_id&amp;transaction_ref=$transaction_ref\"></script></td>";
    }
  else echo"Fehler: Kein Tarif eingetragen (T4 oder T5) oder fehlerhaft. Siehe amember Control Panel!";
  }

if($product_id==$prdid2){
  printf("<td class=\"pwctd\"><h1>%s</h1>",$aname2);
  if($aname2=="Test Abo") $tabo=1;
  if($tarif2=="T4"){
    $t4=1;
    echo"<h2>Schweiz und &Ouml;sterreich</h2><script type=\"text/javascript\" src=\"http://box.passwortcall.de/gatejava.php?wmid=$wmid&amp;agbid=$agbid2&amp;member_id=$member_id&amp;product_id=$product_id&amp;transaction_ref=$transaction_ref\"></script></td>";
    }
  elseif($tarif2=="T5"){
    echo"<h2>Deutschland</h2><script type=\"text/javascript\" src=\"http://box.passwortcall.de/t5gatejava.php?wmid=$wmid&amp;agbid=$agbid2&amp;member_id=$member_id&amp;product_id=$product_id&amp;transaction_ref=$transaction_ref\"></script></td>";
    }
  else echo"Fehler: Kein Tarif eingetragen (T4 oder T5) oder fehlerhaft. Siehe amember Control Panel!";
  }

echo"</tr><tr>";

if($product_id==$prdid3){
  printf("<td class=\"pwctd\"><h1>%s</h1>",$aname3);
  if($aname3=="Test Abo") $tabo=1;
  if($tarif3=="T4"){
    $t4=1;
    echo"<h2>Schweiz und &Ouml;sterreich</h2><script type=\"text/javascript\" src=\"http://box.passwortcall.de/gatejava.php?wmid=$wmid&amp;agbid=$agbid3&amp;member_id=$member_id&amp;product_id=$product_id&amp;transaction_ref=$transaction_ref\"></script></td>";
    }
  elseif($tarif3=="T5"){
    echo"<h2>Deutschland</h2><script type=\"text/javascript\" src=\"http://box.passwortcall.de/t5gatejava.php?wmid=$wmid&amp;agbid=$agbid3&amp;member_id=$member_id&amp;product_id=$product_id&amp;transaction_ref=$transaction_ref\"></script></td>";
    }
  else echo"Fehler: Kein Tarif eingetragen (T4 oder T5) oder fehlerhaft. Siehe amember Control Panel!";
  }

if($product_id==$prdid4){
  printf("<td class=\"pwctd\"><h1>%s</h1>",$aname4);
  if($aname4=="Test Abo") $tabo=1;
  if($tarif4=="T4"){
    $t4=1;
    echo"<h2>Schweiz und &Ouml;sterreich</h2><script type=\"text/javascript\" src=\"http://box.passwortcall.de/gatejava.php?wmid=$wmid&amp;agbid=$agbid4&amp;member_id=$member_id&amp;product_id=$product_id&amp;transaction_ref=$transaction_ref\"></script></td>";
    }
  elseif($tarif4=="T5"){
    echo"<h2>Deutschland</h2><script type=\"text/javascript\" src=\"http://box.passwortcall.de/t5gatejava.php?wmid=$wmid&amp;agbid=$agbid4&amp;member_id=$member_id&amp;product_id=$product_id&amp;transaction_ref=$transaction_ref\"></script></td>";
    }
  else echo"Fehler: Kein Tarif eingetragen (T4 oder T5) oder fehlerhaft. Siehe amember Control Panel!";
  }
?>
</tr>
</table>

<?
if(date("i")==0){
  $stunden = 24-date("H");
  $minuten = "00";
  }
else{
  $stunden = 23-date("H");
  $minuten = 60-date("i");
  }

if($tabo) printf("<p><b>Aktuelle Systemzeit:</b> %s<br />Ein 24 Stunden Testabo endet heute um 24:00 Systemzeit, also in <b>%s Stunden</b> und <b>%s Minuten</b></p>",date("d M Y, H:i"),$stunden,$minuten);

if($t4) echo"<p>Bitte die angegebene Anrufdauer abwarten und das angesagte Passwort in das entsprechende Feld eingeben!</p>";

?>

</div>
</body>
</html>