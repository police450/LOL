<?php
if(!defined('MAIN_PAGE')){
    define('MAIN_PAGE', 'CB Subtitles');
}
if(!defined('SUB_PAGE')){
    define('SUB_PAGE', 'Update Settings');
}

require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$userquery->login_check('admin_access');
$pages->page_redir();


if(isset($_POST['update']))
{
	$lisckey = mysql_clean($_POST['lisc_key']);
	$myquery->Set_Website_Details('cbsc_license',$lisckey);
	update_lisc_key($lisckey);

}

$configs = honey_capt_configs();

# assigns database values such as max caption files
$assign = the_config_assign( $configs );

assign('lisc_config_vals',$lisc_configs);


// displaying Template File For The Plugin
subtitle("Update CB Subtitles Settings");

template_files(HONEY_CAPT_ADMIN_HTML.'/honey_configs.html');
?>