<?php
	require '../../includes/config.inc.php';
	if (isset($_POST['website_title']))
	{
		global $db;
		$data = $_POST;

		#pex($data,true);
		$bools_array = array('daily_mail','single_item_meta','index_videos','index_photos','index_channels','index_categories');
		foreach ($bools_array as $elem_check) 
		{
			if ($data[$elem_check] != 'on')
			{
				$data[$elem_check] = 'off';
			}
		}
		#pex($data,true);
		foreach ($data as $key => $value) 
		{
			$id = $db->update(tbl("seo_ninja"),array("value"),array($value), "name = '$key' AND config_id != ''");
		}

		echo "Website Settings have been updated";
	}
	elseif (isset($_POST['fb_def_title']))
	{
		$bools_array = array('fb_auto_post','tw_auto_post','google_auto_post');
		global $db;
		$data = $_POST;

		foreach ($bools_array as $elem_check) 
		{
			if ($data[$elem_check] != 'on')
			{
				$data[$elem_check] = 'off';
			}
		}
		#pex($data,true);
		foreach ($data as $key => $value) 
		{
			$id = $db->update(tbl("seo_ninja"),array("value"),array($value), "name = '$key' AND config_id != ''");
		}

		echo "Social Settings have been updated";
	}
?>