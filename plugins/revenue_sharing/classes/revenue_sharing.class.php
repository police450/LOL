<?php

/**
 * This class will operate
 * all the functions
 * that are required to make a possible
 * solution for rev sharing for ClipBucket
 *
 * @Author : Awais Fiaz
 * @Built-for : ClipBUcket 2.x.x
 * @License : Commercial [EULA]
 *
 * Code Model : snake_case
 */


class revSharing
{

	
	function __construct(){
    // do nothing

	}

	/**
    * Used to update reveue sharing configurations
    * @param   : {$configurations_array}
    * @author  : Awais Fiaz
    */

	function update_rev_configs($params)
	{
		global $db;
		
		
		if(!empty($params)){
			
			if($params['rev_view_per_matrix'] <= 10000 && $params['rev_view_per_matrix']!=0){
				
				if($params['rev_paypal_email']){
					if (!filter_var($params['rev_paypal_email'], FILTER_VALIDATE_EMAIL)) {
						throw new Exception("Invalid email format!");
					}	
				}

				if($params['rev_paypal_sandbox_email']){
					if (!filter_var($params['rev_paypal_sandbox_email'], FILTER_VALIDATE_EMAIL)) {
						throw new Exception("Invalid sandbox email format!");
					}	
				}
				
				unset($params['rev_update_confs']);
				// pr($params,true);
				foreach ($params as $key => $value) {
					# code...
					$param_value = mysql_clean($value);
					// pr($param_value." ".$key,true);
					$db->update(tbl('config'),array('value'),array($param_value)," name='".$key."'");

				}
				
				$response = "Configurations updated!";
				return $response;
				
			}else{

				throw new Exception("Views value niether can be 0 and nor greater than 10000");

			}

		}else{

			throw new Exception("Views Must be integer value!");
			
		}
	}

	/**
    * Used to get reveue sharing configurations
    * @author  : Awais Fiaz
    */

	function get_rev_configs()
	{
		# code...
		global $Cbucket;
		$rev_configs = $Cbucket->configs;
		// pr($rev_configs,true);
		if(!empty($rev_configs)){

			return $rev_configs;

		}else{

			throw new Exception("There was an error getting configs!");

		}
	}
	
	/**
    * Used to get request about revsharing
    * @author  : Awais Fiaz
    */

	function get_users_requests()
	{
		# code...
		global $db;
		// $requests=$db->select(tbl("revsharing_requests"),"*");
		$requests=$db->select(tbl("revsharing_requests"),"*"," request_state BETWEEN '0' AND '2'");
		return $requests;
	}

	
	/**
    * Used to get all earning users according to there state
    * @param   : {'active', 'inactive', ''}
    * @author  : Awais Fiaz
    */

	function get_earning_users($state)
	{
		# code...
		global $db;
		if($state=='active'){
			
				$earning_users=$db->select(tbl("revsharing_users"),"*"," status = 1");

		}elseif($state=='inactive'){

				$earning_users=$db->select(tbl("revsharing_users"),"*"," status = 0");
		}else{

				$earning_users=$db->select(tbl("revsharing_users"),"*");
		}


		return $earning_users;
	}

	/**
    * Used to get one earning user details according to there userid
    * @param   : {$userid}
    * @author  : Awais Fiaz
    */

	function get_eu_details($userid)
	{
		# code...
		global $db;

		$eu_details=$db->select(tbl("revsharing_users"),"*"," userid='".$userid."'");
		return $eu_details;
	}

	/**
    * Used to update Earning user details
    * @param   : {$eu_details_array}
    * @author  : Awais Fiaz
    */
	
	function update_eu_details($params)
	{
		# code...
		global $db;
		// pr($params,true);
		// exit;
		if($params['eu_email']){
			if (!filter_var($params['eu_email'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception("Invalid email format!");
			}	
		}

		if($params['phone_no']){
			if (!preg_match('/^\+\d+$/', $params['phone_no'])){ 
			    	throw new Exception("Invalid phone number!");
			    }
		}

		$db->update(tbl('revsharing_users'),array('phone_no','paypal_email','bank_acc_no'),array($params['phone_no'],$params['eu_email'],$params['eu_bankacc_no'])," userid='".$params['userid']."'");
		$response="Earning user info has been updated";
		return $response;
	}

	/**
    * Used to update Earning user status
    * @param   : {status,params(userid in specific)}
    * @author  : Awais Fiaz
    */

	function update_eu_status($status,$params)
	{
		# code...
		global $db;
		if($status=='activate'){
			if($params['userid']!=NULL && !empty($params['userid'])){

				$db->update(tbl('revsharing_users'),array('status'),array(1)," userid='".$params['userid']."'");
				$response="User earning status has been activated";
				return $response;

			}else{

				throw new Exception("Userid is empty can't process your request");
				
			}

		}elseif($status=='deactivate'){
			if($params['userid']!=NULL && !empty($params['userid'])){

				$db->update(tbl('revsharing_users'),array('status'),array(0)," userid='".$params['userid']."'");
				$response="User earning status has been deactivated";
				return $response;

			}else{

				throw new Exception("Userid is empty can't process your request");
				
			}
		}
	}

	/**
	* Used to update earning user request status 
	* @param  : {$update_type,userid}
	* @author  : Awais Fiaz
	*/

	function update_request_status($update_type,$userid)
	{	
		global $db;
		$approve_state=3;
		$review_later_state=2;
		$reject_state=1;

		if(!$userid){
			throw new Exception("Userid is required! which is empty!");
			
		}
		
		switch ($update_type) {
			case 'approve':
			

			$requested_u_data = $db->select(tbl('revsharing_requests'),'publisher_website,country,phone_no,paypal_email,bank_acc_no'," userid='".$userid."'");
			// pr($requested_u_data,true);
			// exit("yooo");
			$db->insert(tbl('revsharing_users'),array("userid","status","earning_user_from","publisher_website","country","phone_no","paypal_email","bank_acc_no"),array($userid,1,dateNow(),$requested_u_data[0]['publisher_website'],$requested_u_data[0]['country'],$requested_u_data[0]['phone_no'],$requested_u_data[0]['paypal_email'],$requested_u_data[0]['bank_acc_no']));
			
			$db->delete(tbl("revsharing_requests"),array("userid"),array($userid));
			
			$response="Request has been approved user has been added to earning users";
			
			return $response;

			break;

			case 'reject':

			$db->update(tbl('revsharing_requests'),array('request_state'),array($reject_state)," userid='".$userid."'");
			$response="Request has been rejected";
			return $response;

			break;
			case 'review_later':

			$db->update(tbl('revsharing_requests'),array('request_state'),array($review_later_state)," userid='".$userid."'");
			$response="Request status has been changed to review later";
			return $response;

			break;		
			
			default:

			throw new Exception("Request status can't be updated! update type is empty!");
			break;
		}
	}

	/**
	* Used to get default and specific user's RPM
	* @param  : {$userid}
	* @author  : Awais Fiaz
	*/

	function get_rpm($userid)
	{
		global $db;
		$rpms=$db->select(tbl("revsharing_rpm"),"*"," userid='0' OR userid='".$userid."'");
		return $rpms;


	}

	/**
	* Used to get rpm wit rpm_id
	* @param  : {$rpm_id}
	* @author  : Awais Fiaz
	*/

	function get_rpm_with_rpmid($rpm_id)
	{
		global $db;
		$rpm=$db->select(tbl("revsharing_rpm"),"*"," rpm_id='".$rpm_id."'");
		return $rpm[0];


	}

	/**
	* Used add rpm for a spcific user
	* @param  : {array of submited parameters in rpm form}
	* @author  : Awais Fiaz
	*/

	function add_rpm($params)
	{	
		global $db;
		# code...
		if(is_array($params) && !empty($params)){

			if(isset($params['add_rpm'])){

				$userid = $params['userid'];
				$tier = mysql_clean($params['tier_name']);
				$rpm = mysql_clean($params['rpm']);
				$countries = mysql_clean($params['countries']);
				
				if(!is_numeric($userid)){

					throw new Exception("Userid can't be a string");

				}elseif($tier==NULL || empty($tier)){

					throw new Exception("Tier name can't be a be empty");

				}elseif($countries==NULL || empty($countries)){

					throw new Exception("Countries can't be a be empty");

				}elseif(!is_numeric($rpm)){
					
					throw new Exception("Rpm can only be an integer and decimal value");

				}else{

					$db->insert(tbl('revsharing_rpm'),array("userid","tier_name","rpm","countries"),array($userid,$tier,$rpm,$countries));
					$response='New rpm has been added for '.get_username($userid);
					return $response;
				}
			}
			
		}else{

			throw new Exception("Parameters of adding a tier are empty");
			

		}
	}

	/**
	* Used to update of any user
	* @param  : {array of parameters containing rpm details}
	* @author  : Awais Fiaz
	*/
	
	function update_rpm($params)
	{	
		global $db;
		# code...
		
		if(is_array($params) && !empty($params)){

			if(isset($params['update_rpm'])){

				$rpm_idd = $params['rpm_idd'];
				$tier = mysql_clean($params['tier_name']);
				$rpm = mysql_clean($params['rpm']);
				$countries = mysql_clean($params['countries']);
				
				if(!is_numeric($rpm_idd)){

					throw new Exception("Userid can't be a string");

				}elseif($tier==NULL || empty($tier)){

					throw new Exception("Tier name can't be a be empty");

				}elseif($countries==NULL || empty($countries)){

					throw new Exception("Countries can't be a be empty");

				}elseif(!is_numeric($rpm)){
					
					throw new Exception("Rpm can only be an integer and decimal value");

				}else{

					$db->update(tbl('revsharing_rpm'),array("tier_name","rpm","countries"),array($tier,$rpm,$countries)," rpm_id='".$rpm_idd."'");
					$response='Rpm has been updated for '.get_username($userid);
					return $response;
				}
			}
			
		}else{

			throw new Exception("Parameters of adding a tier are empty");
			

		}
	}

	/**
	* Used to update default rpm
	* @param  : new rpm value
	* @author  : Awais Fiaz
	*/

	function update_dafault_rpm($params)
	{	
		global $db;
		# code...
		
		if(is_array($params) && !empty($params)){

			if(isset($params['update_d_rpm'])){

				$rpm_val = mysql_clean($params['rpm_val']);
				
				// pr($params,true);
				// exit;
				if(!is_numeric($rpm_val)){
					
					throw new Exception("Rpm value can't be empty and can only be an integer or decimal value");

				}else{

					$db->update(tbl('revsharing_rpm'),array("rpm"),array($rpm_val)," userid='0'");
					$response='Default rpm value has been updated';
					return $response;
				}
			}
			
		}else{

			throw new Exception("Parameter of adding a default rpm is empty");
			

		}
	}

	/**
	* Used to get default rpm
	* @author  : Awais Fiaz
	*/

	function get_default_rpm()
	{
		# code...
		global $db;
		$default_rpm=$db->select(tbl('revsharing_rpm'),'rpm'," userid='0'");
		if($default_rpm[0]){

			return $default_rpm[0];

		}else{

			throw new Exception("There was an error getting default RPM!");

		}
	}

	/**
	* Used to remove rpm according to rpm_id
	* @param  : {$rpm_id}
	* @author  : Awais Fiaz
	*/

	function remove_rpm($rpm_id)
	{
		global $db;
		$db->delete(tbl("revsharing_rpm"),array("rpm_id"),array($rpm_id));
		$response='Rpm has been deleted for '.get_username($userid);
		return $response;
	}

	/**
	* Used to create request to become an earning user from front-end
	* @param  : {$user_submited_data_array}
	* @author  : Awais Fiaz
	*/

	function create_eu_request($params)
	{	
		global $db;
		if(is_array($params)){
			$userid = $params["userid"];
			$publisher_website = mysql_clean($params["pub_website"]);
			$country = $params["publisher_country"];
			$phone_no = $params["phone_no"];
			$email = mysql_clean($params["paypal_email"]);
			$account_number = mysql_clean($params["account_no"]);
			
			if($userid){
				$user_check=$db->select(tbl('revsharing_requests'),'userid,request_state'," userid='".$userid."'");
				
				if(!empty($user_check)){
					
					if($user_check[0]['request_state']==2){

						$r_status="REVIEW LATER";

					}elseif($user_check[0]['request_state']==1){
						
						$r_status="REJECTED";

					}else{

						$r_status="NOT REVIEWED";
					}

					throw new Exception("Your request is already pending with status <strong>".$r_status."</strong> please contact admin for further query");
				}


				$eu_user_check=$db->select(tbl('revsharing_users'),'userid'," userid='".$userid."'");
				if(!empty($eu_user_check)){
					
					throw new Exception("You are already an earning user!");
					
				}

			}



			if(!$publisher_website){

				throw new Exception("Website is empty please add one!");

			}elseif(!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$publisher_website)) {

				throw new Exception("Invalid website's URL");


			}elseif(!$country){

				throw new Exception("Country is empty add one!");
				

			}elseif(!$phone_no){

				throw new Exception("Phone is empty add one!");
				

			}else{

				if($email){
					if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
						throw new Exception("Invalid email format!");
					}	
				}

				if($phone_no){
					if (!preg_match('/^\+\d+$/', $phone_no)){ 
					    	throw new Exception("Invalid phone number!");
					    }
    			}

				$db->insert(tbl('revsharing_requests'),array("userid","publisher_website","country","phone_no","paypal_email","bank_acc_no"),array($userid,$publisher_website,$country,$phone_no,$email,$account_number));

				$response="Earning user request has been sent please wait till admin responds you back.";
				return $response;

			}


		}


	}

	/**
    * used to get earning user requests and display them in notification
    * @author  : Awais Fiaz
    */

	function get_notifications()
	{
		# code...
		global $db;
		$notify_data=$db->select(tbl("revsharing_requests"),"*"," request_state=0");
		$notification_message=array();
		if(!empty($notify_data)){
			$request_notification=count($notify_data);
			$notification_message[]="You have ".$request_notification." pending request(s) queued please check <strong><a class='alertlink' 
			href=".baseurl()."/plugin.php?folder=revenue_sharing/admin&file=request_manager.php>Request manager!</a></strong>";
			return $notification_message[0];
		}else{

			$notification_message[]="You currently don't have any pending notification";
			return $notification_message[0]; 
		}
		

	}

	/**
    * Demo function ignore it! look at the next function made for the same purpose
    * @param  : {$userid}
    * @author  : Awais Fiaz
    */

	function countviewsuserid2($userid)
	{
    	# code...
		$collection = mongoConViews();
		$view_result = $collection->find(
			[   
				'userid' => (int)$userid
			]
		);

		$view_w_cc=array();
		$view_total_count=0;
		foreach ($view_result as $entry) {
			$views_w_cc[]=$entry['country_code'];
			$view_total_count+=1;

		}

		$views_w_cc['count']=$view_total_count;		

		return $views_w_cc;


	}

	/**
    * Used to aggregate mongo views against userid
    * @param  : {$userid}
    * @author  : Awais Fiaz
    */

	function countviewsuserid($userid)
	{
    	# code...
		$collection = mongoConViews();
		$view_result = $collection->find(
			[   
				'userid' => (int)$userid,
				'flag' => (int)0
			]
		);

		$view_w_cc=array();
		$view_total_count=0;
		foreach ($view_result as $entry) {
			
			$view_total_count+=1;

			$views_w_cc[$entry['country_code']][]=$entry['country_code'];
			$views_w_cc_temp[$entry['country_code']]  = count($views_w_cc[$entry['country_code']]);
			$views_w_cc_final = $views_w_cc_temp;
			

		}

		
		$views_w_cc_final["total"] = $view_total_count;

		return $views_w_cc_final;


	}

	/**
    * Used to make earnings according to defined tier
    * @param  : {$userid,$country_code,$number_of_views}
    * @author  : Awais Fiaz
    */

	function make_earnings_accordingto_counrty($userid,$c_code,$no_of_views)
	{
		# code...
		global $db;

		$uid = $userid;
		$c_c = $c_code;
		$nov = $no_of_views;		
		
		$rpm_data=$db->select(tbl("revsharing_rpm"),"*"," userid='".$uid."'");
		$def_rpm=$db->select(tbl("revsharing_rpm"),"*"," userid=0");
		
		$default_rpm = $def_rpm[0]['rpm'];

		// pr($rpm_data,true);
		$rate_check=array();
		
		foreach($rpm_data as $counrty_index => $country){
			# code...
			// pr($value['countries'],true);
			$rpm_rate = $country['rpm'];
			$country_array = explode(',', $country['countries']);
			// pr($rpm_rate,true);
			// pr($country_array,true);

			foreach ($country_array as $key => $value) {

				if(strcasecmp($value, $c_c) == 0){
					$rate_check[$rpm_rate]='matched';
					break;

				}else{
					$rate_check[$default_rpm]='unmatched';
				}

			}
		}
		
		$custom_rpm = array_search('matched', $rate_check);
		if($custom_rpm){

			$rate = $custom_rpm;

		}else{
			$rate = $default_rpm;
		}
		
		$vpm = $db->select(tbl('config'),"value"," name='rev_view_per_matrix'");
		$views_per_matrix =$vpm[0][value];

		pr("Rate for ".$c_code." = ".$rate." No of views = ".$no_of_views." Views per matrix : ".$views_per_matrix,true);

		$cal1 = $no_of_views*$rate;
		$revenue = $cal1/$views_per_matrix;

		pr('Revenue is : '.$revenue,true);

		return $revenue;

	}

	/**
    * Used to aggregate user earnings and add them to sql
    * @param  : {$userid,$total_earnings,$number_of_views}
    * @author  : Awais Fiaz
    */

	function add_eu_views_earnings($uid,$t_earnings,$no_of_views)
	{
		global $db;
		$collection = mongoConViews();

		$db->insert(tbl('revsharing_earnings'),array("userid","earnings","views","date_time"),array($uid,$t_earnings,$no_of_views,NOW()));
		
		// updateOne (alternative)
		$update_views=$collection->updateMany(
			[
				'flag'=> (int)0,
				'userid' => (int)$uid

			],
			['$set' => ['flag' => (int)1]]			
		);
		
		$response="Earning for ".get_username($uid)." is ".$t_earnings." no of views ".$no_of_views." added to earnings table of sql \r\n".$update_views->getMatchedCount()." Matched Views found ".$update_views->getModifiedCount()." Views Modified!";

		return $response;


	}

	/**
    * Used to get aggregated views against user
    * @param  : {$userid}
    * @author  : Awais Fiaz
    */

	function get_eu_views($userid)
	{
		global $db;
		$u_views=$db->select(tbl('revsharing_earnings'),"views"," userid='".$userid."' AND paid_check=0");
		
		foreach ($u_views as $k=>$subArray) {
		  
		  foreach ($subArray as $views=>$value) {

		    $total_u_views[$views]+=$value;
		  
		  }

		}
		// pr($total_u_views['views'],true);
		if($total_u_views['views']){
				return $total_u_views['views'];
		}else{
				return '0';
		}

	}

	/**
    * Used to aggregate earnings against user
    * @param  : {$userid}
    * @author  : Awais Fiaz
    */

	function get_eu_earnings($userid)
	{
		global $db;
		$u_earnings=$db->select(tbl('revsharing_earnings'),"earnings"," userid='".$userid."' AND paid_check=0");
		
		// pr($u_earnings,true);

		foreach ($u_earnings as $k=>$subArray) {
		  
		  foreach ($subArray as $earnings=>$value) {

		    $total_u_earnings[$earnings]+=$value;
		  
		  }

		}
		
		// pr($total_u_earnings['earnings'],true);
		if($total_u_earnings['earnings']){
				return $total_u_earnings['earnings'];
		}else{
				return '0';
		}
	}

	/**
    * Used to get stats of user or overall
    * @param  : {$userid}
    * @author  : Awais Fiaz
    */

	function get_stats($userid)
	{
		# code...
		global $db;

		$stat_dates=$db->select(tbl('revsharing_earnings'),"DISTINCT(date_time)");
		// pr($stat_dates,true);
		foreach ($stat_dates as $key => $value) {
			
			$dates[]=$value['date_time'];
		
		}
		
		// pr($dates,true);
		if($userid){

			foreach ($dates as $key => $single_date) {
				$earnings_stats[$single_date]=$db->select(tbl('revsharing_earnings')," sum(earnings) , sum(views) "," date_time='".$single_date."' AND userid='".$userid."'");
			}

		}else{
			
			foreach ($dates as $key => $single_date) {
				$earnings_stats[$single_date]=$db->select(tbl('revsharing_earnings')," sum(earnings) , sum(views) "," date_time='".$single_date."'");
			}

		}

		// pr($earnings_stats,true);
		$count=0;
		foreach ($earnings_stats as $date => $stats) {
			foreach ($stats as $key => $single_stats) {
				
				$final_array[$count]['date'] = $date;
				$final_array[$count]['views'] = $single_stats['sum(views)'];
				$final_array[$count]['earnings'] = $single_stats['sum(earnings)'];

			}
			$count+=1;
		}

		// pr($final_array,true);
		$graph_data = json_encode($final_array);
		

		return $graph_data;
	}

	/**
    * Used to pay user via paypal and mark earnings paid
    * @param  : {$user_paypal_email,$earning_amount,$userid}
    * @author  : Awais Fiaz
    */

	function pay_via_paypal($eu_pp_email,$eu_earning_amount,$userid)
	{
		global $Cbucket,$db;
		
		$pprest = new revPaypalRest();
		$rev_configs = $Cbucket->configs;
		
		$currency_type = $rev_configs['rev_currency'];
		
		// pr($eu_earning_amount,true);

		if(empty($rev_configs['rev_paypal_client_id'])){
			
			throw new Exception("Paypal Client id is null");
		
		}elseif(empty($rev_configs['rev_paypal_secret'])){
		
			throw new Exception("Paypal client secret is null", 1);
		
		}elseif(empty($eu_pp_email)){
		
			throw new Exception("Earning user's email is not available please add it!", 1);
		
		}else{

			$params['paypal_client_id']=$rev_configs['rev_paypal_client_id'];
			$params['paypal_secret']=$rev_configs['rev_paypal_secret'];
			$access_token = $pprest->get_access_token($params);
		}

		// pr($access_token,true);
		
		$payout_attributes=array(
			"sender_batch_header"=>array(
				// "sender_batch_id"=>"5616516",
				"email_subject"=>"You earning payment has been received from ".BASEURL,
    			"recipient_type"=>"EMAIL"
			),

			"items"=>[array(
				"recipient_type"=>"EMAIL",
				"amount"=>array(
						"value"=>$eu_earning_amount,
	        			"currency"=>$currency_type
					),
				"note"=>"Thanks for being our partner in business!",
			    // "sender_item_id"=>"201403140001",
			    "receiver"=>$eu_pp_email

			)]

		);

		$request_attributes['access_token']=$access_token;
		$request_attributes['request']=$payout_attributes;
		$request_attributes['request_type']="pp_payout";
		
		$pp_response=$pprest->paypal_request($request_attributes);
		
		if($pp_response['batch_header']['batch_status']=='SUCCESS'){
			
			$db->update(tbl('revsharing_earnings'),array('paid_check'),array(1)," userid='".$userid."' AND paid_check='0'");

			$db->insert(tbl('revsharing_payments'),array("userid","amount","date_time","receiver_email","sent_via"),array($userid,$eu_earning_amount,NOW(),$eu_pp_email,'paypal'));

			$response = $pp_response['batch_header']['batch_status']." earnings has successfully been transfered to user's paypal email ".$eu_pp_email;

		}else{
			
			$response = $pp_response['details'][0]['issue'];
			
		}
		// pr($pp_response,true);

		return $response;
	}
	
	/**
    * Used to mark earnings paid if paid manually from bank
    * @param  : {$userid,$earning_amount,$bank_account_number}
    * @author  : Awais Fiaz
    */

	function mark_earnings_paid($userid,$eu_earning_amount,$bank_acc_no)
	{
		global $db;
		if(empty($bank_acc_no)){
			
			throw new Exception("User's account number is not added please add it!");
		
		}

		$db->update(tbl('revsharing_earnings'),array('paid_check'),array(1)," userid='".$userid."' AND paid_check='0'");

		$db->insert(tbl('revsharing_payments'),array("userid","amount","date_time","receiver_bank_acc","sent_via"),array($userid,$eu_earning_amount,NOW(),$bank_acc_no,'bank'));
		$response = "Earnings has been marked paid on bank account ".$bank_acc_no;
		return $response;
	}
	
	/**
    * Used to get payment history against any user
    * @param  : {$userid}
    * @author  : Awais Fiaz
    */

	function get_payment_history($userid)
	{
		# code...
		global $db;
		$payment_history=$db->select(tbl("revsharing_payments"),"*"," userid='".$userid."'");
		return $payment_history;
	}

	/**
    * Used to aggregate raw views according to video id
    * @author  : Awais Fiaz
    */

	function aggregate_views()
	{
		global $db;
		$collection = mongoConViews();
		
		$total_v_ids = $collection->distinct(
            'videoid',[
                'view_status'=>(int)0
            ]
        );

		if(!empty($total_v_ids)){
		        foreach ($total_v_ids as $key => $vid) {
		        	# code...
		        	$v_id_views[$vid][] = $collection->count(
		        		[
		        			'videoid' => $vid
		        		],
		        		[
		        			'view_status'=> (int)0
		        		]
		        	);
		
		        }

		    $response = $v_id_views;
		
		}else{

			$response = 0;			
			
		}
		
		return $response;
	}

	/**
    * Used to update views in sql comming out from mongo
    * @param  : {$aggregated_views}
    * @author  : Awais Fiaz
    */

	function update_mongo_sql_views($aggregated_views)
	{
		# code...
		global $db;
		$collection = mongoConViews();

		pr($aggregated_views,true);
		if(!empty($aggregated_views)){

			foreach ($aggregated_views as $videoid => $n_o_views) {
				
				$updated_views=$collection->updateMany(
					[
						'videoid' => (int)$videoid,
						'view_status' => (int)0
					],
					['$set' => [ 'view_status' => (int)1 ]]			
				);

				pr("Updated views for videoid ".$videoid." in Mongo with view status 0 ".$updated_views->getMatchedCount()." Matched Views found ".$updated_views->getModifiedCount()." Views Modified!",true);

				$query = "UPDATE " . tbl("video") . " SET views = views + ".$n_o_views[0]." WHERE videoid = '".$videoid."'";
				$result = $db->Execute($query);

				if($result){
					pr("SQL views updated for videoid = ".$videoid,true);	
				}


				}

				$response ="Cron job Done!";
		
		}

		return $response;
	}

	/**
    * Used to flush earning views on uninstall
    * @author  : Awais Fiaz
    */

	function flush_mongo_views()
	{
		$collection = mongoConViews();
		$collection->deleteMany([]);
		
	}


}