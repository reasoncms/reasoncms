<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherEventsMiniModule';

/**
 * A minisite module that creates a minimal "sidebar" style event listing, linking to the main events page on the site
 */
class LutherEventsMiniModule extends EventsModule
{
	//var $ideal_count = 6;
	var $luther_counter = 3;
	var $show_options = false;
	var $show_navigation = false;
	var $show_views = false;
	var $show_calendar_grid = true;
	var $show_months = false;
	var $snap_to_nearest_view = false;
	var $events_page;
	var $events_page_types = array('events','events_verbose','events_nonav','events_academic_calendar','event_registration','event_slot_registration','events_archive','events_archive_verbose', 'sports_results');
	var $list_date_format = 'M d';
		
	function init( $args = array() )
	{
		parent::init( $args );
		$this->find_events_page();
		
	}
	
	function has_content()
	{
		if(!empty($this->events_page_url) && !empty($this->calendar)
		|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports' & !empty($this->calendar))
		{
			$events = $this->calendar->get_all_events();
			if(empty($events))
				return false;
			else
				return true;
		}
		return false;
	}
	function run()
	{
		$bc = $this->parent->_get_breadcrumbs();
		$page_name = $bc[0]["page_name"];
			
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			echo '<section class="events" role="group">'."\n";
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
			{
				echo '<header class="blue-stripe"><h1><span>Schedule</span></h1></header>'."\n";
			}
			else
			{
				if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving')
				{
					echo '<header class="blue-stripe"><h1><span>Browse Events</span></h1></header>'."\n";
					$this->show_calendar_grid();
				}
				else if (preg_match("/[Gg]lobal [Ll]earning/", $page_name))
				{
					echo '<header class="blue-stripe"><h1><span>Dates and Deadlines</span></h1></header>'."\n";
				}
				else 
				{
					echo '<header class="blue-stripe"><h1><span>Upcoming Events</span></h1></header>'."\n";
				}
			}
		}
		else if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_admissions'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music')
		{
			echo '<section class="events group with-calendar" role="group">'."\n";
			echo '<header class="red-stripe"><h1><span>Upcoming ' . $page_name .' Events</span></h1></header>'."\n";
		}
				
		echo '<ol class="hfeed">'."\n";
	
		//echo '<div id="'.$this->div_id.'">'."\n";
		if (empty($this->request['event_id']))
			$this->list_events();
		else
			$this->show_event();
		//echo '</div>'."\n";
		echo '</ol>'."\n";
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_admissions'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music')
		{
			//echo '<nav id="calendar">'."\n";
			$this->show_calendar_grid();
			//echo '</nav>  <!-- id="calendar" -->'."\n";
		}
		$this->show_feed_link();
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			echo '</section> <!-- class="events" role="group" -->'."\n";
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_admissions'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music')
		{
			echo '</section> <!-- class="events group with-calendar" role="group" -->'."\n";
		}
	}

	function find_events_page()
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
	}
	
	function show_feed_link()
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_home_feature')
		{
			$viewAllLink = "/programming/events/?view=all";
			//$viewAllLink = "/events/";
		}
		else
		{
			$viewAllLink = $this->events_page_url;
		}
		echo '<nav class="button view-all">'."\n";
		echo '<ul>'."\n";
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			echo '<li><a href="'.$viewAllLink.'">Complete schedule &gt;</a></li>'."\n";
		}
		else
		{
			//echo '<li><a href="/programming/calendar">View all events &gt;</a></li>'."\n";
			echo '<li><a href="'.$viewAllLink.'">View all events &gt;</a></li>'."\n";
		}
		echo '</ul>'."\n";
		echo '</nav>'."\n";
	}

	function list_events()
	{
		if ($this->calendar->contains_any_events())
		{
			$this->events_by_date = $this->calendar->get_all_days();
			if (!empty($this->events_by_date))
			{
				$this->events = $this->calendar->get_all_events();
				foreach($this->events_by_date as $day => $val)
				{
					$this->show_daily_events($day);
					if ($this->luther_counter <= 0)
						break;
				}		
			}
		}
	}
	
	function show_daily_events($day)
	{
		foreach ($this->events_by_date[$day] as $event_id)
		{
			
			$this->show_event_list_item( $event_id, $day );
		}		
	}

	function show_event_list_item_standard( $event_id, $day, $ongoing_type = '' )
	{
		$site_id = get_site_id_from_url("/sports");
		//echo $site_id."\n";
		//echo $this->site_id."\n";
		
		
		$sd = substr($this->events[$event_id]->get_value('datetime'), 0, 10);
		if (substr($day, 0, 10) == $sd
			|| substr($day, 0, 10) == substr($this->today, 0, 10))
		{
			echo '<li class="vevent">'."\n";
			if (!empty($this->events_page_url))
			{
				echo '<a href="'.$this->events_page_url.'?event_id='.$this->events[$event_id]->id().'&date='.$day.'">'."\n";
			}
			
			echo '<div>'."\n";
			$d = mktime(0, 0, 0, substr($day, 5, 2), substr($day, 8, 2), substr($day, 0, 4));
			$lo = substr($this->events[$event_id]->get_value('last_occurence'), 0, 10);

			echo '<time class="dtstart" datetime="'.$day.'"><span class="month">'.date('M', $d).'</span><span class="day">'.date('d', $d).'</span></time>'."\n";
			
			echo '<h1 class="summary">';
			if ($site_id == $this->site_id)
			{
				echo ucfirst(preg_replace("|(^.*?)\s\((w?o?m?en)\)$|", "\\2's \\1", $this->events[$event_id]->get_value('sponsor')))."<br>";
			}
			echo $this->events[$event_id]->get_value( 'name' );
			if ($sd != $lo)
			{
				$s = mktime(0, 0, 0, substr($sd, 5, 2), substr($sd, 8, 2), substr($sd, 0, 4));
				$e = mktime(0, 0, 0, substr($lo, 5, 2), substr($lo, 8, 2), substr($lo, 0, 4));
				if (date('M', $d) == date('M', $e))
				{
					echo '<br />('.date('M', $s).' '.date('d', $s).'-'.date('d', $e).')';
				}
				else 
				{
					echo '<br />('.date('M', $s).' '.date('d', $s).'-'.date('M', $e).' '.date('d', $e).')';
				}
			}
			if (!empty($this->events_page_url))
			{
				echo '</a>'."\n";
			}
			echo $this->video_audio_streaming($this->events[$event_id]->get_value('id'))."\n";
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
			{
				echo '<br /><span class="location">'.$this->events[$event_id]->get_value( 'location' );
				if ($this->events[$event_id]->get_value( 'description' ) != '')
				{
					echo ' ('.$this->events[$event_id]->get_value( 'description' ).')';
				}
				else if (substr($this->events[$event_id]->get_value('datetime'), 11) != '00:00:00')
				{
					echo ' ('.prettify_mysql_datetime($this->events[$event_id]->get_value('datetime'), "g:i a" ).')';
				}
				echo '</span>';
			}
			
			echo '</h1>'."\n";
			echo '</div>'."\n";
			
			echo '</li>'."\n";
			$this->luther_counter--;
		}

		
		//print_r( $this->events[$event_id]->get_values())."\n";

		

	}
	
	function video_audio_streaming($event_id)
	// check if video/audio streaming categories are present for an event
	{	
		$es = new entity_selector();
		$es->description = 'Selecting categories for event';
		$es->add_type( id_of('category_type'));
		$es->add_right_relationship( $event_id, relationship_id_of('event_to_event_category') );
		$cats = $es->run_one();
		$vstream = '';
		$astream = '';
		foreach( $cats AS $cat )
		{
			if ($cat->get_value('name') == 'Video Streaming')
			{
				$vstream = '<a title="Video Streaming" href="http://client.stretchinternet.com/client/luther.portal"><img class="video_streaming" src="/images/luther2010/video_camera_white_128.png" alt="Video Streaming"></a>';
			}
			if ($cat->get_value('name') == 'Audio Streaming')
			{
				$astream = '<a title="Video Streaming" href="http://www.luther.edu/kwlc/"><img class="audio_streaming" src="/images/luther2010/headphones_white_256.png" alt="Audio Streaming" title="Audio Streaming"></a>';
			}
		}
		return $astream . $vstream;
	}

}
?>
