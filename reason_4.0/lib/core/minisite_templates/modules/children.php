<?php

/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include base class & register module with Reason
	 */

	reason_include_once( 'minisite_templates/modules/default.php' );
    reason_include_once( 'classes/sized_image.php' );
    reason_include_once( 'function_libraries/url_utils.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ChildrenModule';
	
	
	/**
	 * A minisite module whose output is all the child pages of the current page (or page specified in parent_unique_name param), in sort order.
	 *
	 * Various parameters are available for variant behavior.
	 */
	class ChildrenModule extends DefaultMinisiteModule 
	{
		var $es;
		var $acceptable_params = array(
										'description_part_of_link' => false,
										'provide_az_links' => false,
										'provide_images' => false,
										'randomize_images' => false,
										'show_only_pages_in_nav' => false,
										'show_external_links' => true,
										'exclude' => array(),
										'limit_to' => array(),
										'thumbnail_width' => 0,
										'thumbnail_height' => 0,
										'thumbnail_crop' => '',
										'parent_unique_name' => '',
										'force_full_page_title' => false,
										'blurbs_count' => 0,
										'html5' => false,
										'chunks' => 1,
										'heading' => '',
										'footer' => '',
									);
		var $offspring = array();
		var $az = array();
		
		function init( $args = array() ) // {{{
		{
			parent::init( $args );

			$this->es = new entity_selector();
			$this->es->description = 'Selecting children of the page';

			// find all the children of the parent page
			$this->es->add_type( id_of('minisite_page') );
			$this->es->add_left_relationship( $this->get_parent_page_id(), relationship_id_of( 'minisite_page_parent' ) );
			if($this->params['show_only_pages_in_nav'])
			{
				$this->es->add_relation('nav_display = "Yes"');
			}
			if(isset($this->params['show_external_links']) && !$this->params['show_external_links'])
			{
				$this->es->add_relation('(url = "" OR url IS NULL)');
			}
			if(!empty($this->params['exclude']))
			{
				$this->es->add_relation('unique_name NOT IN ('.$this->_param_to_sql_set($this->params['exclude']).')');
			}
			if(!empty($this->params['limit_to']))
			{
				$this->es->add_relation('unique_name IN ('.$this->_param_to_sql_set($this->params['limit_to']).')');
			}
			
			$this->es->set_order('sortable.sort_order ASC');
			$this->offspring = $this->es->run_one();
			
			if(isset($this->offspring[$this->get_parent_page_id()]))
			{
				unset($this->offspring[$this->get_parent_page_id()]);
			}
			
			if(!empty($this->params['provide_az_links']))
			{
				foreach($this->offspring as $child)
				{
					$page_name = $this->get_page_name($child);
					$letter = carl_strtoupper(substr($page_name,0,1), 'UTF-8');
					if(!in_array($letter, $this->az))
					{
						$this->az[$child->id()] = $letter;
					}
				}
			}
		}
		
		/**
		 * This is typically the page the module is running on unless parent_unique_name is set.
		 *
		 * @return object entity parent page
		 */
		function get_parent_page()
		{
			if (!isset($this->_parent_page))
			{
				if (!empty($this->params['parent_unique_name']))
				{
					if (reason_unique_name_exists($this->params['parent_unique_name']))
					{
						$page = new entity(id_of($this->params['parent_unique_name']));
						if (reason_is_entity($page, 'minisite_page')) $this->_parent_page = $page;
						else trigger_error('The unique name specified in parent_unique_name ('.$this->params['parent_unique_name'].') was ignored - it needs to refer to a minisite page entity.');
					}
					else trigger_error('The unique name specified in parent_unique_name ('.$this->params['parent_unique_name'].') was ignored - it does not exist.');
				}
				if (!isset($this->_parent_page)) $this->_parent_page = new entity($this->page_id);
			}
			return $this->_parent_page;
		}
		
		/**
		 * We determine whether the parent_page is on the current site using the page tree if available or a direct query.
		 *
		 * @return boolean
		 */
		function parent_page_is_on_current_site()
		{
			if (!isset($this->_parent_page_is_on_current_site))
			{
				$page = $this->get_parent_page();
				if ( $pages = $this->get_page_nav() )
				{
					$this->_parent_page_is_on_current_site = isset($pages->values[$page->id()]);
				}
				else
				{
					$this->_parent_page_is_on_current_site = (get_owner_site_id($page->id()) == $this->site_id);
				}
			}
			return $this->_parent_page_is_on_current_site;
		}
		
		/**
		 * Returns the id of the parent page.
		 *
		 * @return int id of the parent page
		 */
		function get_parent_page_id()
		{
			$page = $this->get_parent_page();
			return $page->id();
		}
		
		function _param_to_sql_set($param)
		{
			if(is_array($param))
			{
				array_walk($param, 'db_prep_walk');
				return implode(',',$param);
			}
			else
			{
				return '"'.reason_sql_string_escape($param).'"';
			}
		}
		
		function has_content()
		{
			if( empty($this->offspring) )
			{
				return false;
			}
			else
				return true;
		}
		
		function run()
		{
			if(!empty($this->params['heading']))
			{
				echo '<div class="heading">'.$this->params['heading'].'</div>'."\n";
			}
			/* If the page has no entries, say so */
			if( empty($this->offspring ) )
			{
				echo 'This page has no children<br />';	
			}
			/* otherwise, list them */
			else
			{
				if($this->params['provide_az_links'])
				{
					echo '<div class="childrenAZ">';
					foreach($this->az as $key=>$letter)
					{
						echo '<a href="#child_'.$letter.'">'.$letter.'</a> ';
					}
					echo '</div>';
				}
				$class = 'childrenList';
				if($this->params['provide_images'])
					$class .= ' childrenListWithImages';
				$counter = 1;
				$even_odd = 'odd';
				
				$offspring = $this->offspring;
				
				if($this->params['html5'])
				{
					static $counter = 0;
					$counter++;
					echo '<nav class="children" role="navigation" id="childrenModule'.$counter.'">'."\n";
				}
				if($this->params['chunks'] > 1)
				{
					$num = count($offspring);
					$num_per_chunk = ceil($num / $this->params['chunks']);
					$chunks = array();
					for($i = 0; $i < $this->params['chunks']; $i++)
					{
						$offset = $i * $num_per_chunk;
						$chunks[$i] = array_slice($offspring, $offset, $num_per_chunk, true);
					}
					foreach($chunks as $chunk_key => $chunk)
					{
						
						echo '<ul class="'.$class.' chunk chunk'. ($chunk_key + 1) .'">'."\n";
						foreach( $chunk AS $child )
						{
							$this->show_child_page($child,$counter,$even_odd);
							$counter++;
							$even_odd = ($even_odd == 'even') ? 'odd' : 'even';
						}
						echo "</ul>\n";
					}
				}
				else
				{
					echo '<ul class="'.$class.'">'."\n";
					foreach( $this->offspring AS $child )
					{
						$this->show_child_page($child,$counter,$even_odd);
						$counter++;
						$even_odd = ($even_odd == 'even') ? 'odd' : 'even';
					}
					echo "</ul>\n";
				}
				if($this->params['html5'])
					echo '</nav>'."\n";
			if(!empty($this->params['footer']))
			{
				echo '<div class="footer">'.$this->params['footer'].'</div>'."\n";
			}
			}
		}
		
		function show_child_page($child,$counter,$even_odd)
		{
			/* If the page has a link name, use that; otherwise, use its name */
			$page_name = $this->get_page_name($child);
			$title_attr = '';
			if( $page_name != $child->get_value('name') )
			{
				$title_attr = ' title="'.reason_htmlspecialchars(strip_tags($child->get_value('name')),ENT_QUOTES).'"';
			}
			$page_name = strip_tags($page_name,'<span><strong><em>');
			$link = $this->get_page_link($child);
			$classes = array('number'.$counter, $even_odd);
			$uname = '';
			if($child->get_value( 'unique_name' ))
			{
				$classes[] = 'uname-'.reason_htmlspecialchars($child->get_value( 'unique_name' ));
			}
			if($child->get_value('url'))
			{
				$classes[] = 'jump';
			}
				
			echo '<li class="'.implode(' ',$classes).'">';
			
			if($this->params['provide_az_links'] && array_key_exists($child->id(),$this->az))
			{
				echo '<a name="child_'.$this->az[$child->id()].'"></a>';
			}
			$image_markup = '';
			if($this->params['provide_images'])
			{
				$image = $this->get_page_image($child->id());
				
				if(!empty($image))
				{
					if($this->params['thumbnail_width'] != 0 or $this->params['thumbnail_height'] != 0)
					{
						$rsi = new reasonSizedImage();
						if(!empty($rsi))
						{
							$rsi->set_id($image->id());
							if($this->params['thumbnail_width'] != 0)
							{
								$rsi->set_width($this->params['thumbnail_width']);
							}
							if($this->params['thumbnail_height'] != 0)
							{
								$rsi->set_height($this->params['thumbnail_height']);
							}
							if($this->params['thumbnail_crop'] != '')
							{
								$rsi->set_crop_style($this->params['thumbnail_crop']);
							}
							if(!$this->params['html5'])
								$image_markup = get_show_image_html($rsi, true, false, false, '' , '', false, $link);
							else
								$image_markup = get_show_image_html($rsi, true, false, false, '' , '', false );
						}
					}
					else
					{
						if(!$this->params['html5'])
							$image_markup = get_show_image_html( $image->id(), true, false, false, '' , '', false, $link );
						else
							$image_markup = get_show_image_html( $image->id(), true, false, false, '' , '', false );
					}
				}
				if(!$this->params['html5'])
				{
					echo $image_markup;
				}
			}
			if($this->params['description_part_of_link'])
			{
				$element = $this->params['html5'] ? 'h4' : 'strong';
				echo '<a href="'.$link.'"'.$title_attr.'>';
				if($this->params['html5'])
					echo $image_markup;
				echo '<'.$element.'>'.$page_name.'</'.$element.'>';
				if(!$this->params['html5'])
					echo '<br />';
				if ( $child->get_value( 'description' ))
				{
					$element = $this->params['html5'] ? 'div' : 'span';
					echo "\n".'<'.$element.' class="childDesc">'.$child->get_value( 'description' ).'</'.$element.'>';
				}
				echo '</a>';
			}
			else
			{
				if($this->params['html5'])
					echo $image_markup;
				echo '<h4><a href="'.$link.'"'.$title_attr.'>'.$page_name.'</a></h4>';
				if ( $child->get_value( 'description' ))
				{
					echo "\n".'<div class="childDesc">'.$child->get_value( 'description' ).'</div>';
				}
			}
			if(!empty($this->params['blurbs_count']))
			{
				if($blurbs = $this->get_blurbs_for_page($child, $this->params['blurbs_count']))
				{
					echo '<div class="childBlurbs">';
					foreach($blurbs as $blurb)
					{
						echo '<div class="childBlurb">';
						echo demote_headings($blurb->get_value('content'), 2);
						echo '</div>';
					}
					echo '</div>';
				}
			}
			echo '</li>'."\n";
		}
		
		function get_page_name($page)
		{
			return ($page->get_value( 'link_name' ) && empty($this->params['force_full_page_titles'])) ? $page->get_value( 'link_name' ) : $page->get_value('name');
		}
		
		function get_page_image($page_id)
		{
			$es = new entity_selector();
			$es->set_env( 'site' , $this->site_id );
			$es->add_type(id_of('image'));
			$es->add_right_relationship($page_id, relationship_id_of('minisite_page_to_image'));
			$es->set_num(1);
			$es->limit_tables();
			$es->limit_fields();
			if($this->params['randomize_images']) $es->set_order('rand()');
			else $es->set_order('relationship.rel_sort_order ASC');
			$images = $es->run_one();
			if(!empty($images))
			{
				return current($images);
			}
			return false;
		}
		
		/**
		 * Get the full page link for a page. We fork our linking logic based on whether we have specified a parent_unique_name or not.
		 *
		 * - If no, we get the relative URL just by looking at the url_fragment.
		 * - If yes, we call get_page_link_other_parent($page)
		 *
		 * @return string href attribute
		 */
		function get_page_link($page)
		{
			/* Check for a url (that is, the page is an external link); otherwise, use its relative address */
			if( $page->get_value( 'url' ) )
			{
				$link = $page->get_value( 'url' );
			}
			else
			{
				if ($this->page_id == $this->get_parent_page_id()) // current page is the parent - use a relative link for speed.
				{
					$link = $page->get_value( 'url_fragment' ).'/';
				}
				else // we use a link relative to the site or absolute to another page
				{
					$link = $this->get_page_link_other_parent($page);
				}
			}
			return $link;
		}
		
		/**
		 * A function to get the page link when the parent_unique_name param has been specified.
		 *
		 * - if the page is on the same site we use the page tree directly.
		 * - If not, we just use reason_get_page_url() since it is faster than building the whole tree.
		 *
		 * @return string href attribute
		 */
		function get_page_link_other_parent($page)
		{
			if ($this->parent_page_is_on_current_site() && ($pages = $this->get_page_nav())) // we can use the page tree
			{
				return $pages->get_url_from_base($page->id()); // relative to our site
			}
			else
			{
				return reason_get_page_url($page->id()); // absolute to some other site or page tree is not available
			}
		}
		
		function get_blurbs_for_page($page, $count)
		{
			$cache = array();
			if(!isset($cache[$page->id()]))
			{
				$es = new entity_selector();
				$es->add_type(id_of('text_blurb'));
				$es->add_right_relationship( $page->id(), relationship_id_of('minisite_page_to_text_blurb') );
				$es->add_rel_sort_field( $page->id(), relationship_id_of('minisite_page_to_text_blurb'), 'rel_sort_order');
				$es->set_order( 'rel_sort_order ASC' );
				$es->set_num( (int) $count );
				$cache[$page->id()] = $es->run_one();
			}
			return $cache[$page->id()];
		}
		
		function last_modified()
		{
			if( $this->has_content() )
			{
				foreach ($this->offspring as $entity)
				{
					$last_modified = $entity->get_value( 'last_modified' );
					$max = (!isset($max) || $last_modified > $max) ? $last_modified : $max;
				}
				return $max;
			}
			else return false;
		}
		
		function get_documentation()
		{
			return '<p>Displays links to the current page\'s children. Each link includes the name of the page, along with that page\'s description</p>';
		}
	}
?>