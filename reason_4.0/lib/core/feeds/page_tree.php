<?php
/**
 * @package reason
 * @subpackage feeds
 */

/**
 * Include dependencies & register feed with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
reason_include_once( 'minisite_templates/nav_classes/default.php' );
reason_include_once('function_libraries/url_utils.php');
reason_include_once('classes/page_types.php');
reason_include_once('classes/module_sets.php');
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'pageTreeFeed';

/**
 * This is the page tree feed
 */
class pageTreeFeed extends defaultFeed
{

	var $page_tree;
	var $feed_class = 'pageTreeRSS';
	var $page_types = array();
	var $modules = array();
	var $module_sets = array();
	var $query_string = 'id';
	
	function init( $type, $site = false )
	{
		parent::init($type, $site);
		$this->init_page_types();
	}
	function init_page_types()
	{
		if (!empty($this->module_sets))
		{
			$ms =& reason_get_module_sets();
			foreach ($this->module_sets as $module_set)
			{
				$modules = $ms->get($module_set);
				if (!empty($modules)) $this->modules = array_merge($this->modules, $modules);
			}
		}
		if(!empty($this->modules))
		{
			$rpts =& get_reason_page_types();
			$page_types = $rpts->get_page_type_names_that_use_module($this->modules);
			foreach($page_types as $pt)
			{
				if(!in_array($pt,$this->page_types))
				{
					$this->page_types[] = $pt;
				}
			}
		}
	}
	function create_feed()
	{
		$this->feed = new $this->feed_class( $this->site_id, $this->type->id() );
		$this->feed->page_types = $this->page_types;
		$this->feed->query_string = $this->query_string;
		$this->assign_link_functions();
	}
	function assign_link_functions()
	{
		$this->feed->set_item_field_map('link','id');
		if($this->site_specific)
		{
			$this->feed->set_item_field_handler( 'link', 'site_specific_item_link', true );
		}
		else
		{
			$this->feed->set_item_field_handler( 'link', 'non_site_specific_item_link', true );
		}
	}
	function get_site_link()
	{
		if($this->site_specific)
		{
			$this->create_page_tree();
			$this->site_link = get_page_link($this->site, $this->page_tree, $this->page_types, true);
		}
		else
			$this->site_link = $this->home_url;
	}
	function create_page_tree()
	{
		$this->page_tree = new minisiteNavigation();
		$this->page_tree->site_info = $this->site;
		$this->page_tree->init( $this->site_id, id_of('minisite_page') );
	}
}

class pageTreeRSS extends ReasonRSS
{
	var $trees = array();
	var $pages = array(); //keys = site ids, vals = urls of news pages
	var $page_type_id;
	var $page_types = array();
	var $query_string = '';
	
	function pageTreeRSS( $site_id, $type_id = '' ) // {{{
	{
		$this->page_type_id = id_of('minisite_page');
		$this->site = new entity($site_id);
		$this->init( $site_id, $type_id );
	} // }}}
	function site_specific_item_link( $item_id )
	{
		return $this->get_channel_attr( 'link' ).'?'.$this->query_string.'='.$item_id;
	}
	function non_site_specific_item_link( $item_id )
	{
		$owner = $this->items[ $item_id ]->get_owner();
		if(empty( $this->trees[ $owner->id() ] ) )
		{
			$this->trees[ $owner->id() ] = new minisiteNavigation();
			$this->trees[ $owner->id() ]->site_info = $owner;
			$this->trees[ $owner->id() ]->init( $owner->id(), $this->page_type_id );
		}
		
		if(empty($this->pages[ $owner->id() ]))
		{
			$this->pages[ $owner->id() ] = get_page_link( $owner, $this->trees[ $owner->id() ], $this->page_types, true );
		}
		
		return $this->pages[ $owner->id() ].'?'.$this->query_string.'='.$item_id;
	}
}

?>