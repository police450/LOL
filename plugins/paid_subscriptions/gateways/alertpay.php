<?php

/**
 * This class is used to operate AlertPay
 * IPN
 */
 
class alertpay 
{
	var $gatewayUrl = '';
	var $ipnLogFile = '';
	var $ipnData = array();
	var $ipnResponse = '';
	var $lastError = '';
	var $logIpn = '';
	
	var $invoice_id = "";
	var $order_id = "";
	
	var $gateway = 'alertpay';
	var $is_recurring = false;
	
	/**
	 * Initialize the Paypal gateway
	 *
	 * @param none
	 * @return void
	 */
	 
	function __construct()
	{
        // Some default values of the class
		$this->gatewayUrl = 'https://secure.payza.com/checkout';
		$this->ipnLogFile = PAID_SUBS_DIR.'/ipn/logs/alertpay.ipn_results.log';
		
		global $paidSub;
		
		if($paidSub->configs['test_mode']=='enabled')
			$this->enableTestMode();
	}

    /**
     * Enables the test mode
     *
     * @param none
     * @return none
     */
    function enableTestMode()
    {
        $this->testMode = TRUE;
        $this->gatewayUrl = 'https://sandbox.Payza.com/sandbox/payprocess.aspx';
    }

    /**
	 * Validate the IPN notification
	 *
	 * @param none
	 * @return boolean
	 */
	function validateIpn()
	{
		global $paidSub;
		define("ALERTPAY_SEC_CODE",$paidSub->configs['alertpay_code'] );
		define("ALERTPAY_MARCHANT_EMAIL", $paidSub->configs['alertpay_email']);
		//saving token recieved from payza api
		$token = $_REQUEST["token"];
		$query_string = $this->send_token($token);
		if ($query_string == 'INVALID TOKEN')
			return false;
		else
		{
			$data_chunks = array();
			foreach (explode('&', $query_string) as $chunk)
			{
		    	$param = explode("=", $chunk);
		    	$data_chunks[$param[0]] = urldecode($param[1]); 
			}
			$this->ipnData = $data_chunks;
		}
		
		$post = $this->ipnData;
		//logData("If Token is valid");
		if($post['ap_merchant'] == ALERTPAY_MARCHANT_EMAIL)
		{
			if($post['ap_purchasetype']=='subscription'
				&& !strstr($post['ap_status'],'Subscription'))
					return false;
			
			//logData("If Merchant email is valid");		
		 	// Valid IPN transaction.
		 	$this->logResults(true);
			//Verifying Transaction
			$this->verify_transaction($this->ipnData['ap_status']);
			
			//Setting Array values for database
			$this->ipnData["amount"] 			= $this->ipnData['ap_amount'];
			$this->ipnData["currency"] 			= $this->ipnData['ap_currency'];
			$this->ipnData["fee_charged"] 		= $this->ipnData["ap_feeamount"];
			$this->ipnData["transaction_id"] 	= $this->ipnData["ap_referencenumber"];
			$this->ipnData["payer_email"] 		= $this->ipnData["ap_custemailaddress"];
			$this->ipnData["payer_name"] 		= $this->ipnData["ap_custfirstname"]." "
			.$this->ipnData["ap_custlastname"];
			
			if($this->ipnData["ap_purchasetype"]=='subscription')
				$this->is_recurring ='yes';
			
			//Setting order and invoice
			$this->order_id = $this->ipnData["ap_itemcode"];
			$this->invoice_id = $this->ipnData["apc_1"];
			
		 	return true;
		}
		else
		{
		 	// Invalid IPN transaction.  Check the log for details.
			$this->lastError = "IPN Validation Failed . $urlParsed[path] : $urlParsed[host]";
			$this->logResults(false);
			return false;
		}
	}
	/**
	 * Validate Payza token information
	 *
	 * @param token string
	 * @return query string recieved from payza api 
	 */

	function send_token($token)
	{
		define("TOKEN_IDENTIFIER", "token=");
		// get the token from Payza
		$token = urlencode($token);
		//preappend the identifier string "token=" 
		$token = TOKEN_IDENTIFIER.$token; 
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://secure.payza.com/ipn2.ashx");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $token);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	
	/**
     * Logs the IPN results
     *
     * @param boolean IPN result
     * @return void
     */
    function logResults($success)
    {
        if (!$this->logIpn) return;

        // Timestamp
        $text = '[' . date('m/d/Y g:i A').'] - ';

        // Success or failure being logged?
        $text .= ($success) ? "SUCCESS!\n" : 'FAIL: ' . $this->lastError . "\n";

        // Log the POST variables
        $text .= "IPN POST Vars from gateway:\n";
        foreach ($this->ipnData as $key=>$value)
        {
            $text .= "$key=$value, ";
        }

        // Write to log
        $fp = fopen($this->ipnLogFile,'a+');
        fwrite($fp, $text . "\n\n");
        fclose($fp);
    }
	
	
	/**
	 * function used to verifiy transaction
	 */
	function verify_transaction($trans_status)
	{
		//Lets Check what is the status
		switch($trans_status)
		{
			case "Subscription-Payment-Failed":
			case "Subscription-Canceled":
			{
				$this->status = 'cancelled';
				$this->gatewayStatus = $trans_status;
			}
			break;
			
			case "Success":
			case "Subscription-Payment-Success":

			{
				$this->status = 'ok';
				$this->gatewayStatus = 'Completed';
				return true;
			}
			break;
			
			case "Subscription-Expired":
			{			
				$this->status = 'failed';
				$this->gatewayStatus = $trans_status;
			}
			break;
			
			default:
			{
				$this->status = 'other';
				$this->gatewayStatus = $trans_status;
			}
		}
		
		return false;
	}

}

?>