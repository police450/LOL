<?php
 
 include("../../includes/admin_config.php");
 
 
 if(!has_access('admin_access',true))
         exit('You are not an administrator...');
 
 $server = $_REQUEST['server'];
 $path = str_replace('/files','',$server);
 
 
 if(!$server) exit('Koi server nhin hai');
 
 $ch = curl_init($path.'/actions/regenerate_thumbs.php');
 curl_setopt($ch,CURLOPT_POST,true);
 curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
 curl_setopt($ch,CURLOPT_POSTFIELDS,
 array(
 "application_key"		=>	getAppKey(),
 'file_name'			=>	$_REQUEST['file_name'],
 'file_directory'		=>	$_REQUEST['file_directory'],
 'duration'				=>  $_REQUEST['duration'],
 'thumbs'				=> 5
 ));
 
 curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
 curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,8);
 curl_setopt($ch,CURLOPT_TIMEOUT,15);
 $result = curl_exec($ch);
 curl_close($ch);
 echo $result;
 
 
 if(is_numeric($result))
 {
     $file_name = $_REQUEST['file_name'];
     
     $db->update(tbl('video'),array('file_thumbs_count'),array($result)," file_name='$file_name' ");
 }
?>