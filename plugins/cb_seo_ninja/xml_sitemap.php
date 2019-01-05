<?php
/* 
 *****************************************************************
 | Copyright (c) 2007-2010 Clip-Bucket.com. All rights reserved.	
 | @ Author : tangi											
 | @ Software : ClipBucket , &#169; PHPBucket.com						
 ******************************************************************
*/
 // how to submit sitemap to google or other search engines
 /*
google: http://www.google.com/webmasters/sitemaps/ping?sitemap=url_to_sitemap
bing: http://www.bing.com/webmaster/ping.aspx?siteMap=url_to_sitemap
 */
require '../../includes/config.inc.php';
header("Content-Type: text/xml charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

$limit = 100;
$videos = get_videos(array('limit'=>$limit,'active'=>'yes','order'=>'date_added DESC'));

?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
<!-- by Saqib ( saqib @ clipbucket ) -->
<?php
    foreach($videos as $video)
	{
?>
<url>
<loc><?=video_link($video)?></loc>
<lastmod><?=$video['date_added']?></lastmod>
<changefreq>weekly</changefreq>
<video:video>
<video:thumbnail_loc>
<![CDATA[ <?=get_thumb($video)?> ]]>
</video:thumbnail_loc>
<video:title>
<![CDATA[ <?=$video['title']?> ]]>
</video:title>
<video:description>
<![CDATA[ <?=$video['description']?> ]]>
</video:description>
<video:view_count><?=$video['views']?></video:view_count>
<video:publication_date><?php
echo cbdate("Y-m-d\TH:i:s",strtotime($video['date_added'])).'+00:00';
?></video:publication_date>

<?php
$vtags = strip_tags(tags($video['tags'],'video'));
$vtableau = explode (",",$vtags);
for($i=0;$i<sizeof($vtableau);$i++)
    {
    echo '<video:tag><![CDATA['.trim($vtableau[$i]).']]></video:tag>';
    }
?>
<video:duration><?php
$defaultime = $video['duration'];
$dotfixed = explode (".",$defaultime);
echo $dotfixed[0].$dotfixed[1];
?></video:duration>
</video:video>
</url>
<?php
	}
?>
</urlset>