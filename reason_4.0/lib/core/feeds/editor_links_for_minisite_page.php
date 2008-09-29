<?php

include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
reason_include_once( 'minisite_templates/nav_classes/default.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorPageFeed';

class editorPageFeed extends defaultFeed
{
	var $page_tree;
	var $feed_class = 'editorPagesRSS';
	
	function alter_feed()
	{
		$this->feed->set_item_field_map('author','');
		//$this->feed->set_item_field_map('description','name');
		$this->feed->set_item_field_map('description','');
		$this->feed->set_item_field_map('pubDate','');
		$this->feed->set_item_field_map('title','prepped_name');
		$this->feed->set_item_field_map('link','url');
	}
}

class editorPagesRSS extends ReasonRSS
{
	var $tree = array();
	var $page_type_id;
	var $site;
	
	function editorPagesRSS( $site_id, $type_id = '' ) // {{{
	{
		$this->page_type_id = id_of('minisite_page');
		$this->site = new entity($site_id);
		$this->init( $site_id, $type_id );
	} // }}}
	function _get_items()
	{
		if(empty($this->site))
		{
			$this->items = array();
		}
		else
		{
			$this->tree = new minisiteNavigation();
			$this->tree->site_info = $this->site;
			$this->tree->init( $this->site->id(), $this->page_type_id );
			$tree_data = $this->tree->get_tree_data();
			if(!empty($tree_data))
			{
				$this->items = $this->flatten_tree($tree_data,0);
			}
		}
	}
	function flatten_tree($tree_data,$depth)
	{
		$ret = array();
		foreach($tree_data as $id=>$info)
		{
			//echo $id.'.';
			$ret[$id] = $info['item'];
			$ret[$id]->set_value('prepped_name',$this->prep_name($ret[$id],$depth));
			$ret[$id]->set_value('depth',$depth);
			if(!$ret[$id]->get_value('url'))
			{
				$base_url = trim_slashes($this->site->get_value('base_url'));
				$nice_url = $this->tree->get_nice_url($id);
				$url = (!empty($base_url))
					   ? 'http://'.REASON_HOST.'/'.$base_url.$nice_url.'/'
					   : 'http://'.REASON_HOST.$nice_url.'/';
				$ret[$id]->set_value('url',$url);
			}
			if(!empty($info['children']))
			{
				$next_depth = $depth + 1;
				$ret = $ret + $this->flatten_tree($info['children'],$next_depth);
			}
		}
		return $ret;
	}
	
	function prep_name($item, $depth)
	{
		$prepped_name = '';
		for($i = 1; $i <= $depth; $i++)
		{
			$prepped_name .= '--';
		}
		if(!empty($prepped_name))
		{
			$prepped_name .= ' ';
		}
		$prepped_name .= $item->get_value('name');
		if($depth == 0)
		{
			$prepped_name .= ' (Home Page)';
		}
		return $prepped_name;
	}
}

?>
