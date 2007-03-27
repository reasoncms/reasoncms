<?php
include_once('paths.php');
include_once(ADODB_DATE_INC);

//----------------------------------------------------------
// EXPLODE THE DATE INTO YEAR, MONTH, AND DAY VALUES
//----------------------------------------------------------

function explodeDate($date) {

	$dateStrip = explode ("-", $date);
	$year =		($dateStrip[0]);
	$month = 	($dateStrip[1]); 
	$day = 		($dateStrip[2]);
	
	return array ($year, $month, $day);

}

//----------------------------------------------------------
// carl_date functions
//----------------------------------------------------------

/**
 * Alias for adodb_getdate()
 */
function carl_getdate($timestamp=false,$fast=false)
{
	return adodb_getdate($timestamp,$fast);
}
/**
 * Alias for adodb_date()
 */
function carl_date($format,$timestamp=false,$is_gmt=false)
{
	return adodb_date($format,$timestamp,$is_gmt);
}
/**
 * Alias for adodb_gmdate()
 */
function carl_gmdate($format,$timestamp=false)
{
	return adodb_gmdate($format,$timestamp=false);
}
/**
 * Wrapper for adodb_mktime()
 * Checks if year is zero and returns internal php mktime in that case
 */
function carl_mktime($hr,$min,$sec,$month=false,$day=false,$year=false,$is_dst=false,$is_gmt=false)
{
	$int_year = intval($year);
	if( $int_year == 0 )
	{
		return mktime($hr,$min,$sec,$month,$day,$year,$is_dst);
	}
	else
	{
		if( $int_year < 100 )
		{
			// adodb_mktime seems to have a bug where it returns the time plus 1 hour
			// when  year is 2 digits, so we first turn the year into a 4-digit year
			// before running adodb_mktime
			$year = carl_date('Y',adodb_mktime(1,0,0,1,1,$year));
		}
		return adodb_mktime($hr,$min,$sec,$month,$day,$year,$is_dst,$is_gmt);
	}
}
/**
 * Wrapper for adodb_gmmktime()
 * Checks if year is zero and returns internal php gmmktime in that case
 */
function carl_gmmktime($hr,$min,$sec,$month=false,$day=false,$year=false,$is_dst=false)
{
	if( intval($year) == 0 )
	{
		return gmmktime($hr,$min,$sec,$month,$day,$year,$is_dst);
	}
	else
	{
		return adodb_gmmktime($hr,$min,$sec,$month,$day,$year,$is_dst);
	}
}
/**
 * Alias for adodb_strftime()
 */
function carl_strftime($format, $timestamp=false ,$is_gmt=false)
{
	return adodb_strftime($format, $timestamp ,$is_gmt);
}
/**
 * Alias for adodb_gmstrftime()
 */
function carl_gmstrftime($format, $timestamp=false)
{
	return adodb_gmstrftime($format, $timestamp);
}
/**
 * Alias for adodb_validdate()
 */
function carl_validdate($y,$m,$d)
{
	return adodb_validdate($y,$m,$d);
}

//----------------------------------------------------------
// General date- & time-handling functions
//----------------------------------------------------------

/**
 * This function attempts to get a UNIX timestamp by testing
 * the date given through a number of known date types.
 * if the date is not in one of these types, it will simply
 * return false.
 * NOTE: This function may now return 64-bit timestamps, 
 * so its output should be passed to carl_date() or a similar 
 * 64-bit compatible function.
 * @param mixed $value the date string or integer to be converted into timestamp
 * @return $timestamp
 */
function get_unix_timestamp( $value ) // {{{
{
	 $format = get_date_format( $value );
	 
	if( $format == 'mysql_datetime' )
		$value = datetime_to_unix( $value );
	elseif( $format == 'mysql_timestamp' )
		$value = timestamp_to_unix( $value );
	elseif( $format == 'exif_datetime' )
		$value = exif_datetime_to_unix( $value );
	elseif( $format == 'mysql_date' )
		$value = mysql_date_to_unix( $value );
	// UNIX timestamp - not really sure how to match this, but if it is all numbers and not empty, might as well treat it as a unix timestamp
	elseif( $format == 'unix_timestamp' )
		$value = $value;
	else
	{
		if(!empty($format))
			trigger_error('get_date_format() returned unknown string: '.$format);
		$value = false;
	}

	// mktime can return funky results if it is given a bad date.
	// generally, -1 is returned for a bad date.
	if( $value == -1 )
		return false;
	else
		return $value; 
} // }}}
/**
 * Tries to identify the date format for a given value
 * Formats identified:
 * -- mysql_datetime
 * -- mysql_timestamp
 * -- exif_datetime
 * -- mysql_date
 * -- unix_timestamp
 * @return mixed either a string identifying the date format or false if format not identified
 */
function get_date_format( $value )
{
	// MySQL DATETIME field type: "YYYY-MM-DD HH:MM:SS"
	if( is_mysql_datetime( $value ) )
		return 'mysql_datetime';
	// MySQL TIMESTAMP field type: "YYYYMMDDHHMMSS"
	elseif( preg_match('/^[0-9]{14}$/', $value) )
		return 'mysql_timestamp';
	// EXIF DateTime field: "YYYY:MM:DD HH:MM:SS"
	elseif( preg_match('/^([0-9]{4}):([0-9]{2}):([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $value, $matches) )
		return 'exif_datetime';
	// MySQL Date type: "YYYY-(M)M-(D)D"
	elseif( preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9][0-9]{1,2})$/', $value, $matches) )
		return 'mysql_date';
	// UNIX timestamp - not really sure how to match this, but if it is all numbers and not empty, might as well treat it as a unix timestamp
	elseif( preg_match( '/^[0-9]+$/', $value ) )
		return 'unix_timestamp';
	else
		return false;
}
function is_mysql_datetime( $date )
{
	if( preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $date) )
		return true;
	else
		return false;
}
function timestamp_to_unix( $dt ) // {{{
{
	if( strlen( $dt ) == 14 )
	{
		$year = substr( $dt, 0, 4);
		$month = substr( $dt, 4, 2 );
		$day = substr( $dt, 6, 2 );
		$hour = substr( $dt, 8, 2 );
		$minute = substr( $dt, 10, 2 );
		$second = substr( $dt, 12, 2 );
		return carl_mktime( $hour, $minute, $second, $month, $day, $year );
	}
	else
		return false;
} // }}}
function datetime_to_unix( $dt ) // {{{
{
	list( $date, $time ) = explode( ' ', $dt );
	list( $year, $month, $day ) = explode( '-', $date );
	list( $hour, $minute, $second ) = explode( ':', $time );
	return carl_mktime( $hour, $minute, $second, $month, $day, $year );
} // }}}
function exif_datetime_to_unix( $edt ) // {{{
{
	list( $date, $time ) = explode( ' ', $edt );
	list( $year, $month, $day ) = explode( ':', $date );
	list( $hour, $minute, $second ) = explode( ':', $time );
	return carl_mktime( $hour, $minute, $second, $month, $day, $year );
} // }}}
function mysql_date_to_unix( $md )
{
	preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9][0-9]{1,2})$/', $md, $matches);
	return carl_mktime(0,0,0,$matches[2],$matches[3], $matches[1]);
}
/* These functions were intended to prep iffy mysql-formatted strings for the pear date class. Since we are now using the adodb library, these funcs are not currently necessary. In addition, the 2-to-4-year conversion was just a stub -- it always returns 20xx. */
/* function clean_mysql_datetime( $date )
{
	if(is_mysql_datetime( $date ))
	{
		return $date;
	}
	else
	{
		$date_and_time = explode( ' ',$date );
		
		$good_date = clean_mysql_date($date_and_time[0]);
		
		$time_parts = explode(':',$date_and_time[1]);
		
		$hours = substr($time_parts[0], 0, 2);
		$hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
		
		$minutes = substr($time_parts[1], 0, 2);
		$minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
		
		$seconds = substr($time_parts[2], 0, 2);
		$seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
		
		return $good_date.' '.$hours.':'.$minutes.':'.$seconds;
	}
	
}
function clean_mysql_date( $date )
{
	$date_parts = explode('-',$date_and_time[0]);
	
	$year = substr($date_parts[0], 0, 4);
	$year_length = strlen($year);
	if($year_length < 4)
	{
		if($year_length == 2)
		{
			$year = two_to_four_digit_year( $year );
		}
		else // 1 or 3 digit year -- assume wierdness
		{
			$year = str_pad($year, 4, '0', STR_PAD_LEFT);
		}
	}
	
	$month = substr($date_parts[1], 0, 2);
	$month = str_pad($month, 2, '0', STR_PAD_LEFT);
	
	$day = substr($date_parts[2], 0, 2);
	$day = str_pad($day, 2, '0', STR_PAD_LEFT);
	
	return $year.'-'.$month.'-'.$day;
}
function two_to_four_digit_year( $two_digit_year )
{
	return '20'.$two_digit_year;
} */
function get_microtime() // {{{
{
	list($usec, $sec) = explode(" ",microtime()); 
	return ((float)$usec + (float)$sec);
} // }}}

?>
