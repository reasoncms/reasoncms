<?php
/**
 * @package reason
 * @subpackage minisite_navigation
 */
 
 /**
  * Include the base class
  */
	include_once( 'reason_header.php' );
	reason_include_once( 'minisite_templates/nav_classes/default.php' );

	class LutherDefaultMinisiteNavigation extends MinisiteNavigation
	{
		var $start_depth = 0;
		var $display_parent_of_open_branch = true;
		var $link_to_current_page = true;
		
		function get_home_page_link_text()
		{
			$home_page = $this->values[ $this->root_node() ];
			if($home_page->get_value('link_name'))
			{
				return $home_page->get_value('link_name');
			}
			else
			{
				return $this->site_info->get_value('name');
			}
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
				$item_display = $this->show_item( $this->values[ $item  ], $depth );
				if (!empty($children) && $this->use_accordion_nav($this->values[ $item ]->get_value( 'custom_page' )) && $depth == 1)
				{
					$class = 'accordion ' . $class;
				}
		
				echo $this->prepend_icon($this->values[$item], $class);
				echo $item_display;
				if(( $open AND !empty( $children ))
						|| (!empty($children) && $this->use_accordion_nav($this->values[ $item ]->get_value( 'custom_page' )) && $depth == 1))
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
					|| ($this->use_accordion_nav($item->get_value( 'custom_page' )) && $depth == 1))
			{
				$link = $this->get_full_url($item->id());
		
				// if the selected page should not be shown in the nav, then we should highlight the parent of the
				// invisible page.  This code checks to see if the current page is the parent of the selected
				// page and checks if the selected page should not be shown.
				// It also checks to see if the current page is the same as the item id in the case where link_to_urrent_page is set
				if(($this->cur_page_id == $item->id() ||
						( $this->values[ $this->cur_page_id ]->get_value( 'parent_id' ) == $item->id() AND
								$this->values[ $this->cur_page_id ]->get_value( 'nav_display' ) == 'No' )) AND
						(!$this->use_accordion_nav($item->get_value( 'custom_page' )) && $depth != 1))
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
		
		function use_accordion_nav($custom_page)
		// use accordion nav on show_children page types except for
		// show_children_with_az_list and any show_children_with_first_images
		{
			if ($custom_page == 'show_children_with_az_list')
			{
				return false;
			}
			elseif (preg_match("/^show_children_with_first_images/", $custom_page))
			{
				return false;
			}
			elseif (preg_match("/^show_children/", $custom_page))
			{
				return true;
			}
			else 
			{
				return false;
			}
		}
	}
	
	
?>
