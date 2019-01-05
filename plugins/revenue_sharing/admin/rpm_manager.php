<?php


if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');

$userquery->admin_login_check();
$pages->page_redir();

if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE', 'Revenue Sharing');
}
if(!defined('SUB_PAGE')){
	define('SUB_PAGE', "RPM Manager");
}

try {

	if(isset($_POST['update_d_rpm'])){
		$params=$_POST;
		$response=$revshare->update_dafault_rpm($params);
		e($response,"m");
	}
	
	$default_rpm = $revshare->get_default_rpm();
	// Displaying warning just in case Views per matrix is not being set
	if($default_rpm['rpm']==""){
		e("Value of default rpm is not being set yet please set it","w");
	}
	assign("default_rpm",$default_rpm['rpm']);

	

	
	
} catch (Exception $e) {

	e($e->getMessage(),"e");

}


subtitle("Manage rate per according to region");
template_files(REV_SHARE_DIR.'/admin/rpm_manager.html');