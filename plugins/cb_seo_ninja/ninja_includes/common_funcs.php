<?php
	
	function seo_ninja()
	{
		global $ninja;
		$ninja->seo_ninja_all();
	}

	function seo_edit_video()
	{
		global $ninja;
		$ninja->seo_item_edit();
	}

	function ninja_anchors()
	{
		register_anchor_function("seo_ninja", "seo_ninja");
		register_anchor_function("seo_edit_video", "seo_edit_video");
		register_anchor_function("html_map", "html_map");
	}

	function fire_ninja($active = false)
	{
		global $ninja;
		$ninja->init($active);
		ninja_anchors();
	}

	function html_map()
	{
		global $ninja;
		$ninja->html_sitemap();
	}
	
?>