<?php

/*$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `server_ip` VARCHAR( 20 ) NOT NULL;');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `file_server_path` TEXT NOT NULL ;');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `files_thumbs_path` TEXT NOT NULL;');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `file_thumbs_count` VARCHAR( 30 ) NOT NULL ;');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `has_hq` ENUM(  "yes",  "no" ) NOT NULL DEFAULT  \'no\';');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `has_mobile` ENUM(  "yes",  "no" ) NOT NULL DEFAULT  \'no\';');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `filegrp_size` VARCHAR( 30 ) NOT NULL;');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `process_status` BIGINT( 30 ) NOT NULL DEFAULT \'0\';');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `file_directory` TEXT NOT NULL;');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `has_hd` ENUM(  "yes",  "no" ) NOT NULL DEFAULT \'no\';');*/
$db->Execute('ALTER TABLE  '.tbl("video").' ADD `has_sprite` ENUM( \'yes\', \'no\' ) NOT NULL DEFAULT \'no\';');
//added feilds for version 2
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `version` int( 11 ) NOT NULL DEFAULT \'1\';');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `video_files` TEXT NOT NULL;');
$db->Execute('ALTER TABLE  '.tbl("video").' ADD  `has_resulotion` ENUM(  "yes",  "no" ) NOT NULL DEFAULT \'no\';');

$db->Execute('CREATE TABLE IF NOT EXISTS '.tbl('servers').' (
`server_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_name` varchar(25) NOT NULL,
  `server_ip` varchar(20) NOT NULL,
  `secret_key` varchar(25) NOT NULL,
  `server_api_path` mediumtext NOT NULL,
  `server_main_role` varchar(25) NOT NULL DEFAULT \'snc\',
  `server_action` decimal(1,0) NOT NULL DEFAULT \'0\',
  `thumbs_role` enum(\'1\',\'2\') NOT NULL DEFAULT \'1\',
  `thumbs_assoc` int(20) NOT NULL,
  `upload_photos` enum(\'yes\',\'no\') NOT NULL DEFAULT \'yes\',
  `assoc_server_id` int(255) NOT NULL,
  `max_usage` mediumint(9) NOT NULL,
  `used` varchar(255) NOT NULL,
  `active` enum(\'yes\',\'no\',\'read_only\') NOT NULL,
  `ftp_host` varchar(200) NOT NULL,
  `ftp_user` varchar(50) NOT NULL,
  `ftp_pass` varchar(50) NOT NULL,
  `ftp_port` varchar(50) NOT NULL,
  `ftp_dir` varchar(250) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`server_id`)
  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;') ;


$db->Execute('CREATE TABLE IF NOT EXISTS '.tbl('server_configs').' (
  `id` int(225) NOT NULL AUTO_INCREMENT,
  `name` varchar(225) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;') ;

$db->Execute('INSERT INTO '.tbl('server_configs').' (`id`, `name`, `value`) VALUES
(1, \'license_key\', \'CBMS12345678910\'),
(2, \'license_key_local\', \'\'),
(3, \'success_ip', 'blah\');;') ;

$db->Execute('ALTER TABLE '.tbl('photos').'  ADD `server_url` TEXT NOT NULL AFTER `downloaded`; ');
$db->Execute('ALTER TABLE '.tbl('photos').'  ADD `file_directory` TEXT NOT NULL AFTER `server_url`; ');

?>