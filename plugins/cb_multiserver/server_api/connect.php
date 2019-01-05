<?php

/**
 * This file is used to connect two servers
 * do not change anything except application ket and secrey key values
 */

ini_set("log_errors",true);
ini_set("error_log","error_log.txt");
error_reporting(E_ALL ^E_NOTICE ^E_DEPRECATED);
include("config.php");
if($application_key!=$_POST['application_key'] || $_POST['secret_key'] != $secret_key || !$secret_key || !$application_key)
	echo "error";
else
	echo "ok";