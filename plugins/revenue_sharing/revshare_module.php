<?php

if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');

$section = get('s');
$file = get('p');

if(defined('IN_MODULE') && $section=='revshare')
{
	global $revshare;
	if($file=='edit_info')
	{
		$userquery->logincheck();

		if(isset($_POST['update_eu_details'])){
	
			$response=$revshare->update_eu_details($_POST);
			e($response,'m');

		}

		$user_details = $revshare->get_eu_details(userid());
		
		assign('user_details',$user_details);
		template_files('edit_info.html',REV_SHARE_DIR.'/templates/');
		display_it();
		exit();
	}

	if($file=='user_stats')
	{
		$userquery->logincheck();
		$user_stats = $revshare->get_stats(userid());
		
		assign('user_stats',$user_stats);
		template_files('user_stats.html',REV_SHARE_DIR.'/templates/');
		display_it();
		exit();
	}

}

?>