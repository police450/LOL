<?php


include_once(dirname(__FILE__)."/../../../includes/config.inc.php");
include_once(dirname(__FILE__)."/../../../includes/classes/curl/class.curl.php");
error_reporting(E_ALL ^ E_NOTICE);



ini_set('mysql.connect_timeout', 1000);
ini_set('default_socket_timeout', 1000);

getTheUploader();

$servers = $multi_server->servers_list;
shuffle($servers);



//$yt_key = mysql_clean($argv[1]);
//if(!$file_name)
//$file_name = mysql_clean($argv[2]);




$server = $multi_server->get_path_server($servers[0]['uploadServerPath']);
$serverApi = $servers[0]['uploadServerPath'] . '/actions/yt_downloader.php';

if(!$yt_key) exit(json_encode(array('error'=>'What the??')));


//if($cbvid->get_youtube_video(mysql_clean($yt_key)) && $yt_key) exit(json_encode(array('error'=>'Youtube video already exists')));

$curl = new curl($serverApi);
$curl->setopt(CURLOPT_FOLLOWLOCATION, true);
$curl->setopt(CURLOPT_POST, true);
$curl->setopt(CURLOPT_RETURNTRANSFER, true);
$curl->setopt(CURLOPT_POSTFIELDS, array('file' => $yt_key, 'file_name' => $file_name,
'secret_key' => $server['secret_key'], 'application_key' => getAppKey()));
$curl->setopt(CURLOPT_HTTPHEADER, array('Expect:'));
$curl->setopt(CURLOPT_CONNECTTIMEOUT, 1200);
$curl->setopt(CURLOPT_TIMEOUT, 1200);
$result = $curl->exec();

$data = json_decode($result,true);

if($data['error'])
{


    $video = $cbvid->get_video($file_name,true);
    $extras = get_video_extras($video);

    $extras['failed_reason'] = array(
        'code' => 'yt_upload_error',
        'message' => $data['error']
    );

    $array = array(
        'status' => 'Failed',
        'failed_reason' => 'invalid_upload',
        'extras' => json_encode($extras)
    );

    db_update(tbl('video'),$array,"videoid='".$video['videoid']."'");


}
?>