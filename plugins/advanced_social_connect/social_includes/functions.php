<?php
/**
* 
*/
	function generateRandomString($length = 3) 
	{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) 
    {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
	}

	function fire_smarty_anchors() 
	{
		// Registring Display Facebook Login Button Function For Smarty And Anchoring
		register_anchor_function("social_connect_box","social_connect_box");

		// Registring Display Facebook Login Button Function For Smarty And Anchoring
		register_anchor_function("social_connect_boxe","social_connect_boxe");

		// Registring Display Facebook Login Button Function For Smarty And Anchoring
		register_anchor_function("show_facebook","show_facebook");
		//	$Smarty->unregister_function('display_fbbt_on_page','display_fbbt_on_page');

		// Registring Display Facebook Login Button Function For Smarty And Anchoring
		register_anchor_function("show_google","show_google");

		// Registring Display Twitter Login Button Function For Smarty And Anchoring
		register_anchor_function("show_twitter","show_twitter");

		register_anchor_function("twit_init_login","twit_init_login");
		// Registring Display Twitter Login Button Function For Smarty And Anchoring
		register_anchor_function("show_linkedin","show_linkedin");

		// Registering anchor for a function that will display all buttons together
		register_anchor_function("show_all_btns", "show_all_btns");

	}

	function fire_admin_menus() 
	{
		add_admin_menu('Advanced Social Connects','Plugin Liscence','lisc_update.php', SOCIAL_CON_BASE.'/admin');
		add_admin_menu('Advanced Social Connects','Facebook API Configs','fb_configs.php', SOCIAL_CON_BASE.'/admin');
		add_admin_menu('Advanced Social Connects','Google API Configs','google_configs.php', SOCIAL_CON_BASE.'/admin');
		add_admin_menu('Advanced Social Connects','Twitter API Configs','twit_configs.php', SOCIAL_CON_BASE.'/admin');
		add_admin_menu('Advanced Social Connects','Linkedin API Configs','linkedin_configs.php', SOCIAL_CON_BASE.'/admin');
	}

	function fire_lisc_menu()
	{
		add_admin_menu('Advanced Social Connects','Plugin Liscence','lisc_update.php', SOCIAL_CON_BASE.'/admin');
	}

	function fb_configs()
	{
		global $db;
		$results = $db->select(tbl("socialconn_configs"),"*", "app_id='fbapp'");
		return $results;
	}

	function gm_configs()
	{
		global $db;
		$results = $db->select(tbl("socialconn_configs"),"*", "app_id='gmapp'");
		return $results;
	}

	function tw_configs()
	{
		global $db;
		$results = $db->select(tbl("socialconn_configs"),"*", "app_id='twapp'");
		return $results;
	}

	function lnk_configs()
	{
		global $db;
		$results = $db->select(tbl("socialconn_configs"),"*", "app_id='lnkapp'");
		return $results;
	}

	function cbsc_lisc_configs()
	{
		global $db;
		$results = $db->select(tbl("config"),"*", "name='cbsc_license'");
		return $results;
	}

	function update_lisc_key($lisckey)
	{
		global $db;
		$lisckey = $lisckey;
		$db->Execute("UPDATE ".tbl("socialconn_lisc_configs")." SET value='$lisckey' WHERE  config_id='1'");
	}

	function define_constants() 
	{
		// Getting User App Setting Details From DB To Create User Authenication
		global $db;
		$fbconfigs = array();
		$fbconfigs = $db->select(tbl("socialconn_configs"),"*", "app_id='fbapp'");
		foreach($fbconfigs as $cfbconfig)
		{
			$fbp_appkey = $cfbconfig['app_key'];
			$fbp_appsec = $cfbconfig['app_secret'];
			$fbp_license = $cfbconfig['license_key'];
		}
		
		// Assigning FB Login Variables For Application Access
		define('FBP_APPKEY', $fbp_appkey);
		define('FBP_SECRET',$fbp_appsec);
		define('FBP_LICENSE_KEY',$fbp_license);
		
		assign('fb_appkey', FBP_APPKEY);
		assign('fb_appsecret',FBP_SECRET);
		assign('fb_lickey',FBP_LICENSE_KEY);


		$gmconfigs = array();
		$gmconfigs = $db->select(tbl("socialconn_configs"),"*", "app_id='gmapp'");
		foreach($gmconfigs as $gmconfig)
		{
			$gm_client_key = $gmconfig['app_key'];
			$gm_client_secret = $gmconfig['app_secret'];
			$gm_dev_key = $gmconfig['dev_key'];
			$gm_red_url = $gmconfig['red_url'];
		}
		
		// Assigning GM Login Variables For Application Access
		define('GOOGLE_KEY', $gm_client_key);
		define('GOOGLE_SECRET', $gm_client_secret);
		define('GM_DEV_KEY', $gm_dev_key);
		define('GOOGLE_REDIR_URL', $gm_red_url);

		assign('gm_client_key', GOOGLE_KEY);
		assign('gm_client_secret', GOOGLE_SECRET);
		assign('gm_dev_key', GM_DEV_KEY);
		assign('gm_red_url', GOOGLE_REDIR_URL);



		$twconfigs = array();
		$twconfigs = $db->select(tbl("socialconn_configs"),"*", "app_id='twapp'");
		foreach($twconfigs as $twconfig)
		{
			$tw_client_key = $twconfig['app_key'];
			$tw_client_secret = $twconfig['app_secret'];
			$tw_red_url = $twconfig['red_url'];
		}
		
		// Assigning TW Login Variables For Application Access
		define('TW_CKEY', $tw_client_key);
		define('TW_CSECRET', $tw_client_secret);
		define('TW_RED_URL', $tw_red_url);

		assign('tw_client_key', TW_CKEY);
		assign('tw_client_secret', TW_CSECRET);
		assign('tw_red_url', TW_RED_URL);


		$liconfigs = array();
		$liconfigs = $db->select(tbl("socialconn_configs"),"*", "app_id='lnkapp'");
		foreach($liconfigs as $liconfig)
		{
			$li_appkey = $liconfig['app_key'];
			$li_appsec = $liconfig['app_secret'];
		}

		// Assigning LI Login Variables For Application Access
		define('LI_APPKEY', $li_appkey);
		define('LI_SECRET', $li_appsec);
		define("LI_REDIR", BASEURL.'/signup.php');
		
		assign('li_appkey', LI_APPKEY);
		assign('li_appsec', LI_SECRET);	
		assign('li_redir', LI_REDIR);	
	}

	function show_all_btns()
	{
		global $Cbucket;
		$templateNow = $Cbucket->configs['template_dir'];

		show_google();
		echo "&nbsp;";
		show_facebook();
		echo "&nbsp;";
		if ($templateNow != 'cb_28') {
		echo "<br/>";
		echo "<br/>";
		}
		show_twitter();
		echo "&nbsp;";
		show_linkedin();
	}

	function str_strip_user( $social_account_id, $network )
	{

		global $db;
		$results = $db->select(tbl("users"),"userid", "social_account_id='$social_account_id' AND soclid='$network'");
		$userid = $results[0]['userid'];
		$username = $results[0]['username'];

		if ( !empty($userid) )
		{
			return $userid;
		}
		else
		{
			return false;
		}

		/*$username_len = strlen($username);
		$strip = $username_len - 4;
		$stripped_user = substr($username, 0, $strip);
		pr($stripped_user,true);*/


	}

	function social_user_exists( $user, $network )
	{

		global $db;
		$results = $db->select(tbl("users"),"*", "username='$user' AND soclid='$network'");

		if ( !empty($results) )
		{
			$userid = $results[0]['userid'];
			return $userid;
		}
		else
		{
			return false;
		}
	}

	function add_soclid( $soclid, $social_account_id, $username )
	{
		global $db;
		$db->update(tbl("users"), array("soclid","social_account_id"), array($soclid, $social_account_id), "username='$username'");
		return $username;
	}

	function add_social_avatar( $user, $social_account_id, $avatar_url )
	{
		global $db;
		$db->update(tbl("users"), array("avatar_url"), array($avatar_url), "username='$user' AND social_account_id='$social_account_id'");
	}

	function activate_social_user( $userid ,$about_me=false)
	{
		global $db;
		$db->update(tbl("users"), array("usr_status"), array('Ok'), "userid='$userid'");
		if ($about_me){
			$db->update(tbl("user_profile"), array("about_me"), array($about_me), "userid='$userid'");
		}
	}

	function is_mail_reg( $user_email )
	{
		global $db;
		$results = $db->select(tbl("users"),"username", "email='$user_email'");

		if ( !empty($results) )
		{
			$db_email = $results[0]['username'];
			
			if ( !empty($db_email) )
			{
				return $db_email;
			}
			else 
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	function social_login_pre( $data )
	{
		global $userquery;

		$social_network = $data['soclid'];
		$social_account_id = $data['social_account_id'];
		$user = $data['username'];
		$image = $data['avatar_url'];
		$about_me = $data['about_me'];
		# pr($data,true);
		if ( empty($data['password']) )
		{
			$data = dummy_fields( $data );
		}

		if ( empty($social_network) )
		{
			e("Something is technically wrong.. could't determine social network.",e);
			return false;
		} 
		elseif ( empty($user) )
		{
			e("Something is technically wrong. Couldn't determine social username.",e);
			return false;
		}
		else
		{
			$social_user_id = social_user_exists( $user, $social_network );

			if ( !$social_user_id )
			{
				$stripped_social = str_strip_user( $social_account_id, $social_network );
			}

			if ( !user_exists( $user ) )
			{
				#exit("NO USER FOUND");
				$userid = $userquery->signup_user($data,true);
				add_soclid( $social_network, $social_account_id, $user );
				add_social_avatar( $user, $social_account_id, $image );
				activate_social_user( $userid ,$about_me);
				$userquery->login_as_user($userid);
				header("Location: ".BASEURL.'/myaccount.php?user='.$userid);
				return $userid;
			}
			elseif ( user_exists( $user ) && !$social_user_id )
			{
				
				#exit('FOUND USER but he is not social');
				if ( !$stripped_social )
				{
					if ( is_mail_reg( $data['email'] ) )
					{
						e("An account with same email address <strong>(".$data['email'].")</strong> exists. If it is you, kindly login using that account, otherwise contact our support team");
					}

					$data['username'] = $user.'_'.strtolower(generateRandomString());
					
					$username = $userquery->signup_user($data,true);
					$userid = add_soclid( $social_network, $social_account_id, $data['username'] );
					add_social_avatar( $user, $social_account_id, $image );
					activate_social_user( $userid, $about_me );
					$userquery->login_as_user($userid);
					header("Location: ".BASEURL.'/myaccount.php?user='.$userid);
					return $userid;
				}
				else 
				{
					$userquery->login_as_user($stripped_social);
					header("Location: index.php");
				}
				

			}
			elseif ( $social_user_id )
			{
				$userquery->login_as_user($social_user_id);
				header("Location: index.php");
			}
			else 
			{
				e("Username <strong>".$user."</strong> already in use",e);
			}
		}
	}

	function dummy_fields( $array )
	{
		$array['password'] = generateRandomString(10);
		$array['cpassword'] = $array['password'];
		$array['active'] = 'Ok';
		$array['country'] = 'PK';
		$array['gender'] = 'Male';
		$array['dob'] = '1989-10-14';
		$array['category'] = 1;
		$array['agree'] = 'yes';
		$array['signup'] = 'Signup';

		return $array;
	}

	function login_social( $data )
	{
		$social_network = $data['soclid'];
		$user = $data['username'];
		$image = $data['image'];

		social_login_pre( $data );
	}

	function network_trigger()
	{
		if ( isset($_GET['twit_login']) )
		{
			twit_init_login();
		}
		elseif ( isset($_GET['google_login']) )
		{
			$data = google_init_login( GOOGLE_KEY, GOOGLE_SECRET, GOOGLE_REDIR_URL );

			if ( isset($_SESSION['google_data']) )
			{
				login_social( $data );
			}
		}
		
	 	linkedin_detailer( LI_APPKEY, LI_SECRET, LI_REDIR );
	 	facebook_detailer( FBP_APPKEY, FBP_SECRET, BASEURL.'/signup.php?fbcode=yes' );

		if ( $_GET['oauth_verifier'] ) 
		{
			twitter_login();

		}

		if ( isset($_GET['code']) )
		{
			$token = get_linkedin_token();
		}

		if ( isset($_POST['social_account_id']) ) 
		{
			$signup_data = $_POST;
			$create_account = login_social($signup_data);
		}
	}

?>