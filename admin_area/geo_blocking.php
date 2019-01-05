<?php
/* 
 ***************************************************************
 | Copyright (c) 2007-2017 Clip-Bucket.com. All rights reserved.
 | @ Author 	: Awais Fiaz									
 | @ Software 	: ClipBucket , © PHPBucket.com					
 ***************************************************************
*/
 define("THIS_PAGE",'geo_blocking');
 define("PARENT_PAGE","users");
 require'../includes/admin_config.php';
 $userquery->admin_login_check();
 $userquery->login_check('member_moderation');
 $pages->page_redir();
 $udetails = $userquery->get_user_details(userid());
 $userLevel = $udetails['level'];

//Blocking country
 if(isset($_POST['block'])){

 	$Cbucket->geo_block_country($_POST['b_country_id']);
 	e(lang("country_blocked"),"m");
 }


//Unblocking country
 if(isset($_POST['unblock'])){

 	$Cbucket->geo_unblock_country($_POST['ub_country_id']);
 	e(lang("country_unblocked"),"m");
 }


 subtitle("Geolocking Manager");
 template_files('geo_blocking.html');
 display_it();
 ?>
