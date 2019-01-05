<?php

/*
Plugin Name:  ClipBucket Live Stream
Description:  This Plugin will manage all the live stream Actions for Clipbucket
Author: Fahaf Abbas
Author Website: http://clip-bucket.com/
ClipBucket Version: 2
Version: 1.0
Website: http://clip-bucket.com/
Plugin Type: Private
*/


define("CB_LS_BASE",basename(dirname(__FILE__)));

define("CB_LS_BASEDIR",PLUG_DIR.'/'.CB_LS_BASE);
define("CB_LS_BASEURL",PLUG_URL.'/'.CB_LS_BASE);

define("CB_LS_ADMIN_DIR", CB_LS_BASEDIR.'/admin');
define("CB_LS_ADMIN_URL", CB_LS_BASEURL.'/admin');

define("CB_LS_INC_DIR", CB_LS_BASEDIR.'/includes');

assign("ls_dir_base",CB_LS_BASE);
assign("ls_admin_dir",CB_LS_ADMIN_DIR);
assign("ls_admin_url",CB_LS_ADMIN_URL);
assign("ls_ajax_url",CB_LS_BASEURL.'/ajax.php');

require_once("includes/wowza_sdk.php");
require_once("includes/live_stream.class.php");

$wowza = new CB_wowza();
$cblive = new CB_live_stream();

assign('wowza',$wowza);
assign('cblive',$cblive);

add_admin_menu('Live Stream','Configure','configurations.php', CB_LS_BASE.'/admin');
add_admin_menu('Live Stream','Wowza Applicaitons','manage_wowza_application.php', CB_LS_BASE.'/admin');
add_admin_menu('Live Stream','Add Live Channel','add_live_channel.php', CB_LS_BASE.'/admin');
add_admin_menu('Live Stream','Live Channels','manage_channels.php', CB_LS_BASE.'/admin');
