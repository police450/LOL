<?php
/**
 * @ Author Fawaz Tahir
 * @ File  : Videos List.
 * @ Release Date : Feb 2010
 * @ Version : v1.0
 * @ Description:  Here we display list of videos that don't have sprite based thumb.
 */
 
$page = $_GET['page'];
$manualRefresh = mysql_clean($_GET['manualRefresh']);
$time = $tr->cachedTime;
if(time() - 3600 > $time || $manualRefresh == "refresh")
	$forceDB = true;
	
$videos = $tr->getNonSpriteVideos($forceDB);
$total_videos = count($videos);
$pages = $tr->simplePagination($total_videos,$page);
$newArray = $tr->createArray($videos);

assign('pages',$pages);
assign('videos',$newArray);

subtitle("Videos List");
/* ADDING ADMIN HEADER*/
$Cbucket->add_admin_header(TR_DIR.'/admin/admin_header.html');
template_files('list_videos.html',TR_DIR.'/admin/template');
?>