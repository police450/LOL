<?php

include(__DIR__."/../config.php");


if(isset($_POST['re-encode']))
{
	$file_directory = str_replace('//', "", $_POST['file_directory']); 
	
	if(substr($file_directory, -1) == '/') {
	    $file_directory = substr($file_directory, 0, -1);
	}

	$file_name = $_POST['file_name'];

	$targetFileName_path = ORIGINAL_DIR."/".$file_directory."/".$file_name."*";

	$targetFileName = glob($targetFileName_path);
	$targetFileName = $targetFileName[0];

	if(empty($targetFileName))
	{
		$targetFileName_path = ORIGINAL_DIR."/".$file_name."*";
		$targetFileName = glob($targetFileName_path);
		$targetFileName = $targetFileName[0];
	}

	if(empty($targetFileName))
	{
		$targetFileName_path = TEMP_DIR."/".$file_name."*";
		$targetFileName = glob($targetFileName_path);
		$targetFileName = $targetFileName[0];
	}
	if(empty($targetFileName))
	{
		$targetFileName_path = CON_DIR."/".$file_name."*";
		$targetFileName = glob($targetFileName_path);
		$targetFileName = $targetFileName[0];
	}

	if(empty($targetFileName))
	{
		$targetFileName_path = VIDEOS_DIR."/".$file_directory."/".$file_name."*";
		$targetFileName = glob($targetFileName_path);
		$targetFileName = end($targetFileName);
	}

	if(empty($targetFileName))
	{
		$targetFileName_path = VIDEOS_DIR."/".$file_name."*";
		$targetFileName = glob($targetFileName_path);
		$targetFileName = end($targetFileName);
	}

	if(!empty($targetFileName))
	{
		if (strpos(getName($targetFileName),'-')){
			$name = getName($targetFileName);
			$fileName = explode('-', $name)[0];
		}else{
			$fileName = getName($targetFileName);
		}
		$dest = TEMP_DIR."/".$fileName.".".getExt($targetFileName);
		copy($targetFileName, $dest);
		$exec = PHP_PATH." -q ".BASEDIR."/actions/video_convert.php ";
		$exec .= $fileName." ".getExt($targetFileName);
		$exec .= " ".$_SERVER['REMOTE_ADDR']." ".$file_directory;
		$exec .= " > /dev/null 2>/dev/null &";
		logData($exec,"command");
		//pr($exec,true);
		$conv_results = shell_output($exec);
		// pr($conv_results,true);
		echo json_encode(array("success"=>"yes","file_name"=>$file_name,"target_filename"=>$targetFileName));

	}
	else
	{
		echo json_encode(array("error"=>"yes","msg"=>"Re-encoding requires original video file, but we were unable to find it"));
	}
}




?>