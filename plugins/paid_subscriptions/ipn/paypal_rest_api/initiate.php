<?php

/**
	* File: Initiate
	* Description: This file initializes the paypment process with rest full api of paypal
	* @license: Attribution Assurance License
	* @since: ClipBucket 2.8.2
	* @author[s]: Fahad Abbas
	* @copyright: (c) 2008 - 2016 ClipBucket / PHPBucket
	* @modified: April 4, 2017 ClipBucket 2.8.2
*/


try{
	include("../../../../includes/config.inc.php");
	require_once dirname(dirname(dirname(__FILE__)))."/gateways/paypal_rest.php";
	$paypal = new paypalRest($paidSub);

	$request = $_POST;
	$response = array();

	$pid = $request['package_id'];
	$package_title = $request['package_title'];
	$package_desc = $request['package_desc'];
	$package_days = $request['package_days'];
	$package_price = $request['package_price'];
	$type = $request['type'];
	

	if ($type =='renew'){
		$sid = $request['subscription_id'];
		$sub = $paidSub->getSubscription($sid);
		if(!$sub){
			throw new Exception("Subscription does not exist");
		}elseif($sub['package_id']!=$pid){	
			throw new Exception("Invalid Package");
		}
	}else{
		// Adding inactive subscription 
		$sid = $paidSub->addSubscription(userid(),$pid,array('active'=>'no'));
	}
	//Adding Order
	$order_added = $paidSub->addOrder(array('subscription_id'=>$sid,'package_id'=>$pid,'gateway'=>'paypal'),true);

	if(error() && !$order_added){
		throw new Exception("Something went wrong in placing your order");
	}else{

		$invoice_id = $order_added['invoice_id'];
		$order_id = $order_added['order_id'];

		$token_params['paypal_client_id'] = $paidSub->configs['paypal_client_id'];
		$token_params['paypal_secret'] = $paidSub->configs['paypal_secret'];

		//request for paypal access tokens
		$token = $paypal->get_access_token($token_params);

		//Starting request for paypal first initialization and verifiaction of payment	
		if ($token){
			$initiate_params['access_token'] = $token; 
			$initiate_params['request_type'] = 'initiate'; 
			$initiate_params['request'] = array(
				"intent"=>"sale",
				"redirect_urls"=>array("return_url"=>BASEURL."/module.php?s=premium&p=thank_you","cancel_url"=>BASEURL),
				"payer"=>array("payment_method"=>"paypal"),
				"transactions"=>array(
					array(
						"amount"=>array(
							"total"=>$package_price,
							"currency"=>$paidSub->currency,
							"details"=>array(
								"subtotal"=>$package_price,
								"shipping"=>"0.00",
								"tax"=>"0.00",
								"shipping_discount"=>"0.00",					
							),
						),
						"item_list"=>array(
							"items"=>array(
								array(
									"quantity"=>"1",
									"name"=>$package_title,
									"price"=>$package_price,
									"currency"=>$paidSub->currency,
									"description"=>$package_desc,
									"tax"=>"0"
								)
							)
						),
						"description"=>$package_desc,
						"invoice_number"=>$invoice_id,
						"custom"=>"custom data",
					),
				)
			);

			$initiate_response  = $paypal->paypal_request($initiate_params);
			if ($initiate_response['id']){
				$response['paymentID'] = $initiate_response['id'];
				$response['order_id'] = $order_id;
				$response['subscription_id'] = $sid;
				$response['invoice_id'] = $invoice_id;
			}else{
				throw new Exception($initiate_response['message']);
			}
			
		}
		//Ending request for paypal first initialization and verifiaction of payment		
	}

	echo json_encode($response);

}catch(Exception $e){

	$Exception = $e->getMessage();
	echo json_encode(array("err"=>$Exception));
}

?>



