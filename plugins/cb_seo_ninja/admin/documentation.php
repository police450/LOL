<?php
	if(!defined('MAIN_PAGE')){
    	define('MAIN_PAGE', 'SEO Ninja');
	}

	if(!defined('SUB_PAGE')){
	    define('SUB_PAGE', 'Documentation');
	}

	require_once '../includes/admin_config.php';
	$userquery->admin_login_check();
	$userquery->login_check('admin_access');
	$pages->page_redir();
	subtitle("SEO Ninja Documentation");
	template_files(SEO_NINJA_ADMIN_DIR.'/styles/doc.html');
?>