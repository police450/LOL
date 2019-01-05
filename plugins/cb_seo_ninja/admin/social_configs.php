<?php
	if(!defined('MAIN_PAGE')){
    	define('MAIN_PAGE', 'SEO Ninja');
	}

	if(!defined('SUB_PAGE')){
	    define('SUB_PAGE', 'Social Settings');
	}

	require_once '../includes/admin_config.php';
	$userquery->admin_login_check();
	$userquery->login_check('admin_access');
	$pages->page_redir();
	subtitle("Social Settings - SEO Ninja");
	template_files(SEO_NINJA_ADMIN_HTML.'/social_configs.html');
?>