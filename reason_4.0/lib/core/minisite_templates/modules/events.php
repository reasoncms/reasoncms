<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class & dependencies, and register the module with Reason
  */
reason_include_once( 'minisite_templates/modules/default.php' );
//reason_include_once( 'classes/calendar_new.php' );
reason_include_once( 'classes/calendar.php' );
reason_include_once( 'classes/calendar_grid.php' );
reason_include_once( 'classes/icalendar.php' );
reason_include_once('classes/page_types.php');
include_once(CARL_UTIL_INC . 'cache/object_cache.php');
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsModule';



/**
 * A minisite module that presents a calendar of events
 *
 * By default, this module shows upcoming events on the current site,
 * and proves an interface to see past events
 *
 * @todo develop a templating system to make it easier to customize the output of this module
 */
class EventsModule extends DefaultMinisiteModule
{
	/**
	 * The number of events that the calendar attempts to display if there is no
	 * duration specified. Note that the actual number may be larger or smaller
	 * due to the calendar "snapping" to full days.
	 * @var integer
	 */
	var $ideal_count;
	/**
	 * Callback to a function which operates on the entity selector used by 
	 * the reasonCalendar object to find events (passed by reference as the 
	 * first argument).
	 * @var string
	 */
	var $es_callback;
	/**
	 * The id of the wrapper class around the module
	 * @var string
	 * @access public
	 */
	var $div_id = 'calendar';
	/**
	 * Should the module display options (e.g. categories, audiences, etc.)?
	 * @var boolean
	 */
	var $show_options = true;
	/**
	 * Should the module display navigation (e.g. next/previous links)?
	 * @var boolean
	 */
	var $show_navigation = true;
	/**
	 * Should the module display available views (e.g. all/year/month/day)?
	 * @var boolean
	 */
	var $show_views = true;
	/**
	 * Should the module display a month-grid date picker?
	 * @var boolean
	 */
	var $show_calendar_grid = true;
	/**
	 * Should the module display event times?
	 * @var boolean
	 */
	var $show_times = true;
	/**
	 * The request keys that represent persistent state.
	 *
	 * The module ought to include these request variables in links if they are present.
	 *
	 * @var array
	 * @access private
	 */
	var $passables = array('start_date','textonly','view','category','audience','end_date','search');
	
	/**
	 * Should the module limit events to the current site?
	 *
	 * @deprecated Use additional_sites and sharing_mode parameters instead
	 * @var boolean
	 */
	var $limit_to_current_site = true;
	/**
	 * Key-value pairs (from the request) that should be included in links
	 * unless explicitly cleared.
	 *
	 * These are determined by @passables and represent state that should persist among
	 * distinct page views (again, unless explicitly changed or cleared by the user).
	 *
	 * @var array
	 * @access private
	 */
	var $pass_vars = array();
	/**
	 * Does the module have content to display?
	 *
	 * This should be set in the init phase, as the has_content function needs it to respond correctly.
	 * @var boolean
	 * @access private
	 */
	var $has_content = true;
	/**
	 * The reasonCalendar object that represents the primary set of events being displayed
	 *
	 * @var object
	 * @access private
	 */
	var $calendar;
	/**
	 * The beginning date for events being displayed
	 *
	 * This is populated during init phase.
	 *
	 * @var string A MySQL-formatted date (e.g. YYYY-MM-DD)
	 * @access private
	 */
	var $start_date;
	/**
	 * When calculating the window of time to display (if not given),
	 * should the calendar limit itself to defined views (e.g. day/week/monthyear)?
	 *
	 * @var boolean true to limit to defined views; false to select arbitrary date range
	 */
	var $snap_to_nearest_view = true;
	/**
	 * Events to display
	 *
	 * This is populated during init phase.
	 *
	 * Note that these are date entities, not individual occurrences. Do not iterate through
	 * this array to build the calendar, as you will not handle repeating events correctly.
	 *
	 * @var array of Reason entities
	 * @access private
	 */
	var $events = array();
	/**
	 * Event ids to display, organized by date
	 *
	 * This is populated during init phase.
	 *
	 * @var array in form array('YYYY-MM-DD'=>array('1','2',3'),'YYYY-MM-DD'=>array(...),...)
	 * @access private
	 */
	var $events_by_date = array();
	/**
	 * Should the calendar show months?
	 *
	 * This may be dynamically set in init for certain views (e.g. daily & weekly)
	 *
	 * @var boolean
	 */
	var $show_months = true;
	/**
	 * Place to store the most recent month in which item has been displayed
	 *
	 * This var is what allows the code that lists items to display a month if the month changes.
	 *
	 * @var string in form MM (Jan = '01'; Dec = '12')
	 * @access private
	 */
	var $prev_month;
	/**
	 * Place to store the most recent year in which item has been displayed
	 *
	 * This var is what allows the code that lists items to display a year if the year changes.
	 *
	 * @var string in form YYYY
	 * @access private
	 */
	var $prev_year;
	/**
	 * HTML for the next and previous links
	 *
	 * It turns out that generating these links is surprisingly expensive, so we only do it once, and store the resuts here.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $next_and_previous_links;
	/**
	 * HTML for the calendar grid
	 *
	 * Since generating the grid can be expensive, we store the results here so we don't have to
	 * do it twice.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $calendar_grid_markup = '';
	/**
	 * HTML for the options bar (e.g. categories/audiences/etc.)
	 *
	 * Since generating the options bar can be expensive, we store the results here 
	 * so we don't have to do it twice.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $options_bar;
	/**
	 * Today's date, in MySQL format (YYYY-MM-DD)
	 *
	 * This is set in init.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $today;
	/**
	 * Tomorrow's date, in MySQL format (YYYY-MM-DD)
	 *
	 * This is set in init.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $tomorrow;
	/**
	 * Yesterday's date, in MySQL format (YYYY-MM-DD)
	 *
	 * This is set in init.
	 *
	 * @var string HTML
	 * @access private
	 */
	var $yesterday;
	
	/**
	 * The current event (if viewing an individual event)
	 *
	 * This is set in init.
	 *
	 * @var mixed a Reason event entity or NULL
	 * @access private
	 */
	var $event;
	
	/**
	 * The URL of the events page
	 *
	 * In the base events module, this is empty, but in sidebar-style modules this should be
	 * populated in the init phase with the proper URL.
	 *
	 * @var mixed URL string or NULL
	 */
	var $events_page_url;
	/**
	 * The format for the display of time information in the listing
	 * @var string
	 */
	var $list_time_format = 'g:i a';
	/**
	 * The format for the display of date information in the listing
	 * @var string
	 */
	var $list_date_format = 'l, F jS';
	/**
	 * The audiences that should be available as filters on this calendar
	 * 
	 * This is set in the init phase.
	 *
	 * @var array of reason entities
	 * @access private
	 */
	var $audiences = array();
	/**
	 * Should this calendar show the most recent event if no future events are found?
	 * @var boolean
	 */
	var $rerun_if_empty = true;
	/**
	 * Should this calendar report date of the current event (or event being shown) when
	 * asked for last modified information?
	 * @var boolean
	 */
	var $report_last_modified_date = true;
	/**
	 * HTML markup for the view navigation (e.g. day/week/etc.)
	 *
	 * In order to avoid generating the view navigation twice, it gets stored here
	 * @var string
	 * @access private
	 */
	var $view_markup = '';
	/**
	 * The year that has the earliest calendar item
	 *
	 * This should not be accessed directly; instead use the method get_min_year()
	 *
	 * @var string YYYY
	 * @access private
	 */
	var $min_year;
	/**
	 * The year that has the last calendar item
	 *
	 * This should not be accessed directly; instead use the method get_max_year()
	 *
	 * @var string YYYY
	 * @access private
	 */
	var $max_year;
	/**
	 * The parameters that page types can set on the module.
	 *
	 * 'view' (string) forces a specific view. Possible values: daily, weekly, monthly, yearly, all
	 *
	 * 'limit_to_page_categories' (boolen) determines if the module will display all events on site
	 * or just those with a category matching one on current page
	 *
	 * 'list_type' (string) can be 'standard' or 'verbose'
	 *
	 * 'additional_sites' (string) Sites other than the current one to pull events from.
	 * This can be a comma-separated set of site and/or site type unique names OR the keywords 'k_parent_sites', 'k_child_sites', or 'k_sharing_sites'
	 *
	 * 'sharing_mode' (string) can be 'all' (e.g. both shared and private) or 'shared_only'
	 *
	 * 'show_images' (boolean) determines if teaser images are displayed in list
	 *
	 * 'ideal_count' (integer) sets the @ideal_count value for dynamic view selection
	 *
	 * 'default_view_min_days' (integer) sets a smallest number of days the dynamically selected view can have.
	 *
	 * 'start_date' (string) forces the calendar to use as its default start date a date other than the current one 
	 *
	 * 'map_location' (boolean) show a google map in the event detail if latitude and longitude are set
	 *
	 * 'map_zoom_level' (int) set a zoom level for google maps - default 12
	 *
	 * 'ongoing_display' (string) Treat ongoing events in different ways.
	 * 	'inline' treats them like other events;
	 * 	'above' shows only starts/ends, with ongoing events that start before the start of the current view listed above the main list of events;
	 * 	'below' shows only starts/ends, with ongoing events that start before the current view and end after it listed below the main list of events.
	 *
	 * 'ongoing_show_ends' (boolean) Show the ending dates for events? Note that in combination with 
	 * 'ongoing_display'=>'below', events that start before the current view will not be visible, so 
	 * this should likely only be used in conjunction with 'ongoing_display'=>'above'.
	 * 
	 * 'limit_to_audiences' (string, comma spaced for multiple) limit to these audiences
	 * 'exlude_audiences' (string, comma spaced for multiple) excludes specified audiences
	 *
	 * 'freetext_filters' An array of filters, each in the following format:
	 * array('string to filter on','fields,to,search')
	 * The string to filter on is interpreted as a LIKE statement.
	 *
	 * 'cache_lifespan' How long, in seconds, should the calendar cache the events?
	 * 'cache_lifespan_meta' How long, in seconds, should the calendar cache calendar metadata,
	 *  like window, category, and audience determination?
	 *
	 * @var array
	 * @access private
	 * @todo review current default default_view_min_days value for sanity
	 */
	var $acceptable_params = array(
	 						'view'=>'',
							'limit_to_page_categories'=>false,
							'list_type'=>'standard',
							'additional_sites'=>'',
							'sharing_mode'=>'',
							'show_images'=>false,
							'list_thumbnail_height' => 0,
							'list_thumbnail_width' => 0,
							'list_thumbnail_crop' => '',
							'list_thumbnail_default_image' => '', // a unique name
							'ideal_count'=>NULL,
							'default_view_min_days'=>1,
	 						'start_date'=>'',
	 						'map_location' => true,
	 						'map_zoom_level' => 12,
	 						'ongoing_display' => 'above', // or below or inline
	 						'ongoing_show_ends' => true,
	 						'limit_to_audiences' => '',	 // as comma spaced strings
	 						'exclude_audiences' => '',
	 						'freetext_filters' => array(),
	 						'cache_lifespan' => 0,
	 						'cache_lifespan_meta' => 0,
						);
	/**
	 * Views that should not be indexed by search engines
	 *
	 * The idea is that this keeps search engines from indexing pages that are redundant 
	 * with other pages. Note that these pages should still allow robots to follow links, just
	 * not to index the pages.
	 * @var array
	 * @access private
	 */
	var $views_no_index = array('daily','weekly','monthly');
	/**
	 * Sites that should be queried for events
	 * @access private
	 * @var array
	 */
	var $event_sites;
	/**
	 * Toggles on and off the links to iCalendar-formatted data.
	 *
	 * Set to true to turn on the links; false to turn them off
	 * @var boolean
	 */
	var $show_icalendar_links = true;
	/**
	 * A place to store the default image so it does not have to be
	 * re-identified for each imageless event
	 * @var mixed NULL, false, or image entity object
	 */
	protected $_list_thumbnail_default_image;
	
	//////////////////////////////////////
	// General Functions
	//////////////////////////////////////
	
	/**
	 * Initialize the module
	 */
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

	/**
	 * Get the set of cleanup rules for user input
	 * @return array
	 */
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
	
	/**
	 * Redirect to well-constructed start_date variable when presented with the separate 
	 * start_year/start_day/start_month fields in the date-choosing form.
	 * @return void
	 */
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

	/**
	 * Makes sure the input from userland is sanitized
	 */
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
	
	protected function get_cache_lifespan()
	{
		return $this->params['cache_lifespan'];
	}
	protected function get_cache_lifespan_meta()
	{
		if($this->params['cache_lifespan_meta'])
			return $this->params['cache_lifespan_meta'];
		return $this->get_cache_lifespan();
	}
	
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
	/**
	 * Returns an array of id => reason audience entities to limit to based on the limit_to_audiences parameter
	 * 
	 * If given a faulty audience unique name, ignore it -- still limit to other audiences.
	 * Error triggered if not passed in a string, or if the audiences passed in aren't Reason unique
	 * names.
	 * @return array of id => audience entities, or an empty array
	 */
	function _get_audiences_to_limit_to()
	{
		$all_audiences = array();
		if(!empty($this->params['limit_to_audiences']))
		{
			if(gettype($this->params['limit_to_audiences']) != 'string')
			{
				trigger_error('The limit_to_audiences parameter must be a comma-seperated string of reason unique names.
 					Please check your syntax. Example: \'public_audience, students_audience\'. ');
				return $all_audiences;
			}
			$audiences_to_limit_to = explode(',', $this->params['limit_to_audiences']);
			foreach($audiences_to_limit_to as $audience)
			{
				$audience = trim($audience);
				if(reason_unique_name_exists($audience))
				{
					$audience_id = id_of($audience);
					$all_audiences[$audience_id] = new entity($audience_id);
				}
				else
					trigger_error('Strings passed in must be Reason unique names. \'' . $audience . '\' is 
									not a Reason unique name. Use: \'public_audience\' for example');
			}
		}
		return $all_audiences;
	}
	/**
	 * Returns an array of id => reason audience entities to exclude based on the exclude_audiences parameter.
	 *
	 * If given a faulty audience unique name, ignore it -- still exclude others
	 * Error triggered if not passed in a string, or if the audiences passed in aren't Reason unique
	 * names.
	 * @return array of id => audience entities, or an empty array
	 */
	function _get_audiences_to_exclude()
	{
		$excluded_audiences = array();
		if(!empty($this->params['exclude_audiences']))
		{
			if(gettype($this->params['exclude_audiences']) != "string")
			{
				trigger_error('The exluded_audiences parameter must be a comma-seperated string of reason unique names. Please 
								check your syntax. Example: \'public_audience, students_audience\'. ');
				return $excluded_audiences;
			}
			$audiences_to_exclude = explode(',', $this->params['exclude_audiences']);
			foreach($audiences_to_exclude as $audience)
			{
				$audience = trim($audience);
				if(reason_unique_name_exists($audience))
				{
					$audience_id = id_of($audience);
					$excluded_audiences[$audience_id] = new entity($audience_id);
				}
				else
				{
					trigger_error('Strings passed in must be Reason unique names. \'' . $audience . '\' is 
									not a Reason unique name. Use: \'public_audience\' for example');
				}
			}
		}
		return $excluded_audiences;
	}
	function _get_sites()
	{
		if(empty($this->params['additional_sites']))
		{
			return new entity($this->site_id);
		}
		if(!empty($this->event_sites))
		{
			return $this->event_sites;
		}
		$sites = array();
		$sites[$this->site_id] = new entity($this->site_id);
		$site_strings = explode(',',$this->params['additional_sites']);
		foreach($site_strings as $site_string)
		{
			$site_string = trim($site_string);
			switch($site_string)
			{
				case 'k_parent_sites':
					$psites = $this->_get_parent_sites($this->site_id);
					if(!empty($psites))
						$sites = $sites + $psites;
					break;
				case 'k_child_sites':
					$csites = $this->_get_child_sites($this->site_id);
					if(!empty($csites))
						$sites = $sites + $csites;
					break;
				case 'k_sharing_sites':
					$ssites = $this->_get_sharing_sites($this->site_id);
					if(!empty($ssites))
						$sites = $sites + $ssites;
					break;
				default:
					$usites = $this->_get_sites_by_unique_name($site_string, $this->site_id);
					if(!empty($usites))
						$sites = $sites + $usites;
			}
		}
		$this->event_sites = $sites;
		return $this->event_sites;
	}
	function _get_parent_sites($site_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_right_relationship( $site_id, relationship_id_of( 'parent_site' ) );
		
		$site = new entity($site_id);
		if($site->get_value('site_state') == 'Live')
		{
			$es->limit_tables('site');
			$es->limit_fields('site_state');
			$es->add_relation('site_state="Live"');
		}
		else
		{
			$es->limit_tables();
			$es->limit_fields();
		}
		$es->set_cache_lifespan($this->get_cache_lifespan_meta());
		return $es->run_one();
	}
	function _get_child_sites($site_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_left_relationship( $site_id, relationship_id_of( 'parent_site' ) );
		
		$site = new entity($site_id);
		if($site->get_value('site_state') == 'Live')
		{
			$es->limit_tables('site');
			$es->limit_fields('site_state');
			$es->add_relation('site_state="Live"');
		}
		else
		{
			$es->limit_tables();
			$es->limit_fields();
		}
		$es->set_cache_lifespan($this->get_cache_lifespan_meta());
		return $es->run_one();
	}
	/**
	 * Returns an array of site entities keyed by site id
	 *
	 * If a site unique name is given, this function will return a single-member array of that site
	 *
	 * If a site type unique name is given, this function will return all the sites that are of that site type.
	 * If the context site is live, only live sites will be returned.
	 *
	 * @param string $unique_name the unique anem of a site or a site type entity
	 * @param integer $context_site_id the id of the context site
	 * @access private
	 */
	function _get_sites_by_unique_name($unique_name, $context_site_id)
	{
		$return = array();
		if($id = id_of($unique_name))
		{
			$entity = new entity($id);
		
			switch($entity->get_value('type'))
			{
				case id_of('site'):
					$return[$id] = $entity;
					break;
				case id_of('site_type_type'):
					$es = new entity_selector();
					$es->add_type(id_of('site'));
					$es->add_left_relationship( $id, relationship_id_of( 'site_to_site_type' ) );
					$context_site = new entity($context_site_id);
					if($context_site->get_value('site_state') == 'Live')
					{
						$es->limit_tables('site');
						$es->limit_fields('site_state');
						$es->add_relation('site_state="Live"');
					}
					else
					{
						$es->limit_tables();
						$es->limit_fields();
					}
					$es->set_cache_lifespan($this->get_cache_lifespan_meta());
					$return = $es->run_one();
					break;
				default:
					trigger_error('Unique name "'.$unique_name.'" passed to events module in additional_sites parameter does not correspond to a Reason site or site type. Not included in sites shown.');
			}
		}
		else
		{
			trigger_error($unique_name.' is not a unique name of any Reason entity. Site(s) will not be included.');
		}
		return $return;
	}
	function _get_sharing_sites($site_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_left_relationship( id_of('event_type'), relationship_id_of('site_shares_type'));
		
		$site = new entity($site_id);
		if($site->get_value('site_state') == 'Live')
		{
			$es->limit_tables('site');
			$es->limit_fields('site_state');
			$es->add_relation('site_state="Live"');
		}
		else
		{
			$es->limit_tables();
			$es->limit_fields();
		}
		$es->set_cache_lifespan($this->get_cache_lifespan_meta());
		
		return $es->run_one();
	}
	function _get_start_date()
	{
		if(!empty($this->request['start_date']))
			return $this->request['start_date'];
		
		if(!empty($this->params['start_date']))
			return $this->params['start_date'];

		return $this->today;
	}
	function init_and_run_ical_calendar()
	{
		$init_array = $this->make_reason_calendar_init_array($this->_get_start_date(), '', 'all');
		
		$this->calendar = $this->_get_runned_calendar($init_array);
		
		$events = $this->calendar->get_all_events();
		
		$this->export_ical($events);
	}
	function init_html_calendar()
	{
		$start_date = '';
		$end_date = '';
		$view = '';
		
		if( empty($this->request['no_search']) && empty($this->request['textonly']) && ( !empty($this->request['start_date']) || !empty($this->request['end_date']) || !empty($this->request['view']) || !empty($this->request['audience']) || !empty($this->request['view']) || !empty($this->request['nav_date']) || !empty($this->request['category']) ) && $head_items =& $this->get_head_items() )
		{
			$head_items->add_head_item('meta', array('name'=>'robots','content'=>'noindex,follow'));
		}
		
		if(!empty($this->pass_vars['end_date']))
			$end_date = $this->pass_vars['end_date'];
		else
		{
			if(!empty($this->pass_vars['view']))
				$view = $this->pass_vars['view'];
			elseif(!empty($this->params['view']))
				$view = $this->params['view'];
		}
		$init_array = $this->make_reason_calendar_init_array($this->_get_start_date(), $end_date, $view);
		
		$this->calendar = $this->_get_runned_calendar($init_array);
	}
	
	function _get_runned_calendar($init_array)
	{
		$calendar = new reasonCalendar($init_array);
		$calendar->run();
		return $calendar;
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
				if($this->params['ongoing_display'] == 'above')
				{
					$this->show_ongoing_events( $this->get_ongoing_event_ids('above') );
				}
				foreach($this->events_by_date as $day => $val)
				{
					if ( $this->calendar->get_end_date() && $day > $this->calendar->get_end_date() )
						break;
					$this->show_daily_events( $day );
				}
				
				if($this->params['ongoing_display'] == 'below')
				{
					$this->show_ongoing_events( $this->get_ongoing_event_ids('below') );
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
	
	/**
	 * Get the ids of all ongoing events in the calendar
	 *
	 * @param string $ongoing_display 'inline', 'above', or 'below' -- to determine which events
	 *               should be considered ongoing
	 * @return array event ids
	 */
	function get_ongoing_event_ids($ongoing_display = '')
	{
		if(empty($ongoing_display))
			$ongoing_display = $this->params['ongoing_display'];
		
		$ongoing_ids = array();
		foreach($this->events_by_date as $day => $val)
		{
			if ( $this->calendar->get_end_date() && $day > $this->calendar->get_end_date() )
				break;
			$ongoing_ids = array_merge($ongoing_ids,$val);
		}
		$ongoing_ids = array_unique($ongoing_ids);
		if('above' == $ongoing_display)
		{
			foreach($ongoing_ids as $k => $id)
			{
				if(!$this->event_is_ongoing($this->events[$id]) || $this->events[$id]->get_value('datetime') >= $this->calendar->get_start_date())
					unset($ongoing_ids[$k]);
			}
		}
		elseif('below' == $ongoing_display)
		{
			foreach($ongoing_ids as $k => $id)
			{
				if(!$this->event_is_ongoing($this->events[$id]) || $this->events[$id]->get_value('datetime') >= $this->calendar->get_start_date() || $this->events[$id]->get_value('last_occurence') <= $this->calendar->get_end_date())
					unset($ongoing_ids[$k]);
			}
		}
		else
		{
			trigger_error('Unrecognized string passed to get_ongoing_event_ids(): '.$ongoing_display.'. Should be "above" or "below".');
		}
		return $ongoing_ids;
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
		$this->calendar = $this->_get_runned_calendar($init_array);
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
		ob_start();
		foreach ($this->events_by_date[$day] as $event_id)
		{
			$ongoing_type = $this->get_event_ongoing_type_for_day($event_id,$day);
			if( 'middle' == $ongoing_type )
				continue;
			if ($this->params['list_type'] == 'schedule')
			{
				if (!isset($last_time) || $this->events[$event_id]->get_value( 'datetime' ) != $last_time)
				{
					if(substr($this->events[$event_id]->get_value( 'datetime' ), 11) != '00:00:00')
						$time_string = prettify_mysql_datetime( $this->events[$event_id]->get_value( 'datetime' ), $this->list_time_format );
					else
						$time_string = 'Today';
					if (isset($last_time)) echo '</ul></li>';
					echo '<li class="time_block">';
					echo '<h5 class="time">'.$time_string.'</h5>';
					echo '<ul class="time_events">';
				} 
					
				$last_time = $this->events[$event_id]->get_value( 'datetime' );
			}	
			echo '<li class="event">';
			$this->show_event_list_item( $event_id, $day, $ongoing_type );
			echo '</li>'."\n";
			
		}
		$list_items = ob_get_clean();
		
		if(empty($list_items))
			return;
		
		if($this->show_months == true && ($this->prev_month != substr($day,5,2) || $this->prev_year != substr($day,0,4) ) )
		{
			echo '<h3 class="day">'.prettify_mysql_datetime( $day, 'F Y' ).'</h3>'."\n";
			$this->prev_month = substr($day,5,2);
			$this->prev_year = substr($day,0,4);
		}
		
		if($day == $this->today)
			$today = ' (Today)';
		else
			$today = '';
		echo '<div class="dayblock" id="dayblock_'.$day.'">'."\n";
		echo '<h4 class="day"><a name="'.$day.'"></a>'.prettify_mysql_datetime( $day, $this->list_date_format ).$today.'</h4>'."\n";
		echo '<ul class="dayEvents">';
		echo $list_items;
		if ($this->params['list_type'] == 'schedule') echo '</ul></li>'."\n";
		echo '</ul>'."\n";
		echo '</div>'."\n";
	} // }}}
	/**
	 * For a given event and a given day, should
	 * the event be displayed as starting, ending, "ongoing", or not at all?
	 *
	 * @param integer $event_id
	 * @param string $day YYY-MM-DD
	 * @return string Values: 'starts', 'ends', 'middle', or ''
	 */
	function get_event_ongoing_type_for_day($event_id,$day)
	{
		if($this->params['ongoing_display'] != 'inline' && $this->event_is_ongoing($this->events[$event_id]))
		{
			if(substr($this->events[$event_id]->get_value( 'datetime' ), 0,10) == $day)
			{
				return 'starts';
			}
			elseif($this->params['ongoing_show_ends'] && $this->events[$event_id]->get_value( 'last_occurence' ) == $day)
			{
				return 'ends';
			}
			else
			{
				return 'middle';
			}
		}
		return '';
	}
	/**
	 * Output HTML list of ongoing events
	 * @param array $ids integers
	 * @return void
	 */
	function show_ongoing_events($ids)
	{
		if(!empty($ids))
		{
			echo '<div class="ongoingblock">'."\n";
			echo '<h3>Ongoing</h3>'."\n";
			echo '<ul class="ongoingEvents">'."\n";
			foreach($ids as $id)
			{
				echo '<li class="event">';
				$this->show_event_list_item( $id, '', 'through' );
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
			echo '</div>'."\n";
		}
	}
	/**
	 * Output HTML for an event in the list
	 *
	 * @param integer $event_id
	 * @param string $day YYY-MM-DD
	 * @param string $ongoing_type What method of display are we using for ongoing events? Values: '','starts','ends'
	 * @return void
	 */
	function show_event_list_item( $event_id, $day, $ongoing_type = '' )
	{
		if($this->params['list_type'] == 'verbose')
			$this->show_event_list_item_verbose( $event_id, $day, $ongoing_type );
		else if($this->params['list_type'] == 'schedule')
			$this->show_event_list_item_schedule( $event_id, $day, $ongoing_type );
		else
			$this->show_event_list_item_standard( $event_id, $day, $ongoing_type );
	}
	
	/**
	 * Output standard HTML for an event in the list
	 *
	 * @param integer $event_id
	 * @param string $day YYY-MM-DD
	 * @param string $ongoing_type What method of display are we using for ongoing events? Values: '','starts','ends'
	 * @return void
	 */
	function show_event_list_item_standard( $event_id, $day, $ongoing_type = '' ) // {{{
	{
		$link = $this->events_page_url.$this->construct_link(array('event_id'=>$this->events[$event_id]->id(),'date'=>$day));
		if($this->params['show_images'])
			$this->_show_teaser_image($event_id, $link);
		if($this->show_times && substr($this->events[$event_id]->get_value( 'datetime' ), 11) != '00:00:00')
			echo prettify_mysql_datetime( $this->events[$event_id]->get_value( 'datetime' ), $this->list_time_format ).' - ';
		echo '<a href="'.$link.'">';
		echo $this->events[$event_id]->get_value( 'name' );
		echo '</a>';
		switch($ongoing_type)
		{
			case 'starts':
				echo ' <span class="begins">begins</span>';
			case 'through':
				echo ' <em class="through">(through '.$this->_get_formatted_end_date($this->events[$event_id]).')</em> ';
				break;
			case 'ends':
				echo ' <span class="ends">ends</span>';
				break;
		}
	} // }}}
	
	/**
	 * Output "wordy" HTML for an event in the list
	 *
	 * Includes description, location, etc.
	 *
	 * @param integer $event_id
	 * @param string $day YYY-MM-DD
	 * @param string $ongoing_type What method of display are we using for ongoing events? Values: '','starts','ends'
	 * @return void
	 */
	function show_event_list_item_verbose( $event_id, $day, $ongoing_type = '' ) // {{{
	{
		$link = $this->events_page_url.$this->construct_link(array('event_id'=>$this->events[$event_id]->id(),'date'=>$day));
		if($this->params['show_images'])
			$this->_show_teaser_image($event_id, $link);
		echo '<a href="'.$link.'">';
		echo $this->events[$event_id]->get_value( 'name' );
		echo '</a>';
		switch($ongoing_type)
		{
			case 'starts':
				echo ' <span class="begins">begins</span>';
			case 'through':
				echo ' <em class="through">(through '.$this->_get_formatted_end_date($this->events[$event_id]).')</em> ';
				break;
			case 'ends':
				echo ' <span class="ends">ends</span>';
				break;
		}
		echo '<ul>'."\n";
		if($this->events[$event_id]->get_value( 'description' ))
		{
			echo '<li class="description">';
			echo $this->events[$event_id]->get_value( 'description' );
			echo '</li>'."\n";
		}
		$time_loc = array();
		if(substr($this->events[$event_id]->get_value( 'datetime' ), 11) != '00:00:00')
			$time_loc[] = '<span class="time">'.prettify_mysql_datetime( $this->events[$event_id]->get_value( 'datetime' ), $this->list_time_format ).'</span>';
		if($this->events[$event_id]->get_value( 'location' ))
			$time_loc[] = '<span class="location">'.$this->events[$event_id]->get_value( 'location' ).'</span>';
		if (!empty($time_loc))
		{
			echo '<li class="timeLocation">';
			echo implode(', ',$time_loc);
			echo '</li>'."\n";
		}
		echo '</ul>'."\n";
	} // }}}
	
	/**
	 * Output schedule-like HTML for an event in the list
	 *
	 * Group events that start at the same time together, showing the time
	 * only once, and list dates separately
	 *
	 * @param integer $event_id
	 * @param string $day YYY-MM-DD
	 * @param string $ongoing_type What method of display are we using for ongoing events? Values: '','starts','ends'
	 * @return void
	 */
	function show_event_list_item_schedule( $event_id, $day, $ongoing_type = '' ) // {{{
	{
		$link = $this->events_page_url.$this->construct_link(array('event_id'=>$this->events[$event_id]->id(),'date'=>$day));
		if($this->params['show_images'])
			$this->_show_teaser_image($event_id, $link);
		echo '<a href="'.$link.'" class="name">';
		echo $this->events[$event_id]->get_value( 'name' );
		echo '</a>';
		switch($ongoing_type)
		{
			case 'starts':
				echo ' <span class="begins">begins</span>';
			case 'through':
				echo ' <em class="through">(through '.$this->_get_formatted_end_date($this->events[$event_id]).')</em> ';
				break;
			case 'ends':
				echo ' <span class="ends">ends</span>';
				break;
		}
		if ($duration = $this->prettify_duration($this->events[$event_id]))
			echo ' <span class="duration">('.$duration.')</span>';
		if($this->events[$event_id]->get_value( 'description' ) || $this->events[$event_id]->get_value( 'location' ) )
		{
			echo '<ul>'."\n";
			if($this->events[$event_id]->get_value( 'description' ))
			{
				echo '<li class="description">';
				echo $this->events[$event_id]->get_value( 'description' );
				echo '</li>'."\n";
			}
			if($this->events[$event_id]->get_value( 'location' ))
			{
				echo '<li class="location">'.$this->events[$event_id]->get_value( 'location' ).'</li>'."\n";
			}
			echo '</ul>'."\n";
		}
	} // }}}
	
	/**
	 * Format a date to be shown in a "through [formatted date]" phrase
	 *
	 * @param object $event entity
	 * @return string formatted date
	 */
	function _get_formatted_end_date($event)
	{
		$full_month = prettify_mysql_datetime($event->get_value('last_occurence'),'F');
		$month = prettify_mysql_datetime($event->get_value('last_occurence'),'M');
		
		$ret = $month;
		if($full_month != $month)
			$ret .= '.';
		$ret .= ' ';
		$ret .= prettify_mysql_datetime($event->get_value('last_occurence'),'j');
		
		$start_year = max(substr($event->get_value('datetime'),0,4), substr($this->calendar->get_start_date(),0,4));
		
		if($start_year != substr($event->get_value('last_occurence'),0,4))
			$ret .= ', '.substr($event->get_value('last_occurence'),0,4);
		
		return $ret;
	}

	function _show_teaser_image($event_id, $link)
	{
		static $image_cache = array();
		if(!array_key_exists($event_id, $image_cache))
		{
			$es = new entity_selector();
        	$es->description = 'Selecting images for event';
        	$es->add_type( id_of('image') );
        	$es->add_right_relationship( $event_id, relationship_id_of('event_to_image') );
        	$es->add_rel_sort_field($event_id, relationship_id_of('event_to_image'));
        	$es->set_order('rel_sort_order ASC');
        	$es->set_num(1);
        	$images = $es->run_one();
        	if(!empty($images))
        	{
        		$image_cache[$event_id] = current($images);
        	}
        	elseif($image = $this->_get_list_thumbnail_default_image())
        	{
        		$image_cache[$event_id] = $image;
        	}
        	else
        	{
        		$image_cache[$event_id] = NULL;
        	}
        }

        if(!empty($image_cache[$event_id]))
        {
        	if($this->params['list_thumbnail_width'] || $this->params['list_thumbnail_height'])
        	{
        		$rsi = new reasonSizedImage;
        		$rsi->set_id($image_cache[$event_id]->id());
        		if(0 != $this->params['list_thumbnail_height']) $rsi->set_height($this->params['list_thumbnail_height']);
				if(0 != $this->params['list_thumbnail_width']) $rsi->set_width($this->params['list_thumbnail_width']);
				if('' != $this->params['list_thumbnail_crop']) $rsi->set_crop_style($this->params['list_thumbnail_crop']);
				show_image( $rsi, true, false, false, '', $this->textonly, false, $link );
        	}
        	else
        	{
        		show_image( $image_cache[$event_id], true, false, false, '', $this->textonly, false, $link );
        	}
        }
	}
	/**
	 * Get the default thumbnail image
	 * @return mixed image entity object or boolean false
	 */
	protected function _get_list_thumbnail_default_image()
	{
		if(!isset($this->_list_thumbnail_default_image))
		{
			if(!empty($this->params['list_thumbnail_default_image']))
			{
				$image_id = id_of($this->params['list_thumbnail_default_image']);
				if(!empty($image_id))
				{
					$this->_list_thumbnail_default_image = new entity($image_id);
				}
				else
				{
					trigger_error('list_thumbnail_default_image must be a unique name');
				}
			}
			if(empty($this->_list_thumbnail_default_image))
				$this->_list_thumbnail_default_image = false;
		}
		return $this->_list_thumbnail_default_image;
	}
	
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
	//	$ret .= '<div style="clear:both;border:1px solid #f00;"></div>'."\n"; //ie7 fix??
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
		$cs->set_cache_lifespan($this->get_cache_lifespan_meta());
		$cats = $cs->run_one();
		$cats = $this->check_categories($cats);
		if(empty($cats))
			return '';
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
				$ret .= '<a href="'.$this->construct_link(array('category'=>$cat->id(),'view'=>'','no_search'=>'1')).'" title="'.reason_htmlspecialchars(strip_tags($cat->get_value('name'))).' events">'.$cat->get_value('name').'</a>';
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
		$setup_es->set_cache_lifespan($this->get_cache_lifespan_meta());
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
	
	/**
	 * Populate $audiences class variable
	 * @todo Remove use of $limit_to_current_site class var, which is deprecated
	 */
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
		$es->set_cache_lifespan($this->get_cache_lifespan_meta());
		$audiences = $es->run_one(id_of('audience_type'));
		
		$event_type_id = id_of('event_type');
		$rel_id = relationship_id_of('event_to_audience');
		if($this->limit_to_current_site)
			$setup_es = new entity_selector($this->parent->site_id);
		else
			$setup_es = new entity_selector();
		$setup_es->set_num(1);
		$setup_es->add_type($event_type_id);
		$setup_es->set_cache_lifespan($this->get_cache_lifespan_meta());
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
		$cal = $this->_get_runned_calendar($init_array);
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
		echo '<form action="'.$this->construct_link().'" method="post">'."\n";
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
	function _get_sharing_mode()
	{
		return $this->params['sharing_mode'];
	}
	
	function make_reason_calendar_init_array($start_date, $end_date = '', $view = '')
	{
		$init_array = array();
		$init_array['context_site'] = $this->parent->site_info;
		$init_array['site'] = $this->_get_sites();
		$init_array['sharing_mode'] = $this->_get_sharing_mode();
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
			$es->set_cache_lifespan($this->get_cache_lifespan_meta());
			$cats = $es->run_one();
			if(!empty($cats))
			{
				$init_array['or_categories'] = $cats;
			}
		}
		if($this->params['ideal_count'])
			$init_array['ideal_count'] = $this->params['ideal_count'];
		elseif(!empty($this->ideal_count))
			$init_array['ideal_count'] = $this->ideal_count;
		
		if($this->params['default_view_min_days']) 
			$init_array['default_view_min_days'] = $this->params['default_view_min_days'];
		
		$init_array['automagic_window_snap_to_nearest_view'] = $this->snap_to_nearest_view;
		
		if('inline' == $this->params['ongoing_display'])
		{
			$init_array['ongoing_count_all_occurrences'] = true;
		}
		elseif('above' == $this->params['ongoing_display'])
		{
			$init_array['ongoing_count_all_occurrences'] = false;
			$init_array['ongoing_count_pre_start_dates'] = true;
			$init_array['ongoing_count_ends'] = $this->params['ongoing_show_ends'];
		}
		elseif('below' == $this->params['ongoing_display'])
		{
			$init_array['ongoing_count_all_occurrences'] = false;
			$init_array['ongoing_count_pre_start_dates'] = false;
			$init_array['ongoing_count_ends'] = $this->params['ongoing_show_ends'];
		}
		
		if(!empty($this->request['search']))
		{
			$init_array['simple_search'] = $this->request['search'];
		}
		$init_array['es_callback'] = array($this, 'reason_calendar_master_callback');
		$init_array['cache_lifespan'] = $this->get_cache_lifespan();
		$init_array['cache_lifespan_meta'] = $this->get_cache_lifespan_meta();
		return $init_array;
	}
	
	/**
	 * @param entity_selector $es the entity selector from reason's calendar class used to select events.
	 * 
	 * Modifies the entity selector in reason's calendar to take into account inclduing audiences
	 * or excluding audiences. Also calls any other callback function that might be included in the
	 * calendar class.
	 */
	
	function reason_calendar_master_callback($es)
	{
		/* 
			In case there is another callback function, make sure to call it in addition to limiting
			and excluding audiences
		*/
		if(!empty($this->es_callback))
		{
			$callback_array = array();
			$callback_array[] =& $es;
			call_user_func_array($this->es_callback, $callback_array);
		}
		if($audiences_to_limit_to = $this->_get_audiences_to_limit_to())
		{
			$es->add_left_relationship(array_keys($audiences_to_limit_to), relationship_id_of('event_to_audience'));
		}
		if($audiences_to_exclude = $this->_get_audiences_to_exclude())
		{
			// get all events -- exclude those who have an audience in the excluded audience list
			$audience_table_info = $es->add_left_relationship_field('event_to_audience', 'entity', 'id', 'audience_id');
			$audience_ids = array_keys($audiences_to_exclude);
			array_walk($audience_ids,'db_prep_walk');
			$es->add_relation($audience_table_info['audience_id']['table'].'.'.$audience_table_info['audience_id']['field'].' NOT IN ('.implode(',',$audience_ids).')');
		}
		
		if(!empty($this->params['freetext_filters']))
		{
			foreach($this->params['freetext_filters'] as $filter)
			{
				$string = $filter[0];
				$fields = explode(',',$filter[1]);
				$parts = array();
				foreach($fields as $field)
				{
					$field_parts = explode('.',trim($field));
					
					$parts[] = '`'.implode('`.`',$field_parts).'` LIKE "'.addslashes($string).'"';
				}
				$where = '('.implode(' OR ',$parts).')';
				$es->add_relation($where);
			}
		}
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
		if($this->_get_start_date() < $this->today)
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
		if (defined("REASON_URL_FOR_ICAL_FEED_HELP") && ( (bool) REASON_URL_FOR_ICAL_FEED_HELP != FALSE))
		{
			echo ' | <a href="'.REASON_URL_FOR_ICAL_FEED_HELP.'"><img src="'.REASON_HTTP_BASE_PATH . 'silk_icons/help.png" alt="Help" width="16px" height="16px" /></a>';
			echo ' <a href="'.REASON_URL_FOR_ICAL_FEED_HELP.'">How to Use This</a>';
		}
		echo '</div>'."\n";
	}
	
	
	///////////////////////////////////////////
	// Showing a Specific Event
	///////////////////////////////////////////
	
	
	/**
	 * Initialiation function for event detail mode
	 *
	 * @return void
	 */
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
	/**
	 * Given set of events, generate ical representation, send as ical, and die
	 *
	 * Note that this method will never return, as it calls die().
	 *
	 * @param array $events entities
	 * @return void
	 */
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
	/**
	 * Get an ical representation of a set of events
	 * @param array $events entities
	 * @return string iCal
	 */
	function get_ical($events)
	{
		if(!is_array($events))
		{
			trigger_error('get_ical needs an array of event entities');
			return '';
		}
		
		$calendar = new reason_iCalendar();
  		$calendar -> set_events($events);
		if (count($events) > 1)
		{
			$site = new entity($this->site_id);
			$site_name = $site->get_value('name');
			$calendar->set_title($site_name);
		}
  		return $calendar -> get_icalendar_events();
	}
	/**
	 * Output HTML for the detail view of an event
	 * @return void
	 */
	function show_event() // {{{
	{
		if ($this->event_ok_to_show($this->event))
			$this->show_event_details();
		else
			$this->show_event_error();
	} // }}}
	/**
	 * Is a given event acceptable to show in the calendar?
	 * @param object $event entity
	 * @return boolean
	 */
	function event_ok_to_show($event)
	{
		$id = $event->id();
		if (!isset($this->_ok_to_show[$id]))
		{
			$sites = $this->_get_sites();
			if(is_array($sites)) $es = new entity_selector(array_keys($sites));
			else $es = new entity_selector($sites->id());
			$es->add_type(id_of('event_type'));
			$es->add_relation('entity.id = "'.$id.'"');
			$es->add_relation('show_hide.show_hide = "show"');
			$es->set_num(1);
			$es->limit_tables(array('show_hide'));
			$es->limit_fields();
			if($this->_get_sharing_mode() == 'shared_only') $es->add_relation('entity.no_share != 1');
			$this->_ok_to_show[$id] = ($es->run_one());
		}
		return $this->_ok_to_show[$id];
	}
	/**
	 * Show the detail view of the current event
	 *
	 * @return void
	 */
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
		$this->show_location($e);
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
	
	/**
	 * Show the location section if we have content in the location OR address OR lat / lon fields.
	 */
	function show_location(&$e)
	{
		$lat = ($e->has_value('latitude')) ? $e->get_value('latitude') : false;
		$lon = ($e->has_value('longitude')) ? $e->get_value('longitude') : false;
		$address = ($e->has_value('address')) ? $e->get_value('address') : false;
		$location = ($e->has_value('location')) ? $e->get_value('location') : false;
		
		if ( (!empty($lat) && !empty($lon)) || !empty($address) )
		{
			echo '<div class="eventLocation">'."\n";
			if ($this->params['map_location'] && !empty($lat) && !empty($lon))
			{
				$this->show_map($e);
			}
			echo '<strong>Location:</strong>';
			if ($location)
			{
				echo '<p class="location">'.$e->get_value('location').'</p>'."\n";
			}	
			if ($address)
			{
				echo '<p class="address">'.$e->get_value('address').'</p>'."\n";
			}
			echo '</div>'."\n";
		}
		elseif (!empty($location))
		{
			echo '<p class="location"><strong>Location:</strong> '.$e->get_value('location').'</p>'."\n"; // this is what we used to do.
		}
	}
	
	/**
	 * Show a google static map at an appropriate zoom level for the event.
	 *
	 * @todo replace me with a dynamic map that allows zooming.
	 */
	function show_map(&$e)
	{
		$lat = ($e->has_value('latitude')) ? $e->get_value('latitude') : false;
		$lon = ($e->has_value('longitude')) ? $e->get_value('longitude') : false;
		$address = ($e->has_value('address')) ? $e->get_value('address') : false;
		
		if (!empty($lat) && !empty($lon)) // if we have a location, lets show it with a google static map.
		{
			echo '<div class="eventMap">';
			$static_map_base_url = 'https://maps.googleapis.com/maps/api/staticmap';
			$params['size'] = '100x100';
			$params['markers'] = 'color:0xFF6357|'.$lat.','.$lon;
			$params['sensor'] = 'false';
			
			// lets add zoom level if it is set
			if (isset($this->params['map_zoom_level']) && !empty($this->params['map_zoom_level'])) 
			{
				$params['zoom'] = $this->params['map_zoom_level'];
			}
			$qs = carl_make_query_string($params);
			$static_map_url = $static_map_base_url . $qs;
			
			$google_maps_base_url = 'https://maps.google.com/maps/';
			if ($address) $google_maps_params['saddr'] = $e->get_value('address');
			else $google_maps_params['q'] = $lat.','.$lon;
			$google_maps_qs = carl_construct_query_string($google_maps_params);
			$google_maps_link = $google_maps_base_url . $google_maps_qs;
			echo '<a href="'.$google_maps_link.'"><img src="'.$static_map_url.'" alt="map of '.reason_htmlspecialchars($e->get_value('name')).'" /></a>';	
			echo '</div>';
		}
	}
	
	/**
	 * If a requested event is not found, what should we output?
	 *
	 * By default, this method outputs an error message and lists
	 * other events
	 *
	 * @return void
	 */
	function show_event_error() // {{{
	{
		echo '<p>We\'re sorry; the event requested does not exist or has been removed from this calendar. This may be due to incorrectly typing in the page address; if you believe this is a bug, please report it to the contact person listed at the bottom of the page.</p>';
		$this->init_list();
		$this->list_events();
	} // }}}
	/**
	 * Output HTML that explains the length of an event
	 * @param object $e event
	 * @return void
	 */
	function show_duration(&$e) // {{{
	{
		if ($e->get_value( 'hours' ) || $e->get_value( 'minutes' ))
		{
			echo '<p class="duration"><strong>Duration:</strong> ';
			echo $this->prettify_duration($e);
			echo '</p>'."\n";
		}
	} // }}}
	
	/**
	 * Get a nicely formatted duration of an event for humans
	 * @param object $e event
	 * @return void
	 */
	function prettify_duration(&$e)
	{
		$duration = '';
		if ($e->get_value( 'hours' ))
		{
			if ( $e->get_value( 'hours' ) > 1 )
				$hour_word = 'hours';
			else
				$hour_word = 'hour';
			$duration .= $e->get_value( 'hours' ).' '.$hour_word;
			if ($e->get_value( 'minutes' ))
				$duration .= ', ';
		}
		if ($e->get_value( 'minutes' ))
		{
			$duration .= $e->get_value( 'minutes' ).' minutes';
		}
		return $duration;
	}
	/**
	 * Output HTML that explains which site an event is from
	 * @param object $e event
	 * @return void
	 */
	function show_ownership_info(&$e)
	{
		$owner_site = $e->get_owner();
		if($owner_site->id() != $this->parent->site_info->id())
		{	
			reason_include_once( 'classes/module_sets.php' );
			$ms =& reason_get_module_sets();
			$modules = $ms->get('event_display');
			$rpts =& get_reason_page_types();
			$page_types = $rpts->get_page_type_names_that_use_module($modules);
			if (!empty($page_types))
			{
				$tree = NULL;
				$link = get_page_link($owner_site, $tree, $page_types, true);
				echo '<p>From site: <a href="'.$link.'">'.$owner_site->get_value('name').'</a></p>'."\n";
			}
		}
	}
	/**
	 * Output HTML for contact information for a given event
	 * @param object $e event
	 * @return void
	 */
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
	/**
	 * Output HTML that explains how a repeating event recurs
	 * @param object $e event
	 * @return void
	 */
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
	/**
	 * Output HTML that shows the dates a given event occurs on
	 * @param object $e event
	 * @return void
	 */
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
	/**
	 * Output HTML link to export an ical representation of this event
	 * @param object $e event
	 * @return void
	 */
	function show_item_export_link($e) {
		echo '<div class="export">'."\n";
		if($e->get_value('recurrence') == 'none' || empty($this->request['date']))
		{
			echo '<a href="'.$this->construct_link(array('event_id'=>$e->id(),'format'=>'ical')).'">Import into your calendar program</a>';
		}
		else
		{
			echo 'Add to your calendar: <a href="'.$this->construct_link(array('event_id'=>$e->id(),'format'=>'ical','date'=>$this->request['date'])).'">This occurrence</a> | <a href="'.$this->construct_link(array('event_id'=>$e->id(),'format'=>'ical','date'=>'')).'">All occurrences</a>';
		}
		echo '</div>'."\n";
	}
	/**
	 * Output HTML link to add event to personal calendar
	 * @param object $e event
	 * @return void
	 */
	function show_item_add_to_personal_calendar_interface($e)
	{
		echo '<div class="addToPersonalCalendar">';
		echo '<a href="'.$this->construct_link(array('event_id'=>$e->id(),'add'=>'true')).'">Add to my personal calendar</a>';
		echo '</div>'."\n";
	}
	/**
	 * Output HTML of a link back to the events listing from the view of an individual event
	 * @return void
	 */
	function show_back_link()
	{
		echo '<p class="back"><a href="'.$this->construct_link().'">Back to event listing</a></p>'."\n";
	}
	/**
	 * Output HTML that shows the images attached to a given event
	 * @param object $e event
	 * @return void
	 */
	function show_images(&$e)
	{
		$images = array();
		if ($rel_id = relationship_id_of('event_to_poster_image', true, false))
		{
			$es = new entity_selector();
			$es->description = 'Selecting poster images for event';
			$es->add_type( id_of('image') );
			$es->add_right_relationship( $e->id(), $rel_id );
			$es->add_rel_sort_field($e->id(), $rel_id);
        	$es->set_order('rel_sort_order ASC');
			$images = $es->run_one();
		}
		$es = new entity_selector();
        $es->description = 'Selecting images for event';
        $es->add_type( id_of('image') );
        $es->add_right_relationship( $e->id(), relationship_id_of('event_to_image') );
        if(!empty($images))
        	$es->add_relation('`entity`.`id` NOT IN ("'.implode('","',array_keys($images)).'")');
        $es->add_rel_sort_field($e->id(), relationship_id_of('event_to_image'));
        $es->set_order('rel_sort_order ASC');
        $images += $es->run_one();

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
	/**
	 * Output HTML that lists the categories for a given event
	 * @param object $e event
	 * @return void
	 */
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
	/**
	 * Output HTML that lists the audiences for a given event
	 * @param object $e event
	 * @return void
	 */
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
	/**
	 * Output HTML that lists the keywords for a given event
	 * @param object $e event
	 * @return void
	 */
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
				$parts[] = '<a href="'.$this->construct_link(array('search'=>$key,'no_search'=>'1'),false).'">'.$key.'</a>';
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
	
	/**
	 * Make a query-string-based link within the events module
	 *
	 * @param array $vars The query string variables for the link
	 * @param boolean $pass_passables should the items in $this->pass_vars
	 *                be passed if they are present in the current query?
	 * @return string
	 */
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
			$link_vars[$key] = urlencode($link_vars[$key]);
		}
		return '?'.implode_with_keys('&amp;',$link_vars);
	} // }}}
	/**
	 * Is a given event an all-day event?
	 * @param object $event entity
	 * @return boolean
	 */
	protected function event_is_all_day_event($event)
	{
		return $this->calendar->event_is_all_day_event($event);
	}
	/**
	 * Is a given event an "ongoing" event?
	 * @param object $event entity
	 * @return boolean
	 */
	protected function event_is_ongoing($event)
	{
		return $this->calendar->event_is_ongoing($event);
	}
}
?>
