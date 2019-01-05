<?php 

require_once '../includes/admin_config.php';

if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE','Ultimates Ads');
}
if(!defined('SUB_PAGE')){
		define('SUB_PAGE',"Ad Settings");
}

if( isset($_GET['activate'])){
	$activate = mysql_clean($_GET['activate']);
	$activate_params = array("action"=>"activate","id"=>$activate);
	$CbUads->ad_actions($activate_params);
}

if( isset($_GET['deactivate'])){
	$deactivate = mysql_clean($_GET['deactivate']);
	$deactivate_params = array("action"=>"deactivate","id"=>$deactivate);
	$CbUads->ad_actions($deactivate_params);
}

if( isset($_GET['delete'])){
	$delete = mysql_clean($_GET['delete']);
	$delete_params = array("action"=>"delete","id"=>$delete);
	$CbUads->ad_actions($delete_params);
}

$page = mysql_clean($_GET['page']);
$get_limit = create_query_limit($page,RESULTS);
$ad_params['limit'] = $get_limit;
$ad_params['order'] = " ad_id DESC ";


$Ads = $CbUads->get_ultimate_ads($ad_params);
assign('ultimate_ads',$Ads);



//Collecting Data for Pagination

$adcount['count_only'] = true;
$total_rows  = $CbUads->get_ultimate_ads($adcount);
$total_pages = count_pages($total_rows,RESULTS);
$pages->paginate($total_pages,$page);


template_files('uads.html',CB_UADS_MANAGER_ADMIN_DIR.'/html');

?>