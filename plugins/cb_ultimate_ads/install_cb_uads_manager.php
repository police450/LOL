<?php
//Creating Table for Ads if not exists
function install_cb_uads_manager(){
  	global $db;
  	$db->Execute(
  	"CREATE TABLE IF NOT EXISTS ".tbl('uads')." (
    `ad_id` int(11) NOT NULL AUTO_INCREMENT,
    `ad_tag` varchar(10000) NOT NULL,
    `ad_desc` text NOT NULL,
    `ad_type` int(11) NOT NULL,
    `linear_type` text NOT NULL,
    `skippable` ENUM('yes','no') NOT NULL DEFAULT 'no', 
    `skip_time` INT(20) NOT NULL DEFAULT '5',
    `category_id` text NOT NULL DEFAULT '',
    `ad_status` int(1) NOT NULL DEFAULT '1',
    `target_url` varchar(225) NOT NULL DEFAULT 'http://clip-bucket.com',
    `banner_ext` varchar(10) NOT NULL DEFAULT '',
    `impressions` int(225) NOT NULL DEFAULT '0',
    `clicks` int(225) NOT NULL DEFAULT '0',
    `target_imp` int(225) NOT NULL DEFAULT '0',
    `start_date` int(225) NOT NULL DEFAULT '0',
    `country` text NOT NULL DEFAULT '', 
    `ad_time` VARCHAR(10) NOT NULL,
    `end_date` int(225) NOT NULL DEFAULT '0',
    `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ad_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;"
  	);

    $db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('config_uads')." (
      `config_id` int(20) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `value` text NOT NULL,
      PRIMARY KEY (`config_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;");

    $db->Execute("INSERT INTO ".tbl('config_uads')." (`config_id`, `name`, `value`) VALUES
    (1, 'license_key', 'CBADS13246789-XXX'),
    (2, 'license_local_key', 'XXX');");
}

//This will first check if plugin is installed or not, if not this function will install the plugin details
install_cb_uads_manager();

?>