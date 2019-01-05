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

	function update_honeycapt_key($lisckey)
	{
		global $db;
		$lisckey = $lisckey;
		$db->Execute("UPDATE ".tbl("honey_capt_lisc_configs")." SET value='$lisckey' WHERE  name='license_key'");
	}

	if ( isset($_POST['lisc_key']) )
	{
		$key = $_POST['lisc_key'];
		update_honeycapt_key($key);
	}

	global $db;
	$ann_array = $db->_select('SELECT * FROM '.tbl("honey_capt_lisc_configs"));
	#pr($ann_array,true);
	$lisckey_value = $ann_array[0]['value'];
	if(is_array($ann_array))
	{
		assign('an', $ann_array[0]['value']);
	}
	else
	{
		assign('an', '');	
	}


	// displaying Template File For The Plugin
	subtitle("Update Liscence Details");

	template_files(HONEY_CAPT_ADMIN_HTML.'/lisc_update.html');
?>