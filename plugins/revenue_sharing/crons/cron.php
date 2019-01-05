<?php
 
 /**
 * This file is used to make daily earnings for revenue sharing will work with daily cron
 * @author  : Awais Fiaz
 */

require_once realpath(__DIR__ . '/../../../includes/config.inc.php'); 
if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');

try {

	$earning_users=$revshare->get_earning_users('active');

	if(empty($earning_users)){
		exit("No earning user found!");
	}

	$e_users=array();

	foreach ($earning_users as $key => $value) {
		$e_users[]=$value['userid'];
	}

	$eu_views=array();

	foreach ($e_users as $index => $userid) {
		$eu_views[$userid] = $revshare->countviewsuserid($userid);
	}

	// pr("Number of earning users : ".count($eu_views),true);
	// pr($eu_views,true);
	$total_revenue=array();

	foreach ($eu_views as $userid => $totalviews) {

		// Display
		pr("*****************************************************************\r\n*****************************".get_username($userid)."*******************************\r\n*****************************************************************",true);
		// Display

		
		pr("Total views with userid ".$userid." = ".$totalviews['total'],true);


			foreach ($totalviews as $c_code => $no_of_views) {
				# code...
				if($c_code!='total'){
					
					// pr($c_code." = ".$no_of_views,true);
					$revenue=$revshare->make_earnings_accordingto_counrty($userid,$c_code,$no_of_views);
					$total_revenue[$userid][]=$revenue;


				}
				$total_revenue[$userid]['earning_views']=$no_of_views;
			}
		}
	
	pr($total_revenue,true);
	
	foreach ($total_revenue as $uid => $rev) {
		# code...
			$eu_counted_views=$rev['earning_views'];
			unset($rev['earning_views']);
			$t_earnings= array_sum($rev);
			$response=$revshare->add_eu_views_earnings($uid,$t_earnings,$eu_counted_views);
			pr($response,true);
	}

} catch (Exception $e) {
	echo $e->getMessage();
}



?>