<?php
reason_include_once('classes/date_range_formatters/interface.php');
class defaultDateRangeFormatter implements dateRangeFormatter
{
	/**
	 * 
	 */
	public static function format($start_date, $end_date =  NULL, $recurrence_info = array())
	{
		if( empty($end_date) || $start_date == $end_date)
		{
			if(mb_strlen(prettify_mysql_datetime($start_date,'F')) > 3)
				$format = 'M. j, Y';
			else
				$format = 'M j, Y';
			return prettify_mysql_datetime($start_date,$format);
		}
		
		$diff_years = true;
		$diff_months = true;
		
		$start_year = substr($start_date,0,4);
		$end_year = substr($end_date,0,4);
		if($start_year == $end_year)
		{
			$diff_years = false;
			$start_month = substr($start_date,5,2);
			$end_month = substr($end_date,5,2);
			if($start_month == $end_month)
				$diff_months = false;
		}
		if(mb_strlen(prettify_mysql_datetime($start_date,'F')) > 3)
			$start_format = 'M. j';
		else
			$start_format = 'M j';
		if($diff_years)
			$start_format .= ', Y';
			
		if($diff_months)
		{
			if(mb_strlen(prettify_mysql_datetime($end_date,'F')) > 3)
				$end_format = 'M. j, Y';
			else
				$end_format = 'M j, Y';
		}
		else
			$end_format = 'j, Y';
			
		$ret = prettify_mysql_datetime($start_date,$start_format);
		
		$ret .= ' '.html_entity_decode('&#8211;',ENT_NOQUOTES,'UTF-8').' ';
		
		$ret .= prettify_mysql_datetime($end_date,$end_format);
		
		return $ret;
	}
}