<?php


 /**
* File: Functions
* Description: This Class is written to manange all the Actions for paypal rest api
* @license: Attribution Assurance License
* @since: ClipBucket 2.8
* @author[s]: Fahad Abbas
* @copyright: (c) 2008 - 2016 ClipBucket / PHPBucket
* @modified: June 15, 2017 ClipBucket 2.8.2
*/


class paypalRest 
{
	var $initilzed = false; 
	var $apiUrl = "https://api.paypal.com/v1";
	var $requests = array(
		"initiate"=>"/payments/payment",
		"execute"=>"/payments/payment/{id}/execute",
		"create_plan" => "/payments/billing-plans",
		"activate_plan" => "/payments/billing-plans/{id}",
		"create_agreement"=>"/payments/billing-agreements",
		"bill_agreement"=>"/payments/billing-agreements/{id}/bill-balance",
		"execute_agreement"=>"/payments/billing-agreements/{id}/agreement-execute",
		"view_transactions"=>"/payments/billing-agreements/{id}/transactions",

		);

	function __construct($paidSub){
		$this->initilzed = true;
		if($paidSub->configs['test_mode']=='enabled'){
			$this->devMode();
		}
	}

 	/**
	* Used to get access token from paypal
	* @param   : { Array } { chat details }
	* @example : save_chat($array) { get access token from paypal to proceed furthur}
	* @return  : { Boolean } 
	* @since   : 15th June, 2017 ClipBucket 2.8.2
	* @author  : Fahad Abbas
	*/
	function devMode(){
		$this->apiUrl = "https://api.sandbox.paypal.com/v1";
	}

 	/**
	* Used to get access token from paypal
	* @param   : { Array } { chat details }
	* @example : get_access_token($array) { get access token from paypal to proceed furthur}
	* @return  : { token } 
	* @since   : 13th April, 2017 ClipBucket 2.8.2
	* @author  : Fahad Abbas
	*/
	function get_access_token($params){
		
		$url = $this->apiUrl."/oauth2/token?grant_type=client_credentials";
		$username = $params['paypal_client_id'];
		$password = $params['paypal_secret'];
		//starting request for paypal access token
		$headers = array(
			"Accept-Language: en_US",
			"Accept: application/json"
			);

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL,$url); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		curl_setopt($ch, CURLOPT_USERPWD, trim($username) . ':' . trim($password));
		curl_setopt($ch, CURLOPT_POST, 1); 
		$data = curl_exec($ch); 
		if (curl_errno($ch)) { 
			throw new Exception(curl_error($ch)); 
		} 
		curl_close($ch); 

		if ($data){
			$token_response  = json_decode($data,true);
			if ($token_response['access_token']){
				$token = $token_response['access_token'];
			}else{
				throw new Exception($token_response['error_description']); 
			}
		}
		return $token;
	}


	function paypal_request($params){
		
		if (!$params['access_token']){
			throw new Exception("Please provide access token !");
		}

		$accessToken = $params['access_token'];
		$request = json_encode($params['request']);
		$request_type = $params['request_type'];
		$target_url = $this->apiUrl.$this->requests[$request_type];
		if ($params['id']){
			$target_url = str_replace('{id}',$params['id'],$target_url);
		}
		$headers = array(
			"Authorization: Bearer " . $accessToken,
			"Content-Type: application/json"
			);

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL,$target_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request); 
		if($params['custom_request_type']){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $params['custom_request_type']);
		} 
		$data = curl_exec($ch);

		$returnCode = curl_getinfo($ch);
		// pr($returnCode,true);



		if (curl_errno($ch)) { 
			throw new Exception(curl_error($ch)); 
		} 
		curl_close($ch); 

		if($params['custom_request_type']=='PATCH'){
			
			return $returnCode;

		}else{

			if($data){

				$response  = json_decode($data,true);

			}else{

				throw new Exception("Something went wrong in initializing and getting response!");			

			}

			return $response;
			

		}

		
	}

}