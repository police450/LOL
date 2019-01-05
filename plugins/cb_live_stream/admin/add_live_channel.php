<?php
/* 
 ***************************************************************
 | Copyright (c) 2007-2017 clipbucket.com. All rights reserved.
 | @ Author : Fahad Abbas										
 | @ Software : ClipBucket , © PHPBucket.com					
 ****************************************************************
*/

require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$pages->page_redir();



if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE', 'Live');
}
if(!defined('SUB_PAGE')){
		define('SUB_PAGE', "Live Channels");
}

if ($_POST['create-channel']){
	
	try{
	
		$params = $_POST;
		$response = $cblive->create_live_channel($params);
		if ($response){
			e("Channel (".$_POST['channel_name'].") has been created on clipbucket","m");
		}

	}catch(Exception $e){
		e($e->getMessage(),"e");
	}
}

subtitle("Manage Live Channels");

template_files(CB_LS_BASEDIR.'/admin/add_live_channel.html');

?>