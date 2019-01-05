<?php

if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');

assign('subs_link',BASEURL.'/admin_area/plugin.php?folder='._PAID_SUBS_.'/admin&file=manage_subscription.php');
assign('pkgs_link',BASEURL.'/admin_area/plugin.php?folder='._PAID_SUBS_.'/admin&file=paid_packages.php');
assign('conf_link',BASEURL.'/admin_area/plugin.php?folder='._PAID_SUBS_.'/admin&file=configure.php');


$income_today = $db->select(tbl("paid_invoices")," SUM(amount_recieved) AS total ",
" curdate() = date(date_recieved) ");
$income_today = (float) $income_today[0]['total'];

//Weekly Income
$income_month = $db->select(tbl("paid_invoices")," SUM(amount_recieved) AS total ",
" CONCAT(YEAR(curdate()),MONTH(curdate())) = CONCAT(YEAR(date_recieved),MONTH(date_recieved))  ");
$income_month = $income_month[0]['total'];


assign('income_today',$income_today);
assign('income_month',$income_month);

subtitle('Paid subscriptions');

template_files('paid_subs_home.html',PAID_SUBS_DIR.'/admin');

?>