<?php
/**
*	Plugin Name: Advanced Social Connect
*	Description: New Advanced version of Social Connect. Allows your users to login to your website using their social accounts
*	ClipBucket Version: 2.8.x
*	Plugin Version 1.0
* 	@since October 28th, 2015
*	Author: Saqib Razzaq
*	Last Updated: May 10th, 2016
*/

	define("SOCIAL_CON_BASE",basename(dirname(__FILE__)));
	define("SOCIAL_CON_DIR",PLUG_DIR.'/'.SOCIAL_CON_BASE);
	define("SOCIAL_CON_URL",PLUG_URL.'/'.SOCIAL_CON_BASE);
	define("SOCIAL_ADMIN_DIR", SOCIAL_CON_DIR.'/admin');
	define("SOCIAL_ADMIN_URL", SOCIAL_CON_URL.'/admin');
	define("SOCIAL_CON_INCLUDES", PLUG_DIR.'/'.SOCIAL_CON_BASE.'/social_includes');
	define("SOCIAL_CON_HTML", PLUG_DIR.'/'.SOCIAL_CON_BASE.'/templates');
	define("SOCIAL_CON_ADMIN_HTML", SOCIAL_ADMIN_DIR.'/styles');
	define("SOCIAL_CON_IMGS", SOCIAL_CON_URL.'/imgs');
	define("LOGIN_TWIT_DIR", SOCIAL_CON_INCLUDES.'/login_twitter');
	define("LOGIN_GOOGLE_DIR", SOCIAL_CON_INCLUDES.'/login_google');

	include SOCIAL_CON_INCLUDES.'/social_common.php';
	include SOCIAL_CON_INCLUDES.'/adv_social_lisc.inc.php';
	include_once LOGIN_GOOGLE_DIR."/src/Google_Client.php";
	include_once LOGIN_GOOGLE_DIR."/src/contrib/Google_Oauth2Service.php";

	define_constants();

	//fire_lisc_menu();
	// handles all social logins 
	network_trigger();
	
?>

