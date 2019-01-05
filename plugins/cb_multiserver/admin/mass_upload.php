<?php

/**
 * This file is used to handle all admin functions
 * such as adding new server, updating existing one or deleteing it
 */
	
//Getting SERVER
$server = $multi_server->get_server_api(true);
if(!$server)
	e("No Server found with valid FTP Details");
if(isset($_POST['mass_upload_video']) && $server)
{
	
	$cb_ftp = new cb_ftp();
	$cb_ftp->files_dir = $server['ftp_dir'];
	$cb_ftp->connect($server['ftp_host'],$server['ftp_user'],$server['ftp_pass']);
	
	if($cb_ftp)
	{
		$files = $cbmass->get_video_files();
		
		$total = count($_POST['mass_up']);
		for($i=0;$i<$total;$i++)
		{	
			$file_key = time().RandomString(5);
			$file_arr = $files[$i];
			
			if($cbmass->is_mass_file($file_arr))
			{
				$code = $i+1;
				//Inserting Video Data...
				$array = array
				(
				'title' => $_POST['title'][$i],
				'description' => $_POST['description'][$i],
				'tags' => $_POST['tags'][$i],
				'category' => $_POST['category'.$code],
				'file_name' => $file_key,
				);
				$vid = $Upload->submit_upload($array);
			}else{
				e("\"".$file_arr['title']."\" is not available");
			}
			
			if(error())
			{
				$error_lists[] = "Unable to upload \"".$file_arr['title']."\"";
				$errors = error();
				foreach($errors as $e)
					$error_lists[] = $e;
				
				$eh->flush_error();
			}else{
				e("\"".$file_arr['title']."\" Has been uploaded successfully","m");
			}
			
	
			
			if($vid)
			{
				$cb_ftp->upload($file_arr['path'].$file_arr['file'],$file_key.'.'.getExt($file_arr['file']));
				exec("/usr/bin/curl -q ".$server['server_api_path']."/actions/mass_uploads.php?file='".$file_key."&ext=".getExt($file_arr['file'])."&secret'=".$server['secret_key']."  &> /dev/null &");
				
				//Unlink file
				if(file_exists($file_arr['path'].$file_arr['file']))
				unlink($file_arr['path'].$file_arr['file']);
	
			}
		}
		
		$cb_ftp->close();
	}	
}



if(count($error_lists)>0)
{
	foreach($error_lists as $e)
		e($e);
}

assign("_link","plugin.php?folder=".$cb_multiserver."/admin&file=multi_servers.php");

template_files('mass_upload.html',PLUG_DIR.'/'.$cb_multiserver.'/admin');

?>