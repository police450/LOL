<?php
	if(!defined('MAIN_PAGE')){
    	define('MAIN_PAGE', 'SEO Ninja');
	}

	if(!defined('SUB_PAGE')){
	    define('SUB_PAGE', 'SiteMap Settings');
	}

	require_once '../includes/admin_config.php';
	$userquery->admin_login_check();
	$userquery->login_check('admin_access');
	$pages->page_redir();
	if (isset($_GET['map'])) 
	{
		$map_url = SEO_NINJA_URL.'/xml_sitemap.php';
		$google_ping = file_get_contents('http://www.google.com/webmasters/sitemaps/ping?sitemap='.$map_url);
		#exit('http://www.google.com/webmasters/sitemaps/ping?sitemap='.$map_url);
		#pex($google_ping,true);
		$bing_ping = file_get_contents('http://www.bing.com/webmaster/ping.aspx?siteMap='.$map_url);
		if ($google_ping)
		{
			e("Sitemap successfuly submitted to Google","m");
		}
		if ($bing_ping)
		{
			e("Sitemap successfuly submitted to Bing","m");	
		}
	}
	subtitle("SiteMap Settings - SEO Ninja");
	template_files(SEO_NINJA_ADMIN_HTML.'/sitemap_configs.html');
?>