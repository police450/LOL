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

if (isset($_POST["udpate-configs"])){
	$rows = array(
				  	'wowza_api_basepath',
				  	'wowza_server_name',
					'wowza_server_vhost',
				  	'wowza_api_version',
				  	'wowza_player_ip',
				  	'wowza_server_vhost',
				  	'wowza_file_directory',
				  	'wowza_source_username',
				  	'wowza_source_password',
				  	'wowza_connect_api'
				);

	foreach($rows as $field){
		$value = ($_POST[$field]);
		if(in_array($field,$num_array))
		{
			if($value <= 0 || !is_numeric($value))
				$value = 1;
		}
		$myquery->Set_Website_Details($field,$value);
	}
	e("Live Stream Settings Have Been Updated",'m');
}


subtitle("Configure Live Stream");
template_files(CB_LS_BASEDIR.'/admin/configurations.html');

?>