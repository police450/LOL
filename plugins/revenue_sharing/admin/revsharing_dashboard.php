<?php
if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');

$userquery->admin_login_check();
$pages->page_redir();

if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE', 'Revenue Sharing');
}
if(!defined('SUB_PAGE')){
	define('SUB_PAGE', "Dashboard");
}


try {
	
	$notifications = $revshare->get_notifications();
	assign("notifications",$notifications);
	
	$graph_data = $revshare->get_stats();
	assign("graph_data",$graph_data);


}catch (Exception $e) {

	e($e->getMessage(),"e");

}


subtitle("Revenue sharing dashboard");
template_files(REV_SHARE_DIR.'/admin/revsharing_dashboard.html');