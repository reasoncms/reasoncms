<?php
/**
 * @package reason
 * @subpackage minisite_navigation
 */
 
 /**
  * Include the parent class
  */
	include_once( 'reason_header.php' );
	reason_include_once( 'minisite_templates/nav_classes/link_at_top.php' );
	
	/**
 	 * This nav class will display parent items of a tree as top links to the level specified in max_parent_links 
 	 *
 	 * @author Nathan White
 	 */ 
	class ParentAtTopOneLevelNavigation extends LinkAtTopNavigation
	{
		var $display_parent_of_open_branch = false;
		var $parent_links = array();
		var $parent_link_class = 'LinkAtTop';
		var $max_parent_links;
		
		function gen_parent_links($page_id)
		{
			if (($page_id != $this->root_node_id))
			{
				$this->parent_links[$page_id] = $this->gen_parent_link($page_id);
				return $this->gen_parent_links($this->parent($page_id));
			}
			if ($this->root_node_id != $this->cur_page_id)
			{
				$this->parent_links[$this->root_node_id] = $this->gen_parent_link($this->root_node_id, true);
			}
			
			$this->parent_links = array_reverse($this->parent_links, true);
			
			if (!empty($this->max_parent_links))
			{
				// this is a little weird but array_slice in PHP 4 cannot preserve keys which makes things a bit ugly
				$parent_compare = array_flip(array_slice($this->parent_links, 0, ($this->max_parent_links - 1)));
				foreach ($this->parent_links as $k=>$v)
				{
					if (!isset($parent_compare[$v]))
					{
						unset($this->parent_links[$k]);
					}
				}
			}
			return $this->parent_links;
		}

		function make_nav_tree()
		{
			$parent_id = $this->values[$this->cur_page_id]->get_value('parent_id');
			$node_info = $this->check_node($this->cur_page_id, $parent_id);
			$this->start_depth = count($this->gen_parent_links($this->parent($this->cur_page_id)));
			if ($node_info['has_siblings'] && !$node_info['has_children'])
			{
				$this->display_parent_of_open_branch = true;
				if (!$this->check_parent_links($parent_id)) $this->start_depth++;
			}
			elseif (!$node_info['has_siblings'] && !$node_info['has_children']) //case of no siblings/children
			{
				if ($node_info['is_root']) $this->display_parent_of_open_branch = true;
				else 
				{
					$this->display_parent_of_open_branch = true;
					if (!$this->check_parent_links($parent_id)) $this->start_depth++;
				}
			}
			else
			{
				$this->display_parent_of_open_branch = true;
				$this->start_depth++;
			}
			$this->make_parent_links();
			echo '<ul class="navListTop">';
			$this->make_tree( $this->root_node_id , $this->root_node_id , 0);
			echo '</ul>'."\n";
		}

		function check_parent_links($item_id)
		{
			$ret = false;
			foreach (array_keys($this->parent_links) as $key)
			{
				if ($item_id == $key)
				{
					$ret = true;
					unset($this->parent_links[$key]);
				}
			}
			return $ret;
		}
		
		function make_parent_links()
		{
			foreach ($this->parent_links as $link)
			{
				echo $link;
			}
		}

		function gen_parent_link($page_id, $root_node = false)
		{
			// We are going to have the parent link always be the site name plus the string home
			if ($root_node)
			{
				$link_name = $this->site_info->get_value('name') . ' Home';
			}
			else
			{
			$link_name = ($this->values[$page_id]->get_value('link_name')) ? 
				 		  $this->values[$page_id]->get_value('link_name') : 
						  $this->values[$page_id]->get_value('name');
			}
			$str = '<p class="'.$this->parent_link_class.'">';
			$str .= '<a href="' . $this->get_full_url($page_id) . '">'.$link_name.'</a>';
			$str .= '</p>';
			return $str;
		}
		
		function check_node($page_id, $parent_id)
		{
			$node_info = array();
			$node_info['has_children']     = 	(count($this->children($page_id)) > 0);
			$node_info['has_siblings']     = 	(count($this->children($this->parent($page_id))) > 1);
			$node_info['is_root'] 		   = 	($this->cur_page_id == $this->root_node_id);
			$node_info['is_parent_link']   = 	(!empty($this->parent_links) && isset($this->parent_links[$parent_id]) == true);
			return $node_info;
		}
		
		// is this still necessary?
		function get_item_class($item, $open, $depth = 0, $counter = 0)
		{
			static $first_call = true;
			if($open)
				$class = 'open';
			else
				$class = 'closed';
			$first = (($depth == 1) && $open && $first_call) ? true : false;
			$active = ($this->values[ $item ]->id() == $this->cur_page_id) ? true : false;
			if ($first) $class .= ' first';
			if ($active) $class .= ' active_item';
			if ($first && $active) $class .= ' first_active_item';
			if ($first_call) $first_call = false;
			return $class;
		}
	}
?>
