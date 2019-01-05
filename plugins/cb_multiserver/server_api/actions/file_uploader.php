<?php
header('Access-Control-Allow-Origin: *');
/**
 * @Author : Arslan Hassan
 */
include('../config.php');

$mode=$_POST['mode'];

if($mode != 'test'){
	
	$mode = "upload";	
}
$array = $_POST;
#$array = array_merge( $array, $_FILES );

if(TEST_MODE_VIDEO_UPLOADING == 'yes')
			file_put_contents('Test__filename.txt', json_encode( $array ));

switch($mode)
{	
	case "upload":
	{
		logData($_POST,"post");

		if(isset($_FILES))
		{
			if (isset($_POST['mob_api_upload'])) {
				$mob_api_upload = true;
			}
			$file_name = time().RandomString(6);
			$file_directory = date('Y/m/d');

			$tempFile = $_FILES['Filedata']['tmp_name'];
			if ($mob_api_upload) {
				$targetFileName = $file_name.'.'.getExt( $_POST['name']);
			} else {
				$targetFileName = $file_name.'.'.getExt( $_FILES['Filedata']['name']);
			}
			$targetFile = TEMP_DIR."/".$targetFileName;
			
			$max_file_size_in_bytes = 2147483647;				// 2GB in bytes	
			$types = strtolower(VDO_EXTS);
			
			//Checking filesize
			$POST_MAX_SIZE = ini_get('post_max_size');
			$unit = strtoupper(substr($POST_MAX_SIZE, -1));
			$multiplier = ($unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1)));
		
			if ((int)$_SERVER['CONTENT_LENGTH'] > $multiplier*(int)$POST_MAX_SIZE && $POST_MAX_SIZE) {
				header("HTTP/1.1 500 Internal Server Error"); // This will trigger an uploadError event in SWFUpload
				upload_error("POST exceeded maximum allowed size.");
				exit(0);
			}
			
			//Checking uploading errors
			$uploadErrors = array(
	        0=>"There is no error, the file uploaded with success",
	        1=>"The uploaded file exceeds the upload_max_filesize directive in php.ini",
	        2=>"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
	        3=>"The uploaded file was only partially uploaded",
	        4=>"No file was uploaded",
	        6=>"Missing a temporary folder"
			);
			if (!isset($_FILES['Filedata'])) {
				upload_error("No file was selected");
				exit(0);
			} else if (isset($_FILES['Filedata']["error"]) && $_FILES['Filedata']["error"] != 0) {
				upload_error($uploadErrors[$_FILES['Filedata']["error"]]);
				exit(0);
			} else if (!isset($_FILES['Filedata']["tmp_name"]) || !@is_uploaded_file($_FILES['Filedata']["tmp_name"])) {
				upload_error("Upload failed is_uploaded_file test.");
				exit(0);
			} else if (!isset($_FILES['Filedata']['name'])) {
				upload_error("File has no name.");
				exit(0);
			}
			
			//Check file size
			$file_size = @filesize($_FILES['Filedata']["tmp_name"]);
			if (!$file_size || $file_size > $max_file_size_in_bytes) {
				upload_error("File exceeds the maximum allowed size") ;
				exit(0);
			}
			
			
			//Checking file type
			$types_array = preg_replace('/,/',' ',$types);
			$types_array = explode(' ',$types_array);
			if ($mob_api_upload) {
				$file_ext = getExt($_POST['name']);
			} else {
				$file_ext = getExt($_FILES['Filedata']['name']);
			}
			if(!in_array($file_ext,$types_array))
			{
				upload_error("Invalid file extension");
				exit(0);
			}
			
			if(file_exists(TEMP_DIR))
			{	
				move_uploaded_file($tempFile,$targetFile);
				if(file_exists($targetFile))
				{
					
					$min_res_req = min_res_enabled($targetFile);
					//pr($_FILES,true);
					if($min_res_req)
					{
						logData(json_encode($_POST),'post');
						if(isset($_POST['videoid'])&&isset($_POST['userid']))
						{
							$videoid = $_POST['videoid'];
							$userid = $_POST['userid'];
							$send_email_results = send_upload_email($videoid,$userid);
							logData($send_email_results,'upload_email');
						}
						$server_action = server_config('server_action');
						if($server_action==2)
							echo 'kiaaa!!!!!';
						elseif($server_action==3)
							unlink($targetFile);
						else
						{   
							$exec = PHP_PATH." -q ".BASEDIR."/actions/video_convert.php ";
							$exec .= getName($targetFileName)." ".getExt($targetFileName);
							$exec .= " ".$_SERVER['REMOTE_ADDR']." ".$file_directory;
							$exec .= " ".$videoid." ".$userid;
							$exec .= " > /dev/null 2>/dev/null &";
							$conv_results = shell_exec($exec);
							
							logData($exec,'command');
							echo json_encode(array("success"=>"yes","file_name"=>$file_name,"server_url"=>BASEURL,"thumbs_url"=>BASEURL.'/files/thumbs'));
						}
						
						if($server_action==3)
							unlink($targetFile);
					}
					else
					{
						$original_file_name = $_FILES['Filedata']['name'];
						$msg = "Your video ".$original_file_name." does not qualify for upload criteria.Please provide video whose resolution is at least ".$min_res_req." pixels.";
						$error =  json_encode(array("file_error"=>"yes","msg"=>$msg,"videoid"=>$_POST['videoid']));
						
						echo $error;
					}
					//exit();
					
					
				}
				else
				{
					exit('You Dont have permission to upload file!');
				}
			}
			else
			{
				exit('Target Upload File Doese not present...');
			}
		}
		else
		{
			exit('File Name, Directory or File data not sent properly ');
		}
	}break;
	

}


//function used to display error
function upload_error($error)
{
	echo json_encode(array("error"=>$error));
}
?>