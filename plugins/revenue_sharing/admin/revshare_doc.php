<?php
if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');

$userquery->admin_login_check();
$pages->page_redir();

if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE', 'Revenue Sharing');
}
if(!defined('SUB_PAGE')){
	define('SUB_PAGE', "Documentation");
}



subtitle("Documentation");
template_files(REV_SHARE_DIR.'/admin/revshare_doc.html');
?>