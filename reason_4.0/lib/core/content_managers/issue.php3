<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'issue_handler';

	/**
	 * Content manager for publication issues
	 */
	class issue_handler extends ContentManager 
	{
		function alter_data()  // {{{
		{
			$this -> set_display_name ('name', 'Issue Name');
			$this -> set_display_name ('number', 'Volume');
			$this -> set_display_name ('datetime', 'Publication Date');
			$this -> set_display_name ('show_hide', 'Show or Hide Issue?');
			$this -> add_required ('show_hide');
			$this -> add_required ('datetime');
			$this -> set_comments ('show_hide', form_comment('If you set the issue to be hidden, all the news items within that issue will also be hidden.'));
			$this->remove_element('keywords');
			
			$this->remove_element('number');
			$this->remove_element('description');
			
			$this->add_relationship_element('publication', id_of('publication_type'), 
relationship_id_of('issue_to_publication'),'right','select');
			//$this->add_required('publication');
			
			$this -> set_order (array ('publication','name','number','datetime','show_hide'));
		} // }}}
		
		function pre_show_form()
		{
			echo '<div class="issueSortPosts smallText"><a href="'.carl_make_link(array('cur_module'=>'SortPosts')).'">Sort posts</a></div>'."\n";
		}
	}
?>
