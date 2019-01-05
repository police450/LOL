<?php

if(!defined('IN_CLIPBUCKET'))
exit('Invalid access');




$year = $_POST['year'];
$month = $_POST['month'];


//Get Income of month

$month_data = array();


if(!$year || !is_numeric($year) || !checkdate(1,1,$year))
$year  = date("Y",time());


if($month!='no')
{
	if(!$month || !is_numeric($month) || !checkdate($month,1,$year))
	$month = date("m",time());
	$month_date = $year.'-'.$month;
	
	if($month < 10)
	{
		if(substr($month,0,1)!='0')
			$month = '0'.$month;
	}
}

//-------- MONTHLY REPORT FOR ORDERS -----------//
if($month!='no')
{
	$type = "order";
	while(1)
	{	
			
		$month_report = $paidSub->get_report($month_date,$type.'_month',false);
		
		if($month_report)
		{
			$month_rdata = $month_report['report_data'];
			$month_rdata = json_decode($month_rdata,true);
		}
		
		$total = 0;
		for($i=1;$i<32;$i++)
		{
			$date = $month_date.'-'.$i;
			
			//Checking if date is valid
			if(checkdate($month,$i,$year) && (!isset($month_rdata[$i]) || $date==date("Y-m-d",time())))
			{
				
				if($type=='order')
				{
					$count = $db->count(tbl('paid_orders'),"order_id"," 
					'$date' = date(date_added)  ");	
					$total += $month_data[$i] = $count;
				}else
				{
					$count = 0;
					$count = $db->select(tbl('paid_invoices')," SUM(amount_recieved) as income "," 
					'$date' = date(date_recieved)  ");	
					$count = $count[0]['income'];
					
					$total += $month_data[$i] =  (float) $count;
				}
			}else
			{
				$total += $month_data[$i] = (int) $month_rdata[$i];
			}	
		}
			
		//Updating report
		if(!$month_report)
		{
			$db->insert(tbl('paid_reports'),array('report_type','report_date','report_last_update',
			'report_data','date_added','report_counts'),
			array($type.'_month',$month_date,now(),'|no_mc|'.json_encode($month_data),now(),$total));		
		}else
		{
			$db->update(tbl('paid_reports'),array('report_type','report_last_update',
			'report_data','date_added','report_counts'),
			array($type.'_month',now(),'|no_mc|'.json_encode($month_data),now(),$total)
			,"report_id='".$month_report['report_id']."'");
		}
		
		if($type=='order')
		{
			$order_month_data = $month_data;
			$type= 'income';
		}else
		{
			$income_month_data = $month_data;
			break;
		}
	}

	assign('order_data',$order_month_data);
	assign('income_data',$income_month_data);
	
	assign('tmonthd',$month);
	assign('tmonth',date("F",strtotime("2001-".$month."-1")));
	
}
//-------- MONTHLY REPORT -----------//





//-------- YEARLY REPORT -----------//
if($month=='no')
{
	$type = "order";
	while(1)
	{	
			
		$year_report = $paidSub->get_report($year,$type.'_year',false);
		
		if($year_report)
		{
			$year_rdata = $year_report['report_data'];
			$year_rdata = json_decode($year_rdata,true);
		}
		
		$total = 0;
		for($i=1;$i<13;$i++)
		{
			$date = $year.'-'.$i;
			
			//Checking if date is valid
			if(checkdate($i,1,$year) && (!isset($year_rdata[$i]) || $date==date("Y-m",time())))
			{
				if($type=='order')
				{
					$count = $db->count(tbl('paid_orders'),"order_id"," 
					CONCAT('$year','$i') = CONCAT(YEAR(date_added),MONTH(date_added))  ");	
					
					$total += $year_data[$i] = $count;
				}else
				{
					$count = 0;
					$count = $db->select(tbl('paid_invoices')," SUM(amount_recieved) as income "," 
					CONCAT('$year','$i') = CONCAT(YEAR(date_recieved),MONTH(date_recieved))  ");
					$count = $count[0]['income'];
					
					$total += $year_data[$i] =  (float) $count;
				}
			}else
			{
				$total += $year_data[$i] = (int) $year_rdata[$i];
			}	
		}
			
		/* Updating report */
		if(!$year_report)
		{
			$db->insert(tbl('paid_reports'),array('report_type','report_date','report_last_update',
			'report_data','date_added','report_counts'),
			array($type.'_year',$year,now(),'|no_mc|'.json_encode($year_data),now(),$total));		
		}else
		{
			$db->update(tbl('paid_reports'),array('report_type','report_last_update',
			'report_data','date_added','report_counts'),
			array($type.'_year',now(),'|no_mc|'.json_encode($year_data),now(),$total)
			,"report_id='".$year_report['report_id']."'");
		}
		
		if($type=='order')
		{
			$order_year_data = $year_data;
			$type= 'income';
		}else
		{
			$income_year_data = $year_data;
			break;
		}
	}

	assign('income_data',$income_year_data);
	assign('order_data',$order_year_data);
	
	
}
//-------- YEARLY REPORT -----------//


assign('year',$year);
$months = array();
for($i=1;$i<13;$i++)
{
	$months[$i] = date("F",strtotime("2001-".$i."-1"));
}
assign('months',$months);



//Todays income
$income_today = $db->select(tbl("paid_invoices")," SUM(amount_recieved) AS total ",
" curdate() = date(date_recieved) ");

$income_today = (float) $income_today[0]['total'];
//Orders Today
$orders_today  = (float) $db->count(tbl("paid_orders"),"order_id",
" curdate() = date(date_added) ");

//Weekly Income
$income_month = $db->select(tbl("paid_invoices")," SUM(amount_recieved) AS total ",
" CONCAT(YEAR(curdate()),MONTH(curdate())) = CONCAT(YEAR(date_recieved),MONTH(date_recieved))  ");
$income_month = $income_month[0]['total'];
//Orders in a week
$orders_month  = $db->count(tbl("paid_orders"),"order_id",
" CONCAT(YEAR(curdate()),MONTH(curdate())) = CONCAT(YEAR(date_added),MONTH(date_added))  ");

//Yearly Income
$income_year = $db->select(tbl("paid_invoices")," SUM(amount_recieved) AS total ",
" YEAR(curdate()) = YEAR(date_recieved) ");
$income_year = (float) $income_year[0]['total'];
//Orders in a year
$orders_year  = $db->count(tbl("paid_orders"),"order_id",
" YEAR(curdate()) = YEAR(date_added) ");

assign('income_today',$income_today);
assign('income_month',$income_month);
assign('income_year',$income_year);

assign('orders_today',$orders_today);
assign('orders_month',$orders_month);
assign('orders_year',$orders_year);

subtitle('View reports - Paid subscriptions');


//Loading template
$template = 'view_reports.html';
template_files($template,PAID_SUBS_DIR.'/admin');
?>