<?php
/**
 * @package reason
 * @subpackage feeds
 */
include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
reason_include_once( 'minisite_templates/nav_classes/default.php' );
reason_include_once( 'function_libraries/url_utils.php' );

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
		$this->feed->set_item_field_map('title','name');
		$this->feed->set_item_field_map('selector_text','prepped_name');
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
	
	function flatten_tree($tree_data,$depth,$exclude_depths = array())
	{
		$ret = array();
		$num = count($tree_data);
		$i = 1;
		foreach($tree_data as $id=>$info)
		{
			$last = ( $i >= $num );
			if($last)
				$exclude_depths[] = $depth+1;
			//echo $id.'.';
			$ret[$id] = $info['item'];
			$ret[$id]->set_value('prepped_name',$this->prep_name($ret[$id],$depth,$last,$exclude_depths));
			$ret[$id]->set_value('depth',$depth);
			if(!$ret[$id]->get_value('url'))
			{
				$base_url = rtrim(reason_get_site_url($this->site), '/');
				$nice_url = $this->tree->get_nice_url($id);
				$url = $base_url . $nice_url . '/';
				$ret[$id]->set_value('url',$url);
			}
			if(!empty($info['children']))
			{
				$next_depth = $depth + 1;
				$ret = $ret + $this->flatten_tree($info['children'],$next_depth,$exclude_depths);
			}
			$i++;
		}
		return $ret;
	}
	
	function prep_name($item, $depth, $last = false, $exclude_depths = array() )
	{
		$prepped_name = '';
		for($i = 2; $i <= $depth; $i++)
		{
			if(in_array($i, $exclude_depths))
				$prepped_name .= '&#160;&#160;&#160;';
			else
				$prepped_name .= '&#9474;&#160;';
		}
		if($depth)
		{
			$prepped_name .= ($last ? '&#9492;' : '&#9500;' ).'&#160;';
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
