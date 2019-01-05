<?php
/**
 * @Author Mohammad Shoaib
 * 
 * Rest full Api for ClipBucket to let other application access data
 */
//header("Content-Typeapplication/json");


require_once("Rest.inc.php");

include('global.php');
//include('error_codes.php');
session_unset();
$method = $_SERVER['REQUEST_METHOD'];
$method = strtolower($method);

if($method!="")
{
   if($method=="get")
   require_once("get.php");
   else if($method=="post")
   require_once("post.php"); 
   else if($method=="delete")
   require_once("delete.php");
   else if($method=="put")
   require_once("put.php");
   else
   echo '{"status":"Failure","msg":"Bad Request", "code":"404"}'; 
}
else
echo '{"status":"Failure","msg":"Bad Request", "code":"404"}';  

?>