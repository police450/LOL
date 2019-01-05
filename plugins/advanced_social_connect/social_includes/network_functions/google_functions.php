<?php
	include_once(LOGIN_GOOGLE_DIR."/src/Google_Client.php");
	include_once(LOGIN_GOOGLE_DIR."/src/contrib/Google_Oauth2Service.php");

	function update_google_configs( $data )
	{
		global $db;
		$gm_app_key = mysql_clean($_POST['app_key']);
		$gm_app_secret = mysql_clean($_POST['app_secret']);
		$gm_dev_key = mysql_clean($_POST['dev_key']);
		$gm_red_url = mysql_clean($_POST['red_url']);

		$db->update(tbl("socialconn_configs"), array("app_key","app_secret","dev_key","red_url"), array($gm_app_key,$gm_app_secret,$gm_dev_key,$gm_red_url), "app_id='gmapp'");
		e("Google Connect Settings have been updated","m");
	}

	function show_google()
	{
		echo '<a href='.BASEURL.'/signup.php?google_login=yes class="google-btn">
			<i class="fa fa-google-plus" aria-hidden="true" alt="Sign in with Google Plus"></i>
			google

		</a>';
	}

	function google_init_login( $google_key, $google_secret, $redir_url )
	{
		session_start();
	
		######### edit details ##########
		$clientId = $google_key; //Google CLIENT ID
		$clientSecret = $google_secret; //Google CLIENT SECRET
		$redirectUrl = $redir_url;  //return url (url to script)
		$homeUrl = $redir_url;  //return to home

		##################################

		$gClient = new Google_Client();
		$gClient->setApplicationName('Login to codexworld.com');
		$gClient->setClientId($clientId);
		$gClient->setClientSecret($clientSecret);
		$gClient->setRedirectUri($redirectUrl);

		$google_oauthV2 = new Google_Oauth2Service($gClient);

		if(isset($_REQUEST['code']))
		{
			$gClient->authenticate();
			$_SESSION['token'] = $gClient->getAccessToken();
			header('Location: ' . filter_var($redirect_url, FILTER_SANITIZE_URL));
		}

		if (isset($_SESSION['token'])) 
		{
			$gClient->setAccessToken($_SESSION['token']);
		}

		if ($gClient->getAccessToken()) 
		{
			$userProfile = $google_oauthV2->userinfo->get();
			//DB Insert
			$_SESSION['google_data'] = $userProfile; // Storing Google User Data in Session
			$_SESSION['token'] = $gClient->getAccessToken();

			$raw_data = $_SESSION['google_data'];

			/*echo "HERE";
			pr( $raw_data['picture'], true );	*/		

			$name = $raw_data['name'];
			$username = str_replace(" ", "_", strtolower($name));

			$data = [];
			$data['email'] = $raw_data['email'];
			$data['name'] = $raw_data['name'];
			$data['social_account_id'] = $raw_data['id'];
			$data['username'] = $username;
			$data['avatar_url'] = $raw_data['picture'];
			$data['soclid'] = 'gplus';

			/*echo "THIS";
			pr($data,true);*/

			return $data;

		} 
		else 
		{
			$authUrl = $gClient->createAuthUrl();
		}

		if(isset($authUrl)) 
		{
			header("Location: ".$authUrl);
		} 
		else 
		{
			//echo '<a href="logout.php?logout">Logout</a>';
		}

	}

?>