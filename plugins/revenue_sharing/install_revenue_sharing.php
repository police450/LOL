<?php


if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');
	
$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('revsharing_requests')." (
  `request_id` int(20) NOT NULL AUTO_INCREMENT,
  `userid` int(20) NOT NULL,
  `request_state` int(10) NOT NULL,
  `publisher_website` varchar(255) NOT NULL,
  `country` varchar(10) NOT NULL,
  `phone_no` varchar(100) NOT NULL,
  `paypal_email` varchar(100) NOT NULL,
  `bank_acc_no` varchar(100) NOT NULL,
  PRIMARY KEY (`request_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('revsharing_rpm')." (
  `rpm_id` int(20) NOT NULL AUTO_INCREMENT,
  `userid` int(20) NOT NULL,
  `tier_name` varchar(255) NOT NULL,
  `rpm` TEXT NOT NULL,
  `countries` TEXT NOT NULL,
  PRIMARY KEY (`rpm_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('revsharing_users')." (
  `rev_share_id` int(20) NOT NULL AUTO_INCREMENT,
  `userid` int(20) NOT NULL,
  `status` int(20) NOT NULL,
  `last_payment_done` TEXT NOT NULL,
  `earning_user_from` TEXT NOT NULL,
  `publisher_website` varchar(255) NOT NULL,
  `country` varchar(10) NOT NULL,
  `phone_no` varchar(100) NOT NULL,
  `paypal_email` varchar(100) NOT NULL,
  `bank_acc_no` varchar(100) NOT NULL,
  PRIMARY KEY (`rev_share_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('revsharing_earnings')."(
  `earning_id` int(20) NOT NULL AUTO_INCREMENT,
  `userid` int(20) NOT NULL,
  `earnings` TEXT NOT NULL,
  `views` bigint(20) NOT NULL,
  `date_time` date NOT NULL,
  `paid_check` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`earning_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('revsharing_payments')."(
  `payment_id` int(20) NOT NULL AUTO_INCREMENT,
  `userid` int(20) NOT NULL,
  `amount` TEXT NOT NULL,
  `date_time` datetime NOT NULL,
  `receiver_email` varchar(200) NOT NULL,
  `receiver_bank_acc` varchar(200) NOT NULL,
  `sent_via` varchar(20) NOT NULL,
  PRIMARY KEY (`payment_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

$db->Execute("INSERT INTO ".tbl('config')." (`configid`, `name`, `value`) VALUES ('NULL', 'rev_view_per_matrix', '1000'),
(NULL, 'rev_currency', 'USD'),
(NULL, 'rev_test_mode', 'enabled'),
(NULL, 'rev_paypal_email', 'my@paypal.com'),
(NULL, 'rev_paypal_sandbox_email', 'sandbox@paypal.com'),
(NULL, 'rev_paypal_client_id', '123'),
(NULL, 'rev_paypal_secret', '123'),
(NULL, 'rev_paypal_rest_api', 'yes');");

$db->Execute("INSERT INTO ".tbl('revsharing_rpm')." (`rpm_id`, `userid`, `tier_name`, `rpm`, `countries`) VALUES ('NULL', '0', 'default','0.25','');");

?>