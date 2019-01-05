<?php

// This will work
class CB_live_stream 
{
    
    var $tbl = "";

    function __construct(){
    	$this->tbl = tbl("live_channel");
    	$this->tbl_fields = array("live_channel_id","channel_name","description","stream_name","userid","recording","app_type","is_live");
    }
    
    
    /**
	* Used to insert channel information in relative clomumns
	* @param   : { Array } { post details }
	* @example : create_live_channel($params){will insert the channels with info provided}
	* @return  : { id } 
	* @since   : 21st Septemper, 2017 ClipBucket 2.8.3
	* @author  : Fahad Abbas
	*/
    public function create_live_channel($params){
  		global $wowza,$userquery,$row;
    	//Inserting information of channel into Clipbucket
    	
  		$channel_exists = $this->get_live_channels(array(
  										"count_only"=>true,
  										"userid"=> (int)$params['userid']
  									));
  		
  		if ($channel_exists){
    		throw new Exception("Channel already existed with this user!");
    	}

    	if (!$params['channel_name']){
    		throw new Exception("Please provide channel name !");
    	}

    	if ($params["app_type"] == 'Live' && $wowza->connect_api == 'yes'){
    		$app = $wowza->app($params['channel_name']);
    		if ( $params['channel_name'] != $app['Name']  ){
    			throw new Exception("The application you added doesn't exists on wowza server ");
    		}
    	}

    	$userid = (int)$_POST['userid'];
    	if (!$userquery->user_exists($userid)){
    		throw new Exception("This user doesn't exists");
    	}

    	$query = array(
            'channel_name' => mysql_clean($params['channel_name']),
            'description'  => mysql_clean($params['description']),
            'app_type'     => $params["app_type"],
            'userid'       => (int)$params['userid'],
            'date_added'   => NOW()
        );
        
        $insert_id = db_insert($this->tbl,$query);
        if ($insert_id){
        	return $insert_id;
        }else{
    	throw new Exception("Something went wrong in creating a CB Live Channel");
        }
    
    }

   	/**
	* Used to get channel information in relative clomumns
	* @param   : { Array } { post details }
	* @example : get_live_channels($params){will get the channels with info provided}
	* @return  : { Boolean } 
	* @since   : 21st Septemper, 2017 ClipBucket 2.8.3
	* @author  : Fahad Abbas
	*/
	function get_live_channels($params){
		global $db;

	    $limit = $params['limit'];
		$order = $params['order'];

	    $cond = "";

		if($params['live_channel_id']){
			if($cond!='')
				$cond .= ' AND ';
			$cond .= " live_channel_id = '".$params['live_channel_id']."' ";

		}

		if($params['userid']){
			if($cond!='')
				$cond .= ' AND ';
			$cond .= " userid = '".$params['userid']."' ";

		}

		if($params['count_only'])
			return $result = $db->count( $this->tbl , 'live_channel_id' ,$cond );

		$fields = array('live' => $this->tbl_fields);
		$query = "SELECT ".tbl_fields($fields)." FROM ".$this->tbl." ";
		$query .= " as live ";

	    if( $cond ) {
	    	$query .= " WHERE ".$cond;
	    }

	    $query .= $order ? " ORDER BY ".$order : false;
	    $query .= $limit ? " LIMIT ".$limit : false;
	    //exit($query);
		$results = db_select($query);

		if (!empty($results) && is_array($results)){
			return $results;
		}else{
			return false;
		}

	}

	function delete_live_channel($channel_id,$app_name){
		global $db,$wowza;
		if (!$channel_id){
			throw new Exception("Bad Request, Invalid Parameters !");
		}
		
		$channel_id = (int)$channel_id;
		$query = "DELETE FROM ".tbl('live_channel')." where live_channel_id='{$channel_id}'";
		$db->Execute($query);
		return true;
	}



	/**
	* Used to update a challenge
	* @param   : { Array } {challenge }
	* @example : update_slot($array) { will update a slot depends upon id }
	* @return  : { Boolean } { True/ False }
	* @since   : 27th July, 2016 ClipBucket 2.8.1
	* @author  : Fahad Abbas
	*/
	function update_live_channel($array){
		global $db,$wowza;
		$input = $array;

		if(!$input['live_channel_id']){
			throw new Exception("Invalid details to get things updated provide live_channel_id");
		}

		if (!empty($input['is_live'])){
			$field[] = 'is_live';
			$value[] = $input['is_live'];
			$db->update(tbl('users'),array("is_live"),array($input['is_live'])," userid='".$input['userid']."'");
		}

		if (!empty($input['recording'])){
			$field[] = 'recording';
			$value[] = $input['recording'];
		}

		if (!empty($input['channel_name'])){
			$field[] = 'channel_name';
			$value[] = $input['channel_name'];
		}
		if ($input['app_type'] == 'Live' && $wowza->connect_api == 'yes'){
			$app = $wowza->app($input['channel_name']);
    		if ( $input['channel_name'] != $app['Name']  ){
    			throw new Exception("The application you added doesn't exists on wowza serevr ");
    		}

		}

		$fields = $field;
		$values = $value;

		$db->update($this->tbl,$fields,$values," live_channel_id='".$input['live_channel_id']."'");

		return true;
	}

	function notify_subscribers($userid){
		global $userquery;
		if (!$userid){
			throw new Exception("please provide userid to notify subscribers");
		}

		$user_details = $userquery->get_user_details($userid);
		if (!$user_details){
			throw new Exception("Live user is invalid");
		}
		//Sending mail to subscribers
		$subscribers = $userquery->get_user_subscribers($userid);
		if ($subscribers){
			foreach ($subscribers as $key => $user) {
				$subj = "Live Notification"; 
				$msg = Name($user_details)." is Live Now  !";
				cbmail(array('to'=>$user["email"],'from'=>WEBSITE_EMAIL,'subject'=>$subj,'content'=>$msg));
			}
		}
	}
}