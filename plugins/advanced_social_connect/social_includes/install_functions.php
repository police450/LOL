<?php

	function install_social_con()
	{
		global $db;

		$db->Execute("ALTER TABLE ".tbl("users")." ADD `soclid` VARCHAR( 225 ) NOT NULL AFTER `userid` ");

		$db->Execute("ALTER TABLE ".tbl("users")." ADD `social_account_id` VARCHAR( 225 ) NOT NULL AFTER `soclid` ");
		
		// Creating Table To Store Facebook API Credentials
		$db->Execute(
		'CREATE TABLE IF NOT EXISTS '.tbl("socialconn_configs").' (
		`app_id` TEXT NOT NULL,
		`app_key` TEXT NOT NULL,
		`app_secret` TEXT NOT NULL ,
		`dev_key` TEXT NOT NULL,
		`red_url` TEXT NOT NULL,
		`lisc_key` TEXT NOT NULL	
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;;'
		);

		$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('socialconn_lisc_configs')." (
		  `config_id` int(20) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) NOT NULL,
		  `value` text NOT NULL,
		  PRIMARY KEY (`config_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;");

		$db->Execute("INSERT INTO ".tbl('socialconn_lisc_configs')." (`config_id`, `name`, `value`) VALUES
		(1, 'license_key', 'you_liscence_here'),
		(2, 'license_local_key', 'XXX'),
		(3, 'success_ip', 'blah');");

		// Populating Initial Values
		$db->Execute("INSERT INTO  ".tbl('socialconn_configs')." (app_id, app_key, app_secret, dev_key, red_url, lisc_key) VALUES ('fbapp', 'Your App Key', 'Your App Secret', 'na', 'na', 'na'), ('gmapp', 'Your App Key', 'Your App Secret', 'Your Developer Key', 'Redirection Address', 'na'), ('twapp', 'Your App Key', 'Your App Secret', 'na', 'Redirection Address', 'na'), ('lnkapp', 'Your App Key', 'Your App Secret', 'na', 'na', 'na')");

	}

	function rm_social_con() 
	{
		global $db;

		$db->Execute("ALTER TABLE ".tbl("users")." DROP `soclid` ");
		
		$db->Execute(
		'DROP TABLE '.tbl("socialconn_configs")
		);
	}

?>