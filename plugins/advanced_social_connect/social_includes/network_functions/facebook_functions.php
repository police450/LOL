<?php

	function get_data($url)
	{
	  $ch = curl_init();
	  $timeout = 5;
	  curl_setopt($ch,CURLOPT_URL,$url);
	  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	  curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	  $data = curl_exec($ch);
	  curl_close($ch);
	  return $data;
	}
	
	function update_fb_configs( $data )
	{
		global $db;
		$fb_app_key = mysql_clean($data['app_key']);
		$fb_app_secret = mysql_clean($data['app_secret']);

		$db->update(tbl("socialconn_configs"), array("app_key","app_secret"), array($fb_app_key,$fb_app_secret), "app_id='fbapp'");
		e("Facebook Connect Settings have been updated","m");
	}

	function show_facebook()
	{
		echo '<a href='.BASEURL.'/signup.php?facebook_login=yes class="facebook-btn">
			<i class="fa fa-facebook" aria-hidden="true" alt="Sign in with Facebook"></i>
			facebook
		</a>';
	}

		function facebook_detailer( $app_id, $app_secret, $redirect_uri )
	{
		

		if ( isset($_GET['facebook_login']) )
		{
			header("Location: https://www.facebook.com/dialog/oauth?client_id=".$app_id."&redirect_uri=".$redirect_uri.'&scope=email');
		}

		if ( isset($_GET['fbcode']) )
		{
			$code = $_GET['code'];
			$token_url = 'https://graph.facebook.com/oauth/access_token?client_id=' .$app_id. '&redirect_uri=' .$redirect_uri. '&client_secret=' .$app_secret. '&code='.$code;

			$data = get_data( $token_url );

			$readable = json_decode($data,true);
			
			$response = $readable;

			if( !$response )
			{
				echo 'Login failed. This "code" was already used for getting the token. Get another code (resend user to login page)';
				exit;
			}
			else
			{
				#make from string ex: &token=1&expires=123 into array
				//parse_str($response,$response);
				#get the token if exists
				$token = (isset($response['access_token']))? $response['access_token']:'';
				#call for user data, the returned data is a json	
				$graph_url = 'https://graph.facebook.com/me?fields=id,first_name,last_name,email,picture,about&access_token='.$token;
				#userData is an array
				$raw_user_data = get_data($graph_url);
				$readable_user_data = json_decode( $raw_user_data );

				$graph_url_avatar = 'https://graph.facebook.com/me/picture?height=601&redirect=false&width=601&access_token='.$token;
				$raw_avatar_data = get_data($graph_url_avatar);
				$readable_avatar_data = json_decode( $raw_avatar_data );
				/*pr( $readable_user_data,true );
				exit();*/

				$fb_id = $readable_user_data->id;
				$fb_first_name = $readable_user_data->first_name;
				$fb_last_name = $readable_user_data->last_name;
				$the_name = $fb_first_name.' '.$fb_last_name;
				$build_user = $fb_first_name.$fb_last_name;
				$fb_username = strtolower($build_user);
				$fb_avatar = $readable_avatar_data->data->url;
				$fb_email = $readable_user_data->email;
				$about_me = $readable_user_data->about;

				$data = [];
				$data['social_account_id'] = $fb_id;
				$data['name'] = $the_name;
				$data['username'] = $fb_username;
				$data['avatar_url'] = $fb_avatar;
				$data['email'] = $fb_email;
				$data['soclid'] = 'fb';
				$data['about_me'] = $about_me;

				login_social( $data );
				}
			
		}

	}


?>