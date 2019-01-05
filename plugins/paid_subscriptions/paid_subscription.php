<?php
/*
Plugin Name: Paid Subscription & Pay per view module for ClipBucket
Description: if you are planning to start a paid video sharing website, this plugin will help you control all paid features you want
Author: Arslan Hassan
Author Website: http://clip-bucket.com/
ClipBucket Version: 2
Version: 1.2
Website: http://labguru.com/
Plugin Type: global
*/

/**
 * This is fully featured paid subscription and pay per view system
 * for clipbucket
 *
 * @author : Arslan Hassan
 */
 

if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');

 if(!$in_bg_cron)
 {
	 include("classes/paid_subscription.class.php");
	 include("paid_subs.inc.php");
 }
 


 
 
?>