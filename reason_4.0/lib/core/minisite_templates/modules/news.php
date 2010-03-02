<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
  	/**
 	 * Include parent class and register module with Reason
 	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NewsMinisiteModule';
	reason_include_once( 'minisite_templates/modules/default.php' );
	/**
	 * @deprecated
	 */
    class no_issue_news_viewer extends Viewer
	{
		var $section;
		var $show_author_with_summary = false;
		
		/**
		* @param entity news section
		*/
		function set_section($section) // {{{
		{
			$this->section = $section;
		} // }}}
		
		//overloads viewer function - changes entity selector and the values of Viewer variables num and num_pages.
		function alter_values() // {{{
		{
			$this->es->set_order( 'dated.datetime DESC' );
			$this->es->add_relation( 'status.status = "published"' );
			if(!empty($this->section))
				$this->es->add_left_relationship( $this->section->id(), relationship_id_of( 'news_to_news_section'));
			$this->es->set_sharing(array('owns','borrows'));
			$this->num = $this->es->get_one_count();
			$this->num_pages = ceil( $this->num / $this->num_per_page );
		} // }}}
		
		/**
		* @return the number of viewable news items.
		*/
		function get_count() // {{{
		{
			return $this->num;
		} // }}}
		
		//overloads viewer function
		function show_item( $item ) // {{{
		{              
			issue_news_viewer::show_item( $item );
		} // }}}
		
		//overloads viewer hook
		function show_paging() // {{{
		{       
			if( $this->num_pages > 1 )
			{
				for( $i=1; $i<=$this->num_pages; $i++ )
				{
					$xtra = '';
					if (!empty($this->textonly))
						$xtra = '&amp;textonly=1';
					if( $i == $this->page )
						echo '<strong>' . $i . '</strong> ';
					else
						echo '<a href="?page=' . $i . $xtra . '">'.$i.'</a> ';
				}
			}
		} // }}}
		
		//overloads viewer function
		function show_all_items() // {{{
		{
			foreach($this->values as $item )
			{
				$this->show_item( $item );
			}
		} // }}}
		
		/**
		* Finds and displays the teaser image for a given news item.
		* @param entity news item
		*/
		function show_item_pre( $item )
		{
			$es = new entity_selector( $this->site_id );
			$es->description = 'Finding teaser image for news item';
			$es->add_type( id_of('image') );
			$es->add_right_relationship( $item->id(), relationship_id_of('news_to_teaser_image') );
			$es->set_num(1);
			$result = $es->run_one();
			if (!empty($result))
			{
				echo '<div class="teaserImage">';
				show_image( reset($result), true,false,false );
				echo "</div>\n";
			}
		}
		
	}
	/**
	 * @deprecated
	 */
	class issue_news_viewer extends Viewer
	{
		var $section;
		var $show_author_with_summary = false;
		
		/**
		* @param entity news section
		*/
		function set_section($section) // {{{
		{
			$this->section = $section;
		} // }}}
		
		//overloads viewer function - changes entity selector
		function alter_values() // {{{
		{
			$this->es->add_left_relationship( $this->current_issue->id() , relationship_id_of( 'news_to_issue' ) ); 
			if(!empty($this->section))
				$this->es->add_left_relationship( $this->section->id(), relationship_id_of( 'news_to_news_section'));
			$this->es->set_order( 'dated.datetime DESC' );
			$this->es->add_relation( 'status.status = "published"' );
		} // }}}
		
		/**
		* @return the number of pertinent & viewable news items.
		*/
		function get_count() // {{{
		{
			return $this->es->get_one_count();
		} // }}}
		
		//overloads viewer function; this function is also used by no_issues_news_viewer
		function show_item( $item ) // {{{
		{        
			$content = $item->get_value( 'content' );
			$desc = strip_tags( $item->get_value( 'description' ), '<strong><b><em><i><a><span><ul><ol><li>' );
			echo "\n".'<p class="newsItem">'."\n";
			$this->show_item_pre( $item );
			
			echo '<div class="smallText newsItemDate">' . prettify_mysql_datetime( $item->get_value( 'datetime' ), "F jS, Y" ) . '</div>'."\n";
			echo '<div class="newsItemName">';
			if ( !empty( $content ) )
			{
				echo '<a href="?';
				if (!empty($this->current_issue))
					echo 'issue_id='.$this->current_issue->id().'&amp;';
				echo 'story_id=' . $item->id();
				if ( !empty( $this->request[ 'page' ] ) )
					echo '&amp;page=' . $this->request[ 'page' ];
				if (!empty($this->textonly))
					echo '&amp;textonly=1';
				echo '" class="newsItemLink">';
			}
			echo $item->get_value( 'release_title' );
			if ( !empty( $content ) )
				echo "</a>";
			echo "</div>\n";
			if ( !empty( $desc ) )
				echo '<div class="newsItemDesc">' . $desc . '</div>'."\n";
			if($this->show_author_with_summary && $item->get_value('author') )
				echo "<div class='author'>--" . $item->get_value('author') . "</div>\n";
			echo "</p>\n";
		} // }}}
		
		/**
		* Finds and displays the teaser image for a given news item.
		* @param entity news item
		*/
		function show_item_pre( $item )
		{
			$es = new entity_selector( $this->site_id );
			$es->description = 'Finding teaser image for news item';
			$es->add_type( id_of('image') );
			$es->add_right_relationship( $item->id(), relationship_id_of('news_to_teaser_image') );
			$es->set_num(1);
			$result = $es->run_one();
			if (!empty($result))
			{
				echo '<div class="teaserImage">';
				show_image( reset($result), true,false,false );
				echo "</div>\n";
			}
		}
		
		/* function show_all_items() // {{{
		{
			//this function is meant for overloading
			$row = 0;
			reset( $this->values );
			while( list( $id, $item ) = each( $this->values ) )
			{
				if( ($row % $this->rows_per_sorting) == 0 )
					$this->show_sorting();
				$this->show_item( $item );
				$row++;
			}
		} // }}} */
		function display() // {{{
		{
			echo "\n".'<div id="news">'."\n";
			$this->show_filters();
			$this->show_all_items();
			$this->show_paging();
			echo '</div>'."\n";
		} // }}}
		
		function show_all_items() // {{{
		{
			foreach($this->values as $item )
			{
				$this->show_item( $item );
			}
		} // }}}
	}
	
	/**
	 * @deprecated
	 */
	class related_news_viewer extends Viewer
	{
		function alter_values() // {{{
		{
			$this->es->add_left_relationship( $this->request[ 'story_id' ] , relationship_id_of( 'news_to_news' ));
			$this->es->set_order( 'dated.datetime DESC' );
			$this->es->add_relation( 'status.status = "published"' );
		} // }}}
		
		function show_all_items() // {{{
		{
			foreach($this->values as $item )
			{
				$this->show_item( $item );
			}
		} // }}}
		
	}
	
	/**
	 * A minisite module that lists all news items on the current site
	 *
	 * deprecated since Reason 4 Beta 4 and not fully php5 friendly - use the publications module to display news
	 *
	 * @deprecated
	 */
	class NewsMinisiteModule extends DefaultMinisiteModule
	{
		var $num_per_page = 12;
		var $sections;
		var $issue_uses_name = true;
		var $issue_uses_datetime = true;
		var $issue_links = array();
		var $issue_news_viewer = 'issue_news_viewer';
		var $cleanup_rules = array(
			'issue_id' => array('function' => 'turn_into_int'),
			'story_id' => array('function' => 'turn_into_int'),
			'page' => array('function' => 'turn_into_int')
		);
		var $limit_to_shown_issues = true;
		var $is_issued = false;
		var $feed_url;
		var $add_breadcrumbs = true;
		var $queried_for_issues = false;
		var $issues = array();

		function init( $args ) // {{{
		{
			parent::init( $args );
			trigger_error('NewsMinisiteModule is deprecated and will be removed from the Reason Core before RC1. Transition pages using this module to use publications instead - a migrator is available in /scripts/developer_tools/publication_migrator.php');
			$this->get_issues();
			if( $this->is_issued && $this->has_issues() )
			{
				$this->get_issue_id();
				if($this->add_breadcrumbs)
				{
					$this->_add_crumb( $this->current_issue->get_value( 'name' ) , '?issue_id=' . $this->current_issue->id() );
				}
				//$this->parent->title = $this->current_issue->get_value( 'name' );
			}
			if( !empty( $this->request[ 'story_id' ] ) )
			{
				$e = new entity( $this->request[ 'story_id' ] );
				$this->validate_story_entity($e);
				$this->_add_crumb( $e->get_value( 'release_title' ) , '');	
				//$this->parent->title = $e->get_value( 'name' );

				$es = new entity_selector();
				$es->description = 'Selecting images for news item';
				$es->add_type( id_of('image') );
				$es->set_env( 'site' , $this->site_id );
				$es->add_right_relationship( $this->request[ 'story_id' ], relationship_id_of('news_to_image') );
				$es->add_rel_sort_field( $this->request['story_id'], relationship_id_of('news_to_image') );
				$es->set_order('rel_sort_order');
				$this->images = $es->run_one();
			}
			else
			{
				$es = new entity_selector( $this->parent->site_id );
				$es->add_type( id_of( 'news_section_type' ) );
				$es->set_order( 'sortable.sort_order ASC' );
				$this->sections = $es->run_one();
			}
			
			$this->add_feed_link_to_head();
		} // }}}

		function validate_story_entity(&$e)
		{
			if ($e->get_values())
			{
				$news_post_type = id_of('news');
				$e_type = $e->get_value('type');
				if ($news_post_type == $e_type && $e->get_value('status') != 'pending' ) return true;
			}
			header('Location: ' . ERROR_404_PAGE);
			exit;
		}

		function has_issues() // {{{
		{
			$this->get_issues();
			if(empty($this->issues))
				return false;
			else
				return true;
			/*static $first = true;
			static $has_issues;
			if( $first )
			{
				$es = new entity_selector();
				$es->add_type( id_of( 'type' ) );
				$es->add_right_relationship( $this->parent->site_id , relationship_id_of( 'site_to_type' ) );
				$es->add_relation( 'entity.id = ' . id_of( 'issue_type' ) );
				//$es->add_relation( 'show_hide.show_hide = "show"' );

				if( $es->run_one() )
					$has_issues = true;
				else
					$has_issues = false;
				$first = false;
			}
			return $has_issues;*/
			
		} // }}}
		
		function add_feed_link_to_head()
		{
			if( $this->get_feed_url() )
			{
				$this->parent->add_head_item( 'link', array('rel'=>'alternate','type'=>'application/rss+xml','title'=>'RSS 2.0 news feed for this site','href'=>$this->get_feed_url() ) );
			}
		}
		function run() // {{{
		{
			if( !empty( $this->request[ 'story_id' ] ) )
				$this->show_story();
			else
				$this->list_news();
		} // }}}

		function list_news() // {{{
		{
			if( $this->is_issued && empty($this->issues) )
				echo 'The first issue has not been published yet.';
			elseif( !empty($this->issues) )
				$this->list_news_issue();
			else
				$this->list_news_no_issue();
		
			$this->show_feed_link();
                
		} // }}}
		function list_news_issue() // {{{
		{
			$this->show_issue_options();
			if(empty($this->sections))
			{
				$v = new $this->issue_news_viewer;
				$v->current_issue =& $this->current_issue;
				$v->num_per_page = 500000;
				$v->request = $this->request;
				$v->init( $this->parent->site_id , id_of( 'news' ) );
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
					$v->request = $this->request;
					$v->init( $this->parent->site_id , id_of( 'news' ) );
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
		function show_issue_links() //{{{
		{
			echo '<ul class="hide">';
			foreach($this->issue_links as $id=>$info)
			{
				echo '<li><a href="?issue_id='.$id.'">'.$info.'</a></li>';
			}
			echo '</ul>'."\n";
		} // }}}
		function show_feed_link() //{{{
		{
			if( $this->get_feed_url() )
			{
				reason_include_once( 'function_libraries/feed_utils.php');
				echo make_feed_link( $this->get_feed_url(), $title = 'RSS 2.0 news feed for this site');
			}
		} // }}}
		function get_feed_url()
		{
			if(empty($this->feed_url))
			{
				$type = new entity(id_of('news'));
				if($type->get_value('feed_url_string') == 'posts')
				{
					$this->feed_url = $this->parent->site_info->get_value('base_url').MINISITE_FEED_DIRECTORY_NAME.'/news';
				}
				elseif($type->get_value('feed_url_string'))
				{
					$this->feed_url = $this->parent->site_info->get_value('base_url').MINISITE_FEED_DIRECTORY_NAME.'/'.$type->get_value('feed_url_string');
				}
			}
			if(!empty($this->feed_url))
			{
				return $this->feed_url;
			}
			else
			{
				return false;
			}
		}
		function list_news_no_issue() // {{{
		{
			if(empty($this->sections))
			{
				$v = new no_issue_news_viewer;
				$v->num_per_page = $this->num_per_page;
				$v->request = $this->request;
				$v->init( $this->parent->site_id , id_of( 'news' ) );;
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
					$v->request = $this->request;
					$v->init( $this->parent->site_id , id_of( 'news' ) );;
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

		function show_story() // {{{
		{
			$e = new entity( $this->request[ 'story_id' ] );
			echo "<h3 class='newsTitle'>".strip_tags( $e->get_value( 'release_title' ) )."</h3>\n";
			if( $e->get_value( 'datetime' ) )
				echo '<p class="smallText newsDate">'.prettify_mysql_datetime( $e->get_value( 'datetime' ), "F jS, Y" )."</p>\n";
			if( $e->get_value( 'author' ) )
				echo "<p class='smallText newsAuthor'>By ".$e->get_value( 'author' )."</p>\n";
			$this->show_owner( $e );

			$die = isset( $this->die_without_thumbmail ) ? $this->die_without_thumbnail : false;
			$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
			$desc = isset( $this->description ) ? $this->description : true;
			$text = isset( $this->additional_text ) ? $this->additional_text : "";
			
			if (!empty($this->images))
			{
				echo '<div class="NewsImages">';
				if (!empty($this->parent->textonly))
					echo '<h3>Images</h3>'."\n";
				foreach( $this->images AS $id => $image )
				{
					$show_text = $text;
					if( !empty( $this->show_size ) )
						$show_text .= '<br />('.$image->get_value( 'size' ).' kb)';
					//echo "<div class=\"imageChunk\">";
					show_image( $image, $die, $popup, $desc, $show_text, $this->parent->textonly );
					//echo "</div>\n";
				}
				echo "</div>";
			}

			$content = $e->get_value( 'content' );
			if ( !empty ( $content ) )
				echo $content;
			else echo $e->get_value( 'description' );
			if ( $e->get_value( 'keywords' ) )
				echo '<span class="hide" id="newsItemKeywords">'.$e->get_value('keywords').'</span>'."\n";
			$this->show_related_stories( $e );
			echo "<p class='newsBack'>";
			$this->show_back_link();
			echo "</p>\n";
		} // }}}
		function show_owner( $e ) // {{{
		{
			$owner = $e->get_owner();			
			if ( $owner->get_value('id') != $this->parent->site_id )
			{
				echo '<p class="smallText newsProvider">This story is provided by ';
				$base_url = $owner->get_value('base_url');
				if (!empty($base_url))
					echo '<a href="'. $base_url . '">'. $owner->get_value('name') . '</a>';
				else echo $owner->get_value('name');
				echo "</p>\n";
			}
		} // }}}
		function show_issue( &$issue ) // {{{
		{
			echo '<a href="?issue_id=' . $issue->id() . '"><strong>'. $issue->get_value( 'name' ) . '</strong> (' .
				 prettify_mysql_datetime( $issue->get_value( 'datetime' ) ) . ')</a><br />';
		} // }}}
		function show_related_stories( $e ) // {{{
		{
			// -- news_to_news does not exist and I think this is unused ... nwhite
			//$rel = new entity_selector( $this->parent->site_id );
			//$rel->add_right_relationship( $this->request[ 'story_id' ] , relationship_id_of( 'news_to_news' ) );
			//$rel->add_relation( 'status.status = "published"' );
			//$related = $rel->run_one( id_of( 'news' ) );
			$related = '';

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
		function show_issue_options() // {{{
		{
			$issues = $this->get_issues();
			$this->show_jump_javascript();
			?>
			<form id="issueForm">
			Issue:
			<select id="issueSelector" onChange="MM_jumpMenu('parent',this,0)" class="siteMenu">
			<?php
			foreach( $issues AS $issue )
			{
				echo '<option value="?issue_id='.$issue->id();
				if( !empty( $this->parent->textonly ) )
					echo '&amp;textonly=1';
				echo '"';
				if( $issue->id() == $this->request[ 'issue_id' ] )
					echo ' selected="selected"';
				$info = '';
				if(!empty($this->issue_uses_name))
					$info .= $issue->get_value( 'name' );
				if(!empty($this->issue_uses_datetime))
					$info .= ' - ' . prettify_mysql_datetime( $issue->get_value( 'datetime' ) );
				echo '>' . $info . '</option>' . "\n";
				$this->issue_links[$issue->id()] = $info;
			}
			?>
			</select>
			<noscript>
				<input type="submit" name="Submit" value="Submit">
			</noscript>
		</form>
			<?php
		} // }}}
		/* function get_issues() // {{{
		{
			if(empty($this->issues))
			{
				$es = new entity_selector( $this->parent->site_id );
				$es->add_type( id_of( 'issue_type' ) );
				$es->add_relation( 'show_hide.show_hide = "show"' );
				$es->set_order( 'dated.datetime DESC' );
				$this->issues = $es->run_one();
			}
			return $this->issues;
		} // }}} */
		function get_issues() // {{{
		{
			if(!$this->queried_for_issues)
			{
				$es = new entity_selector( $this->parent->site_id );
				$es->add_type( id_of( 'issue_type' ) );
				$total_issue_count = $es->get_one_count();
				if($total_issue_count > 0)
				{
					$this->is_issued = true;
					if($this->limit_to_shown_issues)
					{
						$es->add_relation( 'show_hide.show_hide = "show"' );
					}
					$es->set_order( 'dated.datetime DESC' );
					$this->issues = $es->run_one();
				}
				$this->queried_for_issues = true;
			}
			return $this->issues;
		} // }}}
		function show_jump_javascript() // {{{
		{
		?>
			<script language="JavaScript" type="text/JavaScript">
			<!--
			function MM_jumpMenu(targ,selObj,restore){ //v3.0
			  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
			  if (restore) selObj.selectedIndex=0;
			}
			//-->
			</script>
		<?php
		} // }}}
		function show_back_link() // {{{
		{
			if( !empty( $this->request[ 'issue_id' ] ) )
				$issue = new entity( $this->request[ 'issue_id' ] );
			echo '<a href="?';
			if( !empty( $this->request[ 'issue_id' ] ) )
				echo 'issue_id=' . $this->request[ 'issue_id' ];
			if( !empty( $this->request[ 'page' ] ) && !empty( $this->request[ 'issue_id' ] ) )
				echo '&amp;';
			if( !empty( $this->request[ 'page' ] ) )
				echo 'page=' . $this->request[ 'page' ];
			if( !empty( $this->parent->textonly ) )
				echo '&amp;textonly=1';
			if( !empty( $this->request[ 'issue_id' ] ) )
				echo '">Back to Issue: '.$issue->get_value( 'name' ).'</a>';
			else
				echo '">Back to '.$this->parent->title.'</a>';
				
		} // }}}
		function get_issue_id() // {{{
		{
			// The old code, archived at
			// </usr/local/webapps/www-dev/fillmorn/reason_backup/news_2003-12-31.php>,
			// doesn't set any current issue if an incorrect issue_id
			// is passed in request. Fatal errors ensue, when other
			// methods try to use current_issue.

			// 1. First try to set current_issue to an issue specified in the request
			if ( !empty( $this->request['issue_id'] ) )
				foreach( $this->issues AS $issue )
					if( $issue->id() == $this->request[ 'issue_id' ] )
						$this->current_issue = $issue;

			// 2. If that doesn't work, set current_issue to the first
			// one in the array, i.e. (at time of writing) the one
			// with the latest datetime.
			if ( empty($this->current_issue) )
			{
				reset( $this->issues );
				$iss = current( $this->issues );
				$this->request[ 'issue_id' ] = $iss->id();
				$this->current_issue = $iss;
			}				
		} // }}}
		
		function last_modified() // {{{
		{
			if(!empty($this->request[ 'story_id' ]))
			{
				$e = new entity($this->request[ 'story_id' ]);
				return $e->get_value('last_modified');
			}
			elseif(!empty($this->current_issue))
			{
				return $this->current_issue->get_value('last_modified');
			}
		}
	}
?>
