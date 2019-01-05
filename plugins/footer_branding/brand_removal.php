<?php
/* 
 *************************************************************************
 | Plugin Name: CB 2.7 Brand Removal  
 | Description: This plugin will remove "Forged By Clipbucket" from the footer					
 | Author: Fahad Abbas 
 | ClipBucket Version: 2.7
 | Plugin Version: 1.1    				
 *************************************************************************
*/



define("BRAND_REMOVAL",basename(dirname(__FILE__)));
define("BRAND_REMOVAL_DIR",PLUG_DIR.'/'.BRAND_REMOVAL);

add_admin_menu("Brand Removal","configuration",'configs.php',BRAND_REMOVAL.'/admin');
include_once("cb_brand_removal.inc.php");
?>