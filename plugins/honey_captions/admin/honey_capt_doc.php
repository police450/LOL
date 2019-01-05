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


template_files(HONEY_CAPT_ADMIN_HTML.'/honey_capt_doc.html');
?>