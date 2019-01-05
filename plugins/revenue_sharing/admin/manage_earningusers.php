<?php


if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');

$userquery->admin_login_check();
$pages->page_redir();

if(!defined('MAIN_PAGE')){
	define('MAIN_PAGE', 'Revenue Sharing');
}
if(!defined('SUB_PAGE')){
	define('SUB_PAGE', "Manage Earning Users");
}

try {
	
	$mode = $_GET['mode'];
	$userid	= $_GET['userid'];
	
	assign('userid',$userid);
	
	switch ($mode) {
		
		case 'pay_now':

		if(isset($_POST['pay_via_paypal'])){
			
			$userid = $_POST['userid'];
			$eu_detials = $revshare->get_eu_details($userid);
			$eu_pp_email = $eu_detials[0]['paypal_email'];
			
			$eu_earning_amount = $revshare->get_eu_earnings($userid);
			$eu_earning_amount = round((float)$eu_earning_amount, 2);
			
			$response=$revshare->pay_via_paypal($eu_pp_email,$eu_earning_amount,$userid);
			
			e($response,'m');
		
		}

		if(isset($_POST['mark_paid'])){
			$userid = $_POST['userid'];

			$eu_detials = $revshare->get_eu_details($userid);
			$eu_bank_acc_no = $eu_detials[0]['bank_acc_no'];
			
			$eu_earning_amount = $revshare->get_eu_earnings($userid);
			$eu_earning_amount = round((float)$eu_earning_amount, 2);

			$response=$revshare->mark_earnings_paid($userid,$eu_earning_amount,$eu_bank_acc_no);
			e($response,'m');
		}


		$eu_details=$revshare->get_eu_details($userid);
		
		assign('eu_bank_acc',$eu_details[0]['bank_acc_no']);
		assign('mode','pay_now');	
		break;
		
		case 'stats':
		
			$user_graph_data = $revshare->get_stats($userid);
			
			assign("user_graph_data",$user_graph_data);
			assign('mode','stats');	
		break;

		case 'payment_history':
		
			$payment_history = $revshare->get_payment_history($userid);
			
			assign("payment_history",$payment_history);
			assign('mode','payment_history');	
		break;
		
		case 'view_details':
			if($_POST['update_eu_info']){
				$params=$_POST;
				$response=$revshare->update_eu_details($params);
				e($response,'m');
			}
			$eu_details=$revshare->get_eu_details($userid);

			assign('eu_details',$eu_details);
			assign('mode','view_details');	
		break;

		case 'set_rpm':
			if(isset($_POST['add_rpm'])){

				$params=$_POST;
				$response = $revshare->add_rpm($params);
				e($response,'m');
			}
			if(isset($_POST['update_rpm'])){

				$params=$_POST;
				$response = $revshare->update_rpm($params);
				e($response,'m');
			}
			if(isset($_POST['remove_tier'])){

				$params=$_POST;
							// pr($params,true);
				$response = $revshare->remove_rpm($params['rpm_id']);
				e($response,'m');
			}

			$rpms=$revshare->get_rpm($userid);

			assign('rpms',$rpms);
			assign('mode','set_rpm');	
		break;

		case 'update_rpm':

			$rpm_id=$_GET['rpm_id'];
			$rpm_rpmid=$revshare->get_rpm_with_rpmid($rpm_id);
			echo json_encode($rpm_rpmid);

			exit;
		break;	

		default:
			if(isset($_POST['activate'])){

				$response=$revshare->update_eu_status('activate',$_POST);
				e($response,'m');
			}
			if(isset($_POST['deactivate'])){
						// pr($_POST,true);
				$response=$revshare->update_eu_status('deactivate',$_POST);
				e($response,'m');
			}
			$earning_users=$revshare->get_earning_users();

			assign('earning_users',$earning_users);
		break;
	}


	
} catch (Exception $e) {

		e($e->getMessage(),"e");	

}




subtitle("Manage earning users");
template_files(REV_SHARE_DIR.'/admin/manage_earningusers.html');

?>