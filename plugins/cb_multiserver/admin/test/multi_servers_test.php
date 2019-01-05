<?php

/**
 * This file is used to handle all admin functions
 * such as adding new server, updating existing one or deleteing it
 */
 

 
assign("_link_test","plugin.php?folder=$cb_multiserver/admin/test&file=multi_servers_test.php");
assign("server_ajax",BASEURL.'/plugins/'.$cb_multiserver.'/admin/test/multi_servers_ajax.php');

//geting sample files...
if ($handle = opendir(PLUG_DIR.'/'.$cb_multiserver.'/admin/test/sample_files/')) {
    while (false !== ($file = readdir($handle)))
    {
        if ($file != "." && $file != ".." && strtolower(substr($file, strrpos($file, '.') + 1)) == 'mp4')
        {
            $thelist .= '<option value="'.PLUG_DIR.'/'.$cb_multiserver.'/admin/test/sample_files/'.$file.'">'.$file.'</option>';
        }
    }
    closedir($handle);
}
assign('options',$thelist);

assign('laoding',BASEURL.'/plugins/'.$cb_multiserver.'/admin/test/image/ajax-loader.gif');

assign('laoding2',BASEURL.'/plugins/'.$cb_multiserver.'/admin/test/image/ajax-loader2.gif');

template_files('multi_servers_test.html',PLUG_DIR.'/'.$cb_multiserver.'/admin/test');

?>