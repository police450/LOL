<?php
/**
	* File: Home Ajax
	* Description: ClipBucket home page will now be Ajax based to imporve page loading
	* and to enhance user experience. This file will handle all ajax requests
	* for ClipBucket's home page
	* @since: 14th March, 2016, ClipBucket 2.8.1 
	* @author: Saqib Razzaq
	* @modified: 8th April, 2016
	*/

	require '../includes/config.inc.php';

	$request = $_POST;
	$id = $request['videoid'];
	$size = $request['size'];
	$res = get_video_details($id);
	if($res)
	{
		// $pix = thumbs_res_settings_28('260');
		$result = get_thumb($res,'default',true,false,true,true,$size);
		//pr($result,true);
		echo json_encode($result,true); 
		
		exit();
	}
	exit('no video');

?>