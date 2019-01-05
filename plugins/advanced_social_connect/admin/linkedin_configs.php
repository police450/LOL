<?php
if(!defined('MAIN_PAGE')){
    define('MAIN_PAGE', 'Advanced Social Connect');
}
if(!defined('SUB_PAGE')){
    define('SUB_PAGE', 'LinkedIn Settings');
}
require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$userquery->login_check('admin_access');
$pages->page_redir();

// Getting Fields List To Display On Form
$lnk_configs = lnk_configs();
assign('lnk_config_vals',$lnk_configs);

if(isset($_GET['enable'])){
	$id = mysql_clean($_GET['enable']);
	status_lnk_connect_button($id);
}

if(isset($_POST['update']))
{
	$data = $_POST;
	update_linkedin_configs( $data );
}

// displaying Template File For The Plugin
subtitle("LinkedIn Settings");
template_files(SOCIAL_CON_ADMIN_HTML.'/linkedin_configs.html');
?>