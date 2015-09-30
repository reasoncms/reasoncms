<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include the base class & register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'blogDescriptionModule';
	
	/**
	 * Minisite module that displays the description of the blog attached to the page
	 */
	class blogDescriptionModule extends DefaultMinisiteModule
	{
		var $blog;
		function init( $args = array() )
		{
			$es = new entity_selector( $this->parent->site_id );
			$es->description = 'Selecting blog/publications for this page';
			$es->add_type( id_of('publication_type') );
			$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_publication') );
			$es->set_num( 1 );
			$blogs = $es->run_one();
			if(!empty($blogs))
			{
				$this->blog = current($blogs);
			}
		}
		function has_content()
		{
			if( !empty($this->blog) && $this->blog->get_value('description') )
				return true;
			else
				return false;
		}
		function run()
		{
			echo '<div id="blogDescription">'."\n";
			echo '<p>'.$this->blog->get_value('description').'</p>';
			echo '</div>'."\n";
		}
	}
?>
