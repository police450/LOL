<?php

if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');

$userquery->admin_login_check();
$pages->page_redir();

if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE', 'Revenue Sharing');
}
if(!defined('SUB_PAGE')){
		define('SUB_PAGE', "Configurations");
}

try{
	

	if ($_POST['rev_update_confs']){
		$params = $_POST;

		$response = $revshare->update_rev_configs($params);
		
		e($response,"m");
	}
	
	$rev_config = $revshare->get_rev_configs();
	// Displaying warning just in case Views per matrix is not being set
	if($rev_config['rev_view_per_matrix']==""){
		e("Value of views per matrix is not being set yet please set it","w");
	}
	assign("rev_config",$rev_config);



}catch(Exception $e){
	
		e($e->getMessage(),"e");

}


subtitle("Configure views per matrix");
template_files(REV_SHARE_DIR.'/admin/basic_configs.html');

?>