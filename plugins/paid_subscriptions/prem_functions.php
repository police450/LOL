<?php

if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');



	//Function used to check paid subscription license
function check_paid_license($license,$localkey)
{
	$results = paid_mod_check_license($license,$localkey);
	$error_setting_link = '<a href="'.BASEURL.'/admin_area/plugin.php?folder='._PAID_SUBS_.'/admin&file=configure.php">Click Here to edit Paid Subscription Settings</a>';
	if(!$results)
	{
		if(BACK_END)
			e("Error while loading Paid Subscription license - $error_setting_link","w");
	}elseif ($results["status"]=="Invalid")
	{
		if(BACK_END)
			e("Your Paid Subscription License is Invalid - $error_setting_link","w");
	}elseif ($results["status"]=="Expired")
	{
		if(BACK_END)
			e("Your Paid Subscription License is Expired - $error_setting_link","w");
	}elseif($results["status"]=="Suspended")
	{
		if(BACK_END)
			e("Your Paid Subscription is suspended - $error_setting_link","w");
	}elseif($results['status']!='Active')
	{
		if(BACK_END)
			e("Error occured while checking license , status : ".$results['status']." - $error_setting_link","w");
	}
	return $results;
}


	//Function used to verify paid module license
function paid_mod_check_license($licensekey,$localkey="")
{
		//return array('status'=>'Active');
	$whmcsurl = "http://client.clip-bucket.com/";
	$prefix = "CBPAIDMOD";
	$licensing_secret_key = "CBPAIDMOD"; # Set to unique value of chars
	$checkdate = date("Ymd"); # Current dateW
	$usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
	$localkeydays = 5; # How long the local key is valid for in between remote checks
	$allowcheckfaildays = 2; # How many days to allow after local key expiry before blocking access if connection cannot be made
	$localkeyvalid = false;
	
	$prefix_len = strlen($prefix);
	
	if(substr($licensekey,0,$prefix_len)!=$prefix)
	{
		return array('status'=>'Unknown license');
	}
	if ($localkey) {
		$localkey = str_replace("\n",'',$localkey); # Remove the line breaks
		$localdata = substr($localkey,0,strlen($localkey)-32); # Extract License Data
		$md5hash = substr($localkey,strlen($localkey)-32); # Extract MD5 Hash
		if ($md5hash==md5($localdata.$licensing_secret_key)) {
			$localdata = strrev($localdata); # Reverse the string
			$md5hash = substr($localdata,0,32); # Extract MD5 Hash
			$localdata = substr($localdata,32); # Extract License Data
			$localdata = base64_decode($localdata);
			$localkeyresults = unserialize($localdata);
			$originalcheckdate = $localkeyresults["checkdate"];
			if ($md5hash==md5($originalcheckdate.$licensing_secret_key)) {
				$localexpiry = date("Ymd",mktime(0,0,0,date("m"),date("d")-$localkeydays,date("Y")));
				if ($originalcheckdate>$localexpiry) {
					$localkeyvalid = true;
					$results = $localkeyresults;
					$validdomains = explode(",",$results["validdomain"]);
					if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
						$localkeyvalid = false;
						$localkeyresults["status"] = "Invalid";
						$results = array();
					}
					$validips = explode(",",$results["validip"]);
					if (!in_array($usersip, $validips)) {
						$localkeyvalid = false;
						$localkeyresults["status"] = "Invalid";
						$results = array();
					}
					if ($results["validdirectory"]!=dirname(__FILE__)) {
						$localkeyvalid = false;
						$localkeyresults["status"] = "Invalid";
						$results = array();
					}
				}
			}
		}
	}
	if (!$localkeyvalid) {
		$postfields["licensekey"] = $licensekey;
		$postfields["domain"] = $_SERVER['SERVER_NAME'];
		$postfields["ip"] = $usersip;
		$postfields["dir"] = dirname(__FILE__);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $whmcsurl."modules/servers/licensing/verify.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);
		if (!$data) {
			$localexpiry = date("Ymd",mktime(0,0,0,date("m"),date("d")-($localkeydays+$allowcheckfaildays),date("Y")));
			if ($originalcheckdate>$localexpiry) {
				$results = $localkeyresults;
			} else {
				$results["status"] = "Remote Check Failed";
				return $results;
			}
		} else {
			preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
			$results = array();
			foreach ($matches[1] AS $k=>$v) {
				$results[$v] = $matches[2][$k];
			}
		}
		if ($results["status"]=="Active") {
			$results["checkdate"] = $checkdate;
			$data_encoded = serialize($results);
			$data_encoded = base64_encode($data_encoded);
			$data_encoded = md5($checkdate.$licensing_secret_key).$data_encoded;
			$data_encoded = strrev($data_encoded);
			$data_encoded = $data_encoded.md5($data_encoded.$licensing_secret_key);
			$data_encoded = wordwrap($data_encoded,80,"\n",true);
			$results["localkey"] = $data_encoded;
			global $db;
			$db->update(tbl("paid_configs"),array("value"),array($results["localkey"]),"config_id=6") ;
		}
		$results["remotecheck"] = true;
	}
	unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$localkeydays,$allowcheckfaildays,$md5hash);
	
	return $results;
}
	
	
	/**
 * function used to check weather user is allowed to watch current
 * video or not
 *
 * @param : $vdata ARRAY
 * @return : nothing
 */


	function check_subscription($vdo)
	{
		global $userquery,$Cbucket,$paidSub;
		if(isset($_POST['play_video']))
		{
			$paidSub->getUserSubscription(userid());
			if(!$paidSub->playableVideo($vdo))
			{
				e("You cannot watch this video");
				$Cbucket->show_page = false;
			}
		}
	}
	cb_register_function('check_subscription','watch_video');


 /**
  * Function used to get subscriptions
  */
 function getSubscriptions($array)
 {
 	global $paidSub;
 	return $paidSub->getSubscriptions($array);
 }
 
 /**
  * Function used to get Orders
  */
 function getOrders($array)
 {
 	global $paidSub;
 	return $paidSub->getOrders($array);
 }
 
 
 /**
  * Function used to get order status
  */
 function orderStatus($order,$assign=false)
 {
 	if(!$order)
 	{
 		$array['class'] = 'default';
 		$array['status'] = 'Skipped';
 	}
 	else
 	{
 		switch($order['status'])
 		{
 			case "pending":
 			{
 				$array['class'] = 'warning';
 				$array['status'] = 'Pending';
 			}
 			break;

 			case "failed":
 			{
 				$array['class'] = 'danger';
 				$array['status'] = 'Failed';
 			}
 			break;

 			case "unpaid":
 			{
 				$array['class'] = 'danger';
 				$array['status'] = 'Unpaid';
 			}
 			break;

 			case "paid":
 			{
 				$array['class'] = 'success';
 				$array['status'] = 'Paid';
 			}
 			break;
 		}

 	}

 	if($assign)
 		assign($assign,$array);
 	else
 		return $array;
 }
 
 
 function pkg_type_name($in)
 {
 	switch($in)
 	{
 		case "subs": echo "Subscription"; break;
 		case "ppv": echo "PPV"; break;
 		case "mins": echo "Minutes"; break;
 		case "vids": echo "Videos"; break;
 	}
 }
 
 function pkg_limit($in,$type="")
 {
 	switch($type)
 	{
 		case "days" : 
 		{ 
 			if($in<1) 
 				echo  'unlimit'; else echo $in;
 		} 
 		break; 

 		case "vids" : 
 		{ 
 			if($in<1) 
 				echo  'unlimit'; else echo $in;
 		} 
 		break;

 		case "ppv" : 
 		{ 
 			if($in<0) 
 				echo 'yes (unlimit)'; elseif(!$in) echo 'disabled'; else echo 'yes ('.$in.')';
 		} 
 		break;

 	}
 }
 
 
 function smartyOrderStatus($param){return orderStatus($param['data'],$param['assign']); }
 
 
	/**
	* currency
	*/
	function currency($in=NULL)
	{
		global $paidSub;
		if(!$in)
			$in = $paidSub->currency;
		return $paidSub->allowedCurrency[$in];
	}

	/**
	* Function used to tell days left
	*/
	function daysLeft($end)
	{
		$start = strtotime(now());
		$end = strtotime($end);
		$diff = $end-$start;
		
		if($diff<0)
			return 0;
		else
			return $diff/60/60/24;
	}


	/**
	* Function used to display Buy Premium button
	*/
	function buyPremiumBttn($in=NULL)
	{
		Template(PAID_SUBS_DIR.'/anchors/buy_premium.html',false);
	}

	//function used to dispaly make premium link
	function make_premium_link($vid)
	{
		global $paidSub;

		$query_string = queryString( null, array( 'make_premium','remove_premium' ) );
		if($vid['is_premium']!='yes' && $vid['is_premium']!='ppv')
			$return = ' <a href="javascript:void(0)"
		onclick="$(this).hide();$(\'#prem_span_'.$vid['videoid'].'\').show()"><span class="label label-primary">
		Make Premium</span></a>';
		else
			$return =' <a href="'.( $query_string ).'remove_premium='.$vid['videoid'].'" title="This will also remove video from all packages">
		<span class="label label-warning">Remove From Premium</span></a>';
		
		
		$return .= '
		
		<span id="prem_span_'.$vid['videoid'].'" style="display:none;">
			<form style="display:inline-block" method="post">
				<label>
					<input type="radio" name="premium" value="premium"
					onchange="shw_credits(\'no\',\''.$vid['videoid'].'\')"
					checked>Premium
				</label>
				
				<label>
					<input type="radio" name="premium" value="ppv" 
					onchange="shw_credits(\'yes\',\''.$vid['videoid'].'\')">PPV
				</label>
				<label id="credits_'.$vid['videoid'].'" style="display:none">
					Credits
					<input type="text" name="credits" value="">
				</label>
				
				<select name="premium_cid">
					<option value="0"> - Add to premium collection -</option>
					';
					
					//adding options value 
					$cpkgs = $paidSub->getPackages(array('is_collection'=>'yes'));
					if($cpkgs)
						foreach($cpkgs as $pkg)
						{
							$return .= '<option value="'.$pkg['package_id'].'">'.$pkg['pkg_title'].'</option>';
						}
						$return .=	'
					</select>


					<input type="hidden" value="'.$vid['videoid'].'" name="videoid">
					<input type="submit" value="Add" name="make_premium" class="btn btn-success btn-xs">

				</form>
			</span>


			';
			return $return;


		}

		function make_premium_video()
		{
			global $paidSub,$cbvid,$db;

			if(isset($_POST['make_premium']))
			{
				$videoid = mysql_clean(post('videoid'));
				$video = $cbvid->get_video($videoid);
				$premium_cid = post('premium_cid');

				if(!$video)
					e("Invalid video");

				if($video['is_premium']=='yes' || $video['is_premium']=='ppv')
					e("Videos is already set as premium");

				if(post('premium')=='ppv' && (!is_numeric(post('credits')) || post('credits')<1))
					e("Please set credit limit of PPV video"); 
				
				if(error())
					return false;

				if(post('premium')=='ppv')
					$prem = 'ppv';
				else
					$prem = 'yes';

				$credits = mysql_clean(post('credits'));

			//Updating video
				$db->update(tbl("video"),array("is_premium",
					"credits_required"),array($prem,$credits),
				"videoid='".$video['videoid']."'");

				if($premium_cid && !$paidSub->in_package_collection($videoid,$premium_cid))
					$db->insert(tbl("paid_pkg_videos"),array('package_id','videoid'),
						array($premium_cid,$videoid));		

				e("Video has been made premium","m");

			}

			if( isset( $_GET[ 'remove_premium' ] ) ) {
				$videoid = mysql_clean( $_GET[ 'remove_premium' ] );
				$video = $cbvid->get_video( $videoid, false, true );
				$types = array( 'yes', 'ppv' );


				if( !$video ) {
					e("Invalid video");
					return false;
				}

				if ( !in_array( $video[ 'is_premium' ], $types ) or $video[ 'is_premium' ] == 'no' ) {
					e( 'Video is not premium' );
					return false;
				}

            /**
             * Remove Video from being premium
             */
            $updated = $db->update( tbl( 'video' ), array( 'is_premium' ), array( 'no' ), " videoid = '".$video[ 'videoid' ]."' " );

                /**
                 * Time to remove video from packages. Since a single
                 * video can be added in multiple packages, we will
                 * not add a LIMIT parameter removing video from all
                 * packages
                 */
                $query = "DELETE FROM ".tbl( 'paid_pkg_videos' )." WHERE videoid = '".$video[ 'videoid' ]."' ";
                $db->Execute( $query );

                e( lang( 'Video has been removed from premium' ), 'm' );
                return true;


                e( lang( 'Unable to remove video from being Premium' ) );
                return false;
            }
        }


        function add_to_package_collection($vid,$pid)
        {
        	global $paidSub,$cbvid,$db;	

        	$videoid = $vid;

        	if($pid && !$paidSub->in_package_collection($videoid,$pid))
        		$db->insert(tbl("paid_pkg_videos"),array('package_id','videoid'),
        			array($pid,$videoid));		

        	e("Added to package","m");

        }


	/**
	 * function used to verify if video is premium or not
	 */
	function play_premium($vdo)
	{
		//changing video seconds into minutes
		if($vdo['duration'])
			$vdo_mins = $vdo['duration']/60;
		
		global $userquery,$paidSub;
		
		$is_premium = false;
		$valid_sub = false;
		$in_package = false;
		
		if($vdo['is_premium']=='yes' || 
			($vdo['is_premium']=='ppv' 
				&& $vdo['credits_required']>0) || $paidSub->configs['premium_type']=='all')
		{			
			$is_premium = $vdo['is_premium'];
			
			$subs_list = $paidSub->getUserSubscription(userid(),true);
			if($subs_list)
				foreach($subs_list as $sub)
				{
					if($paidSub->in_package($vdo,$sub))
					{
						$valid_sub = $sub['subscription_id']; 
						$in_package = true;
						break;
					}
					if($sub['pkg_type']=='ppv' && $vdo['is_premium']=='ppv')
					{
					//checking credits
						if($vdo['credits_required'] <= ($sub['pkg_credits']-$sub['credits_used']))
						{
							$valid_sub = $sub['subscription_id']; 
							break;
						}
					}else
					{
						$type = $sub['pkg_type'];

						switch($type)
						{
							case "subs":
							{
							//making sure number for videos watched are less than 
							//allowed
								if($sub['pkg_vids']==0 || $sub['watched']<$sub['pkg_vids'])
								{
									$valid_sub = $sub['subscription_id']; 
									break;
								}
							}
							break;

							case "mins":
							{
								$watched_mins = $sub['watched_time']/60;
							//Checking if user is allowed to watch this video
							//and have minutes more than that a video has
								if($vdo_mins<= ($sub['pkg_mins'] - $watched_mins))
								{
									$valid_sub = $sub['subscription_id']; 
									break;
								}								 
							}
							break;

							case "vids":
							{
							 //making sure number for videos watched are less than 
							//allowed
								if($sub['pkg_vids']==0 || $sub['watched']<$sub['pkg_vids'])
								{
									$valid_sub = $sub['subscription_id']; 
									break;
								}
							}
							break;
					} //switch
					
					
					if($vdo['is_premium']=='ppv')
					{
						if($sub['pkg_ppv']==0 || !$sub['pkg_ppv'])
							$valid_sub = false;
						elseif($sub['pkg_ppv']<=$sub['watched_ppv'])
							$valid_sub = false;
					}

				} //else
				
			} //foreach
			
		}// if
		
		if(!$valid_sub && !$in_package && $is_premium)
		{

			//Now Checking for demo
			$demo = $paidSub->demo_details();
			$allowed = $paidSub->configs['allow_demo'];
			$allow_type = 'videos';
			
			$valid_demo = false;
			
			if($paidSub->configs['demo_allow_type']=='minutes')
			{
				$allow_type = 'minutes';
				$duration = $vdo['duration'];
				$allowed_duration = $allowed*60;
				
				if($allowed_duration - $demo['watched_time'] >= $duration)
				{
					$allowed_left = $allowed_duration - $demo['watched_time'];
					$valid_demo = true;
				}
			}else
			{
				/*
				*  Edited by : Fahad Abbas ( Clipbucket Web Developer )
				*  Reason : To fix the number of demo allowed issue to Public users   
				*  Date : 24-05-2016
				*/
				if (!$demo['watched']){
					$demo['watched'] = 0;
				}
				if($allowed > $demo['watched'])
				{
					$allowed_left = $allowed - $demo['watched'];
					$valid_demo = true;
				}
			}
			
			
			if($valid_demo)
			{
				return array(
					'is_premium'=>$is_premium,
					'valid_demo'=>true,
					'allowed_type'=>$allow_type,
					'allowed_left'=>$allowed_left,
					'demo_id'=>$demo['demo_id'],
					'allowed'=>$allowed);	
			}
		}
		
		$prem_video = array('is_premium'=>$is_premium,'valid_sub'=>$valid_sub,'in_package'=>$in_package);
		return $prem_video;
	}
	
	
	function watch_premium($vdo,$api=false)
	{
		global $paidSub, $userquery;
		$prem_video = play_premium($vdo);
		
		extract($prem_video);
		if(!$is_premium){
			return true;
		}
		if($is_premium && $in_package){
			return true;
		}
		/* this condition has benn added for if user is admin or uploader, return true*/
		if( has_access('admin_access',TRUE) || $vdo['userid'] == userid()){
			return true;
		}

		if ($api){
			if (!$valid_sub){
				return false;
			}else{
				return true;
			}
		}
		

		if(!$valid_sub && !$valid_demo){
			redirect_to(PAID_SUBS_MOD_URL.'&v='.$vdo['videoid']);
			//template_files('buy_premium.html',PAID_SUBS_DIR.'/templates/');
		}else{
			//checking if user has clicked on watch this video now button
			if(isset($_POST['watch_prem_video'])){
				if($valid_demo){
					if($paidSub->watchDemoVideo($vdo,$prem_video)){
						return true;
					}
				}else{
					if($paidSub->watchPremiumVideo($vdo,$valid_sub)){
						return true;
					}
				}
			}
			
			if($valid_demo)
			{
				assign('demo',$prem_video);
				assign('vdo',$vdo);
				template_files('watch_premium_demo.html',PAID_SUBS_DIR.'/templates/');
			}else
			{
				$package = $paidSub->getSubsWithPackage($valid_sub);
				assign('package',$package);
				template_files('watch_premium.html',PAID_SUBS_DIR.'/templates/');
			}
		}
		
		display_it();
		exit();
	}
	
	
	/**
	 * function used to get premium videos
	 */
	function get_premium_videos($params)
	{
		$cond = $params['cond'];
		$params = $params['params'];
		
		if($params['premium'])
		{
			if($cond)
				$cond .= " AND ";
			
			if($params['premium']=='all')
			{
				$cond .= " ( ";
				$cond .= tbl('video.is_premium')." ='ppv'";
				$cond .= " OR ".tbl('video.is_premium')." ='yes'";
				$cond .= " ) ";
			}else
			$cond .= tbl('video.is_premium')."='".$params['premium']."'";
			
			return $cond;
		}
	}
	
	
	function days_type($days,$amount=false,$type=false)
	{
		
		if($days<=90)
		{
			$d = $days;
			$t = 'D';
			if($type=='alertpay')
				$t = 'Day';
		}
		else
		{
			
			if($days/7 <= 52)
			{
				$d = round($days/7);
				$t = 'W';
				if($type=='alertpay')
					$t = 'Week';

			}elseif($days/31 <= 24)
			{
				$d = round($days/31);
				$t = 'M';
				if($type=='alertpay')
					$t = 'Month';

			}elseif($days/365 <= 5)
			{
				$d = round($days/31);
				$t = 'Y';
				if($type=='alertpay')
					$t = 'Year';
			}
		}
		
		if($amount)
			echo $d;
		else
			echo $t;
	}
	
	/**
	 * function used to get package videos 
	 */
	function get_package_videos($params)
	{
		$cond = $params['cond'];
		$params = $params['params'];
		
		if($params['premium_cid'])
		{
			if($cond)
				$cond .= " AND ";
			
			$cond .= tbl('video.premium_cid')."='".$params['premium_cid']."'";
			
			return $cond;
		}
	}
	
	/**
	 * function used to display add to premium link and its functionality
	 */
	function add_to_package_link($vid=NULL)
	{
		global $paidSub;
		
		if($vid)
		{
		//queryString('make_premium','remove_premium')
			if($vid['is_premium']=='yes')
				$return = ' | <a href="javascript:void(0)"
			onclick="$(this).hide();$(\'#pkg_span_'.$vid['videoid'].'\').show()">Add To Collection</a>';

			$return .= '

			<span id="pkg_span_'.$vid['videoid'].'" style="display:none;">
				<form style="display:inline-block" method="post">
					<select name="premium_cid" id="pkg_vid_'.$vid['videoid'].'">
						<option value="0"> - Add to premium collection -</option>
						';

					//adding options value 
						$cpkgs = $paidSub->getPackages(array('is_collection'=>'yes'));
						if($cpkgs)
							foreach($cpkgs as $pkg)
							{
								$return .= '<option value="'.$pkg['package_id'].'">'.$pkg['pkg_title'].'</option>';
							}
							$return .=	'
						</select>

						<input type="button" value="Add" 
						name="make_premium" onclick="add_to_package_link(\''.$vid['videoid'].'\')">

					</form>
				</span>


				';
				return $return;
			}

		}

/**
 * This actually updates the permission for 'allow_make_premium',
 * according to option selected from configurations page.
 *
 * This will update permission for all levels except for Administrator.
 * If you want to specifically change permission for level, please
 * use User Levels from Admin Area
 *
 * @param STRING $new_access
 * @param STRING $old_access
 * @return bool
 */
function update_user_premium_access( $new_access, $old_access ) {
	global $db;

	if( $new_access == $old_access ) {
		return false;
	}

	$query = $db->update( tbl( 'user_levels_permissions' ), array( 'allow_make_premium' ), array( $new_access ), " user_level_id <> 1 " );

	if( $query ) {
		return true;
	} else {
		return false;
	}
}

function insert_plan_details( $response ) {

	global $db;

	$billing_plan_query = "INSERT INTO ".tbl("paid_billing_plan")." (plan_id , package_id , pkg_title , plan_state , plan_name , plan_description , plan_type , plan_pd_id , plan_pd_name , plan_pd_type , plan_pd_frequency , plan_currency , plan_amount , plan_cycles , plan_frequency_interval , plan_setup_fee , plan_auto_bill_amount , plan_create_time , plan_update_time) 
	VALUES ('".$response['id']."' , '".$response['package_id']."' , '".$response['pkg_title']."' , '".$response['state']."' , '".$response['name']."' , '".$response['description']."' , '".$response['type']."' , '".$response['payment_definitions'][0]['id']."' , '".$response['payment_definitions'][0]['name']."' , '".$response['payment_definitions'][0]['type']."' , '".$response['payment_definitions'][0]['frequency']."' , '".$response['payment_definitions'][0]['amount']['currency']."' , '".$response['payment_definitions'][0]['amount']['value']."' , '".$response['payment_definitions'][0]['cycles']."' , '".$response['payment_definitions'][0]['frequency_interval']."' , '".$response['merchant_preferences']['setup_fee']['value']."' , '".$response['merchant_preferences']['auto_bill_amount']."', '".$response['create_time']."' , '".$response['update_time']."') ";

	if($db->Execute($billing_plan_query) ) {
    		// echo "New billing plan has been created! the Plan id is ".$response['id'];
		return true;
	}else{
		
		return false;
	}
} 
function update_plan_details( $planid ) {
	global $db;
	// echo $planid;
	$update_plan_query="UPDATE ".tbl("paid_billing_plan")." SET plan_state = 'ACTIVE' WHERE plan_id = '".$planid."'";

	if($db->Execute($update_plan_query) ){
		return true;
	}else{
		return false;
	}

}
function get_plan_details( $p_id ) {
	global $db;
	
	if(!isset($p_id)){
		$get_plan_query="SELECT * FROM ".tbl("paid_billing_plan");
	}else{
		$get_plan_query="SELECT * FROM ".tbl("paid_billing_plan")." WHERE p_id = '".$p_id."'";
	}
	// echo $get_plan_query;
	$results = $db->Execute($get_plan_query);
	$result=array();
	while ($obj = $results->fetch_object() )
	{
				//pre($obj);
		$result[$obj->p_id] = array(
			'p_id'=>$obj->p_id,
			'plan_id'=>$obj->plan_id,
			'package_id'=>$obj->package_id,
			'pkg_title'=>$obj->pkg_title,
			'plan_state'=>$obj->plan_state,
			'plan_name'=>$obj->plan_name,
			'plan_description'=>$obj->plan_description,
			'plan_type'=>$obj->plan_type,
			'plan_pd_id'=>$obj->plan_pd_id,
			'plan_pd_name'=>$obj->plan_pd_name,
			'plan_pd_type'=>$obj->plan_pd_type,
			'plan_pd_frequency'=>$obj->plan_pd_frequency,
			'plan_currency'=>$obj->plan_currency,
			'plan_amount'=>$obj->plan_amount,
			'plan_cycles'=>$obj->plan_cycles,
			'plan_frequency_interval'=>$obj->plan_frequency_interval,
			'plan_setup_fee'=>$obj->plan_setup_fee,
			'plan_auto_bill_amount'=>$obj->plan_auto_bill_amount,
			'plan_create_time'=>$obj->plan_create_time,
			'plan_update_time'=>$obj->plan_update_time
			);
	}

	if($result){
		return $result;
	}else{
		return false;
	}

}
function delete_plan( $p_id ) {
	global $db;

	if(isset($p_id)){
		$delete_plan_query="DELETE FROM ".tbl("paid_billing_plan")." WHERE p_id = '".$p_id."'";
	}else{
		e("there is somthing wrong with yor id");
		exit;
	}

	// echo $get_plan_query;
	if($db->Execute($delete_plan_query)){
		return true;
	}else{
		return false;
	}

}

