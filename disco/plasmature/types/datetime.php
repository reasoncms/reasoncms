<?php

/**
 * Date/time type library.
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."default.php";
require_once PLASMATURE_TYPES_INC."text.php";
require_once PLASMATURE_TYPES_INC."hidden.php";
require_once PLASMATURE_TYPES_INC."options.php";

/**
 * @package disco
 * @subpackage plasmature
 */
class disabledDateType extends protectedType
{
	var $type = 'disabledDate';
	var $format = 'F j, Y, g:i a';

	function grab()
	{
	}

	function get_display()
	{
		$year = substr( $this->get(), 0, 4 );
		$month = substr( $this->get(), 4, 2 );
		$day = substr( $this->get(), 6, 2 );
		$hour = substr( $this->get(), 8, 2 );
		$minute = substr( $this->get(), 10, 2 );
		$second = substr( $this->get(), 12, 4 );
		$str = carl_date( $this->format, carl_mktime( $hour, $minute, $second, $month, $day, $year ) );
		return $str;
	}
}

/**
 * Presents a drop-down of months.
 * Months are displayed in the format specified by {@link date_format}.
 * @package disco
 * @subpackage plasmature
 */
class monthType extends selectType
{
	var $type = 'month';
	var $date_format = 'm (F)';
	var $type_valid_args = array('date_format', 'pad_keys');
	var $sort_options = false;
	var $pad_keys = false; // Use two digit zero-padding strings (e.g. 01) instead of integer keys
	function load_options( $args = array() )
	{
		for($month = 1; $month <= 12; $month++)
		{
			$key = ($this->pad_keys) ? sprintf('%02s', $month) : $month;
			/* Note the use of mktime instead of carl_mktime. This is to avoid a strict notice in php 5 regarding is_dst, and
			should not cause any problems as we are fixing the year inside the Unix era */
			$this->options[ $key ] = carl_date($this->date_format,mktime(0,0,0,$month,1,1971));
		}
	}
}
class month_no_labelType extends monthType
{
	var $_labeled = false;
}

/**
 * Presents a drop-down of years.
 *
 * Will display years from 2000 to 2050 by default; these can be altered by
 * setting {@link start} and {@link end}, or by setting {@link
 * num_years_before_today} and {@link num_years_after_today}. By default, each
 * year between {@link start} and {@link end} is displayed, but the interval
 * may be changed by setting {@link step}.
 *
 * @package disco
 * @subpackage plasmature
 */
class yearType extends numrangeType
{
 	var $type = 'year';
	var $start = 2000;
	var $end = 2050;

	/**
	 * The number of years before the current date to start the range.
	 * Set this instead of {@link start} if you want to use a relative range.
	 */
	var $num_years_before_today;

	/**
	 * The number of years after the current date to end the range.
	 * Set this instead of {@link end} if you want to use a relative range.
	 */
	var $num_years_after_today;
	var $type_valid_args = array('num_years_before_today',
		'num_years_after_today');

	function load_options( $args = array() )
	{
		$this->determine_start_year();
		$this->determine_end_year();
		parent::load_options();
	}

	function determine_start_year()
	{
	 	if(!empty($this->num_years_before_today))
		{
			$current_date = getdate();
			$this->start = $current_date['year'] - $this->num_years_before_today;
		}
	}

	function determine_end_year()
	{
		if(!empty($this->num_years_after_today))
		{
			$current_date = getdate();
			$this->end = $current_date['year'] + $this->num_years_after_today;
		}
	 }
}

class year_no_labelType extends yearType
{
	var $_labeled = false;
}

/**
 * A plasmature element to represent the date/time as multiple text fields.
 * This element displays itself as multiple HTML inputs, but it is treated within plasmature and disco as a single
 * element with a single value.
 *
 * @package disco
 * @subpackage plasmature
 *
 * @todo Make a date interface to inherit from.
 * @todo Add timezone functionality.
 * @todo Add display order functionality for fields
 * @todo Add ways to change the delimiters between fields
 */
class textDateTimeType extends textType
{
	var $type = 'textDateTime';
	var $type_valid_args = array(   'prepopulate',
									#'date_format',
									'year_max',
									'year_min',
									'use_picker'
								);
	var $prepopulate = false;
	//note: this isn't a valid arg because it would mess up some of the checks in get() if it was.
	var $date_format = 'Y-m-d H:i:s';
	var $year_max;
	var $year_min;
	var $use_picker = true; // default to using the JavaScript date picker
	var $year;
	var $month;
	var $day;
	var $hour;
	var $minute;
	var $second;
	var $ampm;
	/**
	 * All datetime portions.  This array in child classes define which fields we want to capture.
	 * @var array
	 */
	var $use_fields = array(
		'month',
		'day',
		'year',
		'hour',
		'minute',
		'second',
		'ampm',
	);
	var $id_suffixes = array(
		'year' => '',
		'month' => 'mm',
		'day' => 'dd',
		'hour' => 'HH',
		'minute' => 'MM',
		'second' => 'SS',
		'ampm' => 'ampmElement',
	);
	/**
	 *  Sets the value to the current datetime if the value is empty and {@link prepopulate} is set.
	 */
	function additional_init_actions($args = array())
	{
		if( !$this->get() AND !empty( $this->prepopulate) )
		{
			$this->set( time() );
		}
	}
	function set( $value )
	{
		//get_unix_timestamp now uses carl_ date functions, so this will be compatible with 64-bit timestamps
		$value = get_unix_timestamp( $value );
		$date_pieces = array( 'year' => false,
							  'month' => false,
							  'day' => false,
							  'hour' => false,
							  'minute' => false,
							  'second' => false,
							  'ampm' => false,
							 );
		if( !empty($value) && $value != -1 )
			list( $date_pieces['year'], $date_pieces['month'], $date_pieces['day'], $date_pieces['hour'], $date_pieces['minute'], $date_pieces['second'], $date_pieces['ampm'] ) = explode('-', carl_date('Y-n-j-g-i-s-a', $value));
		//only set the fields that we're actually using
		foreach($this->use_fields as $field_name)
		{
			#if(isset($date_pieces[$field_name]))
			if($date_pieces[$field_name])
				$this->$field_name = $date_pieces[$field_name];
		}
	}
	function grab()
	{
		$request = $this->get_request();
		$fields_that_have_values = array();
		// loop through fields to capture and capture them
		foreach( $this->use_fields AS $field )
		{
			$this->$field = isset( $request[ $this->name ][ $field ] ) ? $request[ $this->name ][ $field ] : '';
			if(!empty($this->$field) && $field != 'ampm')
				$fields_that_have_values[] = $field;
		}
		//run error checks if any values have been entered for this date.
		if(!empty($fields_that_have_values))
		{
			$this->run_error_checks();
		}
	}
	function run_error_checks()
	{
		$name = trim($this->display_name);
		if(empty($name))
		{
			$name = $this->name;
		}
		$name = prettify_string($name);
		if (($this->month AND !is_numeric($this->month)) ||
			($this->day AND !is_numeric($this->day)) ||
				($this->year AND !is_numeric($this->year)) )
		{
			$this->set_error(  $name.':  Date values need to be numbers.' );
			return;
		}
		if(in_array('year', $this->use_fields))
		{
			//check to make sure we have a year if it's in the use_fields
			//otherwise, an empty year will become 2000 and that's confusingly Evil.
			if(empty($this->year))
			{
				$this->set_error( $name.':  Please enter a year for this date.');
			}
			else
			{
				//check to make sure that the year is within valid parameters
				if(!checkdate( 1, 1, $this->year))
					$this->set_error( $name.':  This does not appear to be a valid year.' );
				elseif(!empty($this->year_min) && $this->year < $this->year_min)
					$this->set_error( $name.':  Dates before the year '.$this->year_min.' cannot be processed.' );
				elseif(!empty($this->year_max) && $this->year > $this->year_max)
					$this->set_error( $name.':  Dates after the year '.$this->year_max.' cannot be processed.'  );
			}
		}
		if( $this->month AND $this->day AND $this->year )
		{
			if( !checkdate( $this->month, $this->day, $this->year ) )
				$this->set_error(  $name.':  This does not appear to be a valid date.' );
		}
		elseif( $this->month && $this->year )
		{
			if( !checkdate( $this->month, 1, $this->year ))
				$this->set_error ($name.':  This does not appear to be a valid month/year combination.');
		}
		elseif( $this->day && (in_array('month', $this->use_fields) && !$this->month) )
		{
			$this->set_error( $name.': Please specify a month for this date.' );
		}
	}
	function get()
	{
		$all_fields_empty = true;
		foreach($this->use_fields as $field_name)
		{
			if(!empty($this->$field_name) && $field_name != 'ampm')	//ampm will alaways be set, since it's a select.
			{
				$all_fields_empty = false;
				break;
			}
		}
		if($all_fields_empty)
		{
			$date = false;  //used to be 0; changed so that disco notices if this has no value.
		}
		else
		{
			if(in_array('hour', $this->use_fields))
			{
				if( $this->hour == 12 )
				{
					if( $this->ampm == 'am' )
						$this->hour = 0;
					else
						$this->hour = 12;
				}
				else
				{
					// if PM is chosen, make sure to add 12 hours
					if( $this->ampm == 'pm' AND $this->hour < 12 )
						$this->hour = $this->hour+12;
				}
			}
			$date_format = $this->date_format;
			//if the day isn't set, give it a value while making the timestamp so that carl_maketime() doesn't increment back.
			//just make sure that it's not part of the date format when we're formatting the date from the timestamp.
			if(empty($this->day))
			{
				$this->day = 1;
				$date_format = str_replace('-d', '', $date_format);
			}
			//if the month isn't set, give it a value while making the timestamp so that carl_maketime() doesn't increment back.
			//just make sure that it's not part of the date format when we're formatting the date from the timestamp.
			if(empty($this->month))
			{
				$this->month = 1;
				$date_format = str_replace('-m', '', $date_format);
			}
			$timestamp = carl_mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
			$date = carl_date( $date_format, $timestamp );
		}
		return $date;
	}
	function debug_display()
	{
		foreach( $this AS $var => $value )
		{
			if( in_array( $var, array('year','month','day','hour','minute','second','ampm') ) )
				echo $var.' = '.$value.'<br />';
		}
	}

	/**
	 * Augment a head items object to insert the date_picker head items
	 *
	 * @todo standardize a mechanism for this to be handled in plasmature objects
	 */
	function augment_head_items(&$head_items)
	{
		if (!defined("DATE_PICKER_HEAD_ITEMS_LOADED"))
		{
			define("DATE_PICKER_HEAD_ITEMS_LOADED", true);
			$head_items->add_javascript(DATE_PICKER_HTTP_PATH.'js/datepicker.min.js');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/datepicker.js');
			$head_items->add_stylesheet(DATE_PICKER_HTTP_PATH. 'css/datepicker.min.css');
		}
	}

	function display()
	{
	    if ($this->use_picker && !defined("DATE_PICKER_HEAD_ITEMS_LOADED") && !defined('_PLASMATURE_INCLUDED_DATEPICKER') && defined('REASON_HTTP_BASE_PATH') )
	    {
	        /**
	         * We specify the english datepicker .js file ... the dynamic mechanism to pick the language
	         * file that is built into the datepicker does not appear to work reliably when the date_picker is
	         * added inline instead of into the head items
	         */
	        echo '<script type="text/javascript" src="'. DATE_PICKER_HTTP_PATH.'js/lang/en-US.js"></script>'."\n";
	       	echo '<script type="text/javascript" src="'. DATE_PICKER_HTTP_PATH.'js/datepicker.min.js"></script>'."\n";
	       	echo '<script type="text/javascript" src="'. REASON_HTTP_BASE_PATH.'js/datepicker.js"></script>'."\n";
           	echo '<link href="'.DATE_PICKER_HTTP_PATH. 'css/datepicker.min.css" rel="stylesheet" type="text/css" />'."\n";
           	define('_PLASMATURE_INCLUDED_DATEPICKER', true);
	    }
	    parent::display();
	}
	function get_display()
	{
		$str = '';
		
		$before = '';
		$after = '';
		if(count($this->use_fields) > 1)
		{
			$before = '<span role="group" aria-label=
		"'.htmlspecialchars($this->display_name).'">';
			$after = '</span>';
		}
		
		$str .= $before;
		foreach($this->use_fields as $field_name)
		{
			$get_val_method = 'get_'.$field_name.'_value_for_display';
			if(method_exists($this, $get_val_method))
				$display_val = $this->$get_val_method();
			else
				$display_val = $this->$field_name;
			$display_method = 'get_'.$field_name.'_display';
			if(method_exists($this, $display_method))
				$str .= $this->$display_method($display_val);
			else
				trigger_error($field_name.' is in $use_fields, but no display method exists for it');
		}
		$str .= $after;
		return $str;
	}
	function get_value_for_month_display()
	{
		if((int)$this->month)
			return (int)$this->month;
	}
	function get_value_for_day_display()
	{
		if((int)$this->day)
			return (int)$this->day;
	}
	function get_value_for_year_display()
	{
		if((int)$this->year)
			return (int)$this->year;
	}
	function get_value_for_hour_display()
	{
		//convert from 24-hr time to 12-hr time, if applicable
		if( $this->hour > 12 )
			$h = $this->hour - 12;
		//if the hour is 0 but ampm is set, then we really mean 12
		//if there really isn't a value, then ampm will also be empty.
		elseif(empty($this->hour) && !empty($this->ampm))
			$h = 12;
		else
			$h = $this->hour;
		$str .= $this->get_hour_display($h);
	}
	function _get_display($name, $value, $separator='', $size=2,
	    $class=null)
	{
	    $class = ($class)
	        ? ' class="'.$class.'"'
	        : '';
	    $value = htmlspecialchars($value, ENT_QUOTES);
	    return $separator.'<input type="text"'.$class.' size="'.$size.'" '.
	        'maxlength="'.$size.'" id="'.htmlspecialchars($this->get_field_id($name)).'" '.
	        'name="'.$this->name.'['.$name.']" value="'.htmlspecialchars($value).'" aria-label="'.htmlspecialchars($name).' ('.htmlspecialchars($size).' digits)" />';
	}
	
	function get_month_display($month_val = '')
	{
	    return $this->_get_display('month', $month_val);
	}
	function get_day_display($day_val = '')
	{
	    return $this->_get_display('day', $day_val, ' / ');
	}
	function get_year_display($year_val = '')
	{
	    // The classes on the year display activate the JavaScript
	    // date picker.
	    $class = ($this->use_picker) ? 'datepicker' : null;
		return $this->_get_display('year', $year_val, ' / ', 4,
		    $class);
	}
	function get_hour_display($hour_val = '')
	{
		return $this->_get_display('hour', $hour_val, 
		    '<span class="datetimeAt">&nbsp;&nbsp; at ');
	}
	function get_minute_display($minute_val = '')
	{
	    return $this->_get_display('minute', $minute_val, ' : ');
	}
	function get_second_display($second_val = '')
	{
		return $this->_get_display('second', $second_val, ' : ');
	}
	function get_ampm_display($ampm_val)
	{
		$str = ' ';
		$str .= '<select id="'.$this->name.'ampmElement" name="'.$this->name.'[ampm]" aria-label="AM or PM">';
		$str .= '<option value="am"'.($ampm_val == 'am' ? ' selected="selected"': '').'>AM</option>';
		$str .= '<option value="pm"'.($ampm_val == 'pm' ? ' selected="selected"': '').'>PM</option>';
		$str .= '</select></span>';
		return $str;
	}
	function get_cleanup_rules()
	{
		return array( $this->name => array( 'function' => 'turn_into_array' ));
	}
	function get_label_target_id()
	{
		if(count($this->use_fields) == 1)
		{
			$field = current($this->use_fields);
			if($id = $this->get_field_id($field))
				return $id;
		}
		return false;
	}
	function get_field_id($field)
	{
		if(isset($this->id_suffixes[$field]))
		{
			if(!empty($this->id_suffixes[$field]))
				return $this->name.'-'.$this->id_suffixes[$field];
			return $this->name;
		}
		return NULL;
	}
 }

/**
 * Identical to {@link textDateTimeType} except that it only uses month, day, and year.
 */
class textDateType extends textDateTimeType {
	var $type = 'textDate';
	var $date_format = 'Y-m-d';
	var $use_fields = array( 'month', 'day', 'year');
	function set( $value )
	{
		if(strlen($value) == 10)
			$value = $value.' 00:00:00';
		parent::set($value);
	}
}

/**
 * @package disco
 * @subpackage plasmature
 */
 class textDateTime_jsType extends textDateTimeType
 {
 	var $type = 'textDateTime_jsType';
	var $script_url = '';
	var $type_valid_args = array( 'script_url' );
	function get_display()
	{
		$str = parent::get_display();
		$str .= $this->script_tag();
		return $str;
	}
	function script_tag()
	{
		$s = '';
		if ( !empty( $this->script_url ) )
			$s = '<script language="JavaScript" src="'.$this->script_url.'"></script>'."\n";
		return $s;
	}
}

/**
 * A date type that includes a month select and a year text field.
 *
 * Note: Unlike other date types, this does not support the js date picker, since there is no day of month
 *
 * @package disco
 * @subpackage plasmature
 */
class selectMonthTextYearType extends textDateTimeType
{
	var $type = 'selectMonthTextYear';
	var $date_format = 'Y-m';
	var $use_fields = array ('month', 'year');
	var $month_element;
	var $month_args = array('date_format' => 'F');
	var $type_valid_args = array( 'month_args' );
	var $use_picker = false;
	function additional_init_actions($args = array())
	{
		parent::additional_init_actions();
		$this->use_picker = false; // reject any attempts to use the date picker.
		$this->init_month_element();
	}
	function init_month_element()
	{
		//set up the month plasmature element
		$this->month_element = new month_no_labelType;
		$this->month_element->set_request( $this->_request );
		$this->month_element->set_name( $this->name.'[month]' );
		$this->month_element->set_display_name( 'month' );
		if(empty($this->month_args['date_format']))
			$this->month_args['date_format'] = 'F';
		$this->month_element->init($this->month_args);
	}
	function set( $value )
	{
		$value = get_unix_timestamp( $value.'-01' );
		if( !empty($value) && $value != -1 )
		{
			list( $this->year, $this->month) = explode('-',carl_date($this->date_format, $value));
		}
		else
		{
			$this->year = '';
			$this->month = '';
		}
	}
	function get_month_display($month_val = '')
	{
		$this->month_element->set($month_val);
		return $this->month_element->get_display();
	}
}

/**
 * A date type that includes a month select and a year select.
 *
 * This uses {@link monthType} and {@link yearType} objects.
 *
 * Note: Unlike other date types, this does not support the js date picker, since there is no day of month
 *
 * @package disco
 * @subpackage plasmature
 */
class selectMonthYearType extends selectMonthTextYearType
{
	var $type = 'selectMonthYear';
	var $type_valid_args = array( 'year_args');
	var $year_element;
	var $year_args = array();
	var $use_picker = false;
	/**
	 * Instantiate a new {@link monthType} element and {@link yearType} element.
	 */
	function additional_init_actions($args = array())
	{
		parent::additional_init_actions();
		$this->init_year_element();
	}
	function init_year_element()
	{
		//set up the year plasmature element
		$this->year_element = new year_no_labelType;
		$this->year_element->set_request( $this->_request );
		$this->year_element->set_display_name('year');
		$this->year_element->set_name( $this->name.'[year]' );
		if(empty($this->year_args['end']) || (isset($this->year_args['end']) && $this->year_args['end'] > $this->year_max))
			$this->year_args['end'] = $this->year_max;
		if(empty($this->year_args['start']) || (isset($this->year_args['start']) && $this->year_args['start'] < $this->year_min))
			$this->year_args['start'] = $this->year_min;
		if(empty($this->year_args['end']))
			unset($this->year_args['end']);
		if(empty($this->year_args['start']))
			unset($this->year_args['start']);
		$this->year_element->init($this->year_args);
	}
	function get_year_display($year_val = '')
	{
		$this->year_element->set($year_val);
		return ' / '.$this->year_element->get_display();
	}
}
