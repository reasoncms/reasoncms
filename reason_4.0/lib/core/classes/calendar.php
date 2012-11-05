<?php
/**
 * calendar.php
 *
 * This file contains the reasonCalendar class, which wraps up the event logic in an object that can be used by modules, feeds, etc.
 *
 * @package reason
 * @subpackage classes
 */

/**
 * include dependencies
 */
include_once( 'reason_header.php' );
include_once( CARL_UTIL_INC . 'db/db.php' );
reason_include_once( 'classes/entity_selector.php' );

/**
 * compare_times( $a,$b )
 * This function takes two objects with datetime fields and figures out which
 * one which happens earlier in the day
 * this is used to order events within a particular day.
 * This function is meant to be used with the usort function on an array of entities
 * @param object $a The first entity
 * @param object $b The scond entity
 * @return int $order -1,1,or 0
 */
function compare_times( $a,$b ) // {{{
{
	// grab out the data from the mysql datetime
	list( ,$a_time ) = explode( ' ',$a->get_value( 'datetime' ) );
	//$a_time = str_replace( ':','',$a_time );
	list( ,$b_time ) = explode( ' ',$b->get_value( 'datetime' ) );
	//$b_time = str_replace( ':','',$b_time );

	// compare the two and return the appropriate one
	if( $a_time > $b_time )
		return 1;
	elseif ( $a_time < $b_time )
		return -1;
	else
		return 0;
} // }}}

/**
 * Reason Calendar Class
 *
 * A class that handles the complex logic of extracting events for the Reason database,
 * figuring out what dates they fall on, and returning them in an easy-to-use array
 *
 * Sample code for using this class
 * <code>
 * $site = new entity(id_of('web_development_site'));
 * $cal = new reasonCalendar(array('site'=>$site,'start_date'=>'2004-05-01','ideal_count'=>22));
 * $cal->run();
 * $event_days = $cal->get_all_days();
 * $event_entities = $cal->get_all_events();
 * echo '<p>Calendar view starts on: '.$cal->get_start_date().'</p>'."\n";
 * echo '<p>Calendar view ends on: '.$cal->get_end_date().'</p>'."\n";
 * echo '<p>The first event occurrs on: '.$cal->get_min_date().'</p>'."\n";
 * echo '<p>the last event occurrs on: '.$cal->get_max_date().'</p>'."\n";
 * echo '<p>We are using this view: '.$cal->get_view().'</p>'."\n";
 * foreach($event_days as $day=>$events)
 * {
 * 	echo '<h3>'.$day.'</h3>'."\n";
 * 	foreach($events as $id)
 * 	{
 * 		echo '<p>Name: '.$event_entities[$id]->get_value('name');
 * 		echo '<br />Time: '.date('h:m', get_unix_timestamp($event_entities[$id]->get_value('datetime'))).'</p>'."\n";
 * 	}
 * }
 * </code>
 *
 * Getting events from multiple sites
 *
 * It is possible to use this class to get events belonging to more than one site.
 * There are two ways to do this:
 *
 * 1. Don't provide a site parameter. In this case, the class will return all events shared by
 * Reason sites.
 *
 * 2. Provide an array of sites instead of a single site as the site parameter. In this case
 * the class will return events from the sites specified. Whether the events retrieved are just the
 * shared ones or will include private events can be controlled via the sharing_mode parameter, 
 * which has three possible values:
 *
 * "shared_only" will retrieve just events shared by the sites provided.
 *
 * "all" will retrieve all events, both shared and private.
 *
 * "hybrid" will pay attention to the context_site parameter and, if it is provided, include all
 * events from the context_site (if it is in the list of sites to pull from) and only shared events
 * from all other sites.
 *
 * If you do not specify a sharing_mode, the calendar class will make an educated guess regarding
 * which mode makes sense given how many sites you have provided and whether you have set a context
 * site.
 *
 * Categories
 *
 * By default the Calendar class retrieves events without regard to categories. There are two
 * parameters that allow you to limit events based on which categories they belong to:
 *
 * If you provide an array of category entities via the "categories" parameter, the calendar class
 * will retrieve events assigned to *all* the categories provided.
 *
 * If you provide an array of category entities via the "or_categories" parameter, the calendar
 * class will retrieve events assigned to *any* of the categories provided.
 *
 * It is possible to combine these parameters to retrieve even more sophisticated sets of events.
 *
 * Audiences
 *
 * If you provide an array of audience entities in the "audiences" parameter, the calendar will
 * retrieve events that are assigned to *all* the audiences provided. There is not currently an 
 * "or_audiences" parameter.
 *
 * Views
 *
 * There are two ways to select a set of events based on a time span rather than a count:
 *
 * 1. Specify a view: Pass one of the following strings as the "view" parameter: 
 * 'daily','weekly','monthly','yearly','all'.
 *
 * 2. Specify an end_date parameter, formatted as "YYYY-MM-DD".
 *
 * Searches
 *
 * You can set the calendar to return a set of results matching a search phrase via the 
 * "simple_search" parameter. Just pass a string in and the Calendar will find events that contain
 * that exact phrase, or which occur on a given date if the searched phase is a parseable date.
 *
 * Even more sophisticated usage
 *
 * If you want to use the calendar class in an even more sophisticated way, you can use the 
 * "es_callback" parameter. Just pass in a PHP callback via this parameter, and the calendar 
 * will, when it builds its entity selector, call the callback function with a reference to the
 * entity selector as the first parameter. It is possible, in this manner, to make any complicated
 * restrictions you want. You only want events that have photos attached? That have the word 
 * "monkey" in their title? All this an more is possible with the es_callback parameter.
 *
 *
 * General scheme for internal workings of the class:
 *
 * - reasonCalendar()
 * -- init()
 * --- build_es()
 * 
 * - run()
 * -- grab_events()
 * --- set_bounds()
 * ---- get_end_date()
 * --- alter_es()
 * --- complete_es()
 * -- build_events_array()
 * --- foreach(events): process_event(event)
 *
 * - get_all_days()
 * -- if(no_view && no_end_date): determine_default_view()
 * -- if(no_view && no_end_date): set_bounds()
 *
 */
class reasonCalendar
{
	/**
	 * allowable keys that can be used in the init array
	 * if a key-value pair is passed to the object that is not in this array, it is ignored
	 * @var array
	 */
	protected $init_array_keys = array('site','ideal_count','start_date','view','categories','audiences','or_categories','end_date','automagic_window_snap_to_nearest_view','rels','simple_search','context_site','sharing_mode','default_view_min_days', 'es_callback','ongoing_count_all_occurrences','ongoing_count_pre_start_dates','ongoing_count_ends','cache_lifespan','cache_lifespan_meta');
	/**
	 * site entity that we are looking at
	 *
	 * note: when using this variable you should test to see if it is an array or object before proceeding
	 * Errors will otherwise occur.
	 *
	 * NOT populating this value will be constued as equivalent to entering all live sites that share events and setting the sharing_mode to 'shared_only'.
	 *
	 * @var mixed either an array of sites or a site entity object
	 */
	protected $site;
	/**
	 * the number of items that the calendar class tries to 
	 * optimize for when determining the default view
	 * @var integer
	 */
	protected $ideal_count = 22;
	/**
	 * the first date of the current calendar view
	 * @var string mysql-formatted date (e.g. yyyy-mm-dd)
	 */
	protected $start_date;
	/**
	 * the last date of the current calendar view
	 * @var string mysql-formatted date (e.g. yyyy-mm-dd)
	 */
	protected $end_date;
	/**
	 * the current defined view
	 * @var mixed view name (one of: daily, weekly, monthly, yearly, all, or NULL)
	 */
	protected $view = NULL;
	/**
	 * category entities joined by AND
	 * Only events that are related to *all* the entities in this array will 
	 * be included in the calendar
	 * @var array category entities
	 */
	protected $categories = array();
	/**
	 * audiences
	 * Only events that have *all* the audiences in this array will 
	 * be included in the calendar
	 * @var array audience entities
	 */
	protected $audiences = array();
	/**
	 * category entities joined by OR
	 * Events that are related to *any* of the entities in this array will 
	 * be included in the calendar
	 * @var array category entities
	 */
	protected $or_categories = array();
	/**
	 * relationships to be applied to calendar
	 *
	 * format: array(1=>array('rel_id'=>12345,'entity_ids'=>array($ent1_id,$ent2_id,...),'dir'=>'right'),2=>...);
	 * value options:
	 * dir: right,left (left used if not specified)
	 * @var array
	 */
	protected $rels = array();
	protected $simple_search = '';
	/**
	 * the main entity selector used to select the calendar's event entities
	 * @var object entity_selector
	 */
	protected $es;
	/**
	 * the event that occurs last in the calendar
	 * @var object entity
	 */
	protected $max_event;
	/**
	 * the event that occurs first in the calendar
	 * @var object entity
	 */
	protected $min_event;
	/**
	 * the actual event array returned by the entity selector
	 * @var array of entities
	 */
	var $events = array();
	/**
	 * a 2-d array of dates and event ids
	 * keys of array are dates; values are arrays of event ids
	 * @var array
	 */
	var $events_by_date = array();
	/**
	 * identifies source of end date
	 *
	 * true: end date was supplied by controlling class in init().
	 * false: end date was determined by calendar class
	 * @var boolean
	 */
	protected $end_date_supplied = false;
	/**
	 * Allows automagic timeframe window to be toggled on and off
	 *
	 * Use turn_automagic_on() and turn_automagic_off() to modify this var
	 *
	 * true: end date will be determined automagically if end date and/or view are not supplied
	 * false: if end date and/or view are not supplied, calendar will default to "all" view
	 * @var boolean
	 */
	protected $choose_window_automagically = true;
	/**
	 * Toggle for automagic timeframe window's "snap to nearest defined view" feature
	 *
	 * Use snap_automagic_to_nearest_view() and dont_snap_automagic_to_nearest_view() to modify this var
	 *
	 * true: default window will always be a defined view
	 * false: default window will end on the day that the ideal_count is surpassed
	 * @var boolean
	 */
	protected $automagic_window_snap_to_nearest_view = true;
	
	/**
	 * A lower bound on the number of days that the calendar will default to
	 *
	 * If you are not setting a given view or number of days (and thereby relying on the calendar to use
	 * the ideal_count parameter to select a view and/or number of days to display) you can force the
	 * calendar to select a view or number of days that contains at least this many days.
	 *
	 * Concrete example: I have a calendar that sometimes has lots of events and sometimes only a few.
	 * However, even when it has lots of events, I want to make sure that the default view is at least 
	 * one week out. Therefore, I can set the default_view_min_days to be 7; even if there are 50
	 * events today, the calendar will still show the entire week starting with today.
	 *
	 * @var integer
	 */
	protected $default_view_min_days = 1;
	
	/**
	 * The site that this calendar is being displayed within, if applicable
	 * @var object Reason entity
	 */
	protected $context_site;
	
	/**
	 * Should the calendar include all items, or just those that are shared?
	 *
	 * Possible values: 'all' means they are selected regardless of sharing status;
	 * 'shared_only' means that only shared items are selected.
	 * 'hybrid' means that private events from the context site are included (if given); all other sites only include shared items
	 *
	 * If not set, an appropriate default will be selected, based on
	 * how many sites given/context site/etc.
	 *
	 * Future enhancements to this module could include adding another
	 * sharing mode that only includes private items
	 *
	 * @var string 'all', 'shared_only', 'hybrid'
	 * @access private
	 */
	protected $sharing_mode; // Possible values: 'all', 'shared_only', 'hybrid'
	
	/**
	 * A php callback that can be set to modify the entity selector of the calendar
	 *
	 * The callback should take a reference to an entity selector as its first parameter.
	 *
	 * This callback can then modify the entity selector as needed.
	 *
	 * @var callback
	 * @access protected
	 */
	protected $es_callback;
	
	/**
	 * Should the automagic window determination algorithm count
	 * every occurrence of ongoing events, or just start/end dates?
	 *
	 * @var boolean
	 */
	protected $ongoing_count_all_occurrences = true;
	
	/**
	 * If $ongoing_count_all_occurrences is false, should the
	 * automagic window algorithm count the first occurrence of
	 * an event that falls before the start date?
	 *
	 * @var boolean
	 */
	protected $ongoing_count_pre_start_dates = true;
	
	/**
	 * If $ongoing_count_all_occurrences is false, should the
	 * automagic window algorithm count the last occurrences of events
	 * that fall during potential windows?
	 *
	 * @var boolean
	 */
	protected $ongoing_count_ends = true;
	
	/**
	 * How long to cache calendar data?
	 *
	 * This should be accessed with _get_cache_lifespan()
	 *
	 * @var integer seconds
	 */
	protected $cache_lifespan = 0;
	
	/**
	 * How long to cache calendar metadata?
	 *
	 * This includes the queries needed to determine automagic windows, etc.
	 *
	 * This should be accessed with _get_cache_lifespan_meta(), which handles a particular case
	 * -- specifically, if this is zero and cache_lifespan is nonzero, the meta lifespan is set to
	 * be the same as the cache_lifespan.
	 *
	 * @var integer seconds
	 */
	protected $cache_lifespan_meta = 0;
	
	/**
	 * The entity selector used by the calendar to select the events
	 * @var object entity_selector
	 */
	protected $base_es;
	
	/**
	 * Place to record if any events are in calendar at all.
	 *
	 * This can be expensive to figure out, so we store in in the init phase
	 * and refer to it later by accessing this variable.
	 *
	 * External access is through the method contains_any_events()
	 *
	 * @var boolean
	 * @access private
	 */
	protected $events_exist_in_calendar = true;
	
	protected $known_upper_limit;
	
	protected $known_closest_date_beyond;
	
	protected $known_lower_limit;
	
	protected $known_closest_date_before;
	
	/**
	 * constructor method
	 *
	 * handles basic initialization of object through init() method
	 *
	 * @param array $init_array array keyed using $init_array_keys class var; values as described in eqivalent class vars
	 */
	public function reasonCalendar( $init_array = array() )
	{
 		$this->init( $init_array, true );
	}
	/**
	 * initialize the calendar
	 *
	 * Takes the $init_array and populates the appropriate class variables
	 * as defined by $this->init_array_keys
	 * Then runs build_es() to assemble initial state of entity selector
	 *
	 * @param array $init_array array keyed using $init_array_keys class var; values as described in eqivalent class vars
	 * @todo find all examples of external calls, squash, then mark as protected
	 */
	function init( $init_array = array(), $internal_call = false )
	{
		if(!$internal_call)
		{
			trigger_error('reasonCalendar->init() called publically. This is deprecated and will crash Reason in future releases. Parameters should be set in the constructor instead.');
		}
		foreach($this->init_array_keys as $key)
		{
			if(isset($init_array[$key]))
			{
				$this->$key = $init_array[$key];
			}
		}
		if(!empty($this->end_date))
		{
			$this->end_date_supplied = true;
		}
		$this->_standardize_sharing_mode();
		$this->_add_or_rels(relationship_id_of('event_to_event_category'), $this->or_categories);
		$this->_add_rels(relationship_id_of('event_to_event_category'), $this->categories);
		$this->_add_rels(relationship_id_of('event_to_audience'), $this->audiences);
		$this->build_es();
	}
	protected function _get_cache_lifespan()
	{
		return $this->cache_lifespan;
	}
	protected function _get_cache_lifespan_meta()
	{
		if($this->cache_lifespan_meta)
			return $this->cache_lifespan_meta;
		else
			return $this->_get_cache_lifespan();
	}
	protected function _standardize_sharing_mode()
	{
		if(!empty($this->sharing_mode) && !in_array($this->sharing_mode,array('all','shared_only','hybrid')))
		{
			trigger_error('The reasonCalendar class only supports three sharing modes: "all","shared_only", and "hybrid"; "'.$this->sharing_mode.'" is not recognized. Falling back to automatic selection of sharing mode.');
			$this->sharing_mode = '';
		}
		if(empty($this->sharing_mode))
		{
			if(empty($this->site) || empty($this->context_site) && is_array($this->site))
			{
				$this->sharing_mode = 'shared_only';
			}
			elseif(!empty($this->context_site) && is_array($this->site))
			{
				$this->sharing_mode = 'hybrid';
			}
			else
			{
				$this->sharing_mode = 'all';
			}
		}
	}
	/**
	 * Assembles main entity selector
	 * @todo remove check of location field location for Reason 4 RC 1
	 */
	protected function build_es()
	{
		if(!empty($this->site))
		{
			if(is_array($this->site))
			{
				$site_ids = array();
				foreach($this->site as $site)
				{
					$site_ids[] = $site->id();
				}
				$this->es = new entity_selector( $site_ids );
				$this->es->description = 'Selecting events on multiple sites ';
			}
			else
			{
				$site_ids = array($this->site->id());
				$this->es = new entity_selector( $this->site->id() );
				$this->es->description = 'Selecting events on '.$this->site->get_value('name');
			}
		}
		else
		{
			$this->es = new entity_selector( array_keys( $this->_get_sharing_sites() ) );
			$this->es->description = 'Selecting events on all sharing sites';
		}
		$this->es->set_cache_lifespan( $this->_get_cache_lifespan() );
		if( $this->sharing_mode == 'shared_only' )
		{
			$this->es->add_relation( 'entity.no_share != 1');
		}
		elseif( $this->sharing_mode == 'hybrid' )
		{
			if(!empty($this->context_site) && in_array($this->context_site->id(),$site_ids) )
			{
				$es = new entity_selector($this->context_site->id());
				$es->add_type( id_of('event_type') );
				// $es->add_relation( 'show_hide.show_hide = "show"' );
				$es->limit_tables();
				$es->limit_fields();
				$es->set_cache_lifespan( $this->_get_cache_lifespan() );
				$tmp = $es->run_one();
				$this->es->add_relation('(`entity`.`id` IN ("'.implode('","',array_keys($tmp)).'") OR entity.no_share != 1 )');
			}
			else
			{
				$this->es->add_relation( 'entity.no_share != 1');
			}
		}
		$this->es->add_type( id_of('event_type') );
		if(!empty($this->context_site))
		{
			$this->es->set_env('site',$this->context_site->id());
		}
		$this->es->add_relation( 'show_hide.show_hide = "show"' );
		
		if(!empty($this->es_callback))
		{
			$callback_array = array();
			$callback_array[] =& $this->es;
			call_user_func_array($this->es_callback, $callback_array);
		}
		
		$this->base_es = carl_clone($this->es);
		
		if(!empty($this->site))
		{
			$test_es = carl_clone($this->es);
			$test_es->set_num(1);
			if (empty($this->es_callback)) // since we do not know what is contained in the callback - lets not limit if we have a callback.
			{
				$test_es->limit_tables(array('entity','show_hide'));
				$test_es->limit_fields();
			}
			$test_events = $test_es->run_one();
			if(empty($test_es))
			{
				$this->events_exist_in_calendar = false;
			}
			
		}
		
		//$this->max_event = $this->es->get_max('last_occurence');
		//$this->min_event = $this->es->get_min('datetime');
		
		//$this->es->set_order('dated.datetime ASC');
		if(!empty($this->simple_search))
		{
			$location_field = (in_array('location', get_fields_by_content_table('event'))) ? 'event.location' : 'location.location';
			$simple_search_text_fields = array('entity.name','meta.description','meta.keywords','chunk.content','chunk.author',$location_field,'event.sponsor','event.contact_organization');
			$simple_search_date_fields = array('dated.datetime','event.dates');
			$time = strtotime($this->simple_search);
			$search_chunks = array();
			if($time > 0)
			{
				$date = carl_date('Y-m-d',$time);
				foreach($simple_search_date_fields as $field)
				{
					$search_chunks[] = $field.' LIKE "%'.$date.'%"';
				}
			}
			$prepped = addslashes($this->simple_search);
			foreach($simple_search_text_fields as $field)
			{
				$search_chunks[] = $field.' LIKE "%'.$prepped.'%"';
			}
			
			// Not sure how to do this... the idea is to select categories that match the search term and additionally select 
			// events based on the categories... but I don't think there is a way at this point to wrap that all up in a single entity selector.
			//The basic problem is that add_left_relationship and add_left_relationship_field AND themselves to the end of the 
			// query rather than being able to be placed as needed in the WHERE statement.
			// If solved, we could do the same with site names.
			// -- mr
			
			/* if(!empty($this->site))
				$es = new entity_selector($this->site->id()); // this is deprecated since there might be multiple sites
			else
				$es = new entity_selector();
			$es->add_type(id_of('category_type'));
			$es->add_relation('entity.name LIKE "%'.$prepped.'%"');
			$matching_cats = $es->run_one();
			if(!empty($matching_cats))
			{
				//relationship2.entity_a = entity.id AND relationship2.entity_b IN (31532,32730,182628,199561,199636,201151) AND allowable_relationship2.id = relationship2.type AND allowable_relationship2.id = 193
				$this->es->add_left_relationship(array_keys($matching_cats), relationship_id_of('event_to_event_category'));
			}
			echo $this->es->get_one_query(); */
			
			$this->es->add_relation('('.implode(' OR ',$search_chunks).')');
		}
		
		if(!empty($this->rels))
		{
			foreach($this->rels as $rel)
			{
				if(empty($rel['rel_id']))
				{
					trigger_error('badly formed relationship -- no rel id');
					continue;
				}
				if(empty($rel['entity_ids']))
				{
					trigger_error('badly formed relationship -- no entity ids');
					continue;
				}
				if(empty($rel['dir']) || $rel['dir'] == 'left')
				{
					$this->es->add_left_relationship($rel['entity_ids'], $rel['rel_id']);
				}
				else
				{
					$this->es->add_right_relationship($rel['entity_ids'], $rel['rel_id']);
				}
			}
		}
		
	}
	/**
	 * Get the set of sites that share events
	 * @return array site entities keyed on Reason id
	 * @access private
	 */
	protected function _get_sharing_sites()
	{
		static $sharing_sites = array();
		if(empty($sharing_sites))
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_left_relationship( id_of('event_type'), relationship_id_of('site_shares_type'));
			$es->limit_tables('site');
			$es->limit_fields('site_state');
			$es->add_relation('site_state="Live"');
			$es->set_cache_lifespan( $this->_get_cache_lifespan_meta() );
			$sharing_sites = $es->run_one();
		}
		return $sharing_sites;
	}
	/**
	 * Add a set of relationships (AND-style)
	 *
	 * All events in calendar will have a relationship of the given relationship type with _all_
	 * of the given entities
	 *
	 * @param integer $relationship_id
	 * @param array $entities
	 * @access private
	 */
	protected function _add_rels($relationship_id, $entities)
	{
		if(!empty($entities))
		{
			foreach($entities as $entity)
			{
				$this->rels[] = array('rel_id' => $relationship_id, 'entity_ids' => array($entity->id()));
			}
			
		}
	}
	/**
	 * Add a set of relationships (OR-style)
	 *
	 * All events in calendar will have a relationship of the given relationship type with 
	 * _at least one_ of the given entities
	 *
	 * @param integer $relationship_id
	 * @param array $entities
	 * @access private
	 */
	protected function _add_or_rels($relationship_id, $entities)
	{
		if(!empty($entities))
		{
			$ids = array();
			foreach($entities as $entity)
			{
				$ids[] = $entity->id();
			}
			$this->rels[] = array('rel_id' => $relationship_id, 'entity_ids' => $ids);
		}
		
	}
	/**
	 * Do the necessary queries and assemble the calendar of events
	 *
	 * Runs grab_events() and build_events_array()
	 */
	public function run()
	{
		$this->grab_events();
		if(!empty($this->events))
		{
			$this->build_events_array();
			return true;
		}
		else
			return false;
	}
	/**
	 * If there appear to be any events on the site, this method 
	 * will populate $this->events with the events that correspond to the 
	 * start date, end date, and other parameters provided to this calendar
	 *
	 * This method calls, in order: set_bounds(), alter_es(), and complete_es() before running the entity selector
	 */
	protected function grab_events()
	{
		if($this->events_exist_in_calendar)
		{
			$this->set_bounds();
			$this->alter_es();
			$this->complete_es();
			// we only need to run the events if it will produce results
			if($this->start_date <= $this->end_date)
			{
				//$this->es->limit_fields();
				//$this->es->exclude_tables_dynamically();
				$this->events = $this->es->run_one();
			}
		}
	}
	/**
	 * Determine the start and end dates
	 *
	 * Start date, if not provided, will be considered to be today.
	 * End date, if not explicitly provided, will be either:
	 * a) determined from the view given, or
	 * b) determined by the determine_default_view() method
	 */
	protected function set_bounds()
	{
		if(empty($this->start_date))
			$this->start_date = date('Y-m-d');
		if(empty($this->end_date))
		{
			if(!empty($this->view))
				$this->end_date = $this->get_end_date( $this->view );
			else
				$this->end_date = $this->get_end_date();
		}
	}
	/**
	 * Determine the end date
	 *
	 * Returns:
	 * a) the already provided end date,
	 * b) the end date based on the view provided, or
	 * c) a reasonable end date based on $this->ideal_count
	 *
	 * Calls on get_max_date()
	 * 
	 * @param mixed $view name of current view or NULL if not provided
	 * @return string $end_date mysql-formatted last date in calendar
	 */
	public function get_end_date( $view = NULL )
	{
		if(!empty($this->end_date)) // end date either given or already determined
			return $this->end_date;
		if(empty($view))
		{
			$view = $this->view;
		}
		if(!empty($view)) // view given but no end date given or determined yet
		{
			if($view == 'daily')
			{
				return $this->start_date;
			}
			elseif($view == 'all')
			{
				/* trigger_error('get_max_date called');
				return $this->get_max_date(); */
				return '9999-12-31';
			}
			else
			{
				$start_array = explode('-',$this->start_date);
				if($view == 'weekly')
				{
					$end = get_unix_timestamp($start_array[0].'-'.$start_array[1].'-'.str_pad($start_array[2]+6, 2, '0', STR_PAD_LEFT));
				}
				elseif($view == 'monthly')
				{
					$end = get_unix_timestamp($start_array[0].'-'.str_pad($start_array[1]+1, 2, '0', STR_PAD_LEFT).'-'.str_pad($start_array[2]-1, 2, '0', STR_PAD_LEFT));
				}
				elseif($view == 'yearly')
				{
					$end = get_unix_timestamp($start_array[0]+1 .'-'.$start_array[1].'-'.str_pad($start_array[2]-1, 2, '0', STR_PAD_LEFT));
				}
				else
				{
					trigger_error('$view ('.$view.') not a recognized value');
				}
			}
			return carl_date('Y-m-d', $end);
		}
		else // no view in use and no end date determined yet
		{
			/* trigger_error('get_max_date called');
			return $this->get_max_date(); */
			return '9999-12-31';
		}
	}
	/**
	 * Run through the events returned by the entity selector in $this->events
	 * and build an array organized by date => events in $this->events_by_date
	 *
	 * Calls on process_event()
	 * @return void
	 */
	protected function build_events_array() // {{{
	{
		while(list($event_id) = each($this->events) )
		{
			$this->process_event($event_id);
		}
		ksort($this->events_by_date);
		/* foreach($this->events_by_date as $day=>$event_ids)
		{
			$events = array();
			foreach($event_ids as $id)
			{
				$events[] = $this->events[$id];
			}
			usort( $events, 'compare_times' );
			$this->events_by_date[$day] = array();
			foreach( $events as $event )
			{
				$this->events_by_date[$day][] = $event->id();
			}
		} */
	} // }}}
	public function sort_event_ids_by_time_of_day($event_ids)
	{
		$events = array();
		foreach($event_ids as $id)
		{
			$events[] = $this->events[$id];
		}
		usort( $events, 'compare_times' );
		$time_sorted_event_ids = array();
		foreach( $events as $event )
		{
			$time_sorted_event_ids[] = $event->id();
		}
		return $time_sorted_event_ids;
	}
	/**
	 * Populate $this->events_by_date for a particular event based on the dates stored in the event entity provided
	 *
	 * @param integer $event_id
	 * @return void
	 */
	protected function process_event($event_id) // {{{
	{
		$dates = explode(', ', $this->events[$event_id]->get_value('dates') );
		foreach($dates as $date)
		{
			if($date > $this->end_date)
			{
				break; // no need to continue b/c dates are in chronological order
			}
			elseif($date >= $this->start_date)
			{
				$this->events_by_date[$date][] = $event_id;
			}
		}
	} // }}}
	/**
	 * Overloadable method -- called during grab_events before the entity selector is run
	 *
	 * @deprecated Use the es_callback instead
	 * @return void
	 */
	protected function alter_es()
	{
	}
	/**
	 * Handle the final touches to the entity selector to limit items selected to a particular date range
	 * Called on by grab_events immediately before entity selector is run
	 *
	 * @return void
	 */
	protected function complete_es()
	{
		/*$this->es->add_table( 'event_date_extract' );
		$this->es->add_relation('event_date_extract.date >= "'.$this->start_date.'"' );
		$this->es->add_relation('event_date_extract.entity_id = entity.id' ); */
		$this->es->add_relation( 'event.last_occurence >= "'.$this->start_date.'"' );
		if($this->get_view() != 'all')
		{
			$this->es->add_relation( 'dated.datetime <= "'.$this->end_date.' 23:59:59"' );
			//$this->es->add_relation('event_date_extract.date >= "'.$this->end_date.'"' );
		}
		if( $this->view == 'daily' )
		{
			$this->es->add_relation('event.dates LIKE "%'.$this->start_date.'%"');
		}
	}
	/**
	 * Don't fully understand this method yet --mr
	 * 
	 * @return array $days => $ids
	 */
	public function get_all_days( $limit_to_window = true, $choose_view_automagically = true )
	{
		if( $limit_to_window )
		{
			if( empty( $this->view ) && $this->choose_window_automagically && !$this->end_date_supplied )
			{
				$this->determine_default_view();
				$this->set_bounds();
				// return $this->get_events_by_date( $this->get_min_date(), $this->default_end_date );
				return $this->get_events_by_date( $this->start_date, $this->default_end_date );
			}
			else
			{
				return $this->get_events_by_date( $this->start_date, $this->end_date );
			}
		}
		else
		{
			//return $this->get_events_by_date( $this->get_min_date(), $this->get_max_date() );
			return $this->get_events_by_date( '0000-00-00', '9999-12-31' );
		}
	}
	/**
	 * Provides all entities between the first and last dates provides
	 *
	 * @param string $first mysql-formatted date (yyyy-mm-dd)
	 * @param string $last mysql-formatted date (yyyy-mm-dd)
	 * @return array $days => $ids
	 */
	public function get_events_by_date( $first, $last )
	{
		$ret = array();
		foreach( $this->events_by_date as $date=>$ids )
		{
			if($date >= $first && $date <= $last)
			{
				$sorted_ids = $this->sort_event_ids_by_time_of_day($ids);
				$ret[$date] = $sorted_ids;
			}
		}
		return $ret;
	}
	/**
	 * Gets all the events pertinent to the calendar
	 * @return array $events
	 */
	public function get_all_events()
	{
		return $this->events;
	}
	/**
	 * Gets the start date of the current window on the calendar
	 * @return string $start_date mysql-formatted date (yyyy-mm-dd)
	 */
	public function get_start_date()
	{
		return $this->start_date;
	}
	/**
	 * Gets the current view
	 *
	 * returns a string identifying the current view or NULL if no view available
	 *
	 * @return mixed $view
	 */
	public function get_view()
	{
		return $this->view;
	}
	/**
	 * Gets the date of the earliest occurrence in the calendar
	 *
	 * @return string $min_date mysql-formatted date (yyyy-mm-dd)
	 */
	public function get_min_date()
	{
		//trigger_error('get_min_date() called');
		if(empty($this->min_event))
		{
			//trigger_error('min date query run');
			$this->min_event = $this->base_es->get_min('datetime');
		}
		if(!empty($this->min_event))
		{
			$this->known_lower_limit = substr($this->min_event->get_value('datetime'),0,10);
			return $this->known_lower_limit;
		}
		return false;
	}
	/**
	 * Provides the date of the last event occurrence in the calendar
	 *
	 * @return string $max_date mysql-formatted date (yyyy-mm-dd)
	 */
	public function get_max_date()
	{
		//trigger_error('get_max_date() called');
		if(empty($this->max_event))
		{
			//trigger_error('max date query run');
			$this->max_event = $this->base_es->get_max('last_occurence');
		}
		if(!empty($this->max_event))
		{
			$this->known_upper_limit = $this->max_event->get_value('last_occurence');
			return $this->known_upper_limit;
		}
		return false;
	}
	/**
	 * Provides an array of the defined views supported by the calendar class
	 *
	 * Should be: array('daily','weekly','monthly','yearly','all')
	 *
	 * @return array $views
	 */
	public function get_views()
	{
		static $views = array('day'=>'daily','week'=>'weekly','month'=>'monthly','year'=>'yearly','all'=>'all');
		return $views;
	}
	/**
	 * Gets the "AND-style" category entities applied to this calendar
	 *
	 * @return array $categories
	 */
	public function get_categories()
	{
		return $this->categories;
	}
	/**
	 * Gets the "OR-style" category entities applied to this calendar
	 *
	 * @return array $categories
	 */
	public function get_or_categories()
	{
		return $this->or_categories;
	}
	/**
	 * Gets the audience entities applied to this calendar
	 *
	 * @return array $audiences
	 */
	public function get_audiences()
	{
		return $this->audiences;
	}
	
	/**
	 * Choose an appropriate view using the $ideal_count value
	 *
	 * This code should only gets run if no view and no end date is specified
	 *
	 * @return void
	 */
	protected function determine_default_view() // {{{
	{
		if(!empty($this->view) )
		{
			trigger_error('determine_default_view() called unneccessarily');
			return;
		}
		/* populate the array with events */
		$out = $this->calculate_dates_out();
		
		$total_count = 0;
		
		$event_count = array(	'daily'		=> 0,
								'weekly'	=> 0,
								'monthly'	=> 0,
								'yearly'	=> 0 );

		/* go through day by day and add the total to the appropriate views
		   stop if a view is done being filled and has enough events in it */
		foreach( $this->events_by_date as $day => $ids )
		{
			foreach($ids as $k=>$id)
			{
				if(!$this->include_event_in_automagic_calculation($id,$day))
					unset($ids[$k]);
			}
			$current_count = count($ids);
			if(!$current_count)
			{
				continue;
			}
			$total_count += $current_count;
			if( !$this->automagic_window_snap_to_nearest_view && $total_count >= $this->ideal_count )
			{
				$this->default_end_date = $day;
				break;
			}
			/* we don't need to look more than a year out */
			if( $day > $out['year'] )
			{
				break;
			}
			if( $day == $this->start_date )
				$event_count['daily'] += $current_count;
			if( $day == $this->start_date AND $event_count['daily'] >= $this->ideal_count )
			{
				//echo 'daily view has enough events: '.$event_count['daily'].'<br/>';
				$big = 'daily';
				break;
			}
			if( $day <= $out['week'] )
				$event_count['weekly'] += $current_count;
			if( $day == $out['week'] AND $event_count['weekly'] >= $this->ideal_count )
			{
				//echo 'weekly view has enough events: '.$event_count['weekly'].'<br/>';
				$big = 'weekly';
				break;
			}
			if( $day <= $out['month'] )
				$event_count['monthly'] += $current_count;
			if( $day == $this->start_date AND $event_count['monthly'] >= $this->ideal_count )
			{
				//echo 'monthly view has enough events: '.$event_count['monthly'].'<br/>';
				$big = 'monthly';
				break;
			}
			if( $day <= $out['year'] )
				$event_count['yearly'] += $current_count;
			if( $day == $out['year'] AND $event_count['yearly'] >= $this->ideal_count )
			{
				//echo 'yearly view has enough events: '.$event_count['yearly'].'<br/>';
				$big = 'yearly';
				break;
			}
		}
		if( empty( $big ) AND $event_count['yearly'] >= $this->ideal_count )
		{
			//echo 'yearly view has enough events: '.$event_count['yearly'].'<br/>';
			$big = 'yearly';
		}

		/* now compare the view that has enough with the one before it and use a
		   logarithmic scale to determine which is more appropriate
		   
		   Why is a logarithmic scale used?
		   
		   This is guesswork after the fact, but it appears that the goal is to choose the view
		   that is closest in *scale* to the ideal count rather than the view that is linearly
		   closest. For example, if the daily view has 1 event, the weekly view has 100 events,
		   and the ideal count is 50, we want to choose the weekly view, as it is only 2x the ideal
		   (whereas the ideal is 50x the daily view). If we just picked the closest number on a
		   linear scale, we would choose the daily view in this case, which is not as good a result.
		   */
		if(!empty($big))
		{
			if( $big == 'daily' ) // pick the daily view
				$this->default_end_date = $this->start_date;
			elseif( 1 == $this->ideal_count ) // just pick the first view with any events
			{
				if($event_count['daily'] > 0)
					$this->default_end_date = $this->start_date;
				elseif($event_count['weekly'] > 0)
					$this->default_end_date = $out['week'];
				elseif($event_count['monthly'] > 0)
					$this->default_end_date = $out['month'];
				elseif($event_count['yearly'] > 0)
					$this->default_end_date = $out['year'];
			}
			elseif( $big == 'weekly' ) // choose between daily and weekly views
			{
				$x1 = log( $event_count['daily'] ) / log( $this->ideal_count );
				$x2 = log( $event_count['weekly'] ) / log( $this->ideal_count );
				if( (1-$x1) < ($x2-1) )
					$this->default_end_date = $this->start_date;
				else
					$this->default_end_date = $out['week'];
			}
			elseif( $big == 'monthly' ) // choose between weekly and monthly views
			{
				$x1 = log( $event_count['weekly'] ) / log( $this->ideal_count );
				$x2 = log( $event_count['monthly'] ) / log( $this->ideal_count );
				if( (1-$x1) < ($x2-1) )
					$this->default_end_date = $out['week'];
				else
					$this->default_end_date = $out['month'];
			}
			elseif( $big == 'yearly' ) // choose between monthly and yearly views
			{
				$x1 = log( $event_count['monthly'] ) / log( $this->ideal_count );
				$x2 = log( $event_count['yearly'] ) / log( $this->ideal_count );
				if( (1-$x1) < ($x2-1) )
					$this->default_end_date = $out['month'];
				else
					$this->default_end_date = $out['year'];
			}
		}
		
		if(empty($this->default_end_date))
		{
			/* trigger_error('get_max_date called');
			$this->default_end_date = $this->get_max_date(); */
			$this->default_end_date = '9999-12-31';
		}
		
		if ($this->default_end_date == $this->start_date)
			$this->view = 'daily';
		elseif ($this->default_end_date == $out['week'])
			$this->view = 'weekly';
		elseif ($this->default_end_date == $out['month'])
			$this->view = 'monthly';
		elseif ($this->default_end_date == $out['year'])
			$this->view = 'yearly';
		//elseif ($this->default_end_date == $this->get_max_date())
		elseif ($this->default_end_date == '9999-12-31')
		{
			$this->view = 'all';
		}
		
		if($this->default_view_min_days > 1)
		{
			$date_array = explode('-',$this->start_date);
		
			$year = $date_array[0];
			$month = $date_array[1];
			$day = $date_array[2];
			$min_last_date = carl_date('Y-m-d',carl_mktime('','','',$month,($day + $this->default_view_min_days - 1),$year));
			
			if($this->default_end_date < $min_last_date)
			{
				if($this->automagic_window_snap_to_nearest_view)
				{
					// find the smallest view that goes out far enough
					if($this->view == 'daily' && $this->start_date < $min_last_date)
					{
						$this->view = 'weekly';
						$this->default_end_date = $out['week'];
					}
					if($this->view == 'weekly' && $out['week'] < $min_last_date)
					{
						$this->view = 'monthly';
						$this->default_end_date = $out['month'];
					}
					if($this->view == 'monthly' && $out['month'] < $min_last_date)
					{
						$this->view = 'yearly';
						$this->default_end_date = $out['year'];
					}
					if($this->view == 'yearly' && $out['year'] < $min_last_date)
					{
						$this->view = 'all';
						$this->default_end_date = '9999-12-31';
					}
				}
				else
				{
					$this->default_end_date = $min_last_date;
				}
			}
		}
		
		$this->end_date = $this->default_end_date;
	} // }}}
	
	protected function include_event_in_automagic_calculation($event_id,$day)
	{
		if($this->ongoing_count_all_occurrences)
			return true;
		
		if($this->event_is_ongoing($this->events[$event_id]))
		{
			$first_day = substr($this->events[$event_id]->get_value('datetime'),0,10);
			
			if( $day == $first_day ||
				( $this->ongoing_count_ends && $day == $this->events[$event_id]->get_value('last_occurence') ) ||
				( $this->ongoing_count_pre_start_dates && $this->get_start_date() == $day && $first_day < $this->get_start_date() )
			)
				return true;
			else
				return false;
		}
		return true;
	}
	/**
	 * Is an event an all-day/non-time-specific event?
	 *
	 * Events are currently considered all-day events if no time was
	 * entered (i.e. the time portion of datetime is 00:00:00).
	 *
	 * This is not really ideal as it means that Reason has no
	 * way to distinguish between an all-day event and an event
	 * that occurrs precisely at midnight. In the future it may be
	 * desirable to add an additional field to the event type
	 * that positively identifies all-day/non-time-specific events.
	 *
	 * @param object $event entity
	 * @return boolean
	 */
	function event_is_all_day_event($event)
	{
		return (substr($event->get_value( 'datetime' ), 11) == '00:00:00');
	}
	
	/**
	 * Is an event an ongoing event?
	 *
	 * An ongoing event is one which is considered to be happening
	 * all or most of the time for its duration.
	 *
	 * Ongoing events recur regularly and are (at the moment) all-day events.
	 * If future improvements are made to event display code, the definition
	 * of an ongoing event may be broadened to include events with times
	 * or long-term recurring events that happen less regularly.
	 *
	 * @param object $event entity
	 * @return boolean
	 */
	public function event_is_ongoing($event)
	{
		if($event->get_value('recurrence') == 'none' || $event->get_value('recurrence') == '')
			return false;
		
		if(!$this->event_is_all_day_event($event))
			return false;
		
		if(count(explode(',',$event->get_value('dates'))) < 3)
			return false;
		
		if($event->get_value('recurrence') == 'daily' && $event->get_value('frequency') == 1)
			return true;
		
		if($event->get_value('recurrence') == 'weekly')
		{
			$num_days = 0;
			foreach(array('sunday','monday','tuesday','wednesday','thursday','friday','saturday') as $day)
			{
				if($event->get_value($day) == 'true')
					$num_days++;
			}
			return ($num_days > 4);
		}
		
		return false;
	}
	
	/**
	 * Get the dates corresponding to one week, month and year from the start date
	 *
	 * @return array ('week'=>$week,'month'=>$month,'year'=>$year)
	 *
	 * @todo When support is dropped for php 5.1, switch to simpler code
	 */
	public function calculate_dates_out() // {{{
	{
		list($year,$month,$day) = explode('-',$this->start_date);
		
		$year_out = carl_mktime(0,0,0,$month,$day,$year + 1);
		$month_out = carl_mktime(0,0,0,$month+1,$day,$year);
		$week_out = carl_mktime(0,0,0,$month,$day+7,$year);
		
		/* 
		// Note: this is simpler code, but it won't support dates outside
		// the Unix era until support for 5.1- is dropped.
		
		$u_start = prettify_mysql_datetime($this->start_date,'U');
		
		$year_out = strtotime('+1 year',$u_start);
		$month_out = strtotime('+1 month',$u_start);
		$week_out = strtotime('+1 week',$u_start); */
		
		$week = carl_date('Y-m-d',$week_out);
		$month = carl_date('Y-m-d',$month_out);
		$year = carl_date('Y-m-d',$year_out);
		
		return array('week'=>$week,'month'=>$month,'year'=>$year);
	} // }}}
	
	public function turn_automagic_on()
	{
		$this->choose_window_automagically = true;
	}
	public function turn_automagic_off()
	{
		$this->choose_window_automagically = false;
	}
	public function snap_automagic_to_nearest_view()
	{
		$this->automagic_window_snap_to_nearest_view = true;
	}
	public function dont_snap_automagic_to_nearest_view()
	{
		$this->automagic_window_snap_to_nearest_view = false;
	}
	public function contains_any_events()
	{
		return $this->events_exist_in_calendar;
	}
	public function contains_any_events_before($date)
	{
		if(!$this->contains_any_events())
			return false;
		if(!empty($this->known_lower_limit) && $this->known_lower_limit < $date)
		{
			//echo '<strong>contains_any_events_before</strong>: req.date ('.$date.') after kll ('.$this->known_lower_limit.'); ret true<br />';
			return true;
		}
		elseif(!empty($this->known_closest_date_before) && $this->known_closest_date_before > $date)
		{
			//echo '<strong>contains_any_events_before</strong>: req.date ('.$date.') before kcdb ('.$this->known_closest_date_before.'); ret false<br />';
			return false;
		}
		$test_es = carl_clone($this->base_es);
		$test_es->set_num(1);
		$test_es->add_relation('dated.datetime < "'.addslashes($date).'"');
		$test_es->limit_fields();
		$test_es->exclude_tables_dynamically();
		$test_es->set_cache_lifespan($this->_get_cache_lifespan_meta());
		$test_results = $test_es->run_one();
		if(!empty($test_results))
		{
			$result = current($test_results);
			//echo '<strong>contains_any_events_before</strong>: found event before req.date '.$date.'. id: '.$result->id().'; datetime: '.$result->get_value('datetime').'; ret true<br />';
			$date = prettify_mysql_datetime($result->get_value('datetime'),'Y-m-d');
			$this->known_lower_limit = carl_date('Y-m-d',strtotime($date.' -1 day'));
			return true;
		}
		else
		{
			//echo '<strong>contains_any_events_before</strong>: no events found before req.date ('.$date.'); ret false<br />';
			$this->known_closest_date_before = $date;
			return false;
		}
	}
	public function contains_any_events_after($date)
	{
		if(!$this->contains_any_events())
			return false;
		if(!empty($this->known_upper_limit) && $this->known_upper_limit > $date)
		{
			//echo '<strong>contains_any_events_after</strong>: req.date ('.$date.') before kul ('.$this->known_lower_limit.'); ret true<br />';
			return true;
		}
		elseif(!empty($this->known_closest_date_after) && $this->known_closest_date_after < $date)
		{
			//echo '<strong>contains_any_events_after</strong>: req.date ('.$date.') after kcda ('.$this->known_closest_date_after.'); ret false<br />';
			return false;
		}
		$test_es = carl_clone($this->base_es);
		$test_es->set_num(1);
		$test_es->add_relation('event.last_occurence > "'.addslashes($date).'"');
		$test_es->limit_fields();
		$test_es->exclude_tables_dynamically();
		$test_es->set_cache_lifespan($this->_get_cache_lifespan_meta());
		//$test_es->optimize('');
		//echo $test_es->get_one_query().'<br />';
		$test_results = $test_es->run_one();
		if(!empty($test_results))
		{
			$result = current($test_results);
			//echo '<strong>contains_any_events_after</strong>: found event after req.date '.$date.'. id: '.$result->id().'; datetime: '.$result->get_value('datetime').'; ret true<br />';
			$this->known_upper_limit = substr($result->get_value('datetime'),0,10);
			return true;
		}
		else
		{
			//echo '<strong>contains_any_events_after</strong>: no events found after req.date ('.$date.'); ret false<br />';
			$this->known_closest_date_after = $date;
			return false;
		}
	}
	
}

?>
