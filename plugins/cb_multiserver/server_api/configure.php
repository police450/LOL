<?php

include("config.php");

$file = 'configs.php';
$settings = 'settings.php';
$do_settintgs = false;
if($_POST['post_fields'])
{	
	if($_POST['waterMarkFile'])
		$waterMarkFile = $_POST['waterMarkFile'];
	if($_POST['videowaterMarkFile'])
		$videowaterMarkFile = $_POST['videowaterMarkFile'];

	if($_POST['settings'])
		$do_settintgs = "yes";	
	$_POST = json_decode($_POST['post_fields'],true);
}


if($application_key!=$_POST['application_key'] || $_POST['secret_key'] != $secret_key || !$secret_key || !$application_key)
{
	echo json_encode(array("err"=>"Unable to authenticate"));
}else
{  
    if($videowaterMarkFile)
    {
        $content = $videowaterMarkFile;
		$wmfile = fopen('video_watermark.png','w+');
		fwrite($wmfile,$content);
		fclose($wmfile);
    }

	if($waterMarkFile)
	{
		$content = $waterMarkFile;
		$wmfile = fopen('photo_watermark.png','w+');
		fwrite($wmfile,$content);
		fclose($wmfile);
	}else
	{
		if($do_settintgs)
		{
			$fo = fopen($settings,'w+');
			fwrite($fo,"<?php //".json_encode($_POST)." ?>");
			fclose($fo);
			
		}else
		{
			$fo = fopen($file,'w+');
			fwrite($fo,"<?php //".json_encode($_POST)." ?>");
			fclose($fo);
		}
			
	}
	
}
	
?>