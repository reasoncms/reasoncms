<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'miniEventsModule';

/**
 * A minisite module that creates a minimal "sidebar" style event listing, linking to the main events page on the site
 */
class miniEventsModule extends EventsModule
{
	var $ideal_count = 7;
	var $div_id = 'miniCal';
	var $show_options = false;
	var $show_navigation = false;
	var $show_views = false;
	var $show_months = false;
	var $snap_to_nearest_view = false;
	var $events_page;
	/**
	 * An array of page types that this module should link to
	 *
	 * @var array
	 * @deprecated -- use @var $_events_modules instead.
	 */
	var $events_page_types = array();
	/**
	 * An array of additional module names that this module should link to
	 *
	 * @var array
	 */
	var $_events_modules = array();
	var $list_date_format = 'D. M. j';
	var $show_calendar_grid = false;
	
	function init( $args = array() ) // {{{
	{
		parent::init( $args );
		$this->find_events_page();
		
	} // }}}
	function has_content() // {{{
	{
		if(!empty($this->events_page_url) && !empty($this->calendar))
		{
			$events = $this->calendar->get_all_events();
			if(empty($events))
				return false;
			else
				return true;
		}
		return false;
	} // }}}
	function display_list_title()
	{
		echo '<h3><a href="'.$this->events_page_url.'">'.$this->events_page->get_value('name').'</a></h3>'."\n";
	}
	function _get_events_module_names()
	{
		reason_include_once( 'classes/module_sets.php' );
		$ms =& reason_get_module_sets();
		return array_unique(array_merge($ms->get('event_display'),$this->_events_modules));
	}
	function find_events_page() // {{{
	{
		$module_names = $this->_get_events_module_names();
		reason_include_once( 'minisite_templates/nav_classes/default.php' );
		$ps = new entity_selector($this->parent->site_id);
		$ps->add_type( id_of('minisite_page') );
		$rels = array();
		$page_types = $this->events_page_types;
		foreach($module_names as $module_name)
		{
			$page_types = array_merge($page_types, page_types_that_use_module($module_name));
		}
		$page_types = array_map('addslashes',array_unique($page_types));
		$ps->add_relation('page_node.custom_page IN ("'.implode('","', $page_types).'")');
		$page_array = $ps->run_one();
		reset($page_array);
		$this->events_page = current($page_array);
		if (!empty($this->events_page))
		{
			$ret = $this->parent->pages->get_full_url($this->events_page->id());
		}
		if(!empty($ret))
			$this->events_page_url = $ret;
	} // }}}
	function show_feed_link()
	{
		echo '<p class="more"><a href="'.$this->events_page_url.'">More events</a></p>'."\n";
	}
	function show_list_export_links()
	{
	}
}
?>
