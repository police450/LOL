<?php

/**
 * @Author : Arslan Hassan
 */ 

 ini_set("log_errors",true);
 ini_set("error_log","error_log.txt");
 include("../config.php");

 $secret_key = $_GET['secret'];
 $server_ip = $_SERVER['REMOTE_ADDR'];
 $file = $_GET['file'];
 $ext = $_GET['ext'];
 
 if($server_ip == ALLOWED_SERVER && $secret_key == SECRET_KEY)
 {
	exec(PHP_PATH." -q ".BASEDIR."/actions/video_convert.php ".$file." ".$ext);
 }else
 	exit("Unable to authenticate");
?>