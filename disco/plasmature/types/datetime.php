<?php

//include_once('paths.php');
include_once('/usr/local/webapps/reason/reason_package/disco/plasmature/plasmature.php');
include_once(PLASMATURE_TYPES_INC . "datetime.php");
//////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////
class textDateTimeVisitType extends textDateTimeType
{
	var $type = 'textDateTimeVisit';
	var $date_format = 'Y-m-d H:i';
	var $use_fields = array( 'month', 'day', 'year','hour','minute','ampm',);
	var $datepicker_arg = 'split-date fill-grid-no-select disable-days-7 statusformat-l-cc-sp-d-sp-F-sp-Y opacity-99 disable-20091126 disable-20091127 disable-20091128 disable-20091212 disable-20091219 disable-xxxx1224 disable-xxxx1225 disable-xxxx1226 disable-xxxx1231 disable-xxxx0101 disable-xxxx0102 disable-20100130 disable-20100320 disable-20100327 disable-20100402 disable-20100403 disable-20100531 disable-20100603 disable-20100515 disable-20100522 disable-20100529 disable-20100605 disable-20100612 disable-20100619 disable-20100626 disable-20100703 disable-20100710 disable-20100717 disable-20100724 disable-20100731 disable-20100807 disable-20100814 disable-20100821 disable-20100828 disable-20100904 disable-20100911 range-low-today range-high-20100912'; //the class argument for the JavaScript datepicker
	var $type_valid_args = array(   'prepopulate',
									#'date_format',
									'year_max',
									'year_min',
									'use_picker',
									'datepicker_arg',
								);


	

	function get_year_display($year_val = '')
	{
	    // The classes on the year display activate the JavaScript
	    // date picker.
	    $class = ($this->use_picker)
	        ? 'split-date fill-grid-no-select disable-days-7 statusformat-l-cc-sp-d-sp-F-sp-Y opacity-99 disable-20091126 disable-20091127 disable-20091128 disable-20091212 disable-20091219 disable-xxxx1224 disable-xxxx1225 disable-xxxx1226 disable-xxxx1231 disable-xxxx0101 disable-xxxx0102 disable-20100130 disable-20100320 disable-20100327 disable-20100402 disable-20100403 disable-20100531 disable-20100603 disable-20100515 disable-20100522 disable-20100529 disable-20100605 disable-20100612 disable-20100619 disable-20100626 disable-20100703 disable-20100710 disable-20100717 disable-20100724 disable-20100731 disable-20100807 disable-20100814 disable-20100821 disable-20100828 disable-20100904 disable-20100911 range-low-today range-high-20100912'
	        : null;
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
		return $str;
		//return $this->_get_display($str, $hour_val, 'HH',
		 //   '&nbsp;&nbsp; at ');
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
///////////////////////////////////////////////// 
//////////////////////////////////////////////////////////////////////////////////////////////////
 
?>
