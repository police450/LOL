<?php

require_once('../includes/common.php');

//Creating Table for html5_player configs if not exists
function install_cb_html5_player()
{
	global $db;
	$db->Execute(
	"CREATE TABLE IF NOT EXISTS ".tbl('config_html5')." (
    `config_id` int(20) NOT NULL AUTO_INCREMENT,
     `name` varchar(100) NOT NULL DEFAULT '',
     `value` mediumtext NOT NULL,
      PRIMARY KEY (`config_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;"
	);
	
	//inserting new html5_player configs
	$db->Execute("INSERT INTO ".tbl('config_html5')." (`config_id`,`name`,`value`) VALUES
	('1','license_key','CBHTML5BRAND123456798'),
	('2','license_local_key','XXX'),
	('3','iv_logo_enable','no'),
	('4', 'success_ip', 'blah');");

}
//This will first check if plugin is installed or not, if not this function will install the plugin details
install_cb_html5_player();

?>