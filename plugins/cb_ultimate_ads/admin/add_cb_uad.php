<?php
require_once '../includes/admin_config.php';

if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE','Ultimates Ads');
}
if(!defined('SUB_PAGE')){
		define('SUB_PAGE',"Save Ad");
}

$Ads = $CbUads->get_ultimate_ads(true,false);
if (!empty($Ads)){
	$ad_exists = 'yes';
}else{
	$ad_exists = 'no';
}

assign('ad_exists',$ad_exists);

template_files('add_cb_uad.html',CB_UADS_MANAGER_ADMIN_DIR.'/html');
?>


