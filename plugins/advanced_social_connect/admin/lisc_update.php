<?php
if(!defined('MAIN_PAGE')){
    define('MAIN_PAGE', 'Advanced Social Connect');
}
if(!defined('SUB_PAGE')){
    define('SUB_PAGE', 'Update Licence');
}

require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$userquery->login_check('admin_access');
$pages->page_redir();

// Getting Fields List To Display On Form
$lisc_configs = cbsc_lisc_configs();
assign('lisc_config_vals',$lisc_configs);

global $db;
$ann_array = $db->_select('SELECT * FROM '.tbl("socialconn_lisc_configs"));

$lisckey_value = $ann_array[0]['value'];
if(is_array($ann_array))
assign('an', $ann_array[0]['value']);
else
assign('an', '');

if(isset($_POST['update']))
{
	$lisckey = mysql_clean($_POST['lisc_key']);
	$myquery->Set_Website_Details('cbsc_license',$lisckey);
	update_lisc_key($lisckey);

}

// displaying Template File For The Plugin
subtitle("Update Liscence Details");

template_files(SOCIAL_CON_ADMIN_HTML.'/lisc_update.html');
?>