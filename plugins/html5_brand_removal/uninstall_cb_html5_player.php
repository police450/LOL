<?php
//Function used to uninstall Plugin
	function un_install_cb_html5_player()
	{
		global $db;
		$db->Execute(
		'DROP TABLE '.tbl("config_html5").''
		);
	}

	un_install_cb_html5_player();


	?>