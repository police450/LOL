<?php

/* This file will recieve call back from other server
 * that will send conversion log
 * - video file 
 * - mp4 
 *
 * We get an array
 * Array(
 *       "conversion_log" => Conversion Log
 *   "has_mp4" => boolen
 *   "server_ip" => Ip of the server
 *   "secret_key" => for authentication
 *   "file_server_path" => for path
 *   "file_thumbs_path" => tells paths to the thumbs
 *   "file_thumbs_count" => number of thumbs of the file
 *   
 *   )
 */

ini_set("log_errors", true);
ini_set("error_log", "error_log.txt");
$in_bg_cron = true;

include("../../../includes/config.inc.php");
if($_POST['version'] < 2)
{  
    if (isset($_POST['callback']))
    {
         
        $server_ip = $_SERVER['REMOTE_ADDR'];


        if ($_POST['server_ip'])
            $server_ip = $_POST['server_ip'];

        $secret_key = $_POST['secret_key'];

        $file_server_path = $_POST['file_server_path'];
        $file_thumbs_path = $_POST['files_thumbs_path'];
        $file_thumbs_count = $_POST['file_thumbs_count'];

        $filegrp_size = $_POST['filegrp_size'];

        $has_hq = $_POST['has_hq'];
        if (!$has_hq || $has_hq == 'no')
            $has_hq = 'no';
        else
            $has_hq = 'yes';

        $has_mobile = $_POST['has_mobile'];
    //  if($has_hq!='mp4' && $has_hq!='flv')
    //      $has_hq = 'none';

        $conversion_log = $_POST['conversion_log'];

        /**
         * Steps we have to follow
         *
         * - checks server ip
         * - checks server secret key
         * - get converion log
         * - parse and update details
         */
                

        if (!$multi_server->get_api_server($server_ip, $secret_key))
        {
            $dbfile = fopen("db_query.txt", "w+");
            fwrite($dbfile, $db->db_query);
            fclose($dbfile);
            exit("error_1\nServerIp:$server_ip\nKey:$secret_key\nCurrentIp" . $_SERVER['HTTP_HOST'] . "\n" . json_encode($_POST));
            e("Unable to verfy server with IP : $server_ip || Key : $secret_key ");
        } else
        {
            if (!isset($conversion_log))
                e("No Conversion Log");
            else
            {
                //Lets just parse the conversion log
                $data = $conversion_log;

                preg_match_all('/(.*) : (.*)/', trim($data), $matches);

                $matches_1 = ($matches[1]);
                $matches_2 = ($matches[2]);

                for ($i = 0; $i < count($matches_1); $i++) {
                    $statistics[trim($matches_1[$i])] = trim($matches_2[$i]);
                }
                if (count($matches_1) == 0)
                {
                    exit('There was no conversion log');

                }
                $statistics['conversion_log'] = $data;

                $file = fopen("file.txt", "w");
                foreach ($statistics as $key => $stat)
                //      fwrite($file,"[$key] => $stat \n\n");
                //Extract file name
                    $file_name = explode("/", $statistics['File']);
                $file_name = $file_name[count($file_name) - 1];
                $file_name = getName($file_name);
                if (!$file_name)
                    $file_name = $_POST['file_name'];
                $has_hd = mysql_clean($_POST['is_hd']);
                $process_status = $_POST['process_status'];
                $folder = $_POST['folder'];
                $has_sprite = $_POST['has_sprite'];
                if ($_POST['conv_status'] == 'completed')
                    $status = 'Successful';
                else
                    $status = 'Failed';

                if ($_POST['sprite_thumbs_no'])
                    $sprite_thumbs_no_q = " , sprite_thumbs_no = '" . $_POST['sprite_thumbs_no'] . "' ";

                $query = "UPDATE " . tbl("video") . " SET 
                        file_type='" . $_POST['file_type'] . "',
                        status='" . $status . "',
                        duration='" . $statistics['duration'] . "',
                        server_ip='" . $server_ip . "',
                        file_server_path='" . $file_server_path . "',
                        files_thumbs_path='" . $file_thumbs_path . "',
                        file_thumbs_count='" . $file_thumbs_count . "',
                        has_hq='" . $has_hq . "',
                        has_hd='" . $has_hd . "',
                        has_mobile='" . $has_mobile . "',
                        filegrp_size = '" . $filegrp_size . "',
                        process_status = '" . $process_status . "',
                        file_directory = '" . $folder . "',
                        has_sprite = '" . $has_sprite . "'
                        $sprite_thumbs_no_q
                        WHERE  file_name='" . $file_name . "'";

                mysql_query($query);

                fwrite($file, "DB => " . $query . " \n\n");
                //Updating Server Size
                $query = "UPDATE " . tbl("servers") . " SET 
                        used = used+'" . $filegrp_size . "',
                        last_updated =  now()
                        WHERE  server_ip ='" . $server_ip . "'";
                mysql_query($query);

                // if (function_exists('vi_create_tracker'))
                //     vi_create_tracker($statistics);

                //Now write the content of file converiong in the file

                $log_file = fopen(LOGS_DIR . '/' . $file_name . '.log', "w+");
                fwrite($log_file, $conversion_log);
                fclose($log_file);



                echo "success";

                //fwrite($file,"DB => ".$query." \n\n");
                //fwrite($file,"FILE NAME IS => $file_name \n\n");
                //fwrite($file,"DB QUERY => ".$query." \n\n");
                fclose($file);
            }
        }
    }
}else
{
 
    if ($_POST['update_files'] )
    {

        $server_ip = $_SERVER['REMOTE_ADDR'];


        if ($_POST['server_ip'])
            $server_ip = $_POST['server_ip'];

        $secret_key = $_POST['secret_key'];
        
        if (!$multi_server->get_api_server($server_ip, $secret_key))
        {
            $dbfile = fopen("db_query.txt", "w+");
            fwrite($dbfile, $db->db_query);
            fclose($dbfile);
            exit("error_1\nServerIp:$server_ip\nKey:$secret_key\nCurrentIp" . $_SERVER['HTTP_HOST'] . "\n" . json_encode($_POST));
            e("Unable to verfy server with IP : $server_ip || Key : $secret_key ");
        } else
        {
            
            $file = fopen("file.txt", "w+");
            fwrite($log_file, 'Updating file as '.$_POST['video_files']);
            fclose($log_file);

            $file_name = $_POST['file_name'];

            $videoid = $cbvid->get_videoid_from_filename($file_name);
            $files = json_decode($_POST['video_files']);
            
            $cbvid->update_extras($videoid,'video_files',$files);
            
            exit('success');
        }
            
    } else
    {
        
        // THIS CASE IS RUNNING , FOR TUNE.TV
        if (isset($_POST['callback']))
        {
          
            $server_ip = $_SERVER['REMOTE_ADDR'];

            

            if ($_POST['server_ip'])
                $server_ip = $_POST['server_ip'];

            $secret_key = $_POST['secret_key'];

            $file_server_path = $_POST['file_server_path'];
            $file_thumbs_path = $_POST['files_thumbs_path'];
            $file_thumbs_count = $_POST['file_thumbs_count'];

            $filegrp_size = $_POST['filegrp_size'];

            $has_hq = $_POST['has_hq'];
            if (!$has_hq || $has_hq == 'no')
                $has_hq = 'no';
            else
                $has_hq = 'yes';

            $has_mobile = $_POST['has_mobile'];
        
            $conversion_log = $_POST['conversion_log'];
            
     
            /** 
             * Steps we have to follow
             *
             * - checks server ip
             * - checks server secret key
             * - get converion log
             * - parse and update details
             */ 

            $testing = fopen("test__serverinfo.txt", "w+");
            fwrite($testing, "SERVERIP->".$server_ip."<br>secretkey->".$secret_key);
            fclose($testing);
    
            if (!$multi_server->get_api_server($server_ip, $secret_key))
            {
                $dbfile = fopen("db_query__1.txt", "w+");
                fwrite($dbfile, $server_ip."           ".$secret_key);
                fclose($dbfile);
                exit("error_1\nServerIp:$server_ip\nKey:$secret_key\nCurrentIp" . $_SERVER['HTTP_HOST'] . "\n" . json_encode($_POST));
                e("Unable to verfy server with IP : $server_ip || Key : $secret_key ");
            } else
            {

                

                if (!isset($conversion_log))
                {
                    echo 'No conversion log';
                    e("No Conversion Log");
                }else
                {
                    
                    $data = $conversion_log;
    
                    preg_match_all('/(.*) : (.*)/', trim($data), $matches);

                    $matches_1 = ($matches[1]);
                    $matches_2 = ($matches[2]);

                    for ($i = 0; $i < count($matches_1); $i++) {
                        $statistics[trim($matches_1[$i])] = trim($matches_2[$i]);
                    }
                    // if (count($matches_1) == 0)
                    // {
                    //     exit('There was no conversion log');
                    // }
                    $statistics['conversion_log'] = $data;

                        
                     $file_name = $_POST['file_name'];
                     
                    
                                      
                    $has_hd = mysql_clean($_POST['has_hd']);
                    $process_status = $_POST['process_status'];
                    $folder = $_POST['folder'];
                    $sprite_count = $_POST['sprite_count'];
                    $has_sprite = $_POST['has_sprite'];
                    
                    if ($_POST['conv_status'] == 'completed')
                        $status = 'Successful';
                    else
                        $status = 'Failed';


                    $md5hash = $_REQUEST['md5_hash'];
                    $failed_reason = $_REQUEST['failed_reason'];

                    $duration = $statistics['duration'];
                    #$current_progress = $_POST['current_progress'];
                    if(isset($_POST['duration']))
                        $duration = $_POST['duration'];
                    
                    if ($_POST['sprite_thumbs_no'])
                        $sprite_thumbs_no_q = " , sprite_thumbs_no = '" . $_POST['sprite_thumbs_no'] . "' ";

                  $udpate_query = "UPDATE " . tbl("video") . " SET 
                    file_type='" . $_POST['file_type'] . "',
                    status='" . $status . "',
                    duration='" .$duration  . "',
                    video_files = '" .$_POST['video_files']  . "',
                    server_ip='" . $server_ip . "',
                    file_server_path='" . $file_server_path . "',
                    files_thumbs_path='" . $file_thumbs_path . "',
                    file_thumbs_count='" . $file_thumbs_count . "',
                    has_hq='" . $has_hq . "',
                    has_hd='" . $has_hd . "',
                    version='".$_POST['version']."',
                    has_mobile='" . $has_mobile . "',
                    filegrp_size = '" . $filegrp_size . "',
                    process_status = '" . $process_status . "',
                    file_directory = '" . $folder . "',
                    has_sprite = '" . $has_sprite . "',
                    sprite_count = '" . $sprite_count . "',
                    failed_reason = '".$failed_reason."'
                    $sprite_thumbs_no_q
                    WHERE  file_name='" . $_POST['file_name'] . "'";

                    $db->execute($udpate_query);
                  
                  
                    $dbquery = fopen("db_query.txt", "w+");
                    fwrite($dbquery, $udpate_query);
                    fwrite($dbquery, $sprite_count);
                    fclose($dbquery);


                
                    echo "success";
                    



                    //Now write the content of file converiong in the file

                    $log_file = fopen(LOGS_DIR . '/' . $file_name . '.log', "w+");
                    fwrite($log_file, $conversion_log);
                    fclose($log_file);

                    exit;


                }
            }
        }
    }
}

if (error())
{
    $file = fopen("errors.txt","w");
    $error = error_list();
    foreach($error as $err)
    {
    fwrite($file,"* $err \n\n");
    }
    fclose($file);
}
?>