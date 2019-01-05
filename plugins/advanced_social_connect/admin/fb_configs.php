<?php
if(!defined('MAIN_PAGE')){
    define('MAIN_PAGE', 'Advanced Social Connect');
}
if(!defined('SUB_PAGE')){
    define('SUB_PAGE', 'Facebook Settings');
}

require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$userquery->login_check('admin_access');
$pages->page_redir();

// Getting Fields List To Display On Form
$fb_configs = fb_configs();
assign('fb_config_vals',$fb_configs);

if(isset($_GET['enable'])){
	$id = mysql_clean($_GET['enable']);
	status_fb_connect_button($id);
}

if(isset($_POST['update']))
{
	$data = $_POST;
	update_fb_configs($data);
}

// displaying Template File For The Plugin
subtitle("Facebook Settings");
template_files(SOCIAL_CON_ADMIN_HTML.'/fb_configs.html');
?>