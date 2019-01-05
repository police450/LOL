<?php 

/**
	* File: Execute
	* Description: This file executes the paypment process with rest full api of paypal
	* @license: Attribution Assurance License
	* @since: ClipBucket 2.8.2
	* @author[s]: Fahad Abbas
	* @copyright: (c) 2008 - 2016 ClipBucket / PHPBucket
	* @modified: April 4, 2017 ClipBucket 2.8.2
*/

include("../../../../includes/config.inc.php");
require_once dirname(dirname(dirname(__FILE__)))."/gateways/paypal_rest.php";
$paypal = new paypalRest($paidSub);

try{

	$response = array();
	$payment_id = $_POST['paymentID'];
	$payer_id = $_POST['payerID'];
	$invoice_id = $_POST['invoiceId'];
	$sid = $_POST['subscriptionId'];
	$order_id = $_POST['orderId'];


	if (!$payer_id || !$invoice_id || !$sid || !$order_id){
		throw new Exception("Information is incorrect, please check if your initailization process have any error!");
	}else{

		$token_params['paypal_client_id'] = $paidSub->configs['paypal_client_id'];
		$token_params['paypal_secret'] = $paidSub->configs['paypal_secret'];

		//request for paypal access tokens
		$token = $paypal->get_access_token($token_params);

		//Starting request for paypal final execution and verifiaction of payment
		if ($token){

			$execute_params['access_token'] = $token;
			$execute_params['request'] = array("payer_id"=>$payer_id);
			$execute_params['id'] = $payment_id;
			$execute_params['request_type'] = 'execute'; 

			$execute_response  = $paypal->paypal_request($execute_params);

			if ($execute_response){
				
				if ($execute_response['state'] =='approved' ){

					$response['msg'] = "Done";
					$response['data'] = $execute_response;

					$payer_details = $execute_response['payer'];
					$trans_details = $execute_response['transactions'][0];
					$related_sources = $trans_details['related_resources'][0];
					
					//Checking for transaction status 
					$transaction_id  = $related_sources['sale']['id'];
					$trans_status_resp  = $related_sources['sale']['state'];
					if ($trans_status_resp   == 'completed'){
						$trans_status = "ok";
					}else{
						$trans_status = "failed";
					}
					

					//$tid for transaction id
					$tid = $transaction_id;
					$gateway = $payer_details['gateway'];
					$amount = $trans_details['amount']['total'];
					$fee_chaged = $related_sources['sale']['transaction_fee']['value'];
					$payer_name = $payer_details['payer_info']['first_name']." ".$payer_details['payer_info']['last_name'];
					$payer_email = $payer_details['payer_info']['email'];
					$is_recurring = false;
					$status = $trans_status;
					$gatewayStatus = $trans_status_resp;

					$details = stripslashes(json_encode($execute_response));
					
					$invoiceID = $invoice_id;
					$orderID = $order_id;

					$paid_transactions_query = "INSERT INTO ".tbl("paid_transactions")." (gateway,amount,fee_charged,transaction_code,
						                        status,gateway_payment_status,payer_name,payer_email,details,date_added)
											    VALUES ('".$gateway."','".$amount."','".$fee_chaged."','".$tid."',
											    	   '".$status."','".$gatewayStatus."','".$payer_name."','".$payer_email."','".$details."','".NOW()."') ";
					
					//logData("query=>".$paid_transactions_query);
					$db->Execute($paid_transactions_query);
					
					$insert_id  = $db->insert_id();
					
					
					if($orderID)
						$order = $paidSub->getOrder($orderID);
					
		
					
					//Updating Invoice
					if($invoiceID)
					{
						$invoice  = $paidSub->getInvoice($invoiceID);
						if($invoice)
						{
							if($status=='ok')
							{
								if($amount>=$invoice['amount'] && $invoice['currency']==$trans_details['amount']['currency'])
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
								 '{currency}'	=> $execute_response['currency'],
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

				}else{
					throw new Exception($execute_response['message']);
				}
			}
		}
		//Ending request for paypal final execution and verifiaction of payment	
	}
	echo json_encode($response);
}catch(Exception $e){
	$Exception = $e->getMessage();
	echo json_encode(array("err"=>$Exception));
}

?>
