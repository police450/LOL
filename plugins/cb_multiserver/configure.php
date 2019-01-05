<?php

/**
 * This file will execute cURL command to configure
 * server and upload files
 */

include("../../includes/config.inc.php");

$post_fields = $_POST;
//$post_fields = array('post_fields'=>json_encode($post_fields));


if ($post_fields['server_main_role'] == 'c') {
	global $multi_server;
	$assoc_server_data = $multi_server->get_server($post_fields['assoc_server_id']);
	$post_fields['streaming_server_secret'] = $assoc_server_data['secret_key'];
	$post_fields['streaming_server_key'] = "bG9jYWxob3N0NDIxYWE5MGUwNzlmYTMyNmI2NDk0ZjgxMmFkMTNlNzk=";
	$post_fields['streaming_server_baseurl'] = $assoc_server_data['server_api_path'];
	#pex($post_fields,true);
}

$ch_opts = array(

 CURLOPT_POST=>true,
 CURLOPT_POSTFIELDS=>$post_fields,
 CURLOPT_RETURNTRANSFER=> true,
 CURLOPT_BINARYTRANSFER => true,
 CURLOPT_HEADER => false,
 CURLOPT_SSL_VERIFYHOST=> false, 
 CURLOPT_SSL_VERIFYPEER=> false,
 CURLOPT_HTTPHEADER => array("Expect:"),
);


$config_url = post('baseurl').'/configure.php';
$ch = curl_init($config_url);
curl_setopt_array($ch,$ch_opts);
$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$result = curl_exec($ch);
if ( $error = curl_error($ch) )
echo 'ERROR: ',$error;	
curl_close($ch);

if(!$result)
	echo json_encode(array("msg"=>"Server has been update"));
else
	echo json_encode(array("err"=>"Something went wrong :/","result"=>$result));
?>