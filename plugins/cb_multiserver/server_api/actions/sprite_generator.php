<?php

include("../config.php");
include(BASEDIR."/includes/classes/thumb_rotate_class.php");


if($application_key!=$_POST['application_key'] || $_POST['secret_key'] != $secret_key || !$secret_key || !$application_key)
{
	echo json_encode(array("err"=>"Unable to authenticate"));
}else
{
	$file = $_POST['file_name'];
	$duration =  $_POST['duration'];
	$file_directory =  $_POST['file_directory'];
	
	$tr = new ThumbRotate();
	$msg = $tr->generateSprite(array("file_name"=>$file,"duration"=>$duration,"file_directory"=>$file_directory));
	
	echo json_encode(array("msg"=>$msg));
}
?>