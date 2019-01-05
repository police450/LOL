<?php 

require_once '../includes/admin_config.php';

if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE','Ultimates Ads');
}
if(!defined('SUB_PAGE')){
		define('SUB_PAGE',"Edit Ad");
}

$ad_id = mysql_clean($_GET['edit']);
$ad_params = array("id"=>$ad_id);
$Ad = $CbUads->get_ultimate_ads($ad_params)[0];

if ( $Ad && $CbUads->nonLinearBannerExists($Ad) ){
	$Ad['banner'] = $CbUads->nonLinearBannerExists($Ad);
}

assign('ad',$Ad);

template_files('edit_ad.html',CB_UADS_MANAGER_ADMIN_DIR.'/html');
?>