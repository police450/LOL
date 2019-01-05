<?php
header('Access-Control-Allow-Origin: *');  
/**
 * @Author : Fawaz Tahir, Arslan Hassan
 * License : SWFUpload  <http://swfupload.org/>
 * This file is used to upload file using SWFUpload
 * you dont need to edit this file, edit it at yout own risk :)
 */
include('../config.php');

$exts = explode(",",PHOTO_EXTS);
$max_size = 1048576; // 2MB in bytes
$form = "photoUpload";
$path = PHOTOS_DIR."/";

// These are found in $_FILES. We can access them like $_FILES['file']['error'].
$upErrors = array(
				  0 => "There is no error, the file uploaded with success.",
				  1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
				  2 => " The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
				  3 => "The uploaded file was only partially uploaded.",
				  4 => "No file was uploaded.",
				  6 => "Missing a temporary folder.",
				  7 => "Failed to write file to disk."
				  );
				  
// Let's see if everything is working fine by checking $_FILES.
if(!isset($_FILES[$form])) {
	upload_error("No upload found in \$_FILES for " . $form);
	exit(0);
}
elseif(isset($_FILES[$form]['error']) && $_FILES[$form]['error'] != 0) {
	upload_error($upErrors[$_FILES[$form]['error']]);
	exit(0);
}
elseif(!isset($_FILES[$form]["tmp_name"]) || !@is_uploaded_file($_FILES[$form]["tmp_name"])) {
	upload_error("Upload failed is_uploaded_file test.");
	exit(0);
} elseif(empty($_FILES[$form]['name'])) {
	upload_error("File name is empty");
	exit(0);	
}

// Time to check if Filesize is according to demands
//$filesize = filesize($_FILES[$form]['tmp_name']);
//if(!$filesize || $filesize > $max_size)
//{
//	upload_error("File exceeds the maximum allowed size");
//	exit(0);
//}
//
//if($filesize < 0)
//{
//	upload_error("File size outside allowed lower bound");
//	exit(0);
//}

//Checking Extension of File
$info = pathinfo($_FILES[$form]['name']);
$extension  = strtolower($info['extension']);
$valid_extension = false;

foreach ($exts as $ext) {
	if (strcasecmp($extension, $ext) == 0) {
		$valid_extension = true;
		break;
	}
}

if(!$valid_extension)
{
	upload_error("Invalid file extension");
	exit(0);	
}

$filename = time().RandomString(6);

$folder = "";
if(config('create_subfolders'))
{
	$folder = createDataFolders().'/';
}
	
//Now uploading the file
if(move_uploaded_file($_FILES[$form]['tmp_name'],$path.$folder.$filename.".".$extension))
{
	// Photo Details
	$collection = $_POST['collection'];
	$name = getName($_FILES[$form]['name']);
	$desc = $name;
	$tag = strtolower($name);
			
	//Making Array for inserting
	$flds = array("photo_title","photo_description","photo_tags","filename","ext","server_url",'folder');
	$vls  = array($name,$desc,$tag,$filename,$extension,PHOTOS_URL,$folder);
	
	if(!empty($collection))
	{
		$flds[] = "collection_id";
		$vls[] = $collection;	
	}
	$total = count($flds);
	
	for($i=0;$i<$total;$i++)
	{
		$detailsArray[$flds[$i]] = $vls[$i];	
	}
		
	// Creating Thumb and Med Size Image
	//createThumb($path.$filename.".".$extension,$path.$filename."_t.".$extension,$extension,$cbphoto->thumb_width,$cbphoto->thumb_height);
	/*$cbphoto->createThumb($path.$filename.".".$extension,$path.$filename."_m.".$extension,$extension,$cbphoto->mid_width,$cbphoto->mid_height);*/
	
	$image = str_replace($path,PHOTOS_URL."/",$path.$folder.$filename."_t.".$extension);
	$detailsArray['image_file'] = $image;
	
	generate_photos(array('filename'=>$filename,'ext'=>$extension,'folder'=>$folder));
	
	echo json_encode(array("success"=>"yes","filename"=>$filename,"extension"=>$extension,
	"server_url"=>PHOTOS_URL,'folder'=>$folder));
	
	//$FinalVar = base64_encode(serialize($detailsArray));
	//echo $FinalVar;
} else {	
	upload_error("File could not be saved.");
	exit(0);	
}


//function used to display error
function upload_error($error)
{
	echo json_encode(array("error"=>$error));
} 
?> 