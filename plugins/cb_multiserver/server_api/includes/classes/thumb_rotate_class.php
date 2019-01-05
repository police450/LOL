<?php
/**
 * @ Author Fawaz Tahir
 * @ File : Thumb Rotate Class
 * @ Release Date : Feb 2010
 * @ Version : v1.0
 * @ Description: Main Class for ThumbRotate Plugin.
 */

class ThumbRotate
{
	var $TRconfigs = array();
	var $tblConfigs   = "tr_configs";
	var $listFile = 'videos';
	var $fileExt = 'temp';
	var $cachedTime;
	var $limit;
	var $currentPage;
	var $folder = "";

	/**
	 * CONTRUCTOR
	 */	
	function ThumbRotate()
	{
		$this->TRconfigs = $this->trGetConfigs();
		$this->limit = $this->TRconfigs['tr_display_limit'];
		$this->cachedTime = $this->getCachedTime();
	}

	/**
	 * Function used to create json_encode array
	 * @ $array = Array()
	 */
	function jsonEncode($array)
	{
		if(!is_array($array) || empty($array))
			return false;
		else
		{
			return json_encode($array);	
		}
	}
	
	/**
	 * Function used to create json_decode array
	 * @ $string = json array
	 */	
	function jsonDecode($string,$assArray=true) 
	{
		return json_decode($string,$assArray);
	}
	
	/**
	 * Function used to get thumb rotate plugin configs
	 * @ $return = set true if you want to return array
	 * @ $count = set to true if only want to count configs
	 * @ returns Array(); 
	 */  
	function trGetConfigs($return=false,$count=false)
	{
		global $db;
		$configs = array(
		'tr_no_of_thumbs'	=> config("num_sprite_thumbs"),
		'tr_keep_thumbs'	=> config("keep_sprite_thumbs"),
		'tr_time_interval'	=> config("tr_time_interval"),
		);
		
		if($count)
			return count($configs);
		else
			return $configs;
	}
	
	
	/**
	 * Function used create sprite from images
	 * @$vdetails = Video Details Array();
	 * @$directory = Directory in which files are located
	 * @$moveFile = Move sprite file to video thumbs folder
	 * @Note: This function is not created by me. I can't find the
	 * source url.
	 * @return String. sprite dir_path;
	 */	
	function createSprite($vdetails,$directory,$moveFile=true)
	{
		if(empty($vdetails) || empty($directory))
			e(lang("Video details or Directory in which thumbs are located are empty"));
		else
		{
			$width  = 160; // This is hard-coded. 
			$height = 120; // This is hard-coded.
			$canvasHeight = 0;
			
			$pattern = $directory."/".$vdetails['file_name']."-*.jpg";
			$files = glob($pattern);
			if(empty($files))
				exit("Found 0 files for <strong>".$vdetails['title']."</strong>. Make sure you have a running video file on your server.");
			else
			{
				$total = count($files);
				foreach($files as $file)
				{
					$pathParts = explode("/",$file);
					$filename = $pathParts[count($pathParts)-1];
					$names[] = $filename;	
				}
				natsort($names);
				
				foreach($names as $name)
				{
					$canvasHeight += $height;	
				}
				
				/* CREATING OUR CANVAS */
				$resource = imagecreatetruecolor($width,$canvasHeight);
				$background = imagecolorallocatealpha($resource, 255, 255, 255, 127);
				imagefill($resource, 0, 0, $background);
				imagealphablending($resource, false);
				imagesavealpha($resource, true);
				
				/* PLACING OUR THUMBS IN CANVAS */
				$posY = 0;
				foreach($names as $name)
				{
					$tmp = imagecreatefromjpeg($directory."/".$name);
					if (imagesy($tmp) == $height) {
						imagecopy($resource, $tmp, 0, $posY, 0, 0, $width, $height);
						$posY += ($height);
					}
					imagedestroy($tmp);	// Releasing our resources				
				}
				
				/* CREATING JPEG IMAGE */
				$spriteFile = $directory."/".$vdetails['file_name']."-sprite.jpg";
				imagejpeg($resource,$spriteFile,100);
				if($moveFile)
					rename($spriteFile,THUMBS_DIR."/".$this->folder.$vdetails['file_name']."-sprite.jpg");				
				return $spriteFile;
			}
		}
	}
	
	/**
	 * Function used generate 160x120 thumbs for video
	 * using FFMPEG or MPlayer. After creation of thumbs
	 * createSprite will be called.
	 * @$vdetails = Video Details Array();
	 * @$forceCreation = if TRUE, it will be create sprite regardless it already exists or not,
	 * @ overwritting everything
	*/
	function generateSprite($vdetails,$forceCreation=false,$writeFile=true)
	{
		global $db;

		if(empty($vdetails))
			return false;	
		else
		{
			
			require_once(BASEDIR.'/includes/classes/conversion/ffmpeg.class.php');
			$noThumbs = $this->TRconfigs['tr_no_of_thumbs'];
			$timeInterval = $this->TRconfigs['tr_time_interval'];
			$this->folder = $vdetails['file_directory'];
			$file = $vdetails['file_name'].'.flv';
			$file = VIDEOS_DIR."/".$this->folder.$file;
			$tmpFolder = TEMP_THUMBS_DIR."/".getName($file);
			$ffmpeg = new ffmpeg($file);
			if(!file_exists($tmpFolder))
				mkdir($tmpFolder,0777);			
			//First checking if we have interval
		
			if($timeInterval != 0)
			{
				
				$leastTime = $timeInterval*4;
				if($vdetails['duration'] < $leastTime)
					$timeInterval = 0;	
				else
				{
					$noThumbs = floor($vdetails['duration'] / $timeInterval);
					
					for($i=1;$i<=$noThumbs;$i++)
					{
						$time = $i*$timeInterval;
						$fileName = getName($file)."-".$i.".jpg";
						$filePath = $tmpFolder."/".$fileName;
						if(!empty($ffmpeg->mplayerpath)) #mplayer is not working on local because of the ':' in basedir D:\
						 	$command = $ffmpeg->mplayerpath." $file -ss $time -frames 1 -nosound -vf scale=160:120 -vo jpeg:quality=100:outdir=$tmpFolder";
						else		
							$command = $ffmpeg->ffmpeg." -ss $time -i $file -an -s 160x120 -f image2 -vframes 1  $filePath";
						$ffmpeg->exec($command);
						echo $command."<br/>";	
					}
				}
			}
			// No interval found
			if($timeInterval == 0)
			{
			
				if($noThumbs > 1 && $vdetails['duration'] > $noThumbs)
				{
					$duration = $vdetails['duration'] - rand(0,5);
					$division = $duration / $noThumbs;
					$count=1;
					for($id=3;$id<=$duration;$id++)
					{

						$file_name = getName($file)."-$count.jpg";
						$file_path = $tmpFolder.'/'.$file_name;
						
						$id	= $id + $division - 1;
						$time = $ffmpeg->ChangeTime($id);
						
						$dimension = " -s 160x120  ";
						$mplayer_dim = "-vf scale=160:120";
						
						if(USE_MPLAYER && $this->mplayer) #mplayer is not working on local because of the ':' in basedir D:\
							$command = $ffmpeg->mplayer." '$file' -ss $time -frames 1 -nosound $mplayer_dim -vo jpeg:quality=100:outdir='$tmpFolder'";
						else	
							$command = $ffmpeg->ffmpeg." -ss $time -i $file -an -r 1 $dimension -y -f image2 -vframes 1 $file_path ";
						
						$ffmpeg->exec($command);	
						echo $command."<br/>";
						$count = $count+1;
					}	
				} else {
					$file_name = getName($file)."-%d.jpg";
					$file_path = $tmpFolder."/".$file_name;
					$command = $ffmpeg->ffmpeg." -i $file -an -s 160x120 -y -f image2 -r 1 -vframes $noThumbs $file_path ";
					$ffmpeg->exec($command);	
					$noThumbs = count(glob($tmpFolder."/*"));
					//echo $command;				
				}
			}
			// Now time to create sprite file.
			$sprite = $this->createSprite($vdetails,$tmpFolder);
			if($sprite)
			{
					$storedThumbs = 0;
					if($this->TRconfigs['tr_keep_thumbs'] == 1)
					{
						if(!file_exists(TR_THUMBS_DIR))
							mkdir(TR_THUMBS_DIR,0777);
						rename($tmpFolder,TR_THUMBS_DIR."/".getName($file)); // User want thumbs stored moving to permanent folder
						$storedThumbs = 1;
					} else {
						$this->removeDirectory($tmpFolder);	
					}
					$this->updateVideoDetails($vdetails['videoid'],'1',$noThumbs,$storedThumbs);
					if($writeFile)
					{	
						$list = TR_FILE_DIR."/".$this->listFile.$this->cachedTime.".".$this->fileExt;
						$this->tempFileAction($list,$vdetails,'remove');
					}
					return $noThumbs;	
			}
		}
	}

	/**
	 * rmdir() function requires directory to be
	 * empty due to this removeDirectory came into
	 * being. It firsts deletes all files than directory
	 * if no files found, it will remove directory.
	*/	
	function removeDirectory($directory)
	{
		if(file_exists($directory))
		{
			$files = glob($directory."/*");
			if($files)
			{
				array_map("unlink",$files);
				rmdir($directory);
			} else {
				rmdir($directory);	
			}
		}
	}
	
	/**
	 * This function is used to add/remove
	 * video from our temp video list file
	 */
	function tempFileAction($file,$array,$action='add',$removeFile=true)
	{
		if(file_exists($file))
		{
			$videos = $this->readFile($file);
			if($action == 'add')
			{
				$videos[$array['videoid']] = $array;
				krsort($videos);	
			} elseif($action == 'remove') {
				if($videos[$array['videoid']])
					unset($videos[$array['videoid']]);
			}
			if($removeFile)
				$this->writeFile($videos,$file);
			else
				$this->writeFile($videos);	
		} else {
			if($action == 'add')
			{
				$videos[$array['videoid']] = $array;
				$this->writeFile($videos);	
			}
		}
	}
		
	/**
	 * Function used to get videos which does not
	 * have sprite thumb
	 * @$forceDB = Boolean => If true, this will refresh list
	 *  from database otherwise it will read from file
	 */
	function getNonSpriteVideos($forceDB=false)
	{
		global $db;
		$textFile = TR_FILE_DIR."/".$this->listFile.$this->cachedTime.".".$this->fileExt;	
		if(file_exists($textFile) && !$forceDB)
		{
			return $this->readFile($textFile);	
		} else {
			$params = array("status"=>"Successful","order"=>" date_added DESC","cond"=>" AND has_sprite = '0' AND (embed_code = 'none' AND refer_url = '')");
			$videos = get_videos($params);
			{
				$newFile = $this->writeFile($videos,$textFile);
				return $this->readFile($newFile);	
			}
		}
	}

	/**
	 * Function used to write file
	 * @$array = Array()
	 * @$previousFile = String. Delete the previous we have
	 */
	function writeFile($array,$previousFile=NULL)
	{
		if(!file_exists(TR_FILE_DIR))
			mkdir(TR_FILE_DIR,0777);
		$cachedTime = TR_FILE_DIR."/cached.time";
		if(file_put_contents($cachedTime,time()))
		{			
			if($previousFile != NULL)
				if(file_exists($previousFile))
					unlink($previousFile);
					
			$file = TR_FILE_DIR."/".$this->listFile.$this->getCachedTime().".".$this->fileExt;
			file_put_contents($file,$this->jsonEncode($array));
			return true;
		}
	}
	
	/**
	 * This is used to get thumbs of video that where
	 * created to make sprite.
	 * @$video = Array()
	 * @$returnThumbs = Boolean
	 * @$returnDIR = Boolean
	 */
	function getVideoSpriteThumbs($video,$sortNormal=true,$withURL=true)
	{
		if(!is_array($video))
			$video = get_video_details($video);
		else
			$video = $video;
		if(empty($video))
			e("Video does not exist");
		else
		{
			$directory = TR_THUMBS_DIR."/".$video['file_name'];
			if($video['tr_stored_thumbs'] == 0 || !file_exists($directory))
				e(lang("This video does not have any thumbs stored."));
			else
			{
					$thumbs = glob($directory."/*");
					if($thumbs)
					{
						foreach($thumbs as $thumb)
						{
							$name = end(explode("/",$thumb));
							if($withURL)
								$spriteThumbs[] = TR_THUMBS_URL."/".$video['file_name']."/".$name;
							else
								$spriteThumbs[] = "/".$video['file_name']."/".$name;		
						}
						if($sortNormal)
							natsort($spriteThumbs);
						return $spriteThumbs;
					} else {
						e(lang("No thumbs found in folder. It is empty."));	
					}
			}
		}
	}

	/**
	 * Function used to update video
	 * details
	 */
	function updateVideoDetails($videoid,$has_sprite,$sprite_thumbs_no,$tr_stored_thumbs)
	{
		global $db;
		return false;
	}
	
	/**
	 * Function used to delete sprite file
	 * and if thumbs are available
	 * @$vdetaisl = Array();
	 */
	function deleteVideoSprite($videoid,$complete=true)
	{
		global $cbvid;
		if(empty($videoid))
			e(lang("Video ID is empty"));
		elseif(!$cbvid->video_exists($videoid))
			e(lang("Video does not exist."));
		else
		{
			$video = $cbvid->get_video_details($videoid);
			$sprite = get_thumb($video,'sprite',false,false,false,false);
			if(get_thumb_num($sprite) != 'sprite' || $video['has_sprite'] == 0)
			{
				$message = "Video does not have sprite based thumb.";
				if($video['embed_code'] == "none" && $video['refer_url'] == "")
					$message .= " <strong><a href='".BASEURL."/admin_area/plugin.php?folder=thumb_rotate/admin/&file=generate_sprite.php&vid[]=".$video['videoid']."&mode=single'>Link here to Create One</a></strong>";	
				e(lang($message));
			}
			else
			{
				$file = TR_FILE_DIR."/".$this->listFile.$this->cachedTime.".".$this->fileExt;
				if(file_exists(TR_THUMBS_DIR."/".$video['file_name']))
					$this->removeDirectory(TR_THUMBS_DIR."/".$video['file_name']);
				unlink(THUMBS_DIR."/".$sprite);
				if(!file_exists(THUMBS_DIR."/".$sprite))
				{
					$this->updateVideoDetails($video['videoid'],'0','0','0');
					$this->tempFileAction($file,$video);
					e(lang("Video sprite and sprite thumbs deleted successfully."),"m");
				}
			}
		}
	}
	
	/**
	 * Function used to create limited array
	 * for displaying.
	 * @$array = Array()
	 */
	function createArray($array)
	{
		if(is_array($array))
		{		
			$currentPage = $this->currentPage - 1;
			$start = $currentPage * $this->limit;
			$limit = $this->limit;
			if($limit > count($array))
				$limit = count($array);

			$videos = array_splice($array,$start,$limit,true);
			foreach($videos as $video)
			{
				$newArray[$video['videoid']] = $video;	
			}
			return $newArray;
		} else {
			return false;	
		}
	}

	/**
	 * Function used to create pagination
	 * @$currentPage = Numeric
	 * @$total_videos = Total Videos
	 */
	function simplePagination($total_videos,$currentPage)
	{
		$pages = ceil($total_videos / $this->limit);
		
		if(empty($currentPage) || $currentPage == 0 || $currentPage < 0)
			$currentPage = 1;
		elseif($currentPage > $pages)
			$currentPage = $pages;
				
		$this->currentPage = $currentPage;	
		$pageLink = "?".$_SERVER['QUERY_STRING'];
		$pageLink = preg_replace(array('/(\?page=[0-9]+)/','/(&page=[0-9]+)/','/(page=[0-9+])+/'),'',$pageLink);
		for($i=1;$i<=$pages;$i++)
		{
			if($this->currentPage == $i) 
				$selected = " selected";
			else
				$selected = '';
					
			$link .= "<a href='plugin.php".$pageLink."&page=$i' class='trPageLink".($selected)."'>".$i."</a>";	
		}
		return $link;
	}
	
	/**
	 * Function used to read videos array file
	 * @$file = Filename
	 * @return Array()
	 */
	function readFile($file)
	{
		$fullPath = TR_FILE_DIR."/".$this->listFile.$this->cachedTime.".".$this->fileExt;
		$content = file_get_contents($fullPath);
		if(!empty($content))
		{
			$content = $this->jsonDecode($content);
			if(is_array($content))
			{
				foreach($content as $video)
				{
					$newArray[$video['videoid']] = $video;	
				}
				return $newArray;
			}
		}
	}

	/**
	 * Function used to get cached time
	 * @return cached time
	 */
	function getCachedTime()
	{
		$cachedFile = TR_FILE_DIR."/cached.time";
		if(!file_exists($cachedFile))
			return false;
		else
		{
			$contents = file_get_contents($cachedFile);
			$this->cachedTime = $contents;
			return $contents;	
		}
			
	}
	
	/**
	 * Function used to update configurations
	 * @$array = Array()
	 * @return JsonEncoded Array
	 */
	function trUpdateConfigration($array=NULL)
	{
		global $db;
		
		if($array == NULL)
			$array = $_POST;	
		foreach($array as $name=>$value)
		{
			if(!is_numeric($value))
				return $this->jsonEncode(array("error"=>"<strong>".$name."</strong> should be a numeric value."));
			else
			{
				$db->execute("UPDATE ".$this->tblConfigs." SET tr_value='$value' WHERE tr_name = '$name'");
			}
		}
		return $this->jsonEncode(array("success"=>"Your changes have been saved."));
	}
	
	/**
	 * This function add links in Video Manager of Admin Area
	 * @$v = Array(); Video Details Array
	 * @ return $links
	 */
	function addVideoManagerLinks($v)
	{
		$links = '';
		if($v['has_sprite'] == 1 && $v['tr_stored_thumbs'] == 1)
			$links .= '| <a href="?manageThumbs='.$v['videoid'].'">Manage Thumbs</a>';
		if($v['has_sprite'] == 1)
		{
			$message = "You sure, you want to delete sprite thumb? This will also delete the stored thumbs if found any.";
			$link = "?deleteSprite=".$v['videoid']."";
			$id = $v['videoid'];
			$links .= ' | <a href="javascript:void(0)" id="deleteSprite-'.$id.'" onmouseup="delete_item(\'deleteSprite\',\''.$id.'\',\''.$message.'\',\''.$link.'\');">Delete Sprite</a>'; 
		}
		return $links;				
	}
	
	/**
	 * This function add links in Video Manager of Admin Area
	 * @$video = Array(); Video Details Array
	 * @ $thumb = String; Complete thumb name with extension
	 */
	function TRsetDefaultThumb($video,$thumb)
	{
		global $Upload;
		// First check how many thumbs we already have ..
		$thumbNo = $Upload->get_available_file_num($video['file_name']);
		
		// Time to copy file to video thumbs directory
		$oldFile = TR_THUMBS_DIR."/".$video['file_name']."/".$thumb; $newName = $video['file_name']."-".$thumbNo.".jpg";
		$newFile = THUMBS_DIR."/".$newName;
		if(copy($oldFile,$newFile))
		{
				global $cbvid;
				$cbvid->set_default_thumb($video['videoid'],$newName);
		} else {
			e(lang("We failed to copy thumb to ClipBucket Directory. Please confirm that folder chmod is <strong>0777</strong>"));	
		}
	}
	
	/**
	 * This is used to create sprite thumb after video has converted successfully. If error occurs, it will added to list. :D
	 * Because after_convert_functions accept coversion details. We need to get video details from file_name found in
	 * $file_details array.
	 */
	function TRafterConvertSprite($file_details)
	{
		if(is_array($file_details))
		{
			$file_name = $file_details['cqueue_name'];
			$filename = reset(explode("-",$file_name));
			if($filename)
				$fileName = $filename;
			else
				$fileName = $file_name;
				
			if(!empty($fileName))
			{
				global $cbvideo;
				$videoArray = $cbvideo->get_video($fileName,true);
				if(!empty($videoArray))
				{
					$video = $videoArray;
					$this->generateSprite($video);
					if(error())
					{
						$tmpFile = TR_FILE_DIR."/".$this->listFile.$this->cachedTime.".".$this->fileExt;
						$this->tempFileAction($tmpFile,$video);
					}
				} 
			}
		}
	}
	
	/**
	 * This is used to delete thumb of sprite.
	 */
	function TRdeleteSpriteThumb($video,$thumb)
	{
		$fullPath = TR_THUMBS_DIR."/".$video['file_name']."/".$thumb;
		if(file_exists($fullPath))
		{
			unlink($fullPath);
			e(lang("Sprite thumb deleted successfully."),"m");	
		} else {
			e(lang("File not found."));	
		}
	}
}

?>