<?php
 /**
  * Written by : Arslan Hassan, Awais Tariq, Fahad Abass, Saqib Razzaq, Awais Fiaz
  * Software : ClipBucket v2
  * License : Attribution Assurance License -- http://www.opensource.org/licenses/attribution.php
  **/

 $in_bg_cron = true;

 ini_set('mysql.connect_timeout','6000');
 set_time_limit(0);

//Including FFMPEG CLASS

 include(__DIR__ . "/../config.php");
 require_once(BASEDIR.'/includes/classes/conversion/ffmpeg.class.php');
 include(BASEDIR."/includes/classes/thumb_rotate_class.php");

 if(isset($argv[1])&&isset($argv[2])&&isset($argv[3])&&isset($argv[4]))
 {
 	foreach ($argv as $key => $value) 
 	{
 		logData($key .'=>'. $value);
 	}
 	$tmp_file = $argv[1];
 	$file_name = $argv[1];	
 	$ext = $argv[2];
 	$ipaddress = $argv[3];
 	$file_directory = $argv[4].'/';

 	$temp_file = TEMP_DIR.'/'.$file_name.'.'.$ext;
 	$orig_file = CON_DIR.'/'.$file_name.'.'.$ext;
 	$orig_file1 = ORIGINAL_DIR.'/'.$file_directory.$file_name.'.'.$ext;

 	if(file_exists($temp_file))
 	{	

		//move file to queue folder
 		if ($mainConfigs['keep_orig'] == 'yes') {
 			file_put_contents('/var/www/html/red_viper_api/files/original/2016/11/28/conf.txt', print_r($mainConfigs,true));
			#copy($temp_file, $orig_file1);
 		}

 		rename($temp_file,$orig_file);


 		$inputFileSize = @filesize($orig_file);

 		/*generating 16:9 resolution*/
 		$res169 = array();
 		$res169['240'] = array('426','240');
 		$res169['360'] = array('640','360');
 		$res169['480'] = array('852','480');
 		$res169['720'] = array('1280','720');
 		$res169['1080'] = array('1920','1080');
 		/*end 16:9 resolution*/

 		/*generating 4:3 resolution*/
 		$res43 = array();
 		$res43['240'] = array('320','240');
 		$res43['360'] = array('480','360');
 		$res43['480'] = array('640','480');
 		$res43['720'] = array('960','720');
 		$res43['1080'] = array('1440','1080');
 		/*end 4:3 resolution*/		

 		/*Generating Video Configs*/
 		$vid_configs = array
 		(
 			'use_video_rate' => true,
 			'use_video_bit_rate' => true,
 			'use_audio_rate' => true,
 			'use_audio_bit_rate' => true,
 			'use_audio_codec' => true,
 			'use_video_codec' => true,
 			'format' => 'mp4',
 			'video_codec'=> config('video_codec'),
 			'audio_codec'=> config('audio_codec'),
 			'audio_rate'=> config("srate"),
 			'audio_bitrate'=> config("sbrate"),
 			'video_rate'=> config("vrate"),
 			'video_bitrate_240'=> config("vbrate_240"),
 			'video_bitrate_360'=> config("vbrate_360"),
 			'video_bitrate_480'=> config("vbrate_480"),
 			'video_bitrate_720'=> config("vbrate_720"),
 			'video_bitrate_1080'=> config("vbrate_1080"),
 			'normal_res' => config('normal_resolution'),
 			'high_res' => config('high_resolution'),
 			'max_video_duration' => config('max_video_duration'),
 			'normal_quality' => config('normal_quality'),
 			'res169' => $res169,
 			'res43' => $res43,
 			'resize'=>'max',
 			'gen_240'=>config('gen_240'),
 			'gen_360'=>config('gen_360'),
 			'gen_480'=>config('gen_480'),
 			'gen_720'=>config('gen_720'),
 			'gen_1080'=>config('gen_1080'),
 			'watermark_video'=>config('watermark_video'),
 			'stream_via' => config('stream_via')
 		);
 		/*End Video Configs*/

 		/*Creating Folders */
 		if(!file_exists(VIDEOS_DIR.'/'.$file_directory))
 		{
 			mkdir(VIDEOS_DIR.'/'.$file_directory,0777,true);
 		}
 		if(!file_exists(ORIGINAL_DIR.'/'.$file_directory))
 		{
 			mkdir(ORIGINAL_DIR.'/'.$file_directory,0777,true);
 		}
 		if(!file_exists(THUMBS_DIR.'/'.$file_directory))
 		{
 			mkdir(THUMBS_DIR.'/'.$file_directory,0777,true);
 		}
 		if(!file_exists(PHOTOS_DIR.'/'.$file_directory))
 		{
 			mkdir(PHOTOS_DIR.'/'.$file_directory,0777,true);
 		}
 		if(!file_exists(LOGS_DIR.'/'.$file_directory))
 		{
 			mkdir(LOGS_DIR.'/'.$file_directory,0777,true);
 		}
 		/*End creating folders*/

 		if(file_exists($orig_file))
 		{
 			logData('file_exists');


 			/*Generating ffmpeg class details for conversion*/
 			$ffmpeg_before = new ffmpeg($orig_file);
 			$ffmpeg_before->file_name = $file_name;
 			$ffmpeg_before->file_directory = $file_directory;
 			$ffmpeg_before->configs = $vid_configs;

 			$ffmpeg_before->gen_thumbs = TRUE;
 			$ffmpeg_before->gen_big_thumb = TRUE;
 			$ffmpeg_before->num_of_thumbs = config('num_thumbs');

 			$ffmpeg_before->thumb_dim = config('thumb_width')."x".config('thumb_height');
 			$ffmpeg_before->big_thumb_dim = config('big_thumb_width')."x".config('big_thumb_height');
 			$ffmpeg_before->tmp_dir = TEMP_DIR;
 			$ffmpeg_before->generate_3gp = true;

 			$ffmpeg_before->video_folder = $file_directory;

 			$ffmpeg_before->raw_path = VIDEOS_DIR.'/'.$file_directory.$file_name;
 			$ffmpeg_before->output_file = VIDEOS_DIR.'/'.$file_directory.$file_name.'.mp4';

 			$ffmpeg_before->original_output_path = ORIGINAL_DIR.'/'.$file_directory.$file_name.'.'.$ext;

 			$ffmpeg_before->log_file = LOGS_DIR.'/'.$file_directory.$file_name.'.log';

 			$ffmpeg_before->keep_original = $mainConfigs['keep_orig'];

 			if(strtolower($ffmpeg_before->input_ext)=='mkv')
 				$ffmpeg_before->use_2_pass_encoding = true;

 			echo "Before convert...";

 			$ffmpeg_before->ClipBucket();
 			echo "After convert...";
 			/*Conversion completed*/


 			/*Calling application server for db update*/
 			$call_bk = CALLBACK_URL;

			//Counting size
 			if(file_exists($ffmpeg_before->output_file))
 				$file_size = filesize($ffmpeg_before->output_file);
 			else
 				logData('line 213 video_convert file '.$ffmpeg_before->output_file.' does not exists','error_log');

			//unlink($orig_file);		
 			if(file_exists(CON_DIR.'/'.$file_name.'.'.$ext))
 				unlink(CON_DIR.'/'.$file_name.'.'.$ext);

 			if(file_exists(CON_DIR.'/'.$file_name.'-wm.'.$ext))
 				unlink(CON_DIR.'/'.$file_name.'-wm.'.$ext);


 			if(file_exists($ffmpeg_before->output_file))
 				unlink($ffmpeg_before->output_file);

 			$thumbs_size = 0;

			//Counting thumb size
 			$vid_thumbs = glob(THUMBS_DIR."/".$file_directory.$file_name."*");

 			if(empty($file_name))
 			{
 				$file_name = $ffmpeg_before->$file_name;
 			}

			#replace Dir with URL
 			if(is_array($vid_thumbs))
 				foreach($vid_thumbs as $thumb)
 				{
 					if(file_exists($thumb))
 						$thumbs_size += filesize($thumb);
 				}


			/**
			* New server roles were introduced in Multiserver 4.0 which begin from here
			*/

			// if current server is convert only, dig into below piece
			$server_action = $serverConfigs['main']['server_action'];
			
			if (!empty($server_action) && $server_action = 1)  {
				#application key of app domain
				$streamingServerKey = $serverConfigs['application_key'];


				#details for associated server
				$associated = $serverConfigs['assoc'];
				$streamingServerSecret = $associated['secret_key'];
				$streamnigServerUrl = $associated['server_api_path'];
				
				// getting extension to pass it to zip folder accordingly
				if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'.m3u8')){
					// 2 is type for HLS
					$file_type    = 2;

				}else if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'.mpd')){
					// 1 is type for DASH
					$file_type    = 1;

				}else{
					// 0 is type for FLV_MP4
					$file_type   =  0;

				}
				

				// function to zip files
				function Zip($source, $destination)
				{
					if (!extension_loaded('zip') || !file_exists($source)) {
						return false;
					}

					$zip = new ZipArchive();
					if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
						return false;
					}

					$source = str_replace('\\', '/', realpath($source));

					if (is_dir($source) === true)
					{
						$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

						foreach ($files as $file)
						{
							$file = str_replace('\\', '/', $file);

            	// Ignore "." and ".." folders
							if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
								continue;

							$file = realpath($file);

							if (is_dir($file) === true)
							{
								$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
							}
							else if (is_file($file) === true)
							{
								$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
							}
						}
					}
					else if (is_file($source) === true)
					{
						$zip->addFromString(basename($source), file_get_contents($source));
					}

					return $zip->close();
				}
					// function to zip files end


				// getting extension to pass it to zip folder accordingly

				if($file_type == 1 || $file_type == 2){
				// getting files to move 
					if($file_type==1){

						Zip(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'_segments/', VIDEOS_DIR.'/'.$file_directory.'/'.$file_name.'/'.$file_name.'_compressed.zip');

					}elseif($file_type==2){

						Zip(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'/', VIDEOS_DIR.'/'.$file_directory.'/'.$file_name.'/'.$file_name.'_compressed.zip');

					}




					$filesToMove =array();

					if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'.mpd')){

						$filesToMove[]='mpd';

					}
					if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'.m3u8')){

						$filesToMove[]='m3u8';

					}
					if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-240.mp4')){

						$filesToMove[]='240';

					}
					if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-360.mp4')){

						$filesToMove[]='360';

					}
					if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-480.mp4')){

						$filesToMove[]='480';

					}
					if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-720.mp4')){

						$filesToMove[]='720';

					}
					if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-1080.mp4')){

						$filesToMove[]='1080';

					}
					if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-aud.mp4')){

						$filesToMove[]='aud';

					}
					if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'_compressed.zip')){

						$filesToMove[]='zip';

					}


				}else{

					$filesToMove = $ffmpeg_before->video_files;
				}





				// stores files moved successfully
				$movedFilesStatus = array();

				// loop through all converted files
				foreach ($filesToMove as $key => $fileNow) {
					
					if($fileNow=='240'){
						
						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-240.mp4';
						
					}elseif($fileNow=='360'){
						
						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-360.mp4';
						
					}elseif($fileNow=='480'){
						
						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-480.mp4';
						
					}elseif($fileNow=='720'){
						
						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-720.mp4';
						
					}elseif($fileNow=='1080'){

						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-1080.mp4';
						
					}elseif($fileNow=='aud'){
						
						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'-aud.mp4';
						
					}elseif($fileNow=='zip'){
						
						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'_compressed.zip';
						
					}elseif($fileNow=='mpd'){
						
						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'.mpd';
						

					}elseif($fileNow=='m3u8'){
						
						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'.m3u8';
						
					}else{
						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'-'.$fileNow.'.mp4';
					}


					if (file_exists($fullFilePath)) {

						// moving file to streaming server
						$response = moveToStreamingServer($streamingServerKey, $streamingServerSecret, $streamnigServerUrl, $file_directory, $file_name, $fileNow, $fullFilePath, 'file_'.$fileNow);

						
						$cleaned = json_decode($response,true);
			
						// ensure that file was moved successfuly
						if ($cleaned['status'] == 200 && $cleaned['state'] == 'success' && !empty($cleaned['fullPath'])) {
							$finalStatus = 'success';
						} else {
							$finalStatus = 'failure';
						}

						// set file status
						$movedFilesStatus[$fileNow]['status'] = $finalStatus;
						$movedFilesStatus[$fileNow]['response'] = $cleaned;
					}
				}

				$finalBaseUrl = $streamnigServerUrl;
				
				// check status for all moved files and if they failed, stop moving
				foreach ($movedFilesStatus as $key => $movedFile) {
					
					logData($movedFile['status'],'thumb');
					logData($movedFile,'thumb');

					if ($movedFile['status'] != 'success') {
						$finalBaseUrl = BASEURL;
						break;
					}
				}

				if ($finalBaseUrl != BASEURL) {
					logData('URL changed', 'thumb');
					logData($finalBaseUrl, 'thumb');
					$fullLogPath = LOGS_DIR.'/'.$file_directory.$file_name.'.log';

					// move thumbs to streaming server
					moveToStreamingServer($streamingServerKey, $streamingServerSecret, $streamnigServerUrl, $file_directory, $file_name, $fileNow, $fullFilePath, 'thumbs');

					// move logs to streaming server
					moveToStreamingServer($streamingServerKey, $streamingServerSecret, $streamnigServerUrl, $file_directory, $file_name, $fileNow, $fullLogPath, 'log_file');


				}
			}

			if (!isset($finalBaseUrl)) {
				$finalBaseUrl = BASEURL;
			}

			$videoid = $argv[5];
			$userid = $argv[6];
			

			// getting extension to pass it to app
			if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'.m3u8')){
				// HLS
				$file_type    = 2;

			}else if(file_exists(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'.mpd')){
				// DASH
				$file_type    = 1;

			}else{
				// FLV_MP4
				$file_type   =  0;

			}
			// getting extension to pass it to app end

			
			$array = array(
				'callback' => true,
				'secret_key' => SECRET_KEY,
				'file_server_path' => $finalBaseUrl.'/files',
				'files_thumbs_path' => $finalBaseUrl.'/files/thumbs',
				'file_thumbs_count' => get_thumbs($file_name,true,$file_directory),
				'has_hd'	=> $ffmpeg_before->has_hd,
				'has_mobile' => $ffmpeg_before->has_mobile,
				'file_type' => $file_type,
				'filegrp_size' => $file_size+$hq_size+$thumbs_size+$hd_size+$m_size,
				'process_status' => 2,
				'conversion_log' => file_get_contents($ffmpeg_before->log_file),
				'file_name'=>$file_name,
				'conv_status' => $ffmpeg_before->conv_status,
				'folder'	=> $file_directory,
				'sprite_count'	=> $ffmpeg_before->sprite_count,
				'has_sprite'	=> $ffmpeg_before->has_sprite,
				'version' => VERSION,
				'has_resolution' =>'yes',
				'video_files' =>json_encode($ffmpeg_before->video_files),

			);


		    //storing stream server thumbs count if stream server exists ..
			$steam_server_thumbs_count = $array['file_thumbs_count'];

			$ch = curl_init($call_bk);
			
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
			$charray[CURLOPT_POSTFIELDS] = $array;
			// logData($charray,'new2');
			curl_setopt_array($ch,$charray);

			$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			$result = curl_exec($ch);	

			//logData($call_bk.'       '.$returnCode.'   '.$result);
			
			if(curl_errno($ch)) {
				$error_num =  'Curl error: ' . curl_error($ch);
			}
			logData('curl =>'.curl_error($ch));
			if(DEBUG_MODE=='yes')
				writeFile('results.txt',$result.' '.$returnCode.' Error = '.$err.'<br/> Host '.$call_bk.'   '.$error_num);
			curl_close($ch);
			/*Call back end */

			

			/*Delete All converted file due to no Success reponse from application server*/
			if(!strstr($result,'success'))
			{
				logData("No success","unlink");

			}
			else
			{
				logData("success","unlink");
				if ($finalBaseUrl != BASEURL) {
					
					if($file_type==2){
					
					$fullFilePath = glob(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'/'."*");
					if (is_array($fullFilePath) && !empty($fullFilePath)){
						foreach ($fullFilePath as $key => $file) {
							logData($file,"unlink");
							if (file_exists($file)){
								unlink($file);
							}
						}
					}
					
					$fullFilePath = glob(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'."*");
					if (is_array($fullFilePath) && !empty($fullFilePath)){
						foreach ($fullFilePath as $key => $file) {
							logData($file,"unlink");
							if (file_exists($file)){
								unlink($file);
							}
						}
					}

					}elseif($file_type==1){

					$fullFilePath = glob(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'.$file_name.'_segments/'."*");
					if (is_array($fullFilePath) && !empty($fullFilePath)){
						foreach ($fullFilePath as $key => $file) {
							logData($file,"unlink");
							if (file_exists($file)){
								unlink($file);
							}
						}
					}


					$fullFilePath = glob(VIDEOS_DIR.'/'.$file_directory.$file_name.'/'."*");
					if (is_array($fullFilePath) && !empty($fullFilePath)){
						foreach ($fullFilePath as $key => $file) {
							logData($file,"unlink");
							if (file_exists($file)){
								unlink($file);
							}
						}
					}

					}else{
						foreach ($filesToMove as $key => $fileNow) {
						$fullFilePath = VIDEOS_DIR.'/'.$file_directory.$file_name.'-'.$fileNow.'.mp4';
						logData($fullFilePath,"unlink");
						if (file_exists($fullFilePath)) {
							unlink($fullFilePath);
						}
						}	
					}

					
					$fullLogPath = LOGS_DIR.'/'.$file_directory.$file_name.'.log';
					if (file_exists($fullLogPath)){
						logData($fullLogPath,"unlink");
						unlink($fullLogPath);
					}

					$fullFilePath = glob(THUMBS_DIR.'/'.$file_directory.$file_name."*");
					if (is_array($fullFilePath) && !empty($fullFilePath)){
						foreach ($fullFilePath as $key => $file) {
							logData($file,"unlink");
							if (file_exists($file)){
								unlink($file);
							}
						}
					}
				}
				

				echo 'Whole process is a success!';
			}
		}
		else
		{
			echo 'Concatination process is not Executed Successfully';
		}

		
	}
	else
	{
		echo 'Uploaded File is not created...';	
	}
}
else
{
	echo 'For Conversion parameters are not passed successfully';
}