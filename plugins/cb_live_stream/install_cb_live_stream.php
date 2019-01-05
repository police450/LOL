<?php

function install_cb_live_stream(){
	global $db;

	$db->Execute("CREATE TABLE ".tbl("live_channel")." (
	  `live_channel_id` int(255) NOT NULL AUTO_INCREMENT,
	  `channel_name` varchar(60) NOT NULL,
	  `description` text NOT NULL,
	  `stream_name` varchar(30) NOT NULL,
	  `app_type` varchar(11) NOT NULL,
	  `is_live` enum('yes','no') NOT NULL DEFAULT 'no',
	  `channel_link` TEXT NOT NULL,
	  `recording` enum('yes','no') NOT NULL DEFAULT 'no',
	  `userid` bigint(20) NOT NULL,
	  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	   PRIMARY KEY (`live_channel_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


	$db->Execute("INSERT INTO  ".tbl('config')." (`configid`, `name`, `value`) 
		VALUES 
		(NULL, 'wowza_api_basepath', 'http://localhost:8087'),
		(NULL, 'wowza_server_name', '_defaultServer_'),
		(NULL, 'wowza_api_version', 'v2'),
		(NULL, 'wowza_server_vhost', '_defaultVHost_'),
		(NULL, 'wowza_player_ip', '10.1.1.60'),
		(NULL, 'wowza_player_port', '1935'),
		(NULL, 'wowza_file_directory', '/usr/local/WowzaStreamingEngine-4.7.1/content'),
		(NULL, 'wowza_source_username', 'wowza_source_user'),
		(NULL, 'wowza_source_password', 'wowza_source_pass'),
		(NULL, 'wowza_connect_api', 'no');");

}

install_cb_live_stream();


?>