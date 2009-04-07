<?php
/**
* General utilties for working with calendars
* @package reason
* @subpackage function_libraries
*/

/**
* A utility function for helping in the process of building tabular calendars.
* 
* Builds a multidimensional array of calendar information for a given month.
*
* Returns an array structured like this:
* <code>
* $cal_data = array(
* 				1=>array(
* 					'Thursday'=>1,
* 					'Friday'=>2,
* 					'Saturday=>3,
* 				),
* 				2=>array(
* 					'Sunday'=>4,
* 					'Monday'=>5,
* 					'Etc.'=>6,
* 				),
* 			);
* </code>
* 			
* Useful for building tabular calendar grids and such.
*
* Example Usage:
* <code>
* $month = date('m');
* $year = date('Y');
* $cal = get_calendar_data_for_month( date('Y'), date('m') );
* 
* echo '<h1>'.$year.'-'.$month.'</h1>';
* foreach($cal as $week=>$days_of_week)
* {
* 	echo '<h2>Week '.$week.'</h2>';
* 	echo '<ul>';
* 	foreach($days_of_week as $day_of_week=>$number)
* 	{
* 		echo '<li>'.$day_of_week.': '.$number.'</li>';
* 	}
* 	echo '</ul>';
* }
* </code>
* 
* @author Matt Ryan
* @param int $year
* @param int $month
* @return array
*/


function get_calendar_data_for_month( $year, $month )
{
	$cal_data = array();
	$month_start_timestamp = mktime(0,0,0,$month,1,$year);
	$days_in_month = date('t',$month_start_timestamp);
	$week = 1;
	
	for($day = 1; $day <= $days_in_month; $day++)
	{
		$day_of_week = date('l',mktime(0,0,0,$month,$day,$year));
		$cal_data[$week][$day_of_week] = $day;
		if( $day_of_week == 'Saturday' )
		{
			$week++;
		}
	}
	return $cal_data;
}

?>