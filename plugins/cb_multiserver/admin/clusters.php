<?php


if(isset($_POST['update']))
{

	$fields = array();
	
    

	if($_POST['file_path'])
	{
		
		if($_POST['file_childs'])
		{
			foreach($_POST['file_childs'] as $path)
			{
				if($path)
				$fields[$_POST['file_path']][] = array('file_server_path' => $path);
			}
		}
		
	}
	
	if($_POST['file_paths'])
	{
		foreach($_POST['file_paths'] as $key => $value)
		{
		
			if($_POST['file_path_childs'][$key] && $value)
			foreach($_POST['file_path_childs'][$key] as $child)
			{	
				if($child)
				{
					$fields[$value][] = array('file_server_path' => $child);
				}
			}
			
			
		}
	}
	
	//$fields = array_unique($fields);

	$fields = json_encode($fields);
	
	$db->update(tbl('server_configs'),array("value"),array('|no_mc|'.$fields)," name='clusters' ");
	
	e("Configurations have been updated","m");
}


$configs = $multi_server->getConfigs();
$clusters = $configs['clusters'];
$clusters = json_decode($clusters,true);


assign('clusters',$clusters);

template_files('clusters.html',PLUG_DIR.'/'.$cb_multiserver.'/admin');


?>