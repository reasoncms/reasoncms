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
	var $ideal_count = 3;
	var $div_id = 'miniCal';
	var $show_options = false;
	var $show_navigation = false;
	var $show_views = false;
	var $show_months = false;
	var $snap_to_nearest_view = false;
	var $events_page;
	var $events_page_types = array('events','events_verbose','events_nonav','events_academic_calendar','event_registration','event_slot_registration','events_archive','events_archive_verbose');
	var $list_date_format = 'F j, Y';
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
	function run() // {{{
        {
	echo '<div class="supplemental block block-3 events">'."\n";
                               // echo '<h2>Upcoming Events</h2>'."\n";	
                echo '<div id="'.$this->div_id.'">'."\n";
                if (empty($this->request['event_id']))
                        $this->list_events();
                else
                        $this->show_event();
                echo '</div>'."\n";
                //echo '</div>'."\n";

        } // }}}

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
		return;  // don't show all events 
//		echo '<p class="more"><a href="'.$this->events_page_url.'">More events</a></p>'."\n";
		echo '<p class="links">'."\n";
		echo '<a class="all" href="'.$this->events_page_url.'">'."\n";
		echo 'See all events'."\n";
		echo '</a></p>'."\n";
	}
	function show_list_export_links()
	{
	}

	function show_event_list_item_standard( $event_id, $day )
        {
	// Override - don't want time on admissions home page
                //if($this->show_times && substr($this->events[$event_id]->get_value( 'datetime' ), 11) != '00:00:00')
                        //echo prettify_mysql_datetime( $this->events[$event_id]->get_value( 'datetime' ), $this->list_time_format ).' - ';
                echo '<a href="';
                echo $this->events_page_url;
                echo $this->construct_link(array('event_id'=>$this->events[$event_id]->id(),'date'=>$day ));
                echo '">';
                echo $this->events[$event_id]->get_value( 'name' );
                echo '</a>';
        }

}
?>
