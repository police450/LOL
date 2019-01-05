<?php 
/**
* File: Functions
* Description: Various kind of functions to do CB Ultimate Ads Plugin
* @license: Attribution Assurance License
* @since: ClipBucket 1.0
* @author[s]: Fahad Abbas
* @copyright: (c) 2008 - 2016 ClipBucket / PHPBucket
* @notice: Please maintain this section
* @modified: April 23, 2016 ClipBucket 2.8.1
*/
	
	/**
	* Converts the Unix TimeStamp in php date Object
	* @param   : { unix timestamp } {date only}{ time Stamp which saves the time in seconds }
	* @example : unix_to_date(7234546746,false) // will return only date in this format `2016-04-28 01:00:00` 
	* @return  : { phpdateobject / Date } {  }
	* @since   : 23rd April, 2016 ClipBucket 2.8.1
	* @author  : Fahad Abbas
	*/
	function unix_to_date($timestamp,$date_only=false){
		$date = date("m/d/Y g:i a", $timestamp);
		if (!$date_only){
			$DateObject = new DateTime($date);
			return $DateObject;
		}
		return $date;
	}

	/**
	* Converts the Unix TimeStamp in php date Object and then 
	* @param   : { unix timestamp } { time Stamp which saves the time in seconds }
	* @example : seperate_datetime(array("date")) // will return date index only of $date_time Array
	* @return  : { Date / time } { Array }
	* @since   : 23rd April, 2016 ClipBucket 2.8.1
	* @uses    : unix_to_date()
	* @author  : Fahad Abbas
	*/
	function seperate_datetime($params){

		$timestamp = $params['timestamp'];
		$datetime = unix_to_date($timestamp,true);
		$datetime = explode(' ', $datetime);
		$date_time = array("date"=>$datetime[0],"time"=>$datetime[1]);

		if ($params['date']){
			$return = $date_time['date'];
		}else if($params['time']){
			$return = $date_time['time'];
		}else if($params['date_time']){
			$return = $date_time;
		}else{
			$return = false;
		}
		return $return;
	}



?>