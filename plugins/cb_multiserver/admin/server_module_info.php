<?php
ini_set("log_errors",true);
ini_set("error_log","error_log.txt");
$in_bg_cron = true;
if(isset($_REQUEST['module_info']))
{
	$module_info = $_REQUEST['module_info'];
}
include("../../../includes/config.inc.php");

if(isset($_REQUEST['write'])&&$_REQUEST['write']==true)
{
	$data = $_REQUEST['encoded_data'];
	$sid = $_REQUEST['server_id'];
	pr($data,true);
	$info_file_path = __DIR__."/../server_configs/module_info_".$sid.'.json';
	file_put_contents($info_file_path, $data);
	//echo 'write done';
	
}
elseif(isset($module_info)&&$module_info==true)
{
	$server_id = $_REQUEST['sid'];
	$server_query = "SELECT * FROM ".tbl("servers")." WHERE server_id = '".$server_id."'";
	$server_results = $db->_select($server_query);
	//pr($server_results,true);
	$api_info_path = $server_results[0]['server_api_path'].'/server_module_info.php';
	//echo $api_info_path;
	$call_back_path = BASEURL.'/plugins/cb_multiserver/admin/server_module_info.php';
	$module_info = array("serverinfo"=>TRUE,"call_back_path"=>$call_back_path,'server_id'=>$server_results[0]['server_id']);
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


	//curl_setopt($ch,CURLOPT_POSTFIELDS,$array);
	$charray = $ch_opts;
	$charray[CURLOPT_POSTFIELDS] = $module_info;

	curl_setopt_array($ch,$charray);

	$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

	$result = curl_exec($ch);
	//pr($result,true);
	
}
else
{
	//echo 'Info parsing failed...';
}



?>