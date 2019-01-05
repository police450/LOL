<?php

	/**
	* Class: NinjaSEO
	* Description: Class used for handling most of stuff regarding SEO Ninja
	* Functions: Various
	* @author: Saqib Razzaq
	*/

	class NinjaSEO
	{
		var $ninja_configs = array();

		/**
		* Initializez plugin by creating admin menus and firing up anchors
		* @param: { none }
		*/

		function init($active)
		{
			$this->get_photo_meta();
			#$this->check_pr('allbloggingtips.com');
			#$this->check_alexa('allbloggingtips.com');
			global $Cbucket;
			add_admin_menu('SEO Ninja','License Settings','lisc_configs.php', SEO_NINJA.'/admin');
			if ($active) {
			add_admin_menu('SEO Ninja','Website Settings','website_configs.php', SEO_NINJA.'/admin');
				add_admin_menu('SEO Ninja','Social Settings','social_configs.php', SEO_NINJA.'/admin');
				add_admin_menu('SEO Ninja','SiteMap Settings','sitemap_configs.php', SEO_NINJA.'/admin');
				
				#add_admin_menu('SEO Ninja','Configurations','configs.php', SEO_NINJA.'/admin');
				add_admin_menu('SEO Ninja','Documentation','documentation.php', SEO_NINJA.'/admin');
			}
			$this->get_configs();
			$this->update_vid_seo();
			$this->update_photo_seo();
			assign("ninja_ajax", SEO_NINJA_URL.'/ninja_ajax.php');
			if ($this->show_page_seo())
			{
				assign("ninja_fighting", "yes");
			}
			assign("ninja_configs", $this->ninja_configs);
			assign("ninja_html_url", SEO_NINJA_HTML_URL);
			assign("ninjaless_configs", $Cbucket->configs);
		}

		/**
		* Get and assign all configruations of plugin
		* @return : { array } { $configs } { an array with all configs }
		*/

		function get_configs()
		{
			global $db;
			$configs = $db->select(tbl("seo_ninja"),"*","config_id != ''");
			if ( is_array( $configs ) )
			{
				$this->ninja_configs = $configs;
				$this->assign_configs($configs);
				return $configs;
			}
		}

		/**
		* Loops through cofigs array and assigns one by one
		* @param: { array } { $array } { array with all of configs }
		*/

		function assign_configs($configs)
		{
			if ( is_array( $configs ) )
			{
				foreach ($configs as $key => $value) {
					assign($value['name'], $value['value']);
				}
			}
		}

		function show_page_seo()
		{
			$pages = array('index','watch_video','view_item','videos','photos','channels', 'search_result', 'groups', 'collections');
			#echo THIS_PAGE;
			if (in_array(THIS_PAGE, $pages))
			{
				return true;
			}
			else 
			{
				return false;
			}
		}

		/**
		* Handles all of front end work
		*/

		function seo_ninja_all()
		{
			global $Cbucket;
			$this->index_page();
			if (THIS_PAGE == 'index')
			{
				$this->show_website_meta();
			}
			elseif (THIS_PAGE == '404')
			{
				#exit("Sd");
				echo "<title>Page Not Found</title>";
			}
			elseif (THIS_PAGE == 'watch_video')
			{
				if ( isset( $_GET['v'] ) )  
				{
					$key = $_GET['v'];
					$this->show_vid_meta($key);
				}
			}
			elseif (THIS_PAGE == 'view_item')
			{
				if (isset($_GET['item']))
				{
					$key = $_GET['item'];
					$this->show_photo_meta($key);
				}
			}
			elseif (THIS_PAGE == 'search_result')
			{
				$this->show_search_meta();
			}
			elseif (THIS_PAGE == 'videos')
			{
				$this->show_list_meta('videos');
			}
			elseif (THIS_PAGE == 'photos')
			{
				
				$this->show_list_meta('photos');
			}
			elseif (THIS_PAGE == 'channels')
			{
				$this->show_list_meta('channels');
			}
			elseif (THIS_PAGE == 'groups')
			{
				$this->show_list_meta('groups');
			}
			elseif (THIS_PAGE == 'collections')
			{
				$this->show_list_meta('collections');
			}
		}

		/**
		* Get value of given meta name
		* @param : { integer } { $elem_name } { name of element to get value for }
		* @return : { string } { $value } { value of vien element }
		*/

		function get_meta_val($elem_name)
		{
			$configs = $this->ninja_configs;
			foreach ($configs as $key => $value) {
				if ( $elem_name == $value['name'] )
				{
					return $value['value'];
				}
			}
		}

		/**
		* Shows meta on index (home) page of website
		*/

		function show_website_meta()
		{
			global $Cbucket;
			$this->default_cb_meta();
			echo $this->ninja_confirms().'
			<meta name="description" content="'.$this->get_meta_val("website_description").'"/>
			<meta name="keywords" content="'.$this->get_meta_val("website_keywords").'">
			<link rel="canonical" href="'.BASEURL.'" />
			<meta property="og:locale" content="en_US" />
			<meta property="og:type" content="website" />
			<meta property="og:title" content="'.$this->get_meta_val("fb_def_title").'"" />
			<meta property="og:description" content="'.$this->get_meta_val("fb_def_desc").'" />
			<meta property="og:url" content="'.BASEURL.'"/>
			<meta property="og:site_name" content="'.$Cbucket->configs["site_title"].'"/>
			<meta property="fb:app_id" content="564543806941972" /> 
			<meta property="og:image" content="'.$this->get_meta_val("fb_def_thumb").'" />
			<meta name="twitter:card" content="summary"/>
			<meta name="twitter:description" content="'.$this->get_meta_val("tw_def_desc").'"/>
			<meta name="twitter:title" content="'.$this->get_meta_val("tw_def_title").'"/>
			<meta name="twitter:site" content="@me"/>
			<meta name="twitter:image" content="'.$this->get_meta_val("tw_def_thumb").'"/>
			'.$this->show_seo_title("index").'
			<script type="application/ld+json">'.$this->build_app_json().'</script>
			<script type="application/ld+json">'.$this->build_app_json(true).'</script>
			';
		}

		/**
		* Gets meta data of video for watch_video page
		* @return : { array } { $raw_data } { meta of video }
		*/

		function get_vid_meta()
		{
			global $db;
			if ( isset( $_GET['v'] ) )
			{
				$video = $_GET['v'];
				if ( is_numeric( $video ) )
				{
					$type = 'id';
				}
				else
				{
					$type = 'key';
				}

				if ($type == 'key')
				{
					$raw = $db->select(tbl("video"),"videoid","videokey = '$video'");
					$video = $raw[0]['videoid'];
				}

				$raw_data = $db->select(tbl("ninja_items"),"*","item_id = '$video'");
				return $raw_data[0];
			}
		}

		function get_photo_meta()
		{
			if ( isset($_GET['item']) )
			{
				global $db;
				$photo = $_GET['item'];
				$raw = $db->select(tbl("photos"),"photo_id","photo_key = '$photo'");
				$photo_id = $raw[0]['photo_id'];
				if ( is_numeric($photo_id) ) 
				{
					$raw_data = $db->select(tbl("ninja_items"),"*","item_id = '$photo_id'");
					if (is_array($raw_data))
					{
						return $raw_data[0];
					}
				}
				else
				{
					return false;
				}
			}
		}

		/**
		* Builds default meta values to display
		*/

		function default_cb_meta()
		{
			echo '<meta name="copyright" content="ClipBucket - Integrated Units 2007 - 2016" />
            <meta name="author" content="Arslan Hassan - http://clip-bucket.com/arslan-hassan" />
            <meta name="author" content="Fawaz Tahir - http://clip-bucket.com/fawaz-tahir" />
            <link rel="shortcut icon" href="'.BASEURL.'/favicon.ico">
            <link rel="icon" type="image/ico" href="'.BASEURL.'/favicon.ico" />';
		}

		/**
		* Json info to show in header
		* @param : { boolean } { $person } { builds person related json if true }
		*/

		function build_app_json($person = false)
		{
			global $Cbucket;
			$app_json = array();
			$app_json['@context'] = 'ttp://schema.org';
			if ( !$person )
			{
				$app_json['@type'] = 'WebSite';
				$app_json['url'] = BASEURL;
				$app_json['name'] = $Cbucket->configs['site_title'];
				$app_json['potentialAction'] = array();
				$app_json['potentialAction']['@type'] = 'SearchAction';
				$app_json['potentialAction']['target'] = BASEURL.'/search_result.php?type=videos&query={search_term_string}';
				$app_json['query-input'] = 'required name=search_term_string';
				return json_encode($app_json);
			}
			else
			{
				$app_json['@type'] = $this->get_meta_val("owner_type");
				$app_json['url'] = BASEURL;
				$app_json['sameAs'] = array();
				$app_json['sameAs'][] = $this->get_meta_val("fb_url");
				$app_json['sameAs'][] = $this->get_meta_val("tw_url");
				$app_json['name'] = $this->get_meta_val("owner_name");
				return json_encode($app_json);
			}
		}

		/**
		* Assigns meta of all elements
		* @param : { array } { $data } { array with all configs }
		*/

		function assign_item_meta($data)
		{
			#pex($data,true);
			if (is_array($data))
			{
				foreach ($data as $key => $value) 
				{
					assign($key, $value);
				}
				assign("seo_title", $data['title']);
				assign("seo_description", $data['description']);
				assign("seo_keywords", $data['keywords']);
			}
		}

		/**
		* Displays edit form on single item pages
		*/

		function seo_item_edit()
		{
			$is_on = $this->get_meta_val("single_item_meta");
			if ($is_on == 'on')
			{
				if (isset($_GET['video']))
				{
					$vid = $_GET['video'];
					$meta = $this->get_item_meta($vid);
					$this->assign_item_meta($meta);
				}
				elseif (isset($_GET['photo']))
				{
					$photo = $_GET['photo'];
					$meta = $this->get_item_meta($photo, 'photo');
					#pex($meta,true);
					$this->assign_item_meta($meta);
				}
				Template(SEO_NINJA_HTML.'/seo_item_edit.html',false);
			}
		}
		
		function update_vid_seo()
		{
			global $db;
			if ( isset( $_POST['seo_title'] ) && isset( $_POST['videoid'] ) )
			{
				$data = $_POST;
				#pex($data,true);
				$item_id = $data['videoid'];
				$flds = array('item_id', 'item_type', 'title', 'description', 'keywords', 'fb_title', 'fb_desc', 'fb_thumb', 'tw_title', 'tw_desc', 'tw_thumb', 'tw_hashtag');
				$vals = array();
				$vals[] = $data['videoid'];
				$vals[] = 'video';
				$vals[] = $data['seo_title'];
				$vals[] = $data['seo_description'];
				$vals[] = $data['seo_keywords'];
				$vals[] = $data['fb_title'];
				$vals[] = $data['fb_desc'];
				$vals[] = $data['fb_thumb'];
				$vals[] = $data['tw_title'];
				$vals[] = $data['tw_desc'];
				$vals[] = $data['tw_thumb'];
				$vals[] = $data['tw_hashtag'];
				$meta_check = $this->got_meta($data['videoid']);
				if ($meta_check)
				{
					$db->update(tbl("ninja_items"), $flds, $vals, "item_id = '$item_id'");
				}
				else
				{
					$db->insert(tbl("ninja_items"), $flds, $vals);
				}
			}
		}

		function update_photo_seo()
		{
			global $db;
			if ( isset( $_POST['seo_title'] ) && isset( $_POST['photo_id'] ) )
			{
				$data = $_POST;
				#pex($data,true);
				$item_id = $data['photo_id'];
				$flds = array('item_id', 'item_type', 'title', 'description', 'keywords', 'fb_title', 'fb_desc', 'fb_thumb', 'tw_title', 'tw_desc', 'tw_thumb', 'tw_hashtag');
				$vals = array();
				$vals[] = $data['photo_id'];
				$vals[] = 'photo';
				$vals[] = $data['seo_title'];
				$vals[] = $data['seo_description'];
				$vals[] = $data['seo_keywords'];
				$vals[] = $data['fb_title'];
				$vals[] = $data['fb_desc'];
				$vals[] = $data['fb_thumb'];
				$vals[] = $data['tw_title'];
				$vals[] = $data['tw_desc'];
				$vals[] = $data['tw_thumb'];
				$vals[] = $data['tw_hashtag'];
				$meta_check = $this->got_meta($data['photo_id']);
				if ($meta_check)
				{
					$db->update(tbl("ninja_items"), $flds, $vals, "item_id = '$item_id'");
				}
				else
				{
					$db->insert(tbl("ninja_items"), $flds, $vals);
				}
			}
		}

		/**
		* Fetches meta of an item from database
		* @param : { integer } { $id } { item id }
		* @param : { string } { $type } { photo or video }
		* @return : { array } { $item_meta }
		*/

		function get_item_meta($id, $type = 'video')
		{
			global $db;
			if ( is_numeric( $id ) )
			{
				$item_meta = $db->select(tbl("ninja_items"),"*","item_id = '$id' AND item_type = '$type'");
				if (is_array($item_meta))
				{
					return $item_meta[0];
				}
			}
		}

		function got_meta($item_id)
		{
			global $db;
			$data = $db->select(tbl("ninja_items"),"config_id","item_id = '$item_id'");
			$config_id = $data[0]['config_id'];
			if ($config_id)
			{
				return $config_id;
			}
			else
			{
				return false;
			}
		}

		function default_metas()
		{
			global $Cbucket;
			echo '<meta name="description" content="'.$this->get_meta_val("website_description").'"/>
			<meta name="keywords" content="'.$this->get_meta_val("website_keywords").'">
			<link rel="canonical" href="'.BASEURL.'" />
			<meta property="og:locale" content="en_US" />
			<meta property="og:type" content="website" />
			<meta property="og:title" content="'.$this->get_meta_val("fb_def_title").'"" />
			<meta property="og:description" content="'.$this->get_meta_val("fb_def_desc").'" />
			<meta property="og:url" content="'.BASEURL.'"/>
			<meta property="og:site_name" content="'.$Cbucket->configs["site_title"].'"/>
			<meta property="og:image" content="'.$this->get_meta_val("fb_def_thumb").'" />
			<meta name="twitter:card" content="summary"/>
			<meta name="twitter:description" content="'.$this->get_meta_val("tw_def_desc").'"/>
			<meta name="twitter:title" content="'.$this->get_meta_val("tw_def_title").'"/>
			<meta name="twitter:site" content="@me"/>
			<meta name="twitter:image" content="'.$this->get_meta_val("tw_def_thumb").'"/>';
		}

		function show_vid_meta($id)
		{
			global $Cbucket, $cbvid;
			$data = $this->get_vid_meta($id);
			#pex($data,true);
			$vid_title = $data['title'];
			if ( empty( $vid_title ) ) 
			{
				$data = $cbvid->get_video($id);
				$vid_title = $this->show_seo_title('watch_video', $data);
			}
			else
			{
				$vid_title = $this->show_seo_title('watch_video', $data);
			}

			echo '<link rel="canonical" href="http://'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI].'" />
			<meta property="fb:app_id" content="564543806941972" /> 
				<meta property="og:locale" content="en_US" />
				<meta property="og:type" content="video" />
				<meta property="og:title" content="'.$data["fb_title"].'" />
				<meta property="og:description" content="'.$data["fb_desc"].'" />
				<meta property="og:url" content="http://'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI].'" />
				<meta property="og:site_name" content="'.$Cbucket->configs["site_title"].'" />
				<meta property="article:publisher" content="'.$this->get_meta_val("fb_url").'" />
				<meta property="article:section" content="Uncategorized" />
				<meta property="article:published_time" content="'.$vdo.'" />
				<meta property="og:image" content="'.$data["fb_thumb"].'" />
				<meta name="twitter:card" content="summary"/>
				<meta name="twitter:description" content="'.$data["tw_description"].'"/>
				<meta name="twitter:title" content="'.$data["tw_title"].'"/>
				<meta name="twitter:site" content="'.$this->get_meta_val("tw_url").'"/>
				<meta name="twitter:image" content="'.$data["tw_thumb"].'"/>
				<meta name="twitter:creator" content="'.$this->get_meta_val("tw_url").'"/>
				<title>'.$vid_title.'</title>';
				;
		}

		function show_photo_meta($id)
		{
			global $Cbucket, $cbphoto;
			$data = $this->get_photo_meta($id);

			$photo_title = $data['title'];
			if ( empty( $photo_title ) ) 
			{
				$data = $cbphoto->get_photo($id);
				#pex($data,true);
				$photo_title = $this->show_seo_title('view_item', $data);
			}
			else
			{
				$photo_title = $this->show_seo_title('view_item', $data);
			}

			echo '<link rel="canonical" href="on_hold" />
				<meta property="og:locale" content="en_US" />
				<meta property="og:type" content="video" />
				<meta property="og:title" content="'.$data["fb_title"].'" />
				<meta property="og:description" content="'.$data["fb_description"].'" />
				
				<meta property="og:site_name" content="'.$Cbucket->configs["site_title"].'" />
				<meta property="article:publisher" content="'.$this->get_meta_val("fb_url").'" />
				<meta property="article:section" content="Uncategorized" />
				<meta property="article:published_time" content="'.$vdo.'" />
				<meta property="og:image" content="'.$data["fb_thumb"].'" />
				<meta name="twitter:card" content="summary"/>
				<meta name="twitter:description" content="'.$data["tw_description"].'"/>
				<meta name="twitter:title" content="'.$data["tw_title"].'"/>
				<meta name="twitter:site" content="'.$this->get_meta_val("tw_url").'"/>
				<meta name="twitter:image" content="'.$data["tw_thumb"].'"/>
				<meta name="twitter:creator" content="'.$this->get_meta_val("tw_url").'"/>
				<title>'.$photo_title.'</title>';
				;
		}

		function show_search_meta()
		{
			$this->default_metas();
			if (isset($_GET['query']))
			{
				if (isset($_GET['type']))
				{
					$q = $_GET['query'];
					$type = strtolower($_GET['type']);
					$this->show_seo_title('search_result');
				}
			}
		}

		function show_list_meta($type)
		{
			$this->default_metas();
			$this->show_seo_title($type);
		}

		function build_list_title($type)
		{
			global $cbvid,$cbgroup,$cbcollection,$userquery;
			$buid_title = '<title> '.ucfirst($type);
			if ( isset($_GET['cat']) )
			{
				if (strtolower($_GET['cat']) != 'all')
				{
					switch (strtolower($type)) {
						case 'videos':
							$cat = $cbvid->get_category($_GET['cat']);
							break;
						case 'photos':
							# code...
							break;
						case 'channels':
							$cat = $userquery->get_category($_GET['cat']);
							break;
						case 'collections':
							$cat = $cbcollection->get_category($_GET['cat']);
							break;
						case 'groups':
							$cat = $cbgroup->get_category($_GET['cat']);
							break;
						
						default:
							# code...
							break;
					}
					$cat = $cat['category_name'];
					$buid_title .= ' - Category '.$cat;
				}
			}
			$buid_title .= ' - List by '.$_GET['sort'];
			$buid_title .= ' - '.$this->get_meta_val("website_title");
			$buid_title .= '</title>';
			echo $buid_title;
		}

		function show_seo_title($page, $data = false) 
		{
			global $Cbucket, $cbvid;
			switch ($page) {
				case 'index':
					echo '<title>'.$this->get_meta_val("website_title").' - '.$this->get_meta_val("website_description").' </title>';
					break;
				case 'search_result':
					echo '<title>Search results for '.$_GET["query"].' in '.$_GET["type"].' - '.$this->get_meta_val("website_title").'</title>';
					break;
				case 'videos':
					$this->build_list_title('Videos');
					break;
				case 'photos':
					$this->build_list_title('Photos');
					break;
				case 'channels':
					$this->build_list_title('Channels');
					break;
				case 'groups':
					$this->build_list_title('Groups');
					break;
				case 'collections':
					$this->build_list_title('collections');
					break;
				case 'watch_video':	
					echo '<title>'.$data["title"].' '.$data["title_sep"].' - '.$this->get_meta_val("website_title").'</title>';
					break;
				case 'view_item':
					if (!empty($data['photo_title']))
					{
					echo	'<title>'.$data["photo_title"].' '.$data["title_sep"].' - '.$this->get_meta_val("website_title").'</title>';
					}
					else
					{
						echo	'<title>'.$data["title"].' '.$data["title_sep"].' - '.$this->get_meta_val("website_title").'</title>';
					}
					break;
				case 'view_channel':
					# code...
					break;
				case 'view_collection':
					# code...
					break;
				default:
					# code...
					break;
			}
		}

		function google_confirm()
		{
			$id = $this->get_meta_val("google_id");
			if ( strlen( $id ) > 10 ) 
			{
				return $id;
			}
		}

		function alexa_confirm()
		{
			$id = $this->get_meta_val("alexa_id");
			if ( strlen( $id ) > 10 ) 
			{
				return $id;
			}
		}

		function bing_confirm()
		{
			$id = $this->get_meta_val("bing_id");
			if ( strlen( $id ) > 10 ) 
			{
				return $id;
			}
		}

		function ninja_confirms()
		{
			$str = $this->google_confirm();
			$str .= $this->alexa_confirm();
			$str .= $this->bing_confirm();
			return $str;
		}

		/**
		* Checks Google Page rank for the given url
		* @param : { string } { $url } { url of website }
		* @return : { integer } { $goole_pr } { google page rank for url }
		*/

		function check_pr($url)
		{
			global $google_pr;
			if ( !empty( $url ) )
			{
				if (is_numeric($google_pr->get_google_pagerank($url)))
				{
					return $google_pr->get_google_pagerank($url);
				}
			}
		}

		/**
		* Checks Alexa rank for the given url
		* @param : { string } { $url } { url of website }
		* @return : { integer } { $globalrank } { alexa rank for url }
		*/


		function check_alexa($url)
		{
			$url = file_get_contents('http://data.alexa.com/data?cli=10&dat=snbamz&url='.$url);
			preg_match('/\<popularity url\="(.*?)" text\="([0-9]+)" source\="panel"\/\>/si', $url, $matches);
			$globalrank = $matches[2];
			if ( is_numeric( $globalrank ) )
			{
				return $globalrank;
			}
		}

		/**
		* Check domain authority of a website
		* @param : { string } { $url } { url of website to check }
		*/

		function check_da($url)
		{

		}

		/**
		* Discourage search engines from indexing page
		* @param : { none : all stuff handled inside }
		*/

		function index_page()
		{
			$index_vids = $this->get_meta_val("index_videos");
			$index_photos = $this->get_meta_val("index_photos");
			$index_chans = $this->get_meta_val("index_channels");
			$index_cats = $this->get_meta_val("index_categories");

			switch (THIS_PAGE) 
			{
				case 'videos':
				case 'watch_video':
					if ($index_vids != 'on') 
					{
						echo '<meta name="robots" content="noindex">';
					}
					break;
				case 'photos':
				case 'view_item':
					if ($index_photos != 'on') 
					{
						echo '<meta name="robots" content="noindex">';
					}
					break;
				case 'channels':
				case 'view_channel':
					if ($index_chans != 'on') 
					{
						echo '<meta name="robots" content="noindex">';
					}
					break;

				default:
					# code...
					break;
			}
		}

		function html_sitemap()
		{
			echo $this->build_vids_list();
			echo $this->build_photos_list();
			echo $this->build_chans_list();
			echo $this->build_cats_list();
			echo $this->build_pages_list();
		}

		function build_vids_list()
		{
			$params = array();
			$params['limit'] = 20;
			$data = get_videos($params);
			$str = '<h1>Recent Videos</h1><ul>';
			foreach ($data as $key => $value) 
			{
				$str .= '<li><a href='.BASEURL.'/watch_video.php?v='.$value["videokey"].'>'.$value["title"].'</a></li>';
			}
			$str .'</ul>';
			return $str;
		}

		function build_photos_list()
		{
			$params = array();
			$params['limit'] = 20;
			$data = get_photos($params);
			$str = '<h1>Recent Photos</h1><ul>';
			foreach ($data as $key => $value) 
			{
				$str .= '<li><a href='.BASEURL.'/view_item.php?item='.$value["photo_key"].'&type=photos&collection='.$value["collection_id"].'>'.$value["photo_title"].'</a></li>';
			}
			$str .'</ul>';
			#$exit($str);
			return $str;
		}

		function build_chans_list()
		{
			$params = array();
			$params['limit'] = 20;
			$data = get_users($params);
			$str = '<h1>Channels</h1><ul>';
			foreach ($data as $key => $value) 
			{
				$str .= '<li><a href='.BASEURL.'/user/'.$value["username"].'>'.ucfirst($value["username"]).'</a></li>';
			}
			$str .'</ul>';
			#$exit($str);
			return $str;
		}

		function build_cats_list()
		{
			global $cbvid;
			$params = array();
			$params['limit'] = 20;
			$data = $cbvid->get_categories($params);
			$str = '<h1>Video Categories</h1><ul>';
			foreach ($data as $key => $value) 
			{
				$str .= '<li><a href='.BASEURL.'/videos.php?cat='.$value["category_id"].'>'.ucfirst($value["category_name"]).'</a></li>';
			}
			$str .'</ul>';
			#$exit($str);
			return $str;
		}

		function build_pages_list()
		{
			global $cbpage;
			$params = array();
			$params['limit'] = 20;
			$data = $cbpage->get_pages($params);
			$str = '<h1>Pages</h1><ul>';
			foreach ($data as $key => $value) 
			{
				$str .= '<li><a href='.BASEURL.'/view_page.php?pid='.$value["page_id"].'>'.ucfirst($value["page_name"]).'</a></li>';
			}
			$str .'</ul>';
			#$exit($str);
			return $str;
		}

		function xml_sitemap()
		{
			global $cbvid;
			$str = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1" xmlns:user="http://www.google.com/schemas/sitemap-user/1.1">';
			$vids = get_videos();
			#pex($vids,true);
			foreach ($vids as $key => $value) 
			{
				echo "<url>
				<loc>".BASEURL.'/watch_video.php?v='.$value['videokey']."</loc>
				<lastmod>".$value['date_added']."</lastmod>
				<changefreq>weekly</changefreq>
				<video:video>
					<video:thumbnail_loc><![CDATA[".get_thumb($value)."]]></video:thumbnail_loc>
					<video:title><![CDATA[".$value['title']."]]></video:title>
					<video:description><![CDATA[Story of a Muslim Guy in Amitabh Bachan&#8217;s Show Will Make You Cry]]></video:description>
					<video:view_count>0</video:view_count>
					<video:publication_date>2016-02-11T15:22:22+05:00</video:publication_date>
					<video:tag><![CDATA[]]></video:tag>
					<video:category><![CDATA[Entertainment]]></video:category>
					<video:duration>393.66</video:duration>
				</video:video>
			</url>";	
			}
		}
	}
?>