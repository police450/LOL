<?php


   //require_once(__DIR__.'/includes/classes/conversion/ffmpeg.class.php');

	/*@parms 
		$file_name = file name of a video file /Audio file
		$info can be = CompleteName , FileExtension , Format , FileSize , FileName , Duration
	   @return
	    Complete Name , File Extension , Format , File Size , File Name , Duration
	*/
	function general_video_info($file_name,$info)
	{
		$media_info_path = MEDIAINFO_BINARY;
		if(empty($media_info_path))
		{
			$media_info_path = shell_exec('which mediainfo');       
		}
		
		$media_info_path = trim($media_info_path);
		$cmd = $media_info_path.' "--Inform=General;%'.$info.'%" '.$file_name;
		//echo $cmd.'<br>';
		$output =  shell_exec($cmd);
		if(empty($output))
		{
			$output = 'Not found';
		}
		return $output;
	}
	/*@parms 
		$file_name = file name of a video file /Audio file
		$info can be = CompleteName , FileExtension , Format , FileSize , FileName , Duration
	   @return
	    Complete Name , File Extension , Format , File Size , File Name , Duration
	*/
	function complete_video_info($file_name,$info)
	{
		$media_info_path = MEDIAINFO_BINARY;
		if(empty($media_info_path))
		{
			$media_info_path = shell_exec('which mediainfo');       
		}
		
		$media_info_path = trim($media_info_path);
		$cmd = $media_info_path.' "--Inform=Video;%'.$info.'%" '.$file_name;
		//echo $cmd.'<br>';
		$output =  shell_exec($cmd);
		if(empty($output))
		{
			$output = 'Not found';
		}
		return $output;
	}
	/*@parms 
		$file_name = file name of a video file /Audio file
		$info can be = Format , CodecID , Duration , BitRate , StreamSize 
		 , SamplingRate 
	   @return
	     Format , Codec , Duration , Bit Rate , Width , StreamSize 
		 , SamplingRate 
	*/
	function complete_audio_info($file_name,$info)
	{
		$media_info_path = MEDIAINFO_BINARY;
		if(empty($media_info_path))
		{
			$media_info_path = shell_exec('which mediainfo');       
		}
		
		$media_info_path = trim($media_info_path);
		$cmd = $media_info_path.' "--Inform=Audio;%'.$info.'%" '.$file_name;
		//echo $cmd.'<br>';
		$output =  shell_exec($cmd);
		if(empty($output))
		{
			$output = 'Not found';
		}
		return $output;
	}
	function mysql_clean($id,$replacer=true){
		return $id = htmlspecialchars(strip_tags($id), ENT_COMPAT, 'UTF-8');
	}
	


	function first_convert_video_own_res($res169,$ffmpeg)
	{

		$new_res = array();
		$configs= parse_configs();
		foreach ($res169 as $key => $value) 
		{
			$video_width=(int)$value[0];
			$video_height=(int)$value[1];
			logData('condition => '.$ffmpeg->input_details['video_height'].'=='.$video_height,'my_order');
			if($ffmpeg->input_details['video_height']==$video_height)
			{
				
				$new_res[0] = $value;
				break;
			}
		}

		foreach ($res169 as $key => $value) 
		{
			$video_width=(int)$value[0];
			$video_height=(int)$value[1];
			if($ffmpeg->input_details['video_height']==$video_height)
			{
				
			}
			else
			{
				$new_res[] = $value;
			}
		}
		return $new_res;
	}


	function getClosest($search, $arr) 
	{
		$closest = null;
		foreach ($arr as $item) 
		{
			if($closest === null || abs($search - $closest) > abs($item - $search)) {
				$closest = $item;
			}
		}
		return $closest;
	}


	/**
	* @Reason : this funtion is used to rearrange required resolution for conversion 
	* @params : { resolutions (Array) , ffmpeg ( Object ) }
	* @date : 23-12-2015
	* return : refined reslolution array
	*/
	function reindex_required_resolutions($resolutions,$ffmpeg)
	{
		$configs= parse_configs();
		$original_video_height = $ffmpeg->input_details['video_height'];

		// Setting threshold for input video to convert
		$valid_dimensions = array(240,360,480,720,1080);
		$input_video_height = getClosest($original_video_height, $valid_dimensions);

		
		//Setting contidion to place resolution to first near to input video 
		if ($configs['gen_'.$input_video_height]  == 'yes'){
			$final_res[$input_video_height] = $resolutions[$input_video_height];
		}
		foreach ($resolutions as $key => $value) 
		{
			$video_width=(int)$value[0];
			$video_height=(int)$value[1];	
			if($input_video_height != $video_height && $configs['gen_'.$video_height]  == 'yes'){
				$final_res[$video_height] = $value;	
			}
		}
		
		$revised_resolutions = $final_res;
		if ( $revised_resolutions ){
			return $revised_resolutions;
		}
		else{
			return false;
		}

	}



	function callback($download_size, $downloaded, $upload_size, $uploaded)
	{
		global $curl,$log_file,$file_name,$ext;
		
		$fo = fopen($log_file,'w+');
		//Elapsed time CURLINFO_TOTAL_TIME
		
		$info = curl_getinfo($curl->m_handle);
		
		$download_bytes = $download_size - $downloaded;
		$cur_speed = $info['speed_download'];
		if($cur_speed>0)
		$time_eta = $download_bytes/$cur_speed;
		else
		$time_eta = 0;
		
		$time_took = $info['total_time'];
		
		$curl_info = 
		array(
		'total_size' => $download_size,
		'downloaded' => $downloaded,
		'speed_download' => $info['speed_download'],
		'time_eta' => $time_eta,
		'time_took'=> $time_took,
		'file_name' => $file_name.'.'.$ext
		);
		fwrite($fo,json_encode($curl_info));
		fclose($fo);
	}




	function min_res_enabled($targetFile)
	{

		$video_height = complete_video_info($targetFile,"Height");
		$config_arr = parse_configs();
		//pr($config_arr,true);
		if(isset($config_arr['gen_240']))
		{
			if($config_arr['gen_240']=='yes')
			{
				$gen_res_op[] = 240;
			}
		}
		if(isset($config_arr['gen_360']))
		{
			if($config_arr['gen_360']=='yes')
			{
				$gen_res_op[] = 360;
			}
		}
		if(isset($config_arr['gen_480']))
		{
			if($config_arr['gen_480']=='yes')
			{
				$gen_res_op[] = 480;
			}
		}
		if(isset($config_arr['gen_720']))
		{
			if($config_arr['gen_720']=='yes')
			{
				$gen_res_op[] = 720;
			}
		}
		if(isset($config_arr['gen_1080']))
		{
			if($config_arr['gen_1080']=='yes')
			{
				$gen_res_op[] = 1080;
			}
		}

		$min_res_req = min($gen_res_op);
		//pr($min_res_req.','.$video_height,true);
		if($video_height == $min_res_req || $video_height > $min_res_req)
		{
			return true;
		}
		else
		{
			return false;
		}
	}




	function find_string($needle_start,$needle_end,$results)
	{
		if(empty($results)||empty($needle_start)||empty($needle_end))
		{
			return false;
		}
		$start = strpos($results, $needle_start);	
		$end = strpos($results, $needle_end);
		if(!empty($start)&&!empty($end))
		{
			$results = substr($results, $start,$end);
			//echo $results;
			$end = strpos($results, $needle_end);
			if(empty($end))
			{
				return false;
			}
			$results = substr($results, 0,$end);
			$return_arr = explode(':', $results);
			return $return_arr;
		}
		else
		{
			return false;
		}
	}

	/**
     * Function used to get shell output
     */
    function shell_output($cmd)
    {
        if (stristr(PHP_OS, 'WIN'))
        {
            $cmd = $cmd;
        } else
        {
            $cmd = "PATH=\$PATH:/bin:/usr/bin:/usr/local/bin bash -c \"$cmd\"  2>&1";
        }
        $data = shell_exec($cmd);
        return $data;
    }



 //Common functions
  function GetExt($file)
  {
  	$ext = explode('.', $file);
	$extension = end($ext);
	return strtolower($extension);
  }  
 /**
  * Function used to get file name
  */
  function GetName($file)
  {
	if(!is_string($file))
		return false;
	$path = explode('/',$file);
	if(is_array($path))
		$file = $path[count($path)-1];
	$new_name 	 = substr($file, 0, strrpos($file, '.'));
	return $new_name;
  }
  
  //Funtion of Random String
  function RandomString($length)
  {
    // Generate random 32 charecter string
    $string = md5(time());

    // Position Limiting
    $highest_startpoint = 32-$length;

    // Take a random starting point in the randomly
    // Generated String, not going any higher then $highest_startpoint
    $randomString = substr($string,rand(0,$highest_startpoint),$length);

    return $randomString;
  }
  
	/**
	 * Function used to return mysql time
	 * @author : Fwhite
	 */
	function NOW()
	{
		return date('Y-m-d H:i:s', time());
	}
	
	
	
	
	function get_thumbs($file,$count=false,$folder=NULL)
	{
	
		#get all possible thumbs of video
		$vid_thumbs = glob(THUMBS_DIR."/".$folder.$file."*");
		$thumbs = array();
		
		#replace Dir with URL
		if(is_array($vid_thumbs))
		foreach($vid_thumbs as $thumb)
		{
			$thumb_parts = explode('/',$thumb);
			$thumb_file = $thumb_parts[count($thumb_parts)-1];
			$thumbs[] = $thumb_file;
		}
		
		if($count)
			return count($thumbs);
		else
			return $thumbs;
	}

	function createThumb($from,$to,$ext,$d_width=NULL,$d_height=NULL,$force_copy=false)
	{
		$file = $from;
		$info = getimagesize($file);
		$org_width = $info[0];
		$org_height = $info[1];
		
		if($org_width > $d_width && !empty($d_width))
		{
			$ratio = $org_width / $d_width; // We will resize it according to Width
			
			$width = $org_width / $ratio;
			$height = $org_height / $ratio;
			
			$image_r = imagecreatetruecolor($width, $height);
			if(!empty($d_height) && $height > $d_height && CROPPING == 1)
			{
				$crop_image = TRUE;
			}
			
			switch($ext)
			{
				case "jpeg":
				case "jpg":
				case "JPG":
				case "JPEG":
				{
					$image = imagecreatefromjpeg($file);
					imagecopyresampled($image_r, $image, 0, 0, 0, 0, $width, $height, $org_width, $org_height);
					imagejpeg($image_r, $to, 90);
					
					if(!empty($crop_image))
						crop_image($to,$to,$ext,$width,$d_height);	
				}
				break;
				
				case "png":
				case "PNG":
				{
					$image = imagecreatefrompng($file);
					imagecopyresampled($image_r, $image, 0, 0, 0, 0, $width, $height, $org_width, $org_height);
					imagepng($image_r,$to,9);
					if(!empty($crop_image))
						crop_image($to,$to,$ext,$width,$d_height);	
				}
				break;
				
				case "gif":
				case "GIF":
				{
					$image = imagecreatefromgif($file);
					imagecopyresampled($image_r, $file, 0, 0, 0, 0, $width, $height, $org_width, $org_height);
					imagejpeg($image_r,$to,90);
					if(!empty($crop_image))
						crop_image($to,$to,$ext,$width,$d_height);
				}
				break;
			}
			imagedestroy($image_r);
		} else {
			if(!file_exists($to) || $force_copy === true)
				copy($from,$to);	
		}
	}
	
	/**
	 * Used to crop the image
	 * Image will be crop to dead-center
	 */
	function crop_image($input,$output,$ext,$width,$height)
	{
		$info = getimagesize($input);
		$Swidth = $info[0];
		$Sheight = $info[1];
		
		$canvas = imagecreatetruecolor($width, $height);
		$left_padding = $Swidth / 2 - $width / 2;
		$top_padding = $Sheight / 2 - $height / 2;
		
		switch($ext)
		{
			case "jpeg":
			case "jpg":
			case "JPG":
			case "JPEG":
			{
				$image = imagecreatefromjpeg($input);
				imagecopy($canvas, $image, 0, 0, $left_padding, $top_padding, $width, $height);
				imagejpeg($canvas,$output,90);
			}
			break;
			
			case "png":
			case "PNG":
			{
				$image = imagecreatefrompng($input);
				imagecopy($canvas, $image, 0, 0, $left_padding, $top_padding, $width, $height);
				imagepng($canvas,$output,9);
			}
			break;
			
			case "gif":
			case "GIF":
			{
				$image = imagecreatefromgif($input);
				imagecopy($canvas, $image, 0, 0, $left_padding, $top_padding, $width, $height);
				imagejpeg($canvas,$output,90);	
			}
			break;
			
			default:
			{
				return false;	
			}
			break;
		}
		imagedestroy($canvas);
	}
	
	
	
	/**
	 * Used to resize and watermark image
	 **/
	function generate_photos($array)
	{
		global $db;
		$path = PHOTOS_DIR."/";

		$p = $array;
			
		$filename = $p['filename'];
		$extension = $p['ext'];
		$folder = $p['folder'];
				
		createThumb($path.$folder.$filename.".".$extension,$path.$folder.$filename."_o.".$extension,$extension);	
		createThumb($path.$folder.$filename.".".$extension,$path.$folder.$filename."_t.".$extension,$extension,PHOTO_THUMB_WIDTH,PHOTO_THUMB_HEIGHT);
		createThumb($path.$folder.$filename.".".$extension,$path.$folder.$filename."_m.".$extension,$extension,PHOTO_MID_WIDTH,PHOTO_MID_HEIGHT);
		createThumb($path.$folder.$filename.".".$extension,$path.$folder.$filename."_l.".$extension,$extension,PHOTO_WIDTH);
		
		$should_watermark = ENABLE_WATERMARK;
		
		if(!empty($should_watermark) && $should_watermark == 1)
		{
			watermark_image($path.$folder.$filename."_l.".$extension,$path.$folder.$filename."_l.".$extension);
			watermark_image($path.$folder.$filename."_o.".$extension,$path.$folder.$filename."_o.".$extension);
		}	
	}
	
	
	/**
	 * Used to watermark image
	 */
	function watermark_image($input,$output)
	{
		$watermark_file = WATERMARK_FILE;
		if(!$watermark_file)
			return false;
		else
		{
			list($Swidth, $Sheight, $Stype) = getimagesize($input);
			$wImage = imagecreatefrompng($watermark_file);
			$ww = imagesx($wImage);
			$wh = imagesy($wImage);
			$paddings = position_watermark($input,$watermark_file);
			
			switch($Stype)
			{
				case 1: //GIF
				{
					$sImage = imagecreatefromgif($input);
					imagecopy($sImage,$wImage,$paddings[0],$paddings[1],0,0,$ww,$wh);
					imagejpeg($sImage,$output,90);
				}
				break;
				
				case 2: //JPEG
				{
					$sImage = imagecreatefromjpeg($input);
					imagecopy($sImage,$wImage,$paddings[0],$paddings[1],0,0,$ww,$wh);
					imagejpeg($sImage,$output,90);	
				}
				break;
				
				case 3: //PNG
				{
					$sImage = imagecreatefrompng($input);
					imagecopy($sImage,$wImage,$paddings[0],$paddings[1],0,0,$ww,$wh);
					imagepng($sImage,$output,9);
				}
				break;
			}
		}
	}
	
	/**
	 * Used to set watermark position
	 */
	function position_watermark($file,$watermark)
	{
		if(!WATERMARK_POSITION)
			$info = array('left','top');
		else
			$info = explode(":",WATERMARK_POSITION);
		
		$x = $info[0];
		$y = $info[1];
		list($w,$h) = getimagesize($file);
		list($ww,$wh) = getimagesize($watermark);
		$padding = WATERMARK_PADDING;
		
		switch($x)
		{
			case "center":
			{
				$finalxPadding = $w / 2 - $ww / 2;	
			}
			break;
			
			case "left":
			default:
			{
				$finalxPadding = $padding;
			}
			break;
			
			case "right":
			{
				$finalxPadding = $w - $ww - $padding;
			}
			break;
		}
		
		switch($y)
		{
			case "top":
			default:
			{
				$finalyPadding = $padding;	
			}
			break;
			
			case "center":
			{
				$finalyPadding = $h / 2 - $wh / 2;	
			}
			break;
			
			case "bottom":
			{
				$finalyPadding = $h - $wh - $padding;
			}
			break;
		}
		
		$values = array($finalxPadding,$finalyPadding);
		return $values;			
	}
	
	
	function pr($text,$wrap_pre=false)
	{
		if(!$wrap_pre)
		print_r($text);
		else
		{
			echo "<pre>";
			print_r($text);
			echo "</pre>";
		}
	}
	
	function config($in)
	{
		global $mainConfigs;
		return $mainConfigs[$in];
	}
	

	/**
	 * Function description
	 * This will parse configs from the configs.php in to an array.
	 * @author Awais Tariq 
	 *
	 * @todo {......}
	 *
	 * @return {array} {$configs_arr}
	 */

	function parse_configs()
	{
		$file_path = BASEDIR.'/configs.php';
		
		$read_reuslts = file_get_contents($file_path);
		$parsed_string = str_replace(array('<?php','?>',"//"), '', $read_reuslts);
		$configs_arr = json_decode($parsed_string,true);
		return $configs_arr;
	}
	

	/**
	 * Function description
	 * This function will send curl to the app server for the successfull video upload email request
	 * @author Awais Tariq 
	 * @param {int} {$videoid} {Video id of the uploaded video from db}
	 * @param {int} {$userid} {user id of the user who upload the video from db}
	 * 
	 * @todo {......}
	 *
	 * @return {boolean} {true/false}
	 */
	function send_upload_email($videoid,$userid)
	{
		$videoid = (int)$videoid;
		$userid = (int)$userid;
		if(empty($userid)||empty($videoid))
		{
			return false;
		}
		$post_array = array(
	     
	        'videoid' => $videoid,
		    'userid' => $userid,
		    'upload_email' => 'yes'
		);
		
		$app_url = CALLBACK_URL;
		$app_url_arr = explode('/', $app_url);
		$url_count = count($app_url_arr);
		if(empty($app_url_arr[($url_count-1)]))
		{
			unset($app_url_arr[($url_count-2)]);
			unset($app_url_arr[($url_count-3)]);
			unset($app_url_arr[($url_count-4)]);
			unset($app_url_arr[($url_count-5)]);	
		}
		else
		{
			unset($app_url_arr[($url_count-1)]);
			unset($app_url_arr[($url_count-2)]);
			unset($app_url_arr[($url_count-3)]);
			unset($app_url_arr[($url_count-4)]);
		}
		$app_url = implode('/', $app_url_arr);
		$app_url .= '/actions/file_uploader.php';
		$request = curl_init($app_url);
		//curl_setopt($request, CURLOPT_HEADER, true);
		curl_setopt($request, CURLOPT_POST, true);

		curl_setopt(
		    $request,
		    CURLOPT_POSTFIELDS,
		    $post_array
		);
		$returnCode = (int)curl_getinfo($request, CURLINFO_HTTP_CODE);
		// output the response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		$curl_results = curl_exec($request);
		logData('app_url url =>'.$app_url,'upload_email');
		logData('post fields =>'.json_encode($post_array),'upload_email');
		logData('curl response => '.$curl_results,'upload_email');
		curl_close($request);
		if($returnCode==0||$returnCode=='0')
		{
			return true;
		}
		else
		{
			return false;
		}
	}



	/**
	 * function used to create folder for video
	 * and files
	 */
	function createDataFolders()
	{
		$year = date("Y");
		$month = date("m");
		$day  = date("d");
		$folder = $year.'/'.$month.'/'.$day;
		@mkdir(VIDEOS_DIR.'/'.$folder,0777,true);
		@mkdir(THUMBS_DIR.'/'.$folder,0777,true);
		@mkdir(ORIGINAL_DIR.'/'.$folder,0777,true);
		@mkdir(PHOTOS_DIR.'/'.$folder,0777,true);
		return $folder;
	}
	
	/**
	 * Function used to get thumbnail number from its name
	 * Updated: If we provide full path for some reason and 
	 * web-address has '-' in it, this means our result is messed.
	 * But we know our number will always be in last index
	 * So wrap it with end() and problem solved.
	 */
	function get_thumb_num($name)
	{
		$list = end(explode('-',$name));
		$list = explode('.',$list);
		return  $list[0];
	}
	
	/**
	 * Function used to get server
	 * cofniguration as sent by the application 
	 * server
	 */
	function server_config($in)
	{
		global $serverConfigs;
		return $serverConfigs['main'][$in];
	}
	function assoc_config($in,$thumb=false)
	{
		global $serverConfigs;
		if(!$thumb)
		{		
			return $serverConfigs['assoc'][$in];
		}else
			return $serverConfigs['thumbs_assoc'][$in];
	}
	
	
	/**
	 * function used to create file stat
	 */
	function genStat($in)
	{
		if(!$in['time'])
			$time = strtotime(now());
		else
			$time = $in['time'];
		
		$details = $in;
		$details['time'] = $time;
		
		//Alright, lets do some action
		//first we have to put these deatils
		//in day folder
		$year = date("Y",$time);
		$month = date("m",$time);
		$day = date("d",$time);
		
		$year_dir = LOGS_DIR.'/'.$year;
		$month_dir = $year_dir.'/'.$month;
		$day_dir = $month_dir.'/'.$day;
		
		if(!file_exists(LOGS_DIR.'/'.$year.'/'.$month.'/'.$day))
		@mkdir(LOGS_DIR.'/'.$year.'/'.$month.'/'.$day,0777,true);
		
		$dayFile = $day_dir.'/stats.txt';
		if(file_exists($dayFile))
		$dayStats = file_get_contents($dayFile);
		
		$monthFile = $month_dir.'/stats.txt';
		if(file_exists($monthFile))
		 $monthStats = file_get_contents($monthFile);
		
		$yearFile = $year_dir.'/stats.txt';
		if(file_exists($yearFile))
		 $yearStats = file_get_contents($yearFile);
		
		
		$hourFile = $day_dir.'/'.date("H",$time).'_stats.txt';
		if(file_exists($hourFile))
		 $hourStats = file_get_contents($hourFile);
		 
				
		if($dayStats)
		{
		 $dayStats = json_decode($dayStats,true);
		}else
		{
		 $dayStats  = array('uploads','files'=>0,'size'=>0,'visits'=>0,'bw'=>0);
		}
		
		if($monthStats)
		{
		 $monthStats = json_decode($monthStats,true);
		}else
		{
		 $monthStats  = array('uploads','files'=>0,'size'=>0,'visits'=>0,'bw'=>0);
		}
		
		if($yearStats)
		{
		 $yearStats = json_decode($yearStats,true);
		}else
		{
		 $yearStats  = array('uploads','files'=>0,'size'=>0,'visits'=>0,'bw'=>0);
		}
		
		if($hourStats)
		{
		 $hourStats = json_decode($hourStats,true);
		}else
		{
		 $hourStats  = array('uploads','files'=>0,'size'=>0,'visits'=>0,'bw'=>0);
		}
		
		$dsize = $dayStats['size'] + $details['groupsize'];
		$dfiles_count = $dayStats['files'] + $details['files'];
		$duploads = $dayStats['uploads']+1;
		
		$msize = $monthStats['size'] + $details['groupsize'];
		$mfiles_count = $monthStats['files'] + $details['files'];
		$muploads = $monthStats['uploads']+1;
		
		$ysize = $yearStats['size'] + $details['groupsize'];
		$yfiles_count = $yearStats['files'] + $details['files'];
		$yuploads = $yearStats['uploads']+1;
		
		$hsize = $hourStats['size'] + $details['groupsize'];
		$hfiles_count = $hourStats['files'] + $details['files'];
		$huploads = $hourStats['uploads']+1;
		
		//Day Stats
		$file = fopen($dayFile,'w+');
		fwrite($file,json_encode(array('files'=>$dfiles_count,'size'=>$dsize,
		'visits'=>$dayStats['visits'],'bw'=>$dayStats['bw'],'uploads'=>$duploads)));
		fclose($file);
		
		//Hour Stats
		$file = fopen($hourFile,'w+');
		fwrite($file,json_encode(array('files'=>$hfiles_count,'size'=>$hsize,
		'visits'=>$hourStats['visits'],'bw'=>$hourStats['bw'],'uploads'=>$huploads)));
		fclose($file);
		
		//Filing Hour Log
		$hourFile = $day_dir.'/'.date("H",$time).'.log';
		$file = fopen($hourFile,'a+');
		fwrite($file,json_encode($details).',');
		fclose($file);
		 
		//Month Stats
		$file = fopen($monthFile,'w+');
		fwrite($file,json_encode(array('files'=>$mfiles_count,'size'=>$msize,
		'visits'=>$monthStats['visits'],'bw'=>$monthStats['bw'],'uploads'=>$muploads)));
		fclose($file);
		
		//Year Stats
		$file = fopen($yearFile,'w+');
		fwrite($file,json_encode(array('files'=>$yfiles_count,'size'=>$ysize,
		'visits'=>$yearStats['visits'],'bw'=>$yearStats['bw'],'uploads'=>$yuploads)));
		fclose($file);	 
	}
	
	function writeFile($file,$write,$type='w+')
	{
		$fo = fopen($file,$type);
		fwrite($fo,$write);
		fclose($fo);
	}
	
	
	/**
	 * Function check curl
	 */
	function isCurlInstalled()
	{
		if  (in_array  ('curl', get_loaded_extensions())) {
			return true;
		}
		else{
			return false;
		}
	}


/**
	 * Function used to get PHP Path
	 */
	 function php_path()
	 {
		 if(PHP_PATH !='')
			 return PHP_PATH;
		 else
		 	return "/usr/bin/php";
	 }

    /**
	* Function is used to write logs 
	*/
	function logData($data,$file=NULL,$path=false,$force=false)
	{
		if($force!=false&&!empty($path))
		{
			$file =$path;
			if(is_array($data)) $data = json_encode($data);
			if(file_exists($file))
				$text = file_get_contents($file);
			$text .= " \n {$data}";
			file_put_contents($file, $text);
		}
		else
		{
			if(!empty($file))
			{
				$logFilePath = BASEDIR. "/files/".$file.".txt";
			}
			else
			{
				$logFilePath = BASEDIR. "/files/ffmpegLog.txt";
			}
			if(is_array($data)) $data = json_encode($data);
			if(file_exists($logFilePath))
				$text = file_get_contents($logFilePath);
			$text .= " \n \n  {$data}";
			if(DEVELOPMENT_MODE)
				file_put_contents($logFilePath, $text);
		}
	}
	
	function accepted($completePath, $action) {
		$response = array();

		if (file_exists($completePath)) {
			
			$response['status'] = 200;
			$response['state'] = 'success';
			$response['action'] = $action;
			$response['fullPath'] = $completePath;
			
		} else {

			$response['status'] = 205;
			$response['state'] = 'failed';
			$response['action'] = $action;

		}

		echo json_encode($response);
	}

	function moveToStreamingServer($streamingServerKey, $streamingServerSecret, $streamnigServerUrl, $file_directory, $file_name, $fileNow, $fullFilePath, $action) {
		$uploadPath = $streamnigServerUrl.'/accept_file.php';
		#$fullFilePath = $file_arr['path'].$file_arr['file'];
		//Initialise the cURL var
		$ch = curl_init();

		//Get the response from cURL
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		//Set the Url
		curl_setopt($ch, CURLOPT_URL, $uploadPath);

		//Create a POST array with the file in it
		$postData = array(
			'application_key' => $streamingServerKey,
			'secret_key' => $streamingServerSecret,
		    'filename' => $file_name,
		    'folder' => $file_directory,
		    'fileNow' => $fileNow
		);
		
		$toUpload = array();
		if ($action != 'thumbs' && $action != 'log_file') {

			logData('`````````````````````````````video files activated`````````````````````````````', $file_name.'_log');

			$toUpload[$action] = new CurlFile($fullFilePath);
			logData($toUpload, $file_name.'_log');
			
			
		} elseif ($action == 'log_file') {

			logData('`````````````````````````````log activated`````````````````````````````', $file_name.'_log');
			
			$toUpload['logfile'] = new CurlFile($fullFilePath);
			$toUpload['log_file'] = 'yes';
			logData($toUpload, $file_name.'_log');

		} else {
			
			logData('`````````````````````````````thumb activated`````````````````````````````', $file_name.'_log');
			// logData('`````````````````````````````scan path`````````````````````````````', $file_name.'_log');

			$scanPath = THUMBS_DIR.'/'.$file_directory.$file_name;
			logData($scanPath, $file_name.'_log');
			$toUpload[$action][] = "thumbs";
			foreach (glob($scanPath."*") as $filename) {
				    
				    $toUpload[] =  new CurlFile($filename) ;

				    logData($toUpload, $file_name.'_log');
				}
		}


		$postData = array_merge($postData, $toUpload);
		
		curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

		// Execute the request
		$response = curl_exec($ch);
		$cinfo=curl_getinfo($ch);
		
		logData('`````````````````````````````CURL Info`````````````````````````````', $file_name.'_log');
		logData($cinfo, $file_name.'_log');
		
		$cerror=curl_error($ch);

		logData('`````````````````````````````CURL error`````````````````````````````', $file_name.'_log');
		logData($cerror, $file_name.'_log');

		curl_close($ch);

		return $response;
		#file_put_contents('/var/www/html/streaming_viper/files/videos/'.RandomString(5).".txt", print_r($streamnigServerUrl,true));

	}
?>