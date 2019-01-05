<?php

/**
 * file used to delete video files
 * Updated On 21 May 2014 By Imran
 */

include("../config.php");

if($application_key!=$_POST['application_key'] || $_POST['secret_key'] != $secret_key || !$secret_key || !$application_key)
{
	echo json_encode(array("err"=>"Unable to authenticate"));
}else
{
	if($_POST['file_name'] && !$_POST['photo_file'])
	{
		$folder = "";
		if($_POST['folder'])
			$folder = $_POST['folder'];
			
		$file_name = $_POST['file_name'];
		$log_file = LOGS_DIR.'/'.$file_name.'.log';
		
		// delete Condition start here 
		if($_POST['file_type']==2){
			
			$segment_files = glob(VIDEOS_DIR."/".$folder.$file_name.'/'.$file_name."/*");
			
			if(is_array($segment_files))
			foreach($segment_files as $segment_file)
			{
				if(file_exists($segment_file))
				unlink($segment_file);
			}

			$videos_files = glob(VIDEOS_DIR."/".$folder.$file_name.'/'.$file_name."*");
			
			if(is_array($videos_files))
			foreach($videos_files as $videos_file)
			{
				if(file_exists($videos_file))
				unlink($videos_file);
			}

		}
		elseif($_POST['file_type']==1){
			
			$segment_files = glob(VIDEOS_DIR."/".$folder.$file_name.'/'.$file_name."_segments/"."*");
			
			if(is_array($segment_files))
			foreach($segment_files as $segment_file)
			{
				if(file_exists($segment_file))
				unlink($segment_file);
			}

			$videos_files = glob(VIDEOS_DIR."/".$folder.$file_name.'/'.$file_name."*");
			
			if(is_array($videos_files))
			foreach($videos_files as $videos_file)
			{
				if(file_exists($videos_file))
				unlink($videos_file);
			}
		}
		else{
		#deleting videos files
			$videos_files = glob(VIDEOS_DIR."/".$folder.$file_name."*");
			
			if(is_array($videos_files))
			foreach($videos_files as $videos_file)
			{
				if(file_exists($videos_file))
				unlink($videos_file);
			}
		}
		// delete Condition end here 

		
		if(file_exists($log_file))
			unlink($log_file);
		
		$vid_thumbs = glob(THUMBS_DIR."/".$folder.$file_name."*");
		#replace Dir with URL
		if(is_array($vid_thumbs))
		foreach($vid_thumbs as $thumb)
		{
			if(file_exists($thumb))
			unlink($thumb);
		}
		
	}else
	{
		$folder = "";
		if($_POST['folder'])
			$folder = $_POST['folder'];

		if($_POST['filename'])
		{
			
			$files = glob(PHOTOS_DIR.'/'.$folder.$_POST['filename'].'*');
			if($files)
			{
				foreach($files as $file)
				{
					if(file_exists($file))
						unlink($file);
				}
			}
		}
		json_encode(array("msg"=>"ok"));
	}
}

?>