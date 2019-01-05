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
//pr($call_back,true);
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

pr("total processing files in db => ".count($file_names),true);

$c = 0;
foreach ($file_names as $key => $file_name) 
{
	$basedir = BASEDIR."/files/temp/".$file_name.".mp4";
	if(file_exists($basedir))
	{
		$c++;
		$resul[] = $file_name;
	}
}
pr("Total Files present on this server to be converted =>".$c,true);
pr($resul,true);
?>