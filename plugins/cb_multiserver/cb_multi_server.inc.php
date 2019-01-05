<?php

if(!function_exists("cb_multiserver"))
{
	
//	include(PLUG_DIR.'/cb_multi_server/classes/ftp.class.php');
	
	$cb_multiserver =  basename(dirname(__FILE__));
	define("CB_MULTISERVER",$cb_multiserver);
	include(PLUG_DIR.'/'.$cb_multiserver.'/classes/cb_multiserver.class.php');
	include(PLUG_DIR.'/'.$cb_multiserver.'/classes/ftp.class.php');
	
	define('CB_MS_URL',PLUG_URL.'/'.$cb_multiserver);
	define('CB_MS_DIR',PLUG_DIR.'/'.$cb_multiserver);

	assign('cb_multiserver_url',PLUG_URL.'/'.$cb_multiserver);
	assign('cb_multiserver_dir',PLUG_DIR.'/'.$cb_multiserver);

	$multi_server = new cb_multiserver();
	
	function getUploadifySwf()
	{
		global $multi_server,$Cbucket;;
		
		$server_api = false;
		$server_api = $multi_server->get_server_api(false,'upload');
		if($server_api)
		{			
			$uploaderDetails = array
			(
				'uploadSwfPath' => JS_URL.'/uploadify/uploadify.swf',
				'uploadScriptPath' => $server_api['server_api_path'].'/actions/file_uploader.php',
				'uploadRemoteResult' => $server_api['server_api_path'].'/actions/file_results.php',
				'uploadServerPath' => $server_api['server_api_path'],
			);
			$Cbucket->theUploaderDetails = $uploaderDetails;
			assign('uploaderDetails',$uploaderDetails);		
		}
		
		$server_api = false;
		$server_api = $multi_server->get_server_api(false,'photo_upload');
		
		if($server_api)
		{
			$photoUploaderDetails = array
			(
				'uploadSwfPath' => JS_URL.'/uploadify/uploadify.swf',
				'uploadScriptPath' => $server_api['server_api_path'].'/actions/photo_uploader.php',
				'uploadServerPath' => $server_api['server_api_path'],
			);
			assign('photoUploaderDetails',$photoUploaderDetails);	
		}

	}
	
	
	function serverDomain($in)
	{
		$in = preg_replace('/http\:\/\//','',$in);
		$in = explode('/',$in);
		$in = $in[0];
		echo $in;
	}
	
	function serverStatus($in)
	{
		if($in=='yes')
			echo '<span style="color:#336600; font-weight:bold">Active</span>';
		else
			echo '<span style="color:#ed0000; font-weight:bold">Inactive</span>';
	}
	
	function serverUsed($params)
	{
		$in = $params['server'];
		
		if(!@$params['ivert'])
		{
			if($in['used']<1)
				echo '0%';
			else
				echo (@$in['used']/1024/1024) / $in['max_usage'] * 100 .'%';
		}else
		{
			if($in['used']<1)
				$perc = 0;
			else
				$perc = (@$in['used']/1024/1024) / $in['max_usage'] * 100;
			
			echo 100-$perc.'%';
		}
	}
	
	function getDomainName()
	{
		$name = $_SERVER['HTTP_HOST'] ;
		return preg_replace('/wwww\./','',$name);
	}
	
	function getAppKey()
	{
		$domain = getDomainName();
		$md5 = md5($domain);
		return $key = base64_encode($domain.$md5);
	}
	
	function getServers($params)
	{
		global $multi_server;
		return $multi_server->get_servers($params);
	}
	
	/**
	 * function used to count number of videos
	 * on server
	 */
	function count_server_videos($in)
	{
		global $db;
		$result = $db->count(tbl("video"),"videoid"," file_server_path ='{$in}/files' ");
		return $result;
	}
	
	assign('appKey',getAppKey());
	assign('domainName',getDomainName());
	assign('queryString',queryString(false,array('id','action')));
	
	cb_register_function('getUploadifySwf','uploaderDetails');
	
	$Smarty->assign_by_ref('multi_server',$multi_server);
	$Smarty->register_function('serverUsed','serverUsed');
	$Smarty->register_function('getServers','getServers');
	
	//Adding Menu in administration panel
	add_admin_menu("Multi Servers","Configuration",'configure.php',$cb_multiserver.'/admin');
	add_admin_menu("Multi Servers","Manage Servers",'multi_servers.php',$cb_multiserver.'/admin');
	//add_admin_menu("Multi Servers","Mass Upload",'mass_upload.php',$cb_multiserver.'/admin');
	
	
	add_admin_header(PLUG_DIR.'/'.$cb_multiserver.'/admin/header.html');
	
	define('MULTISERVER_LICENSE',$multi_server->configs['license_key']);
	define('MULTISERVER_LOCAL_LICENSE',$multi_server->configs['license_key_local']);
	function check_multiserver_license($license,$localkey)
	{
		$results = multiserver_license($license,$localkey);
		
		$error_setting_link = '<a href="'.BASEURL.'/admin_area/plugin.php?folder='.CB_MULTISERVER.'/admin&file=configure.php">Click Here to edit Multiserver Settings</a>';
		if(!$results)
		{
			if(BACK_END)
				e("Error while loading Multiserver license - $error_setting_link","w");
		}elseif ($results["status"]=="Invalid")
		{
			if(BACK_END)
				e("Your Multiserver License is Invalid - $error_setting_link","w");
		}elseif ($results["status"]=="Expired")
		{
			if(BACK_END)
				e("Your Multiserver License is Expired - $error_setting_link","w");
		}elseif($results["status"]=="Suspended")
		{
			if(BACK_END)
				e("Your Multiserver is suspended - $error_setting_link","w");
		}elseif($results['status']!='Active')
		{
			if(BACK_END)
				e("Error occured while checking license , status : ".$results['status']." - $error_setting_link","w");
		}
		return $results;
	}
	
	function multiserver_license($licensekey,$localkey="")
	{
		
		if(file_exists(__DIR__.'/_NO_LICENSE_DEVELOPMENT_'))
		{
			e("You are running MS in development mode","i");
			return array('status' => 'Active');
		}

		$whmcsurl = "http://client.clip-bucket.com/";
		$prefix = "CBMS";
		$licensing_secret_key = "CBMS"; # Set to unique value of chars
		$checkdate = date("Ymd"); # Current dateW
		$usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
		$localkeydays = 15; # How long the local key is valid for in between remote checks
		$allowcheckfaildays = 5; # How many days to allow after local key expiry
		$localkeyvalid = false;
		
		$prefix_len = strlen($prefix);
		
		if(substr($licensekey,0,$prefix_len)!=$prefix)
		{
			return array('status'=>'Unknown license');
		}
		if ($localkey) {
			$localkey = str_replace("\n",'',$localkey); # Remove the line breaks
			$localdata = substr($localkey,0,strlen($localkey)-32); # Extract License Data
			$md5hash = substr($localkey,strlen($localkey)-32); # Extract MD5 Hash
			if ($md5hash==md5($localdata.$licensing_secret_key)) {
				$localdata = strrev($localdata); # Reverse the string
				$md5hash = substr($localdata,0,32); # Extract MD5 Hash
				$localdata = substr($localdata,32); # Extract License Data
				$localdata = base64_decode($localdata);
				$localkeyresults = unserialize($localdata);
				$originalcheckdate = $localkeyresults["checkdate"];
				if ($md5hash==md5($originalcheckdate.$licensing_secret_key)) {
					$localexpiry = date("Ymd",mktime(0,0,0,date("m"),date("d")-$localkeydays,date("Y")));
					if ($originalcheckdate>$localexpiry) {
						$localkeyvalid = true;
						$results = $localkeyresults;
						$validdomains = explode(",",$results["validdomain"]);
						if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
							$localkeyvalid = false;
							$localkeyresults["status"] = "Invalid";
							$results = array();
						}
						$validips = explode(",",$results["validip"]);
						if (!in_array($usersip, $validips)) {
							$localkeyvalid = false;
							$localkeyresults["status"] = "Invalid";
							$results = array();
						}
						if ($results["validdirectory"]!=dirname(__FILE__)) {
							$localkeyvalid = false;
							$localkeyresults["status"] = "Invalid";
							$results = array();
						}
					}
				}
			}
		}
		if (!$localkeyvalid) {

			$postfields["licensekey"] = $licensekey;
			$postfields["domain"] = $_SERVER['SERVER_NAME'];
			$postfields["ip"] = $usersip;
			$postfields["dir"] = dirname(__FILE__);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $whmcsurl."modules/servers/licensing/verify.php");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$data = curl_exec($ch);
			curl_close($ch);
			if (!$data) {
				$localexpiry = date("Ymd",mktime(0,0,0,date("m"),date("d")-($localkeydays+$allowcheckfaildays),date("Y")));
				if ($originalcheckdate>$localexpiry) {
					$results = $localkeyresults;
				} else {
					$results["status"] = "Remote Check Failed";
					return $results;
				}
			} else {
				preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
				$results = array();
				foreach ($matches[1] AS $k=>$v) {
					$results[$v] = $matches[2][$k];
				}
			}
			if ($results["status"]=="Active") {
				$results["checkdate"] = $checkdate;
				$data_encoded = serialize($results);
				$data_encoded = base64_encode($data_encoded);
				$data_encoded = md5($checkdate.$licensing_secret_key).$data_encoded;
				$data_encoded = strrev($data_encoded);
				$data_encoded = $data_encoded.md5($data_encoded.$licensing_secret_key);
				$data_encoded = wordwrap($data_encoded,80,"\n",true);
				$results["localkey"] = $data_encoded;
				global $db;
				$db->update(tbl("server_configs"),array("value"),array($results["localkey"]),"config_id=2") ;
			}
			$results["remotecheck"] = true;
		}
		unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$localkeydays,$allowcheckfaildays,$md5hash);
		return $results;
	}
	


    /**
    * Inteligently encodes last checked data
    * @param : { string } { $status } { Current status of plugin }
    * @since : 24th October, 2016 ClipBucket 2.8.1
    * @author : Saqib Razzaq
    */
    
    function messUpLastChecked_ms($status) {
    	$dateStamp = dateStamp();
    	$alphabets_swaped = swapedAlphabets();

    	$status_clean = strtolower($status);
    	$status_array = str_split($status_clean);
    	$status_numeric = '';

    	$num_array = str_split($dateStamp);
    	$mixedTimeArray = '';

    	foreach ($status_array as $key => $char) {
    		$newNum = $alphabets_swaped[$char] + 1;
    		$status_numeric .= "__".$newNum;
    	}

    	foreach ($num_array as $intKey => $numNow) {
    		$mixedTimeArray .= $numNow.''.charsRandomStr();
    	}

    	$toReturn = array();
    	$toReturn['status'] = $status_numeric;
    	$toReturn['lastChecked'] = $mixedTimeArray;

    	return $toReturn;
    }

    /**
    * Inteligently decodes last checked data fetched by above function
    * @param : { string } { $status } { Current status of plugin }
    * @param : { string } { $status } { Last checked encoded string }
    * @since : 24th October, 2016 ClipBucket 2.8.1
    * @author : Saqib Razzaq
    */

    function cleanUpLastChecked_ms($status, $lastChecked) {
    	$alphabets = range('a', 'z');
    	$statusArray = explode('__', $status);
    	$statusArray = array_filter($statusArray);
    	$statusCleaned = '';
    	$lastCheckedCleaned = '';
    	foreach ($statusArray as $key => $charNow) {
    		$charNow = $charNow - 1;
    		$statusCleaned .= $alphabets[$charNow];
    	}

    	$lastCheckedCleaned = preg_replace("/[^0-9,.]/", "", $lastChecked);

    	$toReturn = array();
    	$toReturn['status]'] = $statusCleaned;
    	$toReturn['date'] = date('m/d/Y', $lastCheckedCleaned);
    	$toReturn['lastCheckedStamp'] = $lastCheckedCleaned;

    	return $toReturn;
    }

    /**
    * Runs a lisc check only if last check was 7 or more days ago
    * @param : { string } { $status } { Current status of plugin }
    * @param : { string } { $status } { Last checked encoded string }
    * @since : 24th October, 2016 ClipBucket 2.8.1
    * @author : Saqib Razzaq
    */

    function liscCheckLatest_ms() {

    	$lisc_key = $multi_server->configs['license_key'];
    	$local_key = $multi_server->configs['license_local_key'];
    	$result = check_multiserver_license(MULTISERVER_LICENSE,MULTISERVER_LOCAL_LICENSE);
    	assign('license_key',$license_configs['license_key']);
    	if ($result["status"] == 'Active') {
    		$data = messUpLastChecked_ms('Active');
    		file_put_contents($file, json_encode($data));
    		return $data;
    	}

    	$lisc_key = $multi_server->configs['license_key'];
    	$local_key = $multi_server->configs['license_local_key'];
        // IP of last success (acitve status) check
    	$success_ip = $multi_server->configs['success_ip'];

        // current IP address of server
    	$current_ip = $_SERVER['SERVER_ADDR'];

    	if ((int) trim($success_ip) == (int) trim($current_ip)) {
    		return array('status'=>'Active');
    	} else {
    		$result = check_honeycapt_license($lisc_key,$local_key);
    		if ($result["status"] == 'Active') {
    			global $db;
    			$db->update(tbl('server_configs'),array('value'),array($current_ip),"name ='success_ip'");
    			return $result;
    		}
    	}
    }

	//Checking for license
    $results = liscCheckLatest_ms();
	#$results['status'] = 'Active';
    if($results['status'] != 'Active')
    {	
    	cb_register_function('getUploadifySwf','uploaderDetails');

    	if($results['localkey'])
    		$db->update(tbl('server_configs'),array("value"),array($results['localkey'])," name='license_key_local' ");

		/**
		 * Function used to delete file from server
		 */
		function delete_server_file($video)
		{
			
			if($video['server_ip'])
			{
				
				global $db,$multi_server;
				
				$server = $multi_server->get_path_server(dirname($video['file_server_path']));
				$videohai = 'haan';
				while(1)
				{
					if(!$server)
						break;
					else
					{	
						// exit(pr($video,true));
						//exit('delete file check else');
						$api = $server['server_api_path'];
						$delete_file = $api.'/actions/delete_file.php';
						//Send a request to delete the file
						$ch = curl_init($delete_file);
						curl_setopt($ch,CURLOPT_POST,true);
						curl_setopt($ch,CURLOPT_POSTFIELDS,
							array("secret_key"=>$server['secret_key'],
								"file_name"=>$video['file_name'],
								"file_type"=>$video['file_type'],
								'application_key'=>getAppKey(),
								'folder'=>$video['file_directory']));
						curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
						curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
						$returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						$result = curl_exec($ch);
						curl_close($ch);
						

						if($result)
							e("Unable to authenticate to delete file from the server");
						else
							e("File has been delete from the server","m");
						
						//Minus File details
						$query = "UPDATE ".tbl("servers")." SET 
						used = used-'".$video['filegrp_size']."'
						WHERE  server_ip ='".$video['server_ip']."'";
						mysqli_query($db,$query);
						
						if($videohai=='haan' && $video['file_server_path'] != dirname($video['files_thumbs_path']))
						{
							$server = $multi_server->get_path_server(dirname(dirname($video['files_thumbs_path'])));
							$videohai = 'nahin';
						}else
						$server = false;
					}
				}
			}
		}
		
		
		/**
		 * Get Server PHOTO
		 */
		function getServerPhoto($in)
		{
			global $cbphoto;
			$p = $in;
			$details = $p['details'];
			if(!$details['server_url'] || $details['server_url']=='undefined')
				return false;
			if(($p['size'] != 't' && $p['size'] != 'm' && $p['size'] != 'l' && $p['size'] != 'o') || empty($p['size']))
				$p['size'] = 't';   
			$with_path = $p['with_path'] ? $p['with_path'] : TRUE;
			$photo = $details;
			
			$folder = NULL;
			if($photo['file_directory'])
				$folder = $photo['file_directory'];
			if(empty($photo['photo_id']) || empty($photo['photo_key']))
				return $cbphoto->default_thumb($p['size']);
			else
			{
				
				if(!empty($photo['filename']) && !empty($photo['ext']))
				{
					$files = 
					array(
						$photo['filename'].'.'.$photo['ext'],
						$photo['filename'].'_l.'.$photo['ext'],
						$photo['filename'].'_o.'.$photo['ext'],
						$photo['filename'].'_t.'.$photo['ext'],
						$photo['filename'].'_m.'.$photo['ext']
					);
					if(!empty($files) && is_array($files))
					{
						foreach($files as $file)
						{
							if($with_path)
								$thumbs[] = $details['server_url']."/".$folder.$file;
							else
								$thumbs[] = $file;	
						}
						
						if(empty($p['output']) || $p['output'] == 'non_html')
						{
							if($p['assign'] && $p['multi'])
							{
								assign($p['assign'],$thumbs);
							} elseif(!$p['assign'] && $p['multi']) {
								return $thumbs;	
							} else {
								
								$size = "_".$p['size'];
								
								$return_thumb = array_find($photo['filename'].$size,$thumbs);
								if(empty($return_thumb))
								{
									$cbphoto->default_thumb($size);
								} else {
									if($p['assign'] != NULL)
										assign($p['assign'],$return_thumb);
									else
										return $return_thumb;
								}
							}
						}
						
						if($p['output'] == 'html')
						{
							
							$size = "_".$p['size'];
							
							$src = array_find($photo['filename'].$size,$thumbs);
							if(empty($src))
								$src = $cbphoto->default_thumb($size);
							else
								$src = $src;	
							$dem = getimagesize($src);
							$width = $dem[0];
							$height = $dem[1];
							
							$img = "<img ";
							$img .= "src = '".$src."'";
							
							if($p['id'])
								$img .= " id = '".mysql_clean($p['id'])."_".$photo['photo_id']."'";

							if($p['class'])
								$img .= " class = '".mysql_clean($p['class'])."'";

							if($p['align'])
								$img .= " align = '".$p['align']."'";	
							if($p['width'] && is_numeric($p['width']))
							{
								$height = round($p['width'] / $width * $height);
								$width = $p['width'];
							}
							
							$img .= " width = '".$width."'";
							$img .= " height = '".$height."'";
							
							if($p['title'])
								$img .= " title = '".mysql_clean($p['title'])."'";
							else
								$img .= " title = '".$photo['photo_title']."'";

							if($p['alt'])
								$img .= " alt = '".mysql_clean($p['alt'])."'";
							else
								$img .= " alt = '".$photo['photo_title']."'";

							if($p['anchor'])
							{
								$anchor_p = array("place"=>$p['anchor'],"data"=>$photo);
								ANCHOR($anchor_p);	
							}
							
							if($p['style'])
								$img .= " style = '".$p['style']."'";
							
							if($p['extra'])
								$img .= mysql_clean($p['extra']);

							$img .= " />";
							
							if($p['assign'])
								assign($p['assign'],$img);
							else	
								return $img;
						}
					} else {
						return $this->default_thumb($size);	
					}
				}
			}

		}
		
		/**
		 * Function used to get thumb
		 */
		function ms_server_thumb($video,$in)
		{
			
			
			$num = $in['num'];
			// pr($in,true);
			if(!$num || $num=='default')
				$num = $video['default_thumb'];
			$size = $in['size'];
			//if($num['count']) return $video['file_thumbs_count'];			
			if($video['files_thumbs_path'])
			{
				
				if(CB_SSL)
				{
					/*if(!strstr($video['files_thumbs_path'],'https'))
					{
						$video['files_thumbs_path'] = str_replace('http://', 'https://', $video['files_thumbs_path']);	
					}*/
				}
				if(!$in['multi'])
				{
					$whitelist = array('big','sprite');
					if(($video['file_thumbs_count'] - 1) < $num && !in_array($num,$whitelist))
						$default_thumb = 1;
					else
						$default_thumb = $num;
					
					if($in['count'])
						return $video['file_thumbs_count'];
					
					$folder = "";
					if($video['file_directory'])
						$folder = $video['file_directory'];


					
					if($folder)
						if(substr($folder, strlen($folder) - 1, 1) !='/') $folder = $folder.'/';


					if($num=='big' && $folder)
						return $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-big-'.$video['default_thumb'].'.jpg';	
					
					if($num=='big')
						return $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-big.jpg';
					
					if(($num=='160x120' || $num=='640x480') && $folder)
						return $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-'.$num.'-'.$video['default_thumb'].'.jpg';		
					
					return $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-'.$size.'-'.$default_thumb.'.jpg';		


				}else
				{
					$folder = "";
					// pr($video,true);
					if($video['file_directory'])
						$folder = $video['file_directory'];
					
					// pr(,true);
					$divide = $video['file_thumbs_count'];
					// if(strstr($divide, '.'))
					// {
					// 	$divide = 1;
					// }
					// else
					// {
					// 	$divide = 5;
					// }

					//Looop the loop
					if(!$folder)
						$total = $video['file_thumbs_count']-1;
					else
						$total = $video['file_thumbs_count'];
					
					
					if($folder)
					{
						if($total<1)
						{
							$total = 1;
						}
						for($i=1;$i<=$total;$i++)
						{
							$thumbs[] = $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-'.$i.'.jpg';
//							$thumbs[] = $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-160x120-'.$i.'.jpg';
//							$thumbs[] = $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-640x480-'.$i.'.jpg';
//							$thumbs[] = $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-original-'.$i.'.jpg';
//							$thumbs[] = $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-big-'.$i.'.jpg';
						}
						// pr($total,true);
					}else
					{
						for($i=1;$i<=$total;$i++)
						{
							$thumbs[] = $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-'.$i.'.jpg';
							$thumbs[] = $video['files_thumbs_path'].'/'.$folder.$video['file_name'].'-big.jpg';
						}
					}
					
					// pr($thumbs,true);
					return $thumbs;
				}
				
				return true;
			}
		}
		
		/**
		 * Function used to get video file
		 */
		
		function get_video_filess($video){

			if(!empty($video)){

				$extras_json = json_decode($video['extras'],true);
				$file_name = $video['file_name'];
				$file_server_path = $video['file_server_path']."/videos/";
				$file_dir = $video['file_directory'];

				$data = array();

				foreach($extras_json['video_files'] as $height)
				{	
					$data[$height] = $file_server_path.'/'.$file_dir.'/'.$file_name.'-'.$height.'.mp4';
				}

				return json_encode($data);

			}	

	}//end function

	function server_video($video,$hq=false)
	{
		if($video['file_server_path'])
		{
			$ext = 'mp4';
			if($hq)
			{
				if($video['has_hd'])
					$hd = '-hd';
				else
					$hd = '-hd';
				$ext = 'mp4';
			}


			$folder = "";
			if($video['file_directory'])
				$folder = $video['file_directory'].'/';

			if($folder)
				if(substr($folder, strlen($folder) - 1, 1) !='/') $folder = $folder.'/';




			if($video['file_type']==2) {

				$v_files = $video['file_server_path'].'/videos/'.$folder.$video['file_name'].'/'.$video['file_name'].'.m3u8';
				return $v_files;

			}
			elseif($video['file_type']==1){

				$v_files = $video['file_server_path'].'/videos/'.$folder.$video['file_name'].'/'.$video['file_name'].'.mpd';
				return $v_files;

			}
			else{

				if($video['version'] == 2 || $video['version'] == 1){

						//if hold different resulotions all in mp4 formate 
					$videos = json_decode($video['video_files'],true);
					$v_files = array();

					foreach ($videos as $v) {
						$v_files[$v] = $video['file_server_path'].'/videos/'.$folder.$video['file_name'].'-'.$v.'.'.$ext; 
					}

					return $v_files;

				}
				else
				{
					$ext = 'flv';
					return $video['file_server_path'].'/videos/'.$folder.$video['file_name'].$hd.'.'.$ext;
				}
			}

		}
	}

	function server_downloadable_videos($video,$hq=false)
	{
		if($video['file_server_path'])
		{
			$ext = 'mp4';
			if($hq)
			{
				if($video['has_hd'])
					$hd = '-hd';
				else
					$hd = '-hd';
				$ext = 'mp4';
			}


			$folder = "";
			if($video['file_directory'])
				$folder = $video['file_directory'].'/';

			if($folder)
				if(substr($folder, strlen($folder) - 1, 1) !='/') $folder = $folder.'/';




			if($video['version'] == 2 || $video['version'] == 1){

					//if hold different resulotions all in mp4 formate 
				$videos = json_decode($video['video_files'],true);
				$v_files = array();

				foreach ($videos as $v) {
					if($video['file_type']==2 || $video['file_type']==1){
						
						$v_files[$v] = $video['file_server_path'].'/videos/'.$folder.$video['file_name'].'/'.$video['file_name'].'-'.$v.'.'.$ext; 
					
					}
					else{

						$v_files[$v] = $video['file_server_path'].'/videos/'.$folder.$video['file_name'].'-'.$v.'.'.$ext;
					
					}
				}

				return $v_files;

			}
			else
			{
				$ext = 'flv';
				return $video['file_server_path'].'/videos/'.$folder.$video['file_name'].$hd.'.'.$ext;
			}

		}
	}

	function multi_has_hq($vdata)
	{
		if(has_hq($vdata))
			return true;

		if($vdata['has_hq']!='no' || $vdata['has_hd']!='no')
			return true;

		return false;
	}

	function ms_delete_photo($photo)
	{
		global $multi_server;
			//Checking if photo
		if($photo['server_url'] && $photo['server_url']!='undefined')
		{
			$server_url = dirname(dirname($photo['server_url']));

				//Getting server
			$server = $multi_server->get_server($server_url);

			if(!$server)
				return false;

			$post_fields = array();
			$post_fields['filename'] = $photo['filename'];
			$post_fields['folder'] = $photo['file_directory'];
			$post_fields['secret_key'] = $server['secret_key'];
			$post_fields['application_key'] = getAppKey();

			$ch = curl_init($server_url.'/actions/delete_file.php');
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);
			curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			$result = curl_exec($ch);
			curl_close($ch);			
		}
	}

		//register function to delete file from the server
	register_action_remove_video("delete_server_file");
	cb_register_function('ms_delete_photo','delete_photo');
	$Cbucket->custom_get_thumb_funcs[] = 'ms_server_thumb';
	$Cbucket->custom_video_file_funcs[] = 'server_video';
	$Cbucket->custom_downloadable_file_funcs[] = 'server_downloadable_videos';
	$Cbucket->custom_get_photo_funcs[] = 'getServerPhoto';

	$db->update(tbl('server_configs'),array('value'),
		array($results['localkey']),"name ='license_local_key'" );


}


	//Function Remote Upload JS FUNC
function ms_remote_upload_js()
{
	return 'ms_remote_upload()';
}

uploaderDetails();
cb_register_function('ms_remote_upload_js','remote_url_function');
cb_register_function('multi_has_hq','has_hq');
add_header(CB_MS_DIR.'/ms_header.html','global');
}

?>