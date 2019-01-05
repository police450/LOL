<?php

/**
 * this file controls all the settings and administarting paid packages
 */

if(!defined('SUB_PAGE')){
    define('SUB_PAGE', "CB Multiserver License");
}


template_files('configure.html',PLUG_DIR.'/'.$cb_multiserver.'/admin');


if(isset($_POST['update']))
{
	$array = array
	('license_key'
	);
	
	foreach($array as $name)
	{
		$value = mysql_clean($_POST[$name]);
		$db->update(tbl('server_configs'),array("value"),array($value)," name='$name' ");
	}
	
	e("Configurations have been updated","m");
}

	$configs = $multi_server->getConfigs();
	assign('config',$configs);


?>