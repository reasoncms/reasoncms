<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'publication/'.basename( __FILE__, '.php' ) ] = 'publicationDescriptionModule';
	
	class publicationDescriptionModule extends DefaultMinisiteModule
	{
		var $publication;
		function init( $args = array() )
		{
			$es = new entity_selector( $this->parent->site_id );
			$es->description = 'Selecting publications for this page';
			$es->add_type( id_of('publication_type') );
			$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_publication') );
			$es->set_num( 1 );
			$publications = $es->run_one();
			if(!empty($publications))
			{
				$this->publication = current($publications);
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
