<?php
	/**
	* Plugin Name: SEO Ninja
	* Plugin Description: Enhance your search engine visibility and traffic by making your website more search frindly
	* Author: Saqib Razzaq
	* Version: 1.0
	* ClipBucket Version: 2.8.x
	*/
	define("SEO_NINJA",basename(dirname(__FILE__)));
	define("SEO_NINJA_DIR",PLUG_DIR.'/'.SEO_NINJA);
	define("SEO_NINJA_URL",PLUG_URL.'/'.SEO_NINJA);
	define("SEO_NINJA_ADMIN_DIR", SEO_NINJA_DIR.'/admin');
	define("SEO_NINJA_ADMIN_URL", SEO_NINJA_URL.'/admin');
	define("SEO_NINJA_INCLUDES", PLUG_DIR.'/'.SEO_NINJA.'/cb_beats_includes');
	define("SEO_NINJA_HTML", PLUG_DIR.'/'.SEO_NINJA.'/templates');
	define("SEO_NINJA_HTML_URL", PLUG_URL.'/'.SEO_NINJA.'/templates');
	define("SEO_NINJA_ADMIN_HTML", SEO_NINJA_ADMIN_DIR.'/styles');
	define("SEO_NINJA_ADMIN_HTML_URL", SEO_NINJA_ADMIN_URL.'/styles');
	assign("ninja_url",SEO_NINJA_URL);
	assign("SEO_NINJA_HTML",SEO_NINJA_HTML_URL);
	require 'ninja_includes/common.php';

?>