<?php

/**
 * Type library for new html5 input types.
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."text.php";

/**
 * Plasmature class for the tel html5 input type. As of 8/6/15,
 * this is not supported by most major browsers.
 * @package disco
 * @subpackage plasmature
 */
class telType extends defaultTextType
{
	var $type = 'tel';

	function get_display()
	{
		$str  = '<input type="tel" name="'.$this->name.'" id="'.$this->get_id().'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'"/>';
		return $str;
	}

	function grab()
	{
		$value = $this->grab_value();
		if(strlen($value)>0)
		{
			$value = preg_replace("/[^0-9,.]/","",$value);
			if(!preg_match('/^\d{8,15}$/',$value))
			{
				$this->set_error('Please enter a valid phone number.');
			}
		}
		parent::grab();
	}
	
	function get_label_target_id()
	{
		return $this->get_id();
	}
}

/**
 * Plasmature class for the number html5 input type.
 * Has parameters min, max, and step.
 * @package disco
 * @subpackage plasmature
 */
class numberType extends defaultTextType
{
	var $type = 'number';

	var $type_valid_args = array( 'min', 'max', 'step');
	
	function get_display()
	{
		$str  = '<input type="number" name="'.$this->name.'" id="'.$this->get_id().'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'"';
		if(isset($this->min)){
			$str .= ' min="'.$this->min.'"';
		}
		if(isset($this->max)){
			$str .= ' max="'.$this->max.'"';
		}
		if(isset($this->step)){
			$str .= ' step="'.$this->step.'"';
		}
		$str .= '/>'; 
		return $str;
	}
	
	function get_label_target_id()
	{
		return $this->get_id();
	}
}

/**
 * Plasmature class for the range html5 input type.
 * Has parameters min, max, and step.
 * @package disco
 * @subpackage plasmature
 */
class rangeType extends defaultTextType
{
	var $type = 'range';

	var $type_valid_args = array( 'min', 'max','step');
	
	
	function get_display()
	{
		$str  = '<input type="range" name="'.$this->name.'" id="'.$this->get_id().'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'"';
		if(isset($this->min)){
			$str .= ' min="'.$this->min.'"';
		}
		if(isset($this->max)){
			$str .= ' max="'.$this->max.'"';
		}
		if(isset($this->step)){
			$str .= ' step="'.$this->step.'"';
		}
		$str .= '/>';
		return $str;
	}
	
	function get_label_target_id()
	{
		return $this->get_id();
	}
}

/**
 * Plasmature class for the search html5 input type.
 * @package disco
 * @subpackage plasmature
 */

class searchType extends textType
{
	var $type = 'search';

	function grab()
	{
		// I don't think there needs to be any error checking for searches, but this can be uncommented to use some method
		/*
		$value = $this->grab_value();
		if(!filter_var($value,FILTER_VALIDATE_EMAIL))
		{
			$this->set_error('Something went wrong');
		}
		*/
		parent::grab();
	}
	function get_display()
	{	
		$str  = '<input type="search" name="'.$this->name.'" id="'.$this->get_id().'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'"/>';
		return $str;
	}
}

/**
 * Plasmature class for the email html5 input type.
 * @package disco
 * @subpackage plasmature
 */

class emailType extends textType
{
 	var $type = 'email';

 	function grab()
	{
		$value = $this->grab_value();
		if(strlen($value)>0)
		{	
			if(!filter_var($value,FILTER_VALIDATE_EMAIL))
			{
				$this->set_error('Please enter a valid email.');
			}
		}
		parent::grab();
	}
	function get_display()
	{
		$str  = '<input type="email" name="'.$this->name.'" id="'.$this->get_id().'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'"/>';
		return $str;
	}
}

/**
 * Plasmature class for the url html5 input type.
 * @package disco
 * @subpackage plasmature
 */

class urlType extends textType
{
 	var $type = 'url';
 
	function grab()
	{
		$value = $this->grab_value();
		if(strlen($value)>0)
		{
			if(!filter_var($value,FILTER_VALIDATE_URL))
			{
				$this->set_error('Please enter a valid url.');
			}
		}
		parent::grab();
	}
	function get_display()
	{
		$str  = '<input type="url" name="'.$this->name.'" id="'.$this->get_id().'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'"/>';
		return $str;
	}
}

