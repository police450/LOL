<?php
/**
*	Plugin Name: ClipBucket Mass Embedder
*	Description: This plugin will populate your website with videos grabbed from Youtube and Dailymotion
*	Author: Arslan Hassan, Saqib Razzaq
*	Author Website: http://clip-bucket.com/
*	ClipBucket Version: 2.7.x
*	Version: 3
*	Website: http://clip-bucket.com/
*	Plugin Type: global
*/

if(!function_exists('cb_mass_embed'))
{
	include("cb_mass_embed.class.php");
	define("cb_mass_embed_install","installed");
	define('CB_MASS_EMBED', 'cb_mass_embed');
	define("CB_MASS_EMBED_LOC",basename(dirname(__FILE__)));
	define("CB_MASS_EMBED_DIR",PLUG_DIR."/".CB_MASS_EMBED_LOC);
	define("CB_MASS_EMBED_URL",PLUG_URL."/".CB_MASS_EMBED_LOC);
	
	assign("cb_mass_embed","installed");
	assign('mass_embed_dir',PLUG_DIR.'/'.CB_MASS_EMBED_LOC);
	assign("mass_embed_url",PLUG_URL.'/'.CB_MASS_EMBED_LOC);
	
	include (__DIR__.'/cb_mass_inc.php');

	if(BACK_END)
	{
		function cb_mass_embed()
		{
			/* */
		}

		$cb_mass_embed = new cb_mass_embed();
		// LICENSE 
		define('CB_MASS_EMBED_LICENSE',$cb_mass_embed->configs['license_key']);
		//LICENSE checking 
		//check_cb_embed_license(CB_MASS_EMBED_LICENSE);
		//add_admin_menu('Videos','Mass Embed Videos','cb_mass_embed.php');
	
		$Cbucket->add_admin_header(dirname(__FILE__).'/headers/admin_header.html','cb_mass_embed');
		//Adding Admin Menu
		add_admin_menu("Mass Embedder","Mass Embed Configuration",'cb_mass_configuration.php',CB_MASS_EMBED_LOC.'/admin');
	}
	
	/**
	 * Function used to remove track from database so 
	 * User can embed video later
	 */
	function remove_mass_embed_track($vid)
	{
		global $db;
		$unique_code = $vid['unique_embed_code'];
		$db->Execute("DELETE FROM ".tbl('mass_embed')." WHERE	mass_embed_unique_id='$unique_code'");
	}
	
	/**
	 * Function used to create category form for video
	 */
	function create_vid_cat_form($param)
	{
		#pr($param,true);
		$name = $param['name'];
		$name = $name ? $name : 'category';
		
		$video = $param['video'];
		$cats = $video['category'];
		preg_match_all('/#([0-9]+)#/',$cats,$m);
		$cat_array = $m[1];
		foreach($cat_array as $new_cat)
		{
			if($new_cat)
				$new_arr[$new_cat] = $new_cat;
		}
		
		
		$form =  array(
						'title'		=> lang('vdo_cat'),
						'type'		=> 'checkbox',
						'name'		=> $name.'[]',
						'id'		=> 'category',
						'value'		=> array('category',array($new_arr)));
		//pr($form,true);
		if($param['assign'])
			assign($param['assign'],$form);
		else
			return $form;

	}
	
	
	/**
	 * Function used to get video via Uniquey Embed Code
	 */
	function getVidFromUC($code)
	{
		global $db;
		$result = $db->select(tbl("video"),"*",
		"unique_embed_code = '$code' ");
		
		if($db->num_rows>0)
			return $result[0];
		else
			return false;
	}
	
	/**
	 * get category list for video
	 */
	function getEmbedCategoryList()
	{
		return getCategoryList(array('type'=>'video'));
	}
	
	$Smarty->register_function('create_vid_cat_form','create_vid_cat_form');	
	//Registering Delete video function
	register_action_remove_video('remove_mass_embed_track');

	$Cbucket->add_admin_header(PLUG_DIR.'/'.CB_MASS_EMBED_LOC.'/admin/header.html',CB_MASS_EMBED_LOC);
}


?>