<?php
/**
 * A class for creating iCalendar-formatted data from Reason event entities
 *
 * @package reason
 * @subpackage classes
 */

/**
 * Include the Reason libraries
 */
include_once('reason_header.php');
include_once(CARL_UTIL_INC.'basic/cleanup_funcs.php');
include_once(CARL_UTIL_INC.'basic/date_funcs.php');
  
/**
 * A class for creating iCalendar-formatted data from Reason event entities
 * 
 * Example code:
 * <code>
 * reason_include_once('classes/entity_selector.php');
 * $es = new entity_selector();
 * $es -> add_type(id_of('event_type'));
 * $es -> add_relation('contact_username = \'grossh\'');
 * $es -> add_relation('name = \'Test\'');
 * $es -> set_num(10);
 * $events = $es -> run_one();
 * $calendar = new reason_iCalendar();
 * $calendar -> set_events($events);
 * $calendar -> set_title('My Event Calendar');
 * header( $calendar->get_icalendar_header());
 * echo $calendar -> get_icalendar_events();
 * </code>
 *
 * @todo real timezone handling - right now we use floating date / times. we cannot really implement UTC until reason events implement UTC.
 * @todo include a URL to the appropriate reason event page if the url field for the event is not populated.
 * @author Henry Gross
 */
class reason_iCalendar
{
	var $_events = array();
	var $_title;
	var $use_x_wr_timezone = true;
		
	function set_events($events)
	{
		$this->_events = $events;
	}
	
	function set_title($title)
	{
		$this->_title = $title;
	}
	
	function get_events()
	{
		return $this->_events;
	}
	
	function get_title()
	{
		return $this->_title;
	}
	
	function get_icalendar_header()
	{
		return 'Content-type: text/calendar; charset=utf-8';
	}
	
	function get_icalendar_events()
	{
		return $this -> _create_icalendar_events();
	}
	
	function _create_icalendar_events()
	{
		$icalendar = "";
		$icalendar .= $this -> _create_icalendar_header();
		$events = $this->get_events();
		foreach ($events as $event)
		{
			$icalendar .= $this -> _create_icalendar_event($event);
		}
		$icalendar .= $this -> _create_icalendar_footer();
		return $icalendar;
	}
	
	//create the first several lines of the iCalendar file
	function _create_icalendar_header()
	{
		$title = $this->get_title();
		$icalendar_header = "";
		$icalendar_header .= 'BEGIN:VCALENDAR' . "\r\n";
		$icalendar_header .= 'VERSION:2.0' . "\r\n";
		$icalendar_header .= 'PRODID:-//Reason 4.0//EN' . "\r\n";
		if (!empty($title)) $icalendar_header .= 'X-WR-CALNAME;VALUE=TEXT:'. $this->_fold_text($title) . "\r\n";
		if (defined("REASON_DEFAULT_TIMEZONE") && ( (boolean) REASON_DEFAULT_TIMEZONE ) && $this->use_x_wr_timezone)
		{
			$icalendar_header .= 'X-WR-TIMEZONE:'. REASON_DEFAULT_TIMEZONE . "\r\n";
		}
		return $icalendar_header;
	}
	
	//create the last couple of lines of the iCalendar file
	function _create_icalendar_footer()
	{
		$icalendar_footer = "";
		$icalendar_footer .= 'END:VCALENDAR' . "\r\n";
		return $icalendar_footer;
	}
	
	//create a VEVENT
	function _create_icalendar_event($event)
	{
		$icalendar_event = "";
		$icalendar_event .= 'BEGIN:VEVENT' . "\r\n";  
		$icalendar_event .= 'UID:'.$this->_fold_text(str_replace(array('-',' ',':'),'',$event->get_value('creation_date')).'-'.$event->id().'@'.REASON_ICALENDAR_UID_DOMAIN)."\r\n";
	 
		//SUMMARY
		if (strlen($event -> get_value('name')) != 0)
			$icalendar_event .= 'SUMMARY:' . $this -> _fold_text($event -> get_value('name')) . "\r\n";
		
		//DESCRIPTION
		// if "brief description of event" (field: description) was specified, use that. Otherwise, use the "full event information" (field: content)
		if (strlen($event -> get_value('description')) != 0)
			$icalendar_event .= 'DESCRIPTION:' . $this -> _fold_text($event -> get_value('description')) . "\r\n";
		else if (strlen($event -> get_value('content')) != 0)
			$icalendar_event .= 'DESCRIPTION:' . $this -> _fold_text($event -> get_value('content')) . "\r\n";

		//LOCATION
		if (strlen($event -> get_value('location')) != 0)
			$icalendar_event .= 'LOCATION:' . $this -> _fold_text($event -> get_value('location')) . "\r\n";
		//URL
		if (strlen($event -> get_value('url')) != 0)
			$icalendar_event .= 'URL:' . $this->_fold_text($event -> get_value('url')) . "\r\n";
		//LAST-MODIFIED
		if (strlen($event -> get_value('last_modified')) != 0)
			$icalendar_event .= 'LAST-MODIFIED:' . $this -> _create_datetime(carl_date("Y-m-d H:i:s", strtotime($event -> get_value('last_modified'))), false) . "\r\n";
		//CREATED
		if (strlen($event -> get_value('creation_date')) != 0)
			$icalendar_event .= 'CREATED:' . $this -> _create_datetime(carl_date("Y-m-d H:i:s", strtotime($event -> get_value('creation_date'))), false) . "\r\n";
	
		//DTSTART
		if (strlen($event -> get_value('datetime')) != 0)
		{
			$timestamp = $this -> _create_datetime(carl_date("Y-m-d H:i:s", strtotime($event -> get_value('datetime'))), false);
			if (strstr($event -> get_value('datetime'), '00:00:00'))
			{
				preg_match("/[\d]*/", $timestamp, $matches);
				$icalendar_event .= 'DTSTART;VALUE=DATE:' . $matches[0] . "\r\n";
				//DREND (for all day events)
				$icalendar_event .= 'DTEND;VALUE=DATE:' . $matches[0] . "\r\n";
			}
			else
			{
				$icalendar_event .= 'DTSTART:' . $timestamp . "\r\n";
				//DURATION
				if ((strlen($event -> get_value('hours')) != 0) || (strlen($event -> get_value('minutes')) != 0))
				{
					if ($event -> get_value('hours') != 0 || $event -> get_value('minutes') != 0)
					{
						$icalendar_event .= $this -> _create_duration($event -> get_value('hours'), $event -> get_value('minutes'));
					}
					else
					{
						$icalendar_event .= $this -> _create_duration('0','0');
					}
				}
			}
		}
		
		//Recurrence Rule
		if ($event -> get_value('recurrence') != 'none')
		{
			$icalendar_event .= $this -> _create_rrule($event -> get_value('recurrence'), $event -> get_value('frequency'), $event -> get_value('week_of_month'), $event -> get_value('month_day_of_week'), $event -> get_value('monthly_repeat'), $event -> get_value('sunday'), $event -> get_value('monday'), $event -> get_value('tuesday'), $event -> get_value('wednesday'), $event -> get_value('thursday'), $event -> get_value('friday'), $event -> get_value('saturday'), $event -> get_value('end_date'), $event -> get_value('datetime'), false);
		}
		$icalendar_event .= 'END:VEVENT' . "\r\n";
		return $icalendar_event;
	}
	
	//the standard specifies that text is to be foldeed in this way
	function _fold_text($text)
	{
		$text = str_replace("\n", '\n', $text);
		$text = strip_tags($text);
		if(carl_is_php5()) $text = html_entity_decode($text,ENT_QUOTES,'UTF-8');
		else //even though php4 implements html_entity_decode, it does not handle multibyte encodings.
		{
			$text = unhtmlentities($text);
		}

		/*
		$folded_text = "";
		while (strlen($text) > 75)
		{
			$folded_text .= substr($text, 0, 75) . "\r\n ";
			$text = substr($text, 75);
		}
		$folded_text .= $text;
		 */

		// 2014-08-14: rewritten to handle multibyte strings
		$folded_text = "";
		$break_at = 75;
		$encoding = "UTF-8";
		while (mb_strlen($text, $encoding) > $break_at) {
			$folded_text .= mb_substr($text, 0, $break_at, $encoding) . "\r\n ";
			$text = mb_substr($text, $break_at, mb_strlen($text, $encoding), $encoding);
		}
		$folded_text .= $text;

		return $folded_text;
	}
	
	//create the DURATION property from the hours and minutes specified in the event
	function _create_duration($hours, $minutes)
	{
		$duration = 'DURATION:';
		$duration .= 'PT' . $hours . 'H' . $minutes . 'M0S';
		$duration .= "\r\n";
		return $duration;
	}
	
	//convert a SQL datetime into an iCalendar date-time
	function _create_datetime($datetime, $utc)
	{
		$stamp = explode(' ', $datetime);
		$date = $this -> _create_date($stamp[0]);
		if (strlen($stamp[1]) != 0)
		{
			$time = 'T';
			$time .= $this -> _create_time($stamp[1], true);
			if ($utc) $time .= 'Z';
		}
		return $date . $time;
	}
	
	//convert the date part of the date-time
	function _create_date($date)
	{
		$stamp = explode('-', $date);
		return implode('', $stamp);
	}
	
	//convert the time part of the date-time
	function _create_time($time)
	{
		$stamp = explode(':', $time);
		return implode('', $stamp);
	}
	
	//create the recurrence rules
	function _create_rrule($repeat, $frequency, $week_of_month, $month_day_of_week, $monthly_repeat, $sunday, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $end_date, $datetime, $utc)
	{
		$rrule = 'RRULE:';
		$rrule .= 'FREQ=' . strtoupper($repeat) . ';';	
		$end_date = substr($end_date,0,10);
		// set UNTIL to 23:59:59 unless this is an all day event (in reason these are currently events that start at midnight)
		if (($end_date != '0000-00-00') && (substr($datetime, 11) != '00:00:00')) $end_date = $end_date.' 23:59:59';
		else $end_date = $end_date.' 00:00:00';
		$end = $this -> _create_datetime($end_date, $utc);
		if ($end != '00000000T000000') $rrule .= 'UNTIL=' . $end . ';';
		if (strlen($frequency) != 0) $rrule .= 'INTERVAL=' . $frequency . ';';
		if ($repeat == 'weekly')
		{
			$rrule .= 'BYDAY=' . $this -> _get_days($sunday, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday) . ';';
		}
		elseif ($repeat == 'monthly')
		{
			if ($monthly_repeat == 'semantic')
			{
			  $rrule .= 'BYDAY=' . $week_of_month . $this -> _convert_day_abbr($month_day_of_week) . ';';
			}
		}
		if (substr($rrule, -1, 1) == ';') $rrule = substr($rrule, 0, -1);
		$rrule .= "\r\n";
		return $rrule;
	}
	
	//create a list of days on which the event recurrs
	function _get_days($sunday, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday)
	{
		$days = '';
		if ($sunday == 'true')
			$days .= $this -> _convert_day_abbr('Sunday') . ',';
		if ($monday == 'true')
			$days .= $this -> _convert_day_abbr('Monday') . ',';
		if ($tuesday == 'true')
			$days .= $this -> _convert_day_abbr('Tuesday') . ',';
		if ($wednesday == 'true')
			$days .= $this -> _convert_day_abbr('Wednesday') . ',';
		if ($thursday == 'true')
			$days .= $this -> _convert_day_abbr('Thursday') . ',';
		if ($friday == 'true')
			$days .= $this -> _convert_day_abbr('Friday') . ',';
		if ($saturday == 'true')
			$days .= $this -> _convert_day_abbr('Saturday') . ',';
		if (substr($days, -1, 1) == ',')
			$days = substr($days, 0, -1);
		return $days;
	}
	
	//convert the full name of the day to the iCalendar abbreviation
	function _convert_day_abbr($day)
	{
		switch ($day)
		{
			case 'Monday':
			return 'MO';
			case 'Tuesday':
			return 'TU';
			case 'Wednesday':
			return 'WE';
			case 'Thursday':
			return 'TH';
			case 'Friday':
			return 'FR';
			case 'Saturday':
			return 'SA';
			case 'Sunday':
			return 'SU';
		}
	}
}
?>
