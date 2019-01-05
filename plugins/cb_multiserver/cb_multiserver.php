<?php
	/**
	*	Plugin Name: ClipBucket Multiserver v4
	*	Description: Multiserver allows you to scale like a boss by allowing you to add unlimited number of servers, define server roles, activate / deactivate servers, set server limits and much more
	*	Author: Arslan Hassan, Awais Tariq, Fahad Abass, Saqib Razzaq, Awais Fiaz
	*	Author Website: http://clip-bucket.com/
	*	ClipBucket Version: 2.8.1.x
	*	Version: 4.0
	*	Revision : 37
	*	Website: http://clipbucket.com/products/view/clipbucket-multiserver/
	*	Plugin Type: global
	*	Date : 2015-03-12
	*	Date-updated : 16-11-16
	*/



	define("DEBUG_MS",DEVELOPMENT_MODE);
	define("CB_MULTISERVER_DIR_NAME",basename(dirname(__FILE__)));
	define('CB_MULTISERVER_PLUG_DIR',PLUG_DIR."/".CB_MULTISERVER_DIR_NAME);
	define('CB_MULTISERVER_PLUG_URL',PLUG_URL."/".CB_MULTISERVER_DIR_NAME);
	include("cb_multi_server.inc.php");

?>
