<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'lutherSportsResultsMiniModule';

/**
 * A minisite module that creates a minimal "sidebar" style event listing, linking to the main events page on the site
 */
class lutherSportsResultsMiniModule extends EventsModule
{
	var $ideal_count = 3;
	var $luther_counter = 3;
	var $luther_counter_sports_home = 7;   // show more results on sports home page
	var $show_options = false;
	var $show_navigation = false;
	var $show_views = false;
	var $show_calendar_grid = false;
	var $show_months = false;
	var $snap_to_nearest_view = false;
	var $events_page;
	var $events_page_types = array('events','events_verbose','events_nonav','events_academic_calendar','event_registration','event_slot_registration','events_archive','events_archive_verbose', 'sports_results');
	var $list_date_format = 'M d';
	var $passables = array('start_date','textonly','view','category','audience','end_date','search','season');
	var $season_switch_date = "06-01";
	var $luther_start_year = 2011;   // first year there is events data
	var $start_date;
	
		
	function init( $args = array() )
	{
		parent::init( $args );
		$this->find_events_page();
		if ($this->site_id == get_site_id_from_url("/sports"))
		{
			$this->luther_counter = $this->luther_counter_sports_home;
		}
	}
	
	function event_ok_to_show($event)
	{
		return true;
	}
	
	function show_event_details()
	{
		
		$e =& $this->event;
		$site_id = get_site_id_from_url("/sports");
		
			echo '<div class="eventDetails">'."\n";
			//$this->show_images($e);
			if ($site_id == $this->site_id)
			{
				$event_name = ucfirst(preg_replace("|(^.*?)\s\((w?o?m?en)\)$|", "\\2's \\1", $e->get_value('sponsor')))." - ".$e->get_value( 'name' );
			}
			else 
			{
				$event_name = $e->get_value( 'name' );
			}
			echo '<h1>'.$event_name.'</h1>'."\n";
			//$this->show_ownership_info($e);
			$st = substr($e->get_value('datetime'), 0, 10);
			$lo = substr($e->get_value('last_occurence'), 0, 10);
			$now = date('Y-m-d');
			if (!empty($this->request['date']) && strstr($e->get_value('dates'), $this->request['date']))
			{
				if ($lo != $st)
				{
					echo '<p class="date">'.prettify_mysql_datetime($st, "F j, Y" ).' - '.prettify_mysql_datetime($lo, "F j, Y")."\n";
				}
				else 
				{
					echo '<p class="date">'.prettify_mysql_datetime( $this->request['date'], "F j, Y" )."\n";
				}
			}

			if ($now <= $lo || !$e->get_value('content'))
			{
				if ($e->get_value('description'))
				{
					echo '&nbsp;('.$e->get_value( 'description' ).')'."\n";
				}
				else if (substr($e->get_value( 'datetime' ), 11) != '00:00:00')
				{
					echo '&nbsp;('.prettify_mysql_datetime( $e->get_value( 'datetime' ), "g:i a" ).')'."\n";
				}
				
				if ($e->get_value('location'))
					echo '<br>'.$e->get_value('location')."\n";
			}
			echo $this->video_audio_streaming($e->get_value('id'));	
			echo '</p>'."\n";
	
			if ($e->get_value('content'))
			{
				echo '<div class="eventContent">'."\n";
				echo $e->get_value( 'content' );
				echo '</div>'."\n";
			}
			
			if ($e->get_value('url'))
				echo '<div class="eventUrl">For more information, visit: <a href="'.$e->get_value( 'url' ).'">'.$e->get_value( 'url' ).'</a>.</div>'."\n";
			//$this->show_back_link();
			//$this->show_event_categories($e);
			//$this->show_event_audiences($e);
			//$this->show_event_keywords($e);
			echo '</div>'."\n";
		
	}
	
	function has_content()
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'sports_results')
		{
			return true;
		}
		
		if(!empty($this->events_page_url) && !empty($this->calendar))
		{
			$events = $this->calendar->get_all_events();
			
			if(empty($events))
			{
				return false;
			}
			else
			{
				foreach($events as $key => $value)
				{
					if (preg_match("/post_to_results/", $value->get_value( 'contact_organization' )))
					{
						return true;
					}
				}
			}
		}
		return false;
	}
	function run()
	{
		if (empty($this->request['event_id']))
		{					
			echo '<section class="events" role="group">'."\n";
			if ($this->cur_page->get_value( 'custom_page' ) != 'sports_results')
			{
				echo '<header class="blue-stripe"><h1><span>Results</span></h1></header>'."\n";
			}	
			
			echo '<table class="tablesorter">'."\n";
			$this->list_events();		
			echo '</table>'."\n";
			
			if ($this->cur_page->get_value( 'custom_page' ) != 'sports_results')
			{
				$this->show_feed_link();
			}
			echo '</section> <!-- class="events" role="group" -->'."\n";
		}
		else
		{
			$this->show_event();
		}
	}
	
	function _get_start_date()
	{
		// start date is based on the season switch date
		if ($this->cur_page->get_value( 'custom_page' ) == 'sports_results' && !empty($this->pass_vars['season']))
		{
			$this->start_date = $this->pass_vars['season'] .'-'.$this->season_switch_date;
			return $this->start_date;
		}
		
		if (date('m') >= substr($this->season_switch_date, 0, 2) && date('d') >= substr($this->season_switch_date, 3, 2))
		{		
			$this->start_date = date('Y-', strtotime($this->today)).$this->season_switch_date;
		}
		else
		{
			$this->start_date = date('Y-', strtotime($this->today.' - 1 year')).$this->season_switch_date;
		}
		return $this->start_date;
	}
	
	function register_passables()
	{
		
		foreach($this->request as $key => $value)
		{
			if(in_array($key,$this->passables))
				$this->pass_vars[$key] = $value;
		}
		
		if ($this->cur_page->get_value( 'custom_page' ) != 'sports_results')
		{
			// for results on a sports landing page we want events up to today but nothing in the future
			$this->pass_vars['end_date'] = date('Y-m-d');	
		}
		else if (!empty($this->pass_vars['season']))
		{
			$this->pass_vars['end_date'] = $this->pass_vars['season'] + 1 .'-'.$this->season_switch_date;
		}
		else if (date('m') >= substr($this->season_switch_date, 0, 2) && date('d') >= substr($this->season_switch_date, 3, 2))
		{				
			$this->pass_vars['end_date'] = strval(intval(date('Y')) + 1).'-'.$this->season_switch_date;
		}
		else
		{
			$this->pass_vars['end_date'] = date('Y-').$this->season_switch_date;
			
		}
	}
	
	function get_cleanup_rules()
	{
		if (!isset($this->calendar)) $this->calendar = new reasonCalendar;
		$views = $this->calendar->get_views();
		$formats = array('ical');

		return array(
			'audience' => array(
				'function' => 'turn_into_int',
			),
			'view' => array(
				'function' => 'check_against_array',
				'extra_args' => $views,
			),
			'start_date' => array(
				'function' => 'turn_into_date'
			),
			'date' => array(
				'function' => 'turn_into_date'
			),
			'category' => array(
				'function' => 'turn_into_int'
			),
			'event_id' => array(
				'function' => 'turn_into_int'
			),
			'end_date' => array(
				'function'=>'turn_into_date'
			),
			'nav_date' => array(
				'function'=>'turn_into_date'
			),
			'textonly' => array(
				'function'=>'turn_into_int'
			),
			'start_month' => array(
				'function'=>'turn_into_int'
			),
			'start_day' => array(
				'function'=>'turn_into_int'
			),
			'start_year' => array(
				'function'=>'turn_into_int'
			),
			'search' => array(
				'function'=>'turn_into_string'
			),
			'format' => array(
				'function'=>'check_against_array',
				'extra_args'=>$formats,
			),
			'no_search' => array(
				'function'=>'turn_into_int',
			),
			'season' => array(
				'function'=>'turn_into_int',
			),
		);
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

		$viewAllLink = $this->events_page_url;

		echo '<nav class="button view-all">'."\n";
		echo '<ul>'."\n";
		echo '<li><a href="'.$viewAllLink.'">Complete results &gt;</a></li>'."\n";
		echo '</ul>'."\n";
		echo '<hr>'."\n";
		echo '</nav>'."\n";
	}

	function list_events()
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'sports_results')
		{
			$this->school_year_select_list();		
		}
		
		if ($this->calendar->contains_any_events())
		{
			$this->events_by_date = $this->calendar->get_all_days();
			if (!empty($this->events_by_date))
			{
				$this->events = $this->calendar->get_all_events();
				
				if ($this->cur_page->get_value( 'custom_page' ) != 'sports_results')
				{
					// want most recent results listed first
					$this->events_by_date = array_reverse($this->events_by_date, TRUE);
				}
				else
				{
					echo '<tr>'."\n";
					echo '<th>Date</th>'."\n";
					echo '<th>Opponent</th>'."\n";
					echo '<th>Location</th>'."\n";
					echo '<th>Time/Results</th>'."\n";
					echo '</tr>'."\n";
				}	
				
				foreach($this->events_by_date as $day => $val)
				{
					$this->show_daily_events($day);
					if ($this->cur_page->get_value( 'custom_page' ) != 'sports_results' && $this->luther_counter <= 0)
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

	function show_event_list_item_standard( $event_id, $day, $ongoing_type = ''  )
	{
		
		//if (!empty($this->events_page_url))
		//{
		//	echo '<a href="'.$this->events_page_url.'?event_id='.$this->events[$event_id]->id().'&date='.$day.'">'."\n";
		//}

		if (substr($day, 0, 10) == substr($this->events[$event_id]->get_value('datetime'), 0, 10)
			&& ($this->cur_page->get_value( 'custom_page' ) == 'sports_results'
			|| ($this->cur_page->get_value( 'custom_page' ) != 'sports_results'
			&& preg_match("/post_to_results/", $this->events[$event_id]->get_value( 'contact_organization' )))))
		{
			$site_id = get_site_id_from_url("/sports");
			echo '<tr>'."\n";
			$d = mktime(0, 0, 0, substr($day, 5, 2), substr($day, 8, 2), substr($day, 0, 4));
			$lo = substr($this->events[$event_id]->get_value('last_occurence'), 0, 10);
			if (substr($day, 0, 10) != $lo)
			{
				$e = mktime(0, 0, 0, substr($lo, 5, 2), substr($lo, 8, 2), substr($lo, 0, 4));
				if (date('M', $d) == date('M', $e))
				{
					echo '<td>'.date('M', $d).' '.date('d', $d).'-'.date('d', $e).'</td>'."\n";
				}
				else 
				{
					echo '<td>'.date('M', $d).' '.date('d', $d).'-'.date('M', $e).' '.date('d', $e).'</td>'."\n";
				}
			}
			else
			{
				
				echo '<td>'.date('M', $d).' '.date('d', $d).'</td>'."\n";
			}
			
			//echo '<td>'.$this->events[$event_id]->get_value( 'id' ).'</td>'."\n";
			if ($site_id == $this->site_id)
			{
				$event_name = ucfirst(preg_replace("|(^.*?)\s\((w?o?m?en)\)$|", "\\2's \\1", $this->events[$event_id]->get_value('sponsor')))." - ".$this->events[$event_id]->get_value( 'name' );
			}
			else 
			{
				$event_name = $this->events[$event_id]->get_value( 'name' );
			}
			if (!empty($this->events_page_url))
			{
				echo '<td><a href="'.$this->events_page_url.'?event_id='.$this->events[$event_id]->id().'&date='.$day.'">'.$event_name.'</a></td>'."\n";
			}
			else
			{
				echo '<td>'.$event_name.'</td>'."\n";
			}
			
			echo '<td>'.$this->events[$event_id]->get_value( 'location' ).'</td>'."\n";
        
			echo '<td>';
			if (preg_match("/https?:\/\/[A-Za-z0-9_\-\.\/]+/", $this->events[$event_id]->get_value( 'description' ), $matches))
			{
				echo '<a title="Live stats" href="'. $matches[0] .'">Live</a>';
			}
			else if ($this->events[$event_id]->get_value( 'description' ) != '')
			{
				echo $this->events[$event_id]->get_value( 'description' );
			}
			else if (substr($this->events[$event_id]->get_value('datetime'), 11) != '00:00:00')
			{
				echo prettify_mysql_datetime($this->events[$event_id]->get_value('datetime'), "g:i a" );
			}
			echo $this->video_audio_streaming($event_id);				
			echo '</td>'."\n";

			//echo '<td>'.$this->events[$event_id]->get_value( 'recurrence' ).'</td>'."\n";
			//echo '<td>'.$this->events[$event_id]->get_value( 'last_occurence' ).'</td>'."\n";
			//echo '<td>'.$this->events[$event_id]->get_value( 'datetime' ).'</td>'."\n";
			echo '</tr>'."\n";
			$this->luther_counter--;
		}
		
		if (!empty($this->events_page_url))
		{
			//echo '</a>'."\n";
		}
		
		
		//print_r( $this->events[$event_id]->get_values())."\n";

		

	}
	
	function school_year_select_list()
	{
		
		$d = intval(date('Y'));
		echo '<form method="post" name="disco_form">'."\n";
		echo '<div id="discoLinear">'."\n";
		
		echo "School Year:&nbsp;\n";
		echo '<select name="season" title="choose season" onchange="this.form.submit();">'."\n";
		for ($i = $d; $i >= min($this->luther_start_year, $d - 1); $i--)
		{
			if ($i == intval(date('Y', strtotime($this->start_date))))
			{
				echo '<option value="' . strval($i) . '" selected="selected">' . strval($i) . ' - ' . strval($i + 1) .'</option>'."\n";
			}
			else
			{	
				echo '<option value="' . strval($i) . '">' . strval($i) . ' - ' . strval($i + 1) .'</option>'."\n";
			}
		}
		echo '</select>'."\n";
		//echo '<input type="submit" value="Go"/>'."\n";
		echo '</div>'."\n";
		
		echo '</form>'."\n";

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
				$vstream = '<a title="Video Streaming" href="http://client.stretchinternet.com/client/luther.portal"><img class="video_streaming" src="/images/luther2010/video_camera_gray_128.png" alt="Video Streaming"></a>';
			}
			if ($cat->get_value('name') == 'Audio Streaming')
			{
				$astream = '<a title="Video Streaming" href="http://www.luther.edu/kwlc/"><img class="audio_streaming" src="/images/luther2010/headphones_gray_256.png" alt="Audio Streaming" title="Audio Streaming"></a>';
			}
		}
		return $astream . $vstream;
	}

}
?>
