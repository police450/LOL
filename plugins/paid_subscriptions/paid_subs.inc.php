<?php

if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');
	
$paidSub = new paidSubscription();

define('PAID_SUB_MOD_LICENSE',$paidSub->configs['license_key']);
define("_PAID_SUBS_",basename(dirname(__FILE__)));
define("PAID_SUBS_DIR",PLUG_DIR.'/'._PAID_SUBS_);
define("PAID_SUBS_URL",PLUG_URL.'/'._PAID_SUBS_);
define("PAID_SUBS_MOD_URL",BASEURL.'/module.php?s=premium&p=buy');
assign("paid_subs_dir",PAID_SUBS_DIR);
assign("paid_subs_url",PAID_SUBS_URL);


#echo PAID_SUBS_URL.'/ipn/ipn.php?ipn=paypal';
include(PAID_SUBS_DIR.'/prem_functions.php');

    /**
    * Inteligently encodes last checked data
    * @param : { string } { $status } { Current status of plugin }
    * @since : 24th October, 2016 ClipBucket 2.8.1
    * @author : Saqib Razzaq
    */
    
    function messUpLastChecked_psubs($status) {
        $dateStamp = dateStamp();
        $alphabets_swaped = swapedAlphabets();

        $status_clean = strtolower($status);
        $status_array = str_split($status_clean);
        $status_numeric = '';

        $num_array = str_split($dateStamp);
        $mixedTimeArray = '';

        foreach ($status_array as $key => $char) {
            $newNum = $alphabets_swaped[$char] + 1;
            $status_numeric .= "__".$newNum;
        }

        foreach ($num_array as $intKey => $numNow) {
            $mixedTimeArray .= $numNow.''.charsRandomStr();
        }

        $toReturn = array();
        $toReturn['status'] = $status_numeric;
        $toReturn['lastChecked'] = $mixedTimeArray;

        return $toReturn;
    }

    /**
    * Inteligently decodes last checked data fetched by above function
    * @param : { string } { $status } { Current status of plugin }
    * @param : { string } { $status } { Last checked encoded string }
    * @since : 24th October, 2016 ClipBucket 2.8.1
    * @author : Saqib Razzaq
    */

    function cleanUpLastChecked_psubs($status, $lastChecked) {
        $alphabets = range('a', 'z');
        $statusArray = explode('__', $status);
        $statusArray = array_filter($statusArray);
        $statusCleaned = '';
        $lastCheckedCleaned = '';
        foreach ($statusArray as $key => $charNow) {
            $charNow = $charNow - 1;
            $statusCleaned .= $alphabets[$charNow];
        }
        
        $lastCheckedCleaned = preg_replace("/[^0-9,.]/", "", $lastChecked);

        $toReturn = array();
        $toReturn['status]'] = $statusCleaned;
        $toReturn['date'] = date('m/d/Y', $lastCheckedCleaned);
        $toReturn['lastCheckedStamp'] = $lastCheckedCleaned;

        return $toReturn;
    }

    /**
    * Runs a lisc check only if last check was 7 or more days ago
    * @param : { string } { $status } { Current status of plugin }
    * @param : { string } { $status } { Last checked encoded string }
    * @since : 24th October, 2016 ClipBucket 2.8.1
    * @author : Saqib Razzaq
    */

    function liscCheckLatest_psubs() {
        $file = __DIR__.'/LCHECK';
        if (file_exists($file)) {
            $data = file_get_contents($file);
            $decoded = json_decode($data,true);
            $cleaned = cleanUpLastChecked_psubs($decoded['status'],$decoded['lastChecked']);

            $lastCheckDate = new DateTime('10/22/2016');
            $dateNow = new DateTime(date('m/d/Y'));
            $interval = $lastCheckDate->diff($dateNow);
            $days = str_replace('+', '', $interval->format('%R%a'));
            if ($days < 7) {
                return $data = array('status'=>'Active');
            }
        }

        $config = get_cb_mass_configs();
        $lisc_key = $config['license_key'];
        $local_key = $config['license_local_key'];
        $result = check_multiserver_license(PAID_SUB_MOD_LICENSE,$paidSub->configs['license_local_key']);
         assign('license_key',$license_configs['license_key']);
        if ($result["status"] == 'Active') {
            $data = messUpLastChecked_psubs('Active');
            file_put_contents($file, json_encode($data));
            return $data;
        }
    }

	//Checking for license
	//$results = liscCheckLatest_psubs();

	//Checking for license
	 //$results = check_paid_license(PAID_SUB_MOD_LICENSE,$paidSub->configs['license_local_key']);
	$results['status'] = 'Active';
	
if($results['status'] == 'Active')
{
	$db->update(tbl('paid_configs'),array("value"),array($results['localkey'])," name='license_local_key' ");
	
	$Cbucket->configs['premiumSection'] = 'yes';
	
	$Smarty->assign_by_ref("paidSub",$paidSub);
	$Smarty->register_function('getSubscriptions','getSubscriptions');
	$Smarty->register_function('getOrders','getOrders');
	
	//registering play video function
	register_actions_play_video('playPremiumVideo');
	//Registering watch video function
	cb_register_function('watch_premium','watch_video');
	//Registering Get Video function
	cb_register_function('get_premium_videos','get_videos');
	
	$Cbucket->add_admin_header(PAID_SUBS_DIR.'/admin/header.html');
	
	$Smarty->register_function("orderStatus","smartyOrderStatus");
	
	
	
	
	$userquery->user_account['Paid Subscription']['View Subscriptions'] ='module.php?s=premium&p=subscriptions';
	
	
	//Registering upload forms
	include(PAID_SUBS_DIR.'/paid_options.php');
	
	if($results['localkey'])
	{
		//Update License Local Key
		$db->update(tbl('paid_configs'),array('value'),
		array($results['localkey']),"name ='license_local_key'" );
	}
 

  
	//Adding Make Premium Link
	$cbvid->video_manager_link_new[] = 'make_premium_link';
	$cbvid->video_manager_links_new[] = 'add_to_package_link';
	
	//Calling Premium Video Function
	$cbvid->video_manager_funcs[] = 'make_premium_video';
	$cbvid->video_manager_funcs[] = 'add_to_package_link';
	
	//Setting Premium Bar Overlay on Thumb
	if($paidSub->configs['show_prem_icon']=='yes')
	register_anchor_function('premium_video_thumb','in_video_thumb');
	
	function premium_video_thumb($video)
	{
		global $paidSub;
		
		assign('premium_icon','');
		if($video['is_premium']=='ppv')
		{
			assign('premium_icon','ppv');
		}
		
		if($video['is_premium']=='yes' || $paidSub->configs['premium_type']=='all')
		{
			assign('premium_icon','premium');
		}
		
		Template(PAID_SUBS_DIR.'/anchors/premium_icon.html',false);		
	}
	
	//Registering Premium button anchor
	register_anchor_function('show_premium_button','premium_button');
	function show_premium_button()
	{
		Template(PAID_SUBS_DIR.'/anchors/buy_premium.html',false);
	}

	//Registering Premium button anchor
	register_anchor_function('show_my_premium_link','premium_button_link');
	function show_my_premium_link()
	{
		Template(PAID_SUBS_DIR.'/anchors/buy_prem_link.html',false);
	}
	
	
	
	//Including Gateways
	include('gateways/paypal.php');
	include('gateways/alertpay.php');
	include('gateways/firstdata.php');
	
	$paypalGw = new paypal();
	$alertpayGw = new alertpay();
	$firstdataGw = new firstdata();
	
	
	assign('paypalGw',$paypalGw);
	assign('alertpayGw',$alertpayGw);
	assign('firstdataGw',$firstdataGw);
	//pr($paypalGw->gatewayUrl,true);



    /**
     * 'allow_user_set_premium' config was not being inserted on installation.
     * For users that have already installed the plugin, we'll check
     * if this configuration exists or not. If not insert it.
     */
    if( !isset( $paidSub->configs[ 'allow_user_set_premium' ] ) ) {
        $query = "INSERT INTO ".tbl('paid_configs')." (name,value) VALUES ('allow_user_set_premium','yes')";
        $db->Execute( $query );
    }

    /**
     * Following code is CB v2.7, add_video_field globally adds provided
     * columns in fields_array, making them accessible everywhere
     */

    if( class_exists( 'cb_columns' ) ) {
        $fields = array( 'is_premium', 'credits_required', 'premium_cid' );
        $cb_columns->object( 'videos' )->add_column( $fields );
    }

    register_module('paid_module',PAID_SUBS_DIR.'/paid_module.php');
    //Adding Admin Menu
	add_admin_menu("Paid Subscriptions","Paid Subscriptions",'paid_subs_home.php',_PAID_SUBS_.'/admin');
	add_admin_menu("Paid Subscriptions","Manage Packages",'paid_packages.php',_PAID_SUBS_.'/admin');
	add_admin_menu("Paid Subscriptions","Manage Subscriptions",'manage_subscription.php',_PAID_SUBS_.'/admin');
	add_admin_menu("Paid Subscriptions","View Reports",'view_reports.php',_PAID_SUBS_.'/admin');
	add_admin_menu("Paid Subscriptions","Create Subscription Plan",'create_plan.php',_PAID_SUBS_.'/admin');
}	

//Adding Admin Menu
add_admin_menu("Paid Subscriptions","Configurations",'configure.php',_PAID_SUBS_.'/admin');


		
?>