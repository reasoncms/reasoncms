<?php
/**
 * Reason Event helper class
 *
 * @package reason
 * @subpackage classes
 */

/**
 * Include Reason libraries
 */
include_once('reason_header.php');
reason_include_once('classes/entity.php');
/**
 * Reason Event helper class -- gathers event-related code into one place
 * @author Matt Ryan
 */
class reasonEvent
{
	var $event;
	function set_event($event)
	{
		if(!is_object($event))
		{
			$event_id = turn_into_int($event);
			$event = new entity($event_id);
		}
		
		if($event->get_values() && $event->get_value('type') == id_of('event_type'))
		{
			$this->event = $event;
		}
		else
		{
			trigger_error('Entity passed to reasonEvent object that is not an event');
			$this->event = NULL;
		}
	}
	function get_event()
	{
		return $this->event;
	}
	function get_event_values()
	{
		if(!empty($this->event) && $this->event->get_values())
		{
			return $this->event->get_values();
		}
		return array();
	}
	function get_event_value($field)
	{
		if(!empty($this->event) && $this->event->get_value($field))
		{
			return $this->event->get_value($field);
		}
		return false;
	}
	function set_event_value($field,$value)
	{
		if(!empty($this->event))
		{
			$this->event->set_value($field, $value);
			return true;
		}
		return false;
	}
	function get_stored_dates()
	{
		if($this->get_event_value('dates'))
			return explode(', ',$this->get_event_value('dates'));
		return array();
	}
	function find_occurrence_dates($values = array())
	{
		if(empty($values))
			$values = $this->get_event_values();
		if(empty($values))
			trigger_error('Programmer error: no event values available for reasonEvent::find_occurrence_dates()');
		
		reason_include_once('classes/event_repeater.php');
		$rep = new reasonEventRepeater();
		$rep->set_values($values);
		return $rep->get_occurrence_dates();
	}
	function clean_up($values = array())
	{
		if(empty($values) && !empty($this->disco_form))
			$values = $this->disco_form->get_values();
		if(empty($values))
			$values = $this->get_event_values();
		if(empty($values))
			trigger_error('Programmer error: no event values available for reasonEvent::clean_up_values()');
		
		if(empty($values['datetime']) || $values['datetime'] == '0000-00-00 00:00:00')
		{
			trigger_error('$values array must contain a valid mysql datetime for reasonEvent::clean_up');
			return false;
		}
		if( $values['recurrence'] == 'monthly' AND $values['monthly_repeat'] == 'semantic' )
		{
			$date_info = parse_mysql_date($values['datetime']);
			$values['month_day_of_week'] = carl_date( 'l',$date_info['timestamp'] );
			$values['week_of_month'] = floor($date_info['day']/7)+1;
		}
		else
		{
			$values['month_day_of_week'] = '';
			$values['week_of_month'] = '';
		}
		if(!empty($this->disco_form))
		{
			foreach($values as $key=>$val)
			{
				if($this->disco_form->get_value($key) != $val)
				{
					$this->disco_form->set_value($key,$val);
				}
			}
		}
		return $values;
	}
	function pass_disco_form_reference(&$disco_form)
	{
		$this->disco_form =& $disco_form;
	}
	function find_errors($values = array())
	{
		if(empty($values) && !empty($this->disco_form))
			$values = $this->disco_form->get_values();
		if(empty($values))
			$values = $this->get_event_values();
		if(empty($values))
			trigger_error('Programmer error: no event values available for reasonEvent::find_errors()');
			
		static $required_fields = array('name','datetime','recurrence','show_hide');
		
		$errors = array();
		foreach($required_fields as $field)
		{
			if(empty($values[$field]))
				$errors[$field][] = 'The '.$field.' field is required.';
		}
		
		if(empty($errors))
		{
			if( $values['recurrence'] != 'none')
			{
				if (empty($values['frequency']))
				{
					static $repeat_vals = array( 'daily'=>'day', 'weekly'=>'week', 'monthly'=>'month', 'yearly'=>'year');
					$errors['frequency'][] = 'How often should this event repeat? If it repeats every '.$repeat_vals[ $values['recurrence'] ].', enter a 1.';
				}
				if (empty($values['term_only'] ) )
					$errors['term_only'][] = 'Please indicate whether this event should occur only while classes are in session.';
			}
			
			$d = parse_mysql_date( $values['datetime'] );
			if ($values[ 'recurrence' ] == 'weekly')
			{
				// there must be at least 1 day of the week the event recurs on
				static $days = array ('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
				foreach ($days as $day)
				{
					if ( !empty( $values[$day] ) )
					{
						$day_set = true;
						break;
					}
				}
				if (empty($day_set))
					$errors['sunday'][] = 'Please enter which day(s) of the week this event will occur.';
	
				// the date of the event must be on one of those days of the week
				$day_o_week = carl_date( 'l', $d['timestamp'] );
				if( empty( $values[strtolower( $day_o_week ) ] ) )
				{
					$errors['sunday'][] = 'This event starts on a '.$day_o_week.', which is not one of the repeating days of week provided.';
				}
			}
			elseif ($values['recurrence'] == 'monthly')
			{
				// don't allow repetition on days that don't occur in every month
				if ($d['day'] > 28) 
					$errors['datetime'][] = 'Dates after the 28th do not occur in all months, so they are not available for monthly repeats.';
			}
			elseif ( $values['recurrence'] == 'yearly' )
			{
				if( $d['month'] == 2 AND $d['day'] == 29 )
					$errors['datetime'][] = 'February 29th only occurs on leap-years, so it is not an acceptable date for yearly repeating events.';
			}
		}
		if(!empty($this->disco_form))
		{
			foreach($errors as $field=>$errs)
			{
				foreach($errs as $err_txt)
				{
					$this->disco_form->set_error($field,$err_txt);
				}
			}
		}
		return $errors;
	}
	/**
	 *	Discovers potential conflicts -- can be used to reduce the possibility of duplicate events in Reason
	 * 
	 * Please note: this function is currently experimental.
	 * It may not work precisely as advertised, and may not be particularly fast.
	 *
	 * @author Matt Ryan
	 * @param array $values 
	 * @return array $similar_entities
	 */
	function find_similar_events($values = array())
	{
		if(empty($values) && !empty($this->disco_form))
			$values = $this->disco_form->get_values();
		if(empty($values))
			$values = $this->get_event_values();
		if(empty($values))
			trigger_error('Programmer error: no event values available for reasonEvent::find_errors()');
		
		if(empty($values['datetime']) || $values['datetime'] == '0000-00-00 00:00:00')
		{
			trigger_error('Values must contain a valid datetime in reasonEvent::find_similar_events()');
			return false;
		}
		
		reason_include_once('classes/calendar.php');
		// find events that occur on that day at that time
		$start_date = substr($values['datetime'],0,10);
		$init_array = array('start_date'=>$start_date,'view'=>'daily','automagic_window_snap_to_nearest_view'=>true);
		$cal = new reasonCalendar($init_array);
		$cal->run();
		$days = $cal->get_all_days();
		$all_events = $cal->get_all_events();
		$similar_events = array();
		$time = substr($values['datetime'],11);
		if(!empty($days))
		{
			reset($days);
			$day = current($days);
			if(!empty($day))
			{
				foreach($day as $id)
				{
					if($id != $values['id'] && substr($all_events[$id]->get_value('datetime'),11) == $time)
					{
						$percent = 0;
						similar_text($values['name'], $all_events[$id]->get_value('name'), $percent);
						//echo $all_events[$id]->get_value('name').': '.$percent.'%<br />';
						if($percent > 55)
						{
							$similar_events[$id] =& $all_events[$id];
							$similar_events[$id]->set_value('event_similarity', $percent);
						}
					}
				}
			}
		}
		return $similar_events;
	}
}


function test_reason_repeating_events($id = 236593)
{
	$e = new reasonEvent();
	$e->set_event($id);
	$stored = $e->get_stored_dates();
	$stored_count = count($stored);
	$calculated = $e->find_occurrence_dates();
	$calculated_count = count($calculated);
	$max_count = max($stored_count, $calculated_count);
	echo '<table><tr><th>Stored</th><th>Calculated</th><th>Diff</th></tr>'."\n";
	for($i = 0; $i < $max_count; $i++)
	{
		$s = $c = $d = '';
		if(!empty($stored[$i]))
			$s = $stored[$i];
		if(!empty($calculated[$i]))
			$c = $calculated[$i];
		if($s != $c)
			$d = 'diff';
		echo '<tr><td>'.$s.'</td><td>'.$c.'</td><td>'.$d.'</td></tr>'."\n";
	}
	echo '</table>'."\n";
	
	$errors = $e->find_errors();
	pray($errors);
	pray($e->find_similar_events());
}

//test_reason_repeating_events();
?>