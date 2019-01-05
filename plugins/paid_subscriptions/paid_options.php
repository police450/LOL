<?php

if(!defined('IN_CLIPBUCKET'))
	exit('Invalid access');
	
$premium_opts = array();


if(!$_POST['make_premium'])
	$checked = 'no';


if(has_access('allow_make_premium',true))
{	
	//Make premium option field 
	$premium_opts['make_premium'] = array
	(
		'title' => 'Make premium',
		'id'	=> 'is_premium',
		'name'	=> 'is_premium',
		'type'	=> 'radiobutton',
		'sep'	=> ' ',
		'value' => array('no'=>'No','yes'=>'Yes','ppv'=>'PPV'),
		'extra_tags' => ' onclick="switchPremium(this)"',
		'db_field'	=> 'is_premium'
	);
	
	if($checked)
		$premium_opts['make_premium']['checked'] = $checked;
	
	//Premium options/packages/type
	
	$premium_opts['credits_required'] = array
	(
		'title' => 'Credits required',
		'id'	=> 'credits_required',
		'name'	=> 'credits_required',
		'type'	=> 'textfield',
		'anchor_after' => 'credits_required_after',
		'db_field'	=> 'credits_required'
	);
	
	
	$cpkgs = array(''=>'- Add video to premium-');
	$pkgs = $paidSub->getPackages(array('is_collection'=>'yes'));
	
	if($pkgs)
		foreach($pkgs as $p)
			$cpkgs[$p['package_id']] = $p['pkg_title'];
	
	if(BACK_END)
	$premium_opts['premium_cid'] = array
	(
		'title' => 'Premium collection',
		'id'	=> 'premium_cid',
		'name'	=> 'premium_cid',
		'type'	=> 'dropdown',
		'value'	=> $cpkgs,
		'db_field'	=> 'premium_cid'
	);
	
	
	
	
	$premium_opts_group = array
	(
		'group_name'		=> 'Required fields',
		'group_id'			=> 'required_fields',
		'fields'			=> $premium_opts
	);

	register_custom_form_field($premium_opts_group,true);
	register_anchor('Credits required in order to watch this ppv video','credits_required_after');
}

$Cbucket->add_header(PAID_SUBS_DIR.'/templates/header.html');


?>