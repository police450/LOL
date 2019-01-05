<?php
    include_once('../includes/config.inc.php');

    /**
    * This function is used to clean a string removing all special chars
    * @author Mohammad Shoaib
    * @param string
    * @return cleaned string
    */ 
    function cleanString($string) 
    {
        $string = str_replace("â€™", "'", $string);
        return preg_replace('/[^A-Za-z0-9 !@#$%^&*()_?<>|{}\[\].,+-;\/:"\'\-]/', "'", $string);
    }

    function throw_error_msg($msg)
    {
        throw new Exception($msg); 
    }

    if (!function_exists('mob_description'))
    {
        function mob_description($description)
        {
            global $Cbucket;

            $description = str_replace('ÿþ', '', $description);
            $description = str_replace('?', 'MY_QUE_MARK', $description);
            $description = utf8_decode($description);
            $description = str_replace('?', '', $description);
            $description = str_replace('MY_QUE_MARK', '?', $description);

            return $description;
        }
    }

    if (!function_exists('get_mob_video'))
    {
        function get_mob_video($params)
        {

            $vdo = $params['video'];

            $assign = $params['assign'];
            $vid_file = get_video_file($vdo, true, true);
            $vidfile = substr($vid_file, 0, strlen($vid_file) - 4) . '-m.mp4';
            assign($assign, $vidfile);

            if ($vidfile)
                return $vidfile;
        }
    }

    if (!function_exists('cleanString'))
    {
        function cleanString($string) 
        {
            return preg_replace('/[^A-Za-z0-9 !@#$%^&*()_?<>|{}\[\].,+-;\/:"\'\-]/', "'", $string);
        }
    }

    $blacklist_fields = array(
        'password', 'video_password', 'avcode', 'session'
    );

    $remove_users_attributes    =   array('session','ip','password','signup_ip','featured_date',
                                        'background','background_color',
                                        'background_url','background_repeat',
                                        'background_attachement','banned_users',
                                        'welcome_email_sent','vb_userid','vb_groupid',
                                        'user_session_key','user_session_code','fbuid',
                                        'featured_video','msg_notify','avcode','last_logged',
                                        'doj','num_visits','time_zone','comments_count',
                                        'last_commented','last_active','total_downloads',
                                        'is_subscribed','upload','avatar_url','voters'
                                        );

    //get total likes/dislikes of an object
    function get_total_likes_dislikes($rating, $rated_by, $total=10)
    {
        $total    = (int)$total;
        $rating   = (int)$rating;
        $ratings  = (int)$rated_by;
        
        $perc = $rating*100/$total;
        $perc = round($perc);
        $perc = $perc.'%';
        
        $array['total_likes']     =  round($ratings*$perc/100);
        $array['total_dislikes']  =  $ratings-$array['total_likes'];

        return $array;
    }

    // function used to get quality of a file 
    if(!function_exists('get_quality_new'))
    {
        function get_quality_new($file)
        {
            $quality = explode('-', $file);
            $quality = end($quality);
            
            $quality = explode('.',$quality);
            $quality = trim($quality[0]);

            if( is_numeric($quality) ||  in_array($quality, array('sd','hd','m'))  )
                return $quality;
            else
                return '';    
        }   
    }  

    // this method is for internal api use, don't call directly
    function format_videos($videos, $multi=true)
    {   

        global $db,$userquery,$cbvideo;
        $fields_arr = array(
                            'video' => get_video_fields(),
                            'users' => get_user_fields()
                            );

        $new_videos = array();
        if(!empty($videos))
        {
            foreach ($videos as $video)
            {   

                if($video['broadcast'] == 'private')
                {
                    $user_friends_arr = array();
                    $fetch_user_friends = $userquery->get_contacts($video['userid']);
                     
                    if($fetch_user_friends)
                    {
                        foreach ($fetch_user_friends as $key => $value)
                        {
                            array_push($user_friends_arr,$value['contact_userid']);
                        }

                        $friends_ids = $user_friends_arr;
                    }
                }

                if($video['file_type']=='1'){

                    $video['file_type']='dash';
                
                }elseif($video['file_type']=='2'){
                    
                    $video['file_type']='hls';

                }else{

                    $video['file_type']='mp4';
                }


                $video = array_map('utf8_encode', $video);
                //pr($video,true);
                
                $video['thumbs'] = array('default' => get_thumb($video), 'big' => get_thumb($video, 'big'),'640x480'=>get_thumb($video, '640x480'));
                

                if (function_exists('get_mob_video')) 
                {
                    $video['videos'] = array('mobile' => get_mob_video(array('video' => $video)));
                    if ($video['has_hd']=='yes') 
                        $video['videos']['hq'] = get_hq_video_file($video);
                }
               
                $video['url'] = $video['video_link'] = $video['videoLink'] = videoLink($video);
                $video['avatar'] = $video['user_photo'] = $video['displayPic'] = $userquery->avatar($video);
                $video['avatars']['medium']     = $userquery->avatar($video,'small');
                $video['avatars']['xmedium']    = $userquery->avatar($video,'xmedium');
                $video['avatars']['large']      = $userquery->avatar($video,'large');
                
                $user = array();
                $vid  = array();
               
                if($fetch_user_friends && $video['broadcast'] == 'private' ) 
                { 
                    $vid['contacts'] = $friends_ids;

                }else{
                    $vid['contacts'] = array();
                }

                $temp_video_users = trim($video['video_users']);
                if ( $temp_video_users && !empty($temp_video_users)  && $video['broadcast'] == 'private' ){
                    $video_users = explode(',', $temp_video_users);

                    if (is_array($video_users) && !empty($video_users)){
                        foreach ($video_users as $key => $user) {
                            $user_det = $userquery->get_user_details($user);
                            if (!in_array($user_det['userid'],$vid['contacts']) ){
                                array_push($vid['contacts'],$user_det['userid']);
                            }
                        }
                    }
                }

                #checking for a premium video
                if (function_exists('watch_premium')){
                    $premium = watch_premium($video,true);
                    $vid['watch_premium'] = $premium; 
                }
                
                foreach($fields_arr['video'] as $vid_field)
                {
                    if(isset($video[$vid_field]))
                    {
                        if($vid_field=='files_thumbs_path')
                        {
                            $vid[$vid_field] = $video[$vid_field].'/'.$video['file_directory'].$video['file_name'];
                            
                        }
                        else
                        {
                            $vid[$vid_field] = $video[$vid_field];  
                        }
                    }    
                }
            
                $cats = array();
                if(isset($video['category']))
                {
                    $cats_array = array();
                    $vid_cats = explode('#', $video['category']);
                    foreach($vid_cats as $cat)
                    {
                        if($cat!="" && is_numeric($cat))
                        {
                            $cats_array[] = $cat;
                        }    
                    }
                    $cat_str = implode(',',$cats_array);
                    $query_cat = "select category_id, category_name from ".tbl('video_categories')." Where category_id in ($cat_str)";
                    $cats = db_select($query_cat);
                }

                $video_version = db_select("select video_files,version,video_version from ".tbl('video')." where videoid=".$video['videoid']);

                $video['version']       = $video_version[0]['version'];
                $video['video_version'] = $video_version[0]['video_version'];
                // checkmate
                // $video['video_files']   = $video_version[0]['video_files'];
                
                if($video['has_mobile']=='yes' && $video['version']=='1')
                    $video_files = array( get_mob_video(array('video' => $video)) );    
                else
                    $video_files = get_video_file($video,true,true,true); 

                //get_video_file($vdetails,true,true);
                

                $video_files_new = array();
                $vid_version = 1;
                if(!empty($video_files))
                {
                    foreach($video_files as $file)
                    {
                        $quality = get_quality_new($file);
                        if($quality!="")
                        {
                            if($quality=='hd' || $quality=='sd' || $quality=='m')
                                $vid_version = 1;
                            else
                                $vid_version = 2;

                            $video_files_new[$quality] = $file; 
                        }
                    }    
                } 

                if(empty($video_files_new) )
                    $video_files_new = $video_files;

                if( is_null($video_files) )
                    $video_files_new = array();

                //$video_files = get_video_files_new($video);
                
                // checkmate
                // getting video file for corporate cb
                // $corp_video_file = get_video_files($video);
                // $down_video_files = get_downloadable_files($video);

                //pr($video_files,true);
                //die;

                unset($vid['server_ip']);
               

                $vid['video_link'] = $video['video_link'];
                
                $vid['embed_code'] = unhtmlentities(stripslashes($video['embed_code']));
                //get likes/dislikes of video
                $total_likes_dislikes=get_total_likes_dislikes($video['rating'],$video['rated_by'],10);
                            
                $vid['total_likes']    = (string)$total_likes_dislikes['total_likes'];
                $vid['total_dislikes'] = (string)$total_likes_dislikes['total_dislikes'];
                global $cbvid;
                $logged_in_user = userid();
                $filtered_vids = array();
                if ($logged_in_user) {
                    $video_id = $vid['videoid'];
                    $raw = $cbvid->get_video_rating($video_id);
                    $video_voters = $raw['voter_ids'];
                    $cleaned = json_decode($video_voters,true);
                    
                    foreach ($cleaned as $item => $userNow) {
                        if ($userNow['userid'] == $logged_in_user) {
                            $ratedWhat = $userNow['rating'];
                            $vid['has_liked'] = '0';
                            $vid['has_disliked'] = '0';
                            if ($ratedWhat == 10) {
                                $vid['has_liked'] = '1';
                            } elseif ($ratedWhat == 0) {
                                $vid['has_disliked'] = '1';
                            }
                        }
                    }
                } 

                $video_new = array();
                $video_file_old_version = array();
                // $vid['version']     =  (string)$vid_version;

                // checkmate
                if($video['file_type']=='dash' || $video['file_type']=='hls'){
                    $vid['version']     =  (string)2;
                }else{
                    $vid['version']     =  (string)$vid_version;
                }

                $vid['video_version']     =  (string)$video['video_version'];
                $video_new['video'] =  $vid;
                //$video_new['video']['is_hd']   =  $video['is_hd'];
                $video_new['video']['thumbs']   =  $video['thumbs'];
                
                /*
                // experimenting for corporate video file
                // checkmate
                $video_new['video']['git_api_1']    =  $video_files;
                $video_new['video']['git_api_2']    =  $video_files_new;
                $video_new['video']['corporate_video_file']    =  $corp_video_file;
                $video_new['video']['corporate_downloadable_files']    =  $down_video_files;
                //this line is modified for api
                $video_new['video']['files']    =  $video_files_new; 
                */
                $extcheck = getExt($video_files_new[0]);
                $corp_video_file=array();
                if($extcheck=='mpd' || $extcheck=='m3u8'){
                

                        preg_match_all('!\d+!', $video['video_files'], $res);
                        foreach ($res[0] as $key => $value) {
                     
                            $corp_video_file[$value] = $video_files_new[0];
                            
                        }
                        unset($corp_video_file[0]);
                        
                        $corp_video_file_obj = (object)$corp_video_file;
                        $video_new['video']['files'] = $corp_video_file_obj;

                }else{
                
                        $video_new['video']['files'] = (object)$video_files_new;
                
                }



                $video_new['video_categories']  =  $cats; 

                $video_users = NULL;
                $temp_video_users = trim($video['video_users']);
                if ( $temp_video_users && !empty($temp_video_users) ){
                    $video_users = explode(',', $temp_video_users);
                }
                $video_new['video']['video_users']  =  $video_users;

                /*if(!empty($video_files_new))
                {
                    pr($video_files_new,true);
                    pr($cats,true);
                    die;
                }*/

                $user = format_users($video['userid']);
                $video_new['uploader'] =  $user[0];
                                
                $new_videos[] = $video_new;
            }
           
        }    
        return $new_videos;
    } 

      
    
    function format_users($users,$is_subscribtions=false)
    {
        global $userquery, $cbvid;

        if(!is_array($users) && is_numeric($users))
        {
            $users = $userquery->get_users(array('userid'=>$users));
        }

        if(userid())
            $userid = userid();
        else
            $userid = false; 

        $new_users = array();
        if (!empty($users)) 
        {
            foreach ($users as $user) 
            {
                $user['avatar'] = $userquery->avatar($user);
                
                /*$user['avatars']['small']      =  $userquery->avatar($user,'small');
                $user['avatars']['xmedium']    =  $userquery->avatar($user,'xmedium');
                $user['avatars']['large']      =  $userquery->avatar($user,'large');*/

                
                $user['name'] = $user['fullname'] = name($user);
                if($is_subscribtions)
                    $user['userid'] = $user['subscribed_to']; 

                $user_profile = $userquery->get_user_details_with_profile($user['userid']);
                if(!empty($user_profile))
                {
                    $user['first_name']     =  $user_profile['first_name'];
                    $user['last_name']      =  $user_profile['last_name'];
                    $user['profile_title']  =  cleanString($user_profile['profile_title']);
                    $user['profile_desc']   =  cleanString($user_profile['profile_desc']);
                    $user['about_me']       =  cleanString($user_profile['about_me']);
                    $user['rating']         =  $user_profile['rating'];
                    $user['rated_by']       =  $user_profile['rated_by'];
                }
                else
                {
                    $user['first_name']     =  "";
                    $user['last_name']      =  "";
                    $user['profile_title']  =  "";
                    $user['profile_desc']   =  "";
                    $user['about_me']       =  "";
                    $user['rating']         =  "0";
                    $user['rated_by']       =  "0";
                } 

                global $remove_users_attributes;
                foreach($remove_users_attributes as $index)
                {
                    if(isset($user[$index]))
                    unset($user[$index]);
                }   
                $user['channel_link']  = $userquery->profile_link(array('username'=>$user['username']));

                /*$params_u   =  array('user' => $user['userid'], 'count_only' => "yes");
                $count_upld =  $cbvid->get_videos($params_u);
                $user['total_videos'] = $count_upld;*/

                //get likes/dislikes of user
               
                $total_likes_dislikes = get_total_likes_dislikes($user['rating'],$user['rated_by'],10);
                            
                $user['total_likes']    = (string)$total_likes_dislikes['total_likes'];
                $user['total_dislikes'] = (string)$total_likes_dislikes['total_dislikes'];

                if ($userid) {
                    $voters = $userquery->current_rating($user['userid']);
                    if ($voters['voters']) {
                        $cleaned = json_decode($voters['voters'],true);
                        $ratedWhat = $cleaned[$userid]['rate'];
                        $user['has_liked'] = "0";
                        $user['has_disliked'] = "0";
                        if ($ratedWhat == 10) {
                            $user['has_liked'] = "1";
                        } elseif ($ratedWhat == 0) {
                            $user['has_disliked'] = "1";
                        }
                    }
                }

                if(isset($user['voters']))
                unset($user['voters']);

                if($userid)
                {    
                    $is_subscribed = $userquery->is_subscribed($user['userid'],$userid);
                    if(is_array($is_subscribed))
                    $user['is_subscribed'] = "1";
                    else
                    $user['is_subscribed'] = "0";    
                }

                $new_users[] = $user;
            }
        }
        return $new_users;
    }

    function format_photos($photos, $size="l")
    {
        global $db;
        $new_photos = array();
        if (!empty($photos)) 
        {
            global $cbphoto,$userquery;
            foreach ($photos as $photo) 
            {
                $pid = $photo['photo_id'];

                $ph = $db->select(tbl('photos'),"*"," photo_id = '$pid'");
                $ph = $ph[0];


                $photo_obj['photo']['photo_id']          =  $photo['photo_id'];
                $photo_obj['photo']['photo_key']         =  $photo['photo_key'];
                $photo_obj['photo']['photo_title']       =  $photo['photo_title'];   
                $photo_obj['photo']['photo_description'] =  $photo['photo_description'];
                $photo_obj['photo']['photo_tags']        =  $photo['photo_tags'];
                //$photo_obj['photo']['photo_details']     =  $photo['photo_details'];
                $photo_obj['photo']['date_added']        =  $photo['date_added'];
                $photo_obj['photo']['filename']          =  $photo['filename']; 
                $photo_obj['photo']['ext']               =  $photo['ext'];
                $photo_obj['photo']['collection_id']     =  $photo['collection_id'];

                if(is_null($ph['views'])) 
                    $photo_obj['photo']['photo_views']       =  "0"; 
                else
                    $photo_obj['photo']['photo_views']       =  $ph['views']; 
                
                if(is_null($ph['total_comments'])) 
                    $photo_obj['photo']['total_comments']    =  "0";
                else
                    $photo_obj['photo']['total_comments']    =  $ph['total_comments'];

                $photo_obj['photo']['rated_by']   =  $ph['rated_by']; 
                $photo_obj['photo']['rating']     =  $ph['rating'];   

                $photo_obj['photo']['image_path'] = get_photo(array('details'=>$photo, 'size'=>$size));

                $photo_link = $cbphoto->photo_links($photo,'view_item');
                $photo_obj['photo']['photo_link'] = $photo_link; 

                //get likes/dislikes of photo
                                           
                $total_likes_dislikes = get_total_likes_dislikes($ph['rating'],$ph['rated_by'],10);
               
                $photo_obj['photo']['total_likes']   = (string)$total_likes_dislikes['total_likes'];
                $photo_obj['photo']['total_dislikes']= (string)$total_likes_dislikes['total_dislikes'];
                $photo_obj['photo']['has_liked'] = '0';
                $photo_obj['photo']['has_disliked'] = '0';
                global $cbphoto;
                $logged_in_user = userid();
                $filtered_vids = array();
                if ($logged_in_user) {
                    $photo_id = $photo['photo_id'];
                    $raw = $cbphoto->current_rating($photo_id);
                    $photo_voters = $raw['voters'];
                    $cleaned = json_decode($photo_voters,true);
                    #pex($cleaned,true);
                    foreach ($cleaned as $item => $userNow) {
                        if ($item == $logged_in_user) {
                            $ratedWhat = $userNow['rate'];
                            if ($ratedWhat == 10) {
                                $photo_obj['photo']['has_liked'] = '1';
                            } elseif ($ratedWhat == 0) {
                                $photo_obj['photo']['has_disliked'] = '1';
                            }
                        }
                    }
                } 


                $comments_params['type_id'] = $photo['photo_id'];
                $comments_params['type']    = 'p';

                //$photo_comments = getComments($comments_params);

                $user_info = format_users($photo['userid']); 
                $photo_obj['uploader'] = $user_info[0];

                $new_photos[] = $photo_obj; 
            }
        }
        return $new_photos;
    }
?>