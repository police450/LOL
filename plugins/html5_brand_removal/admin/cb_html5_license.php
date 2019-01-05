<?php
require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$pages->page_redir();
$userquery->login_check('admin_access');

if(!defined('MAIN_PAGE')){
    define('MAIN_PAGE', 'CB HTML5 Player Settings');
}
if(!defined('SUB_PAGE')){
    define('SUB_PAGE', 'License Settings');
}


global $db;


if(isset($_POST['update_license']))
{
     $license = $_POST['hl5_license'];
     $db->update(tbl("config_html5"),
          array("value"),
          array($license), "config_id = '1'");
          e('Your license has been updated','m');
}

$query = "SELECT value FROM ".tbl("config_html5")." WHERE config_id = '1'";
$license =  $db->_select($query);
assign('license',$license[0]['value']);

template_files('cb_html5_license.html',CB_HTML5_PLUG_DIR.'/admin');