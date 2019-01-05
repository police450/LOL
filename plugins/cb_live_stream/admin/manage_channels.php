<?php
/* 
 ***************************************************************
 | Copyright (c) 2007-2010 Clip-Bucket.com. All rights reserved.
 | @ Author : ArslanHassan										
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



if (isset($_POST["update-channel"])){
	try{
		$channel_id = (int)$_GET["edit"];
		$update_params = $_POST;
		$update_params["live_channel_id"] = $channel_id;

		$updated = $cblive->update_live_channel($update_params);
		if ($updated){
			e("Channel has been updated successfully ! ","m");
		}else{
			e("Something went wrong in updating this channel ! ","e");
		}
	}catch(Exception $e){
		e($e->getMessage(),"e");
	}
}


if (isset($_GET["edit"])){

	$channel_id = (int)$_GET["edit"];
	$channel = $cblive->get_live_channels(array("live_channel_id"=>$channel_id));
	if ($channel){
		assign("channel",$channel[0]);
	}else{
		e("No channel found ! ","e");
	}
}


if (isset($_GET["remove"])){
	$channel_id = (int)$_GET["remove"];
	$channel_det = $cblive->delete_live_channel($channel_id);
	if ($channel_det){
		e("Channel has been deleted successfully ! ","m");
	}else{
		e("Unable to delete channel  ! ","e");
	}
}


$channels = $cblive->get_live_channels();
assign("channels",$channels);

subtitle("Manage Live Channels");
template_files(CB_LS_BASEDIR.'/admin/manage_channels.html');

?>