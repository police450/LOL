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
		define('SUB_PAGE', "Wowza Applications");
}


if ($_POST['create-application']){
	try{
	
		$params = $_POST;
		$w_reponse = $wowza->create_application($params);    
	    if ($w_reponse["success"] == true){
	    	e($w_reponse["message"],"m");
	    }else{
	    	throw new Exception("Something went wrong in creating an application at wowza server");
	    }
	
	}catch(Exception $e){
		e($e->getMessage(),"e");
	}
}

if ( isset($_GET["view"]) ){

	try{
	
		$app_name = $_GET["view"];
		$app = $wowza->app($app_name);
		assign("app",$app);
	
	}catch(Exception $e){
		e($e->getMessage(),"e");
	}
	
	
}

if (isset($_GET["remove"])){

	try{
	
		$app_name = $_GET["remove"];
		$app = $wowza->remove_application($app_name);
		assign("app",$app);
	
	}catch(Exception $e){
		e($e->getMessage(),"e");
	}
	
}


try {
	$applications = $wowza->applications();
	assign('applications',$applications);
} catch (Exception $e) {
	e($e->getMessage(),"e");
}



subtitle("Manage Wowza Applications");

template_files(CB_LS_BASEDIR.'/admin/manage_wowza_application.html');

?>