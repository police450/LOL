<?php
  
/**
 * @Author Mohammad Shoaib
 * 
 * Rest full Api for ClipBucket to let other application access data
 */


class API extends REST
{
    public $data = "";
   
    private $max_video_limit =  20;
    private $videos_limit    =  20;
    private $content_limit   =  20;
    private $size = "";
      

    public function __construct()
    {
        parent::__construct();// Init parent contructor
        
        if( isset($_REQUEST['h']) && isset($_REQUEST['w']) )
            $this->photo_size = (int)$_REQUEST['w'].'x'.(int)$_REQUEST['h'];
        else
            $this->photo_size = '320x250';
    }
    
    //Public method for access api.
    //This method dynmically call the method based on the query string
    public function processApi()
    {
        $data = array('404'=>'requested method not available');
        $func = strtolower(trim(str_replace("/","",$_REQUEST['mode'])));
        if((int)method_exists($this,$func) > 0)
        $this->$func();
        else
        $this->response($this->json($data),'404');
        // If the method not exist with in this class, response would be "Page not found".
    }
    
    // Get Categories
    private function getCategories()
    {
        try
        {
            $data = array();

            $request = $_REQUEST;

            if(!isset($request['type']) || $request['type']=="" )
                throw_error_msg("type not provided");

            $type = $request['type'];
            
            $categories = array();   
            switch ($type)
            {
                case "v":
                case "video":
                case "videos":
                default:
                {
                    global $cbvid;
                    $categories = $cbvid->getCbCategories(arraY('indexes_only' => true, 'type'=>'v'));
                }
                break;

                case "u":
                case "user":
                case "users":
                {
                    global $userquery;
                    $categories = $userquery->getCbCategories(arraY('indexes_only' => true));
                }
                break;

                case "g":
                case "group":
                case "groups":
                {
                    global $cbgroup;                       
                    $categories = $cbgroup->getCbCategories(arraY('indexes_only' => true));
                }
                break;

                case "cl":
                case "collection":
                case "collections":
                {
                    global $cbcollection;
                    $categories = $cbcollection->getCbCategories(arraY('indexes_only' => true));
                }
                break;

            }
            
            if(!empty($categories))
            {
                $new_categories = array();
                foreach($categories as $cat)
                {
                    $new_categories[] = $cat; 
                }
                
                $data = array('code' => "200", 'status' => "success", "msg" => "success", "data" => $new_categories);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "success", "data" => array());
                $this->response($this->json($data));    
            }

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }
    
    // Get Videos
    private function getVideos()
    {
        try
        {
            $request = $_REQUEST;

            $blacklist_fields = array(
            'password', 'video_password', 'avcode', 'session'
            );
            if(isset($request['page'])) 
            $page = (int)$request['page'];
            else
            $page = 1;

            $get_limit = create_query_limit($page, $this->videos_limit);

            $request['limit'] = $get_limit;
            
            if (VERSION < 3)
                $request['user'] = $request['userid'];

            //$request['order'] = tbl('video.'.$request['order']);
            $vids = $request['video_id'];
            if ($vids) 
            {
                $vids = explode(',', $vids);
                $request['videoids'] = $vids;
            }
            if($is_mobile)
                $request['has_mobile'] = 'yes';

            global $cbvid;
           
            if(isset($request['sort']))
            {
                switch($request['sort'])
                {
                    case "most_recent":
                    default:
                    {
                        $request['order'] = " date_added DESC ";
                    }
                    break;
                    case "most_viewed":
                    {
                        $request['order'] = " views DESC ";
                        $request['date_span_column'] = 'last_viewed';
                    }
                    break;
                    case "most_viewed":
                    {
                        $request['order'] = " views DESC ";
                    }
                    break;
                    case "featured":
                    {
                        $request['featured'] = "yes";
                    }
                    break;
                    case "top_rated":
                    {
                        $request['order'] = " rating DESC, rated_by DESC";
                    }
                    break;
                    case "most_commented":
                    {
                        $request['order'] = " comments_count DESC";
                    }
                    break;
                }   
            }    
            
            $videos = $cbvid->get_videos($request);
            //header('Content-Type: text/html; charset=utf-8');

            $new_videos = array();
            global $userquery;
            if ($videos) 
            {
                $new_videos = format_videos($videos);
                /*foreach ($videos as $video) 
                {
                    $video['title'] = utf8_encode($video['title']);
                    $video['description'] = utf8_encode($video['description']);
                    $video['thumbs'] = array('default' => get_thumb($video), 'big' => get_thumb($video, 'big'));

                    if (function_exists('get_mob_video')) 
                    {
                        $video['videos'] = array('mobile' => get_mob_video(array('video' => $video)));
                        if (has_hq($video)) 
                            $video['videos']['hq'] = get_hq_video_file($video);
                        
                    }
                    $video['url'] = $video['video_link'] = $video['videoLink'] = videoLink($video);
                    $video['avatar'] = $video['user_photo'] = $video['displayPic'] = $userquery->avatar($video);

                    foreach ($blacklist_fields as $field)
                    unset($video[$field]);

                    $new_videos[] = $video;
                }*/
            }
            //echo $db->db_query;
            //echo json_encode($new_videos);
            if(!empty($new_videos))
            {   
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_videos);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data));    
            }

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    /**
    * getRelatedVideos
    * this function is used to get related videos
    *
    * @param integer $videoid
    * 
    * @return array of video objects 
    */ 
    private function getRelatedVideos()
    {
        try
        {
            $request = $_REQUEST;

            if(!isset($request['videoid']) || $request['videoid']=="" ) 
            {
                throw_error_msg("videoid not provided"); 
            }
            elseif(!is_numeric($request['videoid']))
            {
                throw_error_msg("invalid videoid provided");     
            }    
            else
            {
                $videoid = (int)$request['videoid'];
            }    

            global $cbvid;
            $video = $cbvid->get_videos(array('videoid'=>$videoid));

            if(!isset($video[0]['videoid']))
            {
                throw_error_msg("video does not exist");
            }
            else
            {
                $title = $video[0]['title'];
                $tags  = $video[0]['tags'];
            }    

            $related_videos = $cbvid->get_videos(array('title'=>$title,'tags'=>$tags,
            'exclude'=>$videoid,'show_related'=>'yes','limit'=>12,'order'=>'date_added DESC'));
           
            if(!$related_videos){
                $related_videos  =  $cbvid->get_videos(array('exclude'=>$videoid,'limit'=>12,'order'=>'date_added DESC'));
            }

            if(!empty($related_videos)) 
            {
                $new_videos = format_videos($related_videos);
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_videos);
                $this->response($this->json($data));
            } 
            else 
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }    
    }

    /**
    * search
    * this function is used to search objects like videos,users
    *
    * @param string $type*
    * @param string $query*
    * @param integer $page
    * @param string $limit
    * 
    * @return array 
    */ 
    private function search()
    {
        try
        {
            $request = $_REQUEST;

            $search_types=array('v','video','videos','u','user','users','p','photo','photos','g','group','groups');

            if(!isset($request['type']) || $request['type']=="")
                 throw_error_msg("type not provided");
            else
                $type = $request['type'];

            if( !in_array($type,$search_types) )
               throw_error_msg("invalid type provided"); 
            
            if(!isset($request['query']) || $request['query']=="") 
                throw_error_msg("query not provided"); 
            
            global $cbvid;
                      
            if(isset($request['limit'])) 
                $videos_limit = (int)$request['limit'];
            else
                $videos_limit = $this->videos_limit;
            
            if(isset($request['page']))
                $page = $request['page'];
            else
                $page = 1;

            $get_limit = create_query_limit($page, $videos_limit);

            $chkType = $type;

            if( in_array( $type, array('users','user','u') ) )
                $chkType = 'channels';
            elseif( in_array($type, array('v','video','videos')) )
                $chkType = 'videos';   
            elseif( in_array( $type, array( 'p','photo','photos') ) )   
                $chkType = 'photos';
            elseif( in_array( $type, array( 'g','group','groups') ) )
                $chkType = 'groups';

            isSectionEnabled($chkType,true); 
            
            //inialize search object
            $search = cbsearch::init_search($chkType);
            $search->limit = $get_limit;
            $search->key   = mysql_clean($request['query']);

            $search->date_margin = mysql_clean($request['datemargin']);
            $search->sort_by = mysql_clean($request['sort']);

            $results = $search->search();
           # $results = array_reverse($results);
            if(!empty($results)) 
            {

                if( in_array( $type, array( 'v','video','videos') ) )
                    $new_array = format_videos($results);
                elseif( in_array( $type, array( 'u','user','users') ) )
                    $new_array = format_users($results);
                elseif( in_array( $type, array( 'p','photo','photos') ) )
                    $new_array = format_photos($results);
                elseif( in_array( $type, array( 'g','group','groups') ) )
                {
                    foreach($results as $group)
                    {
                        $array['group'] = array(
                                            'group_id'=>$group['group_id'],
                                            'group_name'=>$group['group_name'],
                                            'group_description'=>$group['group_description'],
                                            'category'=>$group['category'],
                                            'group_privacy'=>$group['group_privacy'],
                                            'active'=>$group['active'],
                                            'date_added'=>$group['date_added'],
                                            'featured'=>$group['featured'],
                                            'total_views'=>$group['total_views'],
                                            'total_videos'=>$group['total_videos'],
                                            'total_members'=>$group['total_members'],
                                            'total_topics'=>$group['total_topics'],
                                            );
                        $uploader = format_users( $group['userid'] );

                        $array['uploader'] = $uploader[0];

                        $new_array[] = $array;

                    }   
                }
                else    
                    $new_array = $results; 

                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_array);
                $this->response($this->json($data));
            } 
            else 
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }    
    }

    // Get Comments
    private function getComments()
    {
        try
        {
            $request = $_REQUEST;
            $params  = array();
            $limit   = config('comments_per_page');

            if(isset($request['page']))
                $page = $request['page'];
            else
                $page = 1;    

            if (!$limit || !is_numeric($limit) || $limit < 1)
            $limit = 20;

            $valid_types=array('v','video','videos','c','channel','channels','p','photo','photos','g','group','groups','t','topic','topics','collection');

            if(!isset($request['type']) || $request['type']=="")
                throw_error_msg("type not provided");

            if( !in_array($request['type'],$valid_types) )
                throw_error_msg("invalid type provided");

            if(!isset($request['type_id']) || $request['type_id']=="")
                throw_error_msg("type_id not provided");

            if(!is_numeric($request['type_id']))
                throw_error_msg("invalid type_id provided");                

            $params['type'] = mysql_clean($request['type']);

            if( in_array( $params['type'],array('u','user','users') ) )
                $params['type'] = 'channel';
            
            if(isset($request['type_id']))
            $params['type_id'] = mysql_clean($request['type_id']);

            if(isset($request['last_update']))
            $params['last_update'] = mysql_clean($request['last_update']);

            $params['limit'] = create_query_limit($page, $limit);

            global $myquery;
            $comments = $myquery->getComments($params);

            $blacklist_fields = array(
            'password', 'video_password', 'avcode', 'session'
            );

            $the_comments = array();

            if ($comments)
            foreach ($comments['comments'] as $comment) 
            {
                if ($comment) 
                {
                    foreach ($blacklist_fields as $field) 
                    {
                        unset($comment[$field]);
                    }

                    $user_id = $comment['userid'];
                    
                    unset($comment['userid']);

                    $new_comment['Comment']  = $comment;
                    $user_info = format_users( get_users(array('userid'=>$user_id)) ); 
                    
                    if(isset($user_info[0]['voters']))
                    unset($user_info[0]['voters']);

                    $new_comment['Uploader'] = $user_info[0];

                    if(!empty($comment['children']['comments']))
                    {
                        $i = 0;
                        foreach($comment['children']['comments'] as $child_comment)
                        {
                            $user_id = $child_comment['userid'];
                    
                            unset($child_comment['userid']);

                            $new_comment['Comment']['Children'][$i]['Comment']  = $child_comment;
                            $user_info = format_users( get_users(array('userid'=>$user_id)) ); 
                            $new_comment['Comment']['Children'][$i]['Uploader'] = $user_info[0];   
                            $i++;
                        }   
                    }
                    else
                    {
                        $new_comment['Comment']['Children'] = array();
                    }   
                    unset( $new_comment['Comment']['children']);

                    //$comment['Comment']

                    $the_comments[] = $new_comment;
                }
            }

            //echo json_encode($the_comments);
            if(!empty($the_comments))
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $the_comments);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }
    
    // Get Fields
    private function getFields()
    {
        try
        {
            global $Upload;
            $groups = $Upload->load_video_fields(null);

            $new_groups = array();
            foreach ($groups as $group) 
            {
                $new_fields = array();
                foreach ($group['fields'] as $field) 
                {
                    // foreach($fields as $field)
                    if ($field)
                    $new_fields[] = $field;
                }

                $group['fields'] = $new_fields;
                $new_groups[] = $group;
            }

            //pr($new_groups,true);
            //echo json_encode($new_groups);
            if(!empty($new_groups))
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_groups);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => "");
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    // Get Playlists
    private function getPlaylists()
    {
        try
        {
            $request = $_REQUEST;

            
            if(isset($request['page']))
                $page = (int)$request['page'];
            else
                $page = 1; 

            $page = mysql_clean($page);

            $playlist_limit = 20;
            $get_limit = create_query_limit($page, $playlist_limit);

            $uid = mysql_clean($request['userid']);
            if (!isset($request['userid']) || !is_numeric($request['userid']))
            {
                $params = array('order' => 'total_items DESC','limit' => $get_limit);
            } 
            else 
            {
                $params = array('user' => $uid,'limit' => $get_limit);
            }

            

            global $cbvid;
            $playlists = $cbvid->action->get_playlists($params);
            
            if (VERSION < 3) 
            {
                $new_playlists = array();
                foreach ($playlists as $playlist) 
                {

                    /*$playlist['total_items'] = $cbvid->action->count_playlist_items($playlist['playlist_id']);
                    if($playlist['first_item']!="" && $playlist['playlist_type']=="v")
                    {
                        $first_item = json_decode($playlist['first_item'],true);
                        $f_item = format_videos(get_videos(array('videoid'=>$first_item['videoid'])));

                        $playlist['first_item'] = $f_item[0];
                    }*/  

                    unset($playlist['first_item']);
                    $last_item = get_playlist_items($playlist['playlist_id'],1);
                    
                    $playlist['last_item'] = array();
                    if(isset($last_item[0]['object_id']))
                    {
                        $last_video = get_videos( array('videoid'=>$last_item[0]['object_id']) );

                        $video = format_videos($last_video);
                        if(isset($video[0]))
                            $playlist['last_item'] = $video[0];  
                            $playlist['last_item']['playlist_item_id'] = $last_item[0]['playlist_item_id'];
                    } 

                    $new_playlists[] = $playlist;
                }
                $playlists = $new_playlists;
            }
            
            if(!empty($playlists))
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $playlists);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    // Get Playlists Items
    private function getPlaylistItems()
    {
        try
        {    
            $request = $_REQUEST;

            if (!isset($request['playlist_id']))
            {
                throw_error_msg("provide playlist id");
            }

            if(isset($request['page']))
                $page = (int)$request['page'];
            else
                $page = 1; 

            $page = mysql_clean($page);

            $playlist_items_limit = 20;
            $get_limit = create_query_limit($page, $playlist_items_limit);

            $pid = mysql_clean($request['playlist_id']);
            global $cbvid;
            $items = $cbvid->get_playlist_items($pid,null,$get_limit);

            $blacklist_fields = array('password', 'video_password', 'avcode', 'session');
            
            global $userquery;
            if (!empty($items))
            {
                $new_videos = array();
                foreach ($items as $video) 
                {
                    $vid_info = format_videos(get_videos( array('videoid'=>$video['object_id']) ) );
                    $vid = $vid_info[0];
                    
                    if(!empty($vid))
                    {
                        foreach ($blacklist_fields as $field)
                        unset($vid[$field]);

                        $vid['playlist_item_id'] = $video['playlist_item_id'];
                        $new_videos[] = $vid;
                    }    
                    
                }
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_videos);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data)); 
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    // Get Configs
    private function getConfigs()
    {
        try
        {
            global $Cbucket;
            $upload_path = BASEURL . '/actions/file_uploader.php';

            //if (function_exists('get_file_uploader_path'))
            //$upload_path = get_file_uploader_path();

            //these lines are temporarily commented
            if(function_exists('getUploadifySwf'))
            {
                global $multi_server;
                $server_api = $multi_server->get_server_api(false,'upload'); //photo_upload
                
                if(isset($server_api['server_api_path']))
                    $upload_path = $server_api['server_api_path'].'/actions/file_uploader.php';
            }            
            $adminConfigs = $Cbucket->configs;
            $adminConfigs['groupsSection'] = 'no';
            $configsCleaned = array();
            foreach ($adminConfigs as $key => $value) {
                if (is_numeric($key)) {
                    continue;
                } else {
                    $configsCleaned[$key] = $value;
                }
            }

            $array = array(
                            'baseurl' => BASEURL,
                            'title' => TITLE,
                            'file_upload_url' => $upload_path,
                            'session' => session_id(),
                            'adminConfigs' => $configsCleaned
                        );

            //echo json_encode($array);
            $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $array);
            $this->response($this->json($data));

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    // Video Flag Options
    private function videoFlagOptions()
    {
        try
        {
            $request = $_REQUEST;
            $type = $request['type'];
            $type = $type ? $type : 'v';

            $flags = get_flag_options($type);

            $new_flags = array();
            for($i=0; $i<count($flags); $i++)
            {
                $new_flags[$i] = array('flag_type_id'=>(string)$i, 'flag_type'=>$flags[$i]);
            }  

            //echo json_encode($flags);
            $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_flags);
            $this->response($this->json($data));

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    // Get Subscribers
    private function getSubscribers()
    {
        try
        {
            $request = $_REQUEST;
            

            if (!isset($request['userid']))
            {
                throw_error_msg("provide user id");
            }

            $uid = (int)$request['userid'];
            
            if(!isset($request['page']))
                $page = 1;
            else
                $page = (int)$request['page'];

            $get_limit = create_query_limit($page, $this->videos_limit);

            $limit = $get_limit;
            
            global $userquery;
            $subscribers = $userquery->get_user_subscribers_detail($uid,$limit);

            $the_subscribers = array();
            if ($subscribers) 
            {
                
                $the_subscribers = format_users($subscribers);
               
                /*foreach ($subscribers as $subscriber) 
                {
                    foreach ($blacklist_fields as $field) 
                    {
                      unset($subscriber[$field]);
                    }
                    $the_subscribers[] = $subscriber;
                }

                $subscribers = $the_subscribers;*/
            }

            if(!empty($the_subscribers))
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $the_subscribers);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => "");
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    // Get Subscriptions
    private function getSubscriptions()
    {
        try
        {
            $request = $_REQUEST;
            $uid = $request['userid'];
            if (!$uid)
            $uid = userid();

            if (!$uid)
            {
                // exit(json_encode(array('err' => lang('Please login'))));
                $data = array('code' => "418", 'status' => "failure", "msg" => "Please Login", "data" => "");
                $this->response($this->json($data));
            }

            if(!isset($request['page']))
                $page = 1;
            else
                $page = (int)$request['page'];

            $get_limit = create_query_limit($page, $this->videos_limit);

            $limit = $get_limit;

            //$limit = '1,1';
            
            global $userquery;
            $subscribers = $userquery->get_user_subscriptions($uid, $limit);
            
            $the_subscribers = array();
            if ($subscribers) 
            {
                $the_subscribers = format_users($subscribers,true);
                /*foreach ($subscribers as $subscriber) 
                {
                    foreach ($blacklist_fields as $field) 
                    {
                        unset($subscriber[$field]);
                    }
                    $the_subscribers[] = $subscriber;
                }
                $subscribers = $the_subscribers;*/
            }

            if(!empty($the_subscribers))
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $the_subscribers);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => "");
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    // Get Favorite Videos
    private function getFavoriteVideos()
    {
        try
        {
            $request = $_REQUEST;

            if( !isset($request['userid']) || $request['userid']=="" )
                throw_error_msg("userid not provided");
            else if(!is_numeric($request['userid']))
                throw_error_msg("invalid user id");    
            else
                $uid =  (int)$request['userid'];

            if(isset($request['page'])) 
            $page = (int)$request['page'];
            else
            $page = 1;

            $get_limit = create_query_limit($page, $this->videos_limit);

            $blacklist_fields = array(
                    'password', 'video_password', 'avcode', 'session'
                );

            global $cbvid;
            $params = array('userid' => $uid, 'limit' => $get_limit);
            $videos = $cbvid->action->get_favorites($params);
               
            global $userquery;
            if (!empty($videos)) 
            {
                $new_videos = array();
                $new_videos = format_videos($videos);
                /*foreach ($videos as $video) 
                {
                    if (!$video['email'])           
                        $udetails = $userquery->get_user_details($video['userid']);
              

                    $video = array_merge($video, $udetails);

                    $video['thumbs'] = array('default' => get_thumb($video));
                    $video['videos'] = array('mobile' => get_mob_video(array('video' => $video)));
                    $video['url'] = $video['video_link'] = $video['videoLink'] = videoLink($video);
                    $video['avatar'] = $video['user_photo'] = $video['displayPic'] = $userquery->avatar($video);

                    foreach ($blacklist_fields as $field)
                    unset($video[$field]);

                    $new_videos[] = $video;
                }*/
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_videos);
                $this->response($this->json($data));
            } 
            else 
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => "");
                $this->response($this->json($data));
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }

    }

    // Get Users
    private function getUsers()
    {
        try
        {
            $request = $_REQUEST;
            
            $page = $request['page'];
            if (!$page || !is_numeric($page) || $page < 1)
                $page = 1;

            $get_limit = create_query_limit($page, $this->videos_limit);

            $request['limit'] = $get_limit;

            if(isset($request['sort']))
            {
                $sort = $request['sort'];

                switch($sort)
                {
                    case "most_recent":
                    default:
                    {
                        $request['order'] = " doj DESC ";
                    }
                    break;
                    case "most_viewed":
                    {
                        $request['order'] = " profile_hits DESC ";
                    }
                    break;
                    case "featured":
                    {
                        $request['featured'] = "yes";
                    }
                    break;
                    case "top_rated":
                    {
                        $request['order'] = " rating DESC";
                    }
                    break;
                    case "most_commented":
                    {
                        $request['order'] = " total_comments DESC";
                    }
                    break;
                }
            }    

            //check if userid or user_id sent
            if( isset($request['userid']) )
            {
                $request['userid'] = (int)$request['userid'];
            }
            
            /* else if( isset($request['user_id']) )
            {
                $request['userid'] = (int)$request['user_id'];
                unset($request['user_id']);   
            } */

            $users = get_users($request);
            
            if(!empty($users))
            {
                $new_users = format_users($users);
                $data = array('code' => "200", 'status' => "success", "msg" => "success", "data" => $new_users);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "success", "data" => array());
                $this->response($this->json($data));    
            }

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    // Get Photos
    private function getPhotos()
    {
        try
        {
            $request = $_REQUEST;

            if(isset($request['page']))
                $page = (int)$request['page'];
            else
                $page = 1;
                        
            if(isset($request['limit']))
                $limit = (int)$request['limit'];
            else
                $limit = $this->videos_limit;

            $get_limit = create_query_limit($page, $limit);

            $request['limit'] = $get_limit;
           
            if (VERSION < 3)
                $request['user'] = $request['userid'];

            if(isset($request['sort']))
            {
                $sort = $request['sort']; 
                //this variable is set to set table for order to get photos
                $table_name = "photos";

                switch($sort)
                {   
                    case "most_recent":
                    default:
                    {
                        $request['order'] = $table_name.".date_added DESC";
                    }
                    break;
                    
                    case "featured":
                    {
                        $request['featured'] = "yes";
                    }
                    break;
                    
                    case "most_viewed":
                    {
                        $request['order'] = $table_name.".views DESC"; 
                    }
                    break;
                    
                    case "most_commented":
                    {
                        $request['order'] = $table_name.".total_comments DESC";
                    }
                    break;
                    
                    case "top_rated":
                    {
                        $request['order'] = $table_name.".rating DESC, ".$table_name.".rated_by DESC";
                    }
                    break;  
                }
            }    

            $request['active'] = 'yes';
            //$request['order'] = tbl('video.'.$request['order']);

            if(isset($request['collection_id']))
            {
                $request['collection'] = (int)$request['collection_id'];
                unset($request['collection_id']);

                $request['order'] = " photo_id DESC";
            }

            if(isset($request['userid']))
            {
                if($request['userid']=="" || !is_numeric($request['userid']))
                    throw_error_msg("userid must be numeric");

                $request['user'] = $request['userid'];
                unset($request['userid']);   
            }

            global $cbphoto, $userquery;
            
            $photos = $cbphoto->get_photos($request);
           
           
            //echo $db->db_query;
            //echo json_encode($new_photos);
            
            if(!empty($photos))
            {
                $new_photos = format_photos($photos, $this->photo_size);
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_photos);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => "");
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    // Get Friends
    private function getFriends()
    {
        $request = $_REQUEST;
        
        $uid = $request['userid'];
        if (!$uid)
            $uid = userid();

        if (!$uid)
        {
            $data = array('code' => "418", 'status' => "failure", "msg" => "Please Login", "data" => "");
            $this->response($this->json($data));
        } 
        
        global $userquery;
        $friends = $userquery->get_contacts($uid);
        
        if(!empty($friends))
        {
            $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $friends);
            $this->response($this->json($data));
        }
        else
        {
            $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => "");
            $this->response($this->json($data));    
        }
    }

    // Get Groups
    private function getGroups()
    {
        try
        {
            $request = $_REQUEST;
            
            if(isset($request['page']))
                $page = (int)$request['page'];
            else
                $page =  1;

            $get_limit = create_query_limit($page,$this->content_limit); 

            $request['limit'] = $get_limit;

            if(isset($request['sort']))
            {
                $sort = $request['sort'];
                switch($sort)
                {
                    case "most_recent":
                    default:
                    {
                        $request['order'] = " date_added DESC ";
                    }
                    break;
                    case "most_viewed":
                    {
                        $request['order'] = " total_views DESC ";
                    }
                    break;
                    case "featured":
                    {
                        $request['featured'] = "yes";
                    }
                    break;
                    case "top_rated":
                    {
                        $request['order'] = " total_members DESC";
                    }
                    break;
                    case "most_commented":
                    {
                        $request['order'] = " total_topics DESC";
                    }
                    break;
                }
            }  

            //check if userid or user_id sent
            if( isset($request['userid']) )
            {
                $request['user'] = (int)$request['userid'];
                unset($request['userid']);
            }
            else if( isset($request['user_id']) )
            {
                $request['user'] = (int)$request['user_id'];
                unset($request['user_id']);   
            }  

            //check if groupid or group_id sent
            if( isset($request['group_id']) )
            {
                $request['group_id'] = (int)$request['group_id'];
            }
            
            if( isset($request['groupid']) )
            {
                throw_error_msg('provide group_id'); 
            } 
           
            global $cbgroup;
            $groups = $cbgroup->get_groups($request);

            if(userid())
                $is_logged_in = userid();
            else
                $is_logged_in = false; 
            
            if(!empty($groups))
            {
                $new_groups = array();
                foreach($groups as $group)
                {
                    $avatar = $cbgroup->get_group_thumb($group);
                    $array['group'] = array(
                                        'group_id'=>$group['group_id'],
                                        'group_name'=>$group['group_name'],
                                        'group_description'=>$group['group_description'],
                                        'category'=>$group['category'],
                                        'group_privacy'=>$group['group_privacy'],
                                        'active'=>$group['active'],
                                        'date_added'=>$group['date_added'],
                                        'featured'=>$group['featured'],
                                        'total_views'=>$group['total_views'],
                                        'total_videos'=>$group['total_videos'],
                                        'total_members'=>$group['total_members'],
                                        'total_topics'=>$group['total_topics'],
                                        'avatar'=>$avatar,
                                        );
                    
                    if($is_logged_in)
                    {    
                        $is_joined = $cbgroup->is_joinable($group,$is_logged_in);
                        
                        $array['group']['is_joined'] = !$is_joined;
                    }

                    $uploader = format_users( get_users(array('userid'=>$group['userid'])) );
                    $array['uploader'] = $uploader[0];
                    $new_groups[] = $array;
                }    
                
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_groups);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }    
    }

    /**
    * getGroupMembers
    * this function is used to get Members in a group
    *
    * @param Integer $group_id*
    *
    * @return
    */ 
    private function getGroupMembers()
    {
        try
        {
            $request = $_REQUEST;

            if( !isset($request['group_id']) || $request['group_id']=="" )
                throw_error_msg("group id not provided");
            else if(!is_numeric($request['group_id']))
                throw_error_msg("invalid group id");    
            else
                $gid =  (int)$request['group_id'];

            if(isset($request['page']))
                $page = (int)$request['page'];
            else
                $page =  1;

            $get_limit = create_query_limit($page,$this->content_limit); 

            global $cbgroup;
            $members = $cbgroup->get_members($gid,"yes");

            if(!empty($members))
            {
                $new_members = array();
            
                $new_members = format_users($members); 
                
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_members);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data));    
            }   
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * getGroupVideos
    * this function is used to get vidoes in a group
    *
    * @param Integer $group_id*
    *
    * @return
    */ 
    private function getGroupVideos()
    {
        try
        {
            $request = $_REQUEST;

            if( !isset($request['group_id']) || $request['group_id']=="" )
                throw_error_msg("group id not provided");
            else if(!is_numeric($request['group_id']))
                throw_error_msg("invalid group id");    
            else
                $gid =  (int)$request['group_id'];

            if(isset($request['page']))
                $page = (int)$request['page'];
            else
                $page =  1;

            $get_limit = create_query_limit($page,$this->content_limit); 

            global $cbgroup;
            $videos = $cbgroup->get_group_videos($gid,NULL,$get_limit);
                    
            if(!empty($videos))
            {
                $new_videos = array();
            
                $new_videos = format_videos($videos, true); 
                
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_videos);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data));    
            }   
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    // Get Collections
    private function getCollections()
    {
        try
        {
            global $cbcollection;
            $request = $_REQUEST;
            
            if(isset($request['page']))
                $page = (int)$request['page'];
            else
                $page =  1;

            $get_limit = create_query_limit($page, $this->content_limit);

            $request['limit'] = $get_limit;
           
            if(isset($request['sort']))
            {
                $sort = $request['sort'];
                switch($sort)
                {
                    case "most_recent":
                    default:
                    {
                        $request['order'] = " date_added DESC";
                    }
                    break;
                    
                    case "featured":
                    {
                        $request['featured'] = "yes";
                    }
                    break;
                    
                    case "most_viewed":
                    {
                        $request['order'] = " views DESC"; 
                    }
                    break;
                    
                    case "most_commented":
                    {
                        $request['order'] = " total_comments DESC";
                    }
                    break;
                    
                    case "most_items":
                    {
                        $request['order'] = " total_objects DESC";
                    }
                    break;  
                }
                unset($request['sort']);
            } 

            if(isset($request['type']))
                $content = $request['type'];
            else
                $content = "";

            switch($content)
            {
                case "videos":
                {
                    $request['type'] = "videos";   
                }
                break;
                
                case "photos":
                {
                    $request['type'] = "photos";   
                }
                default:
                {

                }
                break;
            }

            global $cbcollection;
            if (isset($_GET['userid'])) {
                $request['user'] = $_GET['userid'];
            }
            $collections = $cbcollection->get_collections($request);
           
            if(!empty($collections))
            {
                $new_collections = array();
                foreach($collections as $collect)
                {
                    $array['Collection'] = array(
                                        'collection_id'=>$collect['collection_id'],
                                        'collection_name'=>$collect['collection_name'],
                                        'collection_description'=>$collect['collection_description'],
                                        'thumb'=>$cbcollection->coll_first_thumb($collect),
                                        'category'=>$collect['category'],
                                        'active'=>$collect['active'],
                                        'date_added'=>$collect['date_added'],
                                        'featured'=>$collect['featured'],
                                        'total_views'=>$collect['views'],
                                        'total_objects'=>$collect['total_objects'],
                                        'total_comments'=>$collect['total_comments'],
                                        'rating'=>$collect['rating'],
                                        'rated_by'=>$collect['rated_by'],
                                        'type'=>$collect['type'],
                                        'allow_comments'=>$collect['allow_comments'],
                                        'allow_rating' => $collect['allow_rating']

                                        );

                    $user_info          = format_users( get_users(array('userid'=>$collect['userid'])) );
                    $array['uploader']  = $user_info[0];
                    $new_collections[]  = $array;

                }    
                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_collections);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }     
    }

    // Get Collections
    private function getCollectionItems()
    {
        try
        {
            $request = $_REQUEST;

            if( !isset($request['collection_id']) || $request['collection_id']=="" )
                throw_error_msg("collection id not provided");
            else if(!is_numeric($request['collection_id']))
                throw_error_msg("invalid collection id");    
            else
                $cid =  (int)$request['collection_id'];
            
            if(isset($request['page']))
                $page = (int)$request['page'];
            else
                $page =  1;

            $get_limit = create_query_limit($page, $this->content_limit);

            $order = tbl("collection_items").".ci_id DESC";

            global $cbcollection;

            $cdetails = $cbcollection->get_collection($cid);
           
            if($cdetails)
            {
                $type = $cdetails['type'];

                $new_items = array();
                switch($type)
                {
                    case "videos":
                    case "video":
                    case "v":
                    {
                        global $cbvideo;
                        $items = $cbvideo->collection->get_collection_items_with_details($cid,$order,$get_limit);
                        if($items)
                            $new_items = format_videos($items);
                       
                    }
                    break;

                    case "photos":
                    case "photo":
                    case "p":
                    {
                        global $cbphoto;
                        //$items = $cbphoto->collection->get_collection_items_with_details($cid,$order,$get_limit);
                        //if($items)
                        //    $new_items = format_Photos($items);

                        $params['active']       =  'yes';
                        $params['limit']        =  $get_limit;
                        $params['collection']   =  $cid;
                        $params['order']        =  " photo_id DESC";


                        $photos = $cbphoto->get_photos($params);
                        if(!empty($photos))
                            $new_items = format_Photos($photos, $this->photo_size);
                    
                    }
                    break;
                }

                if(!empty($new_items))
                {
                    $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_items);
                    $this->response($this->json($data));
                }
                else
                {
                    $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                    $this->response($this->json($data));    
                }   
            } 
            else 
            {
                throw_error_msg("collection does not exist");
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }     
    }    

    // Get Topics
    private function getGroupTopics()
    {
        try
        {
            $request = $_REQUEST;
            
            if( !isset($request['group_id']) || $request['group_id']=="" )
                throw_error_msg("group id not provided");
            else if(!is_numeric($request['group_id']))
                throw_error_msg("invalid group id");    
            else
                $gid =  (int)$request['group_id'];
            
            if(isset($request['page']))
                $page = (int)$request['page'];
            else
                $page = 1; 
            
            $page = mysql_clean($page);

            $topics_limit = 20;
            $get_limit = create_query_limit($page, $topics_limit);

            $params = array('group' => $gid, 'limit' => $get_limit);
            global $cbgroup; 
            $topics = $cbgroup->get_group_topics($params);
            
            if(!empty($topics))
            {
                $new_topics = array();
                foreach($topics as $topic)
                {
                    $user_id = $topic['userid'];
                    unset($topic['userid']);
                    $array['Topic']    = $topic;
                    $user_info = format_users(get_users(array('userid'=>$user_id))); 
                    $array['Uploader'] = $user_info[0];
                    $new_topics[]  = $array;
                }    

                $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $new_topics);
                $this->response($this->json($data));
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => array());
                $this->response($this->json($data));    
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    // Get Feeds
    private function getFeeds()
    {
        $request = $_REQUEST;
        
        $page = $request['page'];
        
        if (!$page || !is_numeric($page) || $page < 1)
            $page = 1;

        $id = mysql_clean($request['id']);
        $page = mysql_clean($page);
        $type = mysql_clean($request['type']);
        
        $limit = 20;
        $get_limit = create_query_limit($page, $limit);
        
        $params = array('id' => $id,'limit' => $get_limit,'type'=> $type);

        global $cbfeeds;
        $feeds = array();
        
        $feeds = $cbfeeds->get_feeds($params);
        
        $the_feeds = array();
        
        if(!empty($feeds))
        {
            foreach ($feeds as $feed) 
            {
                $feed['comments'] = json_encode($feed['comments']);
                $the_feeds[] = $feed;                
            }
            //echo json_encode($the_feeds);
            $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $the_feeds);
            $this->response($this->json($data));
        }
        else
        {
            //echo json_encode(array('err' => error()));
            $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => "");
            $this->response($this->json($data));
        }  
    }

    // Get User
    private function getUser()
    {
        $request = $_REQUEST;
        
        $userid = mysql_clean($request['userid']);
        $user = array();
        global $userquery;
        if($userid)
        $user = $userquery->get_user_details_with_profile($userid);
          
        if ($user) 
        {
            $user['avatar'] = $user['user_photo'] = $userquery->avatar($user);
            $user['avatars']['medium']     = $userquery->avatar($user,'medium');
            $user['avatars']['xmedium']    = $userquery->avatar($user,'xmedium');
            $user['avatars']['large']      = $userquery->avatar($user,'large');
            // $user['name'] = name($user);
            //echo json_encode($user);
            $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $user);
            $this->response($this->json($data));
        }
        else
        {
            //echo json_encode(array('err'=>'User does not exist'));
            $data = array('code' => "204", 'status' => "success", "msg" => "No Record Found", "data" => "");
            $this->response($this->json($data));
        }  
    }

    // Home Page
    private function homePage()
    {
        $request = $_REQUEST;
        
        define('API_HOME_PAGE','yes');
        
        global $cbvid,$cbphoto,$userquery;

        //get featured videos    
        $featured_videos = $cbvid->get_videos(array('featured' =>'yes', 'limit' =>10, 'order' =>'featured_date DESC','has_mobile'=>'yes'));
        
        $featured = array();
        if($featured_videos)
            $featured = format_videos($featured_videos);
        
        //get recent videos    
        $recent = array();          
        $recent_videos = get_videos(array("order" =>"date_added DESC", "limit" =>10));
        if($recent_videos)
            $recent = format_videos($recent_videos);


        //get most viewed videos    
        $most_viewed = array(); 
        $most_viewed_videos = get_videos(array("order" =>"views DESC", "limit" =>10));
        if($most_viewed_videos)
            $most_viewed = format_videos($most_viewed_videos);


        //editorspick videos
        $editors_pick = array();
        if(is_installed('editorspick'))
        {
            global $db;
            $editors_pick_videos = $db->select(tbl('editors_picks,video,users'),tbl('editors_picks.*,video.*,users.userid,users.username')," ".tbl('editors_picks').".videoid = ".tbl('video').".videoid AND ".tbl('video.active')." = 'yes' AND ".tbl('video.broadcast')." = 'public' AND ".tbl("video.userid")." = ".tbl("users.userid")." ORDER BY ".tbl('video').".date_added DESC");
            //get_ep_videos();
            
            if(!empty($editors_pick_videos))
            {
                $videoids = array();
                foreach($editors_pick_videos as $video)
                {
                    $videoids[] = $video['videoid'];
                }
            
                $videos = get_videos(array('videoids'=>$videoids,'status'=>'Successful','order'=>'date_added DESC'));
                $editors_pick = format_videos($videos, true);
            } 
        }

        //top 5 playlists
        $playlists = array();
        $result_playlists = get_playlists(array('limit'=>5, 'has_items'=>'yes', 'order'=>'date_added DESC'));
              
        if($result_playlists)
        {            
            foreach($result_playlists as $pl)
            {
                $pl_obj = array();

                $query = "select userid, rated_by from ".tbl('playlists');
                $query.=" Where playlist_id = ".$pl['playlist_id'];
                $user = db_select($query);

                if(isset($user[0]['userid']))
                {
                    $user_info = format_users($user[0]['userid']);
                    $pl_obj['uploader'] = $user_info[0];
                    $pl['rated_by']  =  $user[0]['rated_by'];
                }    
                               
                unset($pl['first_item']);
                $pl_obj['playlist'] = $pl;
               
                //$first_item[] = json_decode($pl['first_item'],true);

                $last_item = get_playlist_items($pl['playlist_id'],1);
                
                $pl_obj['playlist']['last_item'] = array();
                if(isset($last_item[0]['object_id']))
                {
                    $last_video = get_videos( array('videoid'=>$last_item[0]['object_id']) );

                    $video = format_videos($last_video);
                    if(isset($video[0]))
                        $pl_obj['playlist']['last_item'] = $video[0];  
                }    
                
                $playlists[] = $pl_obj; 
            }    
        }    
        
        //get 15 photos
        $photos = array();
        $result_photos = get_photos(array('order'=>' RAND() LIMIT 15 '));
        
        if($result_photos)
        {
            $photos = format_photos($result_photos, $this->photo_size);

            /*$photo_obj = array();
            foreach($result_photos as $photo)
            {
                $photo_obj['photo']['photo_id']          =  $photo['photo_id'];
                $photo_obj['photo']['photo_key']         =  $photo['photo_key'];
                $photo_obj['photo']['photo_title']       =  $photo['photo_title'];   
                $photo_obj['photo']['photo_description'] =  $photo['photo_description'];
                $photo_obj['photo']['photo_tags']        =  $photo['photo_tags'];
                $photo_obj['photo']['photo_details']     =  $photo['photo_details'];
                $photo_obj['photo']['date_added']        =  $photo['date_added'];
                $photo_obj['photo']['filename']          =  $photo['filename']; 
                $photo_obj['photo']['ext']               =  $photo['ext'];
                $photo_obj['photo']['collection_id']     =  $photo['collection_id']; 
                $photo_obj['photo']['photo_views']       =  $photo['photo_views']; 
                $photo_obj['photo']['total_comments']    =  $photo['comments'];
                $photo_obj['photo']['rated_by']          =  $photo['rated_by'];   

                $photo_obj['photo']['image_path'] = get_photo(array('details'=>$photo));

                $photo_link = $cbphoto->photo_links($photo,'view_item');
                $photo_obj['photo']['photo_link'] = $photo_link;  

                $comments_params['type_id'] = $photo['photo_id'];
                $comments_params['type']    = 'p';

                //$photo_comments = getComments($comments_params);
               
                $user_fields = 'userid,username,email,sex,dob,level,usr_status,';
                $user_fields.='featured,ban_status,total_photos,';
                $user_fields.='profile_hits,total_videos,subscribers,total_subscriptions';
                
                $photo_obj['uploader'] = $userquery->get_user_field($photo['userid'],$user_fields); 
                
                $photo_obj['uploader']['avatar'] = $userquery->avatar(array(),'',$photo['userid']);
               
                $photos[] = $photo_obj; 
            }*/
        } 


        /*if(!DEVELOPMENT_MODE)
        {
            //get videos from "Pashto Drama" category    
            $pashto_drama_videos = $cbvid->get_videos(array("category"=>106, "order" =>"date_added DESC", "limit" =>10));

            $pashto_drama = array();
            if($pashto_drama_videos)
                $pashto_drama = format_videos($pashto_drama_videos);

            //get videos from "Pashto Stage Shows" category    
            $pashto_stage_videos = $cbvid->get_videos(array("category"=>141, "order" =>"date_added DESC", "limit" =>10));
            
            $pashto_stage = array();
            if($pashto_stage_videos)
                $pashto_stage = format_videos($pashto_stage_videos);   
        }*/    
        
        
            
        $final_array[] = array( 'type' => 'featured',
                                'name' => 'Featured',
                                'videos' => $featured
                                );

        $final_array[] = array( 'type' => 'editors_pick',
                                'name' => 'Editors Pick',
                                'videos' => $editors_pick
                                );

        $final_array[] = array( 'type' => 'recent_videos',
                                'name' => 'Recent Videos',
                                'videos' => $recent
                                );

        $final_array[] = array( 'type' => 'most_viewed',
                                'name' => 'Most Viewed Videos',
                                'videos' => $most_viewed
                                );

        

        

        /*if(!DEVELOPMENT_MODE)
        {
            $final_array[] = array( 'type' => 'pashto_drama_videos',
                                    'name' => 'Pashto Drama Videos',
                                    'videos' => $pashto_drama
                                    );

            $final_array[] = array( 'type' => 'stage_show_videos',
                                'name' => 'Stage Show Videos',
                                'videos' => $pashto_stage
                                );
        }*/

        $final_array[] = array( 'type' => 'play_lists',
                                'name' => 'Playlists',
                                'Playlists' => $playlists
                                );

        $final_array[] = array( 'type' => 'photos',
                                'name' => 'Photos',
                                'Photos' => $photos
                                );



        $data = array('code' => "200", 'status' => "success", "msg" => "Success", "data" => $final_array);
        $this->response($this->json($data)); 
    }


    public function get_user_stream_configs()
    {
        $params = $_REQUEST;
        global $wowza,$cblive,$userquery;
        try{

            if (!$params['userid']){
                throw new Exception("please provide userid");
            }
           

            $userid = (int)$params['userid'];

            $streaming_configs = $cblive->get_live_channels(array("userid"=>$userid));

            if (!$streaming_configs){
                throw new Exception("No Configs found for this user");
            }else{
                $streaming_configs = $streaming_configs[0];
            }

            $data = array(
                        'code' => "200", 
                        'status' => "success", 
                        "msg" => 'Successs', 
                        "data" => $streaming_configs
                    );
            $this->response($this->json($data));

        }catch(Exception $e){
            $this->getExceptionDelete($e->getMessage());
        }
    }


    public function is_user_live()
    {
        $params = $_REQUEST;
        global $wowza,$cblive,$userquery;
        try{

            if (!$params['username']){
                throw new Exception("please provide username");
            }

            if (!$params['userid']){
                throw new Exception("please provide userid");
            }

            $userid = $params["userid"];
            $username = $params["username"];
           
            if ((isset($wowza)) and ($wowza instanceof CB_wowza)){

                $application = $cblive->get_live_channels(array("userid"=>$userid));
                if ($application){
                    $application = $application[0];
                    $live = $wowza->stream($application["channel_name"],$username);
                    $live["live"] = "yes";
                    $live["stream"] =$wowza->build_upstream_url($application["channel_name"],$username,"hls");
                    $live["extension"] = "m3u8";
                
                    $this->response($this->json($live));
                }else{
                    throw new Exception("No Live channel found for this user");
                }
            }else{
                throw new Exception("Live Module is not installed");
            }

        }catch(Exception $e){
            $this->getExceptionDelete($e->getMessage());
        }
    }
}

// Initiiate Library
$api = new API;
$api->processApi();

/*
<iframe width="300" height="250" src="http://demo.clipbucket.com//player/embed_player.php?vid=1099&width=300&height=250&autoplay=yes" frameborder="0" allowfullscreen></iframe>
*/

?>