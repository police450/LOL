<?php
//Author Awais Tariq
//File : thumb generator in case of 404
include(__DIR__.'/config.php');
$actual_link = $_SERVER['REQUEST_URI'];

$details = explode('/', $actual_link);

//extract details from Request Uri
$c = count($details);
$thumb = new Imagick();
$file_directory = $details[$c-4].'/'.$details[$c-3].'/'.$details[$c-2];
$file_name = $details[$c-1];


//extracting filename
$file_name = explode('-', $file_name);

//extracting thumb number
$thumb_num = explode('.', $file_name[count($file_name)-1]);


$file_name[0] = str_replace('.','',$file_name[0]);
$thumb_num[0] = (int) $thumb_num[0];


$files__dir = '/files/thumbs';

$image_dir = __DIR__.$files__dir.'/'.$file_directory.'/'.$file_name[0].'-'.$thumb_num[0].'.jpg';
$thumbs_base = __DIR__.$files__dir.'/'.$file_directory;
// pr($image_dir,true);
//Check if image file with number exists
if(!file_exists($image_dir))
{
	$image_dir = __DIR__.$files__dir.'/'.$file_directory.'/'.$file_name[0].'-1.jpg';
	$thumbs_base = __DIR__.$files__dir.'/'.$file_directory;
}

if(!file_exists($image_dir))
{
	$image_dir = __DIR__.$files__dir.'/'.$file_directory.'/'.$file_name[0].'-big.jpg';
	$thumbs_base = __DIR__.$files__dir.'/'.$file_directory;
}

//Check if required file not found check for orignam 1 file
if(!file_exists($image_dir))
{
	$image_dir = __DIR__.$files__dir.'/'.$file_name[0].'-1.jpg';
	$thumbs_base = __DIR__.$files__dir;	
}

//Check if required file not found check for orignam 1 file
if(!file_exists($image_dir))
{
	$image_dir = __DIR__.$files__dir.'/'.$file_name[0].'-big.jpg';
	$thumbs_base = __DIR__.$files__dir;	
}



//pr($file_name,true);
$is_dim = strpos($file_name[1], 'x');

if(!$is_dim)
{
	$image_width = 160;
	$image_height = 100;
}
else
{
	//extrating Dimensions
	$dimensions = explode('x', $file_name[1]);

	$image_width = (int) $image_width;
	$image_height = (int) $image_height;
	
	if(isset($dimensions[0])  && $dimensions[0] > 30 && isset($dimensions[1])  && $dimensions[1] > 20)
	{

		$image_height = (int)$dimensions[1];
		$image_width = (int)$dimensions[0];
	}
}
if(empty($image_height)||empty($image_width)||$image_height==0||$image_width==0)
{
	$image_height = 164;
	$image_width = 263;
}


//Check if nothing found then load processing thumb

if(!file_exists($image_dir) || $image_height > 1080 || $image_width > 1920 )
{
	//$image_height = min($image_height,1080);
	$image_width = min($image_width,1920);
	$image_height = $image_width / 1.6;

	//header('content-type:image/jpeg');
	$default_thumb = __DIR__.$files__dir.'/no_thumb.jpg';
	$image = new imagick( $default_thumb );
    $image->cropThumbnailImage( $image_width, $image_height );
	$output = $image->getimageblob();
    $outputtype = $image->getFormat();
	//echo $outputtype, $output;
	//
	header('Content-Type:'.$outputtype);
	//header('Content-Length: ' . filesize($output));
	echo $output;
	exit();
}
//setting output directory

$output_directory = $thumbs_base.'/'.$file_name[0].'-'.$image_width.'x'.$image_height.'-'.$thumb_num[0].'.jpg';

if(!file_exists($output_directory))
{


	//use imagic magic for croping
	$im = new imagick( $image_dir );
	$im->setImageCompressionQuality(85);
	$im->cropThumbnailImage( $image_width, $image_height );
	/* Write to a file */
	//$im->adaptiveResizeImage($image_width,$image_height);

	$im->writeImage( $output_directory );



}
	
	$debug = false;
	if(isset($_GET['debug'])) $debug = true;

//Displaying Created image 
$file = $output_directory;
$type = 'image/jpeg';


if(!$debug)
{

	header('Content-Type:'.$type);
	header('Content-Length: ' . filesize($file));
	readfile($file);
}else
{
	echo file_get_contents($file);
}

?>
