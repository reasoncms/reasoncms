<?php
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'QuoteManager';

	class QuoteManager extends ContentManager
	{
		function alter_data()
		{
			$this->set_comments('description', form_comment('If provided, the short version may be used by modules that need to display quotes in a limited amount of space.'));
			$this->set_display_name('description', 'Quotation Text (Short Version)');
			$this->set_element_properties('description', array('rows' => 3));
			$this->set_display_name('content', 'Quotation Text');
			$this->set_order(array('name', 'unique_name', 'content', 'description', 'author', 'keywords'));
			$this->add_required('content');
		}
	}
?>
