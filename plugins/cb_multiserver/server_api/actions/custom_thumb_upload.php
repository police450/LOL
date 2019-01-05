<?php
#header('Access-Control-Allow-Origin: *');  
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
	
	$mode=$_POST['mode'];

	if($mode=='add'){

		$vid_thumbs = glob(THUMBS_DIR."/".$_POST['file_directory'].$_POST['file_name']."-*.jpg");
		#replace Dir with URL
		$count=0;
		if(is_array($vid_thumbs))
		{
			foreach($vid_thumbs as $thumb)
			{
				$arr_count = explode('/', $thumb);
				$end_arr = end($arr_count);
				$arr_count = explode('-', $end_arr);
				if(file_exists($thumb)&&count($arr_count)==2)
				{
					$count++;
				}
			}
		}
		
		$count = $count+1;
		$file_path = THUMBS_DIR."/".$_POST['file_directory'];
		#$file_path = BASEDIR.'/files/thumbs/'.$_POST['file_directory'].'13987993514b725-5.jpg';
		writeFile('thumb_log.txt',"\n".$_FILES['file_thumb']['name']."\n path \n ".$_POST['files_thumbs_path']."\n".$_POST['file_directory']."\n");

		if(move_uploaded_file($_FILES['file_thumb']['tmp_name'],$file_path.$_POST['file_name'].'-'.$count.'.jpg'))
		{
			$count;
		}
		else{
			$count--;
			echo "Error:";
		}

		echo $count;
	}
	if($mode=='delete'){

		
		writeFile('thumb_log.txt',"file has been deleted");

		#replace Dir with URL
		
		$name = $_POST['file_name'];
		$number = $_POST['thum_num'];
		$total_count = $_POST['total_count'];
		#echo THUMBS_DIR."/".$_POST['file_directory'].$name.'*'.$number.'.jpg';
		
		$vid_thumbs = glob(THUMBS_DIR."/".$_POST['file_directory'].$name.'*'.$number.'.jpg');
		if($number)
		{
			$delete_results = delete_file($vid_thumbs);
			$total_count--;
		}
		
		$vid_thumbs = glob(THUMBS_DIR."/".$_POST['file_directory'].$_POST['file_name']."-*");
		#replace Dir with URL
		$count = 0;
		if(is_array($vid_thumbs))
		{
			foreach($vid_thumbs as $thumb)
			{
				$arr_count = explode('/', $thumb);
				$end_arr = end($arr_count);
				$arr_count = explode('-', $end_arr);
				if(file_exists($thumb)&&count($arr_count)==2)
				{
					$count++;
				}
			}
		}
		$count = count($vid_thumbs);
		if($total_count!=$count)
		{
			$arr = array("error"=>"yes",'message'=>'Db count and thumbs real count are not matching!');
			echo json_encode($arr);
		}
		else
		{
			$arr = array('rem' => $count ,"success"=>"yes",'results'=>$delete_results);
			echo json_encode($arr);
			
		}
	}
	if($mode=='gen_thumbs')
	{

		$dated_folder = $_POST['file_directory'];
		$file_name = $_POST['file_name'];
		$orig_file = VIDEOS_DIR."/".$dated_folder."/".$file_name."-*.mp4";
		$video_files = glob($orig_file);
		
		krsort($video_files);
		if(isset($_POST['numbers']))
		{
			$num = $_POST['numbers'];
		}
		else
		{
			$num = 1;
		}
		if(empty($video_files))
		{
			exit(json_encode(array("error"=>"Video Files Does not exists!!!")));
		}

		if(is_array($video_files))
		foreach ($video_files as $key => $video_file) 
		{
			if(file_exists($video_file))
			{
				$orig_file = $video_file;
				break;

			}
		}
		$original_duration = general_video_info($orig_file,"Duration");
		$duration = $original_duration;

		if(!$original_duration||$original_duration==0)
		{
			exit(json_encode(array("error"=>"Video files are corrupted meta not found!!!")));	
		}
		
		$delete_old_thumb_files = THUMBS_DIR."/".$dated_folder."".$file_name."-*";
		$delete_files = glob($delete_old_thumb_files);
		
		$delete_results = delete_file($delete_files);
		
		$duration = $_POST['duration'];
		// copy($orig_file, $new_orig_file);
		$thumb_path = THUMBS_DIR."/".$dated_folder."/".$file_name."-*.jpg";
		$ffmpeg_before = new ffmpeg($orig_file);
		$ffmpeg_before->video_folder = $dated_folder;
		$ffmpeg_before->file_name = $file_name;
		$ffmpeg_before->prepare($orig_file);
		$log_file = LOGS_DIR."/".$dated_folder."/".$file_name.".log";
		if(file_exists($log_file))
		{
			$data = file_get_contents($log_file);
			preg_match_all('/(.*) : (.*)/',trim($data),$matches);
	            
	        $matches_1 = ($matches[1]);
	        $matches_2 = ($matches[2]);
	        
	        for($i=0;$i<count($matches_1);$i++)
	        {
	            $statistics[trim($matches_1[$i])] = trim($matches_2[$i]);
	        }
	        if(count($matches_1)==0)
	        {
	            return false;
	        }
	        // pr($statistics,true);
	        $ffmpeg_before->input_details['video_height'] = $height = $statistics['video_height'];
	        $ffmpeg_before->input_details['video_width'] = $width = $statistics['video_width'];

		}
		$ffmpeg_before->file_name = $file_name;
		// pr($ffmpeg_before->input_details,true);
		$ffmpeg_before->generate_thumbs($orig_file,$duration,$ffmpeg_before->thumb_dim,$num,'');
		
		$new_total_thumbs = glob($thumb_path);
		if(is_array($new_total_thumbs))
		{
			foreach($new_total_thumbs as $thumb)
			{
				$arr_count = explode('/', $thumb);
				$end_arr = end($arr_count);
				$arr_count = explode('-', $end_arr);
				if(file_exists($thumb)&&count($arr_count)==2)
				{
					$count++;
				}
			}
		}
		$return_arr = array("total"=>$count);
		logData($return_arr,"seaweed_test");
		// unlink($new_orig_file);
		echo json_encode($return_arr);

		
	}
	function delete_file($vid_thumbs='')
	{
			if(is_array($vid_thumbs))
			foreach($vid_thumbs as $thumb)
			{
				if(file_exists($thumb)){
					unlink($thumb);
				}
			}
			
			
		
	}	
?>