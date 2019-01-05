<?php

function uninstall_cb_ad_manager(){
	global $db;
	$db->Execute('DROP TABLE '.tbl("uads"));
	$db->Execute('DROP TABLE '.tbl("config_uads"));
}

uninstall_cb_ad_manager();

?>