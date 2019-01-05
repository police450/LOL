<?php
  
/**
 * @Author Mohammad Shoaib
 * public api for 'delte'
 * Restfull Api for ClipBucket to let other application access data
 */

class API extends REST
{
    public $data = "";
       
    public function __construct()
    {
      
      parent::__construct();

      //check user authentications
      //$this->check_user_authentication();
    }

    //Public method for access api.
    //This method dynmically call the method based on the query string
    public function processApi()
    {
        $func = strtolower(trim(str_replace("/","",$_REQUEST['mode'])));
        if((int)method_exists($this,$func) > 0)
        {
            $this->$func();
        }  
        else
        {
            $data = array('code' => "404", 'status' => "failure", "msg" => "requested method not available", "data" => array());
            $this->response($this->json($data)); 
        }
      
    // If the method not exist with in this class, response would be "Page not found".
    }
   
    /**
    * deleteVideo
    * this function is used to delete a video
    *
    * @param Integer $videoid
    * 
    * @return
    */ 
    private function deleteVideo()
    {
        try 
        {
            global $cbvid;
            
            $request = $_REQUEST;
           #pex($request,true);
            if(!isset($request['videoid']) || $request['videoid']=="" )
                throw_error_msg("video id not provided");

            if( isset($request['videoid']) && !is_numeric($request['videoid']) )
                throw_error_msg("invalid video id");

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));              
            
            $cbvid->delete_video( (int)$request['videoid'] );

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "video deleted successfully", "data" => array());
                $this->response($this->json($data));
            } 
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }    
    }

    /**
    * deleteFavorite
    * this function is used to remove a video from favorites
    *
    * @param Integer $videoid*
    * 
    * @return
    */ 
    private function deleteFavorite()
    {
        try
        {
            $request = $_REQUEST;

            //check if video id provided
            if( !isset($request['videoid']) || $request['videoid']=="" )
                throw_error_msg("Video Id not provided.");

            if( !is_numeric($request['videoid']) )
                throw_error_msg("Video Id is not numeric.");

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));

            $video_id = mysql_clean($request['videoid']);
            global $cbvid;
            $cbvid->action->remove_favorite($video_id);
            
            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'removed from favorites succesfully', "data" => array());
                $this->response($this->json($data));
            }  
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * deleteUser
    * this function is used to delete a user
    * 
    * @param Integer $userid*
    *
    * @return 
    */ 
    private function deleteUser()
    {
        try 
        {
            $request = $_REQUEST;

            if(!isset($request['userid']) || $request['userid']=="")
                throw_error_msg("user id not provided");
            else if(!is_numeric($request['userid']))
                throw_error_msg("invalid user id");
            else
                $userid = (int)$request['userid'];

            global $userquery;
            $user = $userquery->delete_user($userid);
           
            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'user deleted  successfully', "data" => array());
                $this->response($this->json($data));
            } 
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }    
    }

    // Users End //
    
    // Play List Start //

    /**
    * deletePlaylist
    * this function is used to delete playlist of a user
    *
    * @param Integer $pid*
    * 
    * @return
    */ 
    private function deletePlaylist()
    {
        try
        {
            $request = $_REQUEST;

            if( !isset($request['pid']) || $request['pid']=="" )
                throw_error_msg("playlist id not provided");
            else if( !is_numeric($request['pid']) )
                throw_error_msg('invalid playlist id');    
            else
                $request['pid'] =  (int)$request['pid'];

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));

            global $cbvid;
            $pdetails = $cbvid->action->get_playlist($request['pid']);
            
            if(empty($pdetails))
                throw_error_msg(lang("playlist_not_exist"));
            else if($pdetails['userid']!=userid())
                throw_error_msg(lang("you_dont_hv_permission_del_playlist")); 

            $pid = $cbvid->action->delete_playlist($request['pid']);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'playlist deleted successfully', "data" => array());
                $this->response($this->json($data));
            }    
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * removePlaylistItem
    * this function is used to remove item/video in a playlist 
    *
    * @param Integer $playlist_item_id*
    * 
    * @return
    */ 
    private function removePlaylistItem()
    {
        try
        {
            $request = $_REQUEST;

            if( !isset($request['playlist_item_id']) || $request['playlist_item_id']=="" )
                throw_error_msg("playlist item id not provided");
           
            if( !is_numeric($request['playlist_item_id']) )
                throw_error_msg("invalid playlist item id");

            $id =  (int)$request['playlist_item_id'];

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));

            global $cbvid; 

            $cbvid->action->delete_playlist_item($id);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'item has been removed from playlist successfully', "data" => array());
                $this->response($this->json($data));
            }    
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    // Play List End //

    /**
    * deleteGroup
    * this function is used to delete a group
    *
    * @param Integer $group_id*
    * 
    * @return
    */ 
    private function deleteGroup()
    {
        try
        {
            $request = $_REQUEST;

            if( !isset($request['group_id']) || $request['group_id']=="" )
                throw_error_msg("group id not provided");
           
            if( !is_numeric($request['group_id']) )
                throw_error_msg("invalid group id");

            $id =  (int)$request['group_id'];

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));

            global $cbgroup;

            $cbgroup->delete_group($id);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'group deleted successfully', "data" => array());
                $this->response($this->json($data));
            }    
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }
    
    /**
    * removeVideoFromGroup
    * this function is used to remove video from a group
    *
    * @param Integer $group_id*
    * @param Integer $videoid*
    * 
    * @return
    */ 
    private function removeVideoFromGroup()
    {
        try
        {
            $request = $_REQUEST;

            if(!isset($request['group_id']) || $request['group_id']=="" )
                throw_error_msg('provide group id');
            else if(!is_numeric($request['group_id'])) 
                throw_error_msg('invalid group id'); 
            else
                $gid = (int)$request['group_id'];

            if(!isset($request['videoid']) || $request['videoid']=="" )
                throw_error_msg('provide video id');
            else if(!is_numeric($request['videoid'])) 
                throw_error_msg('invalid video id'); 
            else
                $vid = (int)$request['videoid'];

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));     
            
            global $cbgroup; 
            $id = $cbgroup->remove_group_video($vid,$gid,true);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg())
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'video removed from group', "data" => array());
                $this->response($this->json($data));
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }


    /**
    * deleteGroupTopic
    * this function is used to delete topic of a group
    *
    * @param Integer $topic_id*
    * 
    * @return
    */ 
    private function deleteGroupTopic()
    {
        try
        {
            $request = $_REQUEST;

            if(!isset($request['topic_id']) || $request['topic_id']=="" )
                throw_error_msg('provide topic id');
            else if(!is_numeric($request['topic_id'])) 
                throw_error_msg('invalid topic id'); 
            else
                $tid = (int)$request['topic_id'];

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));     
            
            global $cbgroup; 
            $cbgroup->delete_topic($tid);

            if( msg())
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'group topic deleted', "data" => array());
                $this->response($this->json($data));
            }

            if( error() )
            {
                throw_error_msg(error('single')); 
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * deleteComment
    * this function is used to delete comment
    *
    * @param Integer $comment_id*
    * @param String $type* ['v', 't']
    * 
    * @return
    */ 
    private function deleteComment()
    {
        try
        {
            $request = $_REQUEST;
                //check if video id provided
            if( !isset($request['comment_id']) || $request['comment_id']=="" )
                throw_error_msg("comment id not provided");

            if(!is_numeric($request['comment_id']))
                throw_error_msg("invalid comment id");

            //check if type provided
            if( !isset($request['type']) || $request['type']=="" )
                throw_error_msg("type not provided");

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));

            global $myquery;

            $cdetails = $myquery->get_comment((int)$request['comment_id']);
                            
            if(!$cdetails)
                throw_error_msg("provided comment not exist");
            
            $myquery->delete_comment((int)$request['comment_id'], clean($request['type']));

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg())
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'comments deleted', "data" => array());
                $this->response($this->json($data));
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * deletePhoto
    * this function is used to delete a photo
    *
    * @param Integer $photo_id
    * 
    * @return
    */ 
    private function deletePhoto()
    {
        try 
        {
            global $cbphoto;
            
            $request = $_REQUEST;
            
            if(!isset($request['photo_id']) || $request['photo_id']=="" )
                throw_error_msg("photo id not provided");

            if( !is_numeric($request['photo_id']) )
                throw_error_msg("invalid photo id");

            if(!userid())
                throw_error_msg(lang("you_not_logged_in")); 

            $id = (int)$request['photo_id'];
            $photo = $cbphoto->get_photo($id);

            if(!$photo)
            {
                throw_error_msg(lang("photo_not_exists"));
            }
            else
            {
                if($photo['userid']!=userid())
                    throw_error_msg("You don't have enough permission to delete this photo");
            }  
                
            $cbphoto->delete_photo($id);   
            
            if( error() )
            {
                if(error('single')!="Photo does not exist in this collection")
                {
                    throw_error_msg(error('single')); 
                }    
                else
                {
                    $data = array('code' => "200", 'status' => "success", "msg" => "photo deleted successfully", "data" => array());
                    $this->response($this->json($data));    
                }    
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "photo deleted successfully", "data" => array());
                $this->response($this->json($data));
            } 
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }    
    }

    /**
    * deleteCollection
    * to delete collection
    *
    * @param integer cid*
    *
    * @since 27/08/2014
    *
    */
    private function deleteCollection()
    {
        $request = $_REQUEST;
        global $cbcollection;
        try
        {
            $cid=0;

            if(isset($request['cid']))
            $cid =  trim($request['cid']);

            //check if collection id provided
            if( $cid==0 || !is_numeric($cid) )
                throw_error_msg("collection id not provided.");

            if(!userid())
                throw_error_msg(lang("you_not_logged_in")); 

            $collection = $cbcollection->get_collection($cid);

            if(empty($collection))
                throw_error_msg(lang("collection_not_exists"));

            if( $collection['userid'] != userid() )
                throw_error_msg(lang("cant_perform_action_collect"));

            $cid = mysql_clean($cid);
            $user_id  = userid();
            $response = $cbcollection->delete_collection($cid);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "collection has been deleted", "data" => array());
                 $this->response($this->json($data));
            }  
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }

    private function deleteCollectionItem() {
        $request = $_REQUEST;
        global $cbcollection;
        try
        {
            if( !isset($request['collection_id']) || $request['collection_id']=="" )
                throw_error_msg("Collection ID not provided");

            if( !isset($request['item_id']) || $request['item_id']=="" )
                throw_error_msg("Collection item ID not provided");

            if( !isset($request['collection_type']) || $request['collection_type']=="" )
                throw_error_msg("Collection type not provided");

            $item = $request['item_id'];
            $cid = $request['collection_id'];
            $type = $request['collection_type'];

            switch ($type) {
                case 'video':
                   global $cbvideo;
                   $cbvideo->collection->remove_item($item,$cid);
                    break;

                case 'photo':
                    global $cbphoto;
                    $cbphoto->collection->remove_item($item,$cid);
                    $cbphoto->make_photo_orphan($cid,$item);  
                    break;
                
                default:
                    # code...
                    break;
            }

            if( error() )
            {
                throw_error_msg(error('single')); 
            } else {
                $data = array('code' => "200", 'status' => "success", "msg" => "collection item has been deleted", "data" => array());
                $this->response($this->json($data));
            }        
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }
    }
}

// Initiiate Library
$api = new API;
$api->processApi();

?>