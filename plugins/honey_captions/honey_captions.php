<?php
/**
*	Plugin Name: CB Subtitles
*	Description: Allows you to add substitles to your videos in multiple languages. These subtitles can be added on uploading of video, user's edit video section and admin area edit video section. You can make subtitle default, download them or delete them and more
*	Author: Saqib Razzaq
*	ClipBucket Version: 2.8.x
*	Plugin Version 1.0
* 	@since November 6th, 2015
*	Last Updated: November 12th, 2015
*/

	define("HONEY_CAPT_BASE",basename(dirname(__FILE__)));
	define("HONEY_CAPT_DIR",PLUG_DIR.'/'.HONEY_CAPT_BASE);
	define("HONEY_CAPT_URL",PLUG_URL.'/'.HONEY_CAPT_BASE);
	define("HONEY_CAPT_ADMIN_DIR", HONEY_CAPT_DIR.'/admin');
	define("HONEY_CAPT_ADMIN_URL", HONEY_CAPT_URL.'/admin');
	define("HONEY_CAPT_INCLUDES", PLUG_DIR.'/'.HONEY_CAPT_BASE.'/honey_includes');
	define("HONEY_CAPT_HTML", PLUG_DIR.'/'.HONEY_CAPT_BASE.'/templates');
	define("HONEY_CAPT_HTML_URL", PLUG_URL.'/'.HONEY_CAPT_BASE.'/templates');
	define("HONEY_CAPT_ADMIN_HTML", HONEY_CAPT_ADMIN_DIR.'/styles');
	define("HONEY_CAPT_ADMIN_HTML_URL", HONEY_CAPT_ADMIN_URL.'/styles');
	define("HONEY_CAPT_IMGS", HONEY_CAPT_URL.'/imgs');
	define("HONEY_CAPT_INSTALLED", "YES");

	// assign("ajax_file", HONEY_CAPT_URL.'/ajax.php');
	assign("HONEY_CAPT_ADMIN_HTML",HONEY_CAPT_ADMIN_HTML_URL);
	assign("HONEY_CAPT_HTML_URL",HONEY_CAPT_HTML_URL);
	assign("honey_ajax", HONEY_CAPT_URL.'/ajax.php');

	include HONEY_CAPT_INCLUDES.'/honey_capt_common.php';
	require HONEY_CAPT_INCLUDES.'/honey_inc.php';
	total_subs_uploaded();
?>

