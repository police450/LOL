<?php
 /**
* File: Create plan
* Description: This Class is written to manange all the Actions for paypal rest api
* @license: Attribution Assurance License
* @since: ClipBucket 2.8
* @author[s]: Awais Fiaz
* @copyright: (c) 2008 - 2016 ClipBucket / PHPBucket
* @modified: July 11, 2017 ClipBucket 2.8.2
*/

if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');

require_once dirname(dirname(dirname(__FILE__)))."/paid_subscriptions/gateways/paypal_rest.php";
// require_once "../prem_functions.php";
$paypal = new paypalRest($paidSub);

if(isset($_POST['submit'])){
	//On Plan Creation
	$title = $_POST['title'];
	$description = $_POST['description'];
	$payment_type = $_POST['payment_type'];
	$package_id = $_POST['package_id'];
	$package=$paidSub->getPackage($package_id);
	$pkg_title=$package['pkg_title'];
	
	// payment_definitions
	$frequency_interval = $_POST['frequency_interval'];
	$price = $_POST['price'];
	$cycles = $_POST['cycles'];
	$name = "Regular payment definition";
	
	//regular or trial
	$type = "REGULAR";
	$frequency = $_POST['payment_frequency'];
	$currency = "USD";
	
	// merchant_preferences
	$currency;
	$value=1;

	$cancel_url = "{$baseurl}/module.php?s=premium&p=cancle_subscription&package_id=".$package_id."";
	$return_url = "{$baseurl}/module.php?s=premium&p=thank_you_subscription&package_id=".$package_id."";
	$max_fail_attempts = 0;
	$auto_bill_amount = "YES";
	$initial_fail_amount_action = "CONTINUE";

	//plan creation attributes
	$plan_attributes = array(
		'name' =>$title,
		'description' => $description,
		'type' => $payment_type,
		'payment_definitions'=>[array('name'=>$name,'type'=>$type,'frequency_interval'=>$frequency_interval,'frequency'=>$frequency,'cycles'=>$cycles,'amount'=>array('currency'=>$currency,'value'=>$price))],
		'merchant_preferences'=>array('setup_fee'=>array( 'currency'=>$currency , 'value'=>$value ),'cancel_url'=>$cancel_url,'return_url'=>$return_url,'max_fail_attempts'=>$max_fail_attempts,'auto_bill_amount'=>$auto_bill_amount,'initial_fail_amount_action'=>$initial_fail_amount_action));
	//getting token
	$token_params['paypal_client_id'] = $paidSub->configs['paypal_client_id'];
	$token_params['paypal_secret'] = $paidSub->configs['paypal_secret'];
	//request for paypal access tokens
	$token = $paypal->get_access_token($token_params);
	
	//defining request type
	$request_type="create_plan";

	// preparing array to send attributes to function
	$att_send['access_token']=$token;
	$att_send['request']=$plan_attributes;
	$att_send['request_type']=$request_type;

	//calling billing plan api
	$response=$paypal->paypal_request($att_send);
	// array_push($response, $package_id);
	$response['package_id'] = $package_id;
	$response['pkg_title'] = $pkg_title;
	// pr($response,true);
	if($response['state'] == 'CREATED'){
		
		if(insert_plan_details($response)){
			e("New Subscription plan <strong>".$response['name']."</strong> has been created! plan id is <strong>".$response['id']."</strong>","m");
		}else{

			e("There is some error occured creating plan.!");
			e('There was some error in insertion');
		}
	}else{
		e("There is some error occured creating plan.!");
		e($response["details"][0]['issue']);	
	}
	
	//all packages
	$packages=$paidSub->getPackages();
	assign('packages',$packages);
	// listing all plans
	$plans = get_plan_details();
	assign('plans',$plans);
	
}
elseif(isset($_POST['activate_plan'])){
	//On Activation	
	//plan activation attributes
	$activation_attributes=[array(
		'path'=>'/',
		'value'=>array('state'=>'ACTIVE'),
		'op'=>'replace'
		)];

	$plan_id=$_POST['plan_id'];
	$plan_name=$_POST['plan_name'];
	$plan_description=$_POST['plan_description'];

	$active_request_type="activate_plan";
		//getting token
	$token_params['paypal_client_id'] = $paidSub->configs['paypal_client_id'];
	$token_params['paypal_secret'] = $paidSub->configs['paypal_secret'];

	$token = $paypal->get_access_token($token_params);
	$active_att_send['access_token']=$token;
	$active_att_send['request']=$activation_attributes;
	$active_att_send['request_type']=$active_request_type;
	$active_att_send['id']=$plan_id;
	$active_att_send['custom_request_type']="PATCH";

	$active_response=$paypal->paypal_request($active_att_send);

	// pr($active_response,true);

	if($active_response['http_code']==200){

		if(update_plan_details($plan_id)){

			e("Plan has been Activated.! against plan ID <strong>".$plan_id."</strong>","m");

			// //agreement part is now being handled at front-end
			// //setting time to start aggreement
			// $now = time();
			// $increased_time = $now + (5 * 60);
			// $utc = date('Z');
			// $utc = $utc/100;
			// $start_date = date('Y-m-d\TH:i:s.'.$utc.'\Z', $increased_time);

			// //setting time to start aggreement

			// $agreement_attributes=array('name'=>$plan_name,'description'=>$plan_description,'start_date'=>$start_date,
			// 	'plan'=>array('id' => $plan_id ),
			// 	'payer'=>array('payment_method' => 'PAYPAL' )
			// 	);
			// 	// preparing array for creating agreement
			// $agreement_request_type='create_agreement';
			// $agreement_att['access_token']=$token;
			// $agreement_att['request']=$agreement_attributes;
			// $agreement_att['request_type']=$agreement_request_type;
			// $agreement_response=$paypal->paypal_request($agreement_att);
			// // pr($agreement_response,true);
			// if($agreement_response['links']){

			// 	e("agreement has been created Approval_url : <strong>".$agreement_response['links'][0]['href']."</strong>","m");
			// 	e("Execution_url : <strong>".$agreement_response['links'][1]['href']."</strong>","m");
			// 	$payment_token=(explode("=",$agreement_response['links'][0]['href']));
			// 	e("Payment token : <strong>".$payment_token[2]."</strong>","m");

			// }else{

			// 	e("Nah! bro agreement response is not right!");
			// }

		}else{
			e("There was some error occured Updating Plan IN Databases.!");
		}

	}else{
		e("There was some error activating Plan.!");
	}
	//all packages
	$packages=$paidSub->getPackages();
	assign('packages',$packages);
	// listing all plans
	$plans = get_plan_details();
	assign('plans',$plans);
}
elseif(isset($_POST['delete'])){
	//On delete	
	$p_id=$_POST['p_id'];
	if(delete_plan($p_id)){
		$msg = e("Plan with id ".$p_id." has been deleted!","m");	
	}else{
		$msg = e("there is somthing wrong in deleteing plan");
	}
	assign('msg',$msg);

	//all packages
	$packages=$paidSub->getPackages();
	assign('packages',$packages);
	// listing all plans
	$plans = get_plan_details();
	assign('plans',$plans);

}
else{

	//all packages
	$packages=$paidSub->getPackages();
	assign('packages',$packages);
	// listing all plans
	$plans = get_plan_details();
	assign('plans',$plans);
}

subtitle('Create Plan - Paid subscriptions');

//Loading template
$template = 'create_plan.html';
template_files($template,PAID_SUBS_DIR.'/admin');
?>