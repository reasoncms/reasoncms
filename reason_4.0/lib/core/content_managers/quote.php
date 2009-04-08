<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'QuoteManager';

	/**
	 * A content manager for quotes
	 */
	class QuoteManager extends ContentManager
	{
		var $fields_to_remove = array('rating');
		var $field_order = array('name', 'unique_name', 'content', 'description', 'author', 'keywords');
		
		function alter_data()
		{
			$this->set_comments('description', form_comment('If provided, the short version may be used by modules that need to display quotes in a limited amount of space.'));
			$this->set_display_name('description', 'Quotation Text (Short Version)');
			$this->set_element_properties('description', array('rows' => 3));
			$this->set_display_name('content', 'Quotation Text');
			$this->add_required('content');
			
			if (!empty($this->fields_to_remove))
			{
				foreach ($this->fields_to_remove as $field)
				{
					$this->remove_element($field);
				}
			}
			$this -> set_order ($this->field_order);
		}
	}
?>
