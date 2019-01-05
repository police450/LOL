<?php

/**
 * This class is used to operate first data
 * NOTE : First Data Gateway feature is not to added in this plugin Yet ! 
 * IPN
 */
 
class firstdata
{
    var $gatewayUrl = '';
    var $ipnLogFile = '';
    var $ipnData = array();
    var $ipnResponse = '';
    var $lastError = '';
    var $logIpn = '';
    
    var $invoice_id = "";
    var $order_id = "";
    
    var $gateway = 'firstdata';
    var $is_recurring = false;
    
    /**
     * Initialize the firstdata gateway
     *
     * @param none
     * @return void
     */
     



    function __construct()
    {
        // Some default values of the class
        $this->gatewayUrl = 'https://test.ipgonline.com/connect/gateway/processing';
        $this->ipnLogFile = PAID_SUBS_DIR.'/ipn/logs/firstdata.ipn_results.log';
        
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
        $this->gatewayUrl = 'https://sandbox/url/yet/to/define';
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
        define("API_LOGIN",$paidSub->configs['fd_api_login']);
        define("API_KEY", $paidSub->configs['fd_api_key']);
        define("TRAN_PURCHASE",true);


       // if 

        
        

        
    }





   // $dateTime = date("Y:m:d-H:i:s");
                function getDateTime() {
                global $dateTime;
                return $dateTime;
                }
    function createHash($chargetotal, $currency) {
        $storename = "1100000001";

        $sharedSecret = "Test123";

        $stringToHash = $storename . getDateTime() . $chargetotal . $currency .$sharedSecret;
        
        $ascii = bin2hex($stringToHash);
        return sha1($ascii);
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