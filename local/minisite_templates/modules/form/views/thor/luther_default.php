<?php
/**
 * @package reason_package_local
 * @subpackage minisite_modules
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');

/**
 * Register form with Reason
 */
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'LutherDefaultThorForm';

/**
 * LutherDefaultThorForm is an extension of the DefaultThorForm
 * 
 * @author Steve Smith
 */

class LutherDefaultThorForm extends DefaultThorForm
{
	function on_every_time()
	{   
		if ($this->get_element_name_from_label('Gender'))
			$this->change_element_type($this->get_element_name_from_label('Gender'), 'radio_inline',
				array('options' => array('m' => 'Male', 'f'=>'Female')));
		if ($this->get_element_name_from_label('gender'))
			$this->change_element_type($this->get_element_name_from_label('gender'), 'radio_inline',
				array('options' => array('m' => 'Male', 'f'=>'Female')));
		
		if ($this->get_element_name_from_label('Sex'))
			$this->change_element_type($this->get_element_name_from_label('Sex'), 'radio_inline',
				array('options' => array('m' => 'Male', 'f'=>'Female')));
				
		if ($this->get_element_name_from_label('sex'))
			$this->change_element_type($this->get_element_name_from_label('sex'), 'radio_inline',
				array('options' => array('m' => 'Male', 'f'=>'Female')));
				
		if ($this->get_element_name_from_label('State'))
			$this->change_element_type($this->get_element_name_from_label('State'), 'state');
			
		if ($this->get_element_name_from_label('state'))
			$this->change_element_type($this->get_element_name_from_label('state'), 'state');
		
		if ($this->get_element_name_from_label('State/Province'))
			$this->change_element_type($this->get_element_name_from_label('State/Province'), 'state_province');
			
		if ($this->get_element_name_from_label('state/province'))
			$this->change_element_type($this->get_element_name_from_label('state/province'), 'state_province');

        if ($this->get_element_name_from_label('Country'))
            $this->change_element_type ($this->get_element_name_from_label('Country'), 'country', array('default' => 'United States'));
            
        if ($this->get_element_name_from_label('country'))
            $this->change_element_type ($this->get_element_name_from_label('country'), 'country', array('default' => 'United States'));
	}	
}
?>                      