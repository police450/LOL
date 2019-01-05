<?php

if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');
	
$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('paid_configs')." (
  `config_id` int(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;");


$db->Execute("INSERT INTO ".tbl('paid_configs')." (`config_id`, `name`, `value`) VALUES
(1, 'currency', 'USD'),
(2, 'test_mode', 'enabled'),
(3, 'paypal_email', 'my@paypal.com'),
(4, 'paypal_sandbox_email', 'sandbox@paypal.com'),
(5, 'license_key', 'CBPAIDMODXXXXXX'),
(6, 'license_local_key', 'XXX'),
(9, 'allow_demo', '2.5'),
(7, 'premium_type', 'selected'),
(8, 'premium_videos', '0'),
(NULL,'show_prem_icon','yes'),
(NULL,'alertpay_code','yes'),
(NULL,'alertpay_email','yes'),
(NULL,'use_alertpay','yes'),
(NULL,'notify_on_sub','yes'),
(NULL,'notify_on_payment','yes'),
(NULL,'email_notification','yes'),
(NULL,'use_first_data','yes'),
(NULL, 'fd_api_login', 'XXX'),
(NULL, 'fd_api_key', 'XXX123'),
(NULL, 'fd_store_id', 'XXX456'),
(NULL, 'fd_shared_secret', 'XXX'),
(NULL, 'pay_to_text', 'Your pay_to Text'),
(NULL, 'fd_currency', '840'),
(NULL, 'paypal_client_id', '123'),
(NULL, 'paypal_secret', '123'),
(NULL, 'paypal_rest_api', 'yes'),
(NULL,'demo_allow_type','videos');");


$db->Execute("CREATE TABLE IF NOT EXISTS  ".tbl('paid_orders')." (
  `order_id` int(255) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(200) DEFAULT NULL,
  `order_status` enum('active','pending','fraud','cancelled') NOT NULL DEFAULT 'pending',
  `order_qty` bigint(20) DEFAULT NULL,
  `userid` int(200) NOT NULL,
  `package_id` int(200) NOT NULL,
  `subscription_id` int(255) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");



$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('paid_packages')." (
  `package_id` int(11) NOT NULL AUTO_INCREMENT,
  `pkg_title` varchar(255) NOT NULL,
  `pkg_desc` mediumtext NOT NULL,
  `pkg_type` enum('subs','ppv','mins','vids') NOT NULL DEFAULT 'subs',
  `pkg_days` bigint(255) NOT NULL,
  `pkg_vids` bigint(255) NOT NULL,
  `pkg_mins` bigint(255) NOT NULL,
  `pkg_price` double NOT NULL,
  `pkg_credits` bigint(20) NOT NULL,
  `pkg_ppv` bigint(255) NOT NULL DEFAULT '-1',
  `is_collection` enum('yes','no') NOT NULL DEFAULT 'no',
  `active` enum('yes','no') NOT NULL DEFAULT 'yes',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`package_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;");


$db->Execute("CREATE TABLE IF NOT EXISTS  ".tbl('paid_subscriptions')."  (
  `subscription_id` int(255) NOT NULL AUTO_INCREMENT,
  `userid` int(255) NOT NULL,
  `package_id` int(255) NOT NULL,
  `agreement_id` varchar(255) NOT NULL,
  `pkg_qty` bigint(1) NOT NULL,
  `start_date` varchar(30) NOT NULL DEFAULT '',
  `end_date` varchar(30) NOT NULL DEFAULT '',
  `allowed_vids` text NOT NULL,
  `watched` bigint(255) DEFAULT '0',
  `watched_ppv` bigint(20) NOT NULL,
  `watched_time` bigint(255) DEFAULT '0',
  `credits_used` bigint(20) NOT NULL,
  `active` enum('yes','no') NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`subscription_id`),
  KEY `package_id` (`package_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");



$db->Execute("CREATE TABLE IF NOT EXISTS  ".tbl('paid_transactions')." (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `gateway` varchar(200) NOT NULL,
  `amount` varchar(200) NOT NULL,
  `fee_charged` varchar(200) NOT NULL,
  `transaction_code` varchar(200) NOT NULL,
  `status` enum('ok','failed','fraud','cancelled','other') NOT NULL DEFAULT 'other',
  `gateway_payment_status` varchar(200) NOT NULL,
  `payer_name` varchar(200) NOT NULL,
  `payer_email` varchar(200) NOT NULL,
  `details` text NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`transaction_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;");


$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('paid_demo')." (
 `demo_id` int(225) NOT NULL AUTO_INCREMENT,
  `demo_ip` varchar(16) NOT NULL,
  `watched_time` bigint(200) NOT NULL,
  `watched` text NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`demo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");


$db->Execute("CREATE TABLE IF NOT EXISTS  ".tbl('paid_subs_videos')." (
  `subs_video_id` int(255) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(255) NOT NULL,
  `videoid` int(255) NOT NULL,
  PRIMARY KEY (`subs_video_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;");

$db->Execute("CREATE TABLE IF NOT EXISTS  ".tbl('paid_agreements')." (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `plan_id` varchar(255) NOT NULL,
  `package_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `frequency` varchar(100) NOT NULL,
  `currency` varchar(100) NOT NULL,
  `amount` int(255) NOT NULL,
  `cycles` int(100) NOT NULL,
  `frequency_interval` int(100) NOT NULL,
  `setup_fee` int(100) NOT NULL,
  `approval_url` text NOT NULL,
  `execute_url` text NOT NULL,
  `start_date` varchar(255) NOT NULL,
  `agreement_token` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

$db->Execute("CREATE TABLE IF NOT EXISTS  ".tbl('paid_billing_plan')." (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` varchar(255) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `pkg_title` varchar(255) NOT NULL,
  `plan_state` varchar(100) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `plan_description` varchar(255) NOT NULL,
  `plan_type` varchar(100) NOT NULL,
  `plan_pd_id` varchar(255) NOT NULL,
  `plan_pd_name` varchar(255) NOT NULL,
  `plan_pd_type` varchar(100) NOT NULL,
  `plan_pd_frequency` varchar(100) NOT NULL,
  `plan_currency` varchar(100) NOT NULL,
  `plan_amount` int(255) NOT NULL,
  `plan_cycles` int(100) NOT NULL,
  `plan_frequency_interval` int(100) NOT NULL,
  `plan_setup_fee` int(100) NOT NULL,
  `plan_auto_bill_amount` varchar(100) NOT NULL,
  `plan_create_time` varchar(255) NOT NULL,
  `plan_update_time` varchar(255) NOT NULL,
  PRIMARY KEY (`p_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

$db->Execute("CREATE TABLE IF NOT EXISTS  ".tbl('paid_payments_success')." (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agreement_id` varchar(255) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `payer_email` varchar(255) NOT NULL,
  `payer_id` varchar(255) NOT NULL,
  `payer_name` varchar(255) NOT NULL,
  `payer_postal_code` varchar(255) NOT NULL,
  `payer_country_code` varchar(255) NOT NULL,
  `payment_start_date` varchar(255) NOT NULL,
  `payment_end_date` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");


$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('paid_invoices')." (
  `invoice_id` int(255) NOT NULL AUTO_INCREMENT,
  `userid` int(200) NOT NULL,
  `amount` varchar(200) NOT NULL,
  `amount_recieved` varchar(200) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `fee_charged` varchar(200) NOT NULL,
  `gateway` int(30) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_recieved` datetime NOT NULL,
  `status` enum('paid','unpaid','partial_paid','fraud','cancelled') NOT NULL DEFAULT 'unpaid',
  `transaction_id` int(200) NOT NULL,
  PRIMARY KEY (`invoice_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;");


$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('paid_pkg_videos')." (
  `pkg_video_id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `videoid` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`pkg_video_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;
");


//Addin new configs
$db->Execute("INSERT INTO ".tbl('paid_configs')."
(`config_id` ,`name` ,`value`)
VALUES 
(NULL , 'use_pay_pal', 'yes'),
(NULL , 'use_2co', 'yes'),
(NULL , 'mins_allow_type', 'each'),
(NULL , '2co_vendor_id', '123465789'),
(NULL , 'allow_user_set_premium', 'yes');"
);


$db->Execute("ALTER TABLE ".tbl('video')." ADD `is_premium` ENUM( 'yes', 'no', 'ppv' ) NOT NULL DEFAULT 'no',
ADD `credits_required` BIGINT NOT NULL AFTER `is_premium`");

$db->Execute("ALTER TABLE ".tbl('video')." ADD `premium_cid` INT( 200 ) NOT NULL AFTER `credits_required` ");



$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('paid_reports')." (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` varchar(200) NOT NULL,
  `report_date` varchar(100) NOT NULL,
  `report_last_update` datetime NOT NULL,
  `report_data` text NOT NULL,
  `report_counts` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`report_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");




$db->Execute("INSERT INTO ".tbl('email_templates')." (`email_template_id`, `email_template_name`, `email_template_code`, `email_template_subject`, `email_template`, `email_template_allowed_tags`) VALUES
(NULL, 'Paid Subscription Order', 'paid_order', '[{website_title}] New subscription notification', '{website_title} Subscriptions\r\n\r\n\r\nOrder Information\r\nPackage : {package_title}\r\nOrderID : {order_id}\r\nInvoiceID : {invoice_id}\r\nPayment Method : {gateway}\r\n\r\nUser Information\r\nUsername : {username}\r\nUser email : {email}\r\nUserIp : {user_ip}\r\nOrderIp : {order_ip}\r\n\r\n\r\nThanks for making a purchase, we are reviewing your order, once it is reviewed your subscription will be activated. You can review your subscription by <a href=\"{baseurl}/module.php?s=premium&p=subscriptions\">clicking here</a> or following the link given below\r\n{baseurl}/module.php?s=premium&p=subscriptions\r\n\r\nFor assistance and support, please <a href=\"{baseurl}/page/4/help\">click here</a> or follow this link\r\n{baseurl}/page/4/help\r\n\r\nRegards\r\n{website_title}', ''),
(NULL, 'Paid Subscription Activation', 'paid_activation', '[{website_title}] Subscription has been activated', '{website_title} Subscriptions\r\n\r\nHello {username}\r\nYour subscription package \"{package_title}\" has been activated, now you can start watching premium videos, please review your package by <a href=\"{baseurl}/module.php?s=premium&p=subscriptions&sid={sid}\">clicking here</a> or follow the link given below.\r\n{baseurl}/module.php?s=premium&p=subscriptions&sid={sid}\r\n\r\nFor assistance and support, please <a href=\"{baseurl}/page/4/help\">click here</a> or follow this link\r\n{baseurl}/page/4/help\r\n\r\nRegards\r\n{website_title}', ''),
(NULL, 'Paid payment notification', 'paid_payment', '[{website_title}] Payment received notification', '{website_title} Subscription\r\n\r\nA new payment is recieved, following are the details\r\n\r\n=================================\r\nAmount : {amount}\r\nFees : {fees}\r\nGateway : {gateway}\r\nOrder # : {order_id}\r\nInvoice # : {invoice_id}\r\n\r\nPayer Email : {payer_email}\r\nPayer Name : {payer_name}\r\n\r\nUsername : {username}\r\nUser Email : {email}\r\n\r\n=================================\r\nDate &amp; Time: {date}\r\n\r\n{website_title} payment notification email\r\n', '');");



function paid_new_permission()
{
	
	global $userquery,$eh;
	
	$perm_array = array
	(
		'name' => 'Allow users to make video premium',
		'code' => 'allow_make_premium',
		'desc' => 'Allow users to make their videos premium',
		'default' => 'yes',
		'type'	=> 4
	);


	$userquery->add_new_permission($perm_array);
	$eh->flush_error();

	
}
paid_new_permission();



?>