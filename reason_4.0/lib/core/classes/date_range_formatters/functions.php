<?php

function get_date_range_formatter_class($formatter = 'default')
{
	static $formatters = array();
	if(!isset($formatters[$formatter]))
	{
		$formatters[$formatter] = false;
		$path = 'classes/date_range_formatters/'.$formatter.'.php';
		if(reason_file_exists($path))
		{
			reason_include_once($path);
			$classname = $formatter.'DateRangeFormatter';
			if(class_exists($classname))
			{
				$interfaces = class_implements($classname);
				if(in_array('dateRangeFormatter',$interfaces))
					$formatters[$formatter] = $classname;
				else
					trigger_error('Date range formatter '.$classname.' does not implement dateRangeFormatter interface');
			}
			else
				trigger_error('Date range formatter '.$classname.' does not seem to exist at '.$path);
		}
		else
			trigger_error('No date range formatter file appears to exist at '.$path);
	}
	return $formatters[$formatter];
}

function format_date_range($start_date, $end_date = NULL, $recurrence_info = array(), $formatter = 'default')
{
	if($class = get_date_range_formatter_class($formatter))
	{
		return $class::format($start_date, $end_date, $recurrence_info);
	}
	return NULL;
}

function format_event_date_range($event, $formatter = 'default')
{
	$start_date = substr($event->get_value('datetime'),0,10);
	$end_date = $event->get_value('last_occurence');
	if($start_date == $end_date)
		return format_date_range($start_date, NULL, array(), $formatter);
	
	$recurrence_info = array();
	foreach(date_range_recurrence_keys() as $key)
		$recurrence_info[$key] = $event->get_value($key);
	
	return format_date_range($start_date, $end_date, $recurrence_info, $formatter);
}

function date_range_recurrence_keys()
{
	return array(
		'recurrence',
		'term_only',
		'frequency',
		'month_day_of_week',
		'sunday',
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday',
		'sunday',
		'monthly_repeat'
	);
}