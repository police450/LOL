<?php
if(!defined('MAIN_PAGE')){
    define('MAIN_PAGE', 'Advanced Social Connect');
}
if(!defined('SUB_PAGE')){
    define('SUB_PAGE', 'Google Settings');
}
require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$userquery->login_check('admin_access');
$pages->page_redir();

// Getting Fields List To Display On Form
$gm_configs = gm_configs();
assign('gm_config_vals',$gm_configs);

if(isset($_GET['enable'])){
	$id = mysql_clean($_GET['enable']);
	status_gm_connect_button($id);
}

if(isset($_POST['update']))
{
	$data = $_POST;
	update_google_configs($data);
}

// displaying Template File For The Plugin
subtitle("Google Settings");
template_files(SOCIAL_CON_ADMIN_HTML.'/google_configs.html');
?>