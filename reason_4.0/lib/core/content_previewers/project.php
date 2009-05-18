<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	/**
	 * Register previewer with Reason
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'project_previewer';

	/**
	 * A content previewer for projects
	 */
	class project_previewer extends default_previewer
	{
		function display_entity() // {{{
		{
			$this->start_table();
			
			// iFrame Preview
			if( $this->_entity->get_value( 'bug_state' ) != 'Done' )
			{
				// iFrame Preview
				reason_include_once( 'function_libraries/URL_History.php' );
				$site = $this->_entity->get_owner();
				$es = new entity_selector( $site->id() );
				$es->add_type( id_of('minisite_page') );
				$es->add_relation('page_node.custom_page = "projects"');
				$es->set_num(1);
				$pages = $es->run_one();
				if(!empty($pages))
				{
					$page = current($pages);
					$url = reason_get_page_url( $page->id() ).'?item_id='.$this->_entity->id();
					$this->show_item_default( 'Public View of Project' , '<iframe src="'.$url.'" width="100%" height="400"></iframe>' );
					$this->show_item_default( 'Link to Public View of Project' , '<a href="'.$url.'">'.$url.'</a>' );
				}
			}
			
			// Everything Else
			$this->show_all_values( $this->_entity->get_values() );
			
			$this->end_table();
		} // }}}
	}
?>
