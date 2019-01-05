<?php

/**
 * Stats generation for server
 * it will generate
 * - space used
 * - total files
 * - files uploaded on hourly basis
 * - spaces used on hourly basis
 */
 
 include("config.php");
 
 if($_REQUEST['get_status'])
 {
	 $stats = array();
	 $cpu_usage = shell_exec("uptime");
	 preg_match("/load averages?\: ([0-9.]+)/",$cpu_usage,$matches);
	 $stats['cpu'] = round($matches[1],1);
	 $memory_status = shell_exec("free");
	 
	 preg_match("/Mem\:([ ]+)([0-9]+)([ ]+)([0-9]+)([ ]+)([0-9]+)/",$memory_status,$mem_matches);
	 
	 $mem = $mem_matches[4]/$mem_matches[2] * 100;
	 $stats['mem'] = round($mem,1);
	 
	 echo json_encode($stats);
 }
 
 
 if($_REQUEST['get_stats'])
 {
	 $stats = array();
	 //counting files in videos directory
	 $stats['video_files'] = preg_replace("/\n/","",shell_exec("cd ".BASEDIR."/files/videos && find . -type f | wc -l"));
	 if(!$stats['video_files'])
	 	$stats['video_files'] = 0;
	 $stats['thumb_files'] = preg_replace("/\n/","",shell_exec("cd ".BASEDIR."/files/thumbs && find . -type f | wc -l"));
	 if(!$stats['thumb_files'])
	 	$stats['thumb_files'] = 0;
	 $stats['photo_files'] = preg_replace("/\n/","",shell_exec("cd ".BASEDIR."/files/photo && find . -type f | wc -l"));
	 if(!$stats['photo_files'])
	 	$stats['photo_files'] = 0;
	 $stats['total_files'] = preg_replace("/\n/","",shell_exec("cd ".BASEDIR."/files && find . -type f | wc -l"));
	 if(!$stats['total_files'])
	 	$stats['total_files'] = 0;
	 
	 $vdo_size = shell_exec("du -mc ".BASEDIR.'/files/videos');
	 preg_match("/([0-9]+)\ttotal/",$vdo_size,$matches);
	 $stats['videos_size'] = $matches[1];
	 unset($matches);
	 
	 $thumbs_size = shell_exec("du -mc ".BASEDIR.'/files/thumbs');
	 preg_match("/([0-9]+)\ttotal/",$thumbs_size,$matches);
	 $stats['thumbs_size'] = $matches[1];
	 unset($matches);
	 
	 $vdo_size = shell_exec("du -mc ".BASEDIR.'/files/photos');
	 preg_match("/([0-9]+)\ttotal/",$vdo_size,$matches);
	 $stats['photos_size'] = $matches[1];
	 unset($matches);
	 
	 $total_size = shell_exec("du -mc ".BASEDIR.'/files');
	 preg_match("/([0-9]+)\ttotal/",$total_size,$matches);
	 $stats['total_size']  = $matches[1];
	 unset($matches);
	
	 echo json_encode($stats);
 }
 
 
 if($_REQUEST['gen_stats'])
 {
	$type = $_REQUEST['type'];
	$date = $_REQUEST['date'];
	
	if($date)
		$time = strtotime($date);
	else
		$time = strtotime(now());
	
	$year = date("Y",$time);
	$month = date("m",$time);
	$day = date("d",$time);
	
	$year_dir = LOGS_DIR.'/'.$year;
	$month_dir = $year_dir.'/'.$month;
	$day_dir = $month_dir.'/'.$day;
	
	switch($type)
	{
		case "month":
		default:
		{
			$min = 1;
			$max = 31;

			$data = "";
			for($i=$min;$i<=$max;$i++)
			{
				if(checkdate($month,$i,$year))
				{
					$stats = "";
					$soDay = $i;
					if($soDay < 10)
						$soDay = 0 .$soDay;
					else
						$soDay = "".$soDay."";
					if(file_exists($month_dir.'/'.$soDay.'/stats.txt'))
					{
						$dayData = json_decode(file_get_contents($month_dir.'/'.$soDay.'/stats.txt'),true);
						$stats  = array("uploads"=>$dayData['uploads'],"files"=>$dayData['files'],"size"=>genSize($dayData['size']));
					}else
					{
						$stats = array("files"=>0,"size"=>0,"uploads"=>0);
					}
					
					/*".date("D",
					strtotime(date("Y-m",
					$time)."-".$soDay))."*/
					
					if($data)
						$data .= ",";
					$data .= "[".$soDay.",".$stats['uploads'].",".$stats['size']."]\n";
					
					$newData[] = array($soDay,$stats['uploads'],$stats['size']);
				}
			}
			
			echo json_encode($newData);
			//echo $data;
			
		}
		break;
		
		
		
		case "day":
		{
			$min = 0;
			$max = 23;

			$data = "";
			for($i=$min;$i<=$max;$i++)
			{

				$stats = "";
				if($i<10)
					$i = '0'.$i;
				if(file_exists($day_dir.'/'.$i.'_stats.txt'))
				{
					$dayData = json_decode(file_get_contents($day_dir.'/'.$i.'_stats.txt'),true);
					$stats  = array("uploads"=>$dayData['uploads'],"files"=>$dayData['files'],"size"=>genSize($dayData['size']));
				}else
				{
					$stats = array("files"=>0,"size"=>0,"uploads"=>0);
				}
				
				if($data)
					$data .= ",";
				
				$hour = "".$i."";
				$data .= "[".$hour.",".$stats['uploads'].",".$stats['size']."]\n";
				
				$newData[] = array($hour,$stats['uploads'],$stats['size']);
			}
			
			echo json_encode($newData);
			//echo $data;
			
		}
		break;
		
		case "year":
		{
			$min = 1;
			$max = 12;

			$data = "";
			
			
						
			for($i=$min;$i<=$max;$i++)
			{
				$stats = "";
				$soMonth = $i;
						if($soMonth < 10)
							$soMonth = 0 .$soMonth;
						else
							$soMonth = "".$soMonth."";
				if(file_exists($year_dir.'/'.$soMonth.'/stats.txt'))
				{
					$dayData = json_decode(file_get_contents($year_dir.'/'.$soMonth.'/stats.txt'),true);
					$stats  = array("uploads"=>$dayData['uploads'],"files"=>$dayData['files'],"size"=>genSize($dayData['size']));
				}else
				{
					$stats = array("files"=>0,"size"=>0,"uploads"=>0);
					
				}
				
				if($data)
					$data .= ",";
				$data .= "[".date("M",
				strtotime(date("Y-".$i."-1"))).",".$stats['uploads'].",".$stats['size']."]\n";
				
				$newData[] = array(date("M",
				strtotime(date("Y-".$i."-1"))),$stats['uploads'],$stats['size']);
			}
			
			echo json_encode($newData);
			//echo $data;
			
		}
		break;
	}
	
 }
 
 function genSize($in)
 {
	 return round($in/1024/1024);
 }
 
?>