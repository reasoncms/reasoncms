<?php
/**
 * Default Minisite Navigation
 * @package reason
 * @subpackage minisite_navigation
 */
 
 	/**
	 * Include the Reason Header and the Tree Lister (which this extends)
	 */
	include_once( 'reason_header.php' );
	reason_include_once( '/content_listers/tree.php3' );
	reason_include_once( 'minisite_templates/modules/default.php' );

	/**
	 * Default Minisite Navigation Class
	 *
	 * Class used for building and displaying minisite navigation
	 */
	class MinisiteNavigation extends tree_viewer
	{
		var $nice_urls = array();
	
		// Zero shows the root; 1 shows top-level; etc.
		var $start_depth = 0;
		var $end_depth = 99;
		var $display_parent_of_open_branch = false;
		var $link_to_current_page = false;
		
		function show_all_items_given_depths($start = 0,$end = 99)
		{
			$prev_start_depth = $this->start_depth;
			$prev_end_depth = $this->end_depth;
			
			$this->start_depth = $start;
			$this->end_depth = $end;
			
			$this->show_all_items();
			
			$this->start_depth = $prev_start_depth;
			$this->end_depth = $prev_end_depth;
			
		}
		function make_tree( &$item , &$root , $depth, $counter = 0 )
		{
			$display_item = false;
			if($depth >= $this->end_depth)
			{
				$children = array();
			}
			elseif($this->should_show_children($item))
			{
				$children = $this->children( $item );
			}
			else
			{
				$children = array();
			}
			if ( $depth >= $this->start_depth )
			{
				$display_item = true;
			}
			elseif( $this->display_parent_of_open_branch && $depth == $this->start_depth-1 && $this->is_open( $item ) && !empty( $children ) )
			{
				$display_item = true;
			}
			if( $display_item )
			{
				$open = $this->is_open( $item );
				$class = $this->get_item_class($item, $open, $depth, $counter);
				$item_display = $this->show_item( $this->values[ $item  ], $depth, false );
				if (!empty($children) && preg_match("/^show_children/", $this->values[ $item ]->get_value( 'custom_page' )) && $depth == 1)
				{
					$class = 'accordion ' . $class;
				}
				
				// enter a section heading in navigation if description begins with 'Section:'
				// TODO: remove section header logic when section headers are no longer used
				if (preg_match("/(^Section:\s+)(.*?)$/", $this->values[ $item ]->get_value( 'description' ), $m))
				{					
					echo '<li class="navListItem heading">'."\n";
					echo $m[2]."\n";
					echo '</li>'."\n";				
				}
				
				echo $this->prepend_icon($this->values[$item], $class);
				echo $item_display;
				if(( $open AND !empty( $children ))
					|| (!empty($children) && preg_match("/^show_children/", $this->values[ $item ]->get_value( 'custom_page' )) && $depth == 1))
				{
					$shown_children = array();
					$i = $counter;
					foreach($children as $child_id )
					{
						if( $this->values[ $child_id ]->get_value( 'nav_display' ) == 'Yes' )
						{
							$shown_children[$i] = $child_id;
							$i++;
						}
					}
					if(!empty($shown_children))
					{
						echo '<ul class="navList">';
						foreach($shown_children as $child_counter=>$child_id)
						{
							$this->make_tree( $child_id , $root, $depth +1,$child_counter);
						}
						echo '</ul>';
					}
				}
				echo '</li>';
			}
			else
			{
				if( $this->is_open( $item ) AND !empty( $children ) )
				{
					$i = 0;
					foreach($children as $child_id )
					{
						$c = $this->values[ $child_id ];
						if( $c->get_value( 'nav_display' ) == 'Yes' )
						{
							$this->make_tree( $child_id , $root, $depth +1, ++$i);
						}
					}
				}
			}
		}
		
		function get_item_class($id, $open, $depth = 0, $counter = 0)
		{
			if($open)
			{
				$class = 'open';
				if($this->cur_page_id == $id)
					$class .= ' current';
				elseif($this->values[ $this->cur_page_id ]->get_value( 'parent_id' ) == $id AND
						$this->values[ $this->cur_page_id ]->get_value( 'nav_display' ) == 'No')
					$class .= ' pseudoCurrent';
			}
			else
				$class = 'closed';
			if($counter)
				$class .= ' item'.$counter;
			if(isset($this->values[$id]) && $this->values[$id]->get_value( 'url' ))
				$class .= ' jump';
			if($this->values[ $id ]->get_value( 'unique_name'))
				$class .= ' uname-'.htmlspecialchars($this->values[ $id ]->get_value( 'unique_name'), ENT_QUOTES);
			return $class;
		}
		
		function show_all_items()
		{
			$root = $this->root_node();
			ob_start();
			$this->make_tree( $root , $root , 0);
			$tree = ob_get_contents();
			ob_end_clean();
			if(!empty($tree))
			{
				echo '<ul class="navListTop">';
				echo $tree;
				echo '</ul>'."\n";
			}
		}
		function main_nav_has_content()
		{
			return true;
		}
		function top_nav_has_content()
		{
			return false;
		}
		function show_top_nav()
		{
			// to be overloaded
			// sample code:
			// $this->show_all_items_given_depths(0,1);
		}
		function should_show_children($id)
		{
			return true;
		}
		/**
		 * Forces the current page to be a link rather than unlinked text
		 *
		 * Use this method if the current page is in a mode which the user might want to exit
		 * @return void
		 */
		function make_current_page_a_link()
		{
			$this->link_to_current_page = true;
		}
		function should_link_to_current_page()
		{
			return $this->link_to_current_page;
		}
		function get_home_page_link_text()
		{
			$home_page = $this->values[ $this->root_node() ];
			if($home_page->get_value('link_name'))
			{
				return $home_page->get_value('link_name');
			}
			else
			{
				return $this->site_info->get_value('name').' Home';
			}
		}
		function show_item( &$item , $depth, $options = false)
		{
			$class_attr = '';
			if( $item->id() == $this->root_node() )
			{
				$page_name = '<span>'.$this->get_home_page_link_text().'</span>';
				$class_attr = ' class="home"';
			}
			else
			{
				$page_name = $item->get_value( 'link_name' ) ? $item->get_value( 'link_name' ) : $item->get_value('name');
			}
			$page_name = strip_tags($page_name,'<span><strong><em>');
			if( $this->cur_page_id != $item->id() || $this->should_link_to_current_page()
				|| (preg_match("/^show_children/", $item->get_value( 'custom_page' )) && $depth == 1))
			{
				$link = $this->get_full_url($item->id());
				
				// if the selected page should not be shown in the nav, then we should highlight the parent of the
				// invisible page.  This code checks to see if the current page is the parent of the selected
				// page and checks if the selected page should not be shown.
				// It also checks to see if the current page is the same as the item id in the case where link_to_urrent_page is set
				if(($this->cur_page_id == $item->id() ||
					( $this->values[ $this->cur_page_id ]->get_value( 'parent_id' ) == $item->id() AND
					$this->values[ $this->cur_page_id ]->get_value( 'nav_display' ) == 'No' )) AND
					(!preg_match("/^show_children/", $item->get_value( 'custom_page' )) && $depth != 1))
				{
					$prepend = '<strong>';
					$append = '</strong>';
				}
				else
				{
					$prepend = '';
					$append = '';
				}
				
				$link = '<a href="'.$link.'"'.$class_attr.'>'.$prepend.$page_name.$append.'</a>';

				return $link;
			}
			else
				return '<strong'.$class_attr.'>'.$page_name.'</strong>';
		}
		function modify_base_url($base_url)
		// for extending
		{
			return $base_url;
		}
		/**
		 * Recursive function to build the path of a page inside the site
		 *
		 * This method does not return a usable URL -- it only provides the part of the full URL 
		 * that comes after the site's base URL.
		 * If you want to get a usable URL, try the method get_url_from_base() or get_full_url().
		 *
		 * @param integer $id The ID of the page
		 * @param integer $depth The depth from the first get_nice_url() called in this stack
		 * @return string The url of the page (relative to the base url of the site)
		 */
		function get_nice_url( $id, $depth = 1)
		{
			$ret = false;
			if($depth > 60) // deeper than 60 we figure there is a problem
			{
				trigger_error('Apparent infinite loop; maximum get_nice_url() depth of 60 reached (id '.$id.')');
				return false;
			}
			if(!empty($id))
			{
				if( isset( $this->values[ $id ] ) )
				{
					if( isset( $this->nice_urls[ $id ] ) )
						$ret = $this->nice_urls[ $id ];
					elseif( $id == $this->root_node() )
					{
						$this->nice_urls[ $this->root_node() ] = $this->values[ $this->root_node() ]->get_value( 'url_fragment' );
						$ret = $this->nice_urls[ $this->root_node() ];
					}
					else
					{
						$depth++;
						$p_id = $this->parent( $id );
						if(isset($this->values[ $p_id ])) // need to check or else we will call get_nice_url on a page not in the site
						{
							$ret = $this->get_nice_url( $p_id, $depth ).'/'.$this->values[ $id ]->get_value( 'url_fragment' );
						}
					}
				}
				else
				{
					trigger_error('get_nice_url() called with an id not in site ('.$id.') at depth '.$depth);
				}
			}
			else
			{
				trigger_error('get_nice_url() called with an empty id at depth '.$depth);
			}
			return $ret;
		}
		/**
		 * Gets the path to a page from the server root
		 *
		 * If you want textonly inclusion and/or awareness of external URL pages, you might try get_full_url().
		 *
		 * @param integer $id The ID of the page
		 * @return string The url of the page (relative to the server root)
		 */
		function get_url_from_base( $id )
		{
			static $base_url;
			static $base_prepped = false;
			if(!$base_prepped)
			{
				$trimmed_base = trim_slashes($this->site_info->get_value( 'base_url' ));
				if(empty($trimmed_base))
					$base_url = '';
				else
					$base_url = '/'.$trimmed_base;
				$base_url = $this->modify_base_url($base_url);
			}
			return $base_url.$this->get_nice_url( $id ).'/';
		}
		/**
		 * Gets the full url of the page
		 *
		 * If the page is an external link, this method returns the page's url value.
		 * 
		 * Otherwise, it returns a url that conforms to the parameters given.
		 * This method pays attention to the textonly value of the page tree object, and appends that value if it exists.
		 *
		 * @param integer $id The ID of the page
		 * @param boolean $as_uri If true, provides a fully qualified URL (e.g. a URI, like: http://www.somesite.com/sitebase/page/path/) If false, provides a URL relative to the base of the server
		 * @param boolean $secure If true, uses https; otherwise uses http. This param only has an effect if $as_uri is true
		 * @return string The url of the page
		 */
		function get_full_url( $id, $as_uri = false, $secure = false )
		{
			if(empty($this->values[ $id ]))
			{
				return false;
			}
			else
			{
				$item =& $this->values[ $id ];
				if( !$item->get_value( 'url' ) )
				{
					$link = $this->get_url_from_base( $id );
					if ( !empty( $this->textonly ) )
						$link .= '?textonly=1';
					if($as_uri)
					{
						if($secure)
						{
							$link = securest_available_protocol() . '://'.REASON_HOST.$link;
						}
						else
						{
							$link = 'http://'.REASON_HOST.$link;
						}
					}
				}
				else
				{
					$link = $item->get_value( 'url' );
				}
				return $link;
			}
		}
		
		/**
		 * Should include only those items needed by the minisite navigation builder
		 */
		function grab_request()
		{
			$request = array_diff( conditional_stripslashes($_REQUEST), conditional_stripslashes($_COOKIE) );
			$columns = (isset($this->columns)) ? array_keys($this->columns) : array('');
			$cleanup_rules = array('site_id' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => true)),
								   'page_id' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => true)),
								   'textonly' => array('function' => 'turn_into_int'),
								   'editing' => array('function' => 'check_against_array', 'extra_args' => array('off', 'on')));
			
			// apply the cleanup rules
			$this->request = carl_clean_vars($request, $cleanup_rules);
		}
		
		function get_id_chain($page_id)
		{
			if(!isset($this->values[ $page_id ]))
			{
				trigger_error('Page id '.$page_id.' not in site');
				return array();
			}
			$chain = array($page_id);
			if($page_id == $this->root_node())
			{
				return $chain;
			}
			return array_merge($chain, $this->get_id_chain($this->parent($page_id)));
		}
		
		function prepend_icon(&$item, $class)
		// Add unique name-specific class to prepend an icon to navigation text.
		// e.g. Facebook, Twitter, GooglePlus, YouTube, etc.
		// Will require loading image in css
		{
			//$icons = array('facebook', 'twitter', 'google+', 'youtube', 'linkedin', 'pinterest');
			$page_name = $item->get_value( 'link_name' ) ? $item->get_value( 'link_name' ) : $item->get_value('name');
			$page_name = strtolower(preg_replace('| |', '_', $page_name));
			$page_name = strtolower(preg_replace('|[Gg]oogle\+|', 'googleplus', $page_name));
			return '<li class="navListItem ' . $class.' ' . $page_name .'">'; 
			
		}
	}
?>
