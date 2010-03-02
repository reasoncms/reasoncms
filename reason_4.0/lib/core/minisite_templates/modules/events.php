<?php 
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/calendar.php' );
reason_include_once( 'classes/calendar_grid.php' );
reason_include_once( 'classes/icalendar.php' );
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsModule';

class EventsModule extends DefaultMinisiteModule
{
	var $ideal_count;
	var $div_id = 'calendar';
	var $show_options = true;
	var $show_navigation = true;
	var $show_views = true;
	var $show_calendar_grid = true;
	var $show_times = true;
	var $passables = array('start_date','textonly','view','category','audience','end_date','search');
	var $limit_to_current_site = true;
	var $pass_vars = array();
	var $has_content = true;
	var $calendar; // A reasonCalendar object
	var $start_date; // A mysql-formatted date
	var $snap_to_nearest_view = true;
	var $events = array();
	var $events_by_date = array();
	var $show_months = true; // boolean
	var $prev_month;
	var $prev_year;
	var $next_and_previous_links;
	var $calendar_grid_markup = '';
	var $options_bar;
	var $today; // A mysql-formatted date
	var $tomorrow; // A mysql-formatted date
	var $yesterday; // A mysql-formatted date
	var $event; // An event entity
	var $events_page_url; // this is left empty, to be filled by child classes
	var $list_time_format = 'g:i a';
	var $list_date_format = 'l, F jS';
	var $audiences = array();
	var $rerun_if_empty = true;
	var $report_last_modified_date = true;
	var $view_markup = '';
	var $min_year;
	var $max_year;
	var $acceptable_params = array(
	 						'view'=>'',
							'limit_to_page_categories'=>false,
							'list_type'=>'standard',
						);
	
	var $views_no_index = array('daily','weekly','monthly');
	/**
	 * Toggles on and off the links to iCalendar-formatted data.
	 *
	 * Set to true to turn on the links; false to turn them off
	 */
	var $show_icalendar_links = true;
	
	//////////////////////////////////////
	// General Functions
	//////////////////////////////////////
	
	function init( $args = array() ) // {{{
	{
		parent::init( $args );
		
		$this->validate_inputs();
		
		$this->register_passables();
		
		$this->handle_jump();
		
		
		if(empty($this->request['event_id']))
		{
			$this->init_list();
		}
		else
			$this->init_event();
	} // }}}

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
		);
	}
	
	function handle_jump()
	{
		if(!empty($this->request['start_year']))
		{
			$year = $this->request['start_year'];
			$day = 1;
			$month = 1;
			if(!empty($this->request['start_day']))
				$day = $this->request['start_day'];
			if(!empty($this->request['start_month']))
				$month = $this->request['start_month'];
			$year = str_pad($year,4,'0',STR_PAD_LEFT);
			$day = str_pad($day,2,'0',STR_PAD_LEFT);
			$month = str_pad($month,2,'0',STR_PAD_LEFT);
			$full_date = $year.'-'.$month.'-'.$day;
			$query_string = unhtmlentities($this->construct_link(array('start_date'=>$full_date)));
			$url_array = parse_url(get_current_url());
			$link = $url_array['scheme'].'://'.$url_array['host'].$url_array['path'].$query_string;
			header('Location: '.$link);
			die();
		}
	}

	function validate_inputs()
	{
		if (!isset($this->calendar)) $this->calendar = new reasonCalendar;
		$views = $this->calendar->get_views();
		
		if(!empty($this->request['start_date']))
			$this->request['start_date'] = prettify_mysql_datetime($this->request['start_date'], 'Y-m-d');
			
		if(!empty($this->request['end_date']))
			$this->request['end_date'] = prettify_mysql_datetime($this->request['end_date'], 'Y-m-d');
			
		if(!empty($this->request['date']))
			$this->request['date'] = prettify_mysql_datetime($this->request['date'], 'Y-m-d');
			
		if(!empty($this->request['category']))
		{
			$e = new entity($this->request['category']);
			if(!($e->get_values() && $e->get_value('type') == id_of('category_type')))
			{
				unset($this->request['category']);
			}
		}
		if(!empty($this->request['audience']))
		{
			$e = new entity($this->request['audience']);
			if(!($e->get_values() && $e->get_value('type') == id_of('audience_type')))
			{
				unset($this->request['audience']);
			}
		}
	}
	
	function register_passables() // {{{
	{
		foreach($this->request as $key => $value)
		{
			if(in_array($key,$this->passables))
				$this->pass_vars[$key] = $value;
		}
	} // }}}
	
	function has_content() // {{{
	{
		return true;
	} // }}}
	
	function run() // {{{
	{
		echo '<div id="'.$this->div_id.'">'."\n";
		if (empty($this->request['event_id']))
			$this->list_events();
		else
			$this->show_event();
		echo '</div>'."\n";
		
	} // }}}
	
	//////////////////////////////////////
	// For The Events Listing
	//////////////////////////////////////
	
	function init_list()
	{
		$this->today = date('Y-m-d');
		$this->tomorrow = date('Y-m-d',strtotime($this->today.' +1 day'));
		$this->yesterday = date('Y-m-d',strtotime($this->today.' -1 day'));
		
		if(!empty($this->request['format']) && $this->request['format'] == 'ical')
		{
			$this->init_and_run_ical_calendar();
		}
		else
		{
			$this->init_html_calendar();
		}
		
	}
	function init_and_run_ical_calendar()
	{
		$start_date = !empty($this->request['start_date']) ? $this->request['start_date'] : $this->today;
		$init_array = $this->make_reason_calendar_init_array($start_date, '', 'all');
		
		$this->calendar = new reasonCalendar($init_array);
		
		$this->calendar->run();
		
		$events = $this->calendar->get_all_events();
		
		$this->export_ical($events);
	}
	function init_html_calendar()
	{
		$start_date = '';
		$end_date = '';
		$view = '';
		
		if( empty($this->request['no_search']) && empty($this->request['textonly']) && ( !empty($this->request['start_date']) || !empty($this->request['end_date']) || !empty($this->request['view']) || !empty($this->request['audience']) || !empty($this->request['view']) || !empty($this->request['nav_date']) || !empty($this->request['category']) ) )
		{
			$this->parent->head_items->add_head_item('meta', array('name'=>'robots','content'=>'noindex,follow'));
		}
		
		if(!empty($this->pass_vars['start_date']))
			$start_date = $this->pass_vars['start_date'];
		if(!empty($this->pass_vars['end_date']))
			$end_date = $this->pass_vars['end_date'];
		else
		{
			if(!empty($this->pass_vars['view']))
				$view = $this->pass_vars['view'];
			elseif(!empty($this->params['view']))
				$view = $this->params['view'];
		}
		
		$init_array = $this->make_reason_calendar_init_array($start_date, $end_date, $view);
		$this->calendar = new reasonCalendar($init_array);
		
		$this->calendar->run();
	}
	
	function list_events()
	{
		$msg = '';
		if($this->calendar->contains_any_events())
		{
			$this->events_by_date = $this->calendar->get_all_days();
			if($this->rerun_if_empty && empty($this->pass_vars) && empty($this->events_by_date))
			{
				$this->rerun_calendar();
				$this->events_by_date = $this->calendar->get_all_days();
				if(count(current($this->events_by_date)) > 1)
				{
					$msg = '<p>This calendar has no events coming up. Here are the last events available:</p>'."\n";
				}
				else
				{
					$msg = '<p>This calendar has no events coming up. Here is the last event available:</p>'."\n";
				}
				
			}
			$this->events = $this->calendar->get_all_events();
			$this->show_view_options();
			$this->show_calendar_grid_and_options_bar();
			//$this->show_options_bar();
			$this->show_navigation();
			//$this->show_calendar_grid();
			$this->show_focus();
			$this->display_list_title();
			if($this->calendar->get_view() == 'daily' || $this->calendar->get_view() == 'weekly')
				$this->show_months = false;
			if(!empty($this->events_by_date))
			{
				echo $msg;
				/* if($this->calendar->get_start_date() < $this->today && empty($this->request['search']))
				{
					echo '<p>Viewing archive. <a href="'.$this->construct_link(array('start_date'=>'')).'">Reset calendar to today</a></p>';
				} */
				echo '<div id="events">'."\n";
				foreach($this->events_by_date as $day => $val)
				{
					if ( $this->calendar->get_end_date() && $day > $this->calendar->get_end_date() )
						break;
					$this->show_daily_events( $day );
				}
				echo '</div>'."\n";
			}
			else
			{
				$this->no_events_error();
			}
		}
		else
		{
			$this->no_events_error();
		}
		echo '<div class="foot">'."\n";
		$this->show_navigation();
		// $this->show_options_bar();
		if($this->show_icalendar_links)
			$this->show_list_export_links();
		$this->show_feed_link();
		echo '</div>'."\n";
	}
	
	function show_focus()
	{
		if(!empty($this->request['search']) || !empty($this->request['category']) ||  !empty($this->request['audience']) )
		{
			echo '<div class="focus">'."\n";
			if(!empty($this->request['category']) ||  !empty($this->request['audience']) || !empty($this->request['search']))
			{
				$this->show_focus_description();
			}
			echo '</div>'."\n";
		}	
	}
	
	function show_focus_description()
	{
		$out = '';
		$needs_intro = true;
		$cat_str = $this->get_category_focus_description();
		if(!empty($cat_str))
		{
			$out .= $cat_str;
			$needs_intro = false;
		}
		$aud_str = $this->get_audience_focus_description($needs_intro);
		if(!empty($aud_str))
		{
			$out .= $aud_str;
			$needs_intro = false;
		}
		$search_str = $this->get_search_focus_description($needs_intro);
		if(!empty($search_str))
		{
			$out .= $search_str;
		}
		
		if(!empty($out))
		{
			echo '<h3>Currently Browsing:</h3>';
			echo '<ul>'.$out.'</ul>'."\n";
		}
	}
	function get_category_focus_description()
	{
		$ret = '';
		if(!empty($this->request['category']))
		{
			$e = new entity($this->request['category']);
			$name = strip_tags($e->get_value('name'));
			$ret .= '<li class="categories first">';
			$ret .= '<h4>Events in category: '.$name.'</h4>'."\n";
			$ret .= '<a href="'.$this->construct_link(array('category'=>'','view'=>'')).'" class="clear">See all categories (clear <em>&quot;'.htmlspecialchars($name).'&quot;</em>)</a>';
			$ret .= '</li>';
		}
		return $ret;
	}
	function get_audience_focus_description($needs_intro = false)
	{
		$ret = '';
		if(!empty($this->request['audience']))
		{
			$e = new entity($this->request['audience']);
			$ret .= '<li class="audiences';
			if(empty($this->request['category']))
				$ret .= ' first';
			$ret .= '"><h4>';
			if($needs_intro)
				$ret .= 'Events ';
			$name = strip_tags($e->get_value('name'));
			$ret .= 'for '.$name.'</h4>'."\n";
			$ret .= '<a href="'.$this->construct_link(array('audience'=>'','view'=>'')).'" class="clear">See events for all groups (clear <em>&quot;'.htmlspecialchars($name).'&quot;</em>)</a>';
			$ret .= '</li>';
		}
		return $ret;
	}
	function get_search_focus_description($needs_intro = false)
	{
		$ret = '';
		if(!empty($this->request['search']))
		{
			$ret .= '<li class="search';
			if(empty($this->request['category']) && empty($this->request['audience']))
				$ret .= ' first';
			$ret .= '">';
			$ret .= '<h4><label for="calendar_search_above">';
			if($needs_intro)
				$ret .= 'Events ';
			$ret .= 'containing</label></h4> ';
			$ret .= $this->get_search_form('calendar_search_above',true);
			$ret .= $this->get_search_other_actions();
			$ret .= '</li>';
		}
		return $ret;
	}
	
	function rerun_calendar()
	{
		//trigger_error('get_max_date called');
		$init_array = $this->make_reason_calendar_init_array($this->calendar->get_max_date(),'','all' );
		$this->calendar = new reasonCalendar($init_array);
		$this->calendar->run();
	}
	
	
	function display_list_title()
	{
	}
	
	function no_events_error()
	{
		echo '<div class="newEventsError">'."\n";
		$start_date = $this->calendar->get_start_date();
		$audiences = $this->calendar->get_audiences();
		$categories = $this->calendar->get_categories();
		$min_date = $this->calendar->get_min_date();
		if($this->calendar->get_view() == 'all' && empty($categories) && empty( $audiences ) && empty($this->request['search']) )
		{
			//trigger_error('get_max_date called');
			$max_date = $this->calendar->get_max_date();
			if(empty($max_date))
			{
				echo '<p>This calendar does not have any events.</p>'."\n";
			}
			else
			{
				echo '<p>There are no future events in this calendar.</p>'."\n";
				echo '<ul>'."\n";
				echo '<li><a href="'.$this->construct_link(array('start_date'=>$max_date, 'view'=>'all','category'=>'','audience'=>'','search'=>'')).'">View most recent event</a></li>'."\n";
				if($start_date > '1970-01-01')
				{
					echo '<li><a href="'.$this->construct_link(array('start_date'=>$min_date, 'view'=>'all','category'=>'','audience'=>'','search'=>'')).'">View entire event archive</a></li>'."\n";
				}
				echo '</ul>'."\n";
			}
		}
		else
		{
			if(empty($categories) && empty($audiences) && empty($this->request['search']))
			{
				$desc = $this->get_scope_description();
				if(!empty($desc))
				{
					echo '<p>There are no events '.$this->get_scope_description().'.</p>'."\n";
				if($start_date > '1970-01-01')
				{
					echo '<li><a href="'.$this->construct_link(array('start_date'=>'1970-01-01', 'view'=>'all')).'">View entire event archive</a></li>'."\n";
				}
				}
				else
				{
					echo '<p>There are no events available.</p>'."\n";
				}
			}
			else
			{
				echo '<p>There are no events available';
				$clears = '<ul>'."\n";
				if(!empty($audiences))
				{
					$audience = current($audiences);
					echo ' for '.strtolower($audience->get_value('name'));
					$clears .= '<li><a href="'.$this->construct_link(array('audience'=>'')).'">Clear group/audience</a></li>'."\n";
				}
				if(!empty($categories))
				{
					$cat = current($categories);
					echo ' in the '.$cat->get_value('name').' category';
					$clears .= '<li><a href="'.$this->construct_link(array('category'=>'')).'">Clear category</a></li>'."\n";
				}
				if(!empty($this->request['search']))
				{
					echo ' that match your search for "'.htmlspecialchars($this->request['search']).'"';
					$clears .= '<li><a href="'.$this->construct_link(array('search'=>'')).'">Clear search</a></li>'."\n";
				}
				$clears .= '</ul>'."\n";
				echo $clears;
			}
			if($this->calendar->get_start_date() > $this->today)
			{
				echo '<p><a href="'.$this->construct_link(array('start_date'=>'', 'view'=>'','category'=>'','audience'=>'', 'end_date'=>'','search'=>'')).'">Reset calendar to today</a></p>';
			}
			if($start_date > '1970-01-01')
			{
				echo '<p><a href="'.$this->construct_link(array('start_date'=>'1970-01-01', 'view'=>'all')).'">View entire event archive</a></p>'."\n";
			}
		}
		echo '</div>'."\n";
	}
	
	function get_scope_description()
	{
		$scope = $this->get_scope('through','F');
		if(!empty($scope))
		{
			if($this->calendar->get_start_date() == $this->calendar->get_end_date() )
			{
				if($this->calendar->get_view() == 'all')
					return 'on or after '.$this->get_scope('through','F');
				else
				{
					return 'on '.$this->get_scope('through','F');
				}
			}
			else
			{
				return 'between '.$this->get_scope('and','F');
			}
		}
		return '';
	}
	
	function show_view_options()
	{
		if($this->show_views)
		{
			if(empty($this->view_markup))
			{
				$this->view_markup = $this->get_view_options();
			}
			echo $this->view_markup;
		}
	}
	
	function show_daily_events( $day ) // {{{
	{
		if($this->show_months == true && ($this->prev_month != substr($day,5,2) || $this->prev_year != substr($day,0,4) ) )
		{
			echo '<h3>'.prettify_mysql_datetime( $day, 'F Y' ).'</h3>'."\n";
			$this->prev_month = substr($day,5,2);
			$this->prev_year = substr($day,0,4);
		}
		
		if($day == $this->today)
			$today = ' (Today)';
		else
			$today = '';
		echo '<h4>'.prettify_mysql_datetime( $day, $this->list_date_format ).$today.'</h4>'."\n";
		echo '<ul>';
		foreach ($this->events_by_date[$day] as $event_id)
		{
			echo '<li>';
			$this->show_event_list_item( $event_id, $day );
			echo '</li>'."\n";
		}
		echo '</ul>'."\n";
	} // }}}
	
	function show_event_list_item( $event_id, $day )
	{
		if($this->params['list_type'] == 'verbose')
			$this->show_event_list_item_verbose( $event_id, $day );
		else
			$this->show_event_list_item_standard( $event_id, $day );
	}
	
	function show_event_list_item_standard( $event_id, $day ) // {{{
	{
		if($this->show_times && substr($this->events[$event_id]->get_value( 'datetime' ), 11) != '00:00:00')
			echo prettify_mysql_datetime( $this->events[$event_id]->get_value( 'datetime' ), $this->list_time_format ).' - ';
		echo '<a href="';
		echo $this->events_page_url;
		echo $this->construct_link(array('event_id'=>$this->events[$event_id]->id(),'date'=>$day ));
		echo '">';
		echo $this->events[$event_id]->get_value( 'name' );
		echo '</a>';
	} // }}}
	
	function show_event_list_item_verbose( $event_id, $day ) // {{{
	{
		$link = $this->construct_link(array('event_id'=>$this->events[$event_id]->id(),'date'=>$day,'view'=>$this->calendar->get_view()));
		//echo '<p class="name">';
		echo '<a href="'.$link.'">';
		echo $this->events[$event_id]->get_value( 'name' );
		echo '</a>';
		echo '<ul>'."\n";
		if($this->events[$event_id]->get_value( 'description' ))
		{
			echo '<li>';
			echo $this->events[$event_id]->get_value( 'description' );
			echo '</li>'."\n";
		}
		$time_loc = array();
		if(substr($this->events[$event_id]->get_value( 'datetime' ), 11) != '00:00:00')
			$time_loc[] = prettify_mysql_datetime( $this->events[$event_id]->get_value( 'datetime' ), $this->list_time_format );
		if($this->events[$event_id]->get_value( 'location' ))
			$time_loc[] = $this->events[$event_id]->get_value( 'location' );
		if (!empty($time_loc))
		{
			echo '<li>';
			echo implode(', ',$time_loc);
			echo '</li>'."\n";
		}
		echo '</ul>'."\n";
	} // }}}
	
	function show_navigation() // {{{
	{
		if($this->show_navigation)
		{
			
			echo '<div class="nav">'."\n";
			if($this->calendar->get_view() != 'all')
			{
				if(empty($this->next_and_previous_links))
					$this->generate_next_and_previous_links();
				echo $this->next_and_previous_links;
			}
			else
			{
				echo '<strong>Starting '.prettify_mysql_datetime($this->calendar->get_start_date(),$this->list_date_format.', Y');
				switch($this->calendar->get_start_date())
				{
					case $this->today:
						echo ' (today)';
						break;
					case $this->tomorrow:
						echo ' (tomorrow)';
						break;
					case $this->yesterday:
						echo ' (yesterday)';
						break;
				}
				echo '</strong>';
			}
			echo '</div>'."\n";
		}
	} // }}}
	
	function show_calendar_grid_and_options_bar()
	{
		if($this->show_options || $this->show_calendar_grid)
		{
			echo '<div class="gridAndOptions">'."\n";
			$this->show_calendar_grid();
			$this->show_date_picker();
			$this->show_search();
			$this->show_options_bar();
			echo '</div>'."\n";
		}
	}
	function show_options_bar() // {{{
	{
		if($this->show_options)
		{
			if(empty($this->options_bar))
				$this->generate_options_bar();
			echo $this->options_bar;
		}
	} // }}}
	
	function generate_options_bar() // {{{
	{
		$this->options_bar .= '<div class="options">'."\n";
		$this->options_bar .= $this->get_all_categories();
		$this->options_bar .= $this->get_audiences();
		$this->options_bar .= $this->get_today_link();
		$this->options_bar .= $this->get_archive_toggler();
		$this->options_bar .= '</div>'."\n";
	} // }}}
	
	function get_view_options() // {{{
	{
		$ret = '';
		$ret .= "\n".'<div class="views">'."\n";
		$ret .= '<h4>View:</h4>';
		$ret .= '<ul>'."\n";
		$on_defined_view = false;
		foreach($this->calendar->get_views() as $view_name=>$view)
		{
			$ret .= '<li>';
			if($view != $this->calendar->get_view())
			{
				$link_params = array('view'=>$view,'end_date'=>'');
				if(in_array($view,$this->views_no_index))
					$link_params['no_search'] = 1;
				$opener = '<a href="'.$this->construct_link($link_params).'">';
				$closer = '</a>';
			}
			else
			{
				$opener = '<strong>';
				$closer = '</strong>';
				$on_defined_view = true;
			}
			
			$ret .= $opener.prettify_string($view_name).$closer;
			$ret .= '</li>'."\n";
		}
		if(!$on_defined_view)
		{
			$ret .= '<li><strong>'.$this->get_scope('-').'</strong></li>'."\n";
		}
		$ret .= '</ul>'."\n";
		$ret .= '</div>'."\n";
		return $ret;
	} // }}}
	
	
	function get_all_categories() // {{{
	{
		$ret = '';
		$cs = new entity_selector($this->parent->site_id);
		$cs->description = 'Selecting all categories on the site';
		$cs->add_type(id_of('category_type'));
		$cs->set_order('entity.name ASC');
		$cats = $cs->run_one();
		$cats = $this->check_categories($cats);
		$ret .= '<div class="categories';
		if ($this->calendar->get_view() == "all")
			$ret .= ' divider';
		$ret .= '">'."\n";
		$ret .= '<h4>Event Categories</h4>'."\n";
		$ret .= '<ul>'."\n";
		$ret .= '<li>';
		$used_cats = $this->calendar->get_categories();
			if (empty( $used_cats ))
				$ret .= '<strong>All</strong>';
			else
				$ret .= '<a href="'.$this->construct_link(array('category'=>'','view'=>'')).'" title="Events in all categories">All</a>';
		$ret .= '</li>';
		foreach($cats as $cat)
		{
			$ret .= '<li>';
			if (array_key_exists($cat->id(), $this->calendar->get_categories()))
				$ret .= '<strong>'.$cat->get_value('name').'</strong>';
			else
				$ret .= '<a href="'.$this->construct_link(array('category'=>$cat->id(),'view'=>'','no_search'=>'1')).'" title="'.ucfirst(strtolower($cat->get_value('name'))).' events">'.$cat->get_value('name').'</a>';
			$ret .= '</li>';
		}
		$ret .= '</ul>'."\n";
		$ret .= '</div>'."\n";
		return $ret;
	} // }}}
	
	function check_categories($cats)
	{
		if($this->params['limit_to_page_categories'])
		{
			$or_cats = $this->calendar->get_or_categories();
			if(!empty($or_cats))
			{
				foreach($cats as $id=>$cat)
				{
					if(!array_key_exists($id,$or_cats))
					{
						unset($cats[$id]);
					}
				}
			}
		}
		$setup_es = new entity_selector($this->parent->site_id);
		$setup_es->add_type( id_of('event_type') );
		$setup_es->set_env('site_id',$this->parent->site_id);
		$setup_es = $this->alter_categories_checker_es($setup_es);
		$setup_es->set_num(1);
		$rel_id = relationship_id_of('event_to_event_category');
		foreach($cats as $id=>$cat)
		{
			$es = carl_clone($setup_es);
			$es->add_left_relationship( $id, $rel_id);
			$results = $es->run_one();
			if(empty($results))
			{
				unset($cats[$id]);
			}
			$results = array();
		}
		return $cats;
	}
	
	function alter_categories_checker_es($es)
	{
		return $es;
	}
	
	function init_audiences()
	{
		if(REASON_USES_DISTRIBUTED_AUDIENCE_MODEL)
		{
			$es = new entity_selector($this->parent->site_id);
		}
		else
		{
			$es = new entity_selector();
		}
		$es->set_order('sortable.sort_order ASC');
		$audiences = $es->run_one(id_of('audience_type'));
		$event_type_id = id_of('event_type');
		$rel_id = relationship_id_of('event_to_audience');
		if($this->limit_to_current_site)
			$setup_es = new entity_selector($this->parent->site_id);
		else
			$setup_es = new entity_selector();
		$setup_es->set_num(1);
		$setup_es->add_type($event_type_id);
		$setup_es = $this->alter_audiences_checker_es($setup_es);
		foreach($audiences as $id=>$audience)
		{
			$es = carl_clone($setup_es);
			$es->add_left_relationship($id, $rel_id);
			$auds = $es->run_one();
			if(empty($auds))
				unset($audiences[$id]);
		}
		$this->audiences = $audiences;
	}
	
	function alter_audiences_checker_es($es)
	{
		return $es;
	}
	
	function get_audiences() // {{{
	{
		$ret = '';
		$ret .= '<div class="audiences">'."\n";
		$ret .= '<h4>View Events for:</h4>'."\n";
		$ret .= '<ul>'."\n";
		$ret .= '<li>';
		$this->init_audiences();
		$used_auds = $this->calendar->get_audiences();
		if (empty($used_auds))
			$ret .= '<strong>All Groups</strong>';
		else
			$ret .= '<a href="'.$this->construct_link(array('audience'=>'','view'=>'')).'" title="Events for all groups">All Groups</a>';
		$ret .= '</li>';
		foreach ($this->audiences as $id=>$audience)
		{
			$ret .= '<li>';
			if (array_key_exists($id, $used_auds))
				$ret .= '<strong>'.$audience->get_value('name').'</strong>';
			else
				$ret .= '<a href="'.$this->construct_link(array('audience'=>$id,'no_search'=>'1','view'=>'')).'" title="Events for '.strtolower($audience->get_value('name')).'">'.$audience->get_value('name').'</a>';
			$ret .= '</li>';
		}
		$ret .= '</ul>'."\n";
		
		$ret .= '</div>'."\n";
		return $ret;
	} // }}}
	
	function generate_next_and_previous_links() // {{{
	{
		if ($this->calendar->get_view() != 'all')
		{
			$show_links = true;
			$prev_u = 0;
			$start_array = explode('-',$this->calendar->get_start_date() );
			$end_array = explode('-',$this->calendar->get_end_date() );
			if( $this->calendar->get_view() == 'daily' )
			{
				$prev_u = get_unix_timestamp($this->calendar->get_start_date()) - 60*60*24;
				$next_u = get_unix_timestamp($this->calendar->get_start_date()) + 60*60*24;
				$word = '';
			}
			elseif($this->calendar->get_view() == 'weekly')
			{
				$prev_u = get_unix_timestamp($this->calendar->get_start_date()) - 60*60*24*7;
				$next_u = get_unix_timestamp($start_array[0].'-'.$start_array[1].'-'.str_pad($start_array[2]+7, 2, "0", STR_PAD_LEFT));
				$word = 'Week';
			}
			elseif($this->calendar->get_view() == 'monthly')
			{
				$prev_u = get_unix_timestamp($start_array[0].'-'.str_pad($start_array[1]-1, 2, "0", STR_PAD_LEFT).'-'.$start_array[2]);
				$next_u = get_unix_timestamp($start_array[0].'-'.str_pad($start_array[1]+1, 2, "0", STR_PAD_LEFT).'-'.$start_array[2]);
				$word = 'Month';
			}
			elseif($this->calendar->get_view() == 'yearly')
			{
				$prev_u = get_unix_timestamp($start_array[0]-1 .'-'.$start_array[1].'-'.$start_array[2]);
				$next_u = get_unix_timestamp($start_array[0]+1 .'-'.$start_array[1].'-'.$start_array[2]);
				$word = 'Year';
			}
			else
			{
				$show_links = false;
			}
			if($show_links)
			{
				$prev_start = date('Y-m-d', $prev_u);
				$next_start = date('Y-m-d', $next_u);
				
				$starting = '';
				if($this->calendar->get_view() != 'daily')
					$starting = ' Starting';
					
				$format_prev_year = '';
				if (date('Y', $prev_u) != date('Y'))
				{
					$format_prev_year = ', Y';
				}
				
				$format_next_year = '';
				if (date('Y', $next_u) != date('Y'))
					$format_next_year = ', Y';	
				if($this->calendar->contains_any_events_before($this->calendar->get_start_date()) )
				{
					$this->next_and_previous_links = '<a class="previous" href="';
					$link_params = array('start_date'=>$prev_start,'view'=>$this->calendar->get_view());
					if(in_array($this->calendar->get_view(),$this->views_no_index))
						$link_params['no_search'] = 1;
					$this->next_and_previous_links .= $this->construct_link($link_params);
					if(date('M', $prev_u) == 'May') // All months but may need a period after them
						$punctuation = '';
					else
						$punctuation = '.';
					$this->next_and_previous_links .= '" title="View '.$word.$starting.' '.date('M'.$punctuation.' j'.$format_prev_year, $prev_u).'">';
					$this->next_and_previous_links .= '&laquo;</a> &nbsp; ';
				}
			}
			$this->next_and_previous_links .= '<strong>'.$this->get_scope().'</strong>';
			if($show_links && $this->calendar->contains_any_events_after($next_start) )
			{
				$this->next_and_previous_links .= ' &nbsp; <a class="next" href="';
				$link_params = array('start_date'=>$next_start,'view'=>$this->calendar->get_view());
				if(in_array($this->calendar->get_view(),$this->views_no_index))
						$link_params['no_search'] = 1;
				$this->next_and_previous_links .= $this->construct_link($link_params);
				if(date('M', $next_u) == 'May') // All months but may need a period after them
					$punctuation = '';
				else
					$punctuation = '.';
				$this->next_and_previous_links .= '" title="View '.$word.$starting.' '.date('M'.$punctuation.' j'.$format_next_year, $next_u).'">';
				$this->next_and_previous_links .= '&raquo;</a>'."\n";
			}
		}
		else
			$this->next_and_previous_links = '';
	} // }}}
	
	
	function get_scope($through = 'through', $month_format = 'M') // {{{
	{
		$scope = '';
		$format_start_year = '';
		$format_end_year = '';
		if ((prettify_mysql_datetime($this->calendar->get_start_date(), 'Y') != prettify_mysql_datetime($this->calendar->get_end_date(), 'Y'))
			|| ($this->calendar->get_view() == 'daily' && (prettify_mysql_datetime($this->calendar->get_start_date(), 'Y') != date('Y'))))
			$format_start_year = ', Y';
		
		if($month_format != 'M' || prettify_mysql_datetime($this->calendar->get_start_date(), 'M') == 'May') // All months but may need a period after them if month format is "M"
			$punctuation = '';
		else
			$punctuation = '.';
		$scope .= prettify_mysql_datetime($this->calendar->get_start_date(), $month_format.$punctuation.' j'.$format_start_year);
		if($this->calendar->get_start_date() == $this->today)
			$scope .= ' (Today)';
		if($this->calendar->get_view() != 'daily' && $this->calendar->get_start_date() != $this->calendar->get_end_date())
		{
			if ((prettify_mysql_datetime($this->calendar->get_start_date(), 'Y') != prettify_mysql_datetime($this->calendar->get_end_date(), 'Y')) || (prettify_mysql_datetime($this->calendar->get_end_date(), 'Y') != date('Y')))
				$format_end_year = ', Y';
			if($month_format != 'M' || prettify_mysql_datetime($this->calendar->get_end_date(), 'M') == 'May') // All months but may need a period after them
				$punctuation = '';
			else
				$punctuation = '.';
			$scope .= ' '.$through.' '.prettify_mysql_datetime($this->calendar->get_end_date(), $month_format.$punctuation.' j'.$format_end_year);
			if($this->calendar->get_end_date() == $this->today)
				$scope .= ' (Today)';
		}
		return $scope;
	} // }}}
	
	
	function get_archive_toggler() // {{{
	{
		$ret = '';
		if($this->calendar->get_start_date() >= $this->today)
		{
			$new_start = date('Y-m-d', strtotime($this->calendar->get_start_date().' -1 month') );
			$ret .= '<div class="archive"><a href="'.$this->construct_link(array('start_date'=>$new_start, 'view'=>'monthly') ).'">View Archived Events</a></div>';
		}
		elseif($this->calendar->contains_any_events_after($this->yesterday))
			$ret .= '<div class="archive"><a href="'.$this->construct_link(array('start_date'=>$this->today, 'view'=>'')).'">View Upcoming Events</a></div>';
		return $ret;
	} // }}}
	
	
	function get_today_link() // {{{
	{
		if($this->calendar->get_start_date() > $this->today && $this->calendar->contains_any_events_after($this->yesterday))
		return '<div class="today"><a href="'.$this->construct_link(array('start_date'=>$this->today)).'">Today\'s Events</a></div>'."\n";
	} // }}}
	
	function show_calendar_grid()
	{
		if($this->show_calendar_grid)
		{
			if(empty($this->calendar_grid_markup))
			{
				$this->generate_calendar_grid_markup();
			}
			echo $this->calendar_grid_markup;
		}
	}
	function generate_calendar_grid_markup()
	{
		$grid = new calendar_grid();
		$start_day_on_cal = false;
		if(!empty($this->request['nav_date']))
		{
			$nav_date = $this->request['nav_date'];
			if(substr($nav_date,0,7) == substr($this->calendar->get_start_date(),0,7) )
				$start_day_on_cal = true;
		}
		else
		{
			$nav_date = $this->calendar->get_start_date();
			$start_day_on_cal = true;
		}
		$date_parts = explode('-',$nav_date);
		$grid->set_year($date_parts[0]);
		$grid->set_month($date_parts[1]);
		if($start_day_on_cal)
		{
			$grid->set_day($date_parts[2]);
		}
		$grid->set_linked_dates($this->get_calendar_grid_links($date_parts[0], $date_parts[1]) );
		
		if($this->calendar->contains_any_events_before($date_parts[0].'-'.$date_parts[1].'-01'))
		{
			$prev_u = get_unix_timestamp($date_parts[0].'-'.str_pad($date_parts[1]-1, 2, "0", STR_PAD_LEFT).'-'.$date_parts[2]);
			$prev_date = carl_date('Y-m-d',$prev_u);
			$grid->set_previous_month_query_string($this->construct_link(array('nav_date'=>$prev_date,'no_search'=>'1' ) ) );
		}
		if($this->calendar->contains_any_events_after($date_parts[0].'-'.$date_parts[1].'-31'))
		{
			$next_u = get_unix_timestamp($date_parts[0].'-'.str_pad($date_parts[1]+1, 2, "0", STR_PAD_LEFT).'-'.$date_parts[2]);
			$next_date = carl_date('Y-m-d',$next_u);
			$grid->set_next_month_query_string($this->construct_link(array('nav_date'=>$next_date,'no_search'=>'1' ) ) );
		}
		
		$nav_month = substr($nav_date,0,7);
		
		$start_month = substr($this->calendar->get_start_date(),0,7);
		$start_day = intval(substr($this->calendar->get_start_date(),8,2));
		
		$end_month = substr($this->calendar->get_end_date(),0,7);
		$end_day = intval(substr($this->calendar->get_end_date(),8,2));
		
		if(!($start_month > $nav_month || $end_month < $nav_month))
		{
			if($start_month == $nav_month)
			{
				$first_day_in_view = $start_day;
				$grid->add_class_to_dates('startDate', array($start_day));
			}
			else
			{
				$first_day_in_view = 1;
			}
			if($end_month == $nav_month)
			{
				$last_day_in_view = $end_day;
			}
			else
			{
				$last_day_in_view = 31;
			}
			
			$viewing_days = array();
			for($i = $first_day_in_view; $i <= $last_day_in_view; $i++)
			{
				$viewing_days[] = $i;
			}
			$grid->add_class_to_dates('currentlyViewing', $viewing_days);
			
		}
		$days_with_events = $this->get_days_with_events($date_parts[0], $date_parts[1]);
		if(!empty($days_with_events))
		{
			$grid->add_class_to_dates('hasEvent', array_keys($days_with_events));
		}
		$this->calendar_grid_markup = $grid->get_calendar_markup();
	}
	function get_calendar_grid_links($year, $month)
	{
		$links = array();
		$weeks = get_calendar_data_for_month( $year, $month );
		if(!empty($this->request['view']) && $this->request['view'] != 'all')
			$pass_view_val = $this->request['view'];
		else
			$pass_view_val = '';
		foreach($weeks as $week)
		{
			foreach($week as $day)
			{
				$date = $year.'-'.$month.'-'.str_pad($day,2,'0',STR_PAD_LEFT);
				$links[$day] =  $this->construct_link(array('start_date'=>$date,'view'=>$pass_view_val,'no_search'=>'1'));
			}
		}
		return $links;
	}
	function get_days_with_events($year, $month)
	{
		$first_day_in_month = $year.'-'.$month.'-01';
		$init_array = $this->make_reason_calendar_init_array($first_day_in_month, '', 'monthly');
		$cal = new reasonCalendar($init_array);
		$cal->run();
		$days = $cal->get_all_days();
		$counts = array();
		foreach($days as $day=>$ids)
		{
			$num = count($ids);
			if($num > 0)
			{
				$counts[intval(substr($day,8,2))] = $num;
			}
		}
		return $counts;
	}
	function show_date_picker()
	{
		$start = $this->calendar->get_start_date();
		$cur_month = substr($start,5,2);
		$cur_day = substr($start,8,2);
		$cur_year = substr($start,0,4);
		/* $min = $this->calendar->get_min_date();
		$min_year = substr($min,0,4); */
		$min_year = $this->get_min_year();
		/* $max = $this->calendar->get_max_date();
		substr($max,0,4); */
		$max_year = $this->get_max_year();
		echo '<div class="dateJump">'."\n";
		echo '<form action="'.get_current_url().'" method="post">'."\n";
		echo '<h4>Select date:</h4>';
		echo '<span style="white-space:nowrap;">'."\n";
		echo '<select name="start_month">'."\n";
		for($m = 1; $m <= 12; $m++)
		{
			$m_padded = str_pad($m,2,'0',STR_PAD_LEFT);
			 $month_name = prettify_mysql_datetime('1970-'.$m_padded.'-01','M');
			 echo '<option value="'.$m_padded.'"';
			 if($m_padded == $cur_month)
			 	echo ' selected="selected"';
			 echo '>'.$month_name.'</option>'."\n";
		}
		echo '</select>'."\n";
		echo '<select name="start_day">'."\n";
		for($d = 1; $d <= 31; $d++)
		{
			 echo '<option value="'.$d.'"';
			 if($d == $cur_day)
			 	echo ' selected="selected"';
			 echo '>'.$d.'</option>'."\n";
		}
		echo '</select>'."\n";
		echo '<select name="start_year">'."\n";
		for($y = $min_year; $y <= $max_year; $y++)
		{
			 echo '<option value="'.$y.'"';
			 if($y == $cur_year)
			 	echo ' selected="selected"';
			 echo '>'.$y.'</option>'."\n";
		}
		echo '</select>'."\n";
		echo '</span>'."\n";
		if(!empty($this->request['view']))
			echo '<input type="hidden" name="view" value="" />'."\n";
		echo '<input type="submit" name="go" value="go" />'."\n";
		echo '</form>'."\n";
		echo '</div>'."\n";
	}
	function get_max_year()
	{
		if(!empty($this->max_year))
			return $this->max_year;
		//$year = substr($this->calendar->get_start_date(),0,4);
		$year = carl_date('Y');
		$max_year = NULL;
		$max_found_so_far = $year;
		for($i=2; $i < 64; $i = $i*2)
		{
			//echo ($year+$i.'<br />');
			if($this->calendar->contains_any_events_after($year+$i.'-01-01'))
			{
				$max_found_so_far = $year+$i;
				continue;
			}
			else
			{
				$max_year = $this->refine_get_max_year($year+$i, $max_found_so_far);
				break;
			}
		}
		if(empty($max_year))
			$max_year = $year + $i;
		$this->max_year = $max_year;
		return $max_year;
	}
	function refine_get_max_year($year_outside_bounds, $year_inside_bounds, $depth = 1)
	{
		if($depth > 4)
			return $year_outside_bounds;
		$median_year = floor(($year_outside_bounds + $year_inside_bounds)/2);
		//echo $median_year;
		if($median_year == $year_inside_bounds)
			return $year_inside_bounds;
		if($this->calendar->contains_any_events_after($median_year.'-01-01'))
		{
			return $this->refine_get_max_year($year_outside_bounds, $median_year, $depth++);
		}
		else
		{
			return $this->refine_get_max_year($median_year, $year_inside_bounds, $depth++);
		}
		
	}
	function get_min_year()
	{
		if(!empty($this->min_year))
		{
			return $this->min_year;
		}
		$year = carl_date('Y');
		//echo 'start: '.$year.'<br />';
		$min_year = NULL;
		$min_found_so_far = $year;
		for( $i=2; $i < 65; $i = $i*2 )
		{
			//echo 'testing: '. ( $year - $i ) .'<br />';
			if($this->calendar->contains_any_events_before(($year-$i).'-01-01'))
			{
				$min_found_so_far = $year - $i;
				continue;
			}
			else
			{
				$min_year = $this->refine_get_min_year($year-$i, $min_found_so_far);
				break;
			}
		}
		if(empty($min_year))
			$min_year = $year - $i;
		$this->min_year = $min_year;
		return $min_year;
	}
	function refine_get_min_year($year_outside_bounds, $year_inside_bounds, $depth = 1)
	{
		//echo 'yob: '.$year_outside_bounds.'<br />';
		//echo 'yib: '.$year_inside_bounds.'<br />';
		if($depth > 4)
			return $year_outside_bounds;
		$median_year = ceil(($year_outside_bounds + $year_inside_bounds)/2);
		//echo $median_year;
		if($median_year == $year_inside_bounds)
			return $year_outside_bounds;
		if($this->calendar->contains_any_events_before($median_year.'-01-01'))
		{
			return $this->refine_get_min_year($year_outside_bounds, $median_year, $depth++);
		}
		else
		{
			return $this->refine_get_min_year($median_year, $year_inside_bounds, $depth++);
		}
		
	}
	
	function show_search()
	{
		echo '<div class="search">'."\n";
		echo '<h4><label for="calendar_search">Search:</label></h4>'."\n";
		echo $this->get_search_form();
		echo $this->get_search_other_actions();
		echo '</div>'."\n";
	}
	
	function get_search_form($input_id = 'calendar_search',$use_val_for_width = false)
	{
		$ret = '';
		$ret .= '<form action="?" method="get">'."\n";
		$width = 10;
		if(!empty($this->request['search']))
		{
			$val = htmlspecialchars($this->request['search']);
			if($use_val_for_width)
			{
				$width = ceil(strlen($this->request['search'])*0.8);
				if($width > 10)
				{
					$width = 10;
				}
			}
		}
		else
			$val = '';
		$ret .= '<input type="text" name="search" class="search" id="'.$input_id.'" value="'.$val.'" size="'.$width.'" />'."\n";
		$ret .= '<input type="submit" name="go" value="go" />'."\n";
		foreach($this->passables as $passable)
		{
			if(!empty($this->request[$passable]) && !in_array($passable,array('search','view','end_date') ) )
				$ret .= '<input type="hidden" name="'.$passable.'" value="'.htmlspecialchars($this->request[$passable]).'" />'."\n";
		}
		$ret .= '</form>'."\n";
		return $ret;
	}
	function get_search_other_actions()
	{
		$ret = '';
		if(!empty($this->request['search']))
		{
			$ret .= '<div class="otherActions">'."\n";
			$ret .= '<span class="clear"><a href="'.$this->construct_link(array('search'=>'','view'=>'')).'">Clear search</a></span> | '."\n";
			if($this->calendar->get_start_date() > $this->get_min_year().'-01-01')
			{
				$ret .= '<span class="toArchive"><a href="'.$this->construct_link(array('start_date'=>$this->get_min_year().'-01-01','view'=>'')).'">Search archived events for <em class="searchTerm">"'.htmlspecialchars($this->request['search']).'"</em></a></span>'."\n";
			}
			else
			{
				$ret .= '<span class="toCurrent"><a href="'.$this->construct_link(array('start_date'=>$this->today,'view'=>'')).'">Search upcoming events for <em class="searchTerm">"'.htmlspecialchars($this->request['search']).'"</em></a></span>'."\n";
			}
			$ret .= '</div>'."\n";
		}
		return $ret;
	}
	
	function make_reason_calendar_init_array($start_date, $end_date = '', $view = '')
	{
		$init_array = array();
		if($this->limit_to_current_site)
		{
			$init_array['site'] = $this->parent->site_info;
		}
		if(!empty($start_date))
			$init_array['start_date'] = $start_date;
		if(!empty($end_date))
		{
			$init_array['end_date'] = $end_date;
		}
		elseif(!empty($view))
		{
			$init_array['view'] = $view;
		}
		if(!empty($this->pass_vars['audience']))
		{
			$audience = new entity($this->pass_vars['audience']);
			$init_array['audiences'] = array( $audience->id()=>$audience );
		}
		if(!empty($this->pass_vars['category']))
		{
			$category = new entity($this->pass_vars['category']);
			$init_array['categories'] = array( $category->id()=>$category );
		}
		if($this->params['limit_to_page_categories'])
		{
			$es = new entity_selector( $this->parent->site_id );
			$es->description = 'Selecting categories for this page';
			$es->add_type( id_of('category_type') );
			$es->set_env('site',$this->parent->site_id);
			$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_category') );
			$cats = $es->run_one();
			if(!empty($cats))
			{
				$init_array['or_categories'] = $cats;
			}
		}
		if(!empty($this->ideal_count))
			$init_array['ideal_count'] = $this->ideal_count;
			
		$init_array['automagic_window_snap_to_nearest_view'] = $this->snap_to_nearest_view;
		
		if(!empty($this->request['search']))
		{
			$init_array['simple_search'] = $this->request['search'];
		}
		
		return $init_array;
	}
	
	
	function show_feed_link()
	{
		$type = new entity(id_of('event_type'));
		if($type->get_value('feed_url_string'))
			echo '<div class="feedInfo"><a href="'.$this->parent->site_info->get_value('base_url').MINISITE_FEED_DIRECTORY_NAME.'/'.$type->get_value('feed_url_string').'" title="RSS feed for this site\'s events">xml</a></div>';
	}
	function show_list_export_links()
	{
		echo '<div class="iCalExport">'."\n";
		
		/* If they are looking at the current view or a future view, start date in link should be pinned to current date.
			If they are looking at an archive view, start date should be pinned to the start date they are currently viewing */
		
		$start_date = $this->today;
		if(!empty($this->request['start_date']) && $this->request['start_date'] < $this->today)
		{
			$start_date = $this->request['start_date'];
		}
		
		$query_string = $this->construct_link(array('start_date'=>$start_date,'view'=>'','end_date'=>'','format'=>'ical'));
		if(!empty($this->request['category']) || !empty($this->request['audience']) || !empty($this->request['search']))
		{
			$subscribe_text = 'Subscribe to this view in desktop calendar';
			$download_text = 'Download these events (.ics)';
		}
		else
		{
			$subscribe_text = 'Subscribe to this calendar';
			$download_text = 'Download events (.ics)';
		}
		echo '<a href="webcal://'.REASON_HOST.$this->parent->pages->get_full_url( $this->page_id ).$query_string.'">'.$subscribe_text.'</a>';
		if(!empty($this->events))
			echo ' | <a href="'.$query_string.'">'.$download_text.'</a>';
		echo '</div>'."\n";
	}
	
	
	///////////////////////////////////////////
	// Showing a Specific Event
	///////////////////////////////////////////
	
	function init_event() // {{{
	{
		$this->event = new entity($this->request['event_id']);
		if ($this->event_ok_to_show($this->event))
		{
			if(!empty($this->request['format']) && $this->request['format'] == 'ical')
			{
				$event = carl_clone($this->event);
				if(!empty($this->request['date']))
				{
					$event->set_value('recurrence','none');
					$event->set_value('datetime',$this->request['date'].' '.prettify_mysql_datetime($event->get_value('datetime'), 'H:i:s'));
				}
				$this->export_ical(array($event));
			}
			else
			{
				$this->_add_crumb( $this->event->get_value( 'name' ) );
				$this->parent->pages->make_current_page_a_link();
				if($this->event->get_value('keywords'))
				{
						$this->parent->add_head_item('meta',array( 'name' => 'keywords', 'content' => htmlspecialchars($this->event->get_value('keywords'),ENT_QUOTES,'UTF-8')));
				}
			}
		}
	} // }}}
	function export_ical($events)
	{
		while(ob_get_level() > 0)
			ob_end_clean();
		$ical = $this->get_ical($events);
		$size_in_bytes = strlen($ical);
		if(count($events) > 1)
			$filename = 'events.ics';
		else
			$filename = 'event.ics';
		$ic = new reason_iCalendar();
		header( $ic->get_icalendar_header() );
		header('Content-Disposition: attachment; filename='.$filename.'; size='.$size_in_bytes);
		echo $ical;
		die();
	}
	function get_ical($events)
	{
		if(!is_array($events))
		{
			trigger_error('get_ical needs an array of event entities');
			return '';
		}
		
		$calendar = new reason_iCalendar();
  		$calendar -> set_events($events);
		
  		return $calendar -> get_icalendar_events();
	}
	function show_event() // {{{
	{
		if ($this->event_ok_to_show($this->event))
			$this->show_event_details();
		else
			$this->show_event_error();
	} // }}}
	function event_ok_to_show($event)
	{
		if ($event->get_values() 
		&& ($event->get_value('type') == id_of('event_type')) 
		&& ($event->get_value('show_hide') == 'show') 
		&& $event->get_value('state') == 'Live'
		&& ( !$this->limit_to_current_site || site_owns_entity( $this->site_id, $event->id() ) || site_borrows_entity( $this->site_id, $event->id() ) )
		)
			return true;
		else
			return false;
	}
	function show_event_details() // {{{
	{
		$e =& $this->event;
		echo '<div class="eventDetails">'."\n";
		$this->show_back_link();
		$this->show_images($e);
		echo '<h3>'.$e->get_value('name').'</h3>'."\n";
		$this->show_ownership_info($e);
		if ($e->get_value('description'))
			echo '<p class="description">'.$e->get_value( 'description' ).'</p>'."\n";
		$this->show_repetition_info($e);
		if (!empty($this->request['date']) && strstr($e->get_value('dates'), $this->request['date']))
			echo '<p class="date"><strong>Date:</strong> '.prettify_mysql_datetime( $this->request['date'], "l, F jS, Y" ).'</p>'."\n";
		if(substr($e->get_value( 'datetime' ), 11) != '00:00:00')
			echo '<p class="time"><strong>Time:</strong> '.prettify_mysql_datetime( $e->get_value( 'datetime' ), "g:i a" ).'</p>'."\n";
		$this->show_duration($e);
		if ($e->get_value('location'))
			echo '<p class="location"><strong>Location:</strong> '.$e->get_value('location').'</p>'."\n";
		if ($e->get_value('sponsor'))
			echo '<p class="sponsor"><strong>Sponsored by:</strong> '.$e->get_value('sponsor').'</p>'."\n";
		$this->show_contact_info($e);
		if($this->show_icalendar_links)
			$this->show_item_export_link($e);
		if ($e->get_value('content'))
			echo '<div class="eventContent">'.$e->get_value( 'content' ).'</div>'."\n";
		$this->show_dates($e);
		if ($e->get_value('url'))
			echo '<div class="eventUrl"><strong>For more information, visit:</strong> <a href="'.$e->get_value( 'url' ).'">'.$e->get_value( 'url' ).'</a>.</div>'."\n";
		//$this->show_back_link();
		$this->show_event_categories($e);
		$this->show_event_audiences($e);
		$this->show_event_keywords($e);
		echo '</div>'."\n";
	} // }}}
	function show_event_error() // {{{
	{
		echo '<p>We\'re sorry; the event requested does not exist or has been removed from this calendar. This may be due to incorrectly typing in the page address; if you believe this is a bug, please report it to the contact person listed at the bottom of the page.</p>';
		$this->init_list();
		$this->list_events();
	} // }}}
	function show_duration(&$e) // {{{
	{
		if ($e->get_value( 'hours' ) || $e->get_value( 'minutes' ))
		{
			echo '<p class="duration"><strong>Duration:</strong> ';
			if ($e->get_value( 'hours' ))
			{
				if ( $e->get_value( 'hours' ) > 1 )
					$hour_word = 'hours';
				else
					$hour_word = 'hour';
				echo $e->get_value( 'hours' ).' '.$hour_word;
				if ($e->get_value( 'minutes' ))
					echo ', ';
			}
			if ($e->get_value( 'minutes' ))
			{
				echo $e->get_value( 'minutes' ).' minutes';
			}
			echo '</p>'."\n";
		}
	} // }}}
	function show_ownership_info(&$e)
	{
		$owner_site = $e->get_owner();
		if($owner_site->id() != $this->parent->site_info->id())
		{
			$modules = array('events','events_verbose','events_archive');
			$page_types = array();
			foreach($modules as $module)
			{
				$pts = page_types_that_use_module($module);
				foreach($pts as $pt)
				{
					if(!in_array($pt,$page_types))
						$page_types[] = $pt;
				}
			}
			$tree = NULL;
			$link = get_page_link($owner_site, $tree, $page_types, true);
			echo '<p>From site: <a href="'.$link.'">'.$owner_site->get_value('name').'</a></p>'."\n";
		}
	}
	function show_contact_info(&$e) // {{{
	{
		$contact = $e->get_value('contact_username');
		if(!empty($contact) )
		{
			$dir = new directory_service();
			$dir->search_by_attribute('ds_username', array(trim($contact)), array('ds_email','ds_fullname','ds_phone',));
			$email = $dir->get_first_value('ds_email');
			$fullname = $dir->get_first_value('ds_fullname');
			$phone = $dir->get_first_value('ds_phone');
			
			echo '<p class="contact"><strong>Contact:</strong> ';
			if(!empty($email))
				echo '<a href="mailto:'.$email.'">';
			if(!empty($fullname))
				echo $fullname;
			else
				echo $contact;
			if(!empty($email))
				echo '</a>';
			if ($e->get_value('contact_organization'))
				echo ', '.$e->get_value('contact_organization');
			if (!empty($phone))
				echo ', '.$phone;
			echo '</p>'."\n";
		}
	} // }}}
	function show_repetition_info(&$e) // {{{
	{
		$rpt = $e->get_value('recurrence');
		$freq = '';
		$words = array();
		$dates_text = '';
		$occurence_days = array();
		if (!($rpt == 'none' || empty($rpt)))
		{
			$words = array('daily'=>array('singular'=>'day','plural'=>'days'),
							'weekly'=>array('singular'=>'week','plural'=>'weeks'),
							'monthly'=>array('singular'=>'month','plural'=>'months'),
							'yearly'=>array('singular'=>'year','plural'=>'years'),
					);
			if ($e->get_value('frequency') <= 1)
				$sp = 'singular';
			else
			{
				$sp = 'plural';
				$freq = $e->get_value('frequency').' ';
			}
			if ($rpt == 'weekly')
			{
				$days_of_week = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
				foreach($days_of_week as $day)
				{
					if($e->get_value($day))
						$occurence_days[] = $day;
				}
				$last_day = array_pop($occurence_days);
				$dates_text = ' on ';
				if (!empty( $occurence_days ) )
				{
					$comma = '';
					if(count($occurence_days) > 2)
						$comma = ',';
					$dates_text .= ucwords(implode(', ', $occurence_days)).$comma.' and ';
				}
				$dates_text .= prettify_string($last_day);
			}
			elseif ($rpt == 'monthly')
			{
				$suffix = array(1=>'st',2=>'nd',3=>'rd',4=>'th',5=>'th');
				if ($e->get_value('week_of_month'))
				{
					$dates_text = ' on the '.$e->get_value('week_of_month');
					$dates_text .= $suffix[$e->get_value('week_of_month')];
					$dates_text .= ' '.$e->get_value('month_day_of_week');
				}
				else
					$dates_text = ' on the '.prettify_mysql_datetime($e->get_value('datetime'), 'jS').' day of the month';
			}
			elseif ($rpt == 'yearly')
			{
				$dates_text = ' on '.prettify_mysql_datetime($e->get_value('datetime'), 'F jS');
			}
			echo '<p class="repetition">This event takes place each ';
			echo $freq;
			echo $words[$rpt][$sp];
			echo $dates_text;
			echo ' from '.prettify_mysql_datetime($e->get_value('datetime'), 'F jS, Y').' to '.prettify_mysql_datetime($e->get_value('last_occurence'), 'F jS, Y').'.';
			
			echo '</p>'."\n";
		}
			
	} // }}}
	function show_dates(&$e)
	{
		$dates = explode(', ', $e->get_value('dates'));
		if(count($dates) > 1 || empty($this->request['date']) || !strstr($e->get_value('dates'), $this->request['date']))
		{
			echo '<div class="dates"><h4>This event occurs on:</h4>'."\n";
			echo '<ul>'."\n";
			foreach($dates as $date)
			{
				echo '<li>'.prettify_mysql_datetime( $date, "l, F jS, Y" ).'</li>'."\n";
			}
			echo '</ul>'."\n";
			echo '</div>'."\n";
		}
	}
	function show_item_export_link($e) {
		echo '<div class="export">'."\n";
		if($e->get_value('recurrence') == 'none' || empty($this->request['date']))
		{
			echo '<a href="'.$this->construct_link(array('event_id'=>$e->id(),'format'=>'ical')).'">Import into your calendar program</a>';
		}
		else
		{
			echo 'Add to your calendar: <a href="'.$this->construct_link(array('event_id'=>$e->id(),'format'=>'ical','date'=>$this->request['date'])).'">This occurence</a> | <a href="'.$this->construct_link(array('event_id'=>$e->id(),'format'=>'ical','date'=>'')).'">All occurrences</a>';
		}
		echo '</div>'."\n";
	}
	function show_item_add_to_personal_calendar_interface($e)
	{
		echo '<div class="addToPersonalCalendar">';
		echo '<a href="'.$this->construct_link(array('event_id'=>$e->id(),'add'=>'true')).'">Add to my personal calendar</a>';
		echo '</div>'."\n";
	}
	function show_back_link() // {{{
	{
		echo '<p class="back"><a href="'.$this->construct_link().'">Back to event listing</a></p>'."\n";
	} // }}}
	function show_images(&$e)
	{
		$es = new entity_selector();
        $es->description = 'Selecting images for event';
        $es->add_type( id_of('image') );
        $es->add_right_relationship( $e->id(), relationship_id_of('event_to_image') );
        $images = $es->run_one();
		if (!empty($images))
        {
            echo '<div class="images">';
            if (!empty($this->parent->textonly))
                echo '<h4>Images</h4>'."\n";
            foreach( $images AS $image )
            {
                show_image( $image, false, true, true, '', $this->parent->textonly );
            }
            echo "</div>";
        }
	}
	
	function show_event_categories(&$e)
	{
		$es = new entity_selector();
		$es->description = 'Selecting categories for event';
		$es->add_type( id_of('category_type'));
        $es->add_right_relationship( $e->id(), relationship_id_of('event_to_event_category') );
        $cats = $es->run_one();
		if (!empty($cats))
        {
            echo '<div class="categories">';
            echo '<h4>Categories:</h4>'."\n";
			echo '<p>'."\n";
			$links = array();
            foreach( $cats AS $cat )
            {
				$links[] = '<a href="'.$this->construct_link(array('category'=>$cat->id(),'no_search'=>'1'), false).'">'.$cat->get_value('name').'</a>';
            }
			echo implode(', ',$links);
			echo '</p>'."\n";
            echo "</div>";
        }
	}
	function show_event_audiences(&$e)
	{
		$es = new entity_selector();
		$es->description = 'Selecting audiences for event';
		$es->add_type( id_of('audience_type'));
        $es->add_right_relationship( $e->id(), relationship_id_of('event_to_audience') );
		//echo $es->get_one_query();
        $auds = $es->run_one();
		if (!empty($auds))
        {
            echo '<div class="audiences">';
            echo '<h4>Audiences:</h4>'."\n";
			echo '<p>'."\n";
			$links = array();
            foreach( $auds AS $aud )
            {
                $links[] = '<a href="'.$this->construct_link(array('audience'=>$aud->id(),'no_search'=>'1'), false).'">'.$aud->get_value('name').'</a>';
            }
			echo implode(', ',$links);
			echo '</p>'."\n";
            echo "</div>";
        }
	}
	function show_event_keywords(&$e)
	{
		if($e->get_value('keywords'))
		{
			echo '<div class="keywords">';
			echo '<h4>Keywords:</h4>'."\n";
			echo '<p>';
			$keys = explode(',',$e->get_value('keywords'));
			$parts = array();
			foreach($keys as $key)
			{
				$key = trim(strip_tags($key));
				$parts[] = '<a href="'.$this->construct_link(array('search'=>urlencode($key),'no_search'=>'1'),false).'">'.$key.'</a>';
			}
			echo implode(', ',$parts);
			echo '</p>';
			echo '</div>'."\n";
		}
	}
	
	/**
	* Template calls this function to figure out the most recently last modified item on page
	* This function uses the most recently modified event in list if not looking at an individual event
	* If looking at details, it returns last modified info for that the event in question
	* @return mixed last modified value or false
	*/
	function last_modified() // {{{
	{
		if( $this->report_last_modified_date && $this->has_content() )
		{
			if((!empty($this->event)) && $this->event->get_values())
			{
				return $this->event->get_value('last_modified');
			}
			elseif(!empty($this->events_by_date))
			{
				$max_date = '';
				foreach($this->events_by_date as $date=>$events)
				{
					foreach($events as $event_id)
					{
						if($max_date < $this->events[$event_id]->get_value('last_modified'))
						{
							$max_date = $this->events[$event_id]->get_value('last_modified');
						}
					}
				}
				if(!empty($max_date))
				{
					return $max_date;
				}
			}
		}
		return false;
	} // }}}
	
	//////////////////////////////////////
	// Utilities
	//////////////////////////////////////
	
	function construct_link( $vars = array(), $pass_passables = true ) // {{{
	{
		if($pass_passables)
			$link_vars = $this->pass_vars;
		else
		{
			$link_vars = array();
			if(!empty($this->pass_vars['textonly']))
				$link_vars['textonly'] = 1; // always pass the textonly value
		}
		foreach( array_keys($vars) as $key )
		{
			$link_vars[$key] = $vars[$key];
		}
		
		foreach(array_keys($link_vars) as $key)
		{
			$link_vars[$key] = htmlspecialchars($link_vars[$key]);
		}
		return '?'.implode_with_keys('&amp;',$link_vars);
	} // }}}
}
?>
