<?php

/**
 * This file is used to download files
 * from one server to our server 
 * in prior version, this file was not so reliable
 * this time it has complete set of instruction 
 * and proper downloader

 * @Author : Arslan Hassan
 * @License : Attribution Assurance License -- http://www.opensource.org/licenses/attribution.php
 * @Since : 01 July 2009
 */
include("../../../includes/config.inc.php");
include("../../../includes/classes/curl/class.curl.php");
error_reporting(E_ALL ^ E_NOTICE);



ini_set('mysql.connect_timeout', 1000);
ini_set('default_socket_timeout', 1000);

$file = mysql_clean($_POST['file']);
$file_name = time().RandomString(5);


$serverPath = $_POST['server_path'];
$server = $multi_server->get_path_server($serverPath);


$youtube = $_POST['youtube'];

if($youtube!='yes'){
    $serverApi = $server['server_api_path'] . '/actions/file_downloader.php';
}
else
{
    $serverApi = $server['server_api_path'] . '/actions/yt_downloader.php';
    $youtube_url = $file;
    
    $parsed_url = parse_url($youtube_url);

    if($parsed_url['query'])
    {
        parse_str($parsed_url['query'], $output);
        $yt_key = $output['v'];
    }else
    {
        if(!strstr($youtube_url,'www') && !strstr($youtube_url,'http') && strlen($youtube_url) <= 15)
        $yt_key = $youtube_url;
    }
    
    $file = $yt_key;
    
   /* //Checking if Youtube Key already exists..
    if($cbvid->youtube_key_exists($yt_key)&&$asds)
    {
        exit(json_encode(array('error'=>'Youtube Key already exists')));
    }*/
    
}

if($youtube=='yes')
{
    
    $thumbs_dir = THUMBS_DIR;
    if(isset($_POST['youtube']))
    {
        $youtube_url = $_POST['file'];
        $filename = $_POST['file_name'];    
        
        $ParseUrl = parse_url($youtube_url);
        parse_str($ParseUrl['query'], $youtube_url_prop);
        $YouTubeId = isset($youtube_url_prop['v']) ? $youtube_url_prop['v'] : '';
        
        if(!$YouTubeId)
        {
            exit(json_encode(array("error"=>"Invalid youtube url")));
        }
        

        ########################################
        # YouTube Api 3 Starts                 #
        ########################################
        /**
        * After deprecation of YouTube Api 2, ClipBucket is now evolving as
        * it allos grabbing of youtube videos with API 3 now. 
        * @author Saqib Razzaq
        *
        * Tip: Replace part between 'key' to '&' to use your own key
        */ 
        $apiKey = $Cbucket->configs['youtube_api_key'];
        // grabs video details (snippet, contentDetails)
        $request = 'https://www.googleapis.com/youtube/v3/videos?id='.$YouTubeId.'&key='.$apiKey.'&part=snippet,contentDetails';
        $youtube_content = file_get_contents($request);
        $content = json_decode($youtube_content,true);
        $thumb_contents = maxres_youtube($content);
        $max_quality_thumb = $thumb_contents['thumb'];

        $data = $content['items'][0]['snippet'];

        // getting time out of contentDetails
        $time = $content['items'][0]['contentDetails']['duration'];

        /**
        * Converting YouTube Time in seconds
        */

        $total = yt_time_convert($time);
        $vid_array['title']         = $data['title'];
        $vid_array['description']   = $data['description'];
        $vid_array['tags']          = $data['title'];
        $vid_array['duration']      = $total;
        
        $vid_array['thumbs'] = $max_quality_thumb;

        #pex($vid_array['thumbs'],true);

        $vid_array['embed_code'] = '<iframe width="560" height="315"';
        $vid_array['embed_code'] .= ' src="//www.youtube.com/embed/'.$YouTubeId.'" ';
        $vid_array['embed_code'] .= 'frameborder="0" allowfullscreen></iframe>';
        $file_directory = createDataFolders();
        $vid_array['file_directory'] = $file_directory;
        $vid_array['category'] = array($cbvid->get_default_cid());
        $vid_array['file_name'] = $filename;
        $vid_array['userid'] = userid();
        
        $duration = $vid_array['duration'];
        $vid = $Upload->submit_upload($vid_array);
        
        if(error())
        {
            //exit(json_encode(array('error'=>error('single'))));
        }
        
        if(!function_exists('get_refer_url_from_embed_code'))
        {
            exit(json_encode(array('error'=>"Clipbucket embed module is not installed")));
        }
        
        $ref_url = get_refer_url_from_embed_code(unhtmlentities(stripslashes($vdetails['embed_code'])));
        $ref_url = $ref_url['url'];
        $db->update(tbl("video"),array("status","refer_url","duration"),array('Successful',$ref_url,$duration)," videoid='$vid'");

        //Downloading thumb
       # exit($max_quality_thumb);
        $downloaded_thumb = snatch_it(urlencode($max_quality_thumb),$thumbs_dir.'/'.$file_directory,$filename."-ytmax.jpg");

        $params = array();
        $params['filepath'] = $downloaded_thumb;
        $params['files_dir'] = $file_directory;
        $params['file_name'] = $filename;
        $params['width'] = $thumb_contents['width'];
        $params['height'] = $thumb_contents['height'];
        thumbs_black_magic($params);
        exit(json_encode(array('youtubeID'=>$YouTubeId,
        'vid'=>$vid,
        'title'=>$vid_array['title'],'desc'=>$vid_array['description'],
        'tags'=>$vid_array['tags'])));  
    }
}
else
{
    //$serverApi
    $post_vars['application_key']   = getAppKey();
    $post_vars['secret_key']  =  $server['secret_key'];
    $post_vars['file']        =  $file;
    $post_vars['file_name']   =  $file_name; 

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $serverApi);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vars); 

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    curl_setopt($ch, CURLOPT_HEADER, false);
    $result = curl_exec($ch);
    curl_close($ch);

}    


if (strstr($result, 'success'))
{

    
    //Inserting data
    $title = urldecode(mysql_clean(getName($file)));
    $title = $title ? $title : "Untitled";
    $defaultCid = $cbvid->get_default_cid();
    

    if (!$defaultCid)
        $defaultCid = 1;

    $vidDetails = array
        (
        'title' => $title,
        'description' => $title,
        'tags' => genTags(str_replace(' ', ', ', $title)),
        'category' => array($defaultCid),
        'file_name' => $file_name,
        'userid' => userid(),
    );


    $vid = $Upload->submit_upload($vidDetails);


    if (!error())
    {
        $vidDetails['videoid'] = $vid;
        $vidDetails['userid'] = userid();

        $embed_code = $cbvid->embed_code($vidDetails, 'iframe');
        echo json_encode(array('vid' => $vid, 'video_link' => videoLink($vidDetails), 'embed_code' => ($embed_code)));
    } else
    {
        $errors = error();
        $array = array('error' => $errors[0]);


        if (has_access('admin_access', true))
        {
            $array['vid_details'] = $vidDetails;
            $array['mysql_error'] = mysql_error();
        }


        echo json_encode($array);
    }
} else
{
    if (DEBUG_MS)
    {
        $ef = fopen('ms_error_log', 'a+');
        fwrite($ef, '[' . now() . '] ' . $result . "\n");
        fwrite($ef, '[' . now() . '] ' . $serverApi . "\n");
        fclose($ef);
    }
    echo json_encode(array('error' => 'Unable to download video'));
}


?>