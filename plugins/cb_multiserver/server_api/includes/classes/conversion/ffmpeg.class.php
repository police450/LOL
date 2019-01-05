

<?php

/**
 * This is new file for conversion
 * now , FFMPEG will only be used for video conversion
 * @Author : Arslan Hassan,Awais Fiaz
 * @Software : ClipBucket
 * @website : http://clip-bucket.com/
 * Processing Status : 0 [no process]
 * 1 [normal quality but hd still to come]
 * 2 [mission completed]
 * Updated:On Oct 2017 
 */



class ffmpeg 
{
	var $input_details = array(); //Holds File value
	var $output_details = array(); // Holds Converted File Details
	var $ffmpeg ; // path to ffmpeg binary
	var $mplayer; //Path to mplayer binary
	var $input_file; //File to be converted
	var $output_file; //File after $file is converted
	var $tbl = 'video_files';
	var $row_id  ; //Db row id
	var $log; //Holds overall status of conversion
	var $start_time;
	var $end_time;
	var $total_time;
	var $configs = array();
	var $configs_43 = array();
	var $configs_169 = array();
	var $gen_thumbs; //If set to TRUE , it will generate thumbnails also
	var $remove_input = TRUE;
	var $gen_big_thumb = FALSE;
	var $h264_single_pass = FALSE;
	var $hq_output_file = '';
	var $log_file = '';
	var $input_ext = '';
	var $tmp_dir = '/tmp/';
	var $flvtool2 = '';
	var $thumb_dim = '120x90'; //Thumbs Dimension
	var $num_of_thumbs = '3'; //Number of thumbs
	var $big_thumb_dim = 'original'; //Big thumb size , original will get orginal video window size thumb othersie the dimension
	var $has_hq = false;
	var $has_hd = false;
	var $use_2_pass_encoding = false;
	var $conv_status = "processing"; //it can be completed | failed | processing
	var $generate_3gp = true;
	var $generate_mp4 = true;
	var $raw_path;
	var $has_mobile = 'no';
	var $lock_file = '';
	var $video_folder = "";
	var $has_sprite = "";
	var $resolutions = 'yes';
	var $has_resulotions = 'no';
	var $video_files = array(); 
	var $generate_sprite = 'no';
	
	
	/**
	 * Initiating Class
	 */
	function ffmpeg($file)
	{
		$this->ffmpeg = FFMPEG_BINARY;
		$this->mp4box = MP4Box_BINARY;
		$this->flvtool2 = FLVTool2_BINARY;
		$this->mplayer = MPLAYER_BINARY;
		$this->medai_info = MEDIAINFO_BINARY;
		$this->input_file = $file;
		$this->generate_mp4 = true;
		
	}
	
	
	/**
	 * Prepare file to be converted
	 * this will first get info of the file
	 * and enter its info into database
	 */
	function prepare($file=NULL)
	{
		global $db;
		
		if($file)
			$this->input_file = $file;

		if(file_exists($this->input_file))
			$this->input_file = $this->input_file;
		else
			$this->input_file = TEMP_DIR.'/'.$this->input_file;
		
		//Checking File Exists
		if(!file_exists($this->input_file))
		{
			$this->log('File Exists','No');
		}else{
			$this->log('File Exists','Yes');
		}
		
		//Get File info
		$this->input_details = $this->get_file_info();
		
		//Loging File Details
		$this->log .= "\nPreparing file...\n";
		$this->log_file_info();
		
		//Insert Info into database
		//$this->insert_data();		
	}
	
	
	/**
	 * Function used to convert video 
	 */
	function convert($file=NULL,$for_iphone=false,$more_res=NULL, $current_progress)
	{

		global $db,$width, $height, $ratio, $pad_top, $pad_bottom, $pad_left, $pad_right;
		if($file)
			$this->input_file = $file;
		
		$this->log .= "\r\nConverting Video\r\n";


		
		$p = $this->configs;
		$i = $this->input_details;

		# Prepare the ffmpeg command to execute
		// if(isset($p['extra_options']))
		// 	$opt_av .= " -y {$p['extra_options']} ";

		# file format
		if(isset($p['format']))
			$opt_av .= " -f {$p['format']} ";
		


		if($p['use_video_codec'])
		{
			# video codec
			if(isset($p['video_codec']))
				$opt_av .= " -vcodec ".$p['video_codec'];
			elseif(isset($i['video_codec']))
				$opt_av .= " -vcodec ".$i['video_codec'];
			if($p['video_codec'] == 'libx264')
			{
				if($more_res['name'] == '240')
				{
					$opt_av .= " -preset superfast ";
				}
				if($more_res['name'] == '360')
				{
					$opt_av .= " -preset veryfast ";
				}
				if($more_res['name'] == '480')
				{
					$opt_av .= " -preset faster ";
				}
				if($more_res['name'] == '720')
				{
					$opt_av .= " -preset medium ";
				}
				if($more_res['name'] == '1080')
				{
					$opt_av .= " -preset slow ";
				}
			}
		}
		
		# video rate
		if($p['use_video_rate'])
		{
			if(isset($p['video_rate']))
				$vrate = $p['video_rate'];
			elseif(isset($i['video_rate']))
				$vrate = $i['video_rate'];
			if(isset($p['video_max_rate']) && !empty($vrate))
				$vrate = min($p['video_max_rate'],$vrate);
			if(!empty($vrate))
				$opt_av .= " -r $vrate ";
		}
		
	# video bitrate

		if($p['use_video_bit_rate'])
		{	
			
			if(isset($p['use_video_bit_rate'])){
				$vbrate_240 = $p['video_bitrate_240'];
				$vbrate_360 = $p['video_bitrate_360'];
				$vbrate_480 = $p['video_bitrate_480'];
				$vbrate_720 = $p['video_bitrate_720'];
				$vbrate_1080 = $p['video_bitrate_1080'];

			}elseif(isset($i['use_video_bit_rate'])){
				$vbrate_240 = $i['video_bitrate_240'];
				$vbrate_360 = $i['video_bitrate_360'];
				$vbrate_480 = $i['video_bitrate_480'];
				$vbrate_720 = $i['video_bitrate_720'];
				$vbrate_1080 = $i['video_bitrate_1080'];
			}

			if($more_res['name'] == '240')
			{
				$vbrate = $vbrate_240;

			}
			if($more_res['name'] == '360')
			{
				$vbrate = $vbrate_360;

			}
			if($more_res['name'] == '480')
			{
				$vbrate = $vbrate_480;

			}
			if($more_res['name'] == '720')
			{
				$vbrate = $vbrate_720;

			}
			if($more_res['name'] == '1080')
			{
				$vbrate = $vbrate_1080;

			}
			
			$opt_av .= " -maxrate $vbrate -g 60 -profile:v baseline ";
		}	


		
		
		# video size, aspect and padding
		
		#create all posible resolutions of selected video
		if($more_res!=NULL){

			$p['resize']='fit';
			$i['video_width'   ] = $more_res['video_width'] ;
			$i['video_height'  ] = $more_res['video_height'];	
			$opt_av .= " -s ".$more_res['video_width']."x".$more_res['video_height']." -aspect $ratio ";
		}
		else{
			
			$this->calculate_size_padding( $p, $i );
			$opt_av .= " -s {$width}x{$height} -aspect $ratio ";
		}

		
		# audio codec, rate and bitrate
		if($p['use_audio_codec'])
		{
			if(!empty($p['audio_codec']) && $p['audio_codec'] != 'None'){
				$opt_av .= " -acodec {$p['audio_codec']}";
			}
		}
		
		# audio bitrate
		if($p['use_audio_bit_rate'])
		{
			if(isset($p['audio_bitrate']))
				$abrate = $p['audio_bitrate'];
			elseif(isset($i['audio_bitrate']))
				$abrate = $i['audio_bitrate'];
			if(!empty($abrate))
			{
				$abrate=$abrate/1000;
				$abrate_cmd = " -ab ".$abrate."k";
				$opt_av .= $abrate_cmd;
			}
		}
		

		# audio rate
		if($p['use_audio_rate'])
		{
			if(isset($p['audio_rate'])){
				$arate = $p['audio_rate'];
			}
			elseif(isset($i['audio_rate'])){
				$arate = $i['audio_rate'];
			}

			$opt_av .= $arate_cmd = " -ar $arate ";
		}
		
		
		$tmp_file = time().RandomString(5).'.tmp';
		
		//$opt_av .= " -map_meta_data ".$this->output_file.":".$this->input_file;
		$input_file_details = $this->get_file_info($file);		
		//logData('configs => '.json_encode($input_file_details),'more_res');

		if(!$for_iphone)
		{
			logData($more_res,'more_res');
			$this->log .= "\r\nConverting Video file ".$more_res['name']." \r\n";


			$input_brate = (int)$input_file_details['video_bitrate'];

			// stoped from direct copying
			// if($this->conv_status != 'failed' && $more_res['name']==$input_file_details['video_height'] && $input_file_details['video_codec']=='h264'  && $input_file_details['audio_codec']=='aac' && getExt($this->input_file)=='mp4')   
			// {
			// 	// pr("incondition",true);
			// 	logData('in condition','more_res');
			// 	$this->log .= "Copying file as is\r\n\r\n";
			// 	copy($this->input_file,$this->raw_path.'/'.$this->file_name."-".$more_res['name'].".mp4");
   			//  //pr($this->input_file,$this->raw_path."-".$more_res['name'].".mp4",true);

			// }
			// else
			// {
				// pr("notcondition",true);
			logData('more res =>'.json_encode($more_res),'resolution command');
			logData('more res =>'.$p['gen_1080'],'resolution command');
				#create all posible resolutions of selected video
			if($more_res['name'] == '240' && $p['gen_240'] == 'yes' || $more_res['name'] == '360' && $p['gen_360'] == 'yes' || $more_res['name'] == '480' && $p['gen_480'] == 'yes' ||
				$more_res['name'] == '720' && $p['gen_720'] == 'yes' || $more_res['name'] == '1080' && $p['gen_1080'] == 'yes')
			{

				if($this->configs['watermark_video'] == 1){
					$input_file=CON_DIR."/".$this->file_name."-wm.mp4 ";
				}else{
					$input_file=$this->input_file;
				}

				$command  = $this->ffmpeg." -i ".$input_file." $opt_av ".$this->raw_path.'/'.$this->file_name."-".$more_res['name'].".mp4  2> ".TEMP_DIR."/".$tmp_file;
				logData($command,'test');
			}
			else{

				$command = '';
			}
			// }
			// stoped from direct copying

			
			$output = $this->exec($command);
			if(file_exists(TEMP_DIR.'/'.$tmp_file))
			{
				$output = $output ? $output : join("", file(TEMP_DIR.'/'.$tmp_file));
				unlink(TEMP_DIR.'/'.$tmp_file);
			}
			

			if(file_exists($this->raw_path.'/'.$this->file_name."-".$more_res['name'].".mp4") && filesize($this->raw_path.'/'.$this->file_name."-".$more_res['name'].".mp4")>0)
			{
				$this->has_resulotions = 'yes';
				$this->video_files[] =  $more_res['name'];
				$this->log .="\r\n\r\nFiles resolutions\r\n\r\n";
				$this->log .="\r\n\r\n".$more_res['name'].json_encode($this->video_files)."\r\n\r\n";
				$this->log .="\r\n\r\nEnd resolutions\r\n\r\n";
			} 

			
			$this->log('Conversion Command',$command);
			$this->log .="\r\n\r\nConversion Details\r\n\r\n";
			$this->log .=$output;

			$mp4_output = "";
			
			
			if($more_res==NULL)
				$command = $this->mp4box." -inter 0.5 ".$this->raw_path.'/'.$this->file_name.".mp4 -tmp ".$this->tmp_dir." 2> ".TEMP_DIR."/mp4_output.tmp ";
			else
			{
				logData('more res =>'.json_encode($more_res),'resolution command');
				logData('more res =>'.$p['gen_1080'],'resolution command');
				#create all posible resolutions of selected video
				if($more_res['name'] == '240' && $p['gen_240'] == 'yes' || $more_res['name'] == '360' && $p['gen_360'] == 'yes' || $more_res['name'] == '480' && $p['gen_480'] == 'yes' ||
					$more_res['name'] == '720' && $p['gen_720'] == 'yes' || $more_res['name'] == '1080' && $p['gen_1080'] == 'yes')
				{
					$command = $this->mp4box." -inter 0.5 ".$this->raw_path.'/'.$this->file_name."-".$more_res['name'].".mp4 -tmp ".$this->tmp_dir." 2> ".TEMP_DIR."/mp4_output.tmp ";
				}
				else
					$command = '';
			}

			$mp4_output .= $this->exec($command);
			if(file_exists(TEMP_DIR.'/mp4_output.tmp'))
			{
				$mp4_output = $mp4_output ? $mp4_output : join("", file(TEMP_DIR.'/mp4_output.tmp'));
				unlink(TEMP_DIR.'/mp4_output.tmp');
			}
			
			$ouput = $mp4_output;
			
			$this->log('== Mp4Box Command ==',$command);


		}
		
		$this->log .=$output;
		
		
		
		$this->output_details = $this->get_file_info($this->output_file);
		$call_bk = CALLBACK_URL;
		logData($call_bk,'new');
		$this->video_files = array_unique($this->video_files);
		pr($this->video_files,true);
		if(!empty($this->video_files))
		{
			$status = 'completed';
		}
		else
		{
			$status = 'processing';	
		}

	}


	/**
    * Used to create Dash 
    * @todo    : This Function gets all resolutions and convert them into Dash
    * @since   : 25th jul, 2017 Feedback 1.0
    * @author  : Awais Fiaz
    */

	function genDash($file=NULL,$for_iphone=false,$res=NULL,$convertedRes=NULL)
	{
		
		# code...

			// if($file)
			// 	$this->input_file = $file;

		$p = $this->configs;
		$i = $this->input_details;

		$mp4_output = "";

		logData("gendash all res :".$res,'tester');
		logData("gendash file name : ".$this->file_name,'tester');
		logData($convertedRes,'tester');

		if(!$for_iphone){


			# audio codec, rate and bitrate
			if($p['use_audio_codec'])
			{
				if(!empty($p['audio_codec']) && $p['audio_codec'] != 'None'){
					$aud_codec = $p['audio_codec'];
					// {$p['audio_codec']}
				}
			}
			
			# audio bitrate
			if($p['use_audio_bit_rate'])
			{
				if(isset($p['audio_bitrate']))
					$abrate = $p['audio_bitrate'];
				elseif(isset($i['audio_bitrate']))
					$abrate = $i['audio_bitrate'];
				if(!empty($abrate))
				{	
					$abrate=$abrate/1000;
					$abrate=$abrate.'k';
					// $abrate_cmd = $abrate;
					$aud_bit_rate = $abrate;
				}
			}
			
			# audio rate
			if($p['use_audio_rate'])
			{
				if(isset($p['audio_rate'])){
					$arate = $p['audio_rate'];
				}
				elseif(isset($i['audio_rate'])){
					$arate = $i['audio_rate'];
				}
				if(!empty($arate)){
					$aud_rate = $arate;
					$async = $arate;
				}
			}
			
			//command to create separete audio from raw file
			$audCommand=$this->ffmpeg." -i ".$this->input_file." -vn -c:a $aud_codec -ar $aud_rate -async $async -b:a $aud_bit_rate ".$this->raw_path."/".$this->file_name."-aud.mp4 2> ".TEMP_DIR."/".$tmp_file."_ffmpeg_aud_output.tmp";
			logData("ffmpeg audio command : ".$audCommand,'tester');

			if($convertedRes!=NULL){
				$audOutput = $this->exec($audCommand);
			}

			if($convertedRes!=NULL){
				$this->log .= "\r\n\r\n== Running Audio Command == \r\n\r\n";
				$this->log .= $audCommand;
			}

			if(file_exists(TEMP_DIR.'/'.$tmp_file."_ffmpeg_aud_output.tmp")){
				$audOutput = $audOutput ? $audOutput : join("", file(TEMP_DIR.'/'.$tmp_file."_ffmpeg_aud_output.tmp"));
				unlink(TEMP_DIR.'/'.$tmp_file."_ffmpeg_aud_output.tmp");
			}


			if($convertedRes){

            //Dash command using MP4Box
				$mp4command=$this->mp4box." -frag 4000 -dash 4000 -fps 30 -frag-rap -rap -profile baseline -bs-switching merge -brand mp42 -mpd-title 'Some video name' -mpd-source 'www.somewebsite.com' -mpd-info-url 'https://clipbucket.com' -cprt 'Copyright 2017 clipbucket.com all rights reserved' -url-template -segment-name ".$this->file_name."_segments/%s_ -out ".$this->raw_path."/".$this->file_name.".mpd ";


				if (is_array($convertedRes) && !empty($convertedRes)){
					foreach ($convertedRes as $key => $value) {
						$mp4command.=$this->raw_path."/".$this->file_name."-".$value.".mp4#video ";
					}
				}	

				$mp4command.=$this->raw_path."/".$this->file_name."-aud.mp4#audio 2> ".TEMP_DIR."/".$this->file_name."_mp4_output.tmp";

			}
			else{

				$this->log .= "\r\n\r\nMP4box command res are empty!\r\n\r\n";
			}
			
			logData("MP$box Dash command : ".$mp4command,'tester');
			
			if($convertedRes!=NULL){
				$mp4_output .= $this->exec($mp4command);
			}

			if(file_exists(TEMP_DIR."/".$this->file_name."_mp4_output.tmp")){
				$mp4_output = $mp4_output ? $mp4_output : join("", file(TEMP_DIR."/".$this->file_name."_mp4_output.tmp"));
				unlink(TEMP_DIR."/".$this->file_name."_mp4_output.tmp");
			}

			if($mp4_output!=NULL){
				$this->log .= "\n\n==  Mp4Box Command for dash ==\n\n";
				$this->log .=$mp4command;
				$this->log .= "\n\n==  MP4Box OutPut for dash ==\n\n";
				$this->log .=$mp4_output;
			}

			
			$this->log .="\r\n\r\nEnd resolutions @ ".date("Y-m-d H:i:s")."\r\n\r\n";

			// DASH call back
			$ext='mpd';
			$this->callBack( false, $convertedRes, $ext);
			

		}


	}

	
	/**
    * Used to create HLS stream 
    * @todo    : This Function gets all resolutions and convert them into HLS
    * @since   : sep 20th wed, 2017 Feedback 1.0
    * @author  : Awais Fiaz
    */

	function genHls($file=NULL,$for_iphone=false,$res=NULL,$convertedRes=NULL)
	{
		
		# code...

		@mkdir($this->raw_path."/".$this->file_name, 0777, true);

		$myfile = fopen($this->raw_path."/".$this->file_name.".m3u8", "w") or die("Unable to open file!");

		$txt = "#EXTM3U\n";

		fwrite($myfile, $txt);

		$txt = "#EXT-X-VERSION:3\n";

		fwrite($myfile, $txt);
			// fclose($myfile);



			// if($file)
			// 	$this->input_file = $file;

		$p = $this->configs;
		$i = $this->input_details;

		$mp4_output = "";

		logData("gendash all res :".$res,'tester');
		logData("gendash file name : ".$this->file_name,'tester');
		logData($convertedRes,'tester');
		
		if(!$for_iphone){


			# audio codec, rate and bitrate
			if($p['use_audio_codec'])
			{
				if(!empty($p['audio_codec']) && $p['audio_codec'] != 'None'){
					$aud_codec = $p['audio_codec'];
					// {$p['audio_codec']}
				}
			}
			
			# audio bitrate
			if($p['use_audio_bit_rate'])
			{
				if(isset($p['audio_bitrate']))
					$abrate = $p['audio_bitrate'];
				elseif(isset($i['audio_bitrate']))
					$abrate = $i['audio_bitrate'];
				if(!empty($abrate))
				{	
					$abrate=$abrate/1000;
					$abrate=$abrate.'k';
					// $abrate_cmd = $abrate;
					$aud_bit_rate = $abrate;
				}
			}
			
			# audio rate
			if($p['use_audio_rate'])
			{
				if(isset($p['audio_rate'])){
					$arate = $p['audio_rate'];
				}
				elseif(isset($i['audio_rate'])){
					$arate = $i['audio_rate'];
				}
				if(!empty($arate)){
					$aud_rate = $arate;
					$async = $arate;
				}
			}

			//command to create separete audio from raw file
			$audCommand=$this->ffmpeg." -i ".$this->input_file." -vn -c:a $aud_codec -ar $aud_rate -async $async -b:a $aud_bit_rate ".$this->raw_path."/".$this->file_name."-aud.mp4 2> ".TEMP_DIR."/".$tmp_file."_ffmpeg_aud_output.tmp";
			logData("FFMPEG audio command : ".$audCommand,'tester');

			
			if($convertedRes!=NULL){
				$audOutput = $this->exec($audCommand);
			}
			
			if($convertedRes!=NULL){
				$this->log .= "\r\n\r\n== Running Audio Command == \r\n\r\n";
				$this->log .= $audCommand;
			}

			if(file_exists(TEMP_DIR.'/'.$tmp_file."_ffmpeg_aud_output.tmp")){
				$audOutput = $audOutput ? $audOutput : join("", file(TEMP_DIR.'/'.$tmp_file."_ffmpeg_aud_output.tmp"));
				unlink(TEMP_DIR.'/'.$tmp_file."_ffmpeg_aud_output.tmp");
			}


			if($convertedRes){


				if($convertedRes['1080']){

					$hls1080Command=$this->ffmpeg." -i ".$this->raw_path."/".$this->file_name."-"."1080".".mp4 -c copy -hls_list_size 0 -start_number 0 -hls_init_time 0 -hls_time 2 -f hls ".$this->raw_path."/".$this->file_name."/".$this->file_name."-1080.m3u8 2> ".TEMP_DIR."/".$tmp_file."_ffmpeg_hls_1080_output.tmp";


					logData("FFMPEG hls1080Command command : ".$hls1080Command,'tester');



					if($convertedRes!=NULL){
						$hls1080Output = $this->exec($hls1080Command);
					}



					if(file_exists(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_1080_output.tmp")){
						$hls1080Output = $hls1080Output ? $hls1080Output : join("", file(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_1080_output.tmp"));
						unlink(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_1080_output.tmp");
					}


					if($convertedRes!=NULL){
						$this->log .= "\n\n==  FFMPEG HLS 1080 Command ==\n\n";
						$this->log .=$hls1080Command;
						$this->log .= "\n\n==  FFMPEG HLS 1080 OutPut ==\n\n";
						$this->log .=$hls1080Output;
					}

					$txt = "#EXT-X-STREAM-INF:PROGRAM-ID=4,BANDWIDTH=93056000,RESOLUTION=1920x1080\n";
					fwrite($myfile, $txt);
					$txt = $this->file_name."/".$this->file_name."-1080.m3u8\n";
					fwrite($myfile, $txt);
				}
				


				if($convertedRes['720']){

					$hls720Command=$this->ffmpeg." -i ".$this->raw_path."/".$this->file_name."-"."720".".mp4 -map 0 -c:v libx264 -hls_list_size 0 -start_number 0 -hls_init_time 0 -hls_time 2 -f hls ".$this->raw_path."/".$this->file_name."/".$this->file_name."-720.m3u8 2> ".TEMP_DIR."/".$tmp_file."_ffmpeg_hls_720_output.tmp";


					logData("FFMPEG hls720Command command : ".$hls720Command,'tester');



					if($convertedRes!=NULL){
						$hls720Output = $this->exec($hls720Command);
					}



					if(file_exists(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_720_output.tmp")){
						$hls720Output = $hls720Output ? $hls720Output : join("", file(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_720_output.tmp"));
						unlink(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_720_output.tmp");
					}


					if($convertedRes!=NULL){
						$this->log .= "\n\n==  FFMPEG HLS 720 Command ==\n\n";
						$this->log .=$hls720Command;
						$this->log .= "\n\n==  FFMPEG HLS 720 OutPut ==\n\n";
						$this->log .=$hls720Output;
					}

					$txt = "#EXT-X-STREAM-INF:PROGRAM-ID=3,BANDWIDTH=7305600,RESOLUTION=1280x720\n";
					fwrite($myfile, $txt);
					$txt = $this->file_name."/".$this->file_name."-720.m3u8\n";
					fwrite($myfile, $txt);

				}



				if($convertedRes['480']){

					$hls480Command=$this->ffmpeg." -i ".$this->raw_path."/".$this->file_name."-"."480".".mp4 -map 0 -c:v libx264 -hls_list_size 0 -start_number 0 -hls_init_time 0 -hls_time 2 -f hls ".$this->raw_path."/".$this->file_name."/".$this->file_name."-480.m3u8 2> ".TEMP_DIR."/".$tmp_file."_ffmpeg_hls_480_output.tmp";


					logData("FFMPEG hls480Command command : ".$hls480Command,'tester');



					if($convertedRes!=NULL){
						$hls480Output = $this->exec($hls480Command);
					}




					if(file_exists(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_480_output.tmp")){
						$hls480Output = $hls480Output ? $hls480Output : join("", file(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_480_output.tmp"));
						unlink(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_480_output.tmp");
					}


					if($convertedRes!=NULL){
						$this->log .= "\n\n==  FFMPEG HLS 480 Command ==\n\n";
						$this->log .=$hls480Command;
						$this->log .= "\n\n==  FFMPEG HLS 480 OutPut ==\n\n";
						$this->log .=$hls480Output;
					}

					$txt = "#EXT-X-STREAM-INF:PROGRAM-ID=2,BANDWIDTH=5605600,RESOLUTION=854x480\n";
					fwrite($myfile, $txt);
					$txt = $this->file_name."/".$this->file_name."-480.m3u8\n";
					fwrite($myfile, $txt);

				}
				



				if($convertedRes['360']){

					$hls360Command=$this->ffmpeg." -i ".$this->raw_path."/".$this->file_name."-"."360".".mp4 -map 0 -c:v libx264 -hls_list_size 0 -start_number 0 -hls_init_time 0 -hls_time 2 -f hls ".$this->raw_path."/".$this->file_name."/".$this->file_name."-360.m3u8 2> ".TEMP_DIR."/".$tmp_file."_ffmpeg_hls_360_output.tmp";


					logData("FFMPEG hls360Command command : ".$hls360Command,'tester');



					if($convertedRes!=NULL){
						$hls360Output = $this->exec($hls360Command);
					}



					if(file_exists(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_360_output.tmp")){
						$hls360Output = $hls360Output ? $hls360Output : join("", file(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_360_output.tmp"));
						unlink(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_360_output.tmp");
					}


					if($convertedRes!=NULL){
						$this->log .= "\n\n==  FFMPEG HLS 360 Command ==\n\n";
						$this->log .=$hls360Command;
						$this->log .= "\n\n==  FFMPEG HLS 360 OutPut ==\n\n";
						$this->log .=$hls360Output;
					}

					$txt = "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=2855600,RESOLUTION=640x360\n";
					fwrite($myfile, $txt);
					$txt = $this->file_name."/".$this->file_name."-360.m3u8\n";
					fwrite($myfile, $txt);

				}




				if($convertedRes['240']){

					$hls240Command=$this->ffmpeg." -i ".$this->raw_path."/".$this->file_name."-"."240".".mp4 -map 0 -c:v libx264 -hls_list_size 0 -start_number 0 -hls_init_time 0 -hls_time 2 -f hls ".$this->raw_path."/".$this->file_name."/".$this->file_name."-240.m3u8 2> ".TEMP_DIR."/".$tmp_file."_ffmpeg_hls_240_output.tmp";


					logData("FFMPEG hls240Command command : ".$hls240Command,'tester');



					if($convertedRes!=NULL){
						$hls240Output = $this->exec($hls240Command);
					}



					if(file_exists(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_240_output.tmp")){
						$hls240Output = $hls240Output ? $hls240Output : join("", file(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_240_output.tmp"));
						unlink(TEMP_DIR.'/'.$tmp_file."_ffmpeg_hls_240_output.tmp");
					}


					if($hls240Output!=NULL){
						$this->log .= "\n\n==  FFMPEG HLS 240 Command ==\n\n";
						$this->log .=$hls240Command;
						$this->log .= "\n\n==  FFMPEG HLS 240 OutPut ==\n\n";
						$this->log .=$hls240Output;
					}


					$txt = "#EXT-X-STREAM-INF:PROGRAM-ID=0,BANDWIDTH=1755600,RESOLUTION=428x240\n";
					fwrite($myfile, $txt);
					$txt = $this->file_name."/".$this->file_name."-240.m3u8\n";
					fwrite($myfile, $txt);


				}



			}
			else{

				$this->log .= "\r\n\r\HLS commands res are empty!\r\n\r\n";
			}

			

			$this->log .="\r\n\r\nEnd resolutions @ ".date("Y-m-d H:i:s")."\r\n\r\n";



			fclose($myfile);
			


			// HLS call back
			$ext='m3u8';
			$this->callBack( false, $convertedRes, $ext);

		}

			// $this->log_ouput_file_info();
	}

	/**
    * Used for Callback to give back response to cb APP
    * @todo    : Used for Callback to give back response to cb APP and acknowledge cb that conversion is being done!
    * @since   : mon 9th oct, 2017 Feedback 1.0
    * @author  : Awais Fiaz
    */
	function callBack($for_iphone=false,$convertedRes=NULL,$ext=NULL)
	{
    	# code...
		$call_bk = CALLBACK_URL;


		if(!empty($convertedRes))
		{
			if(!file_exists($this->raw_path.'/'.$this->file_name.".".$ext))
			{
				$this->log("conversion_status","failed");
				$this->conv_status = 'failed';
				$status = 'failed';
			}else
			{
				$this->log("conversion_status","completed");
				$this->conv_status = 'completed';
				$status = 'completed';
			}
		}
		if(isset($call_bk) && !empty($convertedRes) )
		{
			$array = array(
				'before_full_convert' => true,
				'callback' => true,
				'secret_key' => SECRET_KEY,
				'file_server_path' => BASEURL.'/files',
				'files_thumbs_path' => BASEURL.'/files/thumbs',
				'file_thumbs_count' => get_thumbs($file_name,true,$folder),
				'duration' => $this->input_details['duration'],
				'has_hd'	=> $this->has_hd,
				'file_name'	=> ($this->file_name),
				'has_mobile' => $this->has_mobile,
				'filegrp_size' => $file_size+$hq_size+$thumbs_size+$hd_size+$m_size,
				'process_status' => 2,
				'conv_status' => $status,
				'folder'	=> $folder,
				'file_directory'	=> $this->file_directory,
				'has_sprite'	=> $this->has_sprite,
				'sprite_count'	=> $this->sprite_count,
				'version' => VERSION,
				'has_resolution' =>'yes',
				'video_files' =>json_encode($this->video_files),
				'current_progress' => $current_progress,
				'conversion_log' => file_get_contents($this->log_file)
			);
			logData(json_encode($this->video_files),'new2');
			logData(BASEURL.'/files','new2');
			logData($this->file_name,'new2');
			logData($folder,'new2');
			
			//pr($array,true);
			$ch = curl_init($call_bk);

			$ch_opts = array(
				CURLOPT_POST=>true,
				CURLOPT_RETURNTRANSFER=> true,
			 //CURLOPT_BINARYTRANSFER => true,
				CURLOPT_HEADER => false,
				CURLOPT_SSL_VERIFYHOST=> false, 
				CURLOPT_SSL_VERIFYPEER=> false,
				CURLOPT_HTTPHEADER => array("Expect:"),
			);


			//curl_setopt($ch,CURLOPT_POSTFIELDS,$array);
			$charray = $ch_opts;
			$charray[CURLOPT_POSTFIELDS] = $array;


			curl_setopt_array($ch,$charray);

			$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

			$result = curl_exec($ch);	
			//logData('results=>'. $result);
			logData(date("[Y-m-d H:i:s]")."\t Video file : ".$suffix."\n".json_encode($array)."\n \n Callback URL : ".$call_bk." \n\n Response = $result \n \n Retune code : $returnCode \n \n \n",false,LOGS_DIR."/".$this->video_folder."/"."call_back-".$this->file_name.".log",true);
		}

		logData('file=> '.$this->raw_path.'/'.$this->file_name.".".$ext,'status');
		logData('status=> '.file_exists($this->raw_path.'/'.$this->file_name.".".$ext),'status');



		$this->create_log_file();
		$this->log = '';
	}


	/**
    * Used to add watermark on raw video before conversion 
    * @todo    : This Function adds watermark just before conversion
    * @since   : 11th oct, 2017 Feedback 1.0
    * @author  : Awais Fiaz
    */
	
	function add_watermark(){

		$w = $this->configs;

		$w_output="";

		$opt_av .= "-filter_complex 'overlay=10:10' -y ";

		if($w["watermark_video"]==1){

			$this->log .= "\r\nAdding custom watermark to Video file ".$this->file_name." @ ".date("Y-m-d H:i:s")." \r\n";
			
			logData("ffmpeg watermark dir : ".BASEDIR,'new2');
			
			$command  = $this->ffmpeg." -i ".$this->input_file." -i ".BASEDIR."/video_watermark.png"." $opt_av".CON_DIR."/".$this->file_name."-wm.mp4 2> ".TEMP_DIR."/".$tmp_file."ffmpeg_watermark_output.tmp";
			
			logData("ffmpeg watermark command : ".$command,'new2');

		}

		if($command!=NULL){

			$w_output = $this->exec($command);

		}
		if(file_exists(TEMP_DIR.'/'.$tmp_file."ffmpeg_watermark_output.tmp")){

			$w_output = $w_output ? $w_output : join("", file(TEMP_DIR.'/'.$tmp_file."ffmpeg_watermark_output.tmp"));
			unlink(TEMP_DIR.'/'.$tmp_file."ffmpeg_watermark_output.tmp");
		}

			// $this->output_file = $this->raw_path."/".$this->input_file.".mp4";

		if($command!=NULL){	
			$this->log .= "\r\n\r\n== Watermark Command == \r\n\r\n";
			$this->log .= $command;
			$this->log .="\r\n\r\n== Watermark OutPut == \r\n\r\n";
			$this->log .=$w_output;
		}

	}
	
	

	/**
	 * Function used to get file information using FFMPEG
	 * @param FILE_PATH
	 */
	

	function get_file_info($file_path=NULL)
	{
		if(!$path_source)
			$path_source = $this->input_file;

		$info['format']          = 'N/A';
		$info['duration']       = 'N/A';
		$info['size']           = 'N/A';
		$info['bitrate']        = 'N/A';
		$info['video_width']    = 'N/A';
		$info['video_height']   = 'N/A';
		$info['video_wh_ratio'] = 'N/A';
		$info['video_codec']    = 'N/A';
		$info['video_rate']     = 'N/A';
		$info['video_bitrate']  = 'N/A';
		$info['video_color']    = 'N/A';
		$info['audio_codec']    = 'N/A';
		$info['audio_bitrate']  = 'N/A';
		$info['audio_rate']     = 'N/A';
		$info['audio_channels'] = 'N/A';
		$info['path']           = $path_source;



		$cmd = FFPROBE. " -v quiet -print_format json -show_format -show_streams '".$path_source."' ";
		logData($cmd,"testingggg.");
		$output = shell_output($cmd);
        //$output = trim($output);
        //$output = preg_replace('/(\n]+){/', '', $output);
		$output = preg_replace('/([a-zA-Z 0-9\r\n]+){/', '{', $output, 1);


		$data = json_decode($output,true);


		if($data['streams'][0]['codec_type'] == 'video')
		{
			$video = $data['streams'][0];
			$audio = $data['streams'][1];
		}else
		{
			$video = $data['streams'][1];
			$audio = $data['streams'][0];
		}


		$info['format']         = $data['format']['format_name'];
		$info['duration']       = (float) round($video['duration'],2);

		$info['bitrate']        = (int) $data['format']['bit_rate'];
		$info['video_bitrate']  = (int) $video['bit_rate'];
		$info['video_width']    = (int) $video['width'];
		$info['video_height']   = (int) $video['height'];

		if($video['height'])
			$info['video_wh_ratio'] = (int) $video['width'] / (int) $video['height'];
		$info['video_codec']    = $video['codec_name'];
		$info['video_rate']     = $video['r_frame_rate'];
		$info['size']           = filesize($path_source);
		$info['audio_codec']    = $audio['codec_name'];;
		$info['audio_bitrate']  = (int) $audio['bit_rate'];;
		$info['audio_rate']     = (int) $audio['sample_rate'];;
		$info['audio_channels'] = (float) $audio['channels'];;
		$info['rotation']       = (float) $video['tags']['rotate'];


		if(!$info['duration'] || 1)
		{
			$CMD = MEDIAINFO_BINARY . "   '--Inform=Video;%Duration%'  '". $path_source."' 2>&1 ";
            //logData($CMD,'test');
			$duration = $info['duration'] = round(shell_output( $CMD )/1000,2);
		}

		$this->raw_info = $info;
		$video_rate = explode('/',$info['video_rate']);
		$int_1_video_rate = (int)$video_rate[0];
		$int_2_video_rate = (int)$video_rate[1];



		$CMD = MEDIAINFO_BINARY . "   '--Inform=Video;'  ". $path_source;

		$results = shell_output($CMD);
		$needle_start = "Original height";
		$needle_end = "pixels"; 
		$original_height = find_string($needle_start,$needle_end,$results);
		if(!empty($original_height)&&$original_height!=false)
		{
			$o_height = trim($original_height[1]);
			$o_height = (int)$o_height;
			if($o_height!=0&&!empty($o_height))
			{
				$info['video_height'] = $o_height;
			}
        	//logData(,'height');
		}
		$needle_start = "Original width";
		$needle_end = "pixels"; 
		$original_width = find_string($needle_start,$needle_end,$results);
		if(!empty($original_width)&&$original_width!=false)
		{
			$o_width = trim($original_width[1]);
			$o_width = (int)$o_width;
			if($o_width!=0&&!empty($o_width))
			{
				$info['video_width'] = $o_width;
			}

		}

		if($int_2_video_rate!=0)
		{
			$info['video_rate'] = $int_1_video_rate/$int_2_video_rate;
		}
		logData(json_encode($info),$this->file_name.'_configs');
		return $info;

	}
	
	
    /**
	 * Function used to get file information using FFMPEG
	 * @param FILE_PATH
	 */
    function _get_file_info( $path_source =NULL) 
    {

    	if(!$path_source)
    		$path_source = $this->input_file;

		# init the info to N/A
    	$info['format']			= 'N/A';
    	$info['duration']		= 'N/A';
    	$info['size']			= 'N/A';
    	$info['bitrate']		= 'N/A';
    	$info['video_width']	= 'N/A';
    	$info['video_height']	= 'N/A';
    	$info['video_wh_ratio']	= 'N/A';
    	$info['video_codec']	= 'N/A';
    	$info['video_rate']		= 'N/A';
    	$info['video_bitrate']	= 'N/A';
    	$info['video_color']	= 'N/A';
    	$info['audio_codec']	= 'N/A';
    	$info['audio_bitrate']	= 'N/A';
    	$info['audio_rate']		= 'N/A';
    	$info['audio_channels']	= 'N/A';
    	$info['path']			= $path_source;


		# get the file size
    	$stats = @stat( $path_source );
    	if( $stats === false )
    		$this->log .= "Failed to stat file $path_source!\n";

        //$this->ffmpeg." -i $path_source -acodec copy -vcodec copy -f null /dev/null 2>&1";

         //if (stristr(PHP_OS, 'WIN')) {
        //     $this->log .= $output = $this->exec( $this->ffmpeg." -i '$path_source' -acodec copy -vcodec copy -f null " );
         //}else
    	$output = shell_output( $this->ffmpeg." -i '$path_source' -acodec copy -vcodec copy -f null /dev/null 2>&1" );


    	$this->parse_format_info( $output );
    	$info = $this->raw_info ;
    	$info['size'] = (integer)$stats['size'];


    	$CMD = MEDIAINFO_BINARY . "  '". $path_source."'  ";
    	$mioutput = shell_output( $CMD );

    	$mioutput = str_replace('"','',$mioutput);
    	$mediainfo = new Mediainfo($mioutput);
    	$m_info = $mediainfo->format();

    	$CMD = MEDIAINFO_BINARY . "   '--Inform=Video;%Duration%'  '". $path_source."' 2>&1 ";
    	$duration = $info['duration'] = round(shell_output( $CMD )/1000,2);

    	$bitrate = $this->pregMatch('([0-9.]+)([A-Za-z]+)',str_replace(' ','',$m_info['general']['bitrate']));
    	$bitrate_bit = $bitrate[2];
    	$bitrate = floatval($bitrate[1]);




    	$video_bitrate = $this->pregMatch('([0-9.]+)([A-Za-z]+)',str_replace(' ','',$m_info['video'][1]['bitrate']));
    	$video_bitrate_bit = $video_bitrate[2];
    	$video_bitrate = floatval($video_bitrate[1]);

    	$audio_bitrate = $this->pregMatch('([0-9.]+)([A-Za-z]+)',str_replace(' ','',$m_info['audio'][1]['bitrate']));
    	$audio_bitrate_bit = $audio_bitrate[2];
    	$audio_bitrate = floatval($audio_bitrate[1]);

    	$width = $this->pregMatch('([0-9.]+)',str_replace(' ','',$m_info['video'][1]['width']));
    	$height = $this->pregMatch('([0-9.]+)',str_replace(' ','',$m_info['video'][1]['height']));
    	$ratio = $this->pregMatch('([0-9:]+)',str_replace(' ','',$m_info['video'][1]['display aspect ratio']));
    	$frame_rate = $this->pregMatch('([0-9.]+)',str_replace(' ','',$m_info['video'][1]['framerate']));
    	$audio_rate = $this->pregMatch('([0-9.]+)',str_replace(' ','',$m_info['audio'][1]['sampling_rate']));
    	$video_codec = $m_info['video'][1]['codec'];
    	$audio_codec = $m_info['audio'][1]['codec'];
    	$audio_channels = $this->pregMatch('([0-9.]+)',str_replace(' ','',$m_info['audio'][1]['channels']));

    	$rotation = $m_info['video'][1]['rotation'];

    	$audio_bitrate = $audio_bitrate ? $audio_bitrate : ($bitrate - $video_bitrate);

    	$ratio = $ratio[1];
    	list($r_width,$r_height) = explode(':',$ratio);
    	if($r_height!=0)
    		$ratio = $r_width/$r_height;
    	else
    		$ratio = 1.77;




    	if(!$info['format'])
    		$info['format'] = $m_info['general']['format'];

    	if($bitrate_bit=='mbps')
    	{

    		$bitrate = $bitrate * 1000;
    	}

    	$info['bitrate'] = $bitrate * 1000;

    	if($video_bitrate_bit=='mbps')
    	{
    		$video_bitrate *=1000;
    	}

    	$info['video_bitrate'] = $video_bitrate * 1000;

    	if($audio_bitrate_bit=='mbps')
    	{
    		$audio_bitrate *=1000;
    	}

    	$info['audio_bitrate'] = $audio_bitrate * 1000;


    	if(!$info['video_rate'])
    		$info['video_rate'] = $frame_rate[1];


    	if(!$info['video_width'])
    		$info['video_width'] = $width[1];

    	if(!$info['video_height'])
    		$info['video_height'] = $height[1];

    	if(!$info['video_wh_ratio'])
    		$info['video_wh_ratio'] = $ratio;

    	if(!$info['video_codec'])
    		$info['video_codec'] = $video_codec;





    	if(!$info['video_color'])
    		$info['video_color'] = $m_info['video'][1]['color space'];

    	$info['audio_codec'] = $audio_codec;

    	$info['audio_rate'] = $audio_rate[1] * 1000;
    	$info['audio_channels'] = $audio_channels[1];
    	$info['rotation'] = $rotation;




		//$info = $this->raw_info ;
    	$this->raw_info = $info;
    	return $info;
    }

	/**
	 * Author : Arslan Hassan and Awais Tariq
	 * parse format info(uses mediainfo)
	 * 
	 * output (string)
	 *  - the ffmpeg output to be parsed to extract format info
	 * 
	 * info (array)
	 *  - see function get_encoding_progress
	 * 
	 * returns:

	 *  - (bool) false on error
	 *  - (bool) true on success
	 */

	function parse_format_info( $output ) 
	{
		//logData($path_source);
		$this->raw_info;
		$info =  $this->raw_info;
        //logData(complete_video_info($path_source,'Duration'));
        # search the output for specific patterns and extract info
        # check final encoding message

		if( $args = $this->pregMatch( 'video:([0-9]+)kB audio:([0-9]+)kB global headers:[0-9]+kB muxing overhead', $output) ) {
			$video_size = (float)$args[1];
			$audio_size = (float)$args[2];
		}

        # check for last enconding update message
		if($args =  $this->pregMatch( '(frame=([^=]*) fps=[^=]* q=[^=]* L)?size=[^=]*kB time=([^=]*) bitrate=[^=]*kbits\/s[^=]*$', $output) ) {
			$frame_count = $args[2] ? (float)ltrim($args[2]) : 0;
			$duration    = (float)$args[4];
		} 

		if(empty($video_height)||$video_height<10)
		{
			$video_height = (integer)complete_video_info($path_source,'Height');
		}


		if(empty($video_width) && empty($video_height) && empty($video_wh_ratio))
		{
            # get video information
			if(  $args= $this->pregMatch( '([0-9]{2,4})x([0-9]{2,4})', $output ) ) 
			{

				$video_width = $args[1];
				$video_height = $args[2];
				$video_wh_ratio = (float)$video_width / (float)$video_height;
			}
		}
		$info['video_width'  ] = $video_width;
		$info['video_height' ] = $video_height;
		$info['video_wh_ratio'] = (float)$video_wh_ratio;
		if($args= $this->pregMatch('Video: ([^ ^,]+)',$output))
			{
				$video_codec = $args[1];
			}
			$video_codec = trim($video_codec);
			if(empty($video_codec))
			{
				$video_codec = complete_audio_info($path_source,'Format');  
			}
			$info['video_codec'] = $video_codec;
        # get audio information
			if($args =  $this->pregMatch( "Audio: ([^ ]+), ([0-9]+) Hz, ([^\n,]*)", $output) ) 
				{
					$audio_codec =  $args[1];
					$audio_rate = $args[2];
					$audio_channels =  $args[3];
				}
				$audio_codec = trim($audio_codec);
				$audio_rate = trim($audio_rate);
				$audio_channels = trim($audio_channels);
				if(empty($audio_codec) && empty($audio_rate) && empty($audio_channels))
				{
					$audio_codec =  complete_audio_info($path_source,'Format');
					$audio_rate = complete_audio_info($path_source,'SamplingRate');
					$audio_channels =  complete_audio_info($path_source,'Channel(s)');
				}
				$info['audio_codec'   ] = $audio_codec;
				$info['audio_rate'    ] = $audio_rate;
				$info['audio_channels'] = $audio_channels;
				if(!$audio_codec || !$audio_rate)
				{
					$args =  $this->pregMatch( "Audio: ([a-zA-Z0-9]+)(.*), ([0-9]+) Hz, ([^\n,]*)", $output);
					$info['audio_codec'   ] = $args[1];
					$info['audio_rate'    ] = $args[3];
					$info['audio_channels'] = $args[4];
				}
				logData(json_encode($info),'configs');
				$this->raw_info = $info;
        # check if file contains a video stream
				return $video_size > 0;

			}




	/**
	 * Function used to excute SHELL Scripts
	 */
	function exec( $cmd ) {
		# use bash to execute the command
		# add common locations for bash to the PATH
		# this should work in virtually any *nix/BSD/Linux server on the planet
		# assuming we have execute permission
		//$cmd = "PATH=\$PATH:/bin:/usr/bin:/usr/local/bin bash -c \"$cmd\" ";
		return shell_exec( $cmd);
	}
	
	
	function pregMatch($in,$str)
	{	
		preg_match("/$in/i",$str,$args);
		return $args;
	}
	
	
	/**
	 * Function used to insert data into database
	 * @param ARRAY
	 */
	
	function insert_data()
	{
		global $db;
		//Insert Info into database
		if(is_array($this->input_details))
		{
			foreach($this->input_details as $field=>$value)
			{
				$fields[] = 'src_'.$field;
				$values[] =  $value;
			}
			$fields[] = 'src_ext';
			$values[] = getExt($this->input_details['path']);
			$fields[] = 'src_name';
			$values[] = getName($this->input_details['path']);
			
			$db->insert(tbl($this->tbl),$fields,$values);	
			$this->row_id = $db->insert_id();
		}
	}
	
	/**
	 * Function used to update data of
	 */
	function update_data($conv_only=false)
	{
		global $db;
		//Insert Info into database
		if(is_array($this->output_details) && !$conv_only)
		{
			foreach($this->output_details as $field=>$value)
			{
				$fields[] = 'output_'.$field;
				$values[] = $value;
			}		
			$fields[] = 'file_conversion_log';
			$values[] = $this->log;
			$db->update(tbl($this->tbl),$fields,$values," id = '".$this->row_id."'");	
		}else
		$fields[] = 'file_conversion_log';
		$values[] = $this->log;
		$db->update(tbl($this->tbl),$fields,$values," id = '".$this->row_id."'");	
	}
	
	
	/**
	 * Function used to add log in log var
	 */
	function log($name,$value)
	{
		$this->log .= $name.' : '.$value."\r\n";
	}
	
	/**
	 * Function used to start log
	 */
	function start_log()
	{
		$this->log = "Started on ".NOW()." - ".date("Y M d")."\r\n\n";
		$this->log .= "Checking File ....\r\n";

		$this->log('File',$this->input_file);
	}
	
	/**
	 * Function used to log video info
	 */
	function log_file_info()
	{
		$details = $this->input_details;
		if(is_array($details))
		{
			foreach($details as $name => $value)
			{
				$this->log($name,$value);
			}
		}else{
			$this->log .=" Unknown file details - Unable to get video details using FFMPEG \n";
		}
	}
	/**
	 * Function log outpuit file details
	 */
	function log_ouput_file_info()
	{
		$details = $this->output_details;
		if(is_array($details))
		{
			foreach($details as $name => $value)
			{
				$this->log('output_'.$name,$value);

			}
		}else{
			$this->log .=" Unknown file details - Unable to get output video details using FFMPEG \n";
		}
	}

	
	
	
	/**
	 * Function used to time check
	 */
	function time_check()
	{
		$time = microtime();
		$time = explode(' ',$time);
		$time = $time[1]+$time[0];
		return $time;
	}
	
	/**
	 * Function used to start timing
	 */
	function start_time_check()
	{
		$this->start_time = $this->time_check();
	}
	
	/**
	 * Function used to end timing
	 */
	function end_time_check()
	{
		$this->end_time = $this->time_check();
	}
	
	/** 
	 * Function used to check total time 
	 */
	function total_time()
	{
		$this->total_time = round(($this->end_time-$this->start_time),4);
	}
	
	
	/**
	 * Function used to calculate video padding
	 */
	function calculate_size_padding( $parameters, $source_info)	
	{
		global $width, $height, $ratio, $pad_top, $pad_bottom, $pad_left, $pad_right;
		$p = $parameters;
		$i = $source_info;

		switch( $p['resize'] ) {
			# dont resize, use same size as source, and aspect ratio
			# WARNING: some codec will NOT preserve the aspect ratio
			case 'no':
			$width      = $i['video_width'   ];
			$height     = $i['video_height'  ];
			$ratio      = $i['video_wh_ratio'];
				//$ratio = 1.77;
			$pad_top    = 0;
			$pad_bottom = 0;
			$pad_left   = 0;
			$pad_right  = 0;
			break;
			# resize to parameters width X height, use same aspect ratio
			# WARNING: some codec will NOT preserve the aspect ratio
			case 'WxH':
			$width  = $p['video_width'   ];
			$height = $p['video_height'  ];
			$ratio  = $i['video_wh_ratio'];
				//$ratio = 1.77;
			$pad_top    = 0;
			$pad_bottom = 0;
			$pad_left   = 0;
			$pad_right  = 0;
			break;
			# make pixel square
			# reduce video size if bigger than p[width] X p[height]
			# and preserve aspect ratio
			case 'max':
			$width        = (float)$i['video_width'   ];
			$height       = (float)$i['video_height'  ];
				//$ratio        = (float)$i['video_wh_ratio'];
			$ratio = 1.77;
			$max_width    = (float)$p['video_width'   ];
			$max_height   = (float)$p['video_height'  ];

				# make pixels square
			if( $ratio > 1.0 )
				$width = $height * $ratio;
			else
				$height = @$width / $ratio;

				# reduce width
			if( $width > $max_width ) {
				$r       = $max_width / $width;
				$width  *= $r;
				$height *= $r;
			}

				# reduce height
			if( $height > $max_height ) {
				$r       = $max_height / $height;
				$width  *= $r;
				$height *= $r;
			}

				# make size even (required by many codecs)
			$width  = (integer)( ($width  + 1 ) / 2 ) * 2;
			$height = (integer)( ($height + 1 ) / 2 ) * 2;
				# no padding
			$pad_top    = 0;
			$pad_bottom = 0;
			$pad_left   = 0;
			$pad_right  = 0;
			break;
			# make pixel square
			# resize video to fit inside p[width] X p[height]
			# add padding and preserve aspect ratio
			case 'fit':
				# values need to be multiples of 2 in the end so
				# divide width and height by 2 to do the calculation
				# then multiply by 2 in the end
			$ratio        = (float)$i['video_wh_ratio'];
			$width        = (float)$i['video_width'   ] / 2;
			$height       = (float)$i['video_height'  ] / 2;
			$trt_width    = (float)$p['video_width'   ] / 2;
			$trt_height   = (float)$p['video_height'  ] / 2;

				# make pixels square
			if( $ratio > 1.0 )
				$width = $height * $ratio;
			else
				$height = $width / $ratio;

				# calculate size to fit
			$ratio_w = $trt_width  / $width;
			$ratio_h = $trt_height / $height;

			if( $ratio_h > $ratio_w ) {
				$width  = (integer)$trt_width;
				$height = (integer)($width / $ratio);
			} else {
				$height = (integer)$trt_height;
				$width  = (integer)($height * $ratio);
			}

				# calculate padding
			$pad_top    = (integer)(($trt_height - $height + 1) / 2);
			$pad_left   = (integer)(($trt_width  - $width  + 1) / 2);
			$pad_bottom = (integer)($trt_height  - $height - $pad_top );
			$pad_right  = (integer)($trt_width   - $width  - $pad_left);

				# multiply by 2 to undo division and get multiples of 2
			$width      *= 2;
			$height     *= 2;
			$pad_top    *= 2;
			$pad_left   *= 2;
			$pad_bottom *= 2;
			$pad_right  *= 2;
			break;
		}
	}
	
	
	/**
	 * this will first check if there is a conversion lock or no
	 * if there is a lock then wait till its delete otherwise create a lock and move forward
	 */
	function isLocked($num=1)
	{
		if(!$num || $num<1)
			return true;

		for($i=0;$i<=$num;$i++)
		{
			$conv_file = TEMP_DIR.'/conv_lock'.$i.'.loc';
			if(!file_exists($conv_file))
			{
				$this->lock_file = $conv_file;
				$file = fopen($conv_file,"w+");
				fwrite($file,"converting..");
				fclose($file);
				return false;
			}
		}
		
		return true;
	}

	 /** 
	 * Function used to generate sprites of video
	 */

	 private function generate_sprites(){

	 	$this->log .= "\r\n Genrating Video Sprite Starting \r\n";
	 	logData('checkpoint 1','gen sprite 123');
	 	try{

	 		$tmp_file = time().RandomString(5).'.tmp';
	 		$round_duration = round($this->input_details['duration'], 0);
	 		$interval = $round_duration / 10 ;
	 		mkdir(SPRITES_DIR . '/' . $this->filetune_directory, 0777, true);				
	 		$this->sprite_output = SPRITES_DIR.'/'.$this->filetune_directory.$this->file_name."_%d.png";

	 		$command = $this->ffmpeg." -i ".$this->input_file." -f image2 -s 168x105 -bt 20M -vf fps=1/".$interval." ".$this->sprite_output;
	 		$this->log .= "\r\nSprite Command : ".$command."\r\n";
	 		$output = $this->exec($command);
	 		$this->log .= "\r\nSprite Command Output : ".$output."\r\n";

			#Fetching all the components of sprite
	 		$sprite_components = glob(SPRITES_DIR.'/'.$this->filetune_directory.$this->file_name."_*.png");

	 		logData('checkpoint 2','gen sprite 123');

	 		if (!empty($sprite_components) && is_array($sprite_components) ){
	 			logData('checkpoint 3','gen sprite 123');
	 			natsort($sprite_components);
	 			$imploed_components = implode(" ", $sprite_components);
	 			$sprite_count = count($sprite_components);
	 			$this->sprite_count = $sprite_count;
	 			$this->log .= "\rSprite Count : ".$this->sprite_count."\r\n";
	 			$mogrify_command = "mogrify -geometry 100x ".$imploed_components;

	 			$this->log .= "\rMogrify Command : ".$mogrify_command."\r\n";
	 			$mogrify_output = $this->exec($mogrify_command);

	 			$itendify_command = 'identify -format "%g - %f" '.$sprite_components[0];
	 			$this->log .= "\rItendify Command : ".$itendify_command."\r\n";
	 			$dimesnions_output = $this->exec($itendify_command);
	 			$this->log .= "\r\rItendify Command Output : ".$dimesnions_output."\r\n";

	 			$this->output_sprite_file =  SPRITES_DIR.'/'.$this->filetune_directory.$this->file_name."-sprite.png";

	 			$montage_command = "montage ".$imploed_components." -tile 12x1 -geometry 100x63+0+0 ".$this->output_sprite_file;

	 			$this->log .= "\rMontage Command : ".$montage_command."\r\n";
	 			$montage_output = $this->exec($montage_command);
	 			$this->log .= "\r\rMontage Command Output : ".$montage_output."\r\n";
	 			$this->log .= "Output File : ".$this->output_sprite_file."\r\n";
	 			logData('checkpoint 4','gen sprite 123');
	 			if (file_exists($this->output_sprite_file)){
	 				for ($i=0; $i < $this->sprite_count; $i++) { 
	 					unlink($sprite_components[$i]);
	 				}
	 			}
	 		}

	 		logData('checkpoint 5','gen sprite 123');
	 	}catch(Exception $e){
	 		$this->log .= "\r\n Errot Occured : ".$e->getMessage()."\r\n";
	 	}
	 	logData('checkpoint 6','gen sprite 123');
	 	$this->log .= "\r\n ====== End : Sprite Generation ======= \r\n";

	 }

	/** 
	 * Function used to perform all actions when converting a video
	 */
	function ClipBucket()
	{
		$conv_file = TEMP_DIR.'/conv_lock.loc';
		//We will now add a loop
		//that will check weather
		
		while(1)
		{

			if(!$this->isLocked(PROCESSESS_AT_ONCE))
			{
				//logData('hrer');
				$this->start_time_check();
				$this->start_log();				
				
				$this->prepare();
				
				$ratio = substr($this->input_details['video_wh_ratio'],0,3);
				

				if(!$this->configs['video_width'])
				{
					if($ratio=='1.7')
					{
						$dims_normal = $this->configs['res169'][$this->configs['normal_res']];
						$dims_high = $this->configs['res169'][$this->configs['high_res']];
					}else
					{
						$dims_normal = $this->configs['res43'][$this->configs['normal_res']];
						$dims_high = $this->configs['res43'][$this->configs['high_res']];
					}
					
					$this->configs['video_width'] = $dims_normal[0];
					$this->configs['video_height'] =  $dims_normal[1];
					
					$this->configs['hq_video_width'] = $dims_high[0];
					$this->configs['hq_video_height'] =  $dims_high[1];
				}
				
				//Setting When and what to do
				$this->convert(NULL,false);


				
				$th_dim = $this->thumb_dim;
				$big_th_dim = $this->big_thumb_dim ;
				//Generating Thumb
				if($this->gen_thumbs)
				{
					logData('generate_thumbs=>'.' input file => '.$this->input_file.' duration =>'.$this->input_details['duration'].' number of thumbs =>'.$this->num_of_thumbs,'thumbs');


					$this->generate_thumbs($this->input_file,$this->input_details['duration'],$th_dim,$this->num_of_thumbs,'');


				}
				if($this->resolutions == 'yes'){
					
					$res169= array();
					$res169=$this->configs['res169'];



					logData('resolution =>'.json_encode($res169),'test');
					logData('resolution info =>'.json_encode($this->input_details),'resolution');

					$input_height = $this->input_details['video_height'];

					$ffmpeg_obj = $this;
					foreach ($res169 as $key => $value) 
					{
						$video_width=(int)$value[0];
						$video_height=(int)$value[1];
						if($this->conv_status != 'failed' && $video_height==$this->input_details['video_height'] && $this->input_details['video_codec']=='h264'  && $this->input_details['audio_codec']=='aac' && getExt($this->input_file)=='mp4')   
						{
							logData('condition12 =>'." status ".$this->conv_status ." Height ".$this->input_details['video_height'].' and  '.$this->input_details['video_codec'].$this->input_details['audio_codec'].getExt($this->input_file),'test');

							$res169 = reindex_required_resolutions($res169,$ffmpeg_obj);
						}
					}
					
					
					// calling method for watermarking if watermark is enabled
					if($this->configs['watermark_video'] == 1){
					// $this->log->writeLine("Watermarking", "Starting");
						$this->add_watermark();
					}
					logData('right before generating sprites','gen sprite 123');
					//Genrating sprite for the video 
					$this->generate_sprites();


					if (!file_exists($this->raw_path,$this->file_name))
					{
						@mkdir($this->raw_path, 0777, true);
					}
					
					//array to get all converted resolutions
					$convertedRes=array();

					logData($res169,'resolutions');
					$total_res = count($res169);
					$incr_prog = 100 / $total_res;
					$current_progress = "0";
					foreach ($res169 as $value) 
					{

						$video_width=(int)$value[0];
						$video_height=(int)$value[1];
						

						logData($this->input_details,"test");
						logData('condition =>'.$this->input_details['video_height'].' and  '.$video_height,'test');
						if($this->input_details['video_height'] > $video_height-1)
						{

							$more_res['video_width'] = $video_width;
							$more_res['video_height'] = $video_height;
							$more_res['name'] = $video_height;
							$this->convert(NULL,false,$more_res,$current_progress);
							$current_progress = $current_progress + $incr_prog;
							$convertedRes[$more_res['video_height']]=$more_res['video_height'];

						}
					}
					logData($this->configs,'new3');
					if($this->configs['stream_via']=='dash'){
							// DASHing video files
						logData('this messages is from dash condition','testeryoo');	
						$this->genDash(NULL, false, $res169 , $convertedRes);
						
					}else if($this->configs['stream_via']=='hls'){
							// converting video files for HLS
						logData('this messages is from hls condition','testeryoo');
						$this->genHls(NULL, false, $res169 , $convertedRes);
					}

				}
				
				$this->end_time_check();
				$this->total_time();
				
				//Copying File To Original Folder
				if($this->keep_original=='yes')
				{
					$this->log .= "\r\nCopy File to original Folder";
					if(copy($this->input_file,$this->original_output_path))
						$this->log .= "\r\File Copied to original Folder...";
					else
						$this->log .= "\r\Unable to copy file to original folder...";
				}
				
				$this->output_details = $this->get_file_info($this->output_file);
				
				

				$this->create_log_file();
				
				
				//Send call back...
				
				$call_bk = CALLBACK_URL;
				//Counting size
				$file_size = @filesize($this->output_file);


				$thumbs_size = 0;
				//Counting thumb size
				$vid_thumbs = glob(THUMBS_DIR."/".$this->video_folder.getName($this->input_file)."*");
				
				#replace Dir with URL
				if(is_array($vid_thumbs))
					foreach($vid_thumbs as $thumb)
					{
						if(file_exists($thumb))
							$thumbs_size += filesize($thumb);
					}

					$sprite_no_thumbs = "";
					if($this->generate_sprite=='yes')
					{
						$tr = new ThumbRotate();

						$sprite_thumbs_no = $tr->generateSprite(array
							(
								"file_name"=>getName($this->input_file),
								"file_directory" => $this->video_folder,
								"duration"=>$this->input_details['duration'])
						);

						$this->has_sprite = 1;
					}


					$this->file_server_path = BASEURL.'/files';



					if(file_exists($this->lock_file))
						unlink($this->lock_file);

					break;
				}
				else
				{
					sleep(10);
				}
			}
		}



	/**
	 * Function used to generate video thumbnails
	 */
	function generate_thumbs($input_file,$duration,$dim='120x90',$num,$prefix=NULL, $rand=NULL,$gen_thumb=FALSE,$output_file_path=false,$specific_dura=false)
	{

		if($specific_dura)
		{
			$durations_format = gmdate("H:i:s", $duration);
			$command = $this->ffmpeg."  -ss ".$durations_format."  -i $input_file    -r 1 $dimension -y -f image2 -vframes 1 $output_file_path ";
			//pr($command,true);
			shell_output($command);
		}
		$tmpDir = TEMP_DIR.'/'.getName($input_file);
		if(!file_exists($tmpDir))
			mkdir($tmpDir,0777,true);
		$output_dir = THUMBS_DIR;
		$dimension = '';

		$original_duration = $duration;

		$duration = (int)$duration;
		$half_vid_duration =(int) ($duration/2);
		$one_third_duration =(int) ($duration/3);
		$one_forth_duration =(int) ($duration/4);
		$durations = array($half_vid_duration,$one_third_duration,$one_forth_duration);
		foreach ($durations as $key => $duration) 
		{
			$key1 = $key+1;
			$this->log .=  "\r\n=====THUMBS LOG========";	

			$file_name = $this->file_name."-{$prefix}{$key1}.jpg";

			$file_path = THUMBS_DIR.'/'.$this->video_folder.$file_name;
			
			
			$this->log .=  "\r\n";	
			$duration = $duration + 6;
			if($duration>$original_duration)
			{
				$duration = $original_duration - 4;
			}
			$durations_format = gmdate("H:i:s", $duration);
			
			$this->log .= $command = $this->ffmpeg." -ss ".$durations_format." -i $input_file    -r 1 $dimension -y -f image2 -vframes 1 $file_path ";
			
			logData('thumbs_command=> '.$command,'thumbs');
			$this->exec($command);
			//pr($command,true);
			//checking if file exists in temp dir
			if(file_exists($tmpDir.'/00000001.jpg'))
			{
				//$this->log .=  "\r\n";		
				//$this->log .=  THUMBS_DIR.'/'.$this->video_folder.$file_name;
				rename($tmpDir.'/00000001.jpg',THUMBS_DIR.'/'.$this->video_folder.$file_name);
			}
			if(file_exists($tmpDir))
				rmdir($tmpDir);
		}
	}
	
	
	
	/**
	 * Function used to convert seconds into proper time format
	 * @param : INT duration
	 * @parma : rand
	 */

	function ChangeTime($duration, $rand = "") {
		if($rand != "") {
			if($duration / 3600 > 1) {
				$time = date("H:i:s", $duration - rand(0,$duration));
			} else {
				$time =  "00:";
				$time .= date("i:s", $duration - rand(0,$duration));
			}
			return $time;
		} elseif($rand == "") {
			if($duration / 3600 > 1 ) {
				$time = date("H:i:s",$duration);
			} else {
				$time = "00:";
				$time .= date("i:s",$duration);
			}
			return $time;
		}
	}
	
	/**
	 * Function used to create log for a file
	 */
	function create_log_file()
	{
		$file = $this->log_file;
		//logData('create_log=>'.$file);
		$data = $this->log;
		$fo = fopen($file,"a");
		if($fo)
		{
			fwrite($fo,$data);
		}
		fclose($fo);
	}
	
	
	function validChannels($in)
	{
		if(!$in)
			return true;
		$in['audio_channels'] = strtolower($in['audio_channels']);
		$channels = false;
		if(is_numeric($in['audio_channels']))
			$channels = $in['audio_channels'];
		else
		{
			if(strstr($in['audio_channels'],'stereo'))
				$channels = 2;
			
			if(strstr($in['audio_channels'],'mono'))
				$channels = 1;

			if(!$channels)
			{
				preg_match('/([0-9.]+)/',$in['audio_channels'],$matches);
				if($matches)
					$channels = $matches[1];
			}
		}
		
		if(!$channels)
			return true;
		elseif($channels>2)
			return false;
		else
			return true;
	}
}
?>