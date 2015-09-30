<?php
/**
 * Class that wraps up logic to determine on which a days a given event occurs
 * @package reason
 * @subpackage classes
 */
 
/**
 * Class that wraps up logic to determine on which a days a given event occurs
 * @author Matt Ryan
 */
class reasonEventRepeater
{
	var $frequency;
	var $recurrence;
	var $monthly_repeat;

	var $datetime;
	var $ystart;
	var $mstart;
	var $dstart;
	var $ustart;
	
	var $end_date;
	var $yend;
	var $mend;
	var $dend;
	var $uend;
	
	var $month_day_of_week;
	var $week_of_month;
	var $days_of_week = array();
	
	var $dates = array();
	
	var $years_out = 3;
	
	function set_values($values)
	{
		$this->dates = array();
		if(!empty($values['frequency']))
			$this->frequency = $values['frequency'];
		else
			$this->frequency = 1;
		
		if(!empty($values['recurrence']))
			$this->recurrence = $values['recurrence'];
		else
			$this->recurrence = 'none';
		
		if(!empty($values['monthly_repeat']))
			$this->monthly_repeat = $values['monthly_repeat'];
		else
			$this->monthly_repeat = 'numeric';
		
		if(!empty($values['datetime']))
		{
			$this->datetime = $values['datetime'];
			$s = parse_mysql_date( $this->datetime );
			$this->ystart = $s['year'];
			$this->mstart = $s['month'];
			$this->dstart = $s['day'];
			$this->ustart = $s['timestamp'];
		}
		else
		{
			trigger_error('A datetime value must be given in reasonEventRepeater::set_values()');
			return false;
		}
		if(!empty($values['end_date']) && $values['end_date'] != '0000-00-00 00:00:00')
		{
			$end_date = $values['end_date'];
		}
		else
		{
			$end_year = max(carl_date('Y'),$this->ystart)+$this->years_out;
			$end_date = $end_year.'-'.$this->mstart.'-'.$this->dstart;
		}
		$e = parse_mysql_date( $end_date );
		if(!empty($e))
		{
			$this->end_date = $end_date;
			$this->yend = $e['year'];
			$this->mend = $e['month'];
			$this->dend = $e['day'];
			$this->uend = $e['timestamp'];
		}
		else
		{
			trigger_error('Problem determining end date');
			return false;
		}
		if(!empty($values['month_day_of_week']) && !empty($values['week_of_month']) )
		{
			$this->month_day_of_week = $values['month_day_of_week'];
			$this->week_of_month  = $values['week_of_month'];
		}
		elseif( $this->recurrence == 'monthly' AND $this->monthly_repeat == 'semantic' )
		{
			$this->month_day_of_week = carl_date( 'l',$this->ustart );
			$this->week_of_month = floor($this->dstart/7)+1;
		}
		else
		{
			$this->month_day_of_week = '';
			$this->week_of_month = '';
		}
		if(!empty($values['month_day_of_week']) && $values['month_day_of_week'] != $this->month_day_of_week)
			trigger_error('Month day of week mismatch');
		if(!empty($values['week_of_month']) && $values['week_of_month'] != $this->week_of_month )
			trigger_error('Week of month mismatch');
		
		$this->days_of_week = array();
		if( !empty($values['sunday'] ) )
			$this->days_of_week[] = 'Sunday';
		if(  !empty($values['monday'] ) )
			$this->days_of_week[] = 'Monday';
		if(  !empty($values['tuesday'] ) )
			$this->days_of_week[] = 'Tuesday';
		if(  !empty($values['wednesday'] ) )
			$this->days_of_week[] = 'Wednesday';
		if(  !empty($values['thursday'] ) )
			$this->days_of_week[] = 'Thursday';
		if(  !empty($values['friday'] ) )
			$this->days_of_week[] = 'Friday';
		if(  !empty($values['saturday'] ) )
			$this->days_of_week[] = 'Saturday';
	}
	
	function get_occurrence_dates()
	{
		if(empty($this->dates))
		{
			if(empty($this->ustart))
			{
				trigger_error('Not able to get occurrence dates; no start date available');
				return array();
			}
			if(empty($this->end_date))
			{
				trigger_error('Not able to get occurrence dates; no end date available');
				return array();
			}
			// chunk out the dates appropriately
			if ( $this->recurrence == 'daily' )
				$this->get_days_daily();
			elseif ( $this->recurrence == 'weekly' )
				$this->get_days_weekly();
			elseif ( $this->recurrence == 'monthly' )
				$this->get_days_monthly();
			elseif ( $this->recurrence == 'yearly' )
				$this->get_days_yearly();
			else
				$this->get_days_norepeat();
			
			sort($this->dates);
		}
		return $this->dates;
	}
	
	function get_days_norepeat() // {{{
	{
		$this->dates[] = $this->ystart.'-'.$this->mstart.'-'.$this->dstart;
	} // }}}
	function get_days_daily() // {{{
	{
		for( $ucur = $this->ustart; $ucur <= $this->uend; $ucur = strtotime( '+'.$this->frequency.' days',$ucur ) )
			$this->dates[] = carl_date( 'Y',$ucur ).'-'.carl_date( 'm',$ucur ).'-'.carl_date( 'd',$ucur );
	} // }}}
	function get_days_weekly() // {{{
	{
		// go through each day of the week to repeat on
		foreach( $this->days_of_week AS $day )
		{
			// start on the date of the event
			$ucur = $this->ustart;

			// advance until the first occurence of that day of the week
			while( $day != carl_date( 'l',$ucur ) )
			{
				$ucur = strtotime( '+1 day',$ucur );
			}
			// now jump by the number of weeks to skip at a time until done
			while( $ucur <= $this->uend )
			{
				$this->dates[] = carl_date( 'Y',$ucur ).'-'.carl_date( 'm',$ucur ).'-'.carl_date( 'd',$ucur );
				$ucur = strtotime( '+ '.$this->frequency.' weeks',$ucur );
			}
		}
	} // }}}
	function get_days_monthly() // {{{
	{
		$ucur = $this->ustart;
		while( $ucur <= $this->uend )
		{
			$this->dates[] = carl_date( 'Y',$ucur ).'-'.carl_date( 'm',$ucur ).'-'.carl_date( 'd',$ucur );
			$ucur = strtotime( '+'.$this->frequency.' months',$ucur );
			if( $this->monthly_repeat == 'semantic' )
			{
				$cur_day = 1+7*($this->week_of_month - 1);
				$ucur_var = date( 'Y',$ucur ).'-'.date( 'm',$ucur ).'-'.str_pad( $cur_day,2,'0',STR_PAD_LEFT );
				$ucur = get_unix_timestamp( $ucur_var);
				if($ucur)
				{
					while( date( 'l',$ucur ) != $this->month_day_of_week )
					{
						$ucur = strtotime( '+1 day',$ucur );
					}
				}
				else
				{
					trigger_error('Not able to find appropriate repeat date for '.$ucur_var);
					break;
				}
			}
		}
	} // }}}
	function get_days_yearly() // {{{
	{
		for( $ucur = $this->ustart; $ucur < $this->uend; $ucur = strtotime( '+'.$this->frequency.' years',$ucur ) )
			$this->dates[] = carl_date( 'Y',$ucur ).'-'.carl_date( 'm',$ucur ).'-'.carl_date( 'd',$ucur );
	} // }}}
	
}

?>