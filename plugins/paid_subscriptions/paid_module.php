<?php


if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');


$section = get('s');
$file = get('p');

if(defined('IN_MODULE') && $section=='premium')
{
	
	//Making PaidSub object Global
	global $paidSub;
	
	
	//Videos Page
	if($file=='videos')
	{
		$userquery->logincheck();
		
		$sid = mysql_clean($_GET['sid']);
		$package = $paidSub->getPackage($sid,true);
		
		$page = mysql_clean($_GET['page']);
		$get_limit = create_query_limit($page,VLISTPP);
		
		if($package['is_collection']=='yes')
			$videos = $paidSub->getPackageVideos($package['package_id'],$get_limit);
		else
			$videos = $db->select(tbl("paid_subs_videos,video"),
				"*",tbl("paid_subs_videos.videoid")."=".tbl("video.videoid")." AND "
				.tbl("paid_subs_videos.subscription_id='".$sid."'"),$get_limit);
		
		
		assign('videos',$videos);	
		assign('package',$package);
		
		$counter = get_counter('video',array('sid'=>$sid));
		if(!$counter)
		{
			$total_rows = $db->count(tbl("paid_subs_videos"),
				"videoid","subscription_id='".$sid."'");
			
			$counter = $total_rows;
			update_counter('video',array('sid'=>$sid),$counter);
		}
		
		$total_pages = count_pages($counter,VLISTPP);
		//Pagination
		$pages->paginate($total_pages,$page);


		template_files('videos.html',PAID_SUBS_DIR.'/templates/');
		display_it();
		exit();
		
	}
	
	
	//Subscription Page
	if($file=='subscriptions')
	{
		$userquery->logincheck();
		//Function used to delete subscription
		if(isset($_GET['delete']))
		{
			$sid = mysql_clean($_GET['delete']);
			$paidSub->deleteSubscription($sid);
		}
		//Getting list of subscriptions
		$subscriptions = getSubscriptions(array('uid'=>userid()));

		assign('subs',$subscriptions);
		template_files('subscriptions.html',PAID_SUBS_DIR.'/templates/');
		display_it();
		exit();
	}
	
	
	
	//Renew Page
	if($file=='renew')
	{
		$userquery->logincheck();
		//Get Subscription Id and validate it
		$sid = mysql_clean($_GET['sid']);
		$subscription = $paidSub->getSubscription($sid);
		
		//Get Package
		$pid = $subscription['package_id'];
		$package = $paidSub->getPackage($pid);
		
		if(!$subscription)
		{
			e("Invalid Subscription");
		}else
		{
			assign('package',$package);
			assign('sub',$subscription);
		}
		
		template_files('renew.html',PAID_SUBS_DIR.'/templates/');
		display_it();
		exit();
	}
	
	
	
	//Invoice Page
	if($file=='view_invoice')
	{
		$userquery->logincheck();
		$sid = mysql_clean($_GET['sid']);
		$subscription = $paidSub->getSubscription($sid);
		
		if(!$subscription || ($subscription['userid']!=userid() 
			&& !has_access('admin_access',true)))
		{
			assign('no_invoice','yes');
		}else
		{
			$order = $paidSub->getOrder($sid,true,true);

			assign('sub',$subscription);
			assign('order',$order);
		}
		Template('global_header.html');
		Template(PAID_SUBS_DIR.'/templates/view_invoice.html',false);
		exit();
	}
	
	//Buy Premium Page		
	if($file=='buy')
	{
		$userquery->logincheck();
		if(isset($_POST['select_package']) || $_GET['view_vids'])
		{
			
			$pid = mysql_clean($_POST['premium_package']);
			
			if(!$pid)
				$pid = mysql_clean($_GET['view_vids']);
			
			$package = $paidSub->getPackage($pid);
			
			
			if(!$package)
			{
				e("Please select a package first");
			}else
			{
				if($package['is_collection'])
				{
					$page = mysql_clean($_GET['page']);
					$get_limit = create_query_limit($page,10);
					
					$videos = $paidSub->getPackageVideos($package['package_id'],$get_limit);
					$total = $paidSub->getPackageVideos($package['package_id'],'count');
					$total_pages = count_pages($total,10);
					//Pagination
					$pages->paginate($total_pages,$page);

					assign('videos',$videos);
				}
				
				assign('package',$package);
				$premium_vids = $cbvid->get_videos(array('premium'=>'all','limit'=>'5','order'=>'rand()'));
				assign('premium_vids',$premium_vids);
				template_files('order_premium.html',PAID_SUBS_DIR.'/templates/');
				display_it();
				exit();
			}
		}
		
		$packges = $paidSub->getPackages(array('active'=>'yes'));
		
		//Counting packages for each type
		$pkg_counts = $db->select(tbl('paid_packages'),"count(pkg_type) as counts,pkg_type",
			"active='yes'",NULL,NULL,"group by pkg_type");
		
		$pkgcounts = array();
		if($pkg_counts)
			foreach($pkg_counts as $pkgc)
				$pkgcounts[$pkgc['pkg_type']] = $pkgc['counts'];
			else
				assign('no_pkgs','yes');


			assign('pkg_counts',$pkgcounts);
			assign('packges',$packges);
			$premium_vids = $cbvid->get_videos(array('premium'=>'all','limit'=>'5','order'=>'rand()'));
			assign('premium_vids',$premium_vids);
			template_files('buy_premium.html',PAID_SUBS_DIR.'/templates/');
			display_it();
			exit();
		}


	//Invoice Page
		if($file=='thank_you')
		{
			$userquery->logincheck();
			$premium_vids = $cbvid->get_videos(array('premium'=>'all','limit'=>'5','order'=>'rand()'));
			assign('premium_vids',$premium_vids);
			template_files('thank_you.html',PAID_SUBS_DIR.'/templates/');
			display_it();
			exit();
		}
		//Invoice Page
		//checkmate
		if($file=='thank_you_subscription')
		{	
			$userquery->logincheck();
			
			require_once "gateways/paypal_rest.php";
			$paypal = new paypalRest($paidSub);

			//getting payment token
			$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$payment_token=(explode("=",$actual_link));
			
			$package_id=(explode("&",$payment_token[3]));
			
			// final payment token and package id
			$package_id=$package_id[0];
			$payment_token=$payment_token[4];

			// pr($package_id,true);
			// pr($payment_token,true);


			//getting token
			$token_params['paypal_client_id'] = $paidSub->configs['paypal_client_id'];
			$token_params['paypal_secret'] = $paidSub->configs['paypal_secret'];
			
			$token = $paypal->get_access_token($token_params);


			$att_send['access_token']=$token;
			$att_send['request_type']='execute_agreement';
			$att_send['id']=$payment_token;

			$agreement_execute_response=$paypal->paypal_request($att_send);
			// pr($agreement_execute_response,true);
			if($agreement_execute_response['id']==true){
				$agreement_id = $agreement_execute_response['id'];
				$payment_method = $agreement_execute_response['payer']['payment_method'];
				$payer_email = $agreement_execute_response['payer']['payer_info']['email'];
				$payer_id = $agreement_execute_response['payer']['payer_info']['payer_id'];
				$payer_name = $agreement_execute_response['payer']['payer_info']['shipping_address']['recipient_name'];
				$payer_postal_code = $agreement_execute_response['payer']['payer_info']['shipping_address']['postal_code'];
				$payer_country_code = $agreement_execute_response['payer']['payer_info']['shipping_address']['country_code'];
				$agreement_start_date = $agreement_execute_response['start_date'];
				$agreement_end_date = $agreement_execute_response['agreement_details']['final_payment_date'];
				if($agreement_end_date == '1970-01-01T00:00:00Z'){
					$agreement_end_date = '0000-00-00T00:00:00Z';
				}

				//changing dates according to system format
				$date_start=$agreement_start_date;
				$date1=explode("T",$date_start);
				$date2=explode("Z",$date1[1]);
				$final_start_date=$date1[0]." ".$date2[0];

				$date_end=$agreement_end_date;
				$date3=explode("T",$date_end);
				$date4=explode("Z",$date3[1]);
				$final_end_date=$date3[0]." ".$date4[0];



				$agreement_payment_query = $db->insert(tbl('paid_payments_success'),array("agreement_id","payment_method","payer_email","payer_id","payer_name","payer_postal_code","payer_country_code","payment_start_date","payment_end_date"),array($agreement_id,$payment_method,$payer_email,$payer_id,$payer_name,$payer_postal_code,$payer_country_code,$final_start_date,$final_end_date));

				

		
				if($agreement_payment_query){

					$subs_query = $db->insert(tbl("paid_subscriptions"),array("userid","package_id","agreement_id","start_date","end_date","allowed_vids","active","date_added","pkg_qty"),array(userid(),$package_id,$agreement_id,$final_start_date,$final_end_date,"0","no",now(),"0"));
					// pr($subs_query,true);
					if($subs_query){
						e("Subscription Added! Please wait till admin activates it!","m");
					}else{
						e("There was somthing wrong adding subscription!");
					}
					// exit;


					e("Payment done!","m");
					assign('payment_response',$agreement_execute_response);
				}else{

					e("Payment not Done some issue during inserting records!");
					assign('payment_response',$agreement_execute_response);
				}
			}else{
				e("Payment not Done probably some issue with payment token!");
				assign('payment_response',$agreement_execute_response);
			}

			template_files('thank_you_subscription.html',PAID_SUBS_DIR.'/templates/');
			display_it();
			exit();
		}


				//checkmate
		if($file=='cancle_subscription')
		{	
			$userquery->logincheck();
			



			template_files('cancle_subscription.html',PAID_SUBS_DIR.'/templates/');
			display_it();
			exit();
		}

		
	//Billing plan Page
		if($file=='billing_plan')
		{	

			$userquery->logincheck();
			$pkg_id=$_GET['pid'];
			if(isset($_POST['select_plan']))
			{	
				require_once "gateways/paypal_rest.php";
				$paypal = new paypalRest($paidSub);
				
				// setting start time to start aggreement
				$now = time();
				$increased_time = $now + (5 * 60);
				$utc = date('Z');
				$utc = $utc/100;
				$start_date = date('Y-m-d\TH:i:s.'.$utc.'\Z', $increased_time);

				$plan_id=$_POST['plan_id'];
				$plan_name=$_POST['plan_name'];
				$plan_description=$_POST['plan_description'];
				$package_id=$_POST['package_id'];
				//setting request type
				$agreement_request_type='create_agreement';
				
				//getting token
				$token_params['paypal_client_id'] = $paidSub->configs['paypal_client_id'];
				$token_params['paypal_secret'] = $paidSub->configs['paypal_secret'];
				//request for paypal access tokens
				$token = $paypal->get_access_token($token_params);
				
				$agreement_attributes=array('name'=>$plan_name,'description'=>$plan_description,'start_date'=>$start_date,
					'plan'=>array('id' => $plan_id ),
					'payer'=>array('payment_method' => 'PAYPAL' )
					);

				// preparing array for creating agreement
				$agreement_att['access_token']=$token;
				$agreement_att['request']=$agreement_attributes;
				$agreement_att['request_type']=$agreement_request_type;
				
				$agreement_response=$paypal->paypal_request($agreement_att);
				
				if($agreement_response){
				// global $db;
					$frequency = $agreement_response['plan']['payment_definitions'][0]['frequency'];
					$currency = $agreement_response['plan']['payment_definitions'][0]['amount']['currency'];
					$amount = $agreement_response['plan']['payment_definitions'][0]['amount']['value'];
					$cycles = $agreement_response['plan']['payment_definitions'][0]['cycles'];
					$frequency_interval = $agreement_response['plan']['payment_definitions'][0]['frequency_interval'];
					$setup_fee = $agreement_response['plan']['merchant_preferences']['setup_fee']['value'];
					$approval_url=$agreement_response['links'][0]['href'];
					$execute_url=$agreement_response['links'][1]['href'];
					$start_date=$agreement_response['start_date'];
					$payment_token=(explode("=",$agreement_response['links'][0]['href']));
					$payment_token=$payment_token[2];
					
					$agreement_insert_query = $db->insert(tbl('paid_agreements'),array("name","description","plan_id","package_id","type","frequency","currency","amount","cycles","frequency_interval","setup_fee","approval_url","execute_url","start_date","agreement_token"),array($agreement_response['name'],$agreement_response['description'],$agreement_response['plan']['id'],$package_id,$agreement_response['plan']['type'],$frequency,$currency,$amount,$cycles,$frequency_interval,$setup_fee,$approval_url,$execute_url,$start_date,$payment_token));
					
					if($agreement_insert_query){
						
						$yes=e("Agreement created","m");
						header('Location:'.$agreement_response['links'][0][href].'');
						assign('agreement_response',$yes);
					}else{
						
						$no=e("Agreement not created");
						assign('agreement_response',$no);
					}
				}
				
				template_files('thank_you_subscription.html',PAID_SUBS_DIR.'/templates/');
				display_it();
				exit();

			}
			
			$billing_plans = $db->select(tbl('paid_billing_plan'),"*"," plan_state LIKE '%ACTIVE%' AND package_id = '".$pkg_id."'");
			assign('billing_plans',$billing_plans);
			template_files('billing_plan.html',PAID_SUBS_DIR.'/templates/');
			display_it();
			exit();
		}
	}
	
	?>