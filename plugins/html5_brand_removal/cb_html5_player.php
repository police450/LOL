<?php


/*
Plugin Name: CB html5_player Settings
Description: Removes the Branding of Clipbucket from player :)
Author: Fahad Abbas
Author Website: http://clip-bucket.com/
ClipBucket Version: 2
Version: 1.0
Website: http://clip-bucket.com/
Plugin Type: 
*/



define('CB_HTML5_PLUG_BASENAME',basename(dirname(__FILE__)));
define('CB_HTML5_PLUG_DIR',PLUG_DIR.'/'.CB_HTML5_PLUG_BASENAME);
define('CB_HTML5_PLUG_URL',PLUG_URL.'/'.CB_HTML5_PLUG_BASENAME);

assign('cb_html5_plug_dir',CB_HTML5_PLUG_DIR);
assign('cb_html5_plug_url',CB_HTML5_PLUG_URL);

define("CB_HTML5_PLAYER_SETTINGS","installed");




/*add_admin_menu('CB Html5 Player Settings','CB Html5 Player license','cb_html5_license.php',CB_HTML5_PLUG_BASENAME.'/admin');*/
include(CB_HTML5_PLUG_DIR."/cb_html5_inc.php");

?>