<?php

	function update_linkedin_configs( $data )
	{
		global $db;
		$lnk_app_key = mysql_clean($_POST['app_key']);
		$lnk_app_secret = mysql_clean($_POST['app_secret']);

		$db->update(tbl("socialconn_configs"), array("app_key","app_secret"), array($lnk_app_key,$lnk_app_secret), "app_id='lnkapp'");
		e("Linkedin Connect Settings have been updated","m");
	}

	function show_linkedin()
	{
		echo '<a href='.BASEURL.'/signup.php?linkedin_login=yes class="linkedin-btn">
			<i class="fa fa-linkedin" aria-hidden="true" alt="Sign in with Linkedin"></i>
			Linkedin
		</a>';
	}

	function linkedin_init_login( $client_id, $client_secret, $redirect_uri )
	{
		header("Location: https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=".$client_id."&redirect_uri=".$redirect_uri."&state=987654321");
	}

	function get_linkedin_token()
	{
			$data = file_get_contents("https://www.linkedin.com/uas/oauth2/accessToken?grant_type=authorization_code&code=".$_GET['code']."&redirect_uri=".$redirect_uri."&client_id=".$client_id."&client_secret=".$client_secret);

			$readable = json_decode($data);
			return $readable;

	}

	function linkedin_user_data( $access_token )
	{
		$user_data = file_get_contents("https://api.linkedin.com/v1/people/~:(id,firstName,lastName,picture-url,email-address)?oauth2_access_token=".$access_token."&format=json");

		$readable = json_decode($user_data);

		return $readable;
	}

	function linkedin_detailer( $client_id, $client_secret, $redirect_uri )
	{
		if ( isset($_GET['linkedin_login']) )
		{
			header("Location: https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=".$client_id."&redirect_uri=".$redirect_uri."&state=987654321");
		}

		if ( isset($_GET['code']) )
		{
			$data = file_get_contents("https://www.linkedin.com/uas/oauth2/accessToken?grant_type=authorization_code&code=".$_GET['code']."&redirect_uri=".$redirect_uri."&client_id=".$client_id."&client_secret=".$client_secret);

			$readable = json_decode($data);
			$access_token = $readable->access_token;
		}

		if ( !empty( $access_token ) )
		{
			$user_data = file_get_contents("https://api.linkedin.com/v1/people/~:(id,firstName,lastName,picture-url,email-address)?oauth2_access_token=".$access_token."&format=json");

			$readable_user_data = json_decode( $user_data );

			$li_id = $readable_user_data->id;
			$li_first_name = $readable_user_data->firstName;
			$li_last_name = $readable_user_data->lastName;
			$the_name = $li_first_name.' '.$li_last_name;
			$build_user = $li_first_name.$li_last_name;
			$li_username = strtolower($build_user);
			$li_avatar = $readable_user_data->pictureUrl;
			$li_email = $readable_user_data->emailAddress;

			$data = [];
			$data['social_account_id'] = $li_id;
			$data['name'] = $the_name;
			$data['username'] = $li_username;
			$data['avatar_url'] = $li_avatar;
			$data['email'] = $li_email;
			$data['soclid'] = 'li';

			#pr($data,true);

			login_social( $data );
		}
	}

?>