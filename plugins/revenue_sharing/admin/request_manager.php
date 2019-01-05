<?php

if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');

$userquery->admin_login_check();
$pages->page_redir();

if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE', 'Revenue Sharing');
}
if(!defined('SUB_PAGE')){
		define('SUB_PAGE', "Requests manager");
}

try {

	if($_POST){
		
		$userid=$_POST['userid'];
	
		if(isset($_POST['approve'])){
			$update_type='approve';
			$reponse=$revshare->update_request_status('approve',$userid);
			e($reponse,'m');
		}

		if(isset($_POST['reject'])){
			$update_type='reject';
			$reponse=$revshare->update_request_status('reject',$userid);
			e($reponse,'m');

		}

		if(isset($_POST['review_later'])){
			$update_type='review_later';
			$reponse=$revshare->update_request_status('review_later',$userid);
			e($reponse,'m');

		}
	}

	$requests = $revshare->get_users_requests();
	assign("requests",$requests);

	
} catch (Exception $e) {

	e($e->getMessage(),"e");
}

subtitle("Manage earning user requests");
template_files(REV_SHARE_DIR.'/admin/request_manager.html');

?>