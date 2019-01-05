<?php
header('Access-Control-Allow-Origin: *');
/**
 * This file is used to download files
 * from one server to our server 
 * in prior version, this file was not so reliable
 * this time it has complete set of instruction 
 * and proper downloader
 
 * @Author : Arslan Hassan
 * @License : Attribution Assurance License -- http://www.opensource.org/licenses/attribution.php
 * @Since : 01 July 2009
 */


include("../config.php");
include("../includes/classes/curl/class.curl.php");


if(!DEVELOPMENT_MODE)
{

	if($application_key!=$_POST['application_key'] || 
	$_POST['secret_key'] != $secret_key || !$secret_key || !$application_key)
	{
	echo json_encode(array("err"));
	exit();
	}

}	
error_reporting(E_ALL ^E_NOTICE);
	
/**
 * Call back function of cURL handlers
 * when it downloads a file, it works with php >= 5.3.0
 * @param $download_size total file size of the file
 * @param $downloaded total file size that has been downloaded
 * @param $upload_size total file size that has to be uploaded
 * @param $uploadsed total file size that is uploaded
 *
 * Writes the log in file
 */

if(!isCurlInstalled())
{
	exit(json_encode(array("error"=>"Sorry, we do not support remote upload")));
}
$uploaded_start = date("Y/m/d H:i:s");
//pr($_POST,true);
$_POST = $_REQUEST;
$file = $_POST['file'];
$file_name = mysql_clean($_POST['file_name']);

$log_file = TEMP_DIR.'/'.$file_name.'_curl_log.cblog';
//For PHP < 5.3.0
$dummy_file = TEMP_DIR.'/'.$file_name.'_curl_dummy.cblog';

$ext = getExt($file);
$svfile = TEMP_DIR.'/'.$file_name.'.'.$ext;

//Checking for the url
if(empty($file))
{
	$array['error'] = "Please enter file url";
	echo json_encode($array);
	exit();
}
//Checkinf if extension is wrong
$types = strtolower(VDO_EXTS);
$types_array = preg_replace('/,/',' ',$types);
$types_array = explode(' ',$types_array);
	
$extension_whitelist = $types_array;
if(!in_array($ext,$extension_whitelist))
{
	$array['error'] = "This file type is not allowed";
	echo json_encode($array);
	exit();
}

$curl = new curl($file);
$curl->setopt(CURLOPT_FOLLOWLOCATION, true) ;

//Checking if file size is not that goood
if(!is_numeric($curl->file_size) || $curl->file_size == '')
{
	$array['error'] = "Unknown file size";
	echo json_encode($array);
	exit();
}

if(phpversion() < '5.3.0')
{
	//Here we will get file size and write it in a file
	//called dummy_log
	$darray = array(
	'file_size' => $curl->file_size,
	'file_name' => $file_name.'.'.$ext,
	'time_started'=>time(),
	'byte_size' => 0
	);
	$do = fopen($dummy_file,'w+');
	fwrite($do,json_encode($darray));
	fclose($do);	
}

//Opening video file
$temp_fo = fopen($svfile,'w+');
$curl->setopt(CURLOPT_FILE, $temp_fo);

// Set up the callback
if(phpversion() >= '5.3.0')
{
	$curl->setopt(CURLOPT_NOPROGRESS, false);
	//$curl->setopt(CURLOPT_PROGRESSFUNCTION, 'callback');
}

$curl->exec();

if ($theError = $curl->hasError())
{
	$array['error'] = $theError ;
	echo json_encode($array);
}

//Finish Writing File
fclose($temp_fo);


//sleep(10);
//$details =  file_get_contents($log_file);
//$details = json_decode($details,true);
//$Upload->add_conversion_queue($details['file_name']);

if(file_exists($log_file))
unlink($log_file);
if(file_exists($dummy_file))
unlink($dummy_file);


move_uploaded_file($tempFile,$targetFile);
$uploaded_end = date("Y/m/d H:i:s"); 
$server_action = server_config('server_action');

if($server_action==3)
	unlink($targetFile);
else
{

	$file_directory = date('Y/m/d');
	echo $results = json_encode(array("success"=>"yes","start_time"=>$uploaded_start,"end_time"=>$uploaded_end));
	///file_put_contents(VIDEOS_DIR."/".$file_directory."/".getName($svfile)."_time_taken_download.txt", $results);
	
	$cmd_convert = PHP_PATH." -q ".BASEDIR."/actions/video_convert.php ".getName($svfile)." ".getExt($svfile)." ".$_SERVER['REMOTE_ADDR']." ".$file_directory." > /dev/null 2>/dev/null &";
	//pr($cmd_convert,true);
	shell_exec($cmd_convert);
}

if($server_action==3)
	unlink($targetFile);

?>