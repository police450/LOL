<?php

/** 
 * File used to get and paypal instant notifaction , parse and store it in appropriate place
 */
define("THIS_PAGE","2co_ipn");
include('../../../includes/config.inc.php');
include(PAID_SUBS_DIR.'/gateways/2checkout.php');
$cb_2co = new toCheckout();

// Check validity and write down it
if($cb_2co->validateIpn())
{
	//pr($_POST);
	
	file_put_contents(PAID_SUBS_DIR.'/ipn/2co.txt', $cb_2co->ipnData);
	
	$trans_code = mysql_clean($cb_2co->ipnData['sale_id']);
	$trans_status = $cb_2co->ipnData['invoice_status'];
	$payment_date = now();
	
	$order_id = mysql_clean($cb_2co->ipnData['vendor_order_id']);
	
	$email = mysql_clean($cb_2co->ipnData['customer_email']);
	$name = mysql_clean($cb_2co->ipnData['customer_first_name']." ".$cb_2co->ipnData['customer_last_name']);
	$trans_array  = '';
	$amount = $cb_2co->ipnData['invoice_list_amount'];

	$trans_array = json_encode($cb_2co->ipnData);
	$sid = $paidSub->getSidFromOid($order_id);
	
	if($trans_status=='approved' && $cb_2co->ipnData['fraud_status']=='pass')
	{
		$paidSub->subscriptionAction('activate',$sid);
		$trans_status = "completed";
		$order_status = "paid";
	}else
	{
		$paidSub->subscriptionAction('deactivate',$sid);
		$trans_status = "completed";
		$order_status = "failed";
	}
	
	
	$db->update(tbl("paid_orders"),array('order_gateway','order_payment_status'),
	array('paypal',$order_status),"order_id='".$order_id."'");
	$db->update(tbl("paid_transactions"),
	array('transaction_code','transaction_gateway','transaction_details','date_updated','transaction_status'),
	array($trans_code,'2Checkout','|no_mc|'.$trans_array,now(),$trans_status),"order_id='".$order_id."'");

	$file = fopen(PAID_SUBS_DIR.'/ipn/ipn_file.txt',"a+");

	redirect_to(BASEURL.'/premium_service.php?mode=thank_you');
	//exit('success');
}
	
	redirect_to(BASEURL.'/premium_service.php?mode=cancel');
	exit();