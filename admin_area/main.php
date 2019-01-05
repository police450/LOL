<?php
/* 
 ***************************************************************
 | Copyright (c) 2007-2010 Clip-Bucket.com. All rights reserved.
 | @ Author : ArslanHassan
 | @ Software : ClipBucket , © PHPBucket.com
 ***************************************************************
*/

 require_once '../includes/admin_config.php';
 $userquery->admin_login_check();
 $userquery->login_check('web_config_access');

 $pages->page_redir();

 /* Assigning page and subpage */
 if(!defined('MAIN_PAGE')){
 	define('MAIN_PAGE', 'General Configurations');
 }
 if(!defined('SUB_PAGE')){
 	define('SUB_PAGE', 'Website Configurations');
 }

 if(@$_GET['msg']){
 	$msg = mysql_clean($_GET['msg']);
 }

 $opt_list = $Upload->load_upload_options();

 assign('opt_list',$opt_list);

 assign('post_max_size',ini_get('post_max_size'));
 assign('upload_max_size',ini_get('upload_max_filesize'));


 if(isset($_POST['update'])){
 	$configs = $Cbucket->configs;

 	if($_FILES["watermark_png"]["tmp_name"] != ''){
 		$target_dir = IMAGES_DIR.'/';
    	// pr(IMAGES_DIR,true);
 		$target_file = $target_dir.basename($_FILES["watermark_png"]["name"]);
		// pr($target_file,true); 
 		
 		$file_name=basename($_FILES["watermark_png"]["name"]);
 		
 		$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
		// pr($imageFileType,true); 	png

 		$check_size = getimagesize($_FILES["watermark_png"]["tmp_name"]);
		// pr($check,true);

 		if($imageFileType == 'png'){
 			if($check_size['mime'] == true && $check_size[0] <= 100 && $check_size[1] <= 100) {
 				
		       // move_uploaded_file($target_file,IMAGES_DIR);
 				if(file_exists(IMAGES_DIR.'/'.'watermark.'.$imageFileType)){
 					unlink(IMAGES_DIR.'/'.'watermark.png');
 				}

 				if(move_uploaded_file($_FILES["watermark_png"]["tmp_name"], $target_file)){
 					
 					rename(IMAGES_DIR.'/'.$file_name, IMAGES_DIR.'/'.'watermark.'.$imageFileType);
 					
 					chmod(IMAGES_DIR.'/'.'watermark.'.$imageFileType, 0777);
 					e("Watermark has been updated!","m");
 				}else{
 					e("Watermark has not been updated!");
 				}
 				
 			}else{
 				
 				e("Uploaded image size is too big for watermark! please try (100px * 100px) or less!");
 				
 			}
 		}else{
 			e("File uploaded is not 'png' please upload with png extension!");
 		}
 	}


 	$rows = array(
 		'allowed_types',
 		'allow_language_change',
 		'allow_unicode_usernames',
 		'allow_username_spaces',
 		'allow_registeration',
 		'allow_upload',
 		'anonymous_id',
 		'anonym_comments',
 		'approve_video_notification',
 		'audio_codec',
 		'activation',
 		'photo_activation',
 		
 		'background_color',
 		'background_upload',
 		'background_url',
 		'big_thumb_width',
 		'big_thumb_height',
 		
 		'closed',
 		'closed_msg',
 		'channel_comments',
 		'channels_list_per_page',
 		'channels_list_per_tab',
 		'channelsSection',
 		'collectionsSection',
 		'channel_rating',
 		'collection_rating',
 		'comments_per_page',
 		'captcha_type',
 		'con_modules_type',
 		'comments_captcha',
 		'comment_rating',
 		'collection_per_page',
 		'collection_home_page',
 		'collection_search_result',
 		'collection_user_collections',
 		'collection_items_page',
 		'collection_user_favorites',
 		'collection_channel_page',
 		
 		'embed_type',
 		
 		'date_format',
 		'description',
 		'debug_level',
 		'default_country_iso2',
 		'default_time_zone',
 		'disallowed_usernames',
 		'use_subs',
 		
 		'embedUpload',
 		'email_verification',
 		'enable_groups',
 		
 		'ffmpegpath'	,
 		'flvtool2path',
 		'flvtoolpp',
 		'ffmpeg_type',
 		'facebook_embed',

 		'ffprobe_path',
 		'media_info',
 		'i_magick',

 		'gen_240',
 		'gen_360',
 		'gen_480',
 		'gen_720',
 		'gen_1080',
 		
 		'gravatars',
 		'grp_categories',
 		'groups_list_per_page',
 		'grps_items_search_page',
 		'grp_thumb_height',
 		'grp_thumb_width',
 		'grp_max_title',
 		'grp_max_desc',
 		'groupsSection',
 		
 		'high_resolution',
 		'hq_output',
 		
 		'keywords'	,
 		'keep_original',
 		'keep_mp4_as_is',
 		
 		'r_height',
 		'r_width',
 		
 		'max_bg_width',
 		'max_bg_size',
 		'max_conversion',
 		'max_profile_pic_height',
 		'max_profile_pic_size',
 		'max_profile_pic_width',
 		'max_topic_title',
 		'max_video_title',
 		'max_topic_length',
 		'max_video_desc',
 		'max_video_tags',
 		'max_username',
 		'min_video_title',
 		'min_video_tags',
 		'min_video_desc',
 		'mp4boxpath',
 		'mplayerpath',
 		'min_age_reg',
 		'min_username',
 		'max_comment_chr',
 		'max_time_wait',
 		'use_watermark',
 		'max_upload_size',
 		'max_video_duration',
 		'mplayerpath',
 		
 		'normal_resolution',
 		'num_thumbs',
 		
 		'own_channel_rating',
 		'own_collection_rating',
 		'own_video_rating',
 		
 		
 		'php_path',
 		'picture_url',
 		'picture_upload',
 		'photosSection',
 		'playlistsSection',			
 		'photo_main_list',
 		'photo_home_tabs',
 		'photo_search_result',
 		'photo_channel_page',
 		'photo_user_photos',
 		'photo_user_favorites',
 		'photo_other_limit',
 		
 		
 		
 		'resize',
 		'remoteUpload',
 		'recently_viewed_limit',
 		
 		'send_comment_notification',
 		'site_title'	,
 		'sys_os'	,
 		'sbrate'	,
 		'srate',
 		'site_slogan',
 		'seo',
 		'seo_vido_url',
 		'access_to_logged_in',
 		'search_list_per_page',
 		'server_friendly_conversion',
 		'support_email',
 		'show_collapsed_checkboxes',
 		
 		'thumb_width',
 		'thumb_height',
 		
 		'use_ffmpeg_vf',
 		'user_comment_own',
 		'user_rate_opt1'	,
 		'users_items_subscriptions',
 		'users_items_subscibers',
 		'users_items_contacts_channel',
 		'users_items_search_page',
 		'users_items_group_page',
 		'user_max_chr',
 		'use_cached_pagin',
 		'cached_pagin_time',
 		
 		'vid_categories',
 		'videosSection',
 		'videos_items_grp_page',
 		'videos_items_hme_page',
 		'videos_items_columns',
 		'videos_items_ufav_page',
 		'videos_items_uvid_page',
 		'videos_items_search_page',
 		'videos_item_channel_page',
 		'videos_list_per_page',
 		'index_featured',
 		'index_recent',
 		'videos_list_per_tab',
 		'video_download'	,
 		'video_embed',
 		'video_comments',
 		'video_rating',
 		'video_comments3',
 		'video_categories',
 		'video_codec',
 		'vrate',
 		'vbrate_240',
 		'vbrate_360',
 		'vbrate_480',
 		'vbrate_720',
 		'vbrate_1080',
 		'video_require_login',
 		'feedsSection',
 		'youtube_api_key',
 		'website_email',
 		'welcome_email',
 		'vbrate_hd',
 		'store_guest_session',
 		'delete_mass_upload',
 		'stay_mp4',
 		'popup_video',
 		'video_round_views',
 		'stream_via'
 		
 	);

foreach($opt_list as $optl)
{
	$rows[] = $optl['load_func'];
}

	//Numeric Array
$num_array = array(
	'anonymous_id',
	
	'channels_list_per_page',
	'channels_list_per_tab',

	'max_upload_size',
	'max_video_title',
	'max_video_desc',
	'max_video_tags',
	'min_video_title',
	'min_video_tags',
	'min_video_desc',
	
	'recently_viewed_limit',
	
	'search_list_per_page',
	
	'users_items_subscriptions',
	'users_items_subscibers',
	'users_items_contacts_channel',
	'users_items_search_page',
	'users_items_group_page',
	
	'videos_items_grp_page',
	'videos_items_hme_page',
	'videos_items_columns',
	'videos_items_ufav_page',
	'videos_items_uvid_page',
	'videos_items_search_page',
	'videos_item_channel_page',
	'index_featured',
	'index_recent',
	'videos_list_per_page',
	'videos_list_per_tab',
	'video_categories',
	
	'photo_main_list',
	'photo_home_tabs',
	'photo_search_result',
	'photo_channel_page',
	'photo_user_photos',
	'photo_user_favorites',
	'photo_other_limit',
	
	'collection_per_page',
	'collection_home_page',
	'collection_search_result',
	'collection_user_collections',
	'collection_items_page',
	'collection_user_favorites',
	'collection_channel_page',					
);


if (isset($_POST['seo']) != "yes") {
	$_POST['seo']= "no";
}
if (isset($_POST['access_to_logged_in']) != "yes") {
	$_POST['access_to_logged_in']= "no";
}
if(isset($_POST['delete_mass_upload']) != "yes"){
	$_POST['delete_mass_upload'] = "no";
}

if(isset($_POST['stay_mp4']) != "yes"){
	$_POST['stay_mp4'] = "no";
}

if (isset($_POST['send_comment_notification']) != "yes") {
	$_POST['send_comment_notification']= "no";

}
if (isset($_POST['approve_video_notification']) != "yes") {
	$_POST['approve_video_notification']= "no";

}
if (isset($_POST['use_cached_pagin']) != "yes") {
	$_POST['use_cached_pagin']= "no";

}
if (isset($_POST['gravatars']) != "yes") {
	$_POST['gravatars']= "no";

}
if (isset($_POST['select5']) != "yes") {
	$_POST['select5']= "no";

}
if (isset($_POST['select6']) != "yes") {
	$_POST['select5']= "no";

}
if (isset($_POST['select4']) != "yes") {
	$_POST['select5']= "no";

}
if (isset($_POST['background_color']) != "yes") {
	$_POST['background_color']= "no";

}
if (isset($_POST['select7']) != "yes") {
	$_POST['select7']= "no";

}



foreach($rows as $field)
{
		//echo $field;
	$value = ($_POST[$field]);
	if(in_array($field,$num_array))
	{
		if($value <= 0 || !is_numeric($value))
			$value = 1;
	}
	$myquery->Set_Website_Details($field,$value);
}
e("Website Settings Have Been Updated",'m');

}

$row = $myquery->Get_Website_Details();
// pr($row,true);
Assign('row',$row);
subtitle("Website Configurations");
template_files('main.html');
display_it();
?>