<?php
header('Access-Control-Allow-Origin: *');  
/**
 * @Author : Imran Hassan
 */
$in_bg_cron = true;

ini_set('mysql.connect_timeout','6000');
set_time_limit(0);
include("../config.php");

require_once(BASEDIR.'/includes/classes/conversion/ffmpeg.class.php');
include(BASEDIR."/includes/classes/thumb_rotate_class.php");
include(BASEDIR."/includes/classes/resize-class.php");

$arry = $_POST;
$mode=$arry['mode'];

switch($mode)
{	
	case "next_temp":
	{
		$file= $arry['file_name'];
		$folder = date(Y);
		$folder .= '/'.date(m);
		$folder .='/'.date(d);
		#echo TEMP_DIR."/".$file.'.tmp');
		#replace Dir with URL
		if (!is_writable(TEMP_DIR)) {
			$arry1['error'] = 'temp directory is not writable!!!';
		}
		elseif (!is_writable(CON_DIR)) {
			$arry1['error'] = 'conversion_queue directory is not writable!!!';
		}
		elseif (!is_writable(VIDEOS_DIR)) {
			$arry1['error'] = 'Videos directory is not writable!!!';
		}
		elseif (!is_writable(LOGS_DIR)) {
			$arry1['error'] = 'logs directory is not writable!!!';
		}
		if (file_exists(TEMP_DIR."/".$file.'.tmp')) {
			$arry1['temp_file'] = 'yes';
			sleep(1);
		}
		if(file_exists(CON_DIR.'/'.$file.'.mp4')){

			$arry1['con_file'] = 'yes';
			sleep(15);
			
		}
		if(file_exists(LOGS_DIR.'/'.$file.'.log')){
			sleep(10);
			$arry1['logs_file'] = 'yes';

		}
		if(file_exists(VIDEOS_DIR.'/'.$folder.'/'.$file.'.mp4')){
			$arry1['vid_file'] = 'yes';
			if(filesize(VIDEOS_DIR.'/'.$folder.'/'.$file.'.mp4')>0)
				$arry1['conversion']='yes';
			else
				$arry1['conversion']='no';
			if(file_exists(LOGS_DIR.'/'.$file.'.log')){
				$arry1['conv_logs'] = file_get_contents(LOGS_DIR.'/'.$file.'.log');
			}
		}

		$arry = array_merge($arry,$arry1);
		echo json_encode($arry);
	}

	break;
}
?>