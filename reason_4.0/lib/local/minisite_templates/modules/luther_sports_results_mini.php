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
	var $snap_to_nearest_view = false;
	var $events_page_types = array('events','events_verbose','events_nonav','events_academic_calendar','event_registration','event_slot_registration','events_archive','events_archive_verbose', 'sports_results');
	var $list_date_format = 'M d';
	var $passables = array('start_date','textonly','view','category','audience','end_date','search','season');
	var $season_switch_date = "06-01";
	var $luther_start_year = 2011;   // first year there is events data
	
		
	function init( $args = array() )
	{
		parent::init( $args );
		//$this->find_events_page();
		//if ($this->site_id == get_site_id_from_url("/sports"))
		//{
		//	$this->luther_counter = $this->luther_counter_sports_home;
		//}
	}
	
	function event_ok_to_show($event)
	{
		return true;
	}
	
	function has_content()
	{
		return true;
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
				'function' => 'turn_into_date',
				'method'=>'get',
			),
			'date' => array(
				'function' => 'turn_into_date',
				'method'=>'get',
			),
			'category' => array(
				'function' => 'turn_into_int'
			),
			'event_id' => array(
				'function' => 'turn_into_int'
			),
			'end_date' => array(
				'function'=>'turn_into_date',
				'method'=>'get',
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
			'slot_id' => array(
				'function' => 'turn_into_int',
			),
			'admin_view' => array(
				'function' => 'check_against_array',
				'extra_args' => array('true'),
			),
			'delete_registrant' => array(
				'function' => 'turn_into_string',
			),
			'season' => array(
				'function' => 'turn_into_string',
			),
		);
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
		
		
		if ($this->calendar->contains_any_events())
		{
			if (luther_is_sports_page(false))
			{
				echo $this->school_year_select_list();
			}
			$this->events_by_date = $this->calendar->get_all_days();
			if (!empty($this->events_by_date))
			{
				$this->events = $this->calendar->get_all_events();
				//echo '<table class="tablesorter">'."\n";
				if (luther_is_sports_page())
				{
					// want most recent results listed first on landing pages
					$this->events_by_date = array_reverse($this->events_by_date, TRUE);
				}
				
				if($markup = $this->get_markup_object('list_chrome'))
				{
					$bundle = new functionBundle();
					$bundle->set_function('calendar', array($this, 'get_current_calendar'));
					$bundle->set_function('view_options_markup', array($this, 'get_section_markup_view_options'));
					$bundle->set_function('calendar_grid_markup', array($this, 'get_section_markup_calendar_grid'));
					$bundle->set_function('search_markup', array($this, 'get_section_markup_search'));
					$bundle->set_function('options_markup', array($this, 'get_section_markup_options'));
					$bundle->set_function('navigation_markup', array($this, 'get_section_markup_navigation'));
					$bundle->set_function('focus_markup', array($this, 'get_section_markup_focus'));
					$bundle->set_function('list_title_markup', array($this, 'get_section_markup_list_title'));
					$bundle->set_function('ical_links_markup', array($this, 'get_section_markup_ical_links'));
					$bundle->set_function('rss_links_markup', array($this, 'get_section_markup_rss_links'));
					$bundle->set_function('list_markup', array($this, 'get_events_list_markup'));
					$bundle->set_function('date_picker_markup', array($this, 'get_section_markup_date_picker'));
					$bundle->set_function('options_markup', array($this, 'get_section_markup_options'));
					$bundle->set_function('full_calendar_link_markup', array($this, 'get_full_calendar_link_markup'));
					$bundle->set_function('prettify_duration', array($this, 'prettify_duration') );
					// get_full_calendar_link_markup()
					$this->modify_list_chrome_function_bundle($bundle);
					/* if($markup->needs_markup('list'))
					 $markup->set_markup('list', $this->get_events_list_markup($msg)); */
					$markup->set_bundle($bundle);
					if($head_items = $this->get_head_items())
						$markup->modify_head_items($head_items);
					echo $markup->get_markup();
				}
				//$ret .= '</table>'."\n";
			}
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
				echo '<a title="Live stats" href="'. $matches[0] .'">Live stats</a>';
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
		$ret = '';
		$d = intval(date('Y'));
		$ret .= '<form method="post" name="disco_form">'."\n";
		$ret .= '<div id="discoLinear">'."\n";
		
		$ret .= "School Year:&nbsp;\n";
		$ret .= '<select name="season" title="choose season" onchange="this.form.submit();">'."\n";
		for ($i = $d; $i >= min($this->luther_start_year, $d - 1); $i--)
		{
			if ($i == intval(date('Y', strtotime($this->start_date))))
			{
				$ret .= '<option value="' . strval($i) . '" selected="selected">' . strval($i) . ' - ' . strval($i + 1) .'</option>'."\n";
			}
			else
			{	
				$ret .= '<option value="' . strval($i) . '">' . strval($i) . ' - ' . strval($i + 1) .'</option>'."\n";
			}
		}
		$ret .= '</select>'."\n";
		//$ret .= '<input type="submit" value="Go"/>'."\n";
		$ret .= '</div>'."\n";
		
		$ret .= '</form>'."\n";
		return $ret;
	}

}
?>
