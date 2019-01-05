<?php

if(isset($_POST['update_archive']))
{    
    $total = count($_POST['from']);
    $data = array();
    for($i=0;$i<$total;$i++)
    {
        
        $d = array(
            'from'  => $_POST['from'][$i],
            'to'  => $_POST['to'][$i],
            'file_server'  => $_POST['file_server'][$i],
            'archive_server'  => $_POST['archive_server'][$i]
        );
        
        $data[] = $d;
    }
   
   
    $servers = array();
    if($data)
    {
        foreach($data as $dat)
        {
            if($dat['file_server'] && $dat['archive_server']
                    && $dat['to'] && $dat['from'])
            {
                $servers[$dat['file_server']][] = array(
                    'to'    => $dat['to'],
                    'from'  => $dat['from'],
                    'archive_server'    => $dat['archive_server'],
                    'file_server'  => $dat['file_server']
                ); 
            }
        }
    }
    
    $fields = json_encode($servers);
	
	$db->update(tbl('server_configs'),array("value"),array('|no_mc|'.$fields)," name='archives' ");
	e("Configurations have been updated","m");
}


$configs = $multi_server->getConfigs(true);
$archives = $configs['archives'];
$archives = json_decode($archives,true);


assign('archives',$archives);

subtitle('Archiving Servers');
template_files('archive.html',PLUG_DIR.'/'.$cb_multiserver.'/admin');

?>