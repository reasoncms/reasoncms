<?php
/**
 * @package disco
 * @author Matt Ryan
 *
 */

/**
 * Include Disco, Akismet
 */
include_once( 'paths.php');
include_once( DISCO_INC . 'disco.php' );

/**
 * Class to add honeypot fields to a disco form
 */
class HoneypotDiscoPlugin
{
	/**
	 * The Disco form containing fields to check
	 * @var object
	 */
	protected $disco_form;
	
	protected $honeypot_fields = array(
		'tarbabypre',
		'tarbaby',
		'noturl',
		'antlion',
	);

	/**
	 * Disco form must be passed in -- callbacks will be attached to various process points in 
	 * disco 
	 * @param $disco_form The Disco form to check for spam
	 */
	public function __construct($disco_form)
	{
		$this->disco_form = $disco_form;
		$this->disco_form->add_callback(array($this, 'set_up_fields'), 'on_every_time');
		$this->disco_form->add_callback(array($this, 'get_css'), 'pre_show_form');
		$this->disco_form->add_callback(array($this, 'detect_robot'), 'run_error_checks');
	}
	
	public function get_css($disco)
	{
		$ret = '';
		$parts = array();
		foreach($this->honeypot_fields() as $field)
		{
			$id = str_replace("_", "", $field);
			$parts[] = 'form #'.$id.'Row';
			$parts[] = '#'.$id.'Item';
		}
		if(!empty($parts))
		{
			$ret .= '<style type="text/css">';
			$ret .= implode(',',$parts);
			$ret .= '{display:none;}';
			$ret .= '</style>';
		}
		return $ret;
	}
	
	public function honeypot_fields($fields = NULL)
	{
		if(is_array($fields))
		{
			$this->honeypot_fields = $fields;
		}
		return $this->honeypot_fields;
	}

	/**
	 * Set up the fields with a disco object
	 */
	public function set_up_fields($disco)
	{
		foreach($this->honeypot_fields() as $field)
		{
			$disco->add_element($field);
			$disco->add_comments($field, '(Please don\'t fill in this field.)');
		}
	}

	/**
	 * Check if a robot is poking around
	 */
	public function detect_robot($disco)
	{
		foreach($this->honeypot_fields() as $field)
		{
			if($disco->get_value($field))
			{
				$disco->set_error($field, 'It looks like you may have entered a value in a field that should remain empty. Please try again.');
			}
		}
	}
}

