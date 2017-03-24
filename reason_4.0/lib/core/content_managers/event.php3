<?php
/**
 * A content manager for events
 * @package reason
 * @subpackage content_managers
 */
 
 /**
  * Store the class name so that the admin page can use this content manager
  */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'event_handler';
 /**
  * Include dependencies
  */
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	require_once CARL_UTIL_INC . 'basic/misc.php';
	require_once INCLUDE_PATH . '/disco/plugins/input_limiter/input_limiter.php';
	reason_include_once('classes/event.php');
	reason_include_once('classes/api/geocoder.php');
	
	/**
	 * A content manager for event entities
	 *
	 * Provides a custom interface for adding and editing events in Reason
	 *
	 * @todo support google maps premiere keys
	 */
	class event_handler extends ContentManager 
	{
		var $years_out = 3;
		var $sync_vals = array();
		var $registration_page_types = array('event_registration','event_signup',);
		
		function should_run_api()
		{
			return ( ($this->geolocation_enabled() && isset($_REQUEST['module_api']) && ($_REQUEST['module_api'] == 'geocoder') ) );
		}
		
		function run_api()
		{
			if ($this->geolocation_enabled())
			{
				$geocoderAPI = new ReasonGeocoderAPI();
				$geocoderAPI->run();
			}
		}
		
		/**
		 * geolocation is currently not enabled in the core - but we still want to see if the upgrade script has been run and
		 * prompt the user to run it if not. once the core events module and others are updated to use the geolocation info
		 * we will enable it in the content manager.
		 * 
		 * @todo fix to have value based upon the constant once we are ready with modules
		 */
		function geolocation_enabled()
		{
			if (!isset($this->_geolocation_enabled))
			{
				if (!$this->_event_type_supports_geolocation())
				{
					trigger_error('The Reason 4 Beta 8 to Beta 9 event location upgrade script has not been run. Please run it so that once geolocation features
					               are available to Reason event modules the geolocation interface will be exposed in the content manager.');
					$this->_geolocation_enabled = false;
				}
				else
				{
					$this->_geolocation_enabled = (defined("REASON_EVENT_GEOLOCATION_ENABLED")) ? constant("REASON_EVENT_GEOLOCATION_ENABLED") : false;
				}
			}
			return $this->_geolocation_enabled;
		}
		
		function _event_type_supports_geolocation()
		{
			if (!isset($this->_event_type_supports_geolocation))
			{
				$this->_event_type_supports_geolocation = ($this->is_element('latitude') &&  $this->is_element('longitude') && $this->is_element('address'));
			}
			return $this->_event_type_supports_geolocation;
		}
		
		function init_head_items()
		{
			$this->head_items->add_javascript(JQUERY_URL, true); // uses jquery - jquery should be at top
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH .'event.js');
			if ($this->geolocation_enabled())
			{
				$base_gmap_url = (HTTPS_AVAILABLE) ? 'https://maps-api-ssl.google.com/maps/api/js' : 'http://maps.google.com/maps/api/js';
				$this->head_items->add_javascript($base_gmap_url . '?v=3&libraries=geometry&sensor=false', true);
				$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'content_managers/event/geo.js');
				$this->head_items->add_stylesheet(WEB_JAVASCRIPT_PATH . 'content_managers/event/geo.css');
			}
		}
		
		function check_for_recurrence_field_existence()
		{
			if(!$this->_is_element('recurrence'))
			{
				$msg = 'Recurrence upgrade script needs to be run. A Reason administrator needs to run the script located at '.REASON_HTTP_BASE_PATH.'scripts/upgrade/4.0b3_to_4.0b4/event_repeat_field_name_change.php';
				echo $msg;
				trigger_error($msg);
				die();
			}
		}
			
		function alter_data() // {{{
		{
			if ($this->is_element('geopoint')) $this->remove_element('geopoint'); // never want to set this directly.
			$this->check_for_recurrence_field_existence();
			//test_reason_repeating_events($this->get_value('id'));
			$site = new entity( $this->get_value( 'site_id' ) );

			// create all additional elements
			$this->add_element('hr1', 'hr');
			$this->add_element('hr2', 'hr');
			$this->add_element('hr3', 'hr');
			$this->add_element('hr4', 'hr');
			
			if(REASON_USES_DISTRIBUTED_AUDIENCE_MODEL)
				$es = new entity_selector($site->id());
			else
				$es = new entity_selector();
			$es->add_type(id_of('audience_type'));
			$es->limit_tables();
			$es->limit_fields();
			$es->set_num(1);
			$result = $es->run_one();
			
			if(!empty($result))
			{
				$this->add_element('audiences_heading', 'comment', array('text'=>'<h4>Visibility</h4> To which groups do you wish to promote this event? (Please enter at least one)'));
				$this->add_relationship_element('audiences', id_of('audience_type'), 
				relationship_id_of('event_to_audience'),'right','checkbox',REASON_USES_DISTRIBUTED_AUDIENCE_MODEL,'sortable.sort_order ASC');
			}
			
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_left_relationship(id_of('category_type'), relationship_id_of('site_to_type'));
			$es->add_relation('entity.id = "'.$site->id().'"');
			$es->limit_tables();
			$es->limit_fields();
			$es->set_num(1);
			$result = $es->run_one();
			
			if(!empty($result))
			{
				$this->add_relationship_element('categories', id_of('category_type'), relationship_id_of('event_to_event_category'),'right','checkbox',true,'entity.name ASC');
			}
			
			$this->add_element('date_and_time', 'comment', array('text'=>'<h4>Date, Time, and Duration of Event</h4>'));
			$this->add_element('info_head', 'comment', array('text'=>'<h4>Title and Description</h4>'));
			$this->add_element('other_info_head', 'comment', array('text'=>'<h4>Other Information</h4>'));

			// change element types if necessary
			$hours = array();
			for( $i = 0; $i <= 24; $i++ )
				$hours[$i] = $i;

			$minutes = array();
			$minutes[0] = '00';
			$minutes[5] = '05';
			for( $i = 10; $i <= 55; $i += 5 )
				$minutes[$i] = $i;

			$this->change_element_type( 'datetime','textDateTime' );
			
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			$this->change_element_type( 'recurrence', 'select_no_sort', 
				array(	'options' => array(	'none'=>'Never (One-Time Event)', 
											'daily'=>'Daily', 
											'weekly'=>'Weekly', 
											'monthly'=>'Monthly', 
											'yearly'=>'Yearly'), 
											'add_empty_value_to_top' => false,
					) );
			$this->change_element_type( 'minutes', 'select_no_label', array('options'=>$minutes,'sort_options'=>false) );
			$this->change_element_type( 'hours', 'select_no_sort', array('options'=>$hours) );
			$this->change_element_type( 'frequency', 'text', array('size'=>3) );
			$this->change_element_type( 'week_of_month','protected' );
			$this->change_element_type( 'month_day_of_week','protected' );
			$this->change_element_type( 'term_only','protected' );
			$this->change_element_type( 'author', 'protected');
			$this->change_element_type( 'end_date', 'textDate' );
			$this->change_element_type( 'last_occurence', 'protected' );
			if(!$this->element_is_hidden('no_share'))
				$this->change_element_type( 'no_share', 'select', array( 'options' => array( 'Shared', 'Private' ), 'add_empty_value_to_top' => false, ) );
			$this->change_element_type( 'dates', $this->get_value( 'dates' ) ? 'solidtext' : 'protected' );

			// format the elements
			$this->set_display_name( 'name', 'Event Title' );
			$this->set_display_name( 'sponsor', 'Sponsoring department or organization' );
			$this->set_display_name( 'contact_username', 'Username of Contact Person' );
			$this->set_display_name( 'contact_organization', 'Contact Department or Group' );
			$this->set_display_name( 'datetime', 'Date &amp; time of event' );
			$this->set_comments(	 'datetime', form_comment( 'Month/Day/Year' ) );
			$this->set_display_name( 'description', 'Brief Description of Event' ); //get default loki type
			$this->set_comments(	 'description', form_comment( 'A brief summary of the event' ) );
			$this->set_display_name( 'content', 'Full Event Information' );
			$this->set_comments(	 'content', form_comment( 'Here is where you can enter all of the important information about the event.' ) );
			$this->set_display_name( 'url', 'URL for More Info' );
			$this->set_comments(	 'url', form_comment( 'If this event has a site dedicated to it, enter that URL here.' ) );
			$this->set_display_name( 'hours', 'Duration' );
			$this->set_comments(	 'hours', ' Hours');
			$this->set_comments(	 'minutes', ' Minutes' );
			$this->set_display_name( 'sunday', 'On' );
			$this->set_comments(	 'sunday', ' Sunday' );
			$this->set_display_name( 'monday', ' ' );
			$this->set_comments(	 'monday', ' Monday' );
			$this->set_display_name( 'tuesday', ' ' );
			$this->set_comments(	 'tuesday', ' Tuesday' );
			$this->set_display_name( 'wednesday', ' ' );
			$this->set_comments(	 'wednesday', ' Wednesday' );
			$this->set_display_name( 'thursday', ' ' );
			$this->set_comments(	 'thursday', ' Thursday' );
			$this->set_display_name( 'friday', ' ' );
			$this->set_comments(	 'friday', ' Friday' );
			$this->set_display_name( 'saturday', ' ' );
			$this->set_comments(	 'saturday', ' Saturday' );
			$this->set_display_name( 'recurrence', 'Repeat This Event' );
			$this->set_display_name( 'frequency', 'Every' );
			$this->set_display_name( 'dates', 'Event Occurs On' );
			$this->set_display_name( 'week_of_month', 'On the' );
			$this->set_display_name( 'month_day_of_week', ' ' );
			$this->set_display_name( 'show_hide', 'Event Status' );
			$this->change_element_type( 'show_hide', 'radio_no_sort', array( 
				'options' => array( 
				//	'tentative' => 'This event is tentative; only display it on calendar planning views.',
					'show' => 'Publish this event',
					'hide' => 'Hide this event from all calendars', 
				//	'cancelled' => 'This event has been cancelled; display it with an appropriate flag.' 
				), 
				'add_empty_value_to_top' => false, ) );
			$this->set_display_name( 'end_date', 'Repeat this event until' );
			$this->set_comments(	 'end_date', form_comment( 'Month/Day/Year' ));
			$this->set_comments(	 'end_date', form_comment( 'If no date is chosen, this event will repeat indefinitely.' ));
			$this->set_display_name( 'no_share', 'Sharing' );
			$this->set_comments(	 'no_share', form_comment( 'If this event is <em>shared</em>, it will be available for other sites to include on their calendars, and may appear on a common events calendar. If it is <em>private</em>, it will only show up on this site\'s events calendar.' ));
			$this->set_comments(	 'frequency', ' <span id="frequencyComment">day(s)</span> ' );
			$this->set_comments(	 'month_day_of_week', ' of the month' );
			$this->set_display_name( 'monthly_repeat',' ' );
			if($this->_should_offer_split())
			{
				$this->set_comments('dates',form_comment('<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'EventSplit' )).'" class="eventSplitLink">Split into separate event items</a>'));
			}

			// set requirements
			$this->add_required( 'datetime' );
			$this->add_required( 'recurrence' );
			$this->add_required( 'show_hide' );
			
			// Check if there is an event page that allows registration on the site.
			// If there is not, hide the registration field.
			$ps = new entity_selector($this->get_value( 'site_id' ));
			$ps->add_type( id_of('minisite_page') );
			$relation_parts = array();
			foreach($this->registration_page_types as $page_type)
			{
				$relation_parts[] = 'page_node.custom_page = "'.$page_type.'"';
			}
			$ps->add_relation('( '.implode(' OR ',$relation_parts).' )');
			$ps->set_num(1);
			$page_array = $ps->run_one();
			if(empty($page_array))
			{
				$this->change_element_type( 'registration', 'protected' );
			}
			
			// general default values
			if( !$this->get_value( 'sponsor' ) )
			{
				if($site->get_value('department'))
				{
					$this->set_value( 'sponsor', $site->get_value('department') );
				}
				else
				{
					$this->set_value( 'sponsor', $site->get_value('name') );
				}
			}
			// If there is no username & this is a perfectly new entity (e.g. not edited once), pre-fill the username
			if( !$this->get_value('contact_username') )
			{
					$e = new entity($this->get_value('id'));
					if(1 == $e->get_value('new') && $e->get_value('last_modified') == $e->get_value('creation_date'))
					{
						$user = new entity( $this->admin_page->user_id );
						$this->set_value( 'contact_username', $user->get_value('name') );
					}
			}
			if( !$this->get_value('recurrence') )
				$this->set_value( 'recurrence', 'none' );
			if( !$this->get_value('term_only') )
				$this->set_value('term_only', 'no');
			if( !$this->get_value('show_hide') )
				$this->set_value('show_hide', 'show');
			if( !$this->get_value('registration') )
				$this->set_value('registration', 'none');
				
			$this->add_element('this_event_is','protected');
			$this->add_element('this_event_is_comment','protected');
			
			$this->setup_location_fields();
			
			//pray($this);
			$this->set_event_field_order();
			
			// limit characters for title and description 
			$limiter = new DiscoInputLimiter($this);
			$limiter->limit_field('name', 70);
			$limiter->limit_field('description', 140);
			
		} // }}}

		/**
		 * We really only want to do this if geolocation is "on".
		 */
		function setup_location_fields()
		{
			if ($this->geolocation_enabled())
			{
				$this->add_element('location_head', 'comment', array('text'=>'<h4>Where is this event?</h4>'));
				$this->set_display_name('location', 'Location Name');
				$this->add_element('auto_update_coordinates', 'checkboxfirst');
			
				// the value of auto_update_coordinates should depend on whether or not they are currently in sync.
				// if they are in sync - then leave it checked.
				// if they are not in sync - uncheck it.
				$auto_update_value = ($this->entity_uses_custom_coordinates()) ? "0" : "1";
				
				$this->set_value('auto_update_coordinates', $auto_update_value);
				$this->set_comments('auto_update_coordinates', form_comment('If checked, the latitude and longitude will be automatically updated on save according to the address of the event.'));
				
				// lets set comments on the address field
				if ($auto_update_value)
				{
					$id = $this->admin_page->id;
					$e = new entity($id);
					$addy = $e->get_value('address');
					if ($addy) $this->set_comments('address', form_comment('This address matches the map coordinates that are currently set.'));
					else $this->set_comments('address', form_comment('Please enter a complete address so that we can accurately determine the coordinates for this event.'));
				}
				else $this->set_comments('address', form_comment('This address does not match the coordinates that are saved for this event.'));
			}
			else // lets remove them if they exist
			{
				if ($this->is_element('latitude')) $this->remove_element('latitude');
				if ($this->is_element('longitude')) $this->remove_element('longitude');
				if ($this->is_element('address')) $this->remove_element('address');
			}
		}
		
		/**
		 * Check whether the saved entity values are using custom coordinates - this is used to initally set auto update.
		 */
		function entity_uses_custom_coordinates()
		{
			if ($this->geolocation_enabled())
			{
				$id = $this->admin_page->id;
				$e = new entity($id);
				$addy = $e->get_value('address');
				$lat = $e->get_value('latitude');
				$lng = $e->get_value('longitude');
				if ($lat && $lng && $addy) // geocode to find out
				{
					$geocoder = new geocoder($addy);
					$geocode = $geocoder->get_geocode();
					if ($geocode['latitude'] != $lat || $geocode['longitude'] != $lng) return true;	
				}
				elseif ($lat && $lng && !$addy) return true;
				else return false;
			}
			return false;
		}
		
		/**
		 * We perform geocoding as needed here - this ensures the map and lat / lon get updated even if there are other errors on the form.
		 */
		function pre_error_check_actions()
		{
			if ($this->geolocation_enabled())
			{
				$auto_update = $this->get_value('auto_update_coordinates');
				if ($auto_update)
				{
					$this->do_geolocation();
				}
			}
		}
		
		function set_event_field_order()
		{
			$this->set_order (array ('this_event_is_comment','this_event_is', 'date_and_time', 'datetime', 'hours', 'minutes', 'recurrence', 'frequency', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'monthly_repeat', 'week_of_month', 'month_day_of_week', 'end_date', 'term_only', 'dates', 'show_hide', 'hr1', 'info_head', 'name', 'description', 'location_head', 'location', 'address', 'auto_update_coordinates', 'latitude', 'longitude', 'other_info_head', 'sponsor', 'contact_username', 'contact_organization', 'url', 'content', 'keywords', 'categories', 'hr2', 'audiences_heading','audiences','no_share', 'hr3', 'registration',  ));
		}
		
		function _should_offer_split()
		{
			if($this->get_value('recurrence') == 'none')
				return false;
			if(!reason_user_has_privs($this->admin_page->user_id, 'add' ) || !reason_user_has_privs($this->admin_page->user_id, 'edit' ))
				return false;
			return true;
		}
		
		function run_error_checks() // {{{
		{
			parent::run_error_checks();
			
			if(!$this->_has_errors())
			{
				$rev = new reasonEvent();
				$rev->pass_disco_form_reference($this);
				$rev->clean_up();
				$rev->find_errors();
				
				/*
				
				// Similarity checking is still experimental.
				// This code snippet is functional, but we don't really want to put it into production
				// until the similarity checking is faster and more robust.
				
				$similar = $rev->find_similar_events();
				if(!empty($similar))
				{
					$num = count($similar);
					
					$options = array();
					
					if($num > 1)
					{
						$error_text = 'There are '.$num.' events already in Reason that appear similar to this one';
						entity_sort($similar,'event_similarity');
						foreach($similar as $other_event)
						{
							$options[$other_event->id()] = $other_event->get_value('name');
						}
						$options[$this->get_value('id')] = 'None of the above';
						$display_name = 'This event is�';
					}
					else
					{
						reset($similar);
						$other_event = current($similar);
						$error_text = 'There is an event in Reason that appears to be similar to this one.';
						$options[$other_event->id()] ='Yes';
						$options[$this->get_value('id')] = 'No';
						$txt = 'Is this the same event as "'.$other_event->get_value('name').'"';
						$txt .= ' on '.prettify_mysql_datetime($this->get_value('datetime'),'F j, Y');
						$txt .= ' at '.prettify_mysql_datetime($other_event->get_value('datetime'),'g:i a');
						if($other_event->get_value('location'))
							$txt .= ' ('.$other_event->get_value('location').')';
						$txt .= '?';
						$this->add_element('this_event_is_comment','comment',array('text'=>$txt));
						$display_name = '&nbsp;';
					}
					$this->add_element( 'this_event_is', 'radio_no_sort', array('options'=>$options) );
					$this->add_required('this_event_is');
					$this->set_display_name('this_event_is',$display_name);
					$this->set_error('this_event_is',$error_text);
					$this->set_event_field_order();
					
				}
				*/
			}
		} // }}}
		function do_event_processing()
		{
			$rev = new reasonEvent();
			$dates = $rev->find_occurrence_dates($this->get_values());
			$this->set_value( 'dates', implode( ', ',$dates ) );
			$this->set_value( 'last_occurence', end($dates) );
		}
		function process() // {{{
		{
			$this->do_event_processing();
			parent::process();
		} // }}}
		
		/**
		 * If auto update is on and the address has changed, geolocate the IP
		 */
		function do_geolocation()
		{
			if ($this->geolocation_enabled())
			{
				$eid = $this->admin_page->id;
				$e = new entity($eid);
				$o_address = $e->get_value('address');
				$n_address = $this->get_value('address');
				//$auto_update = $this->get_value('auto_update_coordinates');
				$lat = $this->get_value('latitude');
				$lng = $this->get_value('longitude');
				
				// if update is on lets always do the geolocation.
				$this->set_value('latitude', "");
				$this->set_value('longitude', "");
				if (!empty($n_address))
				{
					$geocoder = new geocoder($n_address);
					$geocode = $geocoder->get_geocode();
					if ($geocode)
					{
						$this->set_value('latitude', $geocode['latitude']);
						$this->set_value('longitude', $geocode['longitude']);
					}
				}
			}
		}
	}	
?>
