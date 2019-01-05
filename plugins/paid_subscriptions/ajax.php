<?php

/**
 * Premium plugin ajax page
 */


include("../../includes/config.inc.php");
$array = array();

$mode = $_POST['mode'];

switch($mode)
{
	case "add_order":
	{
		$pid = mysql_clean($_POST['pid']);
		$gateway = mysql_clean($_POST['gateway']);
		//adding subscription
		$sid = $paidSub->addSubscription(userid(),$pid,array('active'=>'no'));
		//Adding Order
		$order_added = $paidSub->addOrder(array('subscription_id'=>$sid,'package_id'=>$pid,'gateway'=>$gateway),true);
		
		if(error())
			$array["err"] = error();
		else
			$array = $order_added;
	}
	break;
	
	case "renew_subs":
	case "renew_order":
	{
		$pid = mysql_clean($_POST['pid']);
		$gateway = mysql_clean($_POST['gateway']);
		$sid = mysql_clean($_POST['sid']);
		
		$sub = $paidSub->getSubscription($sid);
		
		//Validate Subscription
		if(!$sub)
		{
			$array["err"] = "Subscription does not exist";
		}elseif($sub['package_id']!=$pid)
		{	
			$array["err"] = "Invalid Package";
		}else
		{
			//Adding Order
			$order_added = $paidSub->addOrder(array('subscription_id'=>$sid,'package_id'=>$pid,'gateway'=>$gateway),true);
			
			if(error())
				$array["err"] = error();
			else
				$array = $order_added;
		}
	
	}
	
	break;
	
	case "remove_package_video":
	{
		if(has_access('admin_access',true))
		{
			$pid = mysql_clean(post('pid'));
			$vid = mysql_clean(post('vid'));
			
		

			if($paidSub->in_package_collection($vid,$pid))
			{
				$db->Execute("DELETE FROM ".tbl("paid_pkg_videos")." WHERE package_id='$pid' AND videoid='$vid' ");
				$array['msg'] = "<span class='label label-success removed'>removed</span>   &nbsp;&nbsp;
				                 <span class='btn btn-warning btn-xs unpremium_$vid' 
				                 onclick='remove_from_prem($vid);'>
				                 Do you also want to remove from Premium?</span>
				                 <span class='btn btn-success btn-xs remain_$vid'>No</span>
				                 </span><div class='paid_output_$vid' style='margin-bottom:30px;'></div>";
			}else
				$array['err'] = "Invalid video";
			
			
		}else
			echo json_encode(array('err'=>'Permission denied'));
	}
	
	break;

	case "remove_from_prem":
	{
		if(has_access('admin_access',true))
		{
			$vid = mysql_clean(post('vid'));

			if($paidSub->is_premium($vid))
			{   
				$updated = $db->update( tbl( 'video' ), array( 'is_premium' ), array( 'no' ), " videoid = '".$vid."' ");
	            $array['msg'] = '<span class="alert alert-success removed-2" >This video has been removed form premium</span>';
	        }
            else
			{
				 $array['err'] = '<span class="alert alert-warning">Invalid request</span>';
			}
		}

	}
    
    break;
	
	case "add_to_package":
	{
		if(has_access('admin_access',true))
		{
			$vid = mysql_clean(post('vid'));
			$pid = mysql_clean(post('pid'));
			add_to_package_collection($vid,$pid);
			
			$msg = msg();
			$err = error();
			
			if($msg)
				$array['msg'] = $msg[0];
			
			if($err)
				$array['err'] = $err[0];
		}else
			echo json_encode(array('err'=>'Permission denied'));
	}
	
}

if(!$array)
	$array['msg'] = "Nothing to do";
	
echo json_encode($array);

?>