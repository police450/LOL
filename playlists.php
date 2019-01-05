<?php

/* 
 ********************************************************************
 | Copyright (c) 2007-2017 Clip-Bucket.com. All rights reserved.	
 | @ Author : AwaisFiaz											
 | @ Software : ClipBucket , © PHPBucket.com							
 ********************************************************************
*/
 define("THIS_PAGE",'playlists');
 define("PARENT_PAGE",'playlists');
 require 'includes/config.inc.php';
 $pages->page_redir();

 //getting limit for pagination
 $page = mysql_clean($_GET['page']);
 $get_limit = create_query_limit($page,7);

		//Getting List of available playlists with pagination
 $result_array=$array;
 $result_array['limit'] = $get_limit;
 $result_array['privacy'] = 'public';
 $result_array['has_items']='yes';
 if(!$array['order'])
 	$result_array['order'] = " playlists.date_added DESC ";
 		// pex($result_array,true);
 $playlists = $cbvid->action->get_playlists($result_array);



 //Playtlists Data for Pagination
 $pcount = $array;
 $pcount['count_only'] = true;
 $pcount['privacy'] = 'public';
 $pcount['has_items']='yes';
 $total_rows  = get_playlists($pcount);
 		// pex($total_rows,true);
 $total_pages = count_pages($total_rows,7);
 $pages->paginate($total_pages,$page);


 assign('playlists',$playlists);



 subtitle(lang('playlists'));
//Displaying The Template
 template_files('playlists.html');
 display_it();
 ?>