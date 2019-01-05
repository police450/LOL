<?php

	/*
	* File : Connect.php
	* Description : Handles insertion, updating and status checking 
	* of multiserver servers
	*/

	include("../../includes/config.inc.php");

	// Handles ajax request for adding new server
	if($_POST['mode']=='add') {
		$multi_server->add_server($_POST);
		$err = error();
		if($err) {
			echo json_encode(array('err'=>$err[0]));
		} else {
			echo json_encode(array('msg'=>"Server has been updated"));
		}		
	}

	// handles ajax request for updating server configs
	if($_POST['mode']=='update') {
		$multi_server->update_server($_POST);
		$err = error();
		if($err) {
			echo json_encode(array('err'=>$err[0]));
		} else {
			echo json_encode(array('msg'=>"Server has been updated"));
		}
	}

	if($_POST['mode']=='get_status') {
		$server = $_POST['server'];
		$stats = $server.'/stats_generater.php';
		
		$ch = curl_init($stats);
		curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,array('get_status'=>true));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$result = curl_exec($ch);
		curl_close($ch);
		
		echo $result;
	}

	if($_POST['mode']=='gen_stats') {
		 $server = $_POST['server'];
		 $ch = curl_init($server.'/stats_generater.php');
		 curl_setopt($ch,CURLOPT_POST,true);
		 curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
		 curl_setopt($ch,CURLOPT_POSTFIELDS,array("gen_stats"=>'yes','type'=>$_POST['type'],'date'=>$_POST['date']));
		 curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		 curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,8);
		 curl_setopt($ch,CURLOPT_TIMEOUT,15);
		 $result = curl_exec($ch);
		 curl_close($ch);
		 echo $result;
	}

?>