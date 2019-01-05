<?php
/**
 * This class is used to operate paypal
 * IPN
 */
 
class paypal 
{
	var $gatewayUrl = '';
	var $ipnLogFile = '';
	var $ipnData = array();
	var $ipnResponse = '';
	var $lastError = '';
	var $logIpn = '';
	
	var $invoice_id = "";
	var $order_id = "";
	
	var $gateway = 'paypal';
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
		$this->gatewayUrl = 'https://www.paypal.com/cgi-bin/webscr';
		$this->ipnLogFile = PAID_SUBS_DIR.'/ipn/logs/paypal.ipn_results.log';
		
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
        $this->gatewayUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }

    /**
	 * Validate the IPN notification
	 *
	 * @param none
	 * @return boolean
	 */
	function validateIpn()
	{
		// parse the paypal URL
		$urlParsed = parse_url($this->gatewayUrl);

		// generate the post string from the _POST vars
		$postString = '';

		foreach ($_POST as $field=>$value)
		{
			$this->ipnData["$field"] = $value;
			$postString .= $field .'=' . urlencode(stripslashes($value)) . '&';
		}
		
		$postString .="cmd=_notify-validate"; // append ipn command
		
		// open the connection to paypal
		$fp = fsockopen('tls://'.$urlParsed[host], 443, $errNum, $errStr, 30);

		if(!$fp)
		{
			// Could not open the connection, log error if enabled
			$this->lastError = "fsockopen error no. $errNum: $errStr";
			$this->logResults(false);

			return false;
		}
		else
		{
			// Post the data back to paypal

			fputs($fp, "POST $urlParsed[path] HTTP/1.1\r\n");
			fputs($fp, "Host: $urlParsed[host]\r\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-length: " . strlen($postString) . "\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $postString . "\r\n\r\n");

			// loop through the response from the server and append to variable
			while(!feof($fp))
			{
				$this->ipnResponse .= fgets($fp, 1024);
			}
		 	fclose($fp); // close connection

		}
        
       
        
		if(strpos($this->ipnResponse,'VERIFIED') !== false)
		{
		 	// Valid IPN transaction.
		 	$this->logResults(true);
			//Verifying Transaction
			$this->verify_transaction($this->ipnData['payment_status']);
			LogData('$this->ipnResponse=>2'.$this->ipnData['payment_status']);
			//Setting Array values for database
			$this->ipnData["amount"] 			= $this->ipnData['mc_gross'];
			$this->ipnData["fee_charged"] 		= $this->ipnData["payment_fee"] ? 
			$this->ipnData["payment_fee"] : $this->ipnData["mc_fee"];
			$this->ipnData["currency"] 			= $this->ipnData['mc_currency'];
			$this->ipnData["transaction_id"] 	= $this->ipnData["txn_id"];
			$this->ipnData["payer_email"] 		= $this->ipnData["payer_email"];
			$this->ipnData["payer_name"] 		= $this->ipnData["first_name"]." ".$this->ipnData["last_name"];


			
			if($this->ipnData["txn_type"]=='subscr_payment' || $this->ipnData["txn_type"]=='subscr_signup')
				$this->is_recurring ='yes';
			
			//Setting order and invoice
			$this->order_id = $this->ipnData["custom"];
			$this->invoice_id = $this->ipnData["invoice"];
			
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

        // Log the response from the paypal server
        $text .= "\nIPN Response from gateway Server:\n " . $this->ipnResponse;

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
			case "Canceled_Reversal":
			case "Refunded":
			case "Reversed":
			case "1":
			{
				$this->status = 'cancelled';
				$this->gatewayStatus = $trans_status;
			}
			break;
			
			case "Completed":
			case "2":
			{
				$this->status = 'ok';
				$this->gatewayStatus = 'Completed';
				return true;
			}
			break;
			
			case "Denied":
			case "Failed":
			case "Expired":
			case "3":
			case "4":
			case "5":
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