<?php

if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');

 /*
 * this file controls all the settings and administarting paid subscriptions
 */


assign("_link","plugin.php?folder="._PAID_SUBS_."/admin&file=manage_subscription.php");
$_link = "plugin.php?folder="._PAID_SUBS_."/admin&file=manage_subscription.php";


//Adding Subscription
if(isset($_POST['add_subs']))
{
	$uid = $userquery->get_user_field_only(mysql_clean($_POST['userid']),"userid");
	if(!$uid)
		e("User does not exist");
	if(!$paidSub->packageExists(mysql_clean($_POST['pid'])))
		e("Package does not exist");

	if(!error())
	{
		$pid  = mysql_clean($_POST['pid']);
		if($_POST['active'])
			$active = 'yes';
		else
			$active = 'no';
		
		$sid = $paidSub->addSubscription($uid,$pid,array('active'=>$active));
		
		if($_POST['gen_invoice'])
		$paidSub->addOrder(array('subscription_id'=>$sid,'package_id'=>$pid,'gateway'=>'paypal','status'=>'ok',
		'invoice_status'=>'paid'),true);
		
		e("Subscription has been added","m");
		
		unset($_POST);
	}else
		assign("call_form","add_form");
}

//Updaing Subscription
if(isset($_POST['update_subs']))
{
	$paidSub->updateSubscription($_POST);	
}


//Function used to delete subscription
if(isset($_GET['delete']))
{
	$sid = mysql_clean($_GET['delete']);
	$paidSub->deleteSubscription($sid);
}
if(isset($_POST['delete_selected'])){
	for($id=0;$id<=count($_POST['check_subs']);$id++){
		$paidSub->deleteSubscription($_POST['check_subs'][$id]);
	}
	$eh->flush();
	e("Selected subscriptions have been deleted","m");
}

//Function used to activate subscription
if(isset($_GET['activate']))
{
	$sid = mysql_clean($_GET['activate']);
	$paidSub->subscriptionAction("activate",$sid);
}

if(isset($_POST['activate_selected'])){
	for($id=0;$id<=count($_POST['check_subs']);$id++){
		$paidSub->subscriptionAction("activate",$_POST['check_subs'][$id]);
	}
	$eh->flush();
	e("Selected subscriptions have been activated","m");
}

//Function used to deactivate subscription
if(isset($_GET['deactivate']))
{
	$sid = mysql_clean($_GET['deactivate']);
	$paidSub->subscriptionAction("deactivate",$sid);
}
if(isset($_POST['deactivate_selected'])){
	for($id=0;$id<=count($_POST['check_subs']);$id++){
		$paidSub->subscriptionAction("deactivate",$_POST['check_subs'][$id]);
	}
	$eh->flush();
	e("Selected subscriptions have been deactivated","m");
}

if($_POST['search'])
{
	$subs_cond = array();
	$uid = $userquery->get_user_field_only(mysql_clean($_POST['uid']),"userid");
	$subs_cond['uid'] 		= 	$uid;
	$subs_cond['pid'] 		= $_POST['pid'];
	$subs_cond['active'] 	= $_POST['active'];
}

//Getting Subscriptions List
$page = mysql_clean($_GET['page']);
$get_limit = create_query_limit($page,RESULTS);
$subsList = $subs_cond;
$subsList['limit'] = $get_limit;
$subs = $paidSub->getSubscriptions($subsList);
Assign('subs', $subs);	
$packages = $paidSub->getPackages();
Assign('packages', $packages);	

//Collecting Data for Pagination
$subsList = $subs_cond;
$subsList['count_only'] = true;
$total_rows  = $paidSub->getSubscriptions($subsList);
$total_pages = count_pages($total_rows,RESULTS);

//Pagination
$pages->paginate($total_pages,$page);

subtitle('Manage subscriptions - Paid subscriptions');


template_files('manage_subscription.html',PLUG_DIR.'/'._PAID_SUBS_.'/admin');
?>