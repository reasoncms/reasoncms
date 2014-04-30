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
	var $snap_to_nearest_view = false;
	var $list_date_format = 'M d';
	var $passables = array('start_date','textonly','view','category','audience','end_date','search','season', 'ideal_count');
	var $season_switch_date = "06-01";
	var $luther_start_year = 2011;   // first year there is events data
	var $events_page_types = array('events','events_verbose','events_nonav','events_academic_calendar','event_registration','event_slot_registration','events_archive','events_archive_verbose', 'sports_results');

	function init( $args = array() )
	{
		parent::init( $args );
		$this->find_events_page();
	}
	
	function event_ok_to_show($event)
	{
		return true;
	}
	
	function has_content()
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'sports_results')
		{
			return true;
		}
		
		if(!empty($this->calendar))
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
		if ($this->cur_page->get_value( 'custom_page' ) == 'sports_results' && !empty($this->pass_vars['start_date']))
		{
			$this->start_date = $this->pass_vars['start_date'];// .'-'.$this->season_switch_date;
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
			$this->request['end_date'] = date('Y-m-d');
		}
		else if (!empty($this->pass_vars['season']))
		{
			$this->pass_vars['season'] = max($this->luther_start_year, $this->pass_vars['season']);
			$this->pass_vars['end_date'] = $this->pass_vars['season'] + 1 .'-'.$this->season_switch_date;
			$this->request['end_date'] = $this->pass_vars['season'] + 1 .'-'.$this->season_switch_date;
		}
		else if (!empty($this->pass_vars['start_date']))
		{
			$year = max($this->luther_start_year, substr($this->pass_vars['start_date'], 0, 4));
			$month = substr($this->pass_vars['start_date'], 5, 2);
			$day = substr($this->pass_vars['start_date'], 8, 2);
			$this->pass_vars['start_date'] = $year . '-' . $month . '-' . $day;
			$this->request['start_date'] =  $year . '-' . $month . '-' . $day;
			if (substr($this->pass_vars['start_date'], 5, 2) >= substr($this->season_switch_date, 0, 2) && substr($this->pass_vars['start_date'], 8, 2)  >= substr($this->season_switch_date, 3, 2))
			{
				$this->pass_vars['end_date'] = substr($this->pass_vars['start_date'], 0, 4) + 1 .'-'.$this->season_switch_date;
				$this->request['end_date'] = substr($this->pass_vars['start_date'], 0, 4) + 1 .'-'.$this->season_switch_date;
			}
			else 
			{
				$this->pass_vars['end_date'] = substr($this->pass_vars['start_date'], 0, 4) .'-'.$this->season_switch_date;
				$this->request['end_date'] = substr($this->pass_vars['start_date'], 0, 4) .'-'.$this->season_switch_date;
			}
		}
		else if (date('m') >= substr($this->season_switch_date, 0, 2) && date('d') >= substr($this->season_switch_date, 3, 2))
		{				
			$this->pass_vars['end_date'] = strval(intval(date('Y')) + 1).'-'.$this->season_switch_date;
			$this->request['end_date'] = strval(intval(date('Y')) + 1).'-'.$this->season_switch_date;
		}
		else
		{
			$this->pass_vars['end_date'] = date('Y-').$this->season_switch_date;
			$this->request['end_date'] = date('Y-').$this->season_switch_date;		
		}	
	}
	
	function handle_jump()
	{
		if(!empty($this->request['season']))
		{
			$year = $this->request['season'];
			$full_date = $year.'-'.$this->season_switch_date;
			$query_string = unhtmlentities($this->construct_link(array('start_date' => $full_date, 'season' => null, 'end_date' => null)));
			$url_array = parse_url(get_current_url());
			$link = $url_array['scheme'].'://'.$url_array['host'].$url_array['path'].$query_string;
			header('Location: '.$link);
			die();
		}
	}
	
	function _get_events_module_names()
	{
		reason_include_once( 'classes/module_sets.php' );
		$ms =& reason_get_module_sets();
		return array_unique(array_merge($ms->get('event_display'),$this->_events_modules));
	}
	
	function find_events_page()
	{
		$module_names = $this->_get_events_module_names();
		echo $module_names;
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
		print_r($page_array);
		reset($page_array);
		$this->events_page = current($page_array);
		if (!empty($this->events_page))
		{
			$ret = $this->parent->pages->get_full_url($this->events_page->id());
		}
		$this->events_page_url = '';
		if(!empty($ret))
			$this->events_page_url = $ret;
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
			'ideal_count' => array(
				'function' => 'turn_into_int',
			),
		);
	}
	
	function show_feed_link()
	// show link to complete sports schedule and results page
	{
		$viewAllLink = $this->events_page_url;

		$ret = '';
		$ret .= '<nav class="button view-all">'."\n";
		$ret .= '<ul>'."\n";
		$ret .= '<li><a href="'.$viewAllLink.'">Complete results &gt;</a></li>'."\n";
		$ret .= '</ul>'."\n";
		$ret .= '<hr>'."\n";
		$ret .= '</nav>'."\n";
		
		return $ret;
	}

	function list_events()
	{		
		if ($this->calendar->contains_any_events())
		{
			$this->events_by_date = $this->calendar->get_all_days();
			if (!empty($this->events_by_date))
			{
				$this->events = $this->calendar->get_all_events();
				if ($this->cur_page->get_value( 'custom_page' ) != 'sports_results')
				{
					// want most recent results listed first on landing pages
					$this->events_by_date = array_reverse($this->events_by_date, TRUE);
				}
				else 
				{
					echo $this->school_year_select_list();
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
			}
		}
	}
	
	/**
	 * Get the markup for just the events list (not including display chrome)
	 * @param string $ongoing_display 'above', 'below', or 'inline'
	 * @return string
	 */
	function get_events_list_markup($ongoing_display = 'above')
	{
		ob_start();
		if(!empty($this->events_by_date))
		{
			echo $this->_no_events_message;
			echo '<div id="events">'."\n";
			if(($list_markup = $this->get_markup_object('list')) && ($item_markup = $this->get_markup_object('list_item')))
			{
				$item_bundle = new functionBundle();
				$item_bundle->set_function('event_link', array($this, 'get_event_link') );
				$item_bundle->set_function('teaser_image', array($this, 'get_teaser_image_html') );
				$item_bundle->set_function('media_works', array($this, 'get_event_media_works'));
				$item_bundle->set_function('prettify_duration', array($this, 'prettify_duration') );
				$item_bundle->set_function('events_page_url', array($this, 'get_events_page_url'));
				$this->modify_list_item_function_bundle($item_bundle);
				$item_markup->set_bundle($item_bundle);
				if($head_items = $this->get_head_items())
					$item_markup->modify_head_items($head_items);
	
				$list_bundle = new functionBundle();
				$list_bundle->set_function('list_item_markup', array($item_markup,'get_markup') );
				$list_bundle->set_function('events', array($this, 'get_integrated_events_array') );
				$list_bundle->set_function('calendar', array($this, 'get_current_calendar') );
				$list_bundle->set_function('today', array($this, 'get_today') );
				$list_bundle->set_function('ideal_count', array($this, 'get_ideal_count'));
				$list_bundle->set_function('feed_link', array($this, 'show_feed_link'));
				$this->modify_list_function_bundle($list_bundle);
				$list_markup->set_bundle($list_bundle);
				if($head_items = $this->get_head_items())
					$list_markup->modify_head_items($head_items);
				echo $list_markup->get_markup();
			}
			echo '</div>'."\n";
		}
		else
		{
			$this->no_events_error();
		}
		return ob_get_clean();
	}
	
	function get_ideal_count()
	// markup needs to know ideal count for sports results
	{
		if($this->params['ideal_count'] > 0)
			$this->ideal_count = $this->params['ideal_count'];
		return $this->ideal_count;
	}
	
	function get_events_page_url()
	// markup needs to know events page url for sports
	{
		return $this->events_page_url;
	}

	function school_year_select_list()
	{
		$ret = '';
		// Make sure select list is set to the proper season when browser's back button is clicked
		$ret .= '<script type="text/javascript" src="/jquery/jquery_latest.js"></script>
				 <script type="text/javascript">			
                   $(document).ready(function()
                   {
                     $("select").val("'.date('Y', strtotime($this->start_date)).'");	
                   });
                   window.onbeforeunload = function()
                   {
					 // Ensure page is not cached by the browser.
                   }
				   window.onunload = function()
				   {
                     // Needed in order to avoid caching
                   }
		         </script>';
		
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
		$ret .= '</div>'."\n";
		
		$ret .= '</form>'."\n";
		return $ret;
	}

}
?>
