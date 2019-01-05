<?php
include("../../includes/config.inc.php");
if(!has_access('admin_access',true)) exit(json_encode(array('err'=>'Invalid admin')));


$server_id = mysql_clean($_POST['server_id']);

if($_POST['sync']=='yes')
    $multi_server->sync_video_profiles($server_id);
else
    $multi_server->show_video_profiles($server_id);

?>