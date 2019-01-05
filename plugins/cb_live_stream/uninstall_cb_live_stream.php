<?php 

?>
<?php

function uninstall_cb_live_stream(){
	global $db;

	$db->Execute('DROP TABLE'.tbl("live_channel")) ;
	$db->Execute("ALTER TABLE ".tbl('users')." DROP `is_live` ");

}

uninstall_cb_live_stream();


?>
