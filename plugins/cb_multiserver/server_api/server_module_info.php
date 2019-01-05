<?php
include("config.php");
function ffmpeg_info($ffmpeg_path)
{
	//echo 'here';
	
	$ffmpeg_command = $ffmpeg_path.' -version';
	$ffmpeg_results = shell_exec($ffmpeg_command);
	$from = strpos($ffmpeg_results, "ffmpeg version");
	$to = strpos($ffmpeg_results, "Copyright (c)");
	$ffmpeg_version = substr($ffmpeg_results, $from,$to);
	$ffmpeg_version = substr($ffmpeg_version, -9);
	
	return 	$ffmpeg_version;
}
function mp4box_info($mp4_path)
{
	$mp4box_command = $mp4_path.' -version';
	$mp4box_command = "PATH=\$PATH:/bin:/usr/bin:/usr/local/bin bash -c \"$mp4box_command\"  2>&1";
	$mp4_box = shell_exec($mp4box_command);
	$from = strpos($mp4_box, "MP4Box - GPAC version");
	$to = strpos($mp4_box, "Copyright (c)");
	$mp4_box_version = substr($mp4_box, $from,$to);
	$mp4_box_version = explode('version', $mp4_box_version);
	$mp4_box_versio = str_replace('GPAC', "", $mp4_box_version[1]);
	return $mp4_box_versio;
}

function php_info($php_path)
{
	$php_command = $php_path.' -v';
	$php = shell_exec($php_command);
	$from = strpos($php, "PHP");
	$to = strpos($php, "Copyright (c)");
	$php_version = substr($php, $from,$to);
	$php_version = substr($php_version, 3);
	$php_version = explode('.', $php_version);
	$php_version_final = $php_version[0].'.'.$php_version[1];
	return $php_version_final;
}

function media_info($media_info_path)
{
	$media_info_cmd = $media_info_path.' --version';
	$media_info = shell_exec($media_info_cmd);
	$media_info_version  = explode('v', $media_info);
	return $media_info_version[1];
}
function flvtool_info($flvtool_path)
{
	$flvtool_cmd =$flvtool_path.' -v';
	$flvtool = shell_exec($flvtool_cmd);
	return $flvtool;
	
}
if(isset($_POST['serverinfo'])&&$_POST['serverinfo']==true)
{

	$ffmpeg_version = ffmpeg_info($configs['ffmpeg_path']);
	$all_files = scandir(BASEDIR);
	foreach ($all_files as $key => $all_file) 
	{
		if(trim($all_file) == '..'||trim($all_file) == '.svn'||trim($all_file) == '.')
			echo '';
		else	
			$perm_array[$all_file] = permissions($all_file);
	}


	$mp4_box_version = mp4box_info($configs['mp4box_path']);
	$php_version_final = php_info($configs['php_path']);
	$media_info_version = media_info($configs['mediainfo_path']);
	$flvtool = flvtool_info($configs['flvtoolpp_path']);
	$module_info ["ffmpeg"] = $ffmpeg_version;
	$module_info ["ffmpeg_path"] = $configs['ffmpeg_path'];
	$module_info["MP4_Box"] = $mp4_box_version;
	$module_info ["MP4_Box_path"] = $configs['mp4box_path'];
	$module_info["PHP"] = $php_version_final;
	$module_info ["PHP_path"] = $configs['php_path'];
	$module_info["Media_info"] = $media_info_version;
	$module_info ["Media_info_path"] = $configs['mediainfo_path'];
	$module_info["flvtool"] = $flvtool;
	$module_info ["flvtool_path"] = $configs['flvtoolpp_path'];
	$module_info["secret_key"] = SECRET_KEY;
	$module_info["ip_add"] = $_SERVER['SERVER_ADDR'];
	$new_array = array_merge($module_info,$_POST);
	$new_array = array_merge($new_array,array("Perms"=>$perm_array));
	$encoded_array = json_encode($new_array);
	//pr($module_info,true);
	$array = array('encoded_data'=>$encoded_array,'write'=>true,'server_id'=>$_POST['server_id']);
	$call_bk = $_POST["call_back_path"];
	//echo $call_bk;
	$ch = curl_init($call_bk);
	$ch_opts = array(
	 CURLOPT_POST=>true,
	 CURLOPT_RETURNTRANSFER=> true,
	 //CURLOPT_BINARYTRANSFER => true,
	 CURLOPT_HEADER => false,
	 CURLOPT_SSL_VERIFYHOST=> false, 
	 CURLOPT_SSL_VERIFYPEER=> false,
	 CURLOPT_HTTPHEADER => array("Expect:"),
	);


	curl_setopt($ch,CURLOPT_POSTFIELDS,$array);
	//$charray = $ch_opts;
	//$charray[CURLOPT_POSTFIELDS] = $module_info;

	//curl_setopt_array($ch,$charray);

	$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

	$result = curl_exec($ch);	
	//echo $result;
}
else
{
	echo 'auth failed';
}
function permissions($dir)
{
	$perms = fileperms($dir);


	if (($perms & 0xC000) == 0xC000) {
	    // Socket
	    $info = 's';
	} elseif (($perms & 0xA000) == 0xA000) {
	    // Symbolic Link
	    $info = 'l';
	} elseif (($perms & 0x8000) == 0x8000) {
	    // Regular
	    $info = '-';
	} elseif (($perms & 0x6000) == 0x6000) {
	    // Block special
	    $info = 'b';
	} elseif (($perms & 0x4000) == 0x4000) {
	    // Directory
	    $info = 'd';
	} elseif (($perms & 0x2000) == 0x2000) {
	    // Character special
	    $info = 'c';
	} elseif (($perms & 0x1000) == 0x1000) {
	    // FIFO pipe
	    $info = 'p';
	} else {
	    // Unknown
	    $info = 'u';
	}

	// Owner
	$info .= (($perms & 0x0100) ? 'r' : '-');
	$info .= (($perms & 0x0080) ? 'w' : '-');
	$info .= (($perms & 0x0040) ?
	            (($perms & 0x0800) ? 's' : 'x' ) :
	            (($perms & 0x0800) ? 'S' : '-'));

	// Group
	$info .= (($perms & 0x0020) ? 'r' : '-');
	$info .= (($perms & 0x0010) ? 'w' : '-');
	$info .= (($perms & 0x0008) ?
	            (($perms & 0x0400) ? 's' : 'x' ) :
	            (($perms & 0x0400) ? 'S' : '-'));

	// World
	$info .= (($perms & 0x0004) ? 'r' : '-');
	$info .= (($perms & 0x0002) ? 'w' : '-');
	$info .= (($perms & 0x0001) ?
	            (($perms & 0x0200) ? 't' : 'x' ) :
	            (($perms & 0x0200) ? 'T' : '-'));
	pr($info,true);
	return $info;
}
?>