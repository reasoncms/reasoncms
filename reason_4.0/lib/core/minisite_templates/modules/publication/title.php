<?php
	reason_include_once( 'minisite_templates/modules/page_title.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'publication/'.basename( __FILE__, '.php' ) ] = 'publicationTitleModule';
	
	class publicationTitleModule extends PageTitleModule
	{
		var $publication;
		var $cleanup_rules = array(
			'story_id' => array( 'function' => 'turn_into_int' ),
		);
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
			if( !empty($this->publication) && $this->publication->get_value('name') )
				return true;
			elseif( !empty( $this->parent->title ) )
				return true;
			else
				return false;
		}
		function run()
		{
			if( !empty($this->publication) && $this->publication->get_value('name') )
			{
				if(!empty($this->request['story_id']))
				{
					$link = construct_link(array(), array('textonly')); // preserve only textonly
					$pubname = '<a href="'.$link.'">'.$this->publication->get_value('name').'</a>';
				}
				else
				{
					$pubname = $this->publication->get_value('name');
				}
				echo '<h2 class="pageTitle"><span>'.$pubname.'</span></h2>';
			}
			elseif( !empty( $this->parent->title ) )
				parent::run();
		}
	}
?>
