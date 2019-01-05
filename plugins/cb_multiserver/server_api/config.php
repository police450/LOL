<?php

include(__DIR__.'/key.php');



if(file_exists(__DIR__.'/development.dev'))
	define("DEVELOPMENT_MODE",true);
else
	define("DEVELOPMENT_MODE",false);

/* ---- DO NOT EDIT BELOW THIS LINE ---- */


 ini_set("log_errors",true);
 ini_set("error_log","error_log.txt");
 ini_set("display_errors","on");
 
 if(phpversion() > 5.2)
	 error_reporting(E_ALL ^E_NOTICE  ^E_DEPRECATED);
 else
	 error_reporting(E_ALL ^E_NOTICE);

 include("functions.php");

 //Reading Config File.
 define("BASEDIR",dirname(__FILE__));
 $file = BASEDIR.'/configs.php';
 $setting_file = BASEDIR.'/settings.php';

 if(!file_exists($file) && !$_POST['baseurl'] && !$_POST['connect'])
 	exit("No Configuration File Was Found");
 
 $fileData = file_get_contents($file);
 $fileData = str_replace(array('<?php',"//","?>"), "", $fileData);
 $mainConfigs = $configs = json_decode($fileData,true);

 $fileData = file_get_contents($setting_file);
 $fileData = str_replace(array('<?php',"//","?>"), "", $fileData);
 $serverConfigs = json_decode($fileData,true);



 if(file_exists(__DIR__.'/local_configs.php'))
 {
 	include(__DIR__.'/local_configs.php');

 }

 define('DEBUG_MODE','yes');
 define("BASEURL",$configs['baseurl']);
 define("FILES_DIR",BASEDIR."/files");
 define("FILES_URL",BASEURL."/files");
 define("TEMP_DIR",FILES_DIR."/temp");
 define("SPRITES_DIR",FILES_DIR."/sprites");
 define("THUMBS_DIR",FILES_DIR."/thumbs");
 define("VIDEOS_DIR",FILES_DIR."/videos");
 define("VIDEOS_URL",FILES_URL.'/videos');
 define("SLIDE_DIR",FILES_DIR."/slides");
 define("PHOTOS_DIR",FILES_DIR.'/photos');
 define("PHOTOS_URL",FILES_URL.'/photos');
 define("CON_DIR",FILES_DIR."/conversion_queue");
 define("LOGS_DIR",FILES_DIR."/logs");
 define("LOGS_URL",FILES_URL."/logs");
 define("ORIGINAL_DIR",FILES_DIR."/original"); 
 define("MASS_UPLOAD_DIR",FILES_DIR."/mass_uploads");
 
 define("TEMP_THUMBS_DIR",BASEDIR.'/files/tr_temp_thumbs');
 define("TEMP_THUMBS_URL",BASEURL.'/files/tr_temp_thumbs');
 define("TR_THUMBS_DIR",BASEDIR."/files/tr_thumbs");
 define("TR_THUMBS_URL",BASEURL."/files/tr_thumbs");
 define("TR_FILE_DIR",BASEDIR."/files/tr_files");

if(!file_exists(TEMP_THUMBS_DIR))
	mkdir(TEMP_THUMBS_DIR);
if(!file_exists(TR_THUMBS_DIR))
	mkdir(TR_THUMBS_DIR);
if(!file_exists(TR_FILE_DIR))
	mkdir(TR_FILE_DIR);

   
 define("PHP_PATH",$configs['php_path']);
 define("KEEP_MP4_AS_IS","yes");
 define("MP4Box_BINARY",$configs['mp4box_path']);
 define("FLVTool2_BINARY",$configs['flvtool2_path']);
 define("MEDIAINFO_BINARY",$configs['mediainfo_path']);
 define("MPLAYER_BINARY",$configs['mplayer_path']);
 define("UNOCONV_BINARY",$configs['unoconv_path']);
 define("CONVERT_BINARY",$configs['convert_path']);
 define('FFPROBE',$configs['ffprobe_path']);
 
 //define("USE_MPLAYER",$configs['use_mplayer']);
 define("FFMPEG_BINARY", $configs['ffmpeg_path']);
 //Number of videos to process at once Set 0 to make it unlimit
 define('PROCESSESS_AT_ONCE',$configs['processes_at_once']);
 
 define("CALLBACK_URL",$configs['callback_url']);
 
 define("VDO_EXTS","wmv,avi,divx,3gp,mov,mpeg,mpg,xvid,flv,asf,rm,mp4,mkv,m4v");
 define("PHOTO_EXTS","jpg,png,gif,jpeg");
 
 //Photo Settings
 define("CROPPING",$configs['photo_crop']);
 define("PHOTO_RATIO",$configs['photo_ratio']);
 define("PHOTO_THUMB_HEIGHT",$configs['photo_thumb_height']);
 define("PHOTO_THUMB_WIDTH",$configs['photo_thumb_width']);
 define("PHOTO_MID_HEIGHT",$configs['photo_med_height']);
 define("PHOTO_MID_WIDTH",$configs['photo_med_width']);
 define("PHOTO_WIDTH",$configs['photo_lar_width']);
 
 //PHOTO WATERMARK
 define("ENABLE_WATERMARK",$configs['watermark_photo']);
 define("WATERMARK_WIDTH",$config['watermark_max_width']);
 define("WATERMARK_POSITION","right:bottom");
 define("WATERMARK_PADDING",$config['watermark_padding']);
 define("WATERMARK_FILE",BASEDIR.'/photo_watermark.png');
 define("THUMB_GEN_TIME",10);
  //VIDEO WATERMARK
 define("ENABLE_VIDEO_WATERMARK",$configs['watermark_video']);
 define("VIDEO_WATERMARK_POSITION",$configs['v_watermark_placement']);
 define("VIDEO_WATERMARK_FILE",BASEDIR.'/video_watermark.png');

  
 //define  version
 define("VERSION",'2');

 define("SECRET_KEY",$secret_key);
?>