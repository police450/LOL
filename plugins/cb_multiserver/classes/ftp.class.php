<?php

/**
 * This class will perform all
 * Ftp connection
 * @Author : ARSLAN HASSAN
 *
 */
 
 
class cb_ftp
{
	var $ftp = null;
	var $ftp_id = false;
	var $files_dir = 'www/files';
	
	var $mode = "video";
	var $transfer_mode = "FTP_ASCII";
	
	/** 
	 * Function used to connect to ftp
	 */
	function connect($host,$user,$pass,$port=21,$timeout=90)
	{
		$ftp = ftp_connect($host,$port,$timeout);
		if(!$ftp)
			e("Unable to connect to FTP server $host");
		else
		{
			$login = ftp_login($ftp,$user,$pass);
			if(!$login)
				e("Unable to login to ftp server $user:$pass@$host");
			else
			{
				$this->ftp = $ftp;
				$this->ftp_id = $login;
				$this->ch_dir();
			}
			
		}
	}
	
	/**
	 * Ch to files Directory
	 */
	function ch_dir($dir=NULL)
	{
		
		if(!$dir)
			$dir = $this->files_dir;
		if($this->ftp_id)
		{
			//first check weather directory exists or not
			if(!ftp_chdir($this->ftp,$dir))
				e("File Directory does not exist : ".$dir);
			else
			{
				if(!@ftp_chdir($this->ftp,$this->mode))
				{
					//ftp_mkdir($this->ftp,$this->mode);
					//ftp_chdir($this->ftp,$this->mode);
				}	
			}
		}
	}
	
	/**
	 * Function used to put file from one server to another
	 * mostly from the main server to file server
	 */
	function upload_vid($vdetails)
	{
		global $db;
		//Current File 
		if(@!file_exists($vdetails) || is_array($vdetails))
			$file = get_video_file($vdetails,false,false);
		else
			$file = $vdetails;

		if(!file_exists(VIDEOS_DIR.'/'.$file))
		{
			e("File does not exists");
			return false;
		}
				
		//We are already connected to the server
		//Lets just move the file to the server
		if(ftp_put($this->ftp,$file,str_replace("\\","/",VIDEOS_DIR.'/'.$file),FTP_ASCII))
		{
			if($this->mode=='video')
			{
				//$db->
			}
		}

	}
	
	/**
	 * Function used to upload file to the server
	 */
	function upload($file,$svFile=NULL,$dir=NULL)
	{
		if(!$svFile)
			$svFile = $file;
			
		if($dir)
			$this->ch_dir($dir);
		if($this->ftp)
		{
			ftp_put($this->ftp,$svFile,$file,FTP_BINARY);
		}else
			e("No valid ftp connection");
	}
	
	/**
	 * Function used to close the ftp connection
	 */
	function close()
	{
		ftp_close($this->ftp);
	}function _exit(){ return $this->close(); }
	
	
}

?>