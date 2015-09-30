<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
include_once( DISCO_INC . 'disco.php' );

/**
 * An extension of disco reason manual map for use with the 
 * user settings module. Basically hides the form after it's run
 * and adds a redirect to get rid of the user_setting querystring.
 *
 * @author Ben Cochran
 **/
class defaultSettingForm extends Disco
{
	var $success;
	var $completed = false;
	
	/**
	 * The entity that is to be mapped to the form. This entity must
	 * be fed in or defined somewhere.
	 *
	 * @var entity
	 **/
	var $entity;
	
	/**
	 * The mapping of form elements to fields.
	 * In the scheme is as follows:
	 * '$element_name' => array (
	 *			'field' => '$field_name',
	 *			'default' => '$default value to use if the field is reason is not set'
	 *			),
	 *
	 * This can also be done using the map() method.
	 **/
	var $mapping = array();
	
	/**
	 * The netid of the user that is to make the changes in reason.
	 **/
	var $user_netid;

	/**
	 * Maps the an form element to a field in reason, with an optional default value.
	 *
	 * @param string $element_name The name of the element on the form that is to be mapped to a field in the entity
	 * @param string $field_in_reason The name of the field to map to the element
	 * @param mixed $default_value The default value to use on the form if the field is reason is not set
	 **/
	function map($element_name, $field_in_reason, $default_value=false)
	{
		$this->mapping[$element_name]['field'] = $field_in_reason;
		$this->mapping[$element_name]['default'] = $default_value;
	}

	/**
	 * Populates the form with the data based on the mapping defined
	 **/
	function on_every_time()
	{
		if (!empty($this->entity))
			foreach ($this->mapping as $element => $array)
			{
				if ($this->entity->get_value($array['field']))
					$this->set_value($element, $this->entity->get_value($array['field']));
				elseif ($array['default'])
					$this->set_value($element, $array['default']);
			}
	}

	/**
	 * Updates the entity and hides the form
	 **/
	function process()
	{
		$this->show_form = false;
		
		$this->user_netid = $this->entity->id();
		
		if ($this->update_entity())
		{
			$this->success = true;
		}
		else
		{
			$this->success = false;
		}
		$this->completed = true;
	}
	
	/**
	 * Update the entity with each element's value
	 **/
	function update_entity()
	{
		$entity_id = $this->entity->id();
		if (!empty($this->entity) && !empty($this->mapping))
		{
			foreach ($this->mapping as $element => $array)
			{
				$values_array[$array['field']] = $this->get_value($element);
			}
			return reason_update_entity($entity_id,$this->user_netid,$values_array);
		}
		else
		{
			return false;
		}
	}
	
	function where_to()
	{
		return carl_make_link(array('user_setting'=> ''));
	}

}
?>
