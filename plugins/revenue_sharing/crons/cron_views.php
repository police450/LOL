<?php
 
 /**
 * This file is used aggrigate mongo views and store them into sql every 5 minute will work with cron
 * @author  : Awais Fiaz
 */

require_once realpath(__DIR__ . '/../../../includes/config.inc.php'); 
if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');


try{
	
	$aggregated_views = $revshare->aggregate_views();	
	
	if($aggregated_views!=0){

		$update_response = $revshare->update_mongo_sql_views($aggregated_views);
		pr($update_response,true);

	}else{

		pr("No views found to aggregate and save into sql!",true);

	}



} catch (Exception $e) {
	
	echo $e->getMessage();
}




?>