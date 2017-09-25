<?php
/**
 * @package reason
 * @subpackage modules
 */
 
/**
 * Register markup class
 */
$GLOBALS['form_data_markups']['minisite_templates/modules/form_data/markup/default.php'] = 'DefaultFormDataMarkup';

/**
 * A base class for form data display markup
 *
 * Usage:
 * 1. Extend this class
 * 2. Redefine get_markup() method to produce the desired markup
 * 3. Specify the path to the the extended class file in the page type, like:
 *
 * 'my_form_display' => array(
 * 		'main_post' => array(
 * 			'module' => 'form_data',
 * 			'markup' => 'path/to/markup/class.php',
 * 		),
 * ),
 *
 * 4. Associate a form with the page, or specify it in the page type for a firmer connection
 *
 * Example get_markup() definition for a form with 'Your Name' and 'Your Major' as labels.
 *
 * function get_markup()
 * {
 * 	$ret = '<ul>';
 * 	foreach($this->get_form_data() as $row)
 * 	{
 * 		$ret .= '<li>'.$this->get_label_value('Your Name', $row).': '.$this->get_label_value('Your Major', $row).'</li>';
 * 	}
 * 	return $ret;
 * }
 */
class DefaultFormDataMarkup
{
	protected $form;
	protected $form_data;
	protected $label_map;
	
	/**
	 * Set the form entity
	 *
	 * @param object $form
	 * @return void
	 */
	function set_form($form)
	{
		$this->form = $form;
	}
	/**
	 * Get the form entity
	 * @return object
	 */
	function get_form()
	{
		return $this->form;
	}
	
	/**
	 * Overload this method to interact with the head items object
	 *
	 * Examples:
	 * $head_items->add_stylesheet('/path/to/css.css');
	 * $head_items->add_javascript('/path/to/js.js');
	 *
	 * @param object $head_items
	 * @return void
	 */
	function add_head_items($head_items)
	{
	}
	
	/**
	 * Set the form data
	 *
	 * @param array $form_data
	 * @return void
	 */
	function set_form_data($form_data)
	{
		$this->form_data = $form_data;
	}
	
	/**
	 * Get the form data
	 * @return array
	 */
	function get_form_data()
	{
		return $this->form_data;
	}
	
	/**
	 * Set the map of keys to labels
	 *
	 * @param array $label_map
	 * @return void
	 */
	function set_label_map($label_map)
	{
		$this->label_map = $label_map;
	}
	
	/**
	 * Get the map of keys to labels
	 *
	 * @return array
	 */
	function get_label_map()
	{
		return $this->label_map;
	}
	
	/**
	 * Get the label for a given key
	 *
	 * @param string $key
	 * @return string label or FALSE if label/key pair not found
	 */
	function get_label($key)
	{
		if(isset($this->label_map[$key]))
		{
			return $this->label_map[$key];
		}
		return FALSE;
	}
	
	/**
	 * Get the key for a given label
	 *
	 * @param string $label
	 * @return mixed string key or FALSE if label/key pair not found
	 */
	function get_key($label)
	{
		return array_search($label, $this->label_map);
	}
	
	/**
	 * Get the value for a given label for a given row
	 *
	 * Note that this method runs htmlspecialchars() on the return value by default.
	 *
	 * @param string $label
	 * @param array $row
	 * @param bool $htmlspecialchars
	 * @return mixed string value or FALSE if label does not appear valid
	 */
	function get_label_value($label, $row, $htmlspecialchars = true)
	{
		if($key = $this->get_key($label))
		{
			return $htmlspecialchars ? htmlspecialchars($row[$key], ENT_QUOTES) : $row[$key];
		}
		trigger_error('Label "'.$label.'" not found.');
		return false;
	}
	
	/**
	 * Produce an HTML formatted report on the structure of the form
	 *
	 * This can assist in debugging or in initial development of the form data display
	 *
	 * @return string HTML report
	 */
	function get_data_structure_report()
	{
		$ret = '';
		$ret .= '<h3>Form Data Report</h3>';
		$ret .= '<h4>Label Map</h4>';
		$ret .= '<ul>';
		$label_map = $this->get_label_map();
		foreach($label_map as $key => $label)
		{
			$ret .= '<li><strong>'.htmlspecialchars($key).':</strong> '.htmlspecialchars($label).'</li>';
		}
		
		$ret .= '</ul>';
		$ret .= '<h4>Data Report</h4>';
		$data = $this->get_form_data();
		if(empty($data))
		{
			$ret .= '<p>No form data in database.</p>';
		}
		else
		{
			$ret .= '<p>'.count($data).' rows in database.</p>';
			$firstrow = reset($data);
			$keys_without_labels = array();
			foreach(array_keys($firstrow) as $key)
			{
				if(!isset($label_map[$key]))
				{
					$keys_without_labels[] = $key;
				}
			}
			if(!empty($keys_without_labels))
			{
				$ret .= '<h5>Keys without labels</h5>';
				$ret .= '<ul>';
				foreach($keys_without_labels as $key)
				{
					$ret .= '<li>'.htmlspecialchars($key).'</li>';
				}
				$ret .= '</ul>';
			}
		}
		return $ret;
	}
	/**
	 * Get the markup
	 * @return string HTML
	 */
	function get_markup()
	{
		return $this->get_data_structure_report();
	}
}