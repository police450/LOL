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

    public function __construct()
    {
        parent::__construct();// Init parent contructor
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
    
    /**
    * signup
    * user signup
    *
    * @first_name* string
    * @last_name* string
    * @username* string
    * @email* string
    * @password* string
    * @cpassword* string
    * @gender* string (male/female)
    * @dob* date (Y-m-d)
    * @agree* string (yes)
    * @category* string (e.g; 1,2,3) 
    * @avatar file (jpeg, png) 
    *
    * @return array 
    */
    function signup()
    {
        #exit("NOW");
        try
        {
            $request = $_REQUEST;
            global $userquery;
           #pex($request,true);
            if(userid())
            {
                throw_error_msg("you have already logged in");   
            }  

            if( !isset($request['username']) || $request['username']=="" )
                throw_error_msg("username not provided");

            if( !isset($request['email']) || $request['email']=="" )
                throw_error_msg("email not provided");

            if( !isset($request['password']) || $request['password']=="" )
                throw_error_msg("password not provided");

            if( !isset($request['cpassword']) || $request['cpassword']=="" ) {
                #throw_error_msg("confirm password not provided");
                $request['cpassword'] = $request['password'];
            }

            if($request['password']!=$request['cpassword'])
                throw_error_msg("password and confirm password not matched");

            if( !isset($request['category']) || $request['category']=="" || !is_numeric($request['category']) ) {
                global $cbvid;
                #throw_error_msg("category not provided.");
                $request['category'] = $cbvid->get_default_cid();
            }

            if( !isset($request['country']) || $request['country']=="" ) {
                #throw_error_msg("country not provided");
                $request['country'] = 'Pakistan';
            }

            if( !isset($request['gender']) || $request['gender']=="" ) {
                #throw_error_msg("gender not provided");
                $request['gender'] = 'Male';
            }

            if( !in_array($request['gender'], array('Male', 'Female')) )
                throw_error_msg("gender must be Male/Female");

            if( !isset($request['dob']) || $request['dob']=="" ) {
                #throw_error_msg("dob not provided");
                $request['dob'] = '1990-11-18';
            }

            $is_valid_date = DateTime::createFromFormat('Y-m-d', $request['dob']);
            if(!$is_valid_date)
                throw_error_msg("dob must be in Y-m-d format like 1990-11-18");

            if( !isset($request['agree']) || $request['agree']!="yes" ) {
                $request['agree'] = 'yes';
                #throw_error_msg("Sorry, you need to agree to the terms of use and privacy policy to create an account");
            }

            $request['email'] = mysql_clean($request['email']);

            $request['api'] = true;
            #exit("RUN BIT");
            $signup = $userquery->signup_user($request,true);

            if(error())
            {
                throw_error_msg(error('single'));
            }

            $data = array('code' => "200", 'status' => "success", "msg" => 'success', "data" => "please check your mail to activate the account");
            $this->response($this->json($data));
    
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * signupWithSocialNetworks
    * user signup
    *
    * @soclid* string
    * @social_account_id* string
    * @username* string
    * @email* string
    *
    * @return array 
    */
    private function signupWithSocialNetworks()
    {
        try
        {
            $request = $_POST;
            #pex($request,true);

            global $cbplugin, $userquery, $db;

            if(userid())
            {
                $user_info = format_users(userid());
                $data = array('code'=>"200",'status'=>"success","msg"=>"you are already logged in","data"=>$user_info[0]);

                $this->response($this->json($data));
                throw_error_msg('You are already logged in '.userid());
            }

            if( !$cbplugin->is_installed('adv_social_connect.php') )
                throw_error_msg('Advances Social Connect plugin must be installed on your system');

            $network_array = array('fb','tw','lnk','gm');
            if( !isset($request['network']) || trim($request['network'])=="")
                throw_error_msg('network not provided');

            if( !in_array($request['network'], $network_array))
                throw_error_msg('invalid network');

            $request['soclid'] = $request['network'];
            unset($request['network']);

            if( !isset($request['social_ac_id']) || $request['social_ac_id']=="" )
            {
                throw_error_msg('social_ac_id not provided');
            }
            else
            {
                $request['social_account_id'] = $request['social_ac_id'];
                unset($request['social_ac_id']);
            }
                
            if(!isset($request['email']))
                throw_error_msg('email not provided');

            if(!isset($request['username']) || $request['username']=="")
                throw_error_msg('username not provided');


            $cond = "social_account_id='".$request['social_account_id']."' AND soclid='".$request['soclid']."'";
            $results = $db->select(tbl("users"),"*",$cond);

            if(!empty($results))
            {
                $user_info = format_users($results);
                #exit("ASDas");
                $userquery->login_as_user($user_info[0]['userid']);
                #pex($sess,true);
                $data = array('code'=>"200",'status'=>"success","msg"=>"success","data"=>$user_info[0]);
                echo json_encode($data,JSON_PRETTY_PRINT);
                exit;
                #$this->response($this->json($data));   
            }    

            $request['api'] = 'yes';

            $user_id = social_login_pre($request);

            if( error() )
            {
                throw_error_msg(error('single'));
            }
            else
            { 
                $cond = "social_account_id='".$request['social_account_id']."' AND soclid='".$request['soclid']."'";
                $results = $db->select(tbl("users"),"*",$cond);

                if(!empty($results))
                {
                    $user_info = format_users($results);
                    $userquery->login_as_user($user_info[0]['userid']);

                    $data = array('code'=>"200",'status'=>"success","msg"=>"success","data"=>$user_info[0]);
                    $this->response($this->json($data));   
                } 
            }

            /*if ( !empty($results) )
            {
                $userid = $results[0]['userid'];
                $user_info = format_users($userid);
                $data = array('code'=>"200",'status'=>"success","msg"=>"success","data"=>$user_info);
                $this->response($this->json($data));
            }
            else
            {
                if(!isset($request['username']))
                    throw_error_msg('provide username');

                $userid = $userquery->signup_user($request,true);

                if( error() )
                {
                    throw_error_msg(error('single'));
                }
                else
                { 
                    //update avatar_url if provide
                    if(isset($request['avatar_url']))
                        add_social_avatar( $request['username'], $social_account_id, $request['avatar_url'] );

                    //update network and social_account_id
                    add_soclid( $network, $social_account_id, $request['username'] );

                    $user_info = format_users($userid);
                    $data = array('code'=>"200",'status'=>"success","msg"=>"success","data"=>$user_info);
                    $this->response($this->json($data));
                }
            }*/
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * forgotPassword
    * this function is used to log in a user
    *
    * @param String $username*
    * @param String $password*
    * 
    * @return
    */ 
    private function forgotPassword()
    {
        try
        {
            $request = $_POST;

            if( !isset($request['username']) || $request['username']=="" )
                throw_error_msg("user name not provided");

            global $userquery; 
            $userquery->reset_password(1,$request['username']);

            if(error())
            {
                throw_error_msg(error('single'));
            }
            else
            {                
                $data = array('code' => "200", 'status' => "success", "msg" => 'success', "data" => "An Email Has Been Sent To You. Please Follow the Instructions there to Reset Your Password");
                $this->response($this->json($data));
            }

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }
    
    /**
    * login
    * this function is used to log in a user
    *
    * @param String $username*
    * @param String $password*
    * 
    * @return
    */ 
    private function login($array = false)
    {
        try
        {   
            global $userquery;
            if ($array) {
                $request = $array;
            } else {
                $request = $_POST;
            }

            if(userid())
            {
                $user = get_users(array('userid'=>userid()));
                $new_user = format_users($user);
                $new_user = $new_user[0];
                $new_user['sess_id'] = $_COOKIE['PHPSESSID'];
                $new_user['permissions'] = $userquery->get_level_permissions($new_user['level']);
                $data = array('code' => "200", 'status' => "success", "msg" => 'success', "data" => $new_user);
                $this->response($this->json($data));
            }

            if( !isset($request['username']) || $request['username']=="" )
                throw_error_msg("user name not provided");

            if( !isset($request['password']) || $request['password']=="" )
                throw_error_msg("password not provided");           
            
            /**
            * Function used to call all signup functions
            */
            //$onLogin = 'onLoginMobile';
            /*if(!userid() && cb_get_functions('signup_page') )
            {
                if(!DEVELOPMENT_MODE)
                {
                    $_POST['mobile_auth'] = 'yes';
                    cb_call_functions('signup_page');
                }    
            }*/
            
            global $userquery; 
            $username = $request['username'];
            $password = $request['password'];
            #pex($_REQUEST,true);
            $userquery->login_user($username,$password,true);
            $userid = $userquery->userid;
            if (!empty($userid)) {
                $userNow = (int)$userid;
                $user = get_users(array('userid'=>$userNow));
                $new_user = format_users($user);
                $new_user = $new_user[0];
                $new_user['permissions'] = $userquery->get_level_permissions($new_user['level']);
              #  $new_user['avatar'] = str_replace('/', '/', $new_user['avatar']);
                $data = array('code' => "200", 'status' => "success", "msg" => 'success', "data" => $new_user);
                echo json_encode($data,JSON_PRETTY_PRINT);
                return false;
            }

            if( error() ) //&& error('single')!=lang( 'you_already_logged' )
            {
                throw_error_msg(error('single'));
            }
            else
            {
                #pr($_COOKIE['PHPSESSID'],true);
                $user = get_users( array('userid'=>$userquery->userid) );
                $new_user = format_users($user);
                $new_user = $new_user[0];
                $new_user['sess_id'] = $_COOKIE['PHPSESSID'];
                $new_user['permissions'] = $userquery->get_level_permissions($new_user['level']);
                $data = array('code' => "200", 'status' => "success", "msg" => 'success', "data" => $new_user);
                $this->response($this->json($data));
            }

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * logout
    * this function is used to log out a user
    *
    * 
    * @return
    */ 
    private function logout()
    {
        try
        {
            global $userquery;

            
            if(!userid())
            {
                $logout_response['logged_out'] = 0;
                $data = array('code' => "200", 'status' => "success", "msg" => 'you are not logged in', "data" => $logout_response);
                $this->response($this->json($data)); 
            }

            $userquery->logout();
            if(cb_get_functions('logout')) 
                cb_call_functions('logout'); 
            
            setcookie('is_logout','yes',time()+3600,'/');

            $logout_response['logged_out'] = 1;
            $data = array('code' => "200", 'status' => "success", "msg" => 'logout successfully', "data" => $logout_response);
            $this->response($this->json($data));

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * changeAvatar
    * this function is used to change user avatar
    *
    * @param File $avatar_file*
    * 
    * @return
    */ 
    private function changeAvatar()
    {
        try
        {
            global $userquery; 

            if(!userid())
                throw_error_msg("Please login to perform this action");

            if(!isset($_FILES['avatar_file']['name']))
                throw_error_msg("provide avatar_file");

            $array['userid'] = userid();
            $userquery->update_user_avatar_bg($array);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }
            else
            {
                $user_info = format_users($array['userid']);
                $data = array('code' => "204", 'status' => "success", "msg" => "success", "data" =>  $user_info);
                $this->response($this->json($data));     
            }  

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * changePassword
    * this function is used to change user's password
    *
    * @param String $old_pass*
    * @param String $new_pass*
    * @param String $c_new_pass*
    * 
    * @return
    */ 
    private function changePassword()
    {
        try
        {
            global $userquery; 

            $request = $_REQUEST;

            if(!userid())
                throw_error_msg("Please login to perform this action");

            if(!isset($request['old_pass']) || $request['old_pass']=="")
                throw_error_msg("provide old_pass");

            //new_pass
            if(!isset($request['new_pass']) || $request['new_pass']=="")
                throw_error_msg("provide new_pass");

            //c_new_pass
            if(!isset($request['c_new_pass']) || $request['c_new_pass']=="")
                throw_error_msg("provide c_new_pass");

            if($request['c_new_pass']!=$request['c_new_pass'])
                throw_error_msg("new password and confirm password do not match");

            $request['userid'] = userid();
            $userquery->change_password($request);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "success", "data" =>  "password has been changed successfully");
                $this->response($this->json($data));     
            }  

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * changeEmail
    * this function is used to change user's email
    *
    * @param String $new_email*
    * @param String $cnew_email*
    * 
    * @return
    */ 
    private function changeEmail()
    {
        try
        {
            global $userquery; 

            $request = $_REQUEST;

            if(!userid())
                throw_error_msg("Please login to perform this action");

            if(!isset($request['new_email']) || $request['new_email']=="")
                throw_error_msg("provide new_email");

            if(!isset($request['cnew_email']) || $request['cnew_email']=="")
                throw_error_msg("provide cnew_email");

            if($request['new_email']!=$request['cnew_email'])
                throw_error_msg("new email and confirm email do not match");

            $request['userid'] = userid();
            $userquery->change_email($request);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "success", "data" =>  "email has been changed successfully");
                $this->response($this->json($data));     
            }  

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * editAccount
    * this function is used to update user's some basic information
    *
    * @param String $country*
    * @param String $sex*
    * @param Date(Y-m-d) $dob*
    * @param String $country*
    * @param Integer $category*
    * 
    * @return
    */ 
    private function editAccount()
    {
        try
        {
            global $userquery; 

            $request = $_REQUEST;

            if(!userid())
                throw_error_msg("Please login to perform this action");

            //country
            if(!isset($request['country']) || $request['country']=="")
                throw_error_msg("provide country");

            //sex
            if(!isset($request['sex']) || $request['sex']=="")
                throw_error_msg("provide sex");

            if(!in_array($request['sex'], array('male','female')))
                throw_error_msg("sex must be male/female");

            //dob
            if(!isset($request['dob']) || $request['dob']=="")
                throw_error_msg("provide dob");

            if(!isset($request['dob']) || $request['dob']=="")
                throw_error_msg("provide dob");

            $is_valid_date = DateTime::createFromFormat('Y-m-d', $request['dob']);

            if(!$is_valid_date)
                throw_error_msg("dob must be in Y-m-d like 1990-11-18 format");

            if(!isset($request['category']) || $request['category']=="")
                throw_error_msg("provide category");

            $request['userid'] = userid();
            $userquery->update_user($request);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }
            else
            {
                $user_info = format_users($request['userid']);
                
                $data = array('code' => "204", 'status' => "success", "msg" => "success", "data" =>  $user_info);
                $this->response($this->json($data));        
            }  

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * rateIt
    * this function is used to rate the object like video,photo,collection
    *
    * @param String $type*
    * @param Integer $id*
    * @param Integer $rating*
    *
    * @return
    */
    private function rateIt()
    {
        try
        {
            $request = $_POST;

            $types = array('videos','video','v','photos','photo','p','collections','collection','cl','users','user','u');

            //check if type sent
            if( !isset($request['type']) || $request['type']=="" )
                throw_error_msg("type not provided");

            //check valid type
            if(!in_array($request['type'], $types))
                throw_error_msg("invalid type");

            //check id 
            if( !isset($request['id']) || $request['id']=="" )
                throw_error_msg("id not provided");            

            //check valid id 
            if( !is_numeric($request['id']) )
                throw_error_msg("invalid id");            

            //check rating 
            if( !isset($request['rating']) || $request['rating']=="" )
                throw_error_msg("rating not provided");            

            //check valid rating 
            if( !is_numeric($request['rating']) )
                throw_error_msg("invalid rating");

            $type   =  mysql_clean($request['type']);
            $id     =  mysql_clean($request['id']);
            $rating =  mysql_clean($request['rating']);
            
            switch($type)
            {
                case "videos":
                case "video":
                case "v":
                {
                    global $cbvid;
                    $rating = $rating*2;
                    $result = $cbvid->rate_video($id,$rating);
                }
                break;

                case "photos":
                case "photo":
                case "p":
                {
                    global $cbphoto;
                    $rating = $rating*2;
                    $result = $cbphoto->rate_photo($id,$rating);
                }
                break;

                case "collections":
                case "collection":
                case "cl":
                {
                    global $cbcollection;
                    $rating = $rating*2;
                    $result = $cbcollection->rate_collection($id,$rating);
                }
                break;

                case "users":
                case "user":
                case "u":
                {
                    global $userquery;
                    $rating = $rating*2;
                    $result = $userquery->rate_user($id,$rating);
                }
                break;
            }

            if( error() )
            {
                throw_error_msg(error('single')); 
            }
            else
            {
                $data = array('code' => "204", 'status' => "success", "msg" => "success", "data" => $result);
                $this->response($this->json($data));     
            }    

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }

    }

    /**
    * reportIt
    * this function is used to report the object like video,photo,collection
    *
    * @param String $flag_type*
    * @param Integer $type*
    * @param Integer $id*
    *
    * @return
    */
    private function reportIt()
    {
        try
        {
            $request = $_POST;

            $types = array('videos','video','v','photos','photo','p','collections','collection','cl','users','user','u','groups','group','g');

            //check if type sent
            if( !isset($request['type']) || $request['type']=="" )
                throw_error_msg("type not provided");

            //check valid type
            if(!in_array($request['type'], $types))
                throw_error_msg("invalid type");

            //check id 
            if( !isset($request['id']) || $request['id']=="" )
                throw_error_msg("id not provided");            

            //check valid id 
            if( !is_numeric($request['id']) )
                throw_error_msg("invalid id");

            if( !is_numeric($request['flag_type']) )
                throw_error_msg("flag type not provided");                
                            
            $type = strtolower($request['type']);
            $id = $request['id'];
            switch($type)
            {
                case 'v':
                case 'video':
                case 'videos':
                default:
                {
                    global $cbvideo;    
                    $cbvideo->action->report_it($id);
                }
                break;

                case 'g':
                case 'group':
                case 'groups':
                {
                    global $cbgroup;
                    $cbgroup->action->report_it($id);
                }
                break;

                case 'u':
                case 'user':
                case 'users':
                {
                    global $userquery;
                    $userquery->action->report_it($id);
                }
                break;

                case 'p':
                case 'photo':
                case 'photos':
                {
                    global $cbphoto;
                    $cbphoto->action->report_it($id);
                }
                break;

                case "cl":
                case "collection":
                case "collections":
                {
                    global $cbcollection;
                    $cbcollection->action->report_it($id);
                }
                break;

            }

            if( error() )
            {
                throw_error_msg(error('single')); 
            }
            else
            {
                $msg = msg_list();
                $data = array('code' => "204", 'status' => "success", "msg" => "success", "data" => $msg[0]);
                $this->response($this->json($data));     
            }    

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }

    }


    /**
    * spamComment
    * this function is used to spam a comment
    *
    * @param Integer $cid*
    * @param Integer $cid*
    *
    * @return
    */
    private function spamComment()
    {
        try
        {
            global $myquery;

            $types = array('v','p','cl','t','u');

            $request = $_POST;

            if(!isset($request['cid']) || $request['cid']=="")
                throw_error_msg("cid not provided"); 
                
            if( !is_numeric($request['cid']) )
                throw_error_msg("invalid cid provided"); 

            if(!isset($request['type']) || $request['type']=="" )
                throw_error_msg("type not provided");

            if(!in_array($request['type'], $types))
                throw_error_msg("invalid type provided.");                

            $cid = $request['cid'];
            $myquery->spam_comment($cid);
            
            
            if($request['type'] != 't' && isset($request['typeid']))
            {
                $type = $requets['type'];
                $typeid = mysql_clean();
                update_last_commented($type,$typeid);   
            }
            

            if( error() )
            {
                throw_error_msg(error('single')); 
            }
            else
            {
                $msg = msg_list();
                $data = array('code' => "204", 'status' => "success", "msg" => "success", "data" => $msg[0]);
                $this->response($this->json($data));     
            }   
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage()); 
        }

    }

    /**
    * addFavorite
    * this function is used to add a video in favorites
    *
    * @param Integer $videoid*
    * 
    * @return
    */ 
    private function addFavorite()
    {
        try
        {
            $request = $_POST;

            if (!isset($request['type'])) {
                throw_error_msg("Type not provided");
            }

            if ($request['type'] == 'video' || $request['type'] == 'collection') {
                //check if video id provided
                if( !isset($request['type_id']) || $request['type_id']=="" )
                    throw_error_msg("type_id not provided.");

                if( !is_numeric($request['type_id']) )
                    throw_error_msg("invalid type_id");

                $type_id = mysql_clean($request['type_id']);
                if ($request['type'] == 'video') {
                    global $cbvid;
                    $cbvid->action->add_to_fav($type_id);
                }

                if ($request['type'] == 'collection') {
                    global $cbcollection;
                    $cbcollection->action->add_to_fav($type_id);
                }
                
            }
            
            if( error() )
            {
                throw_error_msg(error('single')); 
            }
            else
            {    
                $data = array('code' => "200", 'status' => "success", "msg" => 'added to favorites', "data" => array());
                $this->response($this->json($data));
            }  

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * 
    * this function is used to subscribe a user
    *
    * @param Integer $subscribed_to*
    * 
    * @return
    */ 
    private function subscribeUser()
    {
        try
        {
            $request = $_POST;

            $uid = userid();

            if (!$uid)
               throw_error_msg( lang("you_not_logged_in") ) ;

            if( !isset($request['subscribed_to']) || $request['subscribed_to']=="" )
                throw_error_msg("subscribed to not provided");

            if( !is_numeric($request['subscribed_to']) )
                throw_error_msg("invalid subscribed to");

            global $userquery;
            $userquery->subscribe_user($request['subscribed_to']);
            
            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'subscribed successfully', "data" => array());
                $this->response($this->json($data));
            }  

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * unsubscribeUser
    * this function is used to unsubscribe a user
    *
    * @param Integer $subscribed_to*
    * 
    * @return
    */ 
    private function unsubscribeUser()
    {
        try
        {
            $request = $_POST;

            $uid = userid();

            if (!$uid)
               throw_error_msg( lang("you_not_logged_in") ) ;

            if( !isset($request['subscribed_to']) || $request['subscribed_to']=="" )
                throw_error_msg("subscribed to not provided");

            if( !is_numeric($request['subscribed_to']) )
                throw_error_msg("invalid subscribed to");

            global $userquery;
            $userquery->remove_subscription($request['subscribed_to']);
            
            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'unsubscribed successfully', "data" => array());
                $this->response($this->json($data));
            }  

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }


    /**
    * isSubscribed
    * this function is used to check if a user is subscribe to a user
    *
    * @param Integer $userid*
    * 
    * @return
    */ 
    private function isSubscribed()
    {
        try
        {
            $request = $_POST;

            $uid = userid();

            if (!$uid)
               throw_error_msg( lang("you_not_logged_in") ) ;

            if( !isset($request['userid']) || $request['userid']=="" )
                throw_error_msg("userid not provided");

            if( !is_numeric($request['userid']) )
                throw_error_msg("invalid userid");

            global $userquery;
            $is_subscribed = $userquery->is_subscribed($request['userid'],$uid);
            
            if(!$is_subscribed)
            {
                throw_error_msg("user is not subscribed"); 
            }
            else
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'user is subscribed', "data" => $is_subscribed);
                $this->response($this->json($data));
            }    
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * addGroupTopic
    * this function is used to add topic on a group
    *
    * @param Integer $group_id*
    * @param String $topic_title*
    * @param String $topic_post*
    * 
    * @return
    */ 
    private function addGroupTopic()
    {
        try
        {
            $request = $_POST;

            if(!isset($request['group_id']) || $request['group_id']=="" )
                throw_error_msg('provide group id');
            else if(!is_numeric($request['group_id'])) 
                throw_error_msg('invalid group id'); 
            else
                $gid = (int)$request['group_id'];

            if(!isset($request['topic_title']) || $request['topic_title']=="" )
                throw_error_msg('provide topic title');

            if(!isset($request['topic_post']) || $request['topic_post']=="" )
                throw_error_msg('provide topic post');

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));     
         
            global $cbgroup; 
            $cbgroup->add_topic($request);

            if( msg())
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'group topic added', "data" => array());
                $this->response($this->json($data));
            }

            if( error() )
            {
                throw_error(error('single')); 
            }
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * addComment
    * this function is used to add comment
    *
    * @param Integer $type_id*
    * @param String $comment*
    * @param String $type* ['v', 't', 'cl', 'photo']
    * 
    * @return
    */ 
    private function addComment()
    {
        try
        {
            $request = $_POST;
            global $myquery;
            
            $valid_types = array('v','video','videos','t','topic','topics','cl','collection','collections','p','photo','photos');

            //check if video id provided
            if( !isset($request['type_id']) || $request['type_id']=="" )
                throw_error_msg("type id not provided");

            if(!is_numeric($request['type_id']))
                throw_error_msg("invalid type id");

            //check if comment provided
            if( !isset($request['comment']) || $request['comment']=="" )
                throw_error_msg("comment not provided");

            //check if type provided
            if( !isset($request['type']) || $request['type']=="" )
                throw_error_msg("type not provided");

            //check if type provided
            if( !in_array($request['type'], $valid_types) )
                throw_error_msg("invalid type provided");

            $type = $request['type'];

            //check if reply_to provided
            $reply_to = NULL;
            if( isset($request['reply_to']) && $request['reply_to'] !="" && is_numeric($request['reply_to']) )
                $reply_to = $request['reply_to'];

            if ( in_array($type, array('v','video','videos') ) ) 
            {
                global $cbvid;
                $result = $cbvid->add_comment( clean($request['comment']), (int)$request['type_id'], $reply_to);
            }
            elseif ( in_array($type, array('t','topic','topics') ) ) 
            {
                global $cbgroup;
                $result = $cbgroup->add_comment( clean($request['comment']), (int)$request['type_id'], $reply_to);
            }
            elseif ( in_array($type, array('cl','collection','collections') ) ) 
            {
                global $cbcollection;
                $result = $cbcollection->add_comment(clean($request['comment']),(int)$request['type_id'],$reply_to);
            }
            elseif ( in_array($type, array('p','photo','photos') ) )  
            {
                global $cbphoto;
                $result = $cbphoto->add_comment(clean($request['comment']),(int)$request['type_id'],$reply_to);   
            }
            else 
            {
                $result = $myquery->add_comment( clean($request['comment']), (int)$request['type_id'], $reply_to, clean($request['type']));
            }
            if( is_numeric($result) )
            {
                $comment_data = array();
                $uploader_data = array();

                $new_comment = $myquery->get_comment($result);
                foreach ($new_comment as $item => $theval) {
                    if (isset($new_comment['comment_ip'])) {
                        $uploader_data[$item] = $theval;
                    } else {
                        $comment_data[$item] = $theval;
                    }

                    unset($new_comment[$item]);
                }
                $to_print = array();
                $to_print['Uploader'] = $comment_data;
                $to_print['Comment'] = $uploader_data;

                $data = array('code' => "200", 'status' => "success", "msg" => "comments added", "data" => $to_print);
                $this->response($this->json($data));
            } 
            else
            {
                if( error() )
                {
                    throw_error_msg(error('single')); 
                }   
            } 
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * joinGroup
    * this function is used to accept invitation to a group
    *
    * @param Integer $group_id*
    * 
    * @return
    */ 
    private function joinGroup()
    {
        try
        {
            $request = $_POST;

            if(!isset($request['group_id']) || $request['group_id']=="" )
                throw_error_msg('provide group id');
            else if(!is_numeric($request['group_id'])) 
                throw_error_msg('invalid group id'); 
            else
                $gid = (int)$request['group_id'];

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));     
            else 
                $uid = userid();  
            
            global $cbgroup; 
            $group_details = $cbgroup->get_group($gid);
            $id = $cbgroup->join_group($gid,$uid);
    
           
            if( error() )
            {
                if(error('single')==lang('grp_join_error') && $group_details['group_privacy']==1)
                    $error = 'You have already requested to join this group';
                else
                    $error = error('single');

                throw_error_msg($error); 
            }

            if( msg())
            {
                if($group_details['group_privacy']==1)
                    $msg = 'Your request to join group has been sent successfully';
                else
                    $msg = msg('single');

                $data = array('code' => "200", 'status' => "success", "msg" => $msg, "data" => array());
                $this->response($this->json($data));
            }

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * activateGroupMember
    * this function is used to activate member request to join a group
    *
    * @param Integer $group_id*
    * @param Integer $userid*
    * 
    * @return
    */ 
    private function activateGroupMember()
    {
        try
        {
            $request = $_POST;

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));  
            
            if(!isset($request['group_id']) || $request['group_id']=="" )
                throw_error_msg('provide group id');
            else if(!is_numeric($request['group_id'])) 
                throw_error_msg('invalid group id'); 
            else
                $gid = (int)$request['group_id'];

            if(!isset($request['userid']) || $request['userid']=="" )
                throw_error_msg('provide userid');
            else if(!is_numeric($request['userid'])) 
                throw_error_msg('invalid userid'); 
            else
                $uid = (int)$request['userid'];

            global $cbgroup; 
            $cbgroup->member_actions($gid,$uid,'activate');

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg())
            {
                $data = array('code' => "200", 'status' => "success", "msg" => msg('single'), "data" => array());
                $this->response($this->json($data));
            }

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * leaveGroup
    * this function is used to leave user from a group
    *
    * @param Integer $group_id*
    * 
    * @return
    */ 
    private function leaveGroup()
    {
        try
        {
            $request = $_POST;

            if(!isset($request['group_id']) || $request['group_id']=="" )
                throw_error_msg('provide group id');
            else if(!is_numeric($request['group_id'])) 
                throw_error_msg('invalid group id'); 
            else
                $gid = (int)$request['group_id'];

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));     
            else 
                $uid = userid();  
            
            global $cbgroup; 
            $id = $cbgroup->leave_group($gid,$uid);

            if( msg())
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'left group successfully', "data" => array());
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
    * updateVideo
    * this function is used to update a video
    *
    * @param Array
    *
    * @param Integer $videoid*
    * @param String $title*
    * @param String $description*
    * @param String $tags*
    * @param String $category*
    * 
    * @return
    */ 
    private function updateVideo()
    {
        try
        {
            $request = $_POST;

            global $cbvid;

            //check if video id provided
            if( !isset($request['videoid']) || $request['videoid']=="" )
                throw_error_msg("video Id not provided");

            if( !is_numeric($request['videoid']) )
                throw_error_msg("invalid video id");

            //check if title provided
            if( !isset($request['title']) || $request['title']=="")
                throw_error_msg("title not provided");
            else
                $title = mysql_clean($request['title']);

            //check if description provided
            if( !isset($request['description']) || $request['description']=="")
                throw_error_msg("description not provided.");
            else
                $description = mysql_clean($request['description']);

            //check if tags provided
            if(!isset($request['tags']) || $request['tags']=="")
                throw_error_msg("tags not provided.");
            else
                $tags = mysql_clean($request['tags']);

            //check if tags provided
            if(!isset($request['category']) || $request['category']=="")
            {
                throw_error_msg("category not provided.");
            }
            else
            {
                $request['category'] = explode(',',$request['category']); 
                $_POST['category']   = $request['category'];
            }
                
            if (isset($request['video_users-user']) || isset($request['video_users-group'])) 
            {
                $video_user_ = mysql_clean($request['video_users-user']);
                $video_group_ = mysql_clean($request['video_users-group']);

                $request['video_users'] = get_video_users($video_user_,$video_group_,false);
            }
           
            $result = $cbvid->update_video($request);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $vdetails = $cbvid->get_video_details($request['videoid']);
                $formatted_video = format_videos(array($vdetails));

                $data = array('code' => "200", 'status' => "success", "msg" => "success", "data" => $formatted_video[0]);
                $this->response($this->json($data));
            }  
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }


    /**
    * activateVideo
    * this function is used to activate a video
    *
    *
    * @param Integer $videoid*
    * 
    * @return
    */ 
    private function activateVideo()
    {
        try
        {
            $request = $_POST;

            global $cbvid;

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));     
            else 
                $uid = userid();

            //check if video id provided
            if( !isset($request['videoid']) || $request['videoid']=="" )
                throw_error_msg("video Id not provided");

            if( !is_numeric($request['videoid']) )
                throw_error_msg("invalid video id");

            $videoid = $request['videoid'];

            $vdetails = $cbvid->get_video_details($videoid);

            if(!isset($vdetails['videoid']))
                throw_error_msg("video does not exist");

            if($vdetails['userid']!=$uid && !has_access('admin_access',true) )
                throw_error_msg("you have not rights to activate this video");

            $cbvid->action('activate',$videoid);

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "success", "data" => "video has been activated successfully");
                $this->response($this->json($data));
            }
            else
            {
                throw_error_msg("There was some thing wrong to activate video");      
            }  
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

     /**
    * deactivateVideo
    * this function is used to deactivate a video
    *
    *
    * @param Integer $videoid*
    * 
    * @return
    */ 
    private function deactivateVideo()
    {
        try
        {
            $request = $_POST;

            global $cbvid;

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));     
            else 
                $uid = userid();

            //check if video id provided
            if( !isset($request['videoid']) || $request['videoid']=="" )
                throw_error_msg("video Id not provided");

            if( !is_numeric($request['videoid']) )
                throw_error_msg("invalid video id");

            $videoid = $request['videoid'];

            $vdetails = $cbvid->get_video_details($videoid);

            if(!isset($vdetails['videoid']))
                throw_error_msg("video does not exist");

            if($vdetails['userid']!=$uid && !has_access('admin_access',true) )
                throw_error_msg("you have not rights to activate this video");

            $cbvid->action('deactivate',$videoid);


            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "success", "data" => "video has been deactivated successfully");
                $this->response($this->json($data));
            }
            else
            {
                throw_error_msg("There was some thing wrong to deactivate video");      
            }  
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * updateUser
    * this function is used to update user information
    *
    * @param Array 
    * 
    * basic information
    * @param Integer userid*
    * @param String sex ['male','female']
    * @param Date dob in Y-m-d format
    * @param String country 2-digit country code e.g; PK 
    * @param File avatar_file
    *
    * load_personal_details
    * @param String first_name
    * @param String last_name
    * @param String relation_status
    * @param String show_dob ['no', 'yes']
    * @param String about_me
    * @param String profile_tags
    * @param String web_url
    * 
    * load_location_fields
    * @param String postal_code
    * @param String hometown
    * @param String city
    * 
    * load_education_interests
    * @param String education
    * @param String schools
    * @param String occupation
    * @param String companies
    * @param String hobbies
    * @param String fav_movies
    * @param String fav_music
    * @param String fav_books
    * 
    * load_privacy_field
    * @param String online_status       ['online', 'offline', 'custom']
    * @param String show_profile        ['all', 'members', 'friends']
    * @param String allow_comments      ['Yes', 'No']
    * @param String allow_ratings       ['Yes', 'No']
    * @param String allow_subscription  ['Yes', 'No']
    *
    * load_channel_settings
    * @param String profile_title
    * @param String profile_desc
    * @param String show_my_friends         ['Yes', 'No']
    * @param String show_my_videos          ['Yes', 'No']
    * @param String show_my_photos          ['Yes', 'No']
    * @param String show_my_subscriptions   ['Yes', 'No']
    * @param String show_my_subscribers     ['Yes', 'No']
    * @param String show_my_collections     ['Yes', 'No']
    *
    * @return
    */ 
    /*private function updateUser()
    {
        try
        {
            $request = $_POST;
            

            if( !isset($request['userid']) || $request['userid']=="" )
                throw_error_msg("user id not provided");

            if( !is_numeric($request['userid']) )
                throw_error_msg("invalid user id");

            global $userquery;
            $userquery->update_user($request);
            
            if( error() )
            {
                $error = error();
                throw_error_msg($error[0]); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'updated successfully', "data" => array());
                $this->response($this->json($data));
            }  

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }*/

    /**
    * insertVideo
    * this function is used to insert a video
    *
    * @param String $title*
    * @param String $description*
    * @param String $tags*
    * @param String $category*
    * 
    * @return
    */ 
    private function insertVideo($exit = true)
    {
        global $Upload,$cbvid;
        try
        {
            $request = $_POST;
            #pex($_FILES,true);
            //check if user logged in
            if(!userid())
               throw_error_msg( lang("you_not_logged_in") ) ;

            //check if title provided
            if( !isset($request['title']) || $request['title']=="")
                throw_error_msg("title not provided");
            else
                $title = mysql_clean($request['title']);

            //check if description provided
            if( !isset($request['description']) || $request['description']=="")
                throw_error_msg("description not provided");
            else
                $description = mysql_clean($request['description']);

            //check if tags provided
            if(!isset($request['tags']) || $request['tags']=="")
                throw_error_msg("tags not provided");
            else
                $tags = mysql_clean($request['tags']);

            //check if category(s) provided
            if(!isset($request['category']))
                $cat_ids = $cbvid->get_default_cid(); 
            else
                $cat_ids = mysql_clean($request['category']);

            //check if user logged in
            $userid = userid();

            if(isset($request['broadcast']))
            $broadcast = mysql_clean($request['broadcast']);
            else
            $broadcast = "public";    

            //upload video script
            $file_name  = time().RandomString(5);

            // if($broadcast!='private' && $broadcast!='unlisted' )
            //     $broadcast = 'public';

            // insert in to database            
            $file_directory = createDataFolders();
            $vidDetails = array
            (
                'title' => $title,
                'description' => $description,
                'tags' => genTags(str_replace(' ',', ',$tags)),
                'category' => explode(',',$cat_ids),
                'file_name' => $file_name,
                'userid' => $userid,
                'file_directory' => $file_directory,
                'broadcast' => $broadcast,
                
            );

            $vid = $Upload->submit_upload($vidDetails);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            $vidDetails = array();
            $vidDetails['videoid']     =  $vid;
            $vidDetails['file_directory']  =  $file_directory;
            $vidDetails['file_name']  =   $file_name;

            $data = array('code' => "200", 'status' => "success", "msg" => "video inserted", "data" => $vidDetails);
            if ($exit) {
                $this->response($this->json($data)); 
            } else {
                return $data;
            }

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    private function uploadVideo() {
        global $cbvid;
        $request = $_POST;
        try
        {
            if ( !isset($_POST['videoid']) || !is_numeric($_POST['videoid']) )
                throw_error_msg("Invalid or empty video id");

            if ( !isset($_FILES) || $_FILES['Filedata'] == '' )
                throw_error_msg("No file was selected");

            $videoid = $_POST['videoid'];
            $vdata = $cbvid->get_video($videoid);
            $file_name = $vdata['file_name'];
            $file_directory = $vdata['file_directory'];
            $apiDir = BASEURL.'/'.basename(__DIR__);
            $configsFetch = $apiDir.'/getConfigs/';
            $configs = file_get_contents($configsFetch);
            $cleaned = json_decode($configs,true);
            $target_url = $cleaned['data']['file_upload_url'];

            $file_name_with_full_path = realpath($_FILES['Filedata']['tmp_name']);
            $post = array(
                'mob_api_upload' => 'yes',
                'videoid' => $videoid,
                'file_directory' => $file_directory,
                'file_name' => $file_name,
                'name' => $_FILES['Filedata']['name'],
                'Filedata'=>'@'.$file_name_with_full_path
                );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$target_url);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            $result=curl_exec ($ch);
            curl_close ($ch);
            
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }


    /**
    * insertVideo
    * this function is used to insert a video
    *
    * @param String $file_name*
    * @param String $title*
    * 
    * @return
    */ 
    /*private function insertVideo()
    {
        global $Upload,$cbvid;
        try
        {
            session_start();
            pr(session_name() . '=' . session_id(),true);
            die;

            $request = $_POST;

            $post_array['insertVideo'] = 'yes';

            //check if user logged in
            if(!userid())
               throw_error_msg( lang("you_not_logged_in") ) ;

            //check if file_name provided
            if( !isset($request['file_name']) || $request['file_name']=="")
                throw_error_msg("file_name not provided");
            else
                $post_array['file_name'] = mysql_clean($request['file_name']);

            //check if title provided
            if( !isset($request['title']) || $request['title']=="")
                throw_error_msg("title not provided");
            else
                $post_array['title'] = mysql_clean($request['title']);

            //curl call to post on /actions/file_uploader.php
            $upload_url = BASEURL."/actions/file_uploader.php";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$upload_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_array);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
         
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec ($ch);

            curl_close ($ch);
            session_write_close();
            $output = json_decode($server_output,true);
            pr($output,true);
            die;

            if(isset($output['error']))
            {
                throw_error_msg( $output['error'] ) ;
            }

            $data = array('code' => "200",'status' => "success","msg" => "video inserted","data" => $output);
            $this->response($this->json($data)); 

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }*/

    /**
    * updatePhoto
    * this function is used to update a photo
    *
    * @param Array
    *
    * @param Integer $photo_id*
    * @param String  $photo_title*
    * @param String  $photo_description*
    * @param String  $photo_tags*
    * @param Integer $collection_id
    * 
    * @return
    */ 
    private function updatePhoto()
    {
        try
        {
            global $cbphoto;
            $request = $_POST;

             //check if photo_id provided
            if( !isset($request['photo_id']) || $request['photo_id']=="" )
                throw_error_msg("photo id not provided");

            if( !is_numeric($request['photo_id']) )
                throw_error_msg("invalid photo id");

            if(!isset($request['collection_id']))
            {
                $cid = $cbphoto->get_photo_field((int)$request['photo_id'],'collection_id');  
                if(is_numeric($cid))
                    $request['collection_id'] = $cid;
            }
            
            $cbphoto->update_photo($request);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $photo = $cbphoto->get_photo($request['photo_id']);
                $formatted_photo = format_photos( array($photo) );

                $data = array('code' => "200", 'status' => "success", "msg" => "success", "data" => $formatted_photo[0]);
                $this->response($this->json($data));
            }  
       
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        } 

    }

    /**
    * uploadPhoto
    * this function is used to upload a photo
    *
    *
    * @param File $photoUpload*
    * 
    * @return
    */ 
    private function uploadPhoto()
    {
        try
        {
            
            $request = $_POST;

            //check if user logged in
            if(!userid())
               throw_error_msg( lang("you_not_logged_in") ) ;

            //check if title provided
            if( !isset($request['photo_title']) || $request['photo_title']=="")
                throw_error_msg("photo_title not provided");
            else
                $insert_array['photo_title'] = mysql_clean($request['photo_title']);

            //check if description provided
            if( !isset($request['photo_description']) || $request['photo_description']=="")
                throw_error_msg("photo_description not provided");
            else
                $insert_array['photo_description'] = mysql_clean($request['photo_description']);

            //check if tags provided
            if(!isset($request['photo_tags']) || $request['photo_tags']=="")
                throw_error_msg("photo_tags not provided");
            else
                $insert_array['photo_tags'] = mysql_clean($request['photo_tags']);

            //check if collection provided
            if(!isset($request['collection_id']) || $request['collection_id']=="")
                throw_error_msg("collection_id not provided");
            elseif(!is_numeric($request['collection_id']))
                throw_error_msg('invalid collection_id');
            else
                $insert_array['collection_id'] = mysql_clean($request['collection_id']);

            if(!isset($_FILES['photo_file']))
                throw_error_msg("photo file not provided");

            $info = pathinfo($_FILES['photo_file']['name']);
           
            $extension  = strtolower($info['extension']);

            $tmp_file = FILES_DIR.'/temp/temp_photo_'.time().'.'.$extension;
                
            @move_uploaded_file($_FILES['photo_file']['tmp_name'], $tmp_file);

            $photo_upload_url = BASEURL."/actions/photo_uploader.php";

            $post_array['plupload'] = 'yes';
            $post_array['name']     = $_FILES['photo_file']['name'];
            $post_array['file']     = '@'.$tmp_file;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$photo_upload_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_array);
         
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec ($ch);

            curl_close ($ch);
            
            $output = json_decode($server_output,true);

            if(isset($output['error']))
            {
                throw_error_msg( $output['error'] ) ;
            }
            else
            {
                //get ouput 
                /*$insert_array['filename']  =  $output['filename'];
                
                $insert_array['folder']    =  createDataFolders(PHOTOS_DIR);*/
                $post_insert_array['insertPhoto']       =   'yes';
                $post_insert_array['photo_title']       =   $insert_array['photo_title'];
                $post_insert_array['photo_description'] =   $insert_array['photo_description'];
                $post_insert_array['photo_tags']        =   $insert_array['photo_tags'];
                $post_insert_array['collection_id']     =   $insert_array['collection_id'];   
                $post_insert_array['file_name']         =   $output['file_name'];
                $post_insert_array['title']             =   $info['filename']; //$_FILES['photo_file']['name'];
                $post_insert_array['ext']               =   $output['extension'];
                $post_insert_array['userid']            =   userid();

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$photo_upload_url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_insert_array);
             
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $server_output = curl_exec ($ch);

                curl_close ($ch);
                
                $output = json_decode($server_output,true);
               
                if(isset($output['success']))
                {
                    global $cbphoto;

                    $photo = $cbphoto->get_photo($output['photoID']);
                    $cbphoto->collection->add_collection_item($photo['photo_id'],$photo['collection_id']);
                    
                    $photo_details = format_photos( array($photo) );
                }
                else
                {
                    throw_error_msg( $output['error'] ) ;    
                }

                $data = array('code' => "200", 'status' => "success", "msg" => "success", "data" => $photo_details);
                $this->response($this->json($data));
            }     
       
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        } 
    }

    /**
    * sendMessage
    * this function is used to send message to registered user 
    *
    * @param Array
    *
    * @param String $username*
    * @param String $subject*
    * @param String $content*
    * 
    * @return
    */ 
    private function sendMessage()
    {
        try
        {
            $request = $_POST;

            $uid = userid();

            if (!$uid)
               throw_error_msg( lang("you_not_logged_in") ) ;

            if( !isset($request['username']) || $request['username']=="" )
                throw_error_msg("username not provided");

            if( !isset($request['subject']) || $request['subject']=="" )
                throw_error_msg("subject not provided");

            if( !isset($request['content']) || $request['content']=="" )
                throw_error_msg("content not provided");

            $array['is_pm']    =   true;
            $array['from']     =   $uid;
            $array['to']       =   $request['username'];
            $array['subj']     =   mysql_clean($request['subject']);
            $array['content']  =   mysql_clean($request['content']);

            global $cbpm;
            $rs = $cbpm->send_pm($array);

            if( error() )
            {
                throw_error_msg(error('single')); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => "message sent successfully", "data" => array());
                $this->response($this->json($data));
            }     
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        } 

    }

    
    // Playlist Functions Start //

    /**
    * addPlaylist
    * this function is used to add/create playlist 
    *
    * @param String $name*
    * 
    * @return
    */ 
    private function addPlaylist()
    {
        try
        {
            $request = $_POST;

            if( !isset($request['name']) || $request['name']=="" )
                throw_error_msg("playlist name not provided");
            else
                $request['name'] =  mysql_clean($request['name']);   

            
            global $cbvid; 
            $pid = $cbvid->action->create_playlist($request);

            if( error() )
            {
                $error = error();
                throw_error_msg($error[0]); 
            }

            if( msg() )
            {
                $new_playlist = $cbvid->action->get_playlist($pid);
                $data = array('code' => "200", 'status' => "success", "msg" => 'new playlist created successfully', "data" => $new_playlist);
                $this->response($this->json($data));
            }    
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * updatePlaylist
    * this function is used to update playlist 
    *
    * @param String $name*
    * @param Integer $pid*
    * 
    * @return
    */ 
    private function updatePlaylist()
    {
        try
        {
            $request = $_POST;

            if( !isset($request['name']) || $request['name']=="" )
                throw_error_msg("playlist name not provided");
            else
                $request['name'] =  mysql_clean($request['name']);   

            if( !isset($request['pid']) || $request['pid']=="" )
                throw_error_msg("playlist id not provided");
            else if(!is_numeric($request['pid']))
                throw_error_msg("invalid playlist id");    
            else
                $request['pid'] =  (int)$request['pid'];

            global $cbvid; 
            $pdetails = $cbvid->action->get_playlist($request['pid']);

            if(empty($pdetails))
                throw_error_msg(lang("playlist_not_exist"));
            else if($pdetails['userid']!=userid())
                throw_error_msg(lang("you_dont_hv_permission_del_playlist"));       

            $pid = $cbvid->action->edit_playlist($request);

            if( error() )
            {
                $error = error();
                throw_error_msg($error[0]); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'playlist updated successfully', "data" => array());
                $this->response($this->json($data));
            }    
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
    * addPlaylistItem
    * this function is used to add item/video in a playlist 
    *
    * @param Integer $pid*
    * @param Integer $video_id*
    * 
    * @return
    */ 
    private function addPlaylistItem()
    {
        try
        {
            $request = $_POST;

            if( !isset($request['pid']) || $request['pid']=="" )
                throw_error_msg("playlist id not provided");
            else if(!is_numeric($request['pid']))
                throw_error_msg("invalid playlist id");    
            else
                $pid =  (int)$request['pid'];

            if( !isset($request['videoid']) || $request['videoid']=="" )
                throw_error_msg("videoid not provided");
            else if(!is_numeric($request['videoid']))
                throw_error_msg("invalid videoid");    
            else
                $id = (int)$request['videoid'];   
            
            global $cbvid; 
            $item_id = $cbvid->action->add_playlist_item($pid, $id);

            if( error() )
            {
                $error = error();
                throw_error_msg($error[0]); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'item has been added to playlist successfully', "data" => array());
                $this->response($this->json($data));
            }    

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }


     /**
    * Creates collection
    * this function is used to create a collection
    *
    *
    * 
    * @return
    */ 
    private function createCollection()
    {
        try
        {
            $request = $_POST;

            global $cbcollection;

            if(!userid())
                throw_error_msg(lang("you_not_logged_in"));     
            else 
                $uid = userid();

            //check if video id provided
            if( !isset($request['collection_name']) || $request['collection_name']=="" )
                throw_error_msg("Collection Name not provided");

            if( !isset($request['collection_description']) || $request['collection_description']=="" )
                throw_error_msg("Collection Description not provided");

            if( !isset($request['collection_tags']) || $request['collection_tags']=="" )
                $request['tags'] = 'sample_tag';

            if( !isset($request['category']) || $request['category']=="" ) {
                throw_error_msg("Collection category not provided");
            } else {
                $request['category'] = array($request['category']);
            }

            if( !isset($request['type']) || $request['type']=="" )
                throw_error_msg("Collection type not provided");

            if( !isset($request['broadcast']) || $request['broadcast']=="" )
                $request['broadcast'] = 'public';

            if( !isset($request['allow_comments']) || $request['allow_comments']=="" )
                $request['allow_comments'] = 'yes';

            if( !isset($request['public_upload']) || $request['public_upload']=="" )
                $request['public_upload'] = 'no';

            $toclean = array('collection_name','collection_description');
            foreach ($toclean as $key => $item) {
                $request[$item] = mysql_clean($request[$item]);
            }

            $status = $cbcollection->create_collection($request);
           # pex($status,true);
            if( $status )
            {
                $newdata = $cbcollection->get_collection($status);
                $data = array('code' => "200", 'status' => "success", "msg" => "Collection has been created successfully :P ", "data" => $newdata);
                $this->response($this->json($data));
            }
            else
            {
                throw_error_msg("Something went wrong trying to create collection");      
            }  
        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    private function getLikes() {
        try
        {
            global $cbvid,$cbphoto;
            $request = $_POST;
            $loggeduser = userid();

            if ( !isset($request['item_type']) )
                throw_error_msg("Item type not provided");

            if( !isset($request['item_id']) || $request['item_id']=="" || !is_numeric($request['item_id']) )
                throw_error_msg("Item id not provided");

            $item_id = $request['item_id'];
            $item_type = $request['item_type'];

            if ($item_type == 'video') {
                $item_data = $cbvid->get_video($item_id);
            } else {
                $item_data = $cbphoto->get_photo($item_id);
            }

            if ( !is_array($item_data) )
                if ($item_type == 'video') {
                    throw_error_msg("Video doesn't exist");
                } else {
                    throw_error_msg("Photo doesn't exist");
                }
            
            $rating = $item_data['rating'];
            $ratedby = $item_data['rated_by'];

            $rating_data = get_total_likes_dislikes($rating, $ratedby);

            if ( $loggeduser ) {
                $rating_data['has_liked'] = '0';
                $rating_data['has_disliked'] = '0';

                if ($item_type == 'video') {
                    $voters = json_decode($item_data['voter_ids'],true);
                    foreach ($voters as $key => $user) {
                        if ($user['userid'] == $loggeduser) {
                            if ($user['rating'] == 0) {
                                $rating_data['has_disliked'] = '1';
                            } else {
                                $rating_data['has_liked'] = '1';
                            }
                        }
                    }
                } else {
                    $voters = json_decode($item_data['voters'],true);
                    foreach ($voters as $key => $rating) {
                        if ($key == $loggeduser) {
                            if ($rating['rate'] == 0) {
                                $rating_data['has_disliked'] = '1';
                            } else {
                                $rating_data['has_liked'] = '1';
                            }
                        }
                    }
                }
            }

            if( error() )
            {
                $error = error();
                throw_error_msg($error[0]); 
            }

                $data = array('code' => "200", 'status' => "success", "msg" => 'Likes fetched successfully', "data" => $rating_data);
                $this->response($this->json($data));  

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    private function codeActivate()
    {
        try
        {
            global $userquery,$db;
            $request = $_POST;

            if ( userid() )
                throw_error_msg("You are already logged in");

            if( !isset($request['avcode']) || $request['avcode']=="" )
                throw_error_msg("Activation not provided");

            if ( !isset($request['username']) || $request['username']=="" )
                throw_error_msg("Username not provided");

            if ( !isset($request['password']) || $request['username']=="" )
                throw_error_msg("Password not provided");

            $avcode = $request['avcode'];
            $username = $request['username'];
            $raw_password = $request['password'];
            $password = pass_code($raw_password);
            $userdata = $db->select(tbl('users'),'*',"avcode = '$avcode' AND  username = '$username' AND password = '$password'");
            $userid = $userdata[0]['userid'];

            if (!is_numeric($userid))
                throw_error_msg("User doesn't exist or already active");

            $action = $userquery->activate_user_with_avcode($username,$avcode);

            $userquery->login_user($username,$raw_password,true);
            $new_user_id = $userquery->userid;
            if (!empty($new_user_id)) {
                $userNow = (int)$new_user_id;
                $user = get_users(array('userid'=>$userNow));
                $new_user = format_users($user);
                $new_user = $new_user[0];
              #  $new_user['avatar'] = str_replace('/', '/', $new_user['avatar']);
                $data = array('code' => "200", 'status' => "success", "msg" => 'success', "data" => $new_user);
                echo json_encode($data,JSON_PRETTY_PRINT);
                return false;
            }

            if( error() )
            {
                $error = error();
                throw_error_msg($error[0]); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'item has been added to playlist successfully', "data" => array());
                $this->response($this->json($data));
            }    

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    private function codeResend()
    {
        try
        {
            global $userquery,$db;
            $request = $_POST;

            if ( userid() )
                throw_error_msg("You are already logged in");

            if ( !isset($request['username']) || $request['username'] == '' )
                throw_error_msg("Username not provided");

            if ( !isset($request['password']) || $request['password'] == '' )
                throw_error_msg("Password not provided");

            $username = $request['username'];
            $password = pass_code($request['password']);

            $userdata = $db->select(tbl("users"),'email,usr_status',"username = '$username' AND password = '$password'");
            $userdata = $userdata[0];
            if ($userdata['usr_status'] == 'Ok')
                throw_error_msg("Your account is already active");

            $userquery->send_activation_code($userdata['email']);
            if( error() )
            {
                $error = error();
                throw_error_msg($error[0]); 
            }

            if( msg() )
            {
                $data = array('code' => "200", 'status' => "success", "msg" => 'Activation code has been resent successfully', "data" => array());
                $this->response($this->json($data));
            }    

        }
        catch(Exception $e)
        {
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
        * Used to start streaming and updating the app
        * @param   : { Array } 
        * @param   : { live_channel_id,userid,recordername,is_recording,app_name }
        * @example : start_streaming(){will start streaming }
        * @return  : { headers with messgaes } 
        * @since   : 21st Septemper, 2017 ClipBucket 2.8.3
        * @author  : Fahad Abbas
    */
    public function start_streaming(){
        $params = $_POST;

        global $wowza,$cblive,$userquery;
        try{
            
            if ( !userid() ){
                throw_error_msg("please login to proceed");
            }
            if (!$params["live_channel_id"] ){
                throw_error_msg("please provide live_channel_id !");
            }

            $live_channel_id = $params["live_channel_id"];
            $userid = $params["userid"];
            $recorder_name = $params["recorder_name"];
            $stream_name = $params["stream_name"];

           
            if (!$live_channel_id){
                throw_error_msg("please provide live_channel_id");
            }

            $cblive->update_live_channel(array(
                                            "channel_name"=>$params['app_name'],
                                            "is_live"=>"yes",
                                            "live_channel_id"=>$live_channel_id
                                        ));
            
            $cblive->notify_subscribers($userid);
            if ($params["is_recording"] == "yes"){
                $recording_configs = array(
                                            "app_name"=>$params['app_name'],
                                            "recorder_name"=>$recorder_name,
                                            "stream_name"=>$stream_name
                                        );

                $response  = $wowza->start_recording($recording_configs);
                
            }

            $data = array(
                        'code' => "200", 
                        'status' => "success", 
                        "msg" => 'Stream started Successfully', 
                        "data" => $response
                    );
            $this->response($this->json($data));

        }catch(Exception $e){
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
        * Used to stop streaming and updating the app
        * @param   : { Array } 
        * @param   : { live_channel_id,userid,recordername,is_recording,app_name }
        * @example : stop_streaming(){will stop streaming }
        * @return  : { headers with messgaes } 
        * @since   : 21st Septemper, 2017 ClipBucket 2.8.3
        * @author  : Fahad Abbas
    */
    public function stop_streaming(){
        $params = $_POST;
        global $wowza,$cblive,$userquery;
        try{

            if ( !userid() ){
                throw_error_msg("please login to proceed");
            }

            $live_channel_id = $params["live_channel_id"];
            $userid = $params["userid"];
            $recorder_name = $params["recorder_name"];


            $cblive->update_live_channel(array(
                                            "channel_name"=>$params['app_name'],
                                            "is_live"=>"no",
                                            "live_channel_id"=>$live_channel_id
                                        ));
            if ($params["is_recording"] == "yes"){
                $recording_configs = array(
                                            "app_name"=>$params['app_name'],
                                            "recorder_name"=>$recorder_name
                                        );

                $response = $wowza->stop_recording($recording_configs);
            }

            $data = array(
                        'code' => "200", 
                        'status' => "success", 
                        "msg" => 'Stream Stopped Successfully', 
                        "data" => $response
                    );
            $this->response($this->json($data));

        }catch(Exception $e){
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
        * Used to save recorded streaming video and updating the app
        * @param   : { Array } 
        * @param   : { app_name,userid,stream_name,is_recording,app_name }
        * @example : stop_streaming(){will stop streaming }
        * @return  : { headers with messgaes } 
        * @since   : 21st Septemper, 2017 ClipBucket 2.8.3
        * @author  : Fahad Abbas
    */
    public function upload_recorded_stream(){
        $params = $_POST;
        global $wowza,$cblive,$userquery;
        try{

            if ( userid() ){
                throw_error_msg("please login to proceed");
            }

            if ( !$params['app_name'] ){
                throw_error_msg("please provide app_name with");
            }

            if ( !$params['stream_name'] ){
                throw_error_msg("please provide stream_name with");
            }

            $stream_name = $params["stream_name"];
            $stream_name = $params["app_name"];

            $upload  = true;

            $data = array(
                        'code' => "200", 
                        'status' => "success", 
                        "msg" => 'Video Uploaded Successfully', 
                        "data" => $upload
                    );
            $this->response($this->json($data));

        }catch(Exception $e){
            $this->getExceptionDelete($e->getMessage());
        }
    }

    /**
        * Used to delete recorded video stream and updating the app
        * @param   : { Array } 
        * @param   : { app_name,userid,stream_name,is_recording,app_name }
        * @example : delete_recorded_stream(){will delete recorded stream }
        * @return  : { headers with messgaes } 
        * @since   : 21st Septemper, 2017 ClipBucket 2.8.3
        * @author  : Fahad Abbas
    */
    public function delete_recorded_stream(){
        $params = $_POST;
        global $wowza,$cblive,$userquery;
        try{

            if ( userid() ){
                throw_error_msg("please login to proceed");
            }

            if ( !$params['app_name'] ){
                throw_error_msg("please provide app_name with");
            }

            if ( !$params['stream_name'] ){
                throw_error_msg("please provide stream_name with");
            }

            $stream_name = $params["stream_name"];
            $stream_name = $params["app_name"];

            $deleted  = true;

            $data = array(
                        'code' => "200", 
                        'status' => "success", 
                        "msg" => 'Recoeded Stream Deleted Successfully', 
                        "data" => $upload
                    );
            $this->response($this->json($data));

        }catch(Exception $e){
            $this->getExceptionDelete($e->getMessage());
        }
    }

   

}

// Initiiate Library
$api = new API;
$api->processApi();

?>