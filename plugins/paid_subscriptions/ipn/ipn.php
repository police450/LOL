<?php

/**
 * This file does all the IPN authentication JOB
 */

include("../../../includes/config.inc.php");


//exit('shutup! paypal');

$ipn = $_GET['ipn'];

switch($ipn)
{
	//Setting up paypal
	case "paypal":
	{
		$cbipn = new paypal();
	}
	break;
	
	case "alertpay":
	{
		$cbipn = new alertpay();
	}
	break;

	case "firstdata":
	{
		$cbipn = new firstdata();
	}
	break;
	
	
	default:
	exit("Invalid IPN");
}

$fo = fopen('post.txt','a+');
fwrite($fo,json_encode($_POST));
fclose($fo);


if($cbipn->validateIPN())
{
	$ipnData = $cbipn->ipnData;
	//$tid for transaction id
	$tid = $ipnData['transaction_id'];
	$gateway = $cbipn->gateway;
	$amount = $ipnData['amount'];
	$fee_chaged = $ipnData['fee_charged'];
	$payer_name = $ipnData['payer_name'];
	$payer_email = $ipnData['payer_email'];
	$is_recurring = $cbipn->is_recurring;
	$status = $cbipn->status;
	$gatewayStatus = $cbipn->gatewayStatus;

	$details = stripslashes(json_encode($ipnData));
	
	$invoiceID = $cbipn->invoice_id;
	$orderID = $cbipn->order_id;

	$paid_transactions_query = "INSERT INTO ".tbl("paid_transactions")." (gateway,amount,fee_charged,transaction_code,
		                        status,gateway_payment_status,payer_name,payer_email,details,date_added)
							    VALUES ('".$gateway."','".$amount."','".$fee_chaged."','".$tid."',
							    	   '".$status."','".$gatewayStatus."','".$payer_name."','".$payer_email."','".$details."','".NOW()."') ";
	
	//logData("query=>".$paid_transactions_query);
	$db->Execute($paid_transactions_query);
	
	$insert_id  = $db->insert_id();
	
	
	if($orderID)
		$order = $paidSub->getOrder($orderID);
	
	if($is_recurring)
	{
		//Create new order with invoice
		$pid = mysql_clean($order['package_id']);
		$gateway = $cbipn->gateway;
		$sid = $order['subscription_id'];
		//Adding Order
		$order_added = $paidSub->addOrder(array('subscription_id'=>$sid,'package_id'=>$pid,'gateway'=>$gateway),true);
		
		$orderID = $order_added['order_id'];
		$invoiceID = $order_added['invoice_id'];
	}
	
	//Updating Invoice
	if($invoiceID)
	{
		$invoice  = $paidSub->getInvoice($invoiceID);
		if($invoice)
		{
			if($status=='ok')
			{
				if($amount>=$invoice['amount'] && $invoice['currency']==$ipnData['currency'])
					$invoice_status = 'paid';
				elseif($amount)
					$invoice_status = 'partial_paid';
			}
			
			if($status=='cancelled' || $status=='failed')
				$invoice_status = 'cancelled';
			if($status=='fraud')
				$invoice_status = 'fraud';
			
			$amount_recieved = $amount - $fee_chaged;
			//Update invoice accordingly
			$db->update(tbl('paid_invoices'),array('amount_recieved',
			'fee_charged','gateway','status','transaction_id',"date_recieved"),
			array($amount_recieved,$fee_chaged,$gateway,$invoice_status,$insert_id,now()),"invoice_id='".$invoiceID."'");
		}
	}
	
	//Updaing Order
	if($orderID)
	{
		$order = $paidSub->getOrder($orderID);
		if($order)
		{
			if($status=='ok')
				$order_status = 'active';
			if($status=='cancelled' || $status=='failed')
				$order_status = 'cancelled';
			if($status=='fraud')
				$order_status = 'fraud';
			
			//Updating database order
			$db->update(tbl("paid_orders"),array("order_status"),array($order_status),"order_id='$orderID'");
			
			if($status=='ok')
			{
				
				
				//Updaing End Date of Subscription
				$package_data = $db->select(
				tbl('paid_subscriptions')." LEFT JOIN ".tbl("paid_packages")." on ".tbl('paid_subscriptions')
				.".package_id =
				 ".tbl('paid_packages').".package_id",
				tbl("paid_packages.pkg_days,paid_packages.pkg_title,paid_subscriptions.*"),
				tbl("paid_subscriptions.subscription_id ='".$order['subscription_id']."'"));
				
				$pkg = $package_data[0];
				if($pkg['pkg_days'])
				{	
					$now = now();
					if($pkg['active']=='yes' && $pkg['end_date'])
						$now = $pkg['end_date'];
					$now = strtotime($now);
					$end = $now+($pkg['pkg_days']*24*60*60);
					$end = date("Y-m-d h:i:s",$end);
				}else
					$end = "";
				
				//Activating subscription
				$db->update(tbl('paid_subscriptions'),array('active','end_date'),array('yes',$end),
				"subscription_id='".$order['subscription_id']."'");
				
				$userdetails = $userquery->get_user_details($pkg['userid']);
				//Sending Email
				$tpl = $cbemail->get_template('paid_activation');
				
				$more_var = array
				('{username}'	=> $userdetails['username'],
				 '{email}'		=> $userdetails['email'],
				 '{uid}'		=> $userdetails['userid'],
				 '{package_title}' => $pkg['pkg_title'],
				 '{sid}'		=> $order['subscription_id'],
				 '{order_id}'	=> $orderID,
				 '{invoice_id}'	=> $invoiceID,
				 '{payer_name}'	=> $payer_name,
				 '{payer_email}'	=> $payer_email,
				 '{amount}'	=> $amount,
				 '{fees}'	=>  $fee_chaged,
				 '{gateway}'	=> $gateway,
				 '{date}'	=> now(),
				 '{currency}'	=> $ipnData['currency'],
				 '{transaction_id}' => $tid,
				 				 
				);
				if(!is_array($var))
					$var = array();
				$var = array_merge($more_var,$var);
				$subj = $cbemail->replace($tpl['email_template_subject'],$var);
				$msg = nl2br($cbemail->replace($tpl['email_template'],$var));
				
				//Now Finally Sending Email
				cbmail(array('to'=>$userdetails['email'],'from'=>SUPPORT_EMAIL,
				'subject'=>$subj,'content'=>$msg));
				
				//Sending payment notification to admin		
				$tpl = $cbemail->get_template('paid_payment');
				
				$var = $more_var;
				$subj = $cbemail->replace($tpl['email_template_subject'],$var);
				$msg = nl2br($cbemail->replace($tpl['email_template'],$var));
				
				//Now Finally Sending Email
				if($paidSub->configs['email_notification'] 
					&& $paidSub->configs['notify_on_payment']=='yes')
				{
					cbmail(array('to'=>$paidSub->configs['email_notification'],'from'=>SUPPORT_EMAIL,
					'subject'=>$subj,'content'=>$msg));
				}
								
			}
		}
	}
	
	
}else
{
	exit("Nothing to do");
}

?>