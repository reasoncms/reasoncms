<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register the content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'newsSectionHandler';

	/**
	 * A content manager for news sections
	 */
	class newsSectionHandler extends ContentManager 
	{
		function alter_data()  // {{{
		{
			$this->set_display_name('name', 'Section Name');
			$this->remove_element('keywords');
			
			$this->add_relationship_element('publication', id_of('publication_type'), 
relationship_id_of('news_section_to_publication'),'right','select');
			//$this->add_required('publication');
			
			$this->set_order(array ('publication','name'));
		} // }}}
	}
?>
