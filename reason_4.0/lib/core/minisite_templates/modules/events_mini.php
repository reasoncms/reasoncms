<?php 
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'miniEventsModule';

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
	var $events_page_types = array('events','events_verbose','events_nonav','events_academic_calendar','event_registration','event_slot_registration','events_archive','events_archive_verbose');
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
	function find_events_page() // {{{
	{
		reason_include_once( 'minisite_templates/nav_classes/default.php' );
		$ps = new entity_selector($this->parent->site_id);
		$ps->add_type( id_of('minisite_page') );
		$rels = array();
		foreach($this->events_page_types as $page_type)
		{
			$rels[] = 'page_node.custom_page = "'.$page_type.'"';
		}
		$ps->add_relation('( '.implode(' OR ', $rels).' )');
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
