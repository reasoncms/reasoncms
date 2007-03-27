<?php
	include_once( 'reason_header.php' );
	reason_include_once( 'minisite_templates/nav_classes/default.php' );

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
				if(empty($this->page_types))
					$this->page_types = page_types_that_use_module('children');
				if(in_array($this->values[$id]->get_value('custom_page'),$this->page_types))
				{
					return false;
				}
			}
			return true;
		}
	}
?>
