<?php
/**
 * A content manager for comments
 * @package reason
 * @subpackage content_managers
 */
 
  /**
   * Store the class name so that the admin page can use this content manager
   */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'commentManager';

	/**
	 * A content manager for comments
	 *
	 * This content manager modifies the editing interface for comments.
	 *
	 */
	class commentManager extends ContentManager
	{
		function pre_show_form()
		{
			parent::pre_show_form();
			$es = new entity_selector();
			$es->add_type(id_of('news'));
			$es->add_left_relationship($this->get_value('id'),relationship_id_of('news_to_comment'));
			$news_items = $es->run_one();
			$news_item_names = array();
			foreach($news_items as $news_item)
			{
				$news_item_names[] = '"'.$news_item->get_value('name').'"';
			}
			if (!empty($news_item_names)) echo '<h3>Comment made to '.implode(', ',$news_item_names).'</h3>'."\n";
		}
		function alter_data() {
			
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			
			if(!$this->get_value('show_hide'))
			{
				$this->set_value('show_hide','show');
			}
			$this->add_required( 'content' );
			$this->add_required( 'datetime' );
			$this->add_required( 'author' );
			$this->add_required( 'show_hide' );
			$this->set_display_name( 'datetime', 'Date &amp; Time Added' );
		}
	}
?>
