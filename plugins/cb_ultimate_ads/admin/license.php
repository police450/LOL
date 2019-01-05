<?php
require_once '../includes/admin_config.php';

if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE','Ultimates Ads');
}
if(!defined('SUB_PAGE')){
		define('SUB_PAGE',"Ultimate Ads license");
}


if(isset($_POST['update-uads-license']))
{
    $license = $_POST['uads-license'];
    $db->update(tbl("config_uads"),array("value"),array($license),"config_id=1") ;
    e('Ultimate Ads Manager license has been updated','m');
}

template_files('license.html',CB_UADS_MANAGER_ADMIN_DIR.'/html');
?>


