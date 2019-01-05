<?php 

/*
Plugin Name: Clipbucket Ultimate Ads Manager 
Description: This Plugins Supports Invideo uAds i.e fully compatible with Google Ima
Author: Fahad Abbas
Author Website: http://clip-bucket.com/
Version: 2.0
ClipBucket Version: 2.8.1
*/

/**
* Plugin's Prefix : UADS > U(ltimate)ADS
*/

define('CB_UADS_MANAGER', this_plugin( __FILE__ ) );
assign('cb_uads_manager',CB_UADS_MANAGER);

define('CB_ULTIMATE_ADS','installed');
assign('cb_ultimate_ads',CB_ULTIMATE_ADS);
/* PATHS */
define( 'CB_UADS_MANAGER_DIR', PLUG_DIR.'/'.CB_UADS_MANAGER);
define( 'CB_UADS_MANAGER_URL', PLUG_URL.'/'.CB_UADS_MANAGER );

define( 'CB_UADS_MANAGER_ADMIN_DIR', CB_UADS_MANAGER_DIR.'/admin');
define( 'CB_UADS_MANAGER_ADMIN_URL', CB_UADS_MANAGER_URL.'/admin');

assign('cb_uads_manager_dir',CB_UADS_MANAGER_DIR);
assign('cb_uads_manager_url',CB_UADS_MANAGER_URL);

assign('cb_uads_manager_admin_dir',CB_UADS_MANAGER_ADMIN_DIR);
assign('cb_uads_manager_admin_url',CB_UADS_MANAGER_ADMIN_URL);

assign('cb_uads_ajax_url',CB_UADS_MANAGER_URL.'/ads_ajax.php');

include "includes/function_ads.php";
include "includes/classes/cbads.class.php";

$CbUads = new cb_ultimate_ads();
assign('cbuads',$CbUads);


$Cbucket->add_admin_header(CB_UADS_MANAGER_DIR.'/cb_uads_admin_header.html');
$Cbucket->add_header(CB_UADS_MANAGER_DIR.'/cb_uads_header.html');

add_admin_menu('Utimate Ads Manager','Add Player Ad','add_cb_uad.php',CB_UADS_MANAGER.'/admin');
add_admin_menu('Utimate Ads Manager','Ad Settings','uads.php',CB_UADS_MANAGER.'/admin');

?>