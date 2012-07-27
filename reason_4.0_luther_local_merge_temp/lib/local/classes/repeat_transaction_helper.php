<?php

include_once( 'reason_header.php' );

function compare_transaction_timestamps( $a,$b ) // {{{
{
	if( $a['timestamp'] > $b['timestamp'] )
		return 1;
	elseif ( $b['timestamp'] < $a['timestamp'] )
		return -1;
	else
		return 0;
} // }}}


/* Sample code to use this class

$rth = new repeatTransactionHelper();

$rth->set_repeat_amount( 23.47 );
$rth->set_repeat_start_date( '2005-05-02' );
$rth->set_repeat_quantity( 17 );
$rth->set_repeat_type( 'Monthly' );

$rth->set_single_time_amount( 48.05 );
$rth->set_single_time_date( '2005-07-17' );

$trans = $rth->get_transactions();
$year_totals = $rth->get_calendar_year_totals();
$fy_totals = $rth->get_fiscal_year_totals();

echo '<table border="1">';
foreach($trans as $t)
{
	echo '<tr><td>'.date('Y-m-d',$t['timestamp']).'</td><td>'.$t['cents']*.01.'</td></tr>'."\n";
}
echo '</table>';

*/

class repeatTransactionHelper
{
	var $repeat_cents = 0; // Int; divide by 100 to get dollars
	var $repeat_types = array('Monthly'=>'1','Quarterly'=>'3','Yearly'=>'12'); // keys are the strings that can be passed to set_repeat_type; values are numbers of months associated with each repeat type.
	var $repeat_type = ''; // String
	var $repeat_start_timestamp = 0; // Unix timestamp
	var $repeat_quantity = 0; // How many times does the repetition occur?
	var $repeat_end_timestamp = 0; // Unix timestamp of the last point in time a transaction can occur.  This is part of the process of determining number of repeat transactions if this object is given an end date.
	var $last_repeat_timestamp = 0;
	var $repeat_indefinitely = false;
	
	var $single_time_cents = 0; // Int; divide by 100 to get dollars
	var $single_time_timestamp = 0; // Unix timestamp
	var $total_cents = 0;
	var $calendar_year_total_cents = array();
	var $fiscal_year_total_cents = array();
	
	var $transactions = array();
	
	// repeat setting functions
	function set_repeat_amount( $money )
	{
		if(is_numeric($money))
		{
			$cents = round($money * 100);
			settype($cents, 'integer');
			$this->repeat_cents = $cents;
		}
		else
		{
			trigger_error('Non-numeric value passed to set_repeat_amount: '.$money );
		}
	}
	function set_repeat_type( $type_string )
	{
		if( array_key_exists($type_string, $this->repeat_types) )
		{
			$this->repeat_type = $type_string;
		}
		else
		{
			trigger_error('You must pass set_repeat_type a valid repetition string.');
		}
	}
	function set_repeat_start_date( $date_str )
	{
		$timestamp = strtotime($date_str);
		if ($timestamp !== -1)
		{
			$this->repeat_start_timestamp = $timestamp;
		}
		else
		{
		   trigger_error('Bad date passed to set_repeat_start_date: '.$date_str );
		}
	}
	function set_repeat_quantity( $num )
	{
		$int = turn_into_int($num);
		if ( $int == $num )
		{
			$this->repeat_quantity = $int;
		}
		else
		{
			trigger_error('Non-integer passed to set_repeat_quantity: '.$num );
		}
	}
	function set_end_date( $date )
	{
		if($date == 'indefinite')
		{
			if(!empty($this->repeat_start_timestamp))
			{
				$starter_timestamp = $this->repeat_start_timestamp;
			}
			else
			{
				$starter_timestamp = time();
				trigger_error('End date set before start date.  Please assign dates in chronological order.');
			}
			$timestamp = strtotime('+4 years', $starter_timestamp);
			$this->repeat_indefinitely = true;
		}
		else
		{
			$timestamp = strtotime($date.' 23:59:59');
			$this->repeat_indefinitely = false;
		}
		if($timestamp != -1)
		{
			$this->repeat_end_timestamp = $timestamp;
		}
		else
		{
			trigger_error('set_end_date passed a bad date string: '.$date);
		}
	}
	
	// Single-time setting functions
	function set_single_time_amount( $money )
	{
		if(is_numeric($money))
		{
			$cents = round($money * 100);
			settype($cents, 'integer');
			$this->single_time_cents = $cents;
		}
		else
		{
			trigger_error('Non-numeric value passed to set_single_time_amount: '.$money );
		}
	}
	function set_single_time_date( $date_str )
	{
		$timestamp = strtotime($date_str);
		if ($timestamp !== -1)
		{
			$this->single_time_timestamp = $timestamp;
		}
		else
		{
		   trigger_error('Bad date passed to set_single_time_start_date: '.$date_str );
		}
	}
	
	// Runs the thing
	function build_transactions_array()
	{
		// Single time transaction
		if(!empty($this->single_time_cents))
		{
			$this->transactions[] = array('timestamp' => $this->single_time_timestamp, 'cents' => $this->single_time_cents );
			$this->total_cents += $this->single_time_cents;
		}
		
		// Repeat transactions
		if(!empty($this->repeat_cents))
		{
			if(!empty($this->repeat_start_timestamp) && !empty($this->repeat_type) )
			{
				if(!empty($this->repeat_quantity) || !empty($this->repeat_end_timestamp))
				{
					$this->add_repeats();
				}
				else
				{
					trigger_error( 'Either a repeat quantity or an ending date must be set for repeating transactions');
				}
			}
			else
			{
				trigger_error( 'A a valid amount, a valid repeat type, and a valid start date must be set for repeating transactions');
			}
		}
		usort($this->transactions, 'compare_transaction_timestamps');
	}
	/* function add_repeats_by_quantity()
	{
		// first iteration
		$timestamp = $this->repeat_start_timestamp;
		$this->transactions[] = array('timestamp' => $timestamp, 'cents' => $this->repeat_cents );
		// subsequent iterations
		for( $i = 2; $i <= $this->repeat_quantity; $i++ )
		{
			$timestamp = strtotime( '+'.$this->repeat_types[$this->repeat_type].' months', $timestamp );
			$this->transactions[] = array('timestamp' => $timestamp, 'cents' => $this->repeat_cents );
			$this->total_cents += $this->repeat_cents;
		}
		$this->last_repeat_timestamp = $timestamp;
	} */
	function add_repeats()
	{
		$timestamp = $this->repeat_start_timestamp;
		$starting_day_of_month = date('d',$timestamp);
		$continue = true;
		$transaction_count = 0;
		
		while( $continue )
		{
			$num_months = ($transaction_count)*$this->repeat_types[$this->repeat_type];
			$latest_stamp = strtotime( '+'.$num_months.' months', $timestamp );
			$latest_day_of_month = date('d', $latest_stamp);
			if( $starting_day_of_month > 28 && $latest_day_of_month != $starting_day_of_month)
			{
				// We've gone into the beginning of the month after the appropriate one.  That is how strtotime works, but not Verisign, so this code corrects for that
				$real_month_timestamp = strtotime('-1 month',$latest_stamp);
				$num_days_in_real_month = date('t',$real_month_timestamp);
				$latest_stamp = mktime(0,0,0,date('m',$real_month_timestamp),$num_days_in_real_month,date('Y',$real_month_timestamp) );
			}
			if(
				!empty($this->repeat_end_timestamp) && $latest_stamp > $this->repeat_end_timestamp // we're in end by timestamp mode and we've hit the limit
				||
				!empty($this->repeat_quantity) && $transaction_count > $this->repeat_quantity // we're in end by quantity mode and we've hit the limit
			)
			{
				$continue = false;
			}
			elseif( $latest_stamp == -1 )
			{
				trigger_error( 'Bad date; probably, repeats have gone past unix era' );
				$continue = false;
			}
			else
			{
				$this->transactions[] = array('timestamp' => $latest_stamp, 'cents' => $this->repeat_cents );
				$this->total_cents += $this->repeat_cents;
				$this->last_repeat_timestamp = $latest_stamp;
				$transaction_count++;
			}
		}
		$this->set_repeat_quantity( $transaction_count );
	}
	function calculate_calendar_year_totals()
	{
		$this->calendar_year_total_cents = $this->get_arbitrary_year_totals( '1970-01-01' );
	}
	function calculate_fiscal_year_totals()
	{
	//SLS - changed date to 1970-06-01 for Luther's fiscal year (Carleton's starts in 1970-07-01
		$this->fiscal_year_total_cents = $this->get_arbitrary_year_totals( '1970-06-01' );
	}
	function get_arbitrary_year_totals( $date )
	{
		$return = array();
		$timestamp = strtotime($date);
		if($timestamp !== -1)
		{
			if(empty($this->transactions))
			{
				$this->build_transactions_array();
			}
			//$seconds_into_year = ( date('z',$timestamp) * 24 * 60 * 60 );
			$current_year_end = 0;
			foreach($this->transactions as $t)
			{
				if( $t['timestamp'] > $current_year_end)
				{
					if( date( 'md', $t['timestamp'] ) < date('md',$timestamp) ) 
					{
						$y = date('Y',$t['timestamp'] ) - 1;
					}
					else
					{
						$y = date( 'Y', $t['timestamp'] );
					}
					$current_year_end = mktime ( 0, 0, 0, date('m',$timestamp), date('d',$timestamp), $y );
				}
				if(!empty($return[$y]))
				{
					$return[$y] += $t['cents'];
				}
				else
				{
					$return[$y] = $t['cents'];
				}
			}
			return $return;
		}
		else
		{
			trigger_error('bad date passed to calculate_arbitrary_year_totals');
		}
	}
	
	// getters
	function get_transactions()
	{
		if(empty($this->transactions))
		{
			$this->build_transactions_array();
		}
		return $this->transactions;
	}
	function get_calendar_year_totals()
	{
		if(empty($this->calendar_year_total_cents))
		{
			$this->calculate_calendar_year_totals();
		}
		return $this->calendar_year_total_cents;
	}
	function get_fiscal_year_totals()
	{
		if(empty($this->fiscal_year_total_cents))
		{
			$this->calculate_fiscal_year_totals();
		}
		return $this->fiscal_year_total_cents;
	}
	function get_total()
	{	
		if(empty($this->transactions))
		{
			$this->build_transactions_array();
		}
		return $this->total_cents;
	}
	function get_last_repeat_timestamp()
	{	
		if(empty($this->transactions))
		{
			$this->build_transactions_array();
		}
		return $this->last_repeat_timestamp;
	}
	function get_repeat_quantity()
	{
		return $this->repeat_quantity;
	}
	function get_repeat_type()
	{
		return $this->repeat_type;
	}
	function repeats_indefinitely()
	{
		return $this->repeat_indefinitely;
	}
	function repeats()
	{
		if(!empty($this->repeat_cents))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}

?>
