<?php

if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');

/**
 * this file controls all the settings and administarting paid packages
 */




$queryString = queryString('',array('page','id','mode'));
assign("queryString",$queryString );

$mode = @$_GET['mode'];
$pkgid = @$_GET['id'];

switch($mode)
{
	case "deactivate":
	{
		$paidSub->packageAction('deactivate',mysql_clean($pkgid));
	}
	break;
	case "activate":
	{
		$paidSub->packageAction('activate',mysql_clean($pkgid));
	}
	break;
	case "delete":
	{
		$paidSub->packageAction('delete',mysql_clean($pkgid));
	}
	break;
	
}

//Adding new Package
if(isset($_POST['add_package']))
{
	$paidSub->addNewPackage($_POST);
}

//Adding new Package
if(isset($_POST['edit_package']))
{
	$paidSub->editPackage($_POST);
}


//delete
if(isset($_GET['force_delete']))
{
	$paidSub->packageAction('force_delete',mysql_clean($_GET['force_delete']));
}


//Loading template
$template = 'paid_packages.html';
if($mode=='edit')
{
	$package = $paidSub->getPackage($pkgid);
	if(!$package)
		e(lang("Package does not exist"));
	else
	{
		assign('package',$package);
		$template = 'edit_package.html';
	}
}

if($mode=='videos')
{
 $package = $paidSub->getPackage($pkgid);
	if(!$package)
		e(lang("Package does not exist"));
	else
	{
        assign('package',$package);
		$template = 'package_videos.html';
	}
}


subtitle('Manage packages - Paid subscriptions');

template_files($template,PAID_SUBS_DIR.'/admin');


?>