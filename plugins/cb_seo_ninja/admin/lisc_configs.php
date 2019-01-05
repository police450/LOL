<?php
	if(!defined('MAIN_PAGE')){
    	define('MAIN_PAGE', 'SEO Ninja');
	}

	if(!defined('SUB_PAGE')){
	    define('SUB_PAGE', 'Liscense Settings');
	}

	require_once '../includes/admin_config.php';
	$userquery->admin_login_check();
	$userquery->login_check('admin_access');
	$pages->page_redir();
	global $ninja,$db;

	if (isset($_POST['lisc_key']))
	{
		$key = $_POST['lisc_key'];
		$db->update(tbl("seo_ninja_lisc_configs"), array("value"), array($key), "name = 'license_key'");
	}

	$vals = $db->select(tbl("seo_ninja_lisc_configs"),"*","name = 'license_key'");
	assign("lisc_key",$vals[0]['value']);

	subtitle("Liscense Settings - SEO Ninja");
	template_files(SEO_NINJA_ADMIN_HTML.'/ninja_lisc.html');
?>