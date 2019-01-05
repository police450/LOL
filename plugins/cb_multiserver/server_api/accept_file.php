<?php
/**
 * file used to delete video files
 */


include("config.php");

	logData('`````````````````````````````$_REQUEST array`````````````````````````````','thumb');
	logData($_REQUEST,'thumb');

if($_POST['post_fields']) {
	$_POST = json_decode($_POST['post_fields'],true);

	logData('`````````````````````````````$_POST array`````````````````````````````','thumb');
	logData($_POST,'thumb');
}

if($application_key!=$_POST['application_key'] || $_POST['secret_key'] != $secret_key || !$secret_key || !$application_key) {
		#echo json_encode(array("err"=>"Unable to authenticate"));
		#echo json_encode(array($_POST));
	echo "NA";
	logData("NA",'thumb');
} else {
	$fx = fopen('flx.txt','a+');
	fwrite($fx,"Auth : OK\r\n");

	logData('`````````````````````````````Folder index`````````````````````````````','thumb');
	logData($folder,'thumb');


	
	$folder = $_POST['folder'];
	$filename = $_POST['filename'];
	
	if($folder)
	{
		
		@mkdir(VIDEOS_DIR.'/'.$folder,0777,true);
		@mkdir(THUMBS_DIR.'/'.$folder,0777,true);
		@mkdir(ORIGINAL_DIR.'/'.$folder,0777,true);
		@mkdir(PHOTOS_DIR.'/'.$folder,0777,true);
		@mkdir(LOGS_DIR.'/'.$folder,0777,true);
	}

	@mkdir(VIDEOS_DIR.'/'.$folder.$filename,0777,true);
	if($_POST['fileNow'] == 'm3u8'){
		@mkdir(VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename,0777,true);
	}
	if($_POST['fileNow']=='mpd'){
		@mkdir(VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'_segments',0777,true);
	}
	
	if($_FILES['file_240'])
	{
		$video_240 = $_FILES['file_240']['tmp_name'];
		if($filename)
		{	
			$fullPath240 = VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'-240.mp4';
			move_uploaded_file($video_240, $fullPath240);
			return accepted($fullPath240, 'move_240');
		}
	}
	

	if($_FILES['file_360'])
	{
		$video_360 = $_FILES['file_360']['tmp_name'];
		if($filename)
		{
			$fullPath360 = VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'-360.mp4';
			move_uploaded_file($video_360, $fullPath360);
			accepted($fullPath360, 'move_360');
		}
	}
	

	if($_FILES['file_480'])
	{
		$video_480 = $_FILES['file_480']['tmp_name'];
		if($filename)
		{
			$fullPath480 = VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'-480.mp4';
			move_uploaded_file($video_480, $fullPath480);
			accepted($fullPath480, 'move_480');
		}
	}

	if($_FILES['file_720'])
	{
		$video_720 = $_FILES['file_720']['tmp_name'];
		if($filename)
		{
			$fullPath720 = VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'-720.mp4';
			move_uploaded_file($video_720, $fullPath720);
			accepted($fullPath720, 'move_720');
		}
	}
	
	if($_FILES['file_1080'])
	{
		$video_1080 = $_FILES['file_1080']['tmp_name'];
		if($filename)
		{
			$fullPath1080 = VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'-1080.mp4';
			move_uploaded_file($video_1080, $fullPath1080);
			accepted($fullPath1080, 'move_1080');
		}
	}

	if($_FILES['file_aud'])
	{
		$video_aud = $_FILES['file_aud']['tmp_name'];
		if($filename)
		{
			$fullPathAud = VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'-aud.mp4';
			move_uploaded_file($video_aud, $fullPathAud);
			accepted($fullPathAud, 'move_aud');
		}
	}

	if($_FILES['file_mpd'])
	{
		$video_mpd = $_FILES['file_mpd']['tmp_name'];
		if($filename)
		{
			$fullPathMpd = VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'.mpd';
			move_uploaded_file($video_mpd, $fullPathMpd);
			accepted($fullPathMpd, 'move_mpd');
		}
	}

	if($_FILES['file_m3u8'])
	{
		$video_m3u8 = $_FILES['file_m3u8']['tmp_name'];
		if($filename)
		{
			$fullPathM3u8 = VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'.m3u8';
			move_uploaded_file($video_m3u8, $fullPathM3u8);
			accepted($fullPathM3u8, 'move_m3u8');
		}
	}

	if($_FILES['file_zip'])
	{
		$video_zip = $_FILES['file_zip']['tmp_name'];
		if($filename)
		{
			$fullPathZip = VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'_compressed.zip';
			move_uploaded_file($video_zip, $fullPathZip);
			sleep(3);
			accepted($fullPathZip, 'move_zip');
			if(file_exists($fullPathZip)){

				logData('exist','thumb');

			}
		}

		$zip = new ZipArchive;
		if ($zip->open(VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'_compressed.zip') === TRUE) {
			
			if(is_dir(VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename)){
			$zip->extractTo(VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename);
			}

			if(is_dir(VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'_segments')){
			$zip->extractTo(VIDEOS_DIR.'/'.$folder.$filename.'/'.$filename.'_segments');
			}
			$zip->close();
			logData('extracted','debug');
		} else {
			logData('error in file extracting','debug');
		}

	}

	if($_FILES['file_hd'])
	{
		$video_hd = $_POST['file_hd']['tmp_name'];
		if($filename)
		{
			$fullPathHD = VIDEOS_DIR.'/'.$folder.$filename.'-hd.mp4';
			move_uploaded_file($video_hd, $fullPathHD);
			accepted($fullPathHD, 'move_hd');
		}
	}
	
	
	if($_FILES['file_hq'])
	{
		//fwrite($fx,"file_hq : OK\r\n");
		$file_hq = $_POST['file_hq']['tmp_name'];
		if($filename)
		{
			$fullPathHQ = VIDEOS_DIR.'/'.$folder.$filename.'-hq.mp4';
			move_uploaded_file($file_hq, $fullPathHQ);
			accepted($fullPathHQ, 'move_HQ');
		}
	}
	
	
	if($_FILES['file_iphone'])
	{
		//fwrite($fx,"file_iphone : OK\r\n");
		$file_iphone = $_POST['file_iphone']['tmp_name'];
		if($filename)
		{
			$fullPathiPhone = VIDEOS_DIR.'/'.$folder.$filename.'-m.mp4';
			move_uploaded_file($file_iphone, $fullPathiPhone);
			accepted($fullPathiPhone, 'move_iphone_file');
		}
	}
	
	if($_POST['thumbs'])
	{
		if (isset($_FILES))	{

			foreach ($_FILES as $currentFile) {
				$newName = $currentFile['name'];
				$newDest = THUMBS_DIR.'/'.$folder.$newName;
				move_uploaded_file($currentFile['tmp_name'], $newDest);
			}
		}
	}

	if ($_POST['log_file']) 
	{	
		#file_put_contents('/var/www/html/streaming_viper/files/logs/2016/11/14/e.txt', print_r($_FILES,true));
		if (isset($_FILES)) {

			$fullPathLog = LOGS_DIR.'/'.$folder.$filename.'.log';
			move_uploaded_file($_FILES['logfile']['tmp_name'], $fullPathLog);
			accepted($fullPathLog, 'move_log');
		}
	}
	
	
	fclose($fx);
}
?>