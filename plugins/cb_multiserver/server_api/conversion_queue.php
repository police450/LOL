<?php
ini_set('max_execution_time', 0);
set_time_limit(0);
include("config.php");


//$path_queue = TEMP_DIR.'/*.mp4';
//$files = glob($path_queue);
$array = array();
$basedir = BASEDIR;
$callback = CALLBACK_URL;
$call_back = explode("/", $callback);

$count = count($call_back);
unset($call_back[($count-1)]);
unset($call_back[($count-2)]);
unset($call_back[($count-3)]);
unset($call_back[($count-4)]);
$call_back = implode('/', $call_back);
$call_back.= '/processing_file_name.php'; 

$ch = curl_init($call_back);		
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

curl_setopt_array($ch,$charray);

$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

$result = curl_exec($ch);	
curl_close($ch);

$file_names = json_decode($result,true);

pr(count($file_names),true);
$count = 0;
foreach ($file_names as $key => $value) 
{
	$count++;
	$cmd = "/usr/bin/php -q $basedir/actions/video_convert.php $value mp4 10.31.202.48 2015/05/17 ";
	//pr($cmd."\n",true);
	shell_exec($cmd);
	pr($count."\n".$value,true);
}
//pr($cmd,true);
//pr($files,true);
pr("done");

?>