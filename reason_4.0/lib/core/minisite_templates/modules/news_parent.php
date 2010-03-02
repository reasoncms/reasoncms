<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ParentNewsMinisiteModule';
	reason_include_once( 'minisite_templates/modules/news.php' );

	/**
	 * A minisite module that displays news from the parent site
	 *
	 *  @author dave hendler
	 *
	 *  the primary purpose of this module is to pull news from a parent site 
	 *  and make it look like it is a part of the child site.
	 *
	 *  most of this is just changing the references to this->parent->site_id
	 *  to  this->parent_site->id() which gets the parent's site id.
	 *
	 * @deprecated
	 */
        
	class ParentNewsMinisiteModule extends NewsMinisiteModule
	{

		function init( $args ) // {{{
		{
			// skip NewsMinisiteModule and go straight to the source
			DefaultMinisiteModule::init( $args );
			trigger_error('ParentNewsMinisiteModule is deprecated and will be removed from the Reason Core before RC1. Transition pages using this module to use publications instead - a migrator is available in /scripts/developer_tools/publication_migrator.php');
			
			// get parent site information
			$es = new entity_selector();
			$es->add_type( id_of( 'site' ) );
			$es->add_right_relationship( $this->parent->site_id, relationship_id_of( 'parent_site' ) );
			$tmp = $es->run_one();
			$this->parent_site = current( $tmp );

			$this->get_issues();
			if( !empty($this->issues) )
			{
				$this->get_issue_id();
				$this->_add_crumb( $this->current_issue->get_value( 'name' ) , '?issue_id=' . $this->current_issue->id() );
				//$this->parent->title = $this->current_issue->get_value( 'name' );
			}
			if( !empty( $this->request[ 'story_id' ] ) )
			{
				$e = new entity( $this->request[ 'story_id' ] );
				$this->_add_crumb( $e->get_value( 'name' ) , '');	
				//$this->parent->title = $e->get_value( 'name' );

				$es = new entity_selector();
				$es->description = 'Selecting images for news item';
				$es->add_type( id_of('image') );
				$es->add_right_relationship( $this->request[ 'story_id' ], relationship_id_of('news_to_image') );
				$es->add_rel_sort_field( $this->request['story_id'], relationship_id_of('news_to_image') );
				$es->set_order('rel_sort_order');
				$this->images = $es->run_one();
			}
			else
			{
				$es = new entity_selector( $this->parent_site->id() );
				$es->add_type( id_of( 'news_section_type' ) );
				$es->set_order( 'sortable.sort_order ASC' );
				$this->sections = $es->run_one();
			}
		} // }}}
		function show_feed_link()
		{
			$type = new entity(id_of('news'));
			if($type->get_value('feed_url_string'))
				echo '<div class="feedInfo"><a href="'.$this->parent_site->get_value('base_url').MINISITE_FEED_DIRECTORY_NAME.'/'.$type->get_value('feed_url_string').'" title="RSS 2.0 news feed for this site">xml</a></div>';
		}
		function show_owner( $e ) // {{{
		{
			$owner = $e->get_owner();			
			if ( $owner->get_value('id') != $this->parent_site->id() )
			{
				echo '<p class="smallText newsProvider">This story is provided by ';
				$base_url = $owner->get_value('base_url');
				if (!empty($base_url))
					echo '<a href="'. $base_url . '">'. $owner->get_value('name') . '</a>';
				else echo $owner->get_value('name');
				echo "</p>\n";
			}
		} // }}}
		function list_news_issue() // {{{
		{
			$this->show_issue_options();
			if(empty($this->sections))
			{
				$v = new $this->issue_news_viewer;
				$v->current_issue =& $this->current_issue;
				$v->num_per_page = 500000;
				$v->request = &$this->request;
				$v->init( $this->parent_site->id() , id_of( 'news' ) );
				$v->textonly = $this->parent->textonly;
				$v->do_display();
			}
			else
			{
				foreach($this->sections as $section)
				{
					$v = new $this->issue_news_viewer;
					$v->set_section( $section );
					$v->current_issue =& $this->current_issue;
					$v->num_per_page = 500000;
					$v->request = &$this->request;
					$v->init( $this->parent_site->id() , id_of( 'news' ) );
					$v->textonly = $this->parent->textonly;
					$count = $v->get_count();
					if(!empty($count))
					{
						echo '<h3>'.$section->get_value('name').'</h3>'."\n";
						$v->do_display();
					}
				}
			}
			
			if(empty($this->request['issue_id'])) // Only show issue links if first page (for search engines)
				$this->show_issue_links();
		} // }}}
		function list_news_no_issue() // {{{
		{
			if(empty($this->sections))
			{
				$v = new no_issue_news_viewer;
				$v->num_per_page = $this->num_per_page;
				$v->request = &$this->request;
				$v->init( $this->parent_site->id(), id_of( 'news' ) );;
				$v->textonly = $this->parent->textonly;
				$v->do_display();
			}
			else
			{
				foreach($this->sections as $section)
				{
					$v = new no_issue_news_viewer;
					$v->set_section( $section );
					$v->num_per_page = $this->num_per_page;
					$v->request = &$this->request;
					$v->init( $this->parent_site->id() , id_of( 'news' ) );;
					$v->textonly = $this->parent->textonly;
					$count = $v->get_count();
					if(!empty($count))
					{
						echo '<h3>'.$section->get_value('name').'</h3>'."\n";
						$v->do_display();
					}
				}
			}
		} // }}}

		function show_related_stories( $e ) // {{{
		{
			$rel = new entity_selector( $this->parent_site->id() );
			$rel->add_right_relationship( $this->request[ 'story_id' ] , relationship_id_of( 'news_to_news' ) );
			$rel->add_relation( 'status.status = "published"' );
			$related = $rel->run_one( id_of( 'news' ) );
			
			if (!empty($related))
			{
				$num_rel = count($related);
				if ($num_rel == 1) $word = "Story";
				else $word = "Stories";
				$storylinks = "";
				foreach ($related as $item)
				{
					$link = '';
					$rel_title = $item->get_value("release_title");
					if (empty($rel_title)) $rel_title = $item->get_value("name");
					$link = '?story_id=' . $item->get_value("id");
					if (!empty($this->parent->textonly))
						$link .= '&amp;textonly=1';
					$storylinks .= '<li class="relatedLi"><a href="' . $link . '">' . $rel_title . "</a></li>\n";
				}
				if (!empty($storylinks))
				{
					echo "\n<h4 class='relatedHead'>Related ".$word."</h4>\n";
					echo "<ul class='relatedList'>\n";
					echo $storylinks;
					echo "</ul>\n";
				}
			}
		} // }}}
		function get_issues() // {{{
		{
			if(empty($this->issues))
			{
				$es = new entity_selector( $this->parent_site->id() );
				$es->add_type( id_of( 'issue_type' ) );
				$es->add_relation( 'show_hide.show_hide = "show"' );
				$es->set_order( 'dated.datetime DESC' );
				$this->issues = $es->run_one();
			}
			return $this->issues;
		} // }}}
	}
?>
