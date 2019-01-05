<?php
/*
Plugin Name: Floating Social Share Box
Description: This plugin will show a floating share box on website pages
Author: Ruman Malik
Author Website: http://clip-bucket.com/
ClipBucket Version: >= 2.5.1 
Version: 1.0
Website: http://clip-bucket.com/
Plugin Type: global
*/



define("CB_FLOATING_SHARE_DIR",PLUG_DIR.'/'.basename(dirname(__FILE__)));

add_header(CB_FLOATING_SHARE_DIR.'/header.html');

function cb_floating_box()
{
	Template(CB_FLOATING_SHARE_DIR.'/floating_box.html',false);
}

register_anchor_function('cb_floating_box','cb_floating_box');
register_anchor_function('cb_floating_box','cb_floating_box');