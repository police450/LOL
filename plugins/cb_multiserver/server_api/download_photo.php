<?php

include('config.php');


$file = str_replace('..','',mysql_clean($_GET['file']));
$folder= str_replace('..','',mysql_clean($_GET['folder']));
$title = mysql_clean($_GET['title']);

$photo_file = PHOTOS_DIR.'/'.$folder.$file;
$ext = getExt($photo_file);

	$mime_types=array();
	$mime_types['gif']   = 'image/gif';
	$mime_types['jpe']   = 'image/jpeg';
	$mime_types['jpeg']  = 'image/jpeg';
	$mime_types['jpg']   = 'image/jpeg';
	$mime_types['png']	 = 'image/png';
	
	if(array_key_exists($ext,$mime_types) && $file && file_exists($photo_file))
	{
			$fp=fopen($photo_file,'r');
			$mime = $mime_types[$ext];
			$size = filesize($photo_file);
			header("Content-type: $mime");
			header("Content-Length: $size");
			if($title)
			header("Content-Disposition: attachment; filename=\"".$title.".".$ext."\"");
			
			fpassthru($fp);
			// close the file
			fclose($fp);
			exit;
							
	}else
		echo 'File does not exist';
?>