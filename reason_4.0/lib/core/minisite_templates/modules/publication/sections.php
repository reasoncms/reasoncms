<?php
/**
 * List sections on a publication
 * @package reason
 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'publication/'.basename( __FILE__, '.php' ) ] = 'publicationSectionsModule';
	
	/**
	 * Module to list sections on a publication
	 * @todo add parameter to allow switch between two behaviors -- link to top issue always, or link to issue being browsed
	 */
	class publicationSectionsModule extends DefaultMinisiteModule
	{
		var $publication;
		var $sections;
		var $issue;
		var $cleanup_rules = array(
			'section_id' => array( 'function' => 'turn_into_int' ),
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
				if($this->publication->get_value('has_sections') == 'yes')
				{
					$es = new entity_selector( $this->site_id );
					$es->description = 'Selecting news sections for this publication';
					$es->add_type( id_of('news_section_type'));
					$es->add_left_relationship( $this->publication->id(), relationship_id_of('news_section_to_publication') );
					$es->set_order('sortable.sort_order ASC');
					$this->sections = $es->run_one();
				}
			}
			if(!empty($this->sections) && !empty($this->publication) && $this->publication->get_value('has_issues'))
			{
				$es = new entity_selector( $this->site_id );
				$es->description = 'Selecting issues for this publication';
				$es->add_type( id_of('issue_type') );
				$es->limit_tables(array('dated','show_hide'));
				$es->limit_fields('dated.datetime');
				$es->set_order('dated.datetime DESC');
				$es->add_relation('show_hide.show_hide = "show"');
				$es->add_left_relationship( $this->publication->id(), relationship_id_of('issue_to_publication') );
				$es->set_num(1);
				$issues = $es->run_one();
				if(!empty($issues))
				{
					$this->issue = current($issues);
				}
			}
		}
		function has_content()
		{
			if( !empty($this->sections) )
				return true;
			else
				return false;
		}
		function run()
		{
			echo '<div id="publicationSections">'."\n";
			echo '<ul>'."\n";
			$issuechunk = '';
			if(!empty($this->issue))
			{
				$issuechunk = '&amp;issue_id='.$this->issue->id();
			}
			foreach($this->sections as $section)
			{
				if(!empty($this->request['section_id']) && $section->id() == $this->request['section_id'])
					$class = 'selected';
				else
					$class = 'unselected';
				echo '<li class="'.$class.'"><a href="?section_id='.$section->id().$issuechunk.'">'.$section->get_value('name').'</a></li>'."\n";
			}
			echo '</ul>'."\n";
			echo '</div>'."\n";
		}
	}
?>
