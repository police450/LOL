<?php
	
	require_once LOGIN_TWIT_DIR.'/twitteroauth/twitteroauth.php';

		function update_twit_configs( $data )
		{
			global $db;
			
			$tw_app_key = mysql_clean($_POST['app_key']);
			$tw_app_secret = mysql_clean($_POST['app_secret']);
			$tw_red_url = mysql_clean($_POST['red_url']);

			$db->update(tbl("socialconn_configs"), array("app_key","app_secret","red_url"), array($tw_app_key,$tw_app_secret,$tw_red_url), "app_id='twapp'");
			e("Twitter Connect Settings have been updated","m");
		}

		function show_twitter( $image_dir ) 
		{
			echo '<a href='.BASEURL.'/signup.php?twit_login=yes class="twitter-btn">
				<i class="fa fa-twitter" aria-hidden="true" alt="Sign in with Twitter"></i>
				twitter
			</a>';	
		}

		function show_email_box($login_data)
		{
			$name = $login_data['name'];
			$social_account_id = $login_data['social_account_id'];
			$username = $login_data['username'];
			$avatar_url = $login_data['avatar_url'];
			$soclid = $login_data['soclid'];

			assign( "social_name", $name );
			assign( "social_account_id", $social_account_id );
			assign( "social_username", $username );
			assign( "social_avatar_url", $avatar_url );
			assign( "soclid", $soclid );

			Template(SOCIAL_CON_HTML.'/email_box.html',false);
		}

		function twit_define() 
		{
			global $db;
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
		}

		function twit_init_login() 
		{
			session_start();
			$CONSUMER_KEY = TW_CKEY;
			$CONSUMER_SECRET = TW_CSECRET;
			$OAUTH_CALLBACK = TW_RED_URL;

			$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET);
			$request_token = $connection->getRequestToken($OAUTH_CALLBACK); //get Request Token

			if(	$request_token)
			{
				$token = $request_token['oauth_token'];
				$_SESSION['request_token'] = $token ;
				$_SESSION['request_token_secret'] = $request_token['oauth_token_secret'];

				switch ($connection->http_code) 
				{
					case 200:
						$url = $connection->getAuthorizeURL($token);
						//redirect to Twitter .
				    	header('Location: ' . $url); 

					    break;
					default:
					    e("Coonection with twitter failed. Kindly check your internet connection",e);
				    	break;
				}

			}
			else //error receiving request token
			{
				e("Error TWitter receiving request token",e);
			}
		}

		function twit_get_details($key, $secret, $callback)
		{
			$CONSUMER_KEY = $key;
			$CONSUMER_SECRET = $secret;
			$OAUTH_CALLBACK = $callback;

			$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $_SESSION['request_token'], $_SESSION['request_token_secret']);
			$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

			if($access_token)
			{
				$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
				$params =array();
				$params['include_entities']='false';
				$content = $connection->get('account/verify_credentials');

				if($content)
				{
					$twitter_account_id = $content->id;
					$twitter_name = $content->name;
					$twit_pic = $content->profile_image_url;
					$twitter_id = $content->screen_name;	

					$data = [];
					$data['social_account_id'] = $twitter_account_id;
					$data['name'] = $twitter_name;
					$data['username'] = $twitter_id;
					$data['avatar_url'] = $twit_pic;
					$data['soclid'] = 'tw';

					return $data;
					//redirect to main page.
					//header('Location: signup.php'); 
				}
				else
				{
				echo "<h4> Login Error </h4>";
				}
			}
		}

		function twitter_login() 
		{
			global $userquery;
			$login_data = twit_get_details( TW_CKEY, TW_CSECRET, TW_RED_URL);
			$social_account_id = $login_data['social_account_id'];
			$user = $login_data['username'];
			$soclid = $login_data['soclid'];

			$social_user = social_user_exists( $user, $soclid );

			$sripped_user = str_strip_user( $social_account_id, $soclid );;

			if ( !empty($login_data) )
			{
				if ( $social_user )
				{
					$userquery->login_as_user($social_user);
					header("Location: ".BASEURL.'/myaccount.php?user='.$social_user);
				}
				elseif ( $sripped_user )
				{
					$userquery->login_as_user($sripped_user);
					header("Location: ".BASEURL.'/myaccount.php?user='.$social_user);
				}
				else
				{
					show_email_box($login_data);
				}	
			}
			else 
			{
				e('No data returned from Twitter',e);
			}
		}