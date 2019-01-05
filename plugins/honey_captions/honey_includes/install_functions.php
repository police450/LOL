<?php


	function create_capts_dir()
	{
		$main_dir = BASEDIR.'/files/captions';
		mkdir($main_dir);
		$year = date("Y");

		$build_year_dir = $main_dir.'/'.$year;

		$month = date("m");

		$build_month_dir = $main_dir.'/'.$year.'/'.$month;

		$day = date("d");

		$build_day_dir = $main_dir.'/'.$year.'/'.$month.'/'.$day;

		if ( !file_exists( $build_year_dir ) )
		{
			mkdir($build_year_dir);
		}

		if ( !file_exists( $build_month_dir ) )
		{
			mkdir( $build_month_dir );
		}
		
		if ( !file_exists( $build_day_dir ) )
		{
			mkdir( $build_day_dir );
		}
		
		$build_dir = $year.'/'.$month.'/'.$day;
		
		return $build_dir;
	}

	function delete_directory($dirname) 
	{
        if (is_dir($dirname))
           $dir_handle = opendir($dirname);
	 	if (!$dir_handle)
	      return false;
		while($file = readdir($dir_handle)) 
		{
	       if ($file != "." && $file != "..") 
	       {
	            if (!is_dir($dirname."/".$file))
	                 unlink($dirname."/".$file);
	            else
	                 delete_directory($dirname.'/'.$file);
	       }
		}
		closedir($dir_handle);
		rmdir($dirname);
		return true;
	}

	function install_honey_capt()
	{
		global $db;

		create_capts_dir();

		$db->Execute("ALTER TABLE ".tbl("video")." ADD `has_subs` ENUM('yes','no') NOT NULL DEFAULT 'no'");

		$db->Execute("ALTER TABLE ".tbl("video")." ADD `total_subs` int NOT NULL DEFAULT '0'");

		$db->Execute("ALTER TABLE ".tbl("video")." ADD `last_sub_num` int NOT NULL DEFAULT '0'");
		
		// Creating Table To Store Facebook API Credentials
		$db->Execute(
		'CREATE TABLE IF NOT EXISTS '.tbl("honey_capt_subs").' (
		`subid` int(20) NOT NULL AUTO_INCREMENT,
		`videoid` int NOT NULL DEFAULT "0",
		`userid` int NOT NULL DEFAULT "1",
		`file_name` TEXT NOT NULL ,
		`file_path` TEXT NOT NULL,
		`file_language` TEXT NOT NULL,
		`default_sub` TEXT NOT NULL,
		 PRIMARY KEY (subid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;;'
		);


		$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('honey_capt_configs')." (
		  `enable_subs` ENUM('yes','no') NOT NULL DEFAULT 'yes',
		  `max_sub_files` int NOT NULL DEFAULT '2',
		  `max_sub_file_size` int NOT NULL DEFAULT '100',
		  `min_vid_len` int NOT NULL DEFAULT '0',
		  `total_subs` int(20) NOT NULL,
		  `vids_with_subs` int(20) NOT NULL,
		  `allowed_users` text NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;;"
		);

		$db->Execute("INSERT INTO  ".tbl('honey_capt_configs')." (enable_subs,max_sub_files,max_sub_file_size,min_vid_len,total_subs,vids_with_subs,allowed_users) VALUES ('yes','2','100','0','0','0','all')");
		
		$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('honey_capt_lisc_configs')." (
		  `config_id` int(20) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) NOT NULL,
		  `value` text NOT NULL,
		  PRIMARY KEY (`config_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;");

		$db->Execute("INSERT INTO ".tbl('honey_capt_lisc_configs')." (`config_id`, `name`, `value`) VALUES
		(1, 'license_key', 'you_liscence_here'),
		(2, 'license_local_key', 'XXX'),
		(3, 'success_ip', 'blah');");

	}

	function rm_honey_capt() 
	{
		global $db;

		$db->Execute("ALTER TABLE ".tbl("video")." DROP `has_subs` ");
		$db->Execute("ALTER TABLE ".tbl("video")." DROP `total_subs` ");
		$db->Execute("ALTER TABLE ".tbl("video")." DROP `last_sub_num` ");
		
		$db->Execute(
		'DROP TABLE '.tbl("honey_capt_subs")
		);

		$db->Execute(
		'DROP TABLE '.tbl("honey_capt_configs")
		);

		$db->Execute(
		'DROP TABLE '.tbl("honey_capt_lisc_configs")
		);

		delete_directory(BASEDIR.'/files/captions');

		/*$db->Execute(
		'DROP TABLE '.tbl("honey_capt_subs")
		);*/
	}

?>