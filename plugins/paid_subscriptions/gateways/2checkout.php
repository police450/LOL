<?php

/**
 * This class is used to operate 2Checkout
 * IPN
 */
 
class toCheckout 
{
	var $gatewayUrl = 'https://www.2Checkout.com/checkout/purchase';
	var $ipnLogFile = '';
	var $ipnData = array();
	var $ipnResponse = '';
	var $lastError = '';
	var $logIpn = '';
	
	/**
	 * Initialize the Paypal gateway
	 *
	 * @param none
	 * @return void
	 */
	 
	function __construct()
	{
        // Some default values of the class
		$this->gatewayUrl = 'https://www.2Checkout.com/checkout/purchase';
		$this->ipnLogFile = TEMP_DIR.'/2co.ipn_results.log';
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
    }

    /**
	 * Validate the IPN notification
	 *
	 * @param none
	 * @return boolean
	 */
	function validateIpn()
	{
		$ipnData = $_POST;
		
		if($ipnData['invoice_id'] && $ipnData['vendor_order_id'])
		{
			
			$this->ipnData = $ipnData;
			return true;	
		}
		
		return false;		
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
        $fp = fopen($this->ipnLogFile,'a');
        fwrite($fp, $text . "\n\n");
        fclose($fp);
    }

}

?>