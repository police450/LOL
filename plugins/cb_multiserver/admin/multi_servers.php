<?php

/**
 * This file is used to handle all admin functions
 * such as adding new server, updating existing one or deleteing it
 */
 if(!defined('SUB_PAGE')){
    define('SUB_PAGE', "CB Multiserver Manager");
}
 
 if(isset($_POST['add_server']))
 {
	 $multi_server->add_server($_POST);
	 if(!error()) $_POST = '';
 }
 
 if($_GET['action']=='add_server')
 {
	 assign('mode','add');
 }

 if($_GET['action']=='edit')
 {
	$sid = $_GET['id'];
	$server = $multi_server->get_server($sid);

	if($server)
	{
		if(isset($_POST['update']))
		{
			$_POST['sid'] = $sid;
			$multi_server->update_server($_POST);
			$server = $multi_server->get_server($sid);
		}
		$server = $multi_server->get_server($sid);
		assign("mode","edit");
		assign("server",$server);
		//pr($server,true);
	}else
		e("Server does not exist");
 }
 
 if($_GET['action']=='view_stats')
 {
	 $sid = $_GET['id'];
	 $server = $multi_server->get_server($sid);
	 
	 //Gett Stats
	 $ch = curl_init($server['server_api_path'].'/stats_generater.php');
	 curl_setopt($ch,CURLOPT_POST,true);
	 curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
	 curl_setopt($ch,CURLOPT_POSTFIELDS,array("get_stats"=>'yes'));
	 curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	 curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,8);
	 curl_setopt($ch,CURLOPT_HTTPHEADER,array('Expect:'));
	 curl_setopt($ch,CURLOPT_TIMEOUT,15);
	 $result = curl_exec($ch);
	 $results = json_decode($result,true);
	 curl_close($ch);
	 assign('stats',$results);
	 assign('curMonth',date("m"));
	 assign('curYear',date("Y"));

	 $ch = curl_init($server['server_api_path'].'/stats_generater.php');
	 curl_setopt($ch,CURLOPT_POST,true);
	 curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
	 curl_setopt($ch,CURLOPT_POSTFIELDS,array("gen_stats"=>'yes','type'=>'month','date'=>date('Y-m-d')));
	 curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	 curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,8);
	 curl_setopt($ch,CURLOPT_TIMEOUT,15);
	 $result = curl_exec($ch);
	 curl_close($ch);
	 
	 assign('statsData',$result);
	 assign("server",$server);
	 assign('mode','view_stats');
 }
 
	
	 if($_POST['update_configurations']=='update_configurations')
	 {
		 $serverid = $_GET['id'];
		 
		 //Updaing Watermark

		 if($_FILES['watermark_file']['tmp_name'])
		 {
			 $ext = getExt($_FILES['watermark_file']['name']);
			 if(strtolower($ext)!='png')
			 	e("Only png watermark is allowed");	
			 else
			 {
				 $tmpFile = TEMP_DIR.'/tempwatermark.png';
				 move_uploaded_file($_FILES['watermark_file']['tmp_name'],$tmpFile);
				 $waterMark = file_get_contents($tmpFile);
				 unlink($tmpFile);
				 if($multi_server->updateWatermark($serverid,$waterMark))
				 $_POST['has_photo_watermark'] = 'yes';
			 }
		 }
		 
		 if($_FILES['video_watermark_file']['tmp_name'])
		 {
			 $ext = getExt($_FILES['video_watermark_file']['name']);
			 if(strtolower($ext)!='png')
			 	e("Only png watermark is allowed");	
			 else
			 {
				 $tmpFile = TEMP_DIR.'/tempvideowatermark.png';
				 move_uploaded_file($_FILES['video_watermark_file']['tmp_name'],$tmpFile);
				 $videowaterMark = file_get_contents($tmpFile);
				 unlink($tmpFile);
				 if($multi_server->updatevideoWatermark($serverid,$videowaterMark))
				 $_POST['has_video_watermark'] = 'yes';
			 }
		 }

		 if ($_POST['server_main_role'] == 'c') {
		 	if (empty($_POST['assoc_server_id'])) {
		 		e("You must selecte a streaming server for conversion server");
		 		return false;
		 	}
		 }

		 $multi_server->updateServerConfigs($serverid,$_POST);
		 e("Server details have been updated","m");
		 
	 }


	if($_GET['action']=='configure')
	{
		assign("mode","configure");
		$sid = $_GET['id'];
	 	$server = $multi_server->get_server($sid);
	 	
	 	assign('server',$server);
		//Geting configs
		$configs = $multi_server->getServerConfigs($_GET['id']);
		#pex($configs,true);
		assign('server_confing',$configs);
	}
	if($_GET['action']=='module_info')
	{
		$sid = $_GET['id'];
		include('server_module_info.php');
		$module_file_info_path = __DIR__.'/../server_configs/module_info_'.$sid.'.json';
		if(file_exists($module_file_info_path))
		{
			$info = file_get_contents($module_file_info_path);
			$info = json_decode($info,true);
			//pr($info,true);
			assign('module_info',$info);
			template_files('server_module_info.html',PLUG_DIR.'/'.$cb_multiserver.'/admin');
		}
	}


	if($_GET['action']=='module_info_refresh')
	{
		$api_info_path = PLUG_URL.'/'.$cb_multiserver.'/admin/server_module_info.php?module_info=true&include_config=true&sid='.$_GET['id'];
		$ch = curl_init($api_info_path);	
		$ch_opts = array(
		 CURLOPT_POST=>true,
		 CURLOPT_RETURNTRANSFER=> true,
		 //CURLOPT_BINARYTRANSFER => true,
		 CURLOPT_HEADER => false,
		 CURLOPT_SSL_VERIFYHOST=> false, 
		 CURLOPT_SSL_VERIFYPEER=> false,
		 CURLOPT_HTTPHEADER => array("Expect:"),
		);
		$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		$exec = curl_exec($ch);
		#pr($exec,true);
		e("Module info has been refreshed","m");
	}


 
 
 if($_POST['server_name'])
 	e("Server has been added","m");
	
 if($_GET['action']=='activate')
 	$multi_server->action("activate",mysql_clean($_GET['id']));

 if($_GET['action']=='deactivate')
 	$multi_server->action("deactivate",mysql_clean($_GET['id']));
	
 if($_GET['action']=='delete')
 	$multi_server->action("delete",mysql_clean($_GET['id']));
 
assign("_link","plugin.php?folder=$cb_multiserver/admin&file=multi_servers.php");

assign("_link_test","plugin.php?folder=$cb_multiserver/admin/test&file=multi_servers_test.php");

template_files('multi_servers.html',PLUG_DIR.'/'.$cb_multiserver.'/admin');

?>