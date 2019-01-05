<?php
require '../../../../includes/config.inc.php';

$mode=$_POST['mode'];

switch($mode)
{	
	case "upload_file":
	{

	$sid = $_POST['server_get'];
	$server = $multi_server->get_server($sid);
	 #echo $_FILES['file']['name'];
	#pr($server,true); 
	$serverApi = $server['server_api_path'].'/actions/file_uploader.php/';
	 
	 //Reqest
	$postvars['mode'] = 'upload';
	$_POST['server_get_files'];
   	$postvars['Filedata'] =  "@".$_POST['server_get_files'];
   	//$postvars['Filedata'] =  "@".PLUG_DIR.'/'.$cb_multiserver.'/admin/test/logicaly2.mp4';
	
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $serverApi);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars); 
    /* Tell cURL to return the output */
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
     /* Tell cURL NOT to return the headers */
    curl_setopt($ch, CURLOPT_HEADER, false);
    $response = curl_exec($ch);

    $results = json_decode($response);
    /* Check HTTP Code */
  	$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if($status_code != 200){
    	$results->error = 'Connection Error';
    }else{
    	$results->error = 'Permission Error : on Temp Folder';
    }
    curl_close($ch);

    
    $results->server_api_path = $server['server_api_path'];
    
    echo json_encode($results);
	}
	break;
	case 'next_temp':
	{
		$file_name = $_POST['file_name'];

	}
	break;
}
if($_POST['action']=='test_file')
 {
	 

   	$status_code;	
 }

?>