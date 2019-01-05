<?php

/**
 * This class will operate
 * all the functions and queries\
 * that are required to make a possible
 * solution for paid subscription for ClipBucket
 *
 * @Author : Arslan Hassan
 * @Built-for : ClipBUcket 2.x.x
 * @License : Commercial [EULA]
 *
 * Code Model : camelCase
 */
 

class paidSubscription
{
	var $userSubs = array(); 
	var $allowedCurrency = array("USD"=>"$","EUR"=>"€","GBP"=>"£");
	var $currency = "USD";
	var $test_mode = 'disabled';
	var $paypal_email = 'anything@something.com';
	var $paypal_sandbox_email = 'anything@something.com';
	
	var $demoDetails = array();
	
	var $configs;
	
	function paidSubscription()
	{
		
		$this->getConfigs();
	}
	function getConfigs()
	{
		global $db;
		$results = $db->select(tbl("paid_configs"),"*");
		foreach($results as $result)
		{
			$this->configs[$result['name']]= $this->$result['name'] = $result['value'];
			if($result['name']=='premium_videos')
			{
				$this->configs['premium_vids'] = (explode(","
				,preg_replace(array("/([0-9]+)/","/\r\n/"),array("|$1|",","),$result['value'])));
			}
		}
		
		return $this->configs;
	}
	
	/**
	 * Function used to make video premium
	 */
	function make_premium_action($type,$vid)
	{
		global $db;
		switch($type)
		{
			case "make":
			{
				if(!in_array("|".$vid."|",$this->configs['premium_vids']))
				{
					$db->update(tbl("video"),array("is_premium"),array($vids)," name='premium_videos' ");
					e("Video has been set as premium","m");
				}
				else
					e("Video is already premium");
			}
			break;
			
			case "unmake":
			{
				
				if(!in_array("|".$vid."|",$this->configs['premium_vids']))
					e("Video is not premium");
				else
				{
					$vids = $this->configs['premium_videos'];
					$vids = preg_replace("/^".$vid."$/m","",$vids);
					$vids = preg_replace("/\r\n\r\n/","\r\n",$vids);
					$vids = preg_replace("/\r\n\r\n/","\r\n",$vids);
					$db->update(tbl("paid_configs"),array("value"),array($vids)," name='premium_videos' ");
					e("Video has been removed from premium","m");
				}
			}
		}
	}

	/**
	 * Function used to get user subscriptions details
	 * @param : userid
	 * @return 
	 */
	function getUserSubscription($uid)
	{
		global $db;
		$results = $db->select(tbl("paid_packages,paid_subscriptions"),"*","userid='$uid'
		AND ".tbl('paid_packages.package_id')." = ".tbl('paid_subscriptions.package_id')."
		AND ".tbl('paid_subscriptions.active')."='yes'");
		if($results)
		{
			foreach($results as $subs)
			{
				if($this->validPeriod($subs))
				{
					$userSubs[] = $subs;
				}				
			}
			
			return $this->userSubs = $userSubs;
		}else
			return false;
	}
	
	
	/**
	 * Function used to dsecative subscrioption automatically
	 */
	function validPeriod($sub)
	{
		//$start = strtotime($sub['subscription_start_date']);
		$end = strtotime($sub['end_date']);
		$current = time();
		if($current>$end && $sub['end_date'])
		{
			$this->subscriptionAction('deactivate',$sub['subscription_id']);
			return false;
		}
		return true;
	}
	
	
	/**
	 * Function used to add time that user is going to watch
	 * 
	 * @param $sub_id
	 * @param $duration
	 * @return true
	 */
	function addTimeWatched($subId,$duration)
	{
		global $db;
		$db->update(tbl("paid_subscriptions"),
		array
		("subscription_total_watched_time"),
		array
		("|f|subscription_total_watched_time+".$duration), "subscription_id = '".$subId."' ");
		return true;
	}
	
	
	
	
	
	/**
	 * Function used to edit existing package
	 *
	 * @param Package_opts_array
	 * @return Boolean
	 */
	function addNewPackage($array)
	{
		global $db;
		$type 	= $array['type'];
		$price 	= $array['price'];
		$title 	= mysql_clean($array['title']);
		$desc 	= $array['desc'];
		
		$vids	= $array['vids'];
		$ppv 	= $array['ppv'];
		$mins 	= $array['mins'];
		$credits = $array['credits'];
		$days	= $array['days'];
		
		$active = $array['active'];
		$is_collection = $array['is_collection'];
		
		if(!$title)
			e("Please set some unique title for your package");
		if(!$desc)
			e("Please enter description of your package");
		if(!$price)
			e("Giving away packages for free is not allowed, please set some price");
			
		if($ppv && ($ppv < -1 || !is_numeric($ppv)) )
		{
			e("Invalid value set for PPV, set 0 to disable, -1 to unlimit and enter digital value for a specific limit");
		}
		
		if($type=='ppv' && (!$credits || !is_numeric($credits) || $credits < 1))
			e("Please set credits for this PPV package greater than '1'");
		
		if($type=='mins' && (!$mins || !is_numeric($mins) || $mins < 1))
			e("Please set minutes for this package greater than  '1'");
		
		if($vids && (!is_numeric($vids) || $vids < 0))
			e("Please set limit videos values to 0 to make it unlimit or enter digital value for specifc limit");
		
		if($days && (!is_numeric($days) || $days < 0))
			e("Please set days values to 0 to make it unlimit or enter digital value for specifc limit");

		if($type!='ppv' && $type!='mins' && $type!='subs' && $type!='vids')
			e("Please select package type");
		
		
		if(!error())
		{
			$db->insert(tbl('paid_packages'),
			array('pkg_title','pkg_desc','pkg_type',
			'pkg_days','pkg_vids','pkg_mins','pkg_price',
			'pkg_credits','pkg_ppv','is_collection',
			'active','date_added'),
			array($title,$desc,$type,$days,$vids,$mins,$price,$credits,$ppv,$is_collection,$active,now()));
			
			e("New package has been created","m");
			
		}else
			return false;
		
	}
	
	
	/**
	 * Function used to add new package
	 *
	 * @param Package_opts_array
	 * @return Boolean
	 */
	function editPackage($array)
	{
				
		global $db;
		$type 	= $array['type'];
		$price 	= $array['price'];
		$title 	= $array['title'];
		$desc 	= $array['desc'];
		
		$vids	= $array['vids'];
		$ppv 	= $array['ppv'];
		$mins 	= $array['mins'];
		$credits = $array['credits'];
		$days	= $array['days'];
		
		$active = $array['active'];
		$is_collection = $array['is_collection'];
		
		$id = $array['pkgid'];
		
		if(!$this->packageExists($id))
			e("Package does not exist");
		if(!$title)
			e("Please set some unique title for your package");
		if(!$desc)
			e("Please enter description of your package");
		if(!$price)
			e("Giving away packages for free is not allowed, please set some price");
			
		if($ppv && ($ppv < -1 || !is_numeric($ppv)) )
		{
			e("Invalid value set for PPV, set 0 to disable, -1 to unlimit and enter digital value for a specific limit");
		}
		
		if($type=='ppv' && (!$credits || !is_numeric($credits) || $credits < 1))
			e("Please set credits for this PPV package greater than '1'");
		
		if($type=='mins' && (!$mins || !is_numeric($mins) || $mins < 1))
			e("Please set minutes for this package greater than  '1'");
		
		if($vids && (!is_numeric($vids) || $vids < 0))
			e("Please set limit videos values to 0 to make it unlimit or enter digital value for specifc limit");
		
		if($days && (!is_numeric($days) || $days < 0))
			e("Please set days values to 0 to make it unlimit or enter digital value for specifc limit");

		if($type!='ppv' && $type!='mins' && $type!='subs' && $type!='vids')
			e("Please select package type");
		
		
		if(!error())
		{
			$db->update(tbl('paid_packages'),
			array('pkg_title','pkg_desc','pkg_type',
			'pkg_days','pkg_vids','pkg_mins','pkg_price',
			'pkg_credits','pkg_ppv','is_collection',
			'active'),
			array($title,$desc,$type,$days,$vids,$mins,$price,$credits,$ppv,$is_collection,$active),
			"package_id='$id'");
			
			e("Package has been updated","m");
			
		}else
			return false;
	}
	
	
	
	/**
	 * Function used to get all subscription packages
	 *
	 * @param NULL
	 * @return Array of packages
	 */
	function getPackages($params=NULL)
	{
		global $db;
		
		if(!$params)
			$params=array("order_by"=>"date_added","sort"=>"DESC",'limit'=>10);
		
		$cond = array();
		extract($params);
	
		if($type)
		{
			$cond[] = "pkg_type='$type'";
		}
		
		if($active)
		{
			$cond[] = "active='$active'";
		}
		
		if($is_collection)
		{
			$cond[] = "is_collection='$is_collection'";
		}
		
		
		$condition = "";
		foreach($cond as $c)
		{
			if($condition)
				$condition .= " AND ";
			$condition .= $c;
		}
		
		if($order_by)
			$orderby = $order_by." ".$sort;
		
		$results = $db->select(tbl('paid_packages'),'*',$condition,$limit,$orderby);
		if($db->num_rows>0)
			return $results;
		else
			return false;
	}
	
	/**
	 * Function used to get subscription package
	 *
	 * @param : package id
	 * @return : packge details
	 */
	function getPackage($pid,$sid=false)
	{
		global $db;
		if(!$sid)
			$results = $db->select(tbl("paid_packages"),"*","package_id='$pid'");
		else
			$results = $db->select(tbl("paid_packages,paid_subscriptions"),"*",
			tbl("paid_subscriptions.subscription_id='$pid'")." AND ".tbl("paid_subscriptions.package_id").'='.tbl("paid_packages.package_id"));
			
		if($db->num_rows>0)
			return $results[0];
		else
			return false;
	}function getSubsPackage($pid){ return $this->getPackage($id); }
	
	/**
	 * function used to check weather package exists or not
	 * 
	 * @param PID
	 * @return Boolean
	 */
	function packageExists($id)
	{
		global $db;
		if($db->count(tbl("paid_packages"),"package_id","package_id='$id'"))
			return true;
		else
			return false;
	}
	
	
	/**
	 * Function used to count how many subscription are running under a package
	 *
	 * @param PID
	 * @return number of subscriptions
	 */
	function countPackageSubs($pid)
	{
		global $db;
		return $db->count(tbl("paid_subscriptions"),"subscription_id","package_id='$pid'");
    }
	
	/**
	 * Function used to perform some actions with packages
	 *
	 * @param : Action to do
	 * @param : Package ID
	 * @return results
	 */
	function packageAction($action,$pid)
	{
		global $db;
		if(!$this->packageExists($pid))
		{
			e("Package does not exist");
			return false;
		}
		
		switch($action)
		{
			case "activate":
			{
				$db->update(tbl("paid_packages"),array("active"),array("yes"),"package_id='$pid'");
				e("Package has been activated","m");
			}
			break;
			case "deactivate":
			{
				$db->update(tbl("paid_packages"),array("active"),array("no"),"package_id='$pid'");
				e("Package has been deactivated","m");
			}
			break;
			
			case "delete":
			{
				$numOfSubs = $this->countPackageSubs($pid);
				$_link = BASEURL."/admin_area/plugin.php?folder="._PAID_SUBS_."/admin&file=paid_packages.php";
				if($numOfSubs > 0)
				{
					if($numOfSubs=='1')
						$is_are = 'is';
					else
						$is_are = 'are';
					e("There $is_are <strong><em>$numOfSubs</em></strong> subscriptions running under this Package
					, <a href='".$_link."&force_delete=$pid'>Click Here</a> to force delete, this will also delete subscriptions");
				}else
				{
					$db->delete(tbl("paid_packages"),array("package_id"),array($pid));
					e("Package has been deleted","m");
				}
			}
			break;
			
			case "force_delete":
			{
				$db->delete(tbl("paid_packages"),array("package_id"),array($pid));
				$db->delete(tbl("paid_subscriptions"),array("package_id"),array($pid));
					e("Package & Subscriptions have been removed","m");
			}
			break;
		}
		
	}
	
	
	
	/**
	 * Function used to get list of subscriptions
	 */
	function getSubscriptions($params=array("order_by"=>"subscription_id","sort"=>"DESC"))
	{
		global $db;
		if($params['limit'])
			$limit = $params['limit'];
		else
			$limit = NULL;


		if($params['pid'])
		{
			$pid = $params['pid'];
			$cond[] = "package_id='$pid'";
		}
		
		if($params['uid'])
		{
			$uid = $params['uid'];
			$cond[] = "userid='$uid'";
		}
		
		if($params['active'])
		{
			$active = $params['active'];
			if($active!='yes' && $active!='no')
				$active = "yes";
			$cond[] = "active='$active'";
		}

		if($params['expired'])
		{	
			$cond[] = "end_date > CURDATE()";
		}
		
		
		$condition = "";
		if($cond)
		{
			$count = 0;
			foreach($cond as $c)
			{
				if($count>0)
					$condtion .= " AND ";
				$condition .= tbl("paid_subscriptions.").$c;
				
				$count++; 
			}
		}
		
		
		if($params['count_only'])
		{
			$count = $db->count(tbl("paid_subscriptions"),"subscription_id",$condition);
			if($params['assign'])
				assign($params['assign'],$count);
			else
				return $count;
		}else
		{
			//Adding Package Cond
			if($count>0)
				$condition .= " AND ";
			$condition .= tbl("paid_packages.package_id")."=".tbl("paid_subscriptions.package_id");
			
			$condition .= " AND ";
			$condition .= tbl("users.userid")."=".tbl("paid_subscriptions.userid")." ";

			if(!$params['order_by'])
			{
				$params['order_by'] = "subscription_id";
				$params['sort'] = "DESC";
			}
			
			$results =  $db->select(tbl("paid_subscriptions,paid_packages,users"),
			tbl("paid_subscriptions.*,paid_packages.pkg_title,paid_packages.pkg_price,paid_packages.pkg_type,users.username,paid_packages.pkg_ppv	,paid_packages.pkg_mins,paid_packages.pkg_vids,paid_packages.pkg_days,paid_packages.pkg_credits,paid_packages.is_collection,paid_packages.pkg_desc"),
			$condition,$limit,$params['order_by']." ".$params['sort']);
			
			if($results)
			{
				//return $results;
				if($params['assign'])
					assign($params['assign'],$results);
				else
					return $results;
			}else
			{
				if($params['assign'])
					assign($params['assign'],false);
				else
					return false;
			}
		}
	}
	
	/**
	 * function used to get order details
	 * @param : OID
	 */
	function getOrder($oid,$invoice=false,$is_subs=false)
	{
		global $db;
		
		if(!$is_subs)
		{
			if(!$invoice)
				$result = $db->select(tbl('paid_orders'),"*","order_id='$oid'");
			else
				$result = $db->select(tbl('paid_orders,paid_invoices'),"*","order_id='$oid' AND 
				".tbl('paid_orders.invoice_id')."=".tbl('paid_invoices.invoice_id'));
		}else
		{
			if(!$invoice)
				$result = $db->select(tbl('paid_orders'),"*","subscription_id='$oid'");
			else
				$result = $db->select(tbl('paid_orders,paid_invoices'),"*","subscription_id='$oid' AND 
				".tbl('paid_orders.invoice_id')."=".tbl('paid_invoices.invoice_id'));

		}

			
		if($db->num_rows>0)
			return $result[0];
		else
			return false;
	}
	
	
	/**
	 * Function used to get subscriotion order status
	 * @param : Order ID
	 * @return : either results or false
	 */
	function getSubsOrderDetails($order_id,$with_invoice=true)
	{
		return $this->getOrder($order_id,$with_invoice,true);
	}
	
	
	/**
	 * Function used to get subscriptio id from order id
	 */
	function getSidFromOid($oid)
	{
		global $db;
		$results = $db->select(tbl("paid_orders"),"subscription_id","order_id='$oid'");
		
		if($db->num_rows>0)
			return $results[0]['subscription_id'];
		else
			return false;		
	}
	
	
	/**
	 * Function used to check weather subscription exists or not
	 *
	 * @param : subscriptionID
	 * @return : boolean
	 */
	function subscriptionExists($id)
	{
		global $db;
		return $db->count(tbl("paid_subscriptions"),"subscription_id","subscription_id='$id'");
	}
	
	
	/**
	 * Function used to delete user subscription
	 */
	function deleteSubscription($sid)
	{
		global $db;
		if($this->subscriptionExists($sid))
		{
			$db->delete(tbl("paid_subscriptions"),array("subscription_id"),array($sid));
			$db->delete(tbl("paid_orders"),array("subscription_id"),array($sid));
			e("Subscript has been removed","m");
		}
		else
			e("Subscription does not exist");
	}
	
	/**
	 * Function used to perform actions with subscription
	 */
	function subscriptionAction($action,$sid)
	{
		global $db;
		if(!$this->subscriptionExists($sid))
		{
			e("Subscription does not exist");
			return false;
		}
		switch($action)
		{
			case "activate":
			{
				$db->update(tbl("paid_subscriptions"),array("active"),array("yes")," subscription_id='$sid'");
				e("Subscription has been activated","m");
			}
			break;
			
			case "deactivate":
			{
				$db->update(tbl("paid_subscriptions"),array("active"),array("no")," subscription_id='$sid'");
				e("Subscription has been deactivated","m");
			}
			break;
		}
	}
	
	/**
	 * Function used to add subscription
	 *
	 * @param : userid
	 * @param : package
	 */
	function addSubscription($uid=NULL,$pid,$params=array())
	{
		if(!$uid)
			$uid = userid();
		
		global $db;
		$pkg = $this->getPackage($pid);
		if(!$pkg)
			e("Unknown Package Selected");
		else
		{
			$videoIds = 0;
			if($params['video_ids'])
			{
				$videoIds = $params['video_ids'];
			}
			
			if($pkg['pkg_days'])
			{
				$now = now();
				$now = strtotime($now);
				$end = $now+($pkg['pkg_days']*24*60*60);
				$end = date("Y-m-d h:i:s",$end);
			}
			
			$package_quantity = $params['pkg_qty'];
			$active = $params['active'];
			$active = $active ? $active : 'yes';
			
			$db->insert(tbl("paid_subscriptions"),
			array("userid","package_id","start_date",
			"end_date","allowed_vids"
			,"active","date_added","pkg_qty"),
			array($uid,$pid,now(),$end,$videoIds,$active,now(),$package_quantity));
						
			return $db->insert_id();
		}
	}
	
	
	/** 
	 * Function used to check weather subscription exists or not
	 *
	 * @param : sid
	 * @return Boolean
	 */
	function subsExist($sid)
	{
		global $db;
		return $db->count(tbl("paid_subscriptions"),"subscription_id","subscription_id='$sid'");
	}
	
	
	/**
	 * Function used to edit subscription
	 *
	 * @param : subscrioption_id
	 * @param : package
	 */
	function updateSubscription($array)
	{
		global $db;
		if(!$this->subsExist($array['subscription_id']))
			e("Unknown Subscription does not exist");
		else
		{
			$videoIds = 0;
			
			/*if($array['allowed_ids'])
			{
				$videoIds = $array['allowed_ids'];
			}*/
			
			
			$start_date = $array['start_date'];
			$end_date = $array['end_date'];
			$active = $array['active'];
			$credits_used = $array['credits_used'];
			
			$total_watched = $array['total_watched'];
			$total_watched_time = $array['total_watched_time'];
			
			$db->update(tbl("paid_subscriptions"),
			array("start_date",
			"end_date","watched","watched_time","active","credits_used"),
			array($start_date,$end_date,$total_watched,$total_watched_time,$active,$credits_used),
			"subscription_id='".$array['subscription_id']."'");
				
			return true;
		}
	}
	
	/**
	 * Function used to add Order
	 * 
	 * @param : Aarray
	 * @return : Order_ID
	 */
	function addOrder($array,$generate_invoice=true)
	{
		global $db,$userquery,$cbemail;
		
		$sid = $array['subscription_id'];
		$pid = $array['package_id'];
		
		//$price = $array['price'];
		
		//Get Package , if there is no package, return false
		$package = $this->getPackage($pid);
		//$price = $this->getPackagePrice($pid);
		$price = $package['pkg_price'];
		
		$currency = $array['currency'] ? $array['currency'] : $this->configs['currency'];
		if($array['userid'])
			$uid = $array['userid'];
		else
			$uid = userid();
			
		//Getting User Details
		$userdetails = $userquery->get_user_details($uid);
		
		$gateway = $array['gateway'];
		$status = $array['status'] ? $array['status'] : 'pending';
		$invoice_status = $array['invoice_status'] ? $array['invoice_status'] : 'unpaid';
		$qt = $array['qt'];
		
		if($generate_invoice && $price)
		{
			$db->insert(tbl('paid_invoices'),
			array('userid','amount','currency','gateway','date_added','status'),
			array($uid,$price,$currency,$gateway,now(),$invoice_status));
			
			$invoice_id = $db->insert_id();
				
		}
		
		$db->insert(tbl('paid_orders'),array
		('invoice_id','order_status','order_qty','userid',
		'package_id','subscription_id','date_added'),
		array
		($invoice_id,$status,$qt,$uid,$pid,$sid,now()));

		$order_id = $db->insert_id();	
		
		$ids_array = array('order_id'=>$order_id);
		if($invoice_id)
			$ids_array['invoice_id'] = $invoice_id;
		
		
		//Sending Email
		$tpl = $cbemail->get_template('paid_order');
		$more_var = array
		('{username}'	=> $userdetails['username'],
		 '{email}'		=> $userdetails['email'],
		 '{uid}'		=> $userdetails['userid'],
		 '{package_title}' => $package['pkg_title'],
		 '{order_id}'	=> $order_id,
		 '{invoice_id}'	=> $invoice_id,
		 '{gateway}'	=> $gateway,
		 '{user_ip}'	=> $userdetails['signup_up'],
		 '{order_ip}'	=> $_SERVER['REMOTE_ADDR'],
		 
		);
		if(!is_array($var))
			$var = array();
		$var = array_merge($more_var,$var);
		$subj = $cbemail->replace($tpl['email_template_subject'],$var);
		$msg = nl2br($cbemail->replace($tpl['email_template'],$var));
		
		//Now Finally Sending Email
		//cbmail(array('to'=>$userdetails['email'],'from'=>SUPPORT_EMAIL,
		//'subject'=>$subj,'content'=>$msg));
		
		if($this->configs['email_notification'] && $this->configs['notify_on_sub']=='yes')
		//	cbmail(array('to'=>$this->configs['email_notification'],'from'=>SUPPORT_EMAIL,
		//		'subject'=>$subj,'content'=>$msg));
		
		return $ids_array;
	}
	
	
	/**
	 * function used to get price from package
	 */
	function getPackagePrice($pid)
	{
		global $db;
		$select = $db->select(tbl('paid_packages'),"pkg_price"," package_id='$pid'");
	
		if($db->num_rows>0)
			return $select[0]['pkg_price'];
		else
			return false;
	}


	
	/**
	 * Function used to get package details via order details
	 * @param : order array
	 */
	function getPackageFromOrder($order)
	{
		global $db;
		$result = $db->select(tbl("paid_packages,paid_subscriptions"),
		tbl("paid_subscriptions.subscription_id,paid_packages.*"),
		tbl("paid_subscriptions.subscription_id")."='".$order['subscription_id']."' AND ".
		tbl("paid_packages.package_id")."=".tbl("paid_subscriptions.package_id"));
		return $result[0];
		
	}

	
	/**
	 * Function used to get subscription videos
	 * @param : subs_vids_ids 
	 * @return : videos array
	 */
	function getSubsVids($allowed_vids,$limit=false)
	{

		if(is_array($allowed_vids))
		{
			$sub = $allowed_vids;
			$allowed_vids = $allowed_vids['allowed_vids'];
		}
		
		if($sub)
		{
			$pkgid = $sub['package_id'];
			$package = $this->getPackage($pkgid);
			
			if($package['is_collection']=='yes')
			{
				$videos = $this->getPackageVideos($pkgid,$limit);
				return $videos;
			}
		}
		
		$videoIds = json_decode($allowed_vids,false);
		
		$query_cond .= "";
		if($videoIds)
		foreach($videoIds as $vid)
		{
			if(!empty($query_cond))
				$query_cond .= " OR ";
			if($vid!="")
			{
				$query_cond .= "videoid='".$vid."'";
			}			
		}
		if($query_cond)
			$videos = get_videos(array('cond'=>" (".$query_cond.")","cond_and"=>true,'limit'=>$limit));
		return $videos;
	}
	
	/**
	 * Get Transaction details from order
	 * @param : order id
	 * @return : tranasction details
	 */
	function getTransaction($oid,$orderid=true)
	{
		global $db;
		if($orderid)
			$result = $db->select(tbl("paid_transactions"),"*","order_id='$oid'");
		else
			$result = $db->select(tbl("paid_transactions"),"*","transaction_id ='$oid'");
		return $result[0];
	}
	
	/**
	 * function used to get invoice
	 */
	function getInvoice($id)
	{
		global $db;
		$result = $db->select(tbl("paid_invoices"),"*","invoice_id='$id'");
		if($db->num_rows>0)
			return $result[0];
		else
			return false;
	}
	
	
	/**
	 * Function used to get orders
	 *
	 * @param : array();
	 * @return : Orders
	 */
	function getOrders($array=null)
	{
		global $db;
		$cond = array();
		if($array['status'])
		{
			if($array['status']=='failed')
				$cond[] = " order_status ='fraud' OR order_status ='cancelled'";
			else
				$cond[] = " order_status ='".$array['status']."' ";
		}

		
		//date span
		if($array['date_span'])
		{
			$cond[] = " ".cbsearch::date_margin("date_added",$array['date_span']);
		}
		
		if(count($cond)>0)
		{
			foreach($cond as $c)
			{
				if($condition)
					$condition .= " AND ";
				$condition .= $c;
			}
		}
		
		
		
		$limit = $array['limit'];
		$order = $array['order'];
		
		if(!$array['count_income'])
		{
			if(!$array['count_only'])
			{
				$result = $db->select(tbl("paid_orders"),"*",$condition,$limit,$order);
				
				
				if($array['assign'])
					assign($array['assign'],$result);
				else
					return $result;
			}else
			{
				$result = $db->count(tbl("paid_orders"),"*",$condition);
				
				if($array['assign'])
					assign($array['assign'],$result);
				else
					return $result;
			}
		}else
		{
			$result = $db->select(tbl("paid_orders"),"SUM(order_final_price) AS income",$condition,$limit);
			if($array['assign'])
				assign($array['assign'],$result[0]['income']);
			else
				return $result[0]['income'];
		}
	}
	
	/**
	 * function used to get subscription
	 */
	function getSubscription($sid)
	{	
		global $db;
		$result = $db->select(tbl("paid_subscriptions,paid_packages"),"*"," subscription_id='$sid' 
		AND  ".tbl('paid_subscriptions.package_id')."=".tbl('paid_packages.package_id'));
		
		return $result[0];
	}
	
	
	/**
	 * function used to varify weather video is already in package or not
	 */
	function in_package($vdo,$sub)
	{
		if(is_array($vdo))
			$vdo = $vdo['videoid'];
		
		if($sub['is_collection']=='yes')
		{
			if($this->in_package_collection($vdo,$sub['package_id']))
				return true;
			
			return false;
		}
		$allowed_vids  = $sub['allowed_vids'];
		$allowed_vids = json_decode($allowed_vids,false);
		
		if($allowed_vids)
		if(in_array($vdo,$allowed_vids))
			return true;
		else
			return false;
	}
	
	/**
	 * function used to get subscription with package details
	 */
	function getSubsWithPackage($sid)
	{
		global $db;
		$subs = $db->select(tbl("paid_subscriptions,paid_packages"),
		"*",tbl('paid_subscriptions.subscription_id')."='$sid' AND ".tbl('paid_subscriptions.package_id').'='.tbl('paid_packages.package_id'));
		
		if($subs && $db->num_rows>0)
		{
			$sub = $subs[0];	
			$videos_left = $sub['pkg_vids'] - $sub['watched'];	
			$ppv_left = $sub['pkg_vids'] - $sub['watched_ppv'];	
			$mins_left = round($sub['pkg_mins'] - $sub['watched_time'] / 60);
			
			$sub['vids_left'] = $videos_left;
			$sub['ppv_left'] = $ppv_left;
			$sub['mins_left'] = $mins_left;
			$sub['credits_left'] = $sub['pkg_credits'] - $sub['credits_used'];
			
			//$sub['days_left'] =  date("d",strtotime($sub['end_date'])-time());
			
			return $sub;
		}
		
		return false;		
	}
	
	
	/**
	 * function used to add video to 
	 * subscrioption package, deduct any credits,mins or videos from package
	 * and let user watch the video
	 */
	function watchPremiumVideo($vdo,$sub)
	{
		global $db,$cbvid;
		
		if(is_numeric($vdo))
			$vdo = $cbvid->getvideo($vdo);
			
		//package details
		$subs = $this->getSubsWithPackage($sub);
		if(!$subs)
			return false;
		
		$seconds = $vdo['duration'];
		$credits = $vdo['credits_required'];
		$watched_ppv = "";
		
		$watched = $subs['watched'] + 1;
		if($vdo['is_premium']=='ppv')
			$watched_ppv = $subs['watched_ppv'] + 1;
		
		$watched_time = $subs['watched_time']+$seconds;
		$credits_used = $subs['credits_used']+$credits;
			
		//Geting vids from subs array
		$allowed_vids = $subs['allowed_vids'];
		//Decoding from json format
		if($allowed_vids)
			$allowed_vids = json_decode($allowed_vids,true);
		else
			$allowed_vids  = array();
		//Adding new videoid
		$allowed_vids[] = $vdo['videoid'];
		//Making array unique to remove any repeatance
		$allowed_vids = array_unique($allowed_vids);
		//encoding back to store in database
		$allowed_vids = json_encode($allowed_vids);
		
		
		$db->update(tbl('paid_subscriptions'),
		array('allowed_vids','watched','watched_ppv','watched_time','credits_used'),
		array('|no_mc|'.$allowed_vids,$watched,$watched_ppv,$watched_time,$credits_used),
		"subscription_id='".$subs['subscription_id']."'");
		
		$db->insert(tbl('paid_subs_videos'),array('subscription_id','videoid'),array($subs['subscription_id'],$vdo['videoid']));
		
		return true;
		
	}
	
	
	/**
	 * Function used to add reports
	 * such as new orders, new subscriptions
	 * invoices...
	 */
	function add_report($params)
	{
		global $db;
		/**
		 * $type,$date,$oid
		 */
		extract($params);
		
		if(!$date)
			e("Invalid date");
		if(!$type)
			e("Invalid type");
		if(!$oid)
			e("Invalid Object Id");
		
		if(!error())
		{
			$data = array();	
			$mdata = array();
					
			$hour = date("H",strtotime($date));
			$day = date("d",strtotime($date));
			$month = date("m",strtotime($date));
			
			$only_date = date("Y-m-d",strtotime($date));
			$only_month = date("Y-m",strtotime($date));
			
			//Get Report from date
			// Uncomment this to make Dayly stats work
			//$report = $this->get_report($only_date);
			$month_report = $this->get_report($only_month,false);
			
			
			// Uncomment this to make Dayly stats work
			/*if(!$report)
			{
				$data['counts'] = 1;
				$data['time'][$hour]['counts'] = 1;
				$data['time'][$hour]['objects'][$oid] = $date;
				
				//Adding data in database
				$db->insert(tbl('paid_reports'),array('report_type','report_date','report_last_update',
				'report_data','date_added','report_counts'),
				array($type,$only_date,now(),'|no_mc|'.json_encode($data),now(),$data['counts']));
				
			}else
			{
				$data = $report['report_data'];
				$data = json_decode($data,true);
				
				$data['counts'] = $data['counts'] + 1;
				$data['time'][$hour]['counts'] = $data['time'][$hour]['counts'] + 1;
				$data['time'][$hour]['objects'][$oid] = $date;
				
				//Updating Data
				$db->update(tbl('paid_reports'),array('report_last_update',
				'report_data','report_counts'),
				array(now(),'|no_mc|'.json_encode($data),$data['counts']),"report_id='".$report['report_id']."' ");
			}*/
			
			
			if(!$month_report)
			{
				$mdata['counts'] = $data['counts'];
				$mdata['days'][$day] = $data['counts'];
			}else
			{
				$mdata = $month_report['report_data'];
				$mdata = json_decode($mdata,true);
				
				$mdata['counts'] 	 = $mdata['counts'] + 1;
				$mdata['days'][$day] = $mdata['days'][$day] +1;
			}
			
			//Adding Month Report
			if(!$month_report)
			{
				//Adding data in database
				$db->insert(tbl('paid_reports'),array('report_type','report_date','report_last_update',
				'report_data','date_added','report_counts'),
				array($type.'_month',$only_month,now(),'|no_mc|'.json_encode($mdata),now(),$mdata['counts']));
			}else
			{
				//Updating Data
				$db->update(tbl('paid_reports'),array('report_last_update',
				'report_data','report_counts'),
				array(now(),'|no_mc|'.json_encode($mdata),
				$mdata['counts']),"report_id='".$month_report['report_id']."' ");
			}
		}
		
		return false;
	}
	
	/**
	 * function used to get report
	 */
	function get_report($date,$type=false,$format=true)
	{
		global $db;
		if($format)
		$date = date("Y-m-d",strtotime($date));
		
		if($type)
			$typequery = " report_type='$type' AND ";
			
		$result = $db->select(tbl("paid_reports"),"*"," $typequery report_date='$date'");

		if($db->num_rows>0)
			return $result[0];
		else
			return false;
	}
	
	
	/**
	 * function used to generate report
	 */
	function generate_report($date,$type)
	{
		global $db;
		$date = date("Y-m-d",strtotime($date));
		switch($type)
		{
			case "date":
			case "day":
			case "d":
			{
				$report = $this->get_report($data);
				$report = $report['report_data'];
				$report = json_decode($report,true);
			}
			break;
			
			case "month":
			case "m":
			{
				//Get all reports made in a month
				//Sum up all the count
				
				$report = $this->get_report($date,false);	
				$total_counts = $result[0]['total_counts'];
			}
			
			case "month":
			case "m":
			{
				$months_data = array();
				for($i=1; $i<13;$i++)
				{
					
					$result = $this->get_report($date,false);
					
				}
			}
		}
	}
	
	
	
	/**
	 * function used to get package videos 
	 */
	function getPackageVideos($pid,$limit=NULL)
	{
		global $cbvid,$db;
		
		
		if($limit=='count')
		{
			return $videos = $db->count(tbl("paid_pkg_videos"),"videoid","package_id='$pid'");
		}
		$videos = $db->select(tbl("paid_pkg_videos")." LEFT JOIN ".tbl("video")
		." on ".tbl("video.videoid=").tbl("paid_pkg_videos.videoid"),
		"*",tbl("video.videoid!=")."'' AND ".tbl("paid_pkg_videos.package_id='$pid'"),$limit);
		
		if($videos)
			return $videos;
		else
			return false;
	}
	
	/**
	 * function used to check weather video is in premium collection or not
	 */
	function in_package_collection($vid,$pid)
	{
		global $db;
		$result = $db->count(tbl('paid_pkg_videos'),'pkg_video_id'," videoid='$vid' AND package_id='$pid'");
		
		if($result>0)
			return true;
		else
			return false;
	}
	
	
	/**
	 * function used to get demo details
	 */
	function get_demo_details()
	{
		global $db;
		$result = $db->select(tbl("paid_demo"),"*",
		"demo_ip='".$_SERVER['REMOTE_ADDR']."'");
		
		if($db->num_rows>0)
		{
			//Checking date difference is more than 24 hours
			$now = now();
			$date = $result[0]['date_added'];
			
			$diff = strtotime($now) - strtotime($date);
			//Diff in hours
			$diff = $diff/60/60;
			
			if($diff<=24)
			{
				return $result[0];
			}else
			{
				//Delete existing and create new
				$db->Execute("DELETE FROM ".tbl("paid_demo")." WHERE demo_ip='".$result[0]['demo_ip']."'");
			}
		}
		
		//Adding Demo Row
		$db->insert(tbl("paid_demo"),array("demo_ip","date_added"),
				array($_SERVER['REMOTE_ADDR'],now()));
		
		return array('watched'=>0,'watched_time'=>0,'date_added'=>now(),'demo_id'=>$db->insert_id());
	}
	function getDemoDetails(){ return $this->get_demo_details(); }
	function demoDetails(){ return $this->get_demo_details(); }
	function demo_details(){ return $this->get_demo_details(); }
	
	
	/**
	 * Watch demo video
	 */
	function watchDemoVideo($vdo,$demo)
	{
		global $db;
		$duration = $vdo['duration'];
		$db->update(tbl('paid_demo'),array('watched_time','watched'),
		array('|f|watched_time+'.$duration,'|f|watched+1'),
		"demo_id='".$demo['demo_id']."'");
		
		return true;
	}

	/**
	 * if video is premium 
	 */
	function is_premium($vid)
	{
		global $db, $cbvid;
		if($cbvid->video_exists($vid))
		{
			$results = $db->select(tbl("video"),"is_premium", "videoid=".$vid."");
			if($results['0']['is_premium'] == 'yes' || $results['0']['is_premium'] == 'ppv')
				return true;
			else
				return false;
        }
        else
        {
              return false;
        }
	}
	
}