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
	reason_include_once( 'classes/page_types.php' );

	/**
	 * A nav class that does not show child pages in the nav if the page contains 
	 * the children module
	 */
	class MinimalChildrenNavigation extends MinisiteNavigation
	{
		var $page_types = array();
		var $root_id;
		function should_show_children($id)
		{
			if(empty($this->root_id))
			{
				$this->root_id = $this->root_node();
			}
			if($id != $this->root_id && $this->cur_page_id == $id)
			{
				$rpts =& get_reason_page_types();
				$page_type_name = $this->values[$id]->get_value('custom_page');
				$pt = ($page_type_name) ? $rpts->get_page_type($page_type_name) : $rpts->get_page_type();
				if ($pt->has_module('children'))
				{
					return false;
				}
			}
			return true;
		}
	}
?>
