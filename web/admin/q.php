<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Quick lookup 
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2926 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

require "../config.inc.php";
require "login.inc.php";

$vars = get_input_vars();
$res = null;
$qq = strip_tags($vars['q']);
if ($qq != ''){
	$d = & amDb();
	$qqq = "%$qq%";
	// search for members
	$ur = $d->select("SELECT 
		concat('users.php?action=edit&member_id=', member_id) as link,
		member_id, login, email, name_f, name_l
		FROM ?_members 
		WHERE
		   member_id = ? 
		OR login LIKE ? 
		OR email LIKE ?
		OR name_f LIKE ?
		OR name_l LIKE ?
		ORDER BY login
		LIMIT 20
		", $qq, $qqq, $qqq, $qqq, $qqq);
	$pr = $d->select("SELECT 
			concat('users.php?action=edit_payment&member_id=', p.member_id, '&payment_id=',p.payment_id) as link,
			u.member_id, u.login, u.email, u.name_f, u.name_l, 
			p.payment_id, p.receipt_id, p.paysys_id
		FROM ?_payments p LEFT JOIN ?_members u USING (member_id) 
		WHERE
			p.payment_id = ? 
		 OR p.receipt_id LIKE ? 
		 ORDER BY payment_id
		 LIMIT 20
		 ", $qq, $qqq);
}	

$t = new_smarty();
$t->assign('qq', $qq);
$t->assign('ur', $ur);
$t->assign('pr', $pr);
$t->display('admin/q.html');
