<?php

require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$pages->page_redir();
/* Assigning page and subpage */
if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE','CllipBucket');
}
if(!defined('SUB_PAGE')){
	define('SUB_PAGE',"License");
}


if(isset($_POST['update-cb-license']))
{
    $license = $_POST['cb-license'];
    $db->update(tbl("config"),array("value"),array($license),"name='cb_license'") ;
    e('ClipBucket license has been updated','m');
}

subtitle("Update ClipBucket License");
template_files('update_license.html');
display_it();
?>


