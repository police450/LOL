<?php
if(!defined('MAIN_PAGE')){
    define('MAIN_PAGE', 'Advanced Social Connect');
}
if(!defined('SUB_PAGE')){
    define('SUB_PAGE', 'Twitter Settings');
}
require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$userquery->login_check('admin_access');
$pages->page_redir();

// Getting Fields List To Display On Form
$tw_configs = tw_configs();
assign('tw_config_vals',$tw_configs);

if(isset($_GET['enable'])){
	$id = mysql_clean($_GET['enable']);
	status_tw_connect_button($id);
}

if(isset($_POST['update']))
{
	$data = $_POST;
	update_twit_configs( $data );
}

// displaying Template File For The Plugin
subtitle("Twitter Settings");
template_files(SOCIAL_CON_ADMIN_HTML.'/twit_configs.html');

?>