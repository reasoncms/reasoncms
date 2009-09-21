<?php
reason_include_once('disco/plasmature/types/datetime.php');

require_once "disco/plasmature/types/datetime.php";

class textDateTimeNoSecondsType extends textDateTimeType {
	var $type = 'textDateTime';
	var $date_format = 'Y-m-d H:i';
	var $use_fields = array( 'month', 'day', 'year','hour','minute','ampm',);
}

function get_year_display($year_val = '')
	{
	    // The classes on the year display activate the JavaScript
	    // date picker.
	    $class = ($this->use_picker)
	        ? 'split-date fill-grid statusformat-l-cc-sp-d-sp-F-sp-Y'
	        : null;
		return $this->_get_display('year', $year_val, null, ' / ', 4,
		    $class);
	}
	function get_hour_display($hour_val = '')
	{
		return $this->_get_display('hour', $hour_val, 'HH',
		    '&nbsp;&nbsp; at ');
	}
	function get_minute_display($minute_val = '')
	{
	    $str = ' ';
		$str .= '<select id="'.$this->name.'minuteElement" name="'.$this->name.'[minute]">';
		$str .= '<option value="00"'.($ampm_val == '00' ? ' selected="selected"': '').'>00</option>';
		$str .= '<option value="30"'.($ampm_val == '30' ? ' selected="selected"': '').'>30</option>';
		$str .= '</select>';
		return $str;
	}
/*
	function get_second_display($second_val = '')
	{
		return $this->_get_display('second', $second_val, 'SS', ' : ');
	}
*/
	function get_ampm_display($ampm_val)
	{
		$str = ' ';
		$str .= '<select id="'.$this->name.'ampmElement" name="'.$this->name.'[ampm]">';
		$str .= '<option value="am"'.($ampm_val == 'am' ? ' selected="selected"': '').'>AM</option>';
		$str .= '<option value="pm"'.($ampm_val == 'pm' ? ' selected="selected"': '').'>PM</option>';
		$str .= '</select>';
		return $str;
	}
?>