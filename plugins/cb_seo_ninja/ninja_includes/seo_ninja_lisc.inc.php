<?php

//Function used to check CB brand removal license
    function check_seo_ninja_lisc($license,$localkey)
    {
        $results = seo_ninja_lisc_look($license,$localkey);
        $error_setting_link = '<a href="'.BASEURL.'/admin_area/plugin.php?folder='.SEO_NINJA.'/admin&file=lisc_configs.php">Click Here to edit SEO Ninja Settings</a>';
        if(!$results)
        {
            if(BACK_END)
            e("Error while loading SEO Ninja Settings license - $error_setting_link","w");
        }elseif ($results["status"]=="Invalid")
        {
            if(BACK_END)
            e("Your SEO Ninja Settings License is Invalid - $error_setting_link","w");
        }elseif ($results["status"]=="Expired")
        {
            if(BACK_END)
            e("Your SEO Ninja Settings License is Expired - $error_setting_link","w");
        }elseif($results["status"]=="Suspended")
        {
            if(BACK_END)
            e("Your SEO Ninja Settings is suspended - $error_setting_link","w");
        }elseif($results['status']!='Active')
        {
            if(BACK_END)
            e("Error occured while checking license , status : ".$results['status']." - $error_setting_link","w");
        }
        return $results;
    }
    
    
    //Function used to check CB brand removal license
    function seo_ninja_lisc_look($licensekey,$localkey="")
    {
        //return array('status'=>'Active');
        $whmcsurl = "http://client.clip-bucket.com/";
        $prefix = "SEON";
        $licensing_secret_key = "SEON"; # Set to unique value of chars
        $checkdate = date("Ymd"); # Current dateW
        $usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
        $localkeydays = 5; # How long the local key is valid for in between remote checks
        $allowcheckfaildays = 2; # How many days to allow after local key expiry before blocking access if connection cannot be made
        $localkeyvalid = false;
        
        $prefix_len = strlen($prefix);
        
        if(substr($licensekey,0,$prefix_len)!=$prefix)
        {
            #exit("NOW");
            return array('status'=>'Unknown license');
        }
        if ($localkey) {
            $localkey = str_replace("\n",'',$localkey); # Remove the line breaks
            $localdata = substr($localkey,0,strlen($localkey)-32); # Extract License Data
            $md5hash = substr($localkey,strlen($localkey)-32); # Extract MD5 Hash
            if ($md5hash==md5($localdata.$licensing_secret_key)) {
                #exit("aaaa");
                $localdata = strrev($localdata); # Reverse the string
                $md5hash = substr($localdata,0,32); # Extract MD5 Hash
                $localdata = substr($localdata,32); # Extract License Data
                $localdata = base64_decode($localdata);
                $localkeyresults = unserialize($localdata);
                $originalcheckdate = $localkeyresults["checkdate"];
                if ($md5hash==md5($originalcheckdate.$licensing_secret_key)) {
                   # exit("bbbb");
                    $localexpiry = date("Ymd",mktime(0,0,0,date("m"),date("d")-$localkeydays,date("Y")));
                    if ($originalcheckdate>$localexpiry) {
                        $localkeyvalid = true;
                        $results = $localkeyresults;
                        $validdomains = explode(",",$results["validdomain"]);
                        if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                            $localkeyvalid = false;
                            $localkeyresults["status"] = "Invalid";
                            $results = array();
                        }
                        $validips = explode(",",$results["validip"]);
                        if (!in_array($usersip, $validips)) {
                            $localkeyvalid = false;
                            $localkeyresults["status"] = "Invalid";
                            $results = array();
                        }
                        if ($results["validdirectory"]!=dirname(__FILE__)) {
                            $localkeyvalid = false;
                            $localkeyresults["status"] = "Invalid";
                            $results = array();
                        }
                    }
                }
            }
        }
        if (!$localkeyvalid) {
            #exit("aadddddddd");
            $postfields["licensekey"] = $licensekey;
            $postfields["domain"] = $_SERVER['SERVER_NAME'];
            $postfields["ip"] = $usersip;
            $postfields["dir"] = dirname(__FILE__);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $whmcsurl."modules/servers/licensing/verify.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
            $data = curl_exec($ch);
            curl_close($ch);
            if (!$data) {
                $localexpiry = date("Ymd",mktime(0,0,0,date("m"),date("d")-($localkeydays+$allowcheckfaildays),date("Y")));
                if ($originalcheckdate>$localexpiry) {
                    $results = $localkeyresults;
                } else {
                    $results["status"] = "Remote Check Failed";
                    return $results;
                }
            } else {
                #exit("avadNOW");
                preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
                $results = array();
                foreach ($matches[1] AS $k=>$v) {
                    $results[$v] = $matches[2][$k];
                }
            }
            if ($results["status"]=="Active") {
                $results["checkdate"] = $checkdate;
                $data_encoded = serialize($results);
                $data_encoded = base64_encode($data_encoded);
                $data_encoded = md5($checkdate.$licensing_secret_key).$data_encoded;
                $data_encoded = strrev($data_encoded);
                $data_encoded = $data_encoded.md5($data_encoded.$licensing_secret_key);
                $data_encoded = wordwrap($data_encoded,80,"\n",true);
                $results["localkey"] = $data_encoded;
                global $db;
                $db->update(tbl("socialconn_lisc_configs"),array("value"),array($results["localkey"]),"config_id=2") ;
            }
            $results["remotecheck"] = true;
        }
        unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$localkeydays,$allowcheckfaildays,$md5hash);
        
        return $results;
    }



    function get_ninja_lisc_configs()
    {
      global $db;
      $results = $db->select(tbl("seo_ninja_lisc_configs"),"*");
      $configs  = array();
      foreach($results as $result)
      {
        $configs[$result['name']]= $result['name'] = $result['value'];
      }

      return $configs;
    }

    /**
    * Inteligently encodes last checked data
    * @param : { string } { $status } { Current status of plugin }
    * @since : 24th October, 2016 ClipBucket 2.8.1
    * @author : Saqib Razzaq
    */
    
    function messUpLastChecked_ninja($status) {
        $dateStamp = dateStamp();
        $alphabets_swaped = swapedAlphabets();

        $status_clean = strtolower($status);
        $status_array = str_split($status_clean);
        $status_numeric = '';

        $num_array = str_split($dateStamp);
        $mixedTimeArray = '';

        foreach ($status_array as $key => $char) {
            $newNum = $alphabets_swaped[$char] + 1;
            $status_numeric .= "__".$newNum;
        }

        foreach ($num_array as $intKey => $numNow) {
            $mixedTimeArray .= $numNow.''.charsRandomStr();
        }

        $toReturn = array();
        $toReturn['status'] = $status_numeric;
        $toReturn['lastChecked'] = $mixedTimeArray;

        return $toReturn;
    }

     /**
    * Inteligently decodes last checked data fetched by above function
    * @param : { string } { $status } { Current status of plugin }
    * @param : { string } { $status } { Last checked encoded string }
    * @since : 24th October, 2016 ClipBucket 2.8.1
    * @author : Saqib Razzaq
    */

    function cleanUpLastChecked_ninja($status, $lastChecked) {
        $alphabets = range('a', 'z');
        $statusArray = explode('__', $status);
        $statusArray = array_filter($statusArray);
        $statusCleaned = '';
        $lastCheckedCleaned = '';
        foreach ($statusArray as $key => $charNow) {
            $charNow = $charNow - 1;
            $statusCleaned .= $alphabets[$charNow];
        }
        
        $lastCheckedCleaned = preg_replace("/[^0-9,.]/", "", $lastChecked);

        $toReturn = array();
        $toReturn['status]'] = $statusCleaned;
        $toReturn['date'] = date('m/d/Y', $lastCheckedCleaned);
        $toReturn['lastCheckedStamp'] = $lastCheckedCleaned;

        return $toReturn;
    }

    /**
    * Runs a lisc check only if last check was 7 or more days ago
    * @param : { string } { $status } { Current status of plugin }
    * @param : { string } { $status } { Last checked encoded string }
    * @since : 24th October, 2016 ClipBucket 2.8.1
    * @author : Saqib Razzaq
    */

    function liscCheckLatest_ninja() {

        $config = get_ninja_lisc_configs();
        $lisc_key = $config['license_key'];
        $local_key = $config['license_local_key'];
        // IP of last success (acitve status) check
        $success_ip = $config['success_ip'];

        // current IP address of server
        $current_ip = $_SERVER['SERVER_ADDR'];

        if ((int) trim($success_ip) == (int) trim($current_ip)) {
           return array('status'=>'Active');
        } else {
            $result = check_seo_ninja_lisc($lisc_key,$local_key);
            if ($result["status"] == 'Active') {
                global $db;
                $db->update(tbl('seo_ninja_lisc_configs'),array('value'),array($current_ip),"name ='success_ip'");
                return $result;
            }
        }
    }

  fire_ninja(true);
   


?>