<?php
/**
* File: Functions 
* Description: Used for common jobs such as subtitle uploading, filtering, selection, playing, deletion etc
* Functions: Various
* @since: ClipBucket 2.8.1
* @author: Saqib Razzaq
* @since: 6th November, 2015
* Last Updated: 12th November, 2015
*/

# SUB = subtitle

	/**
	* Pulls all configurations for CB Subtitles plugin
	* @param: None
	* @return: {array} { An array with all configurations }
	*/
 
	function honey_capt_configs()
	{
		global $db;
		$results = $db->select(tbl("honey_capt_configs"),"*", "");
		$results = $results[0];
		return $results;
	}

	/**
	* Calls multiple functions to initate plugin and to do simple tasks
	* @param: None
	*/

	function honey_capt_trigger($status = false)
	{
		add_admin_menu('CB Subtitles','Plugin Liscence','lisc_update.php', HONEY_CAPT_BASE.'/admin');
		add_admin_menu('CB Subtitles','Documentation','honey_capt_doc.php', HONEY_CAPT_BASE.'/admin');
		if ($status == "Active")
		{
			define_honey_constants();
			fire_honey_admin_menus();
			fire_honey_anchors();
			make_def_sub();

			if ( isset($_GET['del_sub']) )
			{
				$subid = $_GET['del_sub'];
			}

			delete_sub( $subid );

			if ( BACK_END || THIS_PAGE == 'edit_video' )
			{
				if ( isset($_POST) )
				{
					upload_subtitle();
				}
			}
		}
	}

	function okLisc() {

	}


	/**
	* Gets value of maximum allowed subtitle files per video
	* @param: None
	* @return: {array} { An array including maximum allowed subtitles value }
	*/

	function max_subs_allowed()
	{
		global $db;
		$results = $db->select(tbl("honey_capt_configs"),"max_sub_files");
		return $results;
	}

	/**
	* Gets value of maximum subtitle file size
	* @param: None
	* @return: {array} { An array including maximum allowed subtitle file size value }
	*/

	function max_subs_size()
	{
		global $db;
		$results = $db->select(tbl("honey_capt_configs"),"max_sub_file_size");
		return $results;
	}

	/**
	* Takes all configurations values, defines constants for them then assigns 
	* all those values to be used in smarty templates
	* @param: None
	*/

	function define_honey_constants() 
	{
		global $db;
		$honey_configs = array();
		$honey_configs = $db->select(tbl("honey_capt_configs"),"*", "allowed_users!=''");
		$subbed_vids = total_sub_vids();
		$subs_uploaded = total_subs_uploaded();
		
		foreach($honey_configs as $configs)
		{
			$enable_subs = $configs['enable_subs'];
			$max_sub_files = $configs['max_sub_files'];
			$max_sub_file_size = $configs['max_sub_file_size'];
			$min_vid_len = $configs['min_vid_len'];
		}
		
		define('ENABLE_SUBS', $enable_subs);
		define('MAX_SUB_FILES',$max_sub_files);
		define('MAX_SUB_FILE_SIZE',$max_sub_file_size);
		define('MIN_VID_LEN', $min_vid_len);
		
		assign('enable_subs', ENABLE_SUBS);
		assign('max_sub_files',MAX_SUB_FILES);
		assign('max_sub_file_size',MAX_SUB_FILE_SIZE);
		assign('min_vid_len',MIN_VID_LEN);
		assign('subbed_vids', $subbed_vids);
		assign('subs_uploaded', $subs_uploaded);

	}

	/**
	* Diplays subtitle uploading form on Edit Video page (Admin & Front End User)
	* @param: None
	*/

	function edit_video_form()
	{
		if ( ENABLE_SUBS == 'yes' )
		{
			if ( isset($_GET['video']) )
			{
				$substitles = get_all_subs($_GET['video']);
				$total_subs = get_total_subs($_GET['video']);
			}
			elseif ( isset($_GET['vid']) )
			{
				$substitles = get_all_subs($_GET['vid']);
				$total_subs = get_total_subs($_GET['vid']);
			}
			assign("subs", $substitles);
			assign("total_subs", $total_subs);

			Template(HONEY_CAPT_HTML.'/edit_video_form.html',false);
		}
		
	}

	/**
	* Diplays subtitle uploading form on Upload Video page
	* @param: None
	*/

	function upload_video_form()
	{
		if ( ENABLE_SUBS == 'yes' )
		{
			Template(HONEY_CAPT_HTML.'/upload_video_form.html',false);
		}
	}

	/**
	* Checks if given file is valid caption format (WebVTT)
	* @param: {string} { $file } { name of the file that is to be checked for extension }
	* @param: {boolean} { $msg } { false by default but if set true it displays errors }
	* @return: {string} { extension of given false }
	*/

	function is_caption( $file, $msg = false )
	{
		$ext = pathinfo($file, PATHINFO_EXTENSION);

		if ( $ext == 'vtt' )
		{
			if ( $msg )
			{
				echo "File ".$file." is VTT file which is valid";
			}

			return $ext;
		}
		else
		{
			if ( $msg )
			{
				echo "Invalid extension (".$ext.") provided by file ".$file;
			}

			return false;
		}
	}

	/**
	* Checks if given file exists or not
	* @param: {string} { $file_name } { name of the file to check }
	* @param: {string} { $caption_dir } { false by default but if true it checks in given directory }
	* @return: {string} { Name of file if it exists }
	*/

	function caption_exists( $file_name, $caption_dir = false )
	{
		#exit($file_name);
		if ( $caption_dir )
		{
			$capt_dir = $caption_dir;
		}
		else
		{
			$capt_dir = BASEDIR.'/files/captions/';
		}

		$file_check = $capt_dir.$file_name;

		if ( file_exists($file_check) )
		{
			return $file_name;
		}
		else
		{
			return false;
		}
	}

	/**
	* Gets complete video duration using given VideoId
	* @param: {integer} { $videoid } { id of video to get duration for }
	* @param: {integer} { Duration of video in seconds }
	*/

	function get_video_dur( $videoid )
	{
		global $db;
		$data = $db->select(tbl("video"),"duration", "videoid='$videoid'");
		$video_duration = $data[0]['duration'];
		return $video_duration;
	}

	/**
	* Gets part of string between two characters
	* @param: {string} { $str } { string to cut part from }
	* @param: {string} { $from } { character to start slicing }
	* @param: {string} { $to } { character to end slicing }
	* @return: {string} { Sliced part of string }
	*/

	function the_string_between($str,$from,$to)
	{
    	$sub = substr($str, strpos($str,$from)+strlen($from),strlen($str));
    	return substr($sub,0,strpos($sub,$to));
	}

	/**
	* Increments total subtitles value by 1
	* @param: {string} { $file_name } { name of subtitle file for which to increment }
	* @return: {integer} { Returns incremented subtitle file count }
	*/

	function increment_name( $file_name )
	{
		if ( file_exists($file_name) )
		{
			$number = the_string_between($file_name, '-', '.');
			$add = $number + 1;
			return str_replace($number, $add, $file_name);
		}
		else
		{
			return false;
		}
	}

	/**
	* Renames a video file by checking for existing files
	* @param: {integer} { $videoid } { id of video for which to rename file }
	* @return: {integer} { Returns number added to subtitle file e.g ( 1923-1.vtt ) }
	*/

/*	function rename_file( $videoid )
	{
		$number = 1;
		$dir = BASEDIR.'/files/captions/';

		while ( file_exists($dir.$videoid.'-'.$number.'.vtt') ) 
		{
			$captions[$number] = $videoid.'-'.$number;
			$number++;
		}

		return $number;
	
	}*/

	function rename_file( $videoid )
	{
		global $db;
		$data = $db->select(tbl("video"),"last_sub_num", "videoid='$videoid'");
		$last_sub = $data[0]['last_sub_num'];
		return $last_sub + 1;
	}

	/**
	* Converts Bytes into Kilo Bytes
	* @param: {integer} { $bytes } { bytes to be converted }
	* @return: {integer} { Bytes converted into Kilo Bytes }
	*/

	function bytes_to_kb( $bytes )
	{
		$kb = $bytes / 1024;
		return $kb;
	}

	/**
	* Gets number of total subtitles ever uploaded using plugin
	* @param: None
	* @return: {integer} { Total number of subtitles uploaded }
	*/

	function total_subs_uploaded()
	{
		global $db;
		$results = $db->select(tbl("honey_capt_configs"),"total_subs", "enable_subs!='no'");
		$subs = $results[0]['total_subs'];
		return $subs;
	}

	/**
	* Gets number of total videos with subtitles
	* @param: None
	* @return: {integer} { Total number of videos with subtitles }
	*/

	function total_sub_vids()
	{
		global $db;
		$results = $db->select(tbl("video"),"title", "has_subs='yes'");
		$subtitled_vids = count($results);
		return $subtitled_vids;
	}

	/**
	* Checks if a subtitle with given language already exists in database 
	* @param: {integer} { $videoid } { id of video for which to check file }
	* @param: {string} { $lang } { language to check file against }
	* @return: {string} { Language name if it exists }
	*/

	function same_lang_sub( $videoid, $lang )
	{
		global $db;
		$results = $db->select(tbl("honey_capt_subs"),"file_language", "videoid='$videoid' AND file_language='$lang'");
		$language = $results[0]['file_language'];
		if ( $lang == $language )
		{
			return $lang;
		}
		else
		{
			return false;
		}
	}

	/**
	* Uploads subtitle file in all forms ( Admin Area Edit, User Video Edit, Upload Video )
	* @param: {string} { $updir } { false by default but if true file will be uploaded in this directory }
	* @return: {string} { Name of subtitle file if it uploads with success }
	*/

	function upload_subtitle( $uploaddir = false )
	{
		global $userquery;

		if ( !$updir )
		{
			$basic_updir = get_me_dir();
			$uploaddir = BASEDIR.'/files/captions/'.$basic_updir.'/';
			#exit($updir);
		}


		if (isset($_FILES['file'])) {
			$captFile = $_FILES['file']['name'];
			$captSize = $_FILES['file']['size'];
			$captTemp = $_FILES['file']['tmp_name'];
		} else {
			$captFile = $_FILES['captions']['name'];
			$captSize = $_FILES['captions']['size'];
			$captTemp = $_FILES['captions']['tmp_name'];
		}

		if ( !empty($captFile) )
		{
			$lang = strtolower(mysql_clean($_POST['subtitle_lang']));
			$file = basename($captFile);
			$file_size = bytes_to_kb( $captSize );
			$uploadfile = $uploaddir . basename($captFile);
			
			$videoid = $_POST['videoid'];

			if ( empty($videoid) )
			{
				$videoid = $_GET['vid'];

				if ( empty($videoid) )
				{
					e("Failed to determine video id. Uploading of Subtitle file canceled!","e");
					return false;
				}
			}

			$ext = is_caption($file);
			$total_subs = get_total_subs( $videoid );
			$video_duration = get_video_dur( $videoid );

			if ( !$ext )
			{	
				e("Only <a href=https://w3c.github.io/webvtt/>WebVTT</a> files are supported!","e");
				return false;
			}
			elseif ( empty($lang) )
			{
				e("You must fill Language Field for subtitle file","e");
				return false;
			}
			elseif ( strlen($lang) > 20 )
			{
				e("How can language name possibly be longer than <strong>25</strong> characters?? Kindly given valid language name","e");
				return false;
			}
			elseif ( preg_match('/[\'^£$%&*1234567890}{@#~?><>,|=_+¬-]/', $lang) )
			{
				e("Language name is invlaid because it contains ilegal characters","e");
				return false;
			}
			elseif ( MAX_SUB_FILES > 0 && $total_subs == MAX_SUB_FILES )
			{
				e("Unable to upload subtitle because <strong>maximum subtitle files limit</strong> per video <strong>(".MAX_SUB_FILES.")</strong> has been reached","e");
				return false;
			}
			elseif ( MIN_VID_LEN > 0 && $video_duration < MIN_VID_LEN )
			{
				e("Unable to upload subtitle because <strong>video length</strong> is shorter than <strong>minimum required length (".MIN_VID_LEN." seconds)</strong> to add subtitle","e");
				return false;
			}
			elseif ( MAX_SUB_FILE_SIZE > 0 && $file_size > MAX_SUB_FILE_SIZE )
			{
				e("Unable to upload subtitle because maximum file size for subtitle file <strong>(".MAX_SUB_FILE_SIZE." KB)</strong> is less than your file <strong>(".round($file_size, 2)." KB)</strong>","e");
				return false;
			}
			elseif ( same_lang_sub( $videoid, $lang ) )
			{
				e("A subtitle file in <strong>".ucfirst($lang)."</strong> language is already available for this video. If you are trying to add subtitle in certain accent like English US or English UK, kindly use <strong>English(us)</strong> or <strong>English(uk)</strong> in language field","e");
				return false;
			}

			$renamed = $uploaddir.$videoid.'-'.$lang.'.'.$ext;
			#exit($renamed);

			if ( file_exists($renamed) )
			{
				/*$new_name = rename_file($videoid);
				$renamed = $uploaddir.$videoid.'-'.$new_name.'.'.$ext;*/
				e("A subtitle file in <strong>".ucfirst($lang)."</strong> language is already available for this video. If you are trying to add subtitle in certain accent like English US or English UK, kindly use <strong>English(us)</strong> or <strong>English(uk)</strong> in language field","e");
				return false;
			}

			$user = $userquery->userid;
			if (move_uploaded_file($captTemp, $uploadfile)) 
			{
				$newname = rename($uploadfile, $renamed);
				$get_file_name = substr($renamed, strrpos($renamed, '/') + 1);
				caption_db_operation( $videoid, $user, $get_file_name, $basic_updir, $lang );
				e("Subtitle file <strong>".$file."</strong> has been uploaded and has been renamed <strong>".$get_file_name."</strong>","m");
				return $get_file_name;
			} 
			else 
			{
			    e("Something went wrong trying to upload your subtitle. Kindly try again.");
			    return false;
			}
		}
	}

	/**
	* Inserts a caption into database ( commony used by caption_db_operation() function )
	* @param: {integer} { $videoid } { id of video for which subtitle is uploaded }
	* @param: {integer} { $userid } { id of user that uploading subtitle file}
	* @param: {string} { $file_name } { name of file that is to be uploaded }
	* @param: {string} { $file_path } { full directory path of file }
	* @param: {string} { $lang } { language of subtitle file to be uploaded }
	* @return: {string} { Name of file just uploaded }
	*/

	function insert_caption( $videoid, $userid, $file_name, $file_path, $lang )
	{
		global $db;
		$db->Execute("INSERT INTO  ".tbl('honey_capt_subs')." (`videoid`,`userid`,`file_name`, `file_language`, `file_path`) VALUES ($videoid, $userid, '$file_name', '$lang', '$file_path')");
		return $file_name;
	}

	/**
	* Gets total subtitles for given videoid
	* @param: {integer} { $videoid } { id of video for which to fetch subtitles }
	* @return: {integer} { Number of total subtitles for that video }
	*/

	function get_total_subs( $videoid )
	{
		global $db;
		$results = $db->select(tbl("video"),"total_subs", "videoid='$videoid'");
		$subs = $results[0]['total_subs'];
		return $subs;
	}

	/**
	* Used when there is only one subtitle file for video and makes that one file
	* default subtitle file
	* @param: {integer} { $videoid } { id of video for which to set default subtitle }
	*/

	function make_default_sub( $videoid )
	{
		global $db;
		$db->update(tbl("honey_capt_subs"), array("default_sub"), array("yes"), "videoid='$videoid'");
	}

	/**
	* Handles all Database operations required for insertion of subtitle
	* @param: {integer} { $videoid } { id of video for which subtitle is uploaded }
	* @param: {integer} { $userid } { id of user that uploading subtitle file}
	* @param: {string} { $file_name } { name of file that is to be uploaded }
	* @param: {string} { $file_path } { full directory path of file }
	* @param: {string} { $lang } { language of subtitle file to be uploaded }
	* @return: {string} { Name of file just uploaded }
	*/

	function caption_db_operation( $videoid, $userid, $file_name, $file_path, $lang )
	{
		global $db;
		$total_subs = get_total_subs($videoid);
		$total_overall_subs = total_overall_subs();

		if(!$total_overall_subs)
		{
			$total_overall_subs = 0;
		}
		
		$incr_subs = $total_subs + 1;
		$incr_overall_subs = $total_overall_subs + 1;
		$last_sub = rename_file( $videoid );
		insert_caption( $videoid, $userid, $file_name, $file_path, $lang );

		$db->update(tbl("video"), array("has_subs","total_subs","last_sub_num"), array("yes",$incr_subs,$last_sub), "videoid='$videoid'");

		$db->update(tbl("honey_capt_configs"), array("total_subs"), array($incr_overall_subs), "enable_subs!='no'");

		$total_subs = has_single_sub( $videoid );

		if ( $total_subs )
		{
			make_default_sub( $videoid );
		}

		return $file_name;
	}

	/**
	* Gets all subtitles for given videoid
	* @param: {integer} { $videoid } { id of video for which to search subtitles }
	* @return: {array} { An array with all subtitles of that video }
	*/

	function get_all_subs( $videoid )
	{
		global $db;

		if ( !$videoid )
		{
			$videoid = $_GET['video'];
		}

		$results = $db->select(tbl("honey_capt_subs"),"*","videoid='$videoid'");
		return $results;
	}

	/**
	* Checks if given video has any subtitles using videoid 
	* @param: {integer} { $videoid } { video id to check subtitles against }
	* @return: {boolean} { Yes or No depending either video has subtitles or not }
	*/

	function has_subs( $videoid )
	{
		global $db;
		$results = $db->select(tbl("video"),"has_subs","videoid='$videoid'");
		$subs = $results[0]['has_subs'];
		if ( $subs == 'yes' )
		{
			return $subs;
		}
		else
		{
			return false;
		}
	}

	function total_overall_subs()
	{
		global $db;
		$results = $db->select(tbl("honey_capt_configs"),"total_subs", "total_subs!=''");
		$subs = $results[0]['total_subs'];

		
		return $subs;
	}

	/**
	* Decrements subtitles count by 1 ( called when deleting subtitle )
	* @param: {integer} { $videoid } { video id for which to decrease subtitles count }
	*/

	function subs_decrement( $videoid )
	{
		global $db;
		$total_subs = get_total_subs($videoid);
		$total_overall_subs = total_overall_subs();
		$decr_subs = $total_subs - 1;
		$decr_overall_subs = $total_overall_subs - 1;
		$db->update(tbl("video"), array("total_subs"), array($decr_subs), "videoid='$videoid'");
		$db->update(tbl("honey_capt_configs"), array("total_subs"), array($decr_overall_subs), "enable_subs!='no'");

		if ( $total_subs == 1) 
		{
			if ( has_subs( $videoid ) )
			{
				$db->update(tbl("video"), array("has_subs"), array("no"), "videoid='$videoid'");
			}
		}
	}

	/**
	* Checks if given video has only 1 subtitle or not using videoid
	* @param: {integer} { $videoid } { videoid to check for subtitles }
	* @return: {integer} { 1 if video has single subtitle }
	*/

	function has_single_sub( $videoid )
	{
		global $db;
		$results = $db->select(tbl("video"),"total_subs","videoid='$videoid'");
		$subs = $results[0]['total_subs'];
		if ( $subs == 1 )
		{
			return $subs;
		}
		else
		{
			return false;
		}
	}

	/**
	* Checks if given subtitle is default subtitle of video
	* @param: {string} { $file_name } { name of subtitle to check }
	* @return: {boolean} { Yes or False depending on situation }
	*/

	function is_default_sub( $subid )
	{
		global $db;
		$results = $db->select(tbl("honey_capt_subs"),"default_sub","subid='$subid'");
		$sub  = $results[0]['default_sub'];
		
		if ( $sub == 'yes' )
		{
			return $sub;
		}
		else
		{
			return false;
		}
	}

	function get_sub_path( $subfile_name )
	{
		global $db;
		$results = $db->select(tbl("honey_capt_subs"),"file_path","file_name='$subfile_name'");
		$sub_path = $results[0];
		return $sub_path;
	}

	function get_subname( $subid )
	{
		global $db;
		$results = $db->select(tbl("honey_capt_subs"),"file_name,file_path","subid='$subid'");
		$sub = $results[0];
		return $sub;
	}

	/**
	* Deletes a subtitle file after certain checks
	* @param: {string} { $sub_file_path } { false by default but if true it will delete sub from that DIR }
	* @return: {boolean} { Returns TRUE if file is deleted and FALSE if not }
	*/

	function delete_sub( $subid )
	{
		global $db;

		$data = get_subname( $subid );
		$file_name = $data['file_name'];
		$file_path = $data['file_path'];
		$videoid = $_GET['video'];


		if ( empty($videoid) )
		{
			$videoid = $_GET['vid'];
		}

		if ( empty($file_name) )
		{
			return false;
		}

		$sub_file_path = BASEDIR.'/files/captions/'.$file_path.'/'.$file_name;
		$total_subs = get_total_subs($videoid);

		if ( is_default_sub( $subid ) && $total_subs > 1 )
		{
			e("You cannot delete default Subtitle file. If you must, kindly make some other file default first");
			return false;
		}

		if ( file_exists( $sub_file_path ) )
		{
			unlink( $sub_file_path );

			if ( !file_exists( $sub_file_path ) )
			{
				$db->delete(tbl('honey_capt_subs'),array("file_name"),array($file_name));
				subs_decrement( $videoid );
				e("Subtitle file <strong>(".$file_name.")</strong> has been deleted!","m");
			}
			else 
			{
				e("Unable to delete <strong>(".$file_name.")</strong>. Make sure file is writable","e");
			}
			
			return true;
		}
		else 
		{
			e("Subtitle file that you are trying to delete <strong>(".$file_name.")</strong> does not exit!","e");

			return false;
		}
	}

	/**
	* Plays a subtitle file in VideoJS player
	* @param: {integer} { $videoid } { id of video for which to play subtitle }
	* @return: {string} { HTML5 track element with all required parameters }
	*/

	function play_def_sub( $videoid )
	{
		global $db;
		if ( !$videoid )
		{
			global $cbvid;
			$vkey = @$_GET['v'];
			if (empty($vkey)) {
				$vkey = $_GET['vid'];
			}
			$vkey = mysql_clean($vkey);
			$vdo = $cbvid->get_video($vkey);
			$videoid = $vdo['videoid'];
		}
		
		if ( has_subs( $videoid ) )
		{
			$results = $db->select(tbl("honey_capt_subs"),"file_name, file_language, file_path","videoid='$videoid' AND default_sub='yes'");
			$data = $results[0];
			$file_name = $data['file_name'];
			$file_dir = $data['file_path'];
			$file_lang = ucfirst($data['file_language']);

			if ( empty($file_lang) )
			{
				$file_lang = 'English';
			}

			$lang = strtolower(substr($file_lang, 0, 2));
			$build_src = BASEURL.'/files/captions/'.$file_dir.'/'.$file_name;
			$src_for_vidjs = '<track kind="captions" src="'.$build_src.'" srclang="'.$lang.'" label="'.$file_lang.'" default>';
			echo $src_for_vidjs;

			if ( !has_single_sub( $videoid ) )
			{
				$normal_subs = $db->select(tbl("honey_capt_subs"),"file_name, file_language, file_path","videoid='$videoid' AND default_sub!='yes'");
				
				foreach ( $normal_subs as $all_subs ) 
				{
					$file_dir = $all_subs['file_path'];
					$file_name = $all_subs['file_name'];
					$file_lang = ucfirst($all_subs['file_language']);

					if ( empty($file_lang) )
					{
						$file_lang = 'English';
					}

					$lang = strtolower(substr($file_lang, 0, 2));
					
					$build_src = BASEURL.'/files/captions/'.$file_dir.'/'.$file_name;

					$src_for_vidjs = '<track kind="captions" src="'.$build_src.'" srclang="'.$lang.'" label="'.$file_lang.'">';
					echo $src_for_vidjs;
				}
			}
		}
		else
		{
			return false;
		}

		
	}

	/**
	* Makes a subtitle file DEFAULT file
	* @param: {string} { $sub_name } { false by default but if true it will make that file default }
	* @param: {integer} { $videoid } { false by default but if true it will make default for that video }
	* @return: {string} { Message depending on situation }
	*/

	function make_def_sub( $subid = false, $videoid = false )
	{
		global $db;

		if ( !$subid )
		{
			$subid = $_GET['defualt_sub'];
		}

		if ( !$videoid )
		{
			$videoid = $_GET['video'];

			if ( empty($videoid) )
			{
				$videoid = $_GET['vid'];

				if ( empty($videoid) )
				{
					return false;
				}
			}
		}

		if ( empty($subid) )
		{
				return false;
		}
		else
		{
			$db->update(tbl("honey_capt_subs"), array("default_sub"), array("no"), "videoid='$videoid' AND default_sub='yes'");
			$db->update(tbl("honey_capt_subs"), array("default_sub"), array("yes"), "subid='$subid'");
			$results = $db->select(tbl("honey_capt_subs"),"default_sub","subid='$subid'");
			$sub = $results[0]['default_sub'];

			if ( $sub == 'yes' )
			{
				e("Subtitle file ".$subid." has been set as default subtitle file for this video","m");
			}
			else 
			{
				e("Something went wrong trying to set ".$subid." as default subtitle file for this video","e");
			}
		}
	}

	function get_me_dir()
	{
		$main_dir = BASEDIR.'/files/captions';
		$year = date("Y");

		$build_year_dir = $main_dir.'/'.$year;

		$month = date("m");

		$build_month_dir = $main_dir.'/'.$year.'/'.$month;

		$day = date("d");

		$build_day_dir = $main_dir.'/'.$year.'/'.$month.'/'.$day;

		if ( !file_exists( $build_year_dir ) )
		{
			mkdir($build_year_dir);
		}
		elseif ( !file_exists( $build_month_dir ) )
		{
			mkdir( $build_month_dir );
		}
		elseif ( !file_exists( $build_day_dir ) )
		{
			mkdir( $build_day_dir );
		}
		
		$build_dir = $year.'/'.$month.'/'.$day;
		
		return $build_dir;
	}

	/**
	* Register anchor for calling PHP functions inside HTML (smarty) files
	* @param : None
	*/

	function fire_honey_anchors() 
	{
		# Anchor for Substitle upload form on edit video
		register_anchor_function("edit_video_form","edit_video_form");

		# Anchor for Substitle upload form on upload video
		register_anchor_function("upload_video_form","upload_video_form");

		register_anchor_function("make_def_sub", "make_def_sub");
		register_anchor_function("play_def_sub", "play_def_sub");
		register_anchor_function("upload_subtitle", "upload_subtitle");

	}

	/**
	* Creates admin area menus for plugin 
	* @param: None
	*/

	function fire_honey_admin_menus() 
	{
		
		add_admin_menu('CB Subtitles','Configurations','honey_configs.php', HONEY_CAPT_BASE.'/admin');
		
	}

	/**
	* Creates Lisc menu for plugin
	* @param: None
	*/

	function fire_honey_lisc_menu()
	{
		add_admin_menu('CB Subtitles','Plugin Liscence','lisc_update.php', HONEY_CAPT_BASE.'/admin');
	}


	/**
	* Assigns configuration values so they can be used in Smarty
	* @param: {array} { $configs } { an array with all configurations of plugin }
	*/

	function the_config_assign( $configs )
	{
		$enable_subs = $configs['enable_subs'];
		$allowed_users = $configs['allowed_users'];
		$max_files = $configs['max_sub_files'];
		$max_sub_file_size = $configs['max_sub_file_size'];
		$vid_len = $configs['min_vid_len'];

		assign( "enable_subs", $enable_subs );
		assign( "allowed_users", $allowed_users );
		assign( "max_files", $max_files );
		assign( "max_sub_size", $max_sub_file_size );
		assign( "vid_len", $vid_len );

	}

?>