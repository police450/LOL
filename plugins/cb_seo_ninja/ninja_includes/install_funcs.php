<?php
	function build_ninja()
	{
		global $db;
		$db->Execute(
		'CREATE TABLE IF NOT EXISTS '.tbl("seo_ninja")." (
		  `config_id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `name` varchar(225) NOT NULL,
		  `value` varchar(225) NOT NULL,
		  PRIMARY KEY (`config_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;"
		);

		$db->Execute(
		'CREATE TABLE IF NOT EXISTS '.tbl("ninja_items")." (
		  `config_id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `item_id` varchar(225) NOT NULL,
		  `item_type` varchar(225) NOT NULL,
		  `title` text NOT NULL,
		  `description` text NOT NULL,
		  `keywords` text NOT NULL,
		  `fb_title` text NOT NULL,
		  `fb_desc` text NOT NULL,
		  `fb_thumb` text NOT NULL,
		  `fb_hashtag` text NOT NULL,
		  `fb_mention` text NOT NULL,
		  `tw_title` text NOT NULL,
		  `tw_desc` text NOT NULL,
		  `tw_thumb` text NOT NULL,
		  `tw_hashtag` text NOT NULL,
		  `tw_mention` text NOT NULL,
		  `google_title` text NOT NULL,
		  `google_desc` text NOT NULL,
		  `google_thumb` text NOT NULL,
		  `google_hashtag` text NOT NULL,
		  `google_mention` text NOT NULL,
		  `default_share_title` text NOT NULL,
		  `default_share_desc` text NOT NULL,
		  `default_share_thumb` text NOT NULL,
		  PRIMARY KEY (`config_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;"
		);

		$db->Execute("CREATE TABLE IF NOT EXISTS ".tbl('seo_ninja_lisc_configs')." (
		  `config_id` int(20) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) NOT NULL DEFAULT 0,
		  `value` text NOT NULL,
		  PRIMARY KEY (`config_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;");

		$db->Execute("INSERT INTO ".tbl('seo_ninja_lisc_configs')." (`config_id`, `name`, `value`) VALUES
		(1, 'license_key', 'you_liscence_here'),
		(2, 'license_local_key', 'XXX'),
		(3, 'success_ip', 'blah');");

		$db->Execute("INSERT INTO  ".tbl('seo_ninja')." (name, value) VALUES ('website_title','your_seo_title_here'), ('website_description','your_seo_description_here'), ('website_keywords','your_seo_keywords_here'), 
			('alexa_id',''),
			('google_id',''),
			('bing_id',''),
			('index_videos','yes'),
			('index_photos','yes'),
			('index_channels','yes'),
			('index_categories','yes'),
			('single_item_meta','yes'),
			('single_vid_analysis','yes'),
			('daily_mail','no'),
			('fb_url','your_page_url_here'),
			('fb_def_thumb','fb_default_thumb_url'),
			('fb_def_title','fb_default_share_title'),
			('fb_def_desc','fb_default_share_description'),
			('fb_auto_post','no'),
			('tw_url','your_twitter_url_here'),
			('tw_def_thumb','twitter_default_thumb_url'),
			('tw_def_title','twitter_default_share_title'),
			('tw_def_desc','twitter_default_share_description'),
			('tw_auto_post','no'),
			('google_def_thumb','google_plus_default_thumb_url'),
			('google_def_title','google_plus_default_share_title'),
			('google_def_desc','google_plus_default_share_description'),
			('google_auto_post','no'),
			('max_items_sitemap','500'),
			('sitemap_submit_after','1 week'),
			('auto_submit_pop','yes'),
			('auto_submit_google','yes'),
			('auto_submit_bing','yes'),
			('external_nofollow','yes'),
			('title_separator','-'),
			('owner_name','your_name_here'),
			('owner_type','Person')
			");
	}

	function create_robots()
	{
		if (is_writable(BASEDIR))
		{
			$content = "User-agent: * \n";
			$content .= "Disallow: /cgi-bin/ \n";
			$content .= "Disallow: /files/videos \n";
			$content .= "Disallow: /files/photos \n";
			$content .= "Disallow: /cache/ \n";
			$content .= "Disallow: /cb_install/ \n";
			$content .= "Disallow: /admin_area/ \n";
			$content .= "Disallow: /api/ \n";
			$content .= "Disallow: /actions/ \n";
			$content .= "Disallow: /includes/ \n";
			$file = BASEDIR.'/robots.txt';
			#exit($file);
			file_put_contents($file, $content);
			chmod($file, 0777);
		}
	}

	function kill_ninja()
	{
		global $db;
		$db->Execute('DROP TABLE '.tbl("seo_ninja"));
		$db->Execute('DROP TABLE '.tbl("ninja_items"));
	}
?>