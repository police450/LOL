<?php


/** 
 * This class handles multi server
 * fetch other servers stats and info
 * so clipbucket can operate them more properly
 */


 
class cb_multiserver
{
	
	var $configs = array();
	
	
	/**
	 * Function used to get multi server configurations
	 */
	function getConfigs()
	{
		
		global $db;
		$results = $db->select(tbl("server_configs"),"*");
		foreach($results as $result)
		{
			$this->configs[$result['name']]= $this->$result['name'] = $result['value'];
		}
		
		return $this->configs;
	}
	
	
	function cb_multiserver()
	{
		$this->getConfigs();
	}
	
	/**
	 * This function is used to add new servers
	 * add_server
	 * @param : Array()
	 */
	function add_server($params)
	{
		global $db;
		
		$server_ip = $params['server_ip'];
		$server_name = $params['server_name'];
		$secret_key = $params['secret_key'];
		$api_path = $params['api_path'];
		$max_usage = $params['max_usage'];

		$server_action	= $params['server_action'];
		$assoc_server	= $params['assoc_server_id'];
		
		$thumbs_role	= $array['thumbs_role'];
		$thumbs_assoc	= $array['thumbs_assoc'];
		
		$upload_photos = $array['upload_photos'];
		
		$active = 'no';
		
		//FTP Details
		$ftp_user = $params['ftp_user'];
		$ftp_pass = $params['ftp_pass'];
		$ftp_host = $params['ftp_host'];
		$ftp_port = $params['ftp_port'];
		$ftp_dir = $params['ftp_dir'];
		
		
		if($this->get_api_server($server_ip,$secret_key))
			e("Server with this IP and Secret Key already exist");
		if(!$server_ip)
			e("Please enter server ip");
		if(!$server_name)
			e("Please enter server name");
		if(!$secret_key)
			e("Please enter secret key");
		if(!$active)
			$active = "no";
		
		//Testing server
		if(!$this->server_check($server_ip))
			e("Unable to connect to server : $server_ip");
		else
		{
			//Connect to api...
			$connect_path = $api_path.'/connect.php';
			$this->connect($connect_path,$secret_key);
		}
		
		if(!error())
		{
			$db->insert(tbl("servers"),array("server_name","server_ip",
			"secret_key","server_api_path","max_usage","active","date_added",
			"ftp_host","ftp_user","ftp_pass","ftp_port","ftp_dir",
			"server_action","assoc_server_id",'thumbs_role','thumbs_assoc','upload_photos'),array(
			$server_name,$server_ip,$secret_key,$api_path,$max_usage,$active,now(),
			$ftp_host,$ftp_user,$ftp_pass,$ftp_port,$ftp_dir,$server_action,$assoc_server
			,$thumbs_role,$thumbs_assoc,$upload_photos));
			
			$this->createConfig($db->insert_id());
			e("Server has been addedd successfully","m");
		}
	}
	
	/**
	 * Function used to update server, only allowed to change max space
	 */
	function update_server($array)
	{
		global $db;
		$id = $array['sid'];
		$max_usage = $array['max_usage'];
		$server_name = $array['server_name'];
		$api_path = $array['api_path'];
		$secret_key = $array['secret_key'];
		$active = $array['active'];
		
		$server_action	= $array['server_action'];
		$assoc_server	= $array['assoc_server_id'];
		
		$thumbs_role	= $array['thumbs_role'];
		$thumbs_assoc	= $array['thumbs_assoc'];
		
		$upload_photos = $array['upload_photos'];
	
		//FTP Details
		$ftp_user = $array['ftp_user'];
		$ftp_pass = $array['ftp_pass'];
		$ftp_host = $array['ftp_host'];
		$ftp_port = $array['ftp_port'];
		$ftp_dir = $array['ftp_dir'];

		// adding field for role
		$server_role = $array['server_main_role'];

		if(!$this->get_server($id))
			e("Server does not exist");
		else
		{
			$server_details['main'] = $array;
			//Getting Assoc Server
			if($assoc_server)
			{
				$server_details['assoc'] = $this->get_server($assoc_server);
			}
			if($thumbs_assoc)
			{
				$server_details['thumbs_assoc'] = $this->get_server($thumbs_assoc);
			}
			
			//Updating Details
			$this->update_server_settings($server_details);
			
			$db->update(tbl("servers"),array("max_usage","server_name","active","server_api_path","secret_key",
			"ftp_host","ftp_user","ftp_pass","ftp_port","ftp_dir",
			"server_action","assoc_server_id",'thumbs_role','thumbs_assoc','upload_photos'),
			array($max_usage,$server_name,$active,$api_path,$secret_key,
			$ftp_host,$ftp_user,$ftp_pass,$ftp_port,$ftp_dir
			,$server_action,$assoc_server,$thumbs_role,$thumbs_assoc,$upload_photos,$server_role)," server_id='$id' ");
			e("Server has been updated successfully","m");
		}
	}
	
	/** 
	 * Function used to check weather server exists or not
	 */
	function server_check($ip,$http="http://")
	{
		$data = $http.$ip;
		// Create a curl handle to a non-existing location
		$ch = curl_init($data);
		// Execute
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_exec($ch);
		// Check if any error occured
		if(curl_errno($ch))
			return false;
		else
			return true;
		// Close handle
		curl_close($ch);
	}
	
	
	/**
	 * Function used to check weather
	 * server is allowed to connect or not
	 * we will send a secret key to the server
	 * and it will return us TRUE or FALSE
	 */
	function connect($path,$key)
	{
		if(!$this->server_check($path,false))
		{
			e("Unable to connect to $path");
			return false;
		}
		$ch = curl_init($path);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,array("secret_key"=>$key,"application_key"=>getAppKey(),'connect'=>true));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$error_code = curl_errno($ch);

		$result = curl_exec($ch);
		
		$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		
		if(DEBUG_MS)
			e("Return Code : $returnCode, Result from server $result","w");
			
		curl_close($ch);
		
		if(strstr($result,"417 - Expectation Failed") || $returnCode==417)
		{
			$ch = curl_init($path);
			curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,array("secret_key"=>$key,"application_key"=>getAppKey(),'connect'=>true));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$error_code = curl_errno($ch);
			$result = curl_exec($ch);
			curl_close($ch);
			
			if(DEBUG_MS)
				e("Return Code : $returnCode, Result from server $result","w");
		}
		
		
		if(strstr(strtolower($result),'ok'))
			return true;
		else
		{

			if($error_code==404)
				$error_msg = "Unable to connect to server, 404 api url was not found.";
			if($error_code==403)
				$error_msg = "Forbidden Error, please check for valid permissions";	
			if($error_code==502)
				$error_msg = "502 error, no response came from server";

			if(!isset($error_msg))		
			$error_msg = "Authentication failed, please make sure your appkey and secret key is set properly";
			if(DEBUG_MS)
			{
				$error_msg .= "<br>";
				$error_msg .= $result;
			}


			e($error_msg);

			return false;
		}
	}
	
	/** 
	 * Function used to get server list
	 */
	function get_servers($params=false)
	{
		//pr($params,true);	
		global $db;
		
		$cond = "";
		
		if($params['active'])
		{
			if($cond)
				$cond .= " AND ";
			$cond .= "active='".$params['active']."'";
		}
		
		if($params['action'])
		{
			if($cond)
				$cond .= " AND ";
			$cond .= "server_action='".$params['action']."'";
		}
		
		if($params['type'])
		{
			if($params['type']=='conversion')
			{
				if($cond)
					$cond .= " AND ";
				$cond .= "( server_action='0' OR server_action='2')";
			}
			
			if($params['type']=='stream')
			{
				if($cond)
					$cond .= " AND ";
				$cond .= "( server_action='0' OR server_action='3')";
			}
			
			if($params['type']=='thumbs')
			{
				if($cond)
					$cond .= " AND ";
				$cond .= "( thumbs_role='1' )";
			}
			
		}
		
		
		if($params['assoc'])
		{
			if($cond)
				$cond .= " AND ";
			$cond .= "assoc_server_id='".$params['assoc']."'";
		}
		
		
		if($params['exclude'])
		{
			if($cond)
				$cond .= " AND ";
			$cond .= "server_id!='".$params['exclude']."'";
		}
		
			
		$servers = $db->select(tbl("servers"),"*",$cond,$params['limit']);
		//pr($servers,true);
		if($db->num_rows>0)
		{
			if($params['assign'])
				assign($params['assign'],$servers);
			else
				return $servers;
		}else
			return false;
	}
	
	/**
	 * get_api_server($ip,$key)
	 * function used to get server with allowed ip and key
	 */
	function get_api_server($ip,$key)
	{
		global $db;
		$server = $db->select(tbl("servers"),"*"," server_ip='$ip' AND secret_key = '$key' ");
		if($db->num_rows>0)
			return $server[0];
		else
			return false;
	}
	
	/**
	 * Function used to get server using ID
	 */
	function get_server($id)
	{
		global $db;
		if(is_numeric($id))
			$server = $db->select(tbl("servers"),"*","server_id='$id'");
		else
			$server = $db->select(tbl("servers"),"*","server_api_path='$id'");
		if($db->num_rows>0)
			return $server[0];
		else
			return false;
	}
	
	
	/**
	 * Function used to perform
	 * delete or change status of the server
	 */
	function action($type,$id)
	{
		global $db;
		if(!$this->get_server($id))
			e("Server does not exist");
		else
		{
			switch($type)
			{
				case "activate":
				case "active":
				$db->update(tbl("servers"),array("active"),array("yes"),"server_id='$id'");
				e("Server has been activated","m");
				break;
				
				case "inactive":
				case "deactivate":
				$db->update(tbl("servers"),array("active"),array("no"),"server_id='$id'");
				e("Server has been deactivated","m");
				break;
				
				case "read-only":
				case "readonly":
				case "ro":
				$db->update(tbl("servers"),array("active"),array("read-only"),"server_id='$id'");
				e("Server has been set as \"read only\"","m");
				break;
				
				case "delete":
				$db->delete(tbl("servers"),array("server_id"),array($id));
				e("Server has been deleted","m");
				break;
			}
		}
	}
	
	
	/**
	 * Function used to get server for upload
	 */
	function get_server_api($with_ftp=false,$type=NULL)
	{
		global $db;
		
		//Get server whose usage is less than allocated
		//and is activated
		//ordery by lowest used
		
		//$query = "SELECT *,views/2 AS subtract FROM cb_video ORDER BY subtract DESC";
		if(!$with_ftp)
			$ftp_query = "";
		else
			$ftp_query = " AND ftp_host <>'' ";
		
		if($type)
		{
			switch($type)
			{
				case "upload":
				{
					$type_query = " AND server_action <> '2' ";
				}
				break;
				case "photo_upload":
				{
					$type_query = " AND upload_photos = 'yes' ";
				}
			}
			
		}
		else
			$type_query = "";
		$result = $db->select(tbl("servers"),"*,used/max_usage AS current_use"," active='yes' AND (used/1024000000)/max_usage <1 $ftp_query $type_query",1," current_use ASC");
		
		
		if($db->num_rows>0)
		{
			if (CB_SSL)
			{
				if(!strstr($result[0]['server_api_path'],'https')){
					$result[0]['server_api_path'] = str_replace('http','https',$result[0]['server_api_path']);
				}
			}

			return $result[0];
		}else
		{
			return false;
		}
	}
	
	/**
	 * Function used to get server from ip
	 */
	function get_ip_server($ip)
	{
		global $db;
		$result = $db->select(tbl("servers"),"*","server_ip='$ip'");
		
		if($db->num_rows>0)
			return $result[0];
		else
			return false;
	}
	function get_path_server($ip)
	{
		global $db;
	
		$result = $db->select(tbl("servers"),"*","server_api_path LIKE '$ip%'");
	
		if($db->num_rows>0)
			return $result[0];
		else
			return false;
	}
	/**
	 * Function used to create configuration file
	 * for each server
	 */
	function createConfig($serverId)
	{
		global $cb_multiserver;
		$server = $this->get_server($serverId);
		if($server)
		{
			$default = array(
			'ffmpeg_path' => '/usr/bin/ffmpeg',
			'mplayer_path' => "/usr/bin/mplayer",
			'mediainfo_path' => '/usr/bin/mediainfo',
			'flvtoolpp_path' => '/usr/bin/flvtool++',
			'ffprobe_path' => '/usr/bin/ffprobe',
			'mp4box_path' 	=> '/usr/bin/MP4Box',
			'php_path' 	=> '/usr/bin/php',
			'processes_at_once' => 5,
			'baseurl'	=> $server['server_api_path'],
			'normal_resolution'	=> config('normal_resolution'),
			'high_res'		=> config('high_resolution'),
			'num_thumbs'	=> config('num_thumbs'),
			'thumb_width'	=> config('thumb_width'),
			'thumb_height'	=> config('thumb_height'),
			'big_thumb_width' => config('big_thumb_width'),
			'big_thumb_height'	=> config('big_thumb_height'),
			'video_codec'	=> config('video_codec'),
			'audio_codec'	=> config('audio_codec'),
			'vrate'			=> config('vrate'),
			'vbrate_240'=> config("vbrate_240"),
			'vbrate_360'=> config("vbrate_360"),
			'vbrate_480'=> config("vbrate_480"),
			'vbrate_720'=> config("vbrate_720"),
			'vbrate_1080'=> config("vbrate_1080"),
			'srate'			=> config('srate'),
			'sbrate'		=> config('sbrate'),
			'photo_ratio'	=> config('photo_ratio'),
			'photo_thumb_width'		=> config('photo_thumb_width'),
			'photo_thumb_height'	=> config('photo_thumb_height'),
			'photo_med_width'		=> config('photo_med_width'),
			'photo_med_height'		=> config('photo_med_height'),
			'photo_lar_width'		=> config('photo_lar_width'),
			'watermark_photo'		=> config('watermark_photo'),
			'watermark_max_width'	=> config('watermark_max_width'),
			'watermark_placement'	=> config('watermark_placement'),
			'photo_crop'			=> config('photo_crop'),
			'max_video_duration'	=> config('max_video_duration'),
			'watermark_padding'		=> 5,
			'gen_iphone'			=> 'yes',
			'gen_hq'				=> 'yes',

            'watermark_video'		=> config('watermark_video'),
			'watermark_placement_v'	=> config('watermark_placement_v'),

			// 'use_watermark' => config('use_watermark'),
			'stream_via' => config('stream_via'),

            'gen_240'				=> 'yes',
			'gen_360'				=> 'yes',
			'gen_480'				=> 'yes',
			'gen_720'				=> 'no',
            'gen_1080'				=> 'no',
			'normal_quality'		=> 'normal',
			'num_sprite_thumbs'		=> 20,
			'gen_sprite'			=> 'no',
			'tr_time_interval'		=> 0,
			'callback_url'			=> PLUG_URL.'/'.CB_MULTISERVER.'/api/call_back.php',
			'create_subfolders'		=> 1,
			);
			$dir = PLUG_DIR.'/'.$cb_multiserver;
			$file = fopen($dir.'/server_configs/'.$serverId.'.json','w+');
			fwrite($file,json_encode($default));
			fclose($file);
		}
	}
	
	/**
	 * Function used to get server configuirations
	 */
	function getServerConfigs($serverId)
	{
		global $cb_multiserver;
		$server = $this->get_server($serverId);
		if($server)
		{
			$dir = PLUG_DIR.'/'.$cb_multiserver;
			$fileData = file_get_contents($dir.'/server_configs/'.$serverId.'.json');
			$configs =  json_decode($fileData,true);
			$configs = array_merge($configs,$server);
			return $configs;
		}
		return false;
	}
	
	
	/**
	 * Function used to update server configs
	 */
	function updateServerConfigs($serverId,$details)
	{
		global $cb_multiserver,$db;
		$server = $this->get_server($serverId);
		if($server)
		{

			if ($details['server_main_role'] != 'c') {
				$details['assoc_server_id'] = '0';
			}

			$db->update(tbl('servers'),array('assoc_server_id'),array($details['assoc_server_id']),'server_id = '.$serverId);
			$dir = PLUG_DIR.'/'.$cb_multiserver;
			$file = fopen($dir.'/server_configs/'.$serverId.'.json','w+');
			fwrite($file,json_encode($details));
			fclose($file);
		}
	}
	
	/**
	 * function used to uypdat
	 */
	function updateWatermark($serverID,$waterMark)
	{
		$server = $this->get_server($serverID);
		if($server)
		{
			$post_fields = $server;
			//$post_fields['waterMarkFile'] = $waterMark;
			$post_fields['application_key'] = getAppKey(); 
			
			$post_fields = json_encode($post_fields);
			$config_url = $server['server_api_path'].'/configure.php';
			$ch = curl_init($config_url);
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,array('post_fields'=>$post_fields,'waterMarkFile'=>$waterMark));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER,array("Expect:"));
			$result = curl_exec($ch);	
			curl_close($ch);
			
			if($result)
				return false;
			else
				return true;
		}
	}
	
	/**
	 * function used to uypdat
	 */
	function updatevideoWatermark($serverID,$videowaterMark)
	{
		$server = $this->get_server($serverID);
		if($server)
		{
			$post_fields = $server;
			//$post_fields['waterMarkFile'] = $waterMark;
			$post_fields['application_key'] = getAppKey(); 
			
			$post_fields = json_encode($post_fields);
			$config_url = $server['server_api_path'].'/configure.php';
			$ch = curl_init($config_url);
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,array('post_fields'=>$post_fields,'videowaterMarkFile'=>$videowaterMark));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER,array("Expect:"));
			$result = curl_exec($ch);	
			curl_close($ch);
			
			if($result)
				return false;
			else
				return true;
		}
	}
	
	/**
	 * function used to update server detais
	 */
	function update_server_settings($details)
	{
		$server = $details['main'];		
		$post_fields = $details;
		
		$post_fields['secret_key'] = $server['secret_key'];
		$post_fields['application_key'] = getAppKey(); 
		$post_fields = json_encode($post_fields);
		$config_url = $server['api_path'].'/configure.php';
		
		$ch = curl_init($config_url);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,array('post_fields'=>$post_fields,'settings'=>'yes'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
		$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$result = curl_exec($ch);	
		curl_close($ch);

		
		if($result)
			return false;
		else
			return true;
	}
	
}

?>