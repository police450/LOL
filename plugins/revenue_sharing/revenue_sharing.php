<?php
/*
Plugin Name: Revenue sharing
Description: if you are planning to maximize your sales by sharing revenue among stackholders this is the plugin to do so
Author: Awais Fiaz
Author Website: http://clip-bucket.com/
ClipBucket Version: 2.8.x
Version: 1.0
Plugin Type: global
*/

/**
 * This is fully featured revenue sharing system
 * for clipbucket
 *
 * @author : Awais Fiaz
 */
 

if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');


// define('PAID_SUB_MOD_LICENSE',$paidSub->configs['license_key']);
define("_REV_SHARE_",basename(dirname(__FILE__)));
define("REV_SHARE_DIR",PLUG_DIR.'/'._REV_SHARE_);
define("REV_SHARE_URL",PLUG_URL.'/'._REV_SHARE_);
// define("REV_SHARE_MOD_URL",BASEURL.'/module.php?s=revshare&p=stats');
assign("rev_share_dir",REV_SHARE_DIR);
assign("rev_share_url",REV_SHARE_URL);



require_once("classes/paypal_rest.php");
require_once("classes/revenue_sharing.class.php");
require_once("revsharing_functions.php");

$pprest = new revPaypalRest();
assign('pprest',$pprest);

$revshare = new revSharing();
assign('revshare',$revshare);

	

	if(!function_exists('revenue_sharing_request'))
	{	
		define("revshare_install","installed");
		function revenue_sharing_request()
		{	

			Template(REV_SHARE_DIR."/anchors/request.html",false);


		}
		function rev_sharing_request_modal_fun()
		{	
			global $revshare;
			
			try {
				
				if(isset($_POST["request_earning_user"])){
				$response=$revshare->create_eu_request($_POST);
				e($response,"m");
				}	

			} catch (Exception $e){

				e($e->getMessage(),"e");
			
			}
			

			Template(REV_SHARE_DIR."/anchors/request_modal.html",false);

		}

    	//revshare anchors
		register_anchor_function("revenue_sharing_request","revenue_sharing_request_link");
		register_anchor_function("rev_sharing_request_modal_fun","rev_sharing_request_modal");
	

	}


// registering module for front end
register_module("revshare_module",REV_SHARE_DIR."/revshare_module.php");
//Adding Admin Menu
add_admin_menu("Revenue Sharing","Dashboard",'revsharing_dashboard.php',_REV_SHARE_.'/admin');
add_admin_menu("Revenue Sharing","Manage Earning Users",'manage_earningusers.php',_REV_SHARE_.'/admin');
add_admin_menu("Revenue Sharing","Configurations",'basic_configs.php',_REV_SHARE_.'/admin');
add_admin_menu("Revenue Sharing","RPM Manager",'rpm_manager.php',_REV_SHARE_.'/admin');
add_admin_menu("Revenue Sharing","Requests manager",'request_manager.php',_REV_SHARE_.'/admin');
add_admin_menu("Revenue Sharing","Documentation",'revshare_doc.php',_REV_SHARE_.'/admin');






 


 
 
?>