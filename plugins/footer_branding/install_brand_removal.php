<?php


$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('brand_configs')." (
  `config_id` int(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;");

$db->Execute("INSERT INTO ".tbl('brand_configs')." (`config_id`, `name`, `value`) VALUES
(1, 'license_key', 'CBBRANDREMOVALXXX'),
(2, 'license_local_key', 'XXX'),
(3, 'footer_replaced', 'no'),
(4, 'success_ip', 'blah');");


