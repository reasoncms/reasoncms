<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'publication/'.basename( __FILE__, '.php' ) ] = 'publicationDescriptionModule';
	
	/**
 	 * A minisite module that outputs the description of the publication attached to the
 	 * current page
 	 */
	class publicationDescriptionModule extends DefaultMinisiteModule
	{
		var $publication;
		var $cleanup_rules = array(
			'story_id' => array( 'function' => 'turn_into_int' ),
			'page' => array('function' => 'turn_into_int' ),
		);
		var $acceptable_params = array(
			'hide_on_item' => false,
			'hide_on_archive_pages' => false,
		);
		function init( $args = array() )
		{
			$show = true;
			if($this->params['hide_on_item'] && !empty($this->request['story_id']) )
				$show = false;
			elseif($this->params['hide_on_archive_pages'] && !empty($this->request['page']) && $this->request['page'] > 1)
				$show = false;
				
			if($show)
			{
				$es = new entity_selector( $this->site_id );
				$es->description = 'Selecting publications for this page';
				$es->add_type( id_of('publication_type') );
				$es->add_right_relationship( $this->page_id, relationship_id_of('page_to_publication') );
				$es->set_num( 1 );
				$publications = $es->run_one();
				if(!empty($publications))
				{
					$this->publication = current($publications);
				}
			}
		}
		function has_content()
		{
			if( !empty($this->publication) && $this->publication->get_value('description') )
				return true;
			else
				return false;
		}
		function run()
		{
			echo '<div id="blogDescription">'."\n";
			echo '<p>'.$this->publication->get_value('description').'</p>';
			echo '</div>'."\n";
		}
	}
?>
