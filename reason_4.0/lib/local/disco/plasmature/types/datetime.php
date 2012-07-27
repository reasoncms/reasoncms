<?php
include_once('paths.php');
include_once(DISCO_INC.'plasmature/plasmature.php');
/**
 *	Extension of the textDateTime type that pretties up the time format for the public-facing forms. 
 *	This type also allows the passing of $datepicker_class_arg which overwrites the default class argument for the JavaScript datepicker
 **/

class textDateTimePublicType extends textDateTimeType
{
	public $type = 'textDateTimePublic';
	public $use_fields = array( 'month', 'day', 'year','hour','minute','ampm',);
	public $datepicker_class_arg = 'split-date fill-grid statusformat-l-cc-sp-d-sp-F-sp-Y';
	public $type_valid_args = array('prepopulate', 'use_picker', 'datepicker_class_arg',);
	
	function get_year_display($year_val = '')
	{
	    // The classes on the year display activate the JavaScript
	    // date picker.
	    $class = ($this->use_picker) ? $this->datepicker_class_arg : null;
		return $this->_get_display('year', $year_val, null, ' / ', 4,
		    $class);
	}
	function get_hour_display($hour_val = '')
	{
		$str = ' at ';
		$str .= '<select id="'.$this->name.'hourElement" name="'.$this->name.'[hour]">';
		$str .= '<option value="--"'.($hour_val == '--' ? ' selected="selected"': '').'>--</option>';
		$str .= '<option value="1"'.($hour_val == '1' ? ' selected="selected"': '').'>1</option>';
		$str .= '<option value="2"'.($hour_val == '2' ? ' selected="selected"': '').'>2</option>';
		$str .= '<option value="3"'.($hour_val == '3' ? ' selected="selected"': '').'>3</option>';
		$str .= '<option value="4"'.($hour_val == '4' ? ' selected="selected"': '').'>4</option>';
		$str .= '<option value="5"'.($hour_val == '5' ? ' selected="selected"': '').'>5</option>';
		$str .= '<option value="6"'.($hour_val == '6' ? ' selected="selected"': '').'>6</option>';
		$str .= '<option value="7"'.($hour_val == '7' ? ' selected="selected"': '').'>7</option>';
		$str .= '<option value="8"'.($hour_val == '8' ? ' selected="selected"': '').'>8</option>';
		$str .= '<option value="9"'.($hour_val == '9' ? ' selected="selected"': '').'>9</option>';
		$str .= '<option value="10"'.($hour_val == '10' ? ' selected="selected"': '').'>10</option>';
		$str .= '<option value="11"'.($hour_val == '11' ? ' selected="selected"': '').'>11</option>';
		$str .= '<option value="12"'.($hour_val == '12' ? ' selected="selected"': '').'>12</option>';
		$str .= '</select>';
		return $str. ' : ';
	}
	function get_minute_display($minute_val = '')
	{
	    $str = ' ';
		$str .= '<select id="'.$this->name.'minuteElement" name="'.$this->name.'[minute]">';
		$str .= '<option value="--"'.($minute_val == '--' ? ' selected="selected"': '').'>--</option>';
		$str .= '<option value="00"'.($minute_val == '00' ? ' selected="selected"': '').'>00</option>';
		$str .= '<option value="15"'.($minute_val == '15' ? ' selected="selected"': '').'>15</option>';
		$str .= '<option value="30"'.($minute_val == '30' ? ' selected="selected"': '').'>30</option>';
		$str .= '<option value="45"'.($minute_val == '45' ? ' selected="selected"': '').'>45</option>';
		$str .= '</select>';
		return $str;
	}
	function get_ampm_display($ampm_val)
	{
		$str = ' ';
		$str .= '<select id="'.$this->name.'ampmElement" name="'.$this->name.'[ampm]">';
		$str .= '<option value="--"'.($ampm_val == '--' ? ' selected="selected"': '').'>--</option>';
		$str .= '<option value="am"'.($ampm_val == 'am' ? ' selected="selected"': '').'>AM</option>';
		$str .= '<option value="pm"'.($ampm_val == 'pm' ? ' selected="selected"': '').'>PM</option>';
		$str .= '</select>';
		return $str;
	}
}

class textDatePublicType extends textDateTimePublicType
{
	public $type = 'textDateTimePublic';
	public $date_format = 'Y-m-d';
	public $use_fields = array( 'month', 'day', 'year',);
}


class textTimePublicType extends textDateTimePublicType
{
	public $type = 'textTimePublic';
	public $use_fields = array('hour','minute','ampm',);	
}
?>