<?php 

/**
* File: Functions
* Description: This Class is written to manange all the Actions for CB Ultimate Ads {CRUD}
* @license: Attribution Assurance License
* @since: ClipBucket 2.8
* @author[s]: Fahad Abbas
* @copyright: (c) 2008 - 2016 ClipBucket / PHPBucket
* @modified: April 23, 2016 ClipBucket 2.8.1
*/
class cb_ultimate_ads 
{
	
	var $ad_types = array();	
	function __construct(){	
		
		$this->ad_types = array(
							"1"=>"DFP Google",
							"2"=>"Linear (Custom)",
							"3"=>"Non-Linear (Custom)"
						); 
	}

	/**
	* Used to Insert Ad in sql database table `uads`
	* @param   : { Array } {ad_tag,ad_desc,ad_type,ad_status,target_imp etc }
	* @example : add_ultimate_ad($post) // will match the post information to build the database query
	*            and insert
	* @return  : { ad_id {lastinsertid} / Boolean {false}} 
	* @since   : 23rd April, 2016 ClipBucket 2.8.1
	* @author  : Fahad Abbas
	*/
	function add_ultimate_ad($array){
		global $db;
		$input = $array;

		if (!empty($input['ad_tag'])){
			$field[] = 'ad_tag';
			$value[] = "'".$input['ad_tag']."'";
		}

		if (!empty($input['ad_desc'])){
			$field[] = 'ad_desc';
			$value[] = "'".$input['ad_desc']."'";
		}

		if (!empty($input['ad_type'])){
			$field[] = 'ad_type';
			$value[] = "'".$input['ad_type']."'";
		}

		if (!empty($input['ad_status'])){
			$field[] = 'ad_status';
			$value[] = "'".$input['ad_status']."'";
		}

		if (!empty($input['target_imp'])){
			$field[] = 'target_imp';
			$value[] = "'".$input['target_imp']."'";
		}

		if (!empty($input['category_id'])){
			$field[] = 'category_id';
			$value[] = "'".$input['category_id']."'";
		}

		if (!empty($input['country'])){
			$field[] = 'country';
			$value[] = "'".$input['country']."'";
		}


		if (!empty($input['ad_time'])){
			$field[] = 'ad_time';
			$value[] = "'".$input['ad_time']."'";
		}

		if (!empty($input['start_date'])){
			$field[] = 'start_date';
			$value[] = "'".$input['start_date']."'";
		}

		if (!empty($input['end_date'])){
			$field[] = 'end_date';
			$value[] = "'".$input['end_date']."'";
		}

		if (!empty($input['skippable'])){
			$field[] = 'skippable';
			$value[] = "'".$input['skippable']."'";
		}

		if (!empty($input['skip_time'])){
			$field[] = 'skip_time';
			$value[] = "'".$input['skip_time']."'";
		}
	
		$fields = implode(',', $field);
		$values = implode(',', $value);

		$query = "INSERT INTO " . tbl("uads") . " (".$fields.")
				 VALUES(".$values.")";
				 
		$db->Execute($query);
		$ad_id = $db->insert_id();
		if (!empty($ad_id)){
			return $ad_id;
		}else{
			return false;
		}
	}

	/**
	* Used to update an ad fields 
	* @param   : { Array } {ad_tag,ad_desc,ad_type,ad_status,target_imp etc }
	* @example : update_ultimate_ad($post) // will match the post information to build the database query
	*            and update 
	* @return  : { Boolean / true } 
	* @since   : 23rd April, 2016 ClipBucket 2.8.1
	* @author  : Fahad Abbas
	*/
	function update_ultimate_ad($array){
		global $db;
		$input = $array;
		
		if (array_key_exists('ad_tag', $input)){
			$field[] = 'ad_tag';
			$value[] = "|no_mc|".$input['ad_tag'];
		}

		if (array_key_exists('ad_desc', $input)){
			$field[] = 'ad_desc';
			$value[] = $input['ad_desc'];
		}

		if (array_key_exists('ad_type', $input)){
			$field[] = 'ad_type';
			$value[] = $input['ad_type'];
		}

		if (array_key_exists('ad_status', $input) || $input['ad_status'] == 0){
			$field[] = 'ad_status';
			$value[] = $input['ad_status'];
		}

		if (array_key_exists('target_imp', $input)){
			$field[] = 'target_imp';
			$value[] = $input['target_imp'];
		}

		if (array_key_exists('country', $input)){
			$field[] = 'country';
			$value[] = $input['country'];
		}

		if (array_key_exists('category_id', $input)){
			$field[] = 'category_id';
			$value[] = $input['category_id'];
		}

		if (array_key_exists('ad_time', $input)){
			$field[] = 'ad_time';
			$value[] = $input['ad_time'];
		}

		if (array_key_exists('linear_type', $input)){
			$field[] = 'linear_type';
			$value[] = $input['linear_type'];
		}

		if (array_key_exists('start_date', $input)){
			$field[] = 'start_date';
			$value[] = $input['start_date'];
		}

		if (array_key_exists('end_date', $input)){
			$field[] = 'end_date';
			$value[] = $input['end_date'];
		}

		if (array_key_exists('skippable', $input)){
			$field[] = 'skippable';
			$value[] = $input['skippable'];
		}

		if (array_key_exists('skip_time', $input)){
			$field[] = 'skip_time';
			$value[] = $input['skip_time'];
		}
	
		$fields = $field;
		$values = $value;
		
		$db->update(tbl("uads"),$fields,$values," ad_id='".$input['ad_id']."'");
		return true;

	}

	/**
	* Used to get ads depending upon parameters passed in array
	* @param   : { Array } { id, status, non_expiry, count_only }
	* @example : get_ultimate_ads($array) // will return the sql row/rows depending upon params passed 
	* @return  : { Array } { SQL Rows/row } 
	* @since   : 23rd April, 2016 ClipBucket 2.8.1
	* @author  : Fahad Abbas
	*/
	function get_ultimate_ads($params){
		global $db;
        $limit = $params['limit'];
		$order = $params['order'];

        //non-uid to exclude user videos from related
        $cond = "";

        if($params['id'])
		{
			if($cond!='')
				$cond .= ' AND ';
			$cond .= " ad_id = '".$params['id']."' ";

		}

		if($params['status'])
		{
			if($cond!='')
				$cond .= ' AND ';
			$cond .= " ad_status = '".$params['status']."' ";

		}

		if($params['non_expiry'])
		{
			if($cond!='')
				$cond .= ' AND ';
			$cond .= " target_imp > impressions ";

		}

		if($params['count_only'])
			return $result = $db->count( cb_sql_table('uads') , 'ad_id' ,$cond );

        $query = "SELECT * FROM ".tbl('uads')." ";

        if( $cond ) {
        	$query .= " WHERE ".$cond;
        }

        $query .= $order ? " ORDER BY ".$order : false;
        $query .= $limit ? " LIMIT ".$limit : false;
		$results = db_select($query);
        
        if (!$params['filter_ad']){
            return $results;
        }

        if ($params['video']){
        	$results_['ads'] = $results;
        	$results_['video'] = $params['video'];
        }

		$filtered_ad = $this->filter_ad($results_);
	    return $filtered_ad;
	}

/**
	* Used to check if an ad exits in sql rwos or not 
	* @param   : { id } { Int }
	* @example : ad_exists($ad_id) // will count if a row exists in database against ad_id  
	* @return  : { Boolean } { True / False }
	* @since   : 23rd April, 2016 ClipBucket 2.8.1
	* @author  : Fahad Abbas
	*/
	function ad_exists($id)
	{
		global $db;
		$count = $db->count(tbl("uads"),"ad_id"," ad_id='$id' ");
		if($count>0)
			return true;
		else
			return false;
	}

	/**
	* Used to update ad impression field against per id
	* @param   : { ID } { Int }
	* @example : update_ad_impression(id) // will update impression field with +1 in database against ad_id 
	* @return  : { Boolean } { true }
	* @since   : 23rd April, 2016 ClipBucket 2.8.1
	* @author  : Fahad Abbas
	*/
	function update_ad_impression($id){
		global $db;
        $query = "UPDATE " . tbl("uads") . " SET impressions = impressions + 1 WHERE ad_id = {$id}";
 		$db->Execute($query);
	    return true;
	}

	/**
	* It used to Filter the ad among ads by reurning the ad who's per day Ratio of views is highest among all
	* @param   : { Array } { contaning number of rows of Ads }
	* @example : filter_ad($Array) // 
	* (Cont..) : This fucntion will use Current DateTime Object to get the diff() from start and 
	* (Cont..) : End Date per Ad row, and after  calculating Current Per Day Views Ratio of All
	* (Cont..) : Ads, it will return Ad of highest ratio among all. It will Return False if ratio is ZERO of All. 
	* @return  : { $Ad /  False }
	* @since   : 23rd April, 2016 ClipBucket 2.8.1
	* @author  : Fahad Abbas
	*/
	function filter_ad($array){
		global $Cbucket;
        if (empty($array)){
            return false;  
        }
		if (!$array['ads']){
			return false;
		}        

        $allAds = $array['ads'];
        $video = $array['video'];
        #Getting the current datetime Object
		$nowObject = new DateTime();
    		
    	$highestRatio = 0;
        $filter_ads = array();

        $c_ip=$Cbucket->get_client_ip();
        // For testing uncomment the following line ! 
        //$c_ip='43.245.9.236';
        //getting client country on the base of ip
        $c_country_code=$Cbucket->get_client_country($c_ip);


        foreach ($allAds as $key => $ad) {

        	//checking for if the current ad falls in this country
        	$countries = $ad['country'];
        	if ( !empty(trim($countries)) ){
        		$countries = explode(',',$countries);
        		if ( !in_array($c_country_code,$countries) ){
        			unset($ad);
        			continue;
        		}
        	}

        	//checking for if the current ad falls in the video category
        	$ad_categories = $ad['category_id'];
        	if ( !empty($ad_categories) ){
        		$ad_categories = explode(',',$ad_categories);
        		$video_category  = $video['category'];
        		$video_categories = explode(' ', $video_category);
        		if( is_array($video_categories) && !empty($video_categories) ){
        			foreach ( $video_categories as $key => $cat ) {
        				if ( !empty( trim($cat) ) ){
        					$cat = str_replace('#','',$cat);
        					$new_cat[]  = $cat;
        				}
        			}
        			$video_categories = $new_cat;
        		}
        		foreach ( $ad_categories as $key => $ad_cat ) {
        			if ( !in_array($ad_cat,$video_categories) ){
        				unset($ad);
        				continue;
        			}
        		}
        	}


            $ad_start_datetime = $ad['start_date'];
            $ad_end_datetime   = $ad['end_date'];
            $target_imp    = $ad['target_imp'];
            $current_imp    = $ad['impressions'];
            # Converting Start and End Date into Date format
          	$start_dateformat = date("Y-m-d H:i:s", $ad_start_datetime);
         	$end_dateformat = date("Y-m-d H:i:s", $ad_end_datetime);
            
            #Getting difference form current time to Start Time
            $start_datetime = new DateTime($start_dateformat);
            $before_interval = $nowObject->diff($start_datetime);
          	#Getting difference form current time to End Time
            $end_datetime = new DateTime($end_dateformat);
            $interval = $nowObject->diff($end_datetime);
            
            #Getting target remaining impressions
            $rem_target_impressions = $target_imp - $current_imp;
            
            #Checking if ad start and end date is valid to be served or not
            if ( $interval->format("%R") == '+' && $before_interval->format("%R") == '-'  ){
            	#Coverting minutes remaining into days 
            	$remaining_minutes = $interval->h * 60 + $interval->i;
                $remaining_time = $remaining_minutes / 1440;
                $remaining_time = $interval->days + $remaining_time;

                $PerDayRatio = $rem_target_impressions / $remaining_time;
            }else{
                $PerDayRatio = 0;
            }
            # The Ad having highest Ratio and greater than 0 will be served
            if ($PerDayRatio > $highestRatio){
                $filtered_ad = $ad;
                $highestRatio = $PerDayRatio;    
            }
        }
    
        #Now returning the final Results
        if ($filtered_ad){
        	if ($this->nonLinearBannerExists($filtered_ad)){
        		$filtered_ad['banner'] = $this->nonLinearBannerExists($filtered_ad);
        	}
        	
        	return $filtered_ad;	
        }else{
        	return false;
        }
        
	}

	/**
	* It is used to perform multiple actions over Ad row
	* @param   : { $array } { Id, Action } {Action : activate, deactivate, delete}
	* @example : ad_actions($input) 
	* (Cont..) : // this function can activate , deactivate , delete per ad depending upon parameters 
	* @return  : { Null }
	* @uses    : ad_exists()
	* @since   : 23rd April, 2016 ClipBucket 2.8.1
	* @author  : Fahad Abbas
	*/
	function ad_actions($params){
		$action = $params['action'];
		$id = $params['id'];
		global $db;
		if($this->ad_exists($id)){
			switch ($action) {
			 	case 'activate':{
					$query = "UPDATE " . tbl("uads") . " SET ad_status = 1 WHERE ad_id = {$id}";
					$db->Execute($query);
					e(lang("ad_active"),"m"); 

			 	}
			 	break;

			 	case 'deactivate':{
			 		$query = "UPDATE " . tbl("uads") . " SET ad_status = 0 WHERE ad_id = {$id}";
					$db->Execute($query);
					e(lang("ad_deactive"),"m"); 
			 	}
			 	break;

			 	case 'delete':{
			 		$query = "DELETE  FROM ".tbl('uads')." WHERE ad_id='".$id."' ";
					$db->Execute($query);
			 		e(lang('ad_del_msg'),"m");
			 	}
			 	break;
			 	
			 	default:{
			 		echo "do Nothing, Wrong parameter sent ! :(";
			 	}
			 	break;
			}
		}else{
			e(lang("ad_exists_error1"));
		}
	}


	function update_non_linear_banner($file,$id,$return_file=false){
		global $db;
		if(isset($file) && !empty($file['name']) && $id){

	        $filename = $file['name'];
	        $physical_path = $file['tmp_name'];
	        $ext = getExt($filename);
	        
	        
	        
	        $temp_destination = CB_UADS_MANAGER_DIR.'/resource/'.$id.".".$ext;
	        $uploaded = move_uploaded_file($physical_path,$temp_destination);
	        
	        if ($uploaded){
	        	$db->update(tbl("uads"),array("banner_ext"),array($ext)," ad_id='".$id."'");
	        	if ($return_file){
	        		return  CB_UADS_MANAGER_URL.'/resource/'.$id.".".$ext;
	        	}else{
	        		return true;
	        	}
	        }else{
	        	return false;
	        } 
	            
		}else{	
			return false;
			
		}

	}

	function nonLinearBannerExists($ad){

		$fileToCheck = CB_UADS_MANAGER_DIR.'/resource/'.$ad['ad_id'].'.'.$ad['banner_ext'];
		if (file_exists($fileToCheck)){
			$fileToReturn = CB_UADS_MANAGER_URL.'/resource/'.$ad['ad_id'].'.'.$ad['banner_ext'];
			return $fileToReturn;
		}else{
			return false;
		}

	}

	function removeBanner($id){

		$file = CB_UADS_MANAGER_DIR.'/resource/'.$id.'*';
		$files = glob($file);
	
		$removed  = false;
		if ($files){
			foreach ($files as $key => $file) {
				unlink($file);
				$removed = true;
			}
		}
		return $removed;
	}
}