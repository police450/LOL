<?php

/**
 * This class is used to get videos from
 * Youtube.com
 *
 * Parameters for all API requests:
 * client
 * key
 *
 * Search parameters:
 * caption
 * category
 * format
 * location
 * location-radius
 * lr
 * orderby
 * q
 * safeSearch
 * time
 * uploader
 * @author Arslan Hassan, Saqib Razzaq
 * @since YouTube API 3, ClipBucket 2.7.6
 * Last Updated: August 5th, 2015
 */
 


class youtube extends cb_mass_embed
{
	
	/**
	 * Developers id is used to tell .youtube.com from which developer
	 * is this request coming from
	 * this DEVID is CLIPBUCKET's
	 */
	var $dev_id = "AIzaSyDOkg-u9jnhP-WnzX5WPJyV1sc5QQrtuyc";
	
	var $xml_api = true;
	
	var $result_offset = 1;
	
	var $max_result = 50;
	
	var $results_got = 0;
	
	var $website = 'youtube';
	
	
	/**
	 * Function used to create API FEED URL
	 * this url will be called by SERVER and FETCH results and save it in 
	 * $html_data
	 */
	function get_feed_url()
	{
		$APIUrl = 'https://www.googleapis.com/youtube/v3/search?type=video&part=snippet';
		
		#Adding keywords
		$APIUrl .= '&q='.$this->get_keyword_query();

		#Sorting
		$APIUrl .= '&order='.$this->get_sort_type();
		#Time

		$APIUrl .= '&maxResults='.$this->max_results();

		$APIUrl .= '&key='.$this->dev_id;
		#Setting API DEV ID
		$APIUrl .= '&start-index='.$this->result_offset;
		
		return $this->feed_url = $APIUrl ;
	}

	########################################
	# YouTube Api 3 Starts (New functions) #
	########################################
	/**
	* Some new functions written for YouTube API 3
	* @author Saqib Razzaq
	*/ 

	/**
	* Functions used for retiriving individual video data (duration, views etc)
	* By default search YouTube doesn't give all video details
	* Hence, we need to make another request to get extra data we need
	* @param YouTube Video Id
	*/

	function get_v_deta($yt_vid_id, $raw = false)
	{
		$url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails,statistics';
		$url .= '&id='.$yt_vid_id;
		$url .= '&key='.$this->dev_id;

		$raw_data = file_get_contents($url);
		if ($raw) {
			return $raw_data;
		}

		return $decoded_data;
	}

	/**
	* Functions used to grab part part of string between
	* any two characters. Used for breaking down YouTube Time
	* format so we can convert it in CB format
	* @param string $str: The string provide to cut from
	* @param from $from: The starting character
	* @param to $to: The ending character
	*/

	function getStringBetween($str,$from,$to)
	{
	    $sub = substr($str, strpos($str,$from)+strlen($from),strlen($str));
	    return substr($sub,0,strpos($sub,$to));
	}

	/**
	* Converts default YouTube Time in CB usable time
	* @param YouTube Video Duration
	* Default YouTube Format: PT15M51S = (00:15:51)
	* We have to convert it into seconds because that is how CB stores 
	* all videos duration in Database
	*/

	function break_down_yt_time($duration)
	{

	$str = $duration;
	$str = str_replace("P", "", $str);
	$from = "T";
	$to = "H";

	$hours = $this->getStringBetween($str,$from,$to);

	$from = "H";
	$to = "M";

	$mins = $this->getStringBetween($str,$from,$to);

	$from = "M";
	$to = "S";

	$secs = $this->getStringBetween($str,$from,$to);

	$hours = $hours * 3600;
	$mins = $mins * 60;
	$total = $hours + $mins + $secs;
	//echo $total;
	return $total;

	}
	
	/**
	 * Function used to return API ready result type
	 * ie if user set RELEVENCE then Youtube's API result type would be orderby=relevance
	 */
	function get_sort_type()
	{
		$type = $this->sort_type;
		$Yt_sortings = array
		('relevance'=>'relevance', 'published'=>'published',
		 'views'=>'viewCount','rating'=>'rating'
		 );
		$sorttype = $Yt_sortings[$type];
		if($sorttype)
			return $sorttype;
		else
			return $Yt_sortings['published'];
	}
	
	/**
	 * Function used to get max results that an API can request
	 */
	function max_results()
	{
		if($this->max_results>50)
			return 50;
		else
			return $this->max_results;
	}
	
	/**
	 * Function used to convert KEYWORDS into QUERY
	 */
	function get_keyword_query()
	{
		$keywords = $this->keywords;
		$keywords = preg_replace("/, /",",",$keywords);
		$keywords = preg_replace("/ ,/",",",$keywords);
		$keywords = explode(",",$keywords);
		$query = "";
		foreach($keywords as $keyword)
		{
			if(!empty($query))
				$query .="%7C";
			$query .= urlencode($keyword);
		}
		
		return $query;
	}

	function maxqual_thumb($thumbsarray) {
		$encoded = json_encode($thumbsarray);
		$cleaned = json_decode($encoded,true);
		return maxres_youtube(false, $cleaned);
	}
	
	/**
	 * Function used for embeding videos by search
	 * It gets content from Google servers and then
	 * converts that content into usable CB data
	 * Max Results: 50
	 */

	function parse_and_get_results($apiFeed=NULL)
	{
		$this->results_got = 1;
		$vids = array();
		while($this->results_got<=$this->results)
		{
			if(!$apiFeed)
			$array = file_get_contents($this->get_feed_url());
			else
			$array = file_get_contents($apiFeed);
			
			$entries = $array;
			$entries = json_decode($array);
			$dearray = json_decode($array,true);
			$v_details = $entries->items;
			#pex($v_details,true);
			if(empty($v_details[0]->snippet->title))
				echo " Something went wrong! Make sure your Internet connection is working and API is activated!";			

			$this->results_found = count($entries);
			if($this->results_found > $this->results)
				$this->results_found = $this->results;
			foreach($v_details as $entry)
			{	
				if( $entry != '' )
				{
					$count = ($this->results_got) - 1;
					$yt_vid_id = $v_details[$count]->id->videoId;
					$raw_data = $this->get_v_deta($yt_vid_id,true);
					$readArray = $this->maxqual_thumb($entry->snippet->thumbnails);
					$maxThumb = $readArray['thumb'];
					$max_thumb_height = $readArray['height'];
					$max_thumb_width = $readArray['width'];
					/*Store data in variables to make it easier */
					$title = $v_details[$count]->snippet->title;
					$description = $v_details[$count]->snippet->description;
					$tags = $v_details[$count]->snippet->tags;
					$embed_code = "<iframe src='https://www.youtube.com/embed/".$yt_vid_id."' style='width:100%; height:100%;'></iframe>";
					$published = $v_details[$count]->snippet->publishedAt;
					$thumb_def = $v_details[$count]->snippet->thumbnails->default->url;
					$thumb_med = $v_details[$count]->snippet->thumbnails->medium->url;
					$thumb_high = $v_details[$count]->snippet->thumbnails->high->url;
					

					/*Get stats and Duration*/
					// $video_content = file_get_contents('https://www.googleapis.com/youtube/v3/videos?id='.$yt_vid_id.'&key='.$this->dev_id.'&part=contentDetails,statistics');
					// $content_data = json_decode($video_content);

					$content_data = json_decode($raw_data);
					$tags = $content_data->items[0]->snippet->tags;
					$tags = implode(',',$tags);
				
					$encoded_duration = $content_data->items[0]->contentDetails->duration;
					$duration = $this->break_down_yt_time($encoded_duration);
					$views = $content_data->items[0]->statistics->viewCount;
					$rating = $content_data->items[0]->statistics->likeCount;

					/*Add all required video data into an array*/
					$vids[$count]['title'] = $title;
					$vids[$count]['description'] = $description;
					$vids[$count]['tags'] = $tags;
					$vids[$count]['embed_code'] = $embed_code;
					$vids[$count]['duration'] = $duration;
					$vids[$count]['views'] = $views;
					$vids[$count]['rating'] = 0;
					$vids[$count]['rated_by'] = 0;
					$vids[$count]['date_added'] = $published;
					$vids[$count]['category'] = array('1');
					$vids[$count]['website'] = $this->website;
					$vids[$count]['url'] = 'http://www.youtube.com/watch?v='.$yt_vid_id;
					$vids[$count]['unique_id'] = $yt_vid_id;
					$vids[$count]['thumb'] = $maxThumb;
					$vids[$count]['thumb_width'] = $max_thumb_width;
					$vids[$count]['thumb_height'] = $max_thumb_height;
				
					$this->results_got++;
				}		
			}
			
			$this->get_the_offset();
		}

		return $this->results_array = $vids;
	}
	
	/**
	 * Function used for embeding videos by URLs. It takes a URL
	 * sends API call, gets data, converts it and returns usable 
	 * array for CB
	 * @param $youtube_url : array with youtube ids
	 * Max URLs : 20
	*/

	function get_details_from_url($youtube_ids_array)
	{
		$this->results_got = 0;
		$count = 0;
		
		if (is_array($youtube_ids_array))
		{
			$videos = array();
			foreach ($youtube_ids_array as $key => $value) 
			{
				/*Getting raw content from YouTube API*/
				$youtube_content = file_get_contents('https://www.googleapis.com/youtube/v3/videos?id='.$value.'&key='.$this->dev_id.'&part=snippet,contentDetails,statistics');
				
				/*Decode content to make it acessible*/
				$content = json_decode($youtube_content);
				//pr($content,true);
				/*Setting variables to make accessing easier*/
				$duration = $content->items[0]->contentDetails->duration;
				$vid_details = $content->items[0]->snippet;
				$title = $vid_details->title;
				$description = $vid_details->description;
				$tags = $vid_details->tags;
				$tags = implode(',',$tags);
				$duration = $this->break_down_yt_time($duration);
				$views = $content->items[0]->statistics->viewCount;
				$thumb_def = $vid_details->thumbnails->default->url;
				$thumb_med = $vid_details->thumbnails->medium->url;
				$thumb_high = $vid_details->thumbnails->high->url;
				$published = $vid_details->publishedAt;

				$readArray = $this->maxqual_thumb($content->items[0]->snippet->thumbnails);
				$maxThumb = $readArray['thumb'];
				$max_thumb_height = $readArray['height'];
				$max_thumb_width = $readArray['width'];

				/*Adding required video data in video array*/
				$vids['title'] = $title;
				$vids['description'] = $description;
				$vids['tags'] = $tags;
				$vids['duration'] = $duration;
				$vids['views'] = $views;
				$vids['rating'] = 0;
				$vids['rated_by'] = 0;
				$vids['category'] = array('1');
				$vids['website'] = $this->website;
				$vids['url'] = 'https://www.youtube.com/watch?v='.$value;
				$vids['unique_id'] = $value;
				unset($vids['thumbs']);
				$vids['thumb'] = $maxThumb;
				$vids['thumb_width'] = $max_thumb_width;
				$vids['thumb_height'] = $max_thumb_height;
				$vids['date_added'] = $published;

				/*Incrementing result got*/
				$count++;
				$videos[] = $vids;
			}
			#pr($videos,true);
			return $videos;
		}
		else
			return false;
	}
	
	/**
	 * Function used to generate Embed Video Code
	 */
	function embed_video_code($code)
	{
		$sample = '<object width="425" height="344"><param name="movie" value="{FILE}"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="{FILE}" type="application/x-shockwave-flash" width="425" height="344" allowscriptaccess="always" allowfullscreen="true"></embed></object>';
		$embed_video_code = preg_replace('/{FILE}/',$code,$sample);
		#exit($embed_video_code);
		return $embed_video_code;
	}
	
	/**
	 * function used to get offseet
	 * if will return doubles the MAX_RESULT each time it is called
	 */
	function get_the_offset()
	{
		return $this->result_offset += $this->max_result;
	}
	
	/**
	 * Function used to get video comments
	 */
	function get_comments($vid)
	{
		if(!$this->import_comments)	
			return false;
		$url = 'http://gdata.youtube.com/feeds/api/videos/'.$vid.'/comments';
		#exit($url);
		$comments = xml2array($url);
		$comments = $comments['feed']['entry'];
		$comm_array = array();
		$comment = array();
		if(count($comments)>0)
		{
			foreach($comments as $comment)
			{
				#echo $comment['content'].'<br/>';
				if(is_array($comment))
				$comm_array[] = array('name'=>$comment['author']['name'],'comment'=>$comment['content'],'email'=>'anonymous@youtube.com');
			}
			return $comm_array;
		}else
			return false;
	}
	
	/**
	 * Function used to convert time span into period
	 * so that only videos uploaded in specifi time frame are show or embed
	 */
	function get_time_span()
	{
		return $this->result_time;
	}
	
	/**
	* Function used for retriving channel id using 
	* channel username so we can later extract other
	* required video details using that ID
	* @param $channel_name : username of channel
	*/

	function get_channel_id($channel_name) 
	{
		$get_id_call = 'https://www.googleapis.com/youtube/v3/channels?part=contentDetails';
		$get_id_call .= '&forUsername='.$channel_name;
		$get_id_call .= '&key=AIzaSyDOkg-u9jnhP-WnzX5WPJyV1sc5QQrtuyc';

		$get_channel_id = file_get_contents($get_id_call);
		$convert_data = json_decode($get_channel_id);
		$channel_id = $convert_data->items[0]->id;
		
		return $channel_id;
	}

	/**
	* Function used for getting a complete list of 
	* a YouTube channel using channel id extracted
	* via get_channel_id function
	* @param $user : youtube user
	*/

	function get_channel_videos($user)
	{
		$get_videos_call = 'https://www.googleapis.com/youtube/v3/search?';
		$get_videos_call .= 'channelId='.$this->get_channel_id($user);
		$get_videos_call .= '&part=snippet,id&order=date&maxResults=50';
		$get_videos_call .= '&key=AIzaSyDOkg-u9jnhP-WnzX5WPJyV1sc5QQrtuyc';

		$get_videos_data = file_get_contents($get_videos_call);
		$convert_data = json_decode($get_videos_data);
		$total_videos = $convert_data->pageInfo->totalResults;
		$on_page = $convert_data->pageInfo->resultsPerPage;

		$next_page = $convert_data->nextPageToken;
		$main_details = $convert_data->items;

		$vids = array();

		foreach ($main_details as $videos) 
		{
			$main_dets = $videos->snippet;
			$thumb = $main_dets->thumbnails->default->url;
			$vids[] = $this->extract_video_id($thumb);

/*			$title = $main_dets->title;
			$description = $main_dets->description;
			$published = $main_dets->publishedAt;

			$vids['title'] = $title;
			$vids['description'] = $description;
			$vids['published'] = $published;*/
			
		}
		echo "Total videos are: ".$total_videos;
		echo "</br>You searched: ".$on_page;
		//pr($vids);
		return $vids;
	}

	function extract_video_id($thumb_url)
	{
		$str = $thumb_url;
		$from = 'vi/';
		$to = '/';

		$yt_vid_id = $this->getStringBetween($str,$from,$to);
		return $yt_vid_id;
	}


	/**
	 * Functions used for grabbing entire YouTube Channel videos
	 * and then also creating channel of that name in CB
	 * @param $username : YouTube Channel UserName
	 */

	function get_data_from_user($username)
	{
		$vid_ids_array = $this->get_channel_videos($username);
		$returned_vids = $this->get_details_from_url($vid_ids_array);
		//pr($returned_vids,true);
		return $returned_vids;
	}
}

?>