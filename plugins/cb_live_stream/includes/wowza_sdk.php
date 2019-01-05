<?php


/**
* @todo    : This class is used to hanlde all the events related wowza stremaing engine
* @param   : { cb configs }  
* @since   : { 15th September 2017 } 
* @example : { $wowza = new CB_wowza } 
* @return  : {} 
* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
*/

class CB_wowza 
{
    var $api_basepath = "";
    var $server_name = "";
    var $virtual_host = "";
    var $api_v = "";
    var $current_request = "";
    var $player_ip = "";
    var $player_port = "";
    var $instance_name = "";
    var $request_names = array(
		"create_application"=>"/applications",
		"get_application"=>"/applications/{appName}",
		"get_applications"=>"/applications",
		"remove_application"=>"/applications/{appName}",
		"get_stream" => "/applications/{appName}/instances/{instanceName}/incomingstreams/{streamName}",
		"start_recording" => "/applications/{appName}/instances/{instanceName}/streamrecorders/{recorderName}",
		"stop_recording" => "/applications/{appName}/instances/{instanceName}/streamrecorders/{recorderName}/actions/stopRecording",

	);


    
    /**
	* @todo    : This is a constructor of class , does nothing much ;)
	* @param   : { Array } { can be any thing }  
	* @since   : { 15th September 2017 } 
	* @example : { __construct($params) } {This will validate the subscriber } 
	* @return  : { Boolean } {True/False}
	* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/
    public function __construct(){
        
        global $row;
       
        $this->api_basepath = $row["wowza_api_basepath"];
        $this->server_name = $row["wowza_server_name"];
        $this->virtual_host = $row["wowza_server_vhost"];
        $this->api_v = $row["wowza_api_version"];
        $this->player_ip = $row["wowza_player_ip"];
        $this->player_port = $row["wowza_player_port"];
        $this->connect_api = $row["wowza_connect_api"];
        $this->instance_name = "_definst_";
        
    }
   	
   	/**
		* @todo    : This function is used to creat channel/application 
		* @param   : { Array } { app_name,server_name,description }  
		* @since   : { 15th September 2017 } 
		* @example : { create_application($params) } {This will validate the subscriber } 
		* @return  : { Boolean } {True/False}
		* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/
    public function create_application($params){
    	
    	$create_params = $params;

    	if (!$params["channel_name"]){
    		throw new Exception("Please Provide Channel Name");
    	}

    	$create_params["request_type"] = "POST";
    	$create_params["request_name"] = "create_application";

    	$secuirity_config = array(
    						"clientStreamWriteAccess"=>"*",
    						"publishBlockDuplicateStreamNames"=>true,
    						"publishRequirePassword"=>true,
    						"PublishAuthenticationMethod"=>'digest'
    					);
    	//Setting up all the params for a request 
    	$create_params["request_params"] = array(
											"name"=>$params["channel_name"],
											"description"=>$params["description"],
											"appType"=>"Live",
											"clientStreamWriteAccess"=>"*",
											"securityConfig"=>$secuirity_config,
											"httpCORSHeadersEnabled"=>true
										);
		
        $response = $this->request_wowza($create_params);
        return $response;
    }

    /**
	* @todo    : This function is used to get wowza app information
	* @param   : { Var } { app name }  
	* @since   : { 15th September 2017 } 
	* @example : { wowza_app($app_name) } {This will return all the info } 
	* @return  : { Boolean } {True/False}
	* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/
    public function app($app_name){
    	
    	if (!$app_name){
    		throw new Exception("Please Provide Channel Name");
    	}

    	$create_params["request_type"] = "GET";
    	$create_params["request_name"] = "get_application";
    	$create_params["app_name"] = $app_name;

        $response = $this->request_wowza($create_params);
        return $response;
   
    }

    /**
	* @todo    : This function is used to get wowza all applications information
	* @param   : { Var } { app name }  
	* @since   : { 15th September 2017 } 
	* @example : { wowza_app($app_name) } {This will return all the info } 
	* @return  : { Boolean } {True/False}
	* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/
    public function applications(){

    	$app_params["request_type"] = "GET";
    	$app_params["request_name"] = "get_applications";

        $response = $this->request_wowza($app_params);
        return $response;
   
    }

    /**
	* @todo    : This function is used to start recording for current stream
	* @param   : { Array } { app_name,recorder_name,instance_name .. }  
	* @since   : { 15th September 2017 } 
	* @example : { start_recording($params) } {This will start recording for streaming } 
	* @return  : { Boolean } {True/False}
	* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/
    public function start_recording($params){
    	
    	if (!$params['app_name']){
    		throw new Exception("Please Provide Channel Name");
    	}

    	$record_params["request_type"] = "POST";
    	$record_params["request_name"] = "start_recording";
    	$record_params["app_name"] = $app_name;
    	
    	if (!$params["app_name"]){
    		throw new Exception("please provide app_name to start recording");
    	}
    	if (!$params["stream_name"]){
    		throw new Exception("please provide stream_name to start recording ");
    	}
    	if (!$params["recorder_name"]){
    		throw new Exception("please provide recorder_name to start recording");
    	}
    	
    	$record_params["app_name"] = $params['app_name'];
    	$record_params["instance_name"] = $this->instance_name;
    	$record_params["recorder_name"] = $params["recorder_name"];
    	//Setting up all the params for a request 
    	$record_params["request_params"] = array(
											"fileFormat"=>"mp4",
											"appType"=>"live",
											"streamName"=>$params["stream_name"],
											"recorderName"=>$params["recorder_name"],
											"segmentationType"=>"None",
											"moveFirstVideoFrameToZero"=>"true",
											"baseFile"=>$params["stream_name"].".mp4",
											"outputPath"=>$this->wowza_file_directory,
											"fileFormat"=>"MP4",
											"_recordData"=>"on",
											"startOnKeyFrame"=>"true",
											"_startOnKeyFrame"=>"on",
											"segmentationOption"=>"None",
											"durationHours"=>"0",
											"durationMinutes"=>"15",
											"durationSeconds"=>"0",
											"segmentSize"=>"10",
											"scheduleOption"=>"Every hour on the hour",
											"scheduleString"=>"0 * * * * *",
											"option"=>"Overwrite existing file"
										);

        $response = $this->request_wowza($record_params);
        return $response;
   
    }

    /**
	* @todo    : This function is used to start recording for current stream
	* @param   : { Array } { app_name,recorder_name,instance_name .. }  
	* @since   : { 15th September 2017 } 
	* @example : { start_recording($params) } {This will start recording for streaming } 
	* @return  : { Boolean } {True/False}
	* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/
    public function stop_recording($params){
    	
    	if (!$params['app_name']){
    		throw new Exception("Please Provide Channel Name");
    	}

    	$record_params["request_type"] = "PUT";
    	$record_params["request_name"] = "stop_recording";
    	
    	
    	if (!$params["app_name"]){
    		throw new Exception("please provide app_name to stop recording");
    	}

    	if (!$params["recorder_name"]){
    		throw new Exception("please provide recorder_name to stop recording ");
    	}
    	
    	$record_params["app_name"] = $params['app_name'];
    	$record_params["instance_name"] = $this->instance_name;
    	$record_params["recorder_name"] = $params["recorder_name"];
    	
    	//Setting up all the params for a request 
    	$record_params["request_params"] = array();

        $response = $this->request_wowza($record_params);
        return $response;
   
    }

    /**
	* @todo    : This function is used to get wowza app information
	* @param   : { Var } { app name }  
	* @since   : { 15th September 2017 } 
	* @example : { wowza_app($app_name) } {This will return all the info } 
	* @return  : { Boolean } {True/False}
	* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/
    public function stream($app_name,$stream_name){
    	
    	if (!$app_name){
    		throw new Exception("Please Provide Channel Name");
    	}

    	$create_params["request_type"] = "GET";
    	$create_params["request_name"] = "get_stream";
    	$create_params["app_name"] = $app_name;
    	$create_params["stream_name"] = $stream_name;
    	$create_params["instance_name"] = $this->instance_name;

        $response = $this->request_wowza($create_params);
        return $response;
   
    }


    /**
		* @todo    : This function is used to creat channel/application 
		* @param   : { Array } { app_name,server_name,description }  
		* @since   : { 15th September 2017 } 
		* @example : { create_application($params) } {This will validate the subscriber } 
		* @return  : { Boolean } {True/False}
		* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/
    public function remove_application($app_name){
    	
    	if (!$app_name){
    		throw new Exception("Please Provide Channel Name");
    	}

    	$create_params["request_type"] = "DELETE";
    	$create_params["request_name"] = "remove_application";
    	$create_params["app_name"] = $app_name;

        $response = $this->request_wowza($create_params);
        return $response;
    }


    /**
	* @todo    : This function is used to connect wowza streamin server
	* @param   : { Array } { can be any thing }  
	* @since   : { 15th September 2017 } 
	* @example : { verify_digi_subscriber($params) } {This will validate the subscriber } 
	* @return  : { Boolean } {True/False}
	* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/

	function request_wowza($params){

	    //Checking for a valid request
	    if (!array_key_exists($params['request_name'], $this->request_names)){
	    	throw new Exception("Bad Request ! No such request exists ! ");
	    }

	    $this->current_request = $params['request_name'];

	    $target_url  = $this->api_basepath.'/'.$this->api_v;
	    $target_url .= '/servers/'.$this->server_name;
	    $target_url .= '/vhosts/'.$this->virtual_host;
	    
	    $request_url.= $this->request_names[$this->current_request];
	    $target_url .= $request_url;

	    

	    //building up dynamic urls 
	    if ($params['app_name']){
			$target_url = str_replace('{appName}',urlencode($params['app_name']),$target_url);
		}
		if ($params['stream_name']){
			$target_url = str_replace('{streamName}',urlencode($params['stream_name']),$target_url);
		}
		if ($params['instance_name']){
			$target_url = str_replace('{instanceName}',urlencode($params['instance_name']),$target_url);
		}
		if ($params['recorder_name']){
			$target_url = str_replace('{recorderName}',urlencode($params['recorder_name']),$target_url);
		}
		/*if($params['request_name'] == 'stop_recording'){
	    	pex($target_url,true);
	    }*/
	    
		//headers
	    $headers = array(
	        "Content-Type: application/json"
	    );

	    //initializing connecttion with wowza
	    $ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL,$target_url); 
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	    curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
	    
	    if ($params["request_type"] == "POST"){
	    	if (empty( $params["request_params"] )){
	    		throw new Exception("you cannot post a request with empty body");
	    	}
	    	curl_setopt($ch, CURLOPT_POST, true); 
	    	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["request_params"])); 
	    
	    }elseif($params["request_type"] == "DELETE"){
	    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		}elseif($params["request_type"] == "PUT"){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	    }else{
	    	curl_setopt($ch, CURLOPT_POST, false);
	    }
	     
	    $data = curl_exec($ch); 

	    $returnCode = curl_getinfo($ch); 	
	    if (curl_errno($ch)) { 
	        throw new Exception(curl_error($ch)); 
	    }
	    curl_close($ch); 
	    //wowza connection closed
		
		if ($data){
	    	$response = $this->xml_to_array($data);
	    }
	    
		$httpCode = $returnCode["http_code"];
	    if ($httpCode != '200' && $httpCode != '201'){
	    	throw new Exception("Error Code (".$httpCode.") : ".$response["message"]);
	    }

	    return $response;
	    
	}

	/**
		* @todo    : This function is used to convert xml into array
		* @param   : { Var } { xml }  
		* @since   : { 21th September 2017 } 
		* @example : { xml_to_array($xml) } {This will return array of xml } 
		* @return  : { Boolean } {True/False}
		* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/
	function xml_to_array($xml){
		if (empty($xml)){
			throw new Exception("Provied XML input is empty ! ");
		}

		$xml = simplexml_load_string($xml);
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);
		return $array;
	}

	 /**
	* @todo    : This function will build stream url for the current stream 
	* @param   : { Var } { app_name, stream_name, type }  
	* @since   : { 04th December 2017 } 
	* @example : { build_upstream_url($app_name,$channel_name,$type } 
	*            {This will return url depends upon url } 
	* @return  : { URL } {URL/ False}
	* @author  : <fahad.dev@iu.com.pk> <Fahad Abbas>
	*/
    public function build_upstream_url($app_name,$stream_name,$type){
    	
    	if (!$app_name || !$stream_name || !$type){
    		throw new Exception("Please Provide app_name,stream_name,type to build player url");
    	}

    	switch ($type) {
    		default:
    		case 'flash':{
    			$url = "rtmp://".$this->player_ip.":".$this->player_port."/".$app_name."/".$stream_name;
    		}
    		break;

    		case 'dash':{
    			$url = "http://".$this->player_ip.":".$this->player_port."/".$app_name."/".$stream_name."/manifest.mpd";
    		}
    		break;

			case 'hls':{
				$url = "http://".$this->player_ip.":".$this->player_port."/".$app_name."/".$stream_name."/playlist.m3u8";
    		}
    		break;
    		
    	}
        return $url;   
    }

	

}