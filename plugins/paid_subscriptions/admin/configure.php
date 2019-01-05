<?php

if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');

/**
 * this file controls all the settings and administarting paid packages
 */

template_files('configure.html',PAID_SUBS_DIR.'/admin');

$allow_user_access = $paidSub->configs[ 'allow_user_set_premium' ];

if(isset($_POST['update']))
{
	$array = array
	('currency',
	'allow_user_set_premium',
	'paypal_email',
	'paypal_sandbox_email',
	'currency',
	'license_key',
	'premium_type',
	'premium_videos',
	'allow_demo',
	'use_first_data',
	'fd_api_login',
	'fd_api_key',
	'fd_store_id',
	'fd_currency',
	'use_pay_pal',
	'use_2co',
	'2co_vendor_id',
	'demo_allow_type',
	'test_mode',
	'pay_to_text',
	'show_prem_icon',
	'alertpay_code',
	'alertpay_email',
	'use_alertpay',
	'notify_on_sub',
	'notify_on_payment',
	'email_notification',
	'paypal_client_id',
	'paypal_rest_api',
	'paypal_secret'
	);
	
	foreach($array as $name)
	{
		$value = clean(post($name));
		if($name=='premium_videos')
			$value = '|no_mc|'.preg_replace("/\,+/",",",$value);
		$db->update(tbl('paid_configs'),array("value"),array($value)," name='$name' ");
	}

    if ( isset( $_POST[ 'allow_user_set_premium' ] ) ) {
        update_user_premium_access( mysql_clean( post( 'allow_user_set_premium' ) ), $allow_user_access );
    }

	e("Configurations have been updated","m");
}

	$configs = $paidSub->getConfigs();

	assign('config',$configs);
	
	subtitle('Configuration - Paid subscriptions');



?>