<?php
	require_once '../../includes/config.inc.php';
	if (isset($_POST['enable_capts']) || isset($_POST['max_capt_files'])) {
		$data = $_POST;
		$insert = update_configs($data, $front);
		echo $insert;
	}
	function update_configs( $data, $frontEnd = false )
	{
		global $db;
		#pr($data,true);

		if ( isset($data['enable_capts']) )
		{
			$enable_capts = 'yes';
		}
		else
		{
			$enable_capts = 'no';
		}

		$max_capt_files = mysql_clean($data['max_capt_files']);
		$max_capt_size = mysql_clean($data['max_capt_size']);
		$min_vid_len = mysql_clean($data['min_vid_len']);

		$db->update(tbl("honey_capt_configs"), array("enable_subs","max_sub_files","max_sub_file_size","min_vid_len"), array($enable_capts,$max_capt_files,$max_capt_size,$min_vid_len), "allowed_users!=''");
		$msg  = 'Honey Caption settings have been updated';
		return $msg;
	}

	if (isset($_FILES)) {
		upload_subtitle($front);
	}
?>