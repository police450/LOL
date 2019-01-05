<?php
	if(!defined('MAIN_PAGE')){
    	define('MAIN_PAGE', 'SEO Ninja');
	}

	if(!defined('SUB_PAGE')){
	    define('SUB_PAGE', 'Website Settings');
	}

	require_once '../includes/admin_config.php';
	$userquery->admin_login_check();
	$userquery->login_check('admin_access');
	$pages->page_redir();
	subtitle("Website Settings - SEO Ninja");
	template_files(SEO_NINJA_ADMIN_HTML.'/website_configs.html');
?>