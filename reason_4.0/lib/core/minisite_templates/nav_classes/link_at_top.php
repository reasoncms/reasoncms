<?php
/**
 * @package reason
 * @subpackage minisite_navigation
 */
 
 /**
  * Include the base class and other neccessities
  */
	include_once( 'reason_header.php' );
	reason_include_once( 'minisite_templates/nav_classes/default.php' );
	reason_include_once( 'function_libraries/url_utils.php' );

	class LinkAtTopNavigation extends MinisiteNavigation
	{

	var $root_node_id;
	var $top_link_site_id;
	var $link;
	var $top_link_class = 'LinkAtTop';
		
		function show_all_items() // {{{
		{
			$this->root_node_id = $this->root_node();
			$this->pre_show_all_items();
			$this->make_top_link();
			$this->make_nav_tree();
		} // }}}

		function pre_show_all_items()
		{
			if ($this->top_link_site_id == $this->site_id)
			{
				$this->link = $this->top_link_site_id = '';
			}
			elseif (!empty($this->top_link_site_id) && empty($this->link))
			{
				$site = new entity($this->top_link_site_id);
				$this->link = '<a href="'.$site->get_value('base_url').'">'.$site->get_value('name') . " Home".'</a>';
			}
		}
		
		function make_nav_tree()
		{
			echo '<ul class="navListTop">';
			$this->make_tree( $this->root_node_id , $this->root_node_id , 0);
			echo '</ul>'."\n";
		}
		
		function make_top_link()
		{
			if ($this->link)
			{
				echo '<p class="'.$this->top_link_class.'">'.$this->link.'</p>';
				return true;
			}
			return false;
		}
	}
?>