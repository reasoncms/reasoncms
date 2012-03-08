<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
	/**
	 * Include the parent module & Reason db update library
	 */
	reason_include_once( 'minisite_templates/modules/events_verbose.php' );
	reason_include_once( 'function_libraries/admin_actions.php');
	/**
	 * Register the module with Reason
	 */
	$GLOBALS[ '_module_class_names' ][ 'event_slot_registration' ] = 'EventSlotRegistrationModule';
/**
 * A minisite module that allows users to register for events via registration slots
 */
class EventSlotRegistrationModule extends VerboseEventsModule
{
	/**
	 * The delimiter used to separate the information of different registrants in the registrant_data field
	 * @var string
	 */
	var $delimiter1 = ';';
	/**
	 * The delimiter used to separate the different kinds of information of an individual registrant in the registrant_data field
	 * @var string
	 */
	var $delimiter2 = '|';
	var $admin_messages;

	var $extra_params = array('form_include' => 'minisite_templates/modules/event_slot_registration/event_slot_registration_form.php' );

	function init( $args = array() )
	{
 		parent::init($args);
  		if ($head_items = $this->get_head_items())
		{
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/events/event_slot.css');
		}
  		$this->verify_slot_if_provided();
		reason_include_once($this->params['form_include']);
	}

	/**
	 * We do some verification of inputs throughout - but lets make sure a legitimate slot is being requested
	 */
	function verify_slot_if_provided()
	{
		if (!empty($this->request['slot_id']))
		{
			$slot = new entity($this->request['slot_id']);
			$ok = reason_is_entity($slot, 'registration_slot_type');
			if (!$ok)
			{
				$redir = carl_make_redirect(array('slot_id' => ''));
				header("Location: " . $redir );
				exit;
			}
		}
		return true;
	}
	
	function handle_params($params)
	{
		$this->acceptable_params += $this->extra_params;
		parent::handle_params($params);
	}
	
	function get_cleanup_rules()
	{
		$cleanup_rules = parent::get_cleanup_rules();
		$cleanup_rules['slot_id'] = array('function' => 'turn_into_int');
		$cleanup_rules['admin_view'] = array('function' => 'check_against_array', 'extra_args' => array('true'));
		$cleanup_rules['delete_registrant'] = array('function' => 'turn_into_string');
		return $cleanup_rules;
	}

	function show_event() // {{{
	{
		if (($this->event->get_values() && $this->event->get_value('type') == id_of('event_type')) && ($this->event->get_value('show_hide') == 'show'))
		{
			$this->show_event_details();
			$this->registration_logic();			
		}
		else
			$this->show_event_error();
	} // }}}
	
	function registration_logic()
	{
		echo '<div id="slotInfo">'."\n";
		if(!($this->event->get_value('last_occurence') < date('Y-m-d')))
		{
			if(!empty($this->request['delete_registrant']) && $this->user_is_admin() )
			{
				$this->delete_registrant();
			}
		
			if(!empty($this->request['admin_view']) && $this->validate_date() && $this->user_is_admin())
			{
				$this->show_admin_view();
			}
			elseif(!$this->validate_date())
			{
				$this->show_registration_dates();
			}
			elseif(empty($this->request['slot_id']))
			{
				$this->show_registration_slots();
			}
			else
			{
				$this->show_registration_form();
			}
		}
		echo '</div>'."\n";
	}
	
	/**
	 * Registrations need to be accompanied by a valid date
	 */
	function validate_date()
	{
		$date = (isset($this->request['date'])) ? $this->request['date'] : '';
		if (empty($date)) return false;
		else
		{
			$possible_dates_str = $this->event->get_value('dates');
			$possible_dates = explode(", ", $possible_dates_str);
			if (in_array($date, $possible_dates))
			{
				return true;
			}
			else
			{
				header("Location: " . carl_make_redirect(array('date' => '')));
				exit;
			}
		}
		// if the value for request['date'] is missing or invalid
	}
	
	function gen_cancel_link()
	{
	
		$link = $this->events_page_url;
		$link .= $this->construct_link(array('event_id'=>$this->request['event_id'],'date'=>$this->request['date'],'view'=>(isset($this->request['view']) ? $this->request['view'] : '') ));
		return $link;
	}

	function show_registration_slots()
	{
		//find registration slots
		$es = new entity_selector($this->site_id);
		$es->description = "Getting the registration slots for this event";
		$es->add_type( id_of( 'registration_slot_type' ) );
		$es->add_right_relationship($this->event->id(), relationship_id_of('event_type_to_registration_slot_type'));
		$es->set_order( 'sortable.sort_order ASC' );
		$num_slots = $es->get_one_count();
		
		//display registration slots
		if($num_slots > 0 && $this->event->get_value('registration') != 'full')
		{
			$results = $es->run_one();
			echo '<h3>Registration</h3>'."\n";
			echo '<ul>'."\n";
			foreach($results as $slot)
			{
				echo '<li>'."\n";
				echo '<h4>'.$slot->get_value('name').'</h4>'."\n";
				echo '<ul>'."\n";
				$description = $slot->get_value('slot_description');
				if(!empty($description))
					echo '<li>'.$description.'</li>'."\n";
				$spaces_available = $this->get_spaces_available(get_entity_by_id($slot->id()));
				if($spaces_available < 0)
					$spaces_available = 0;
				echo '<li>Spaces Available: '.$spaces_available.'</li>'."\n";
				if($spaces_available > 0)
				{
					$link_vars = array('event_id'=>$this->request['event_id'], 'date'=>$this->request['date'], 'slot_id'=>$slot->id());
					echo '<li><a href="'.$this->construct_link($link_vars).'" title = "Register for '.htmlspecialchars($slot->get_value('name'), ENT_QUOTES).'">Register Now</a></li>'."\n";;
				}
				//if user is admin of slot, display admin link
				if($this->user_is_admin(false))
				{
					$link_vars = array('event_id'=>$this->request['event_id'], 'date'=>$this->request['date'], 'slot_id'=>$slot->id(), 'admin_view'=>'true');
					echo '<li><a href="'.$this->construct_link($link_vars).'" title = "Administer '.htmlspecialchars($slot->get_value('name'), ENT_QUOTES).'">Administer '.$slot->get_value('name').'</a></li>'."\n";;
				}
				echo '</ul>'."\n";
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
		}
	}
	
	/**
	 * We require a specific date to be passed in order to register for an event
	 * 
	 * If only one date is available, redirect to that date ... otherwise show a screen that allows a date selection
	 */
	function show_registration_dates()
	{
		$possible_dates =& $this->get_possible_registration_dates();
		if (count($possible_dates) == 1) // redirect to the date
		{
			$date = $possible_dates[0];
			$link = carl_make_redirect(array('date' => $date));
			header("Location: " . $link);
			exit;
		}
		else
		{
			echo '<h3>To register, please choose a date</h3>';
			echo '<ul>';
			foreach ($possible_dates as $the_date)
			{
				$link = carl_make_link(array('date' => $the_date));
				echo '<li>';
				echo '<a href="'. $link . '">'.prettify_mysql_datetime($the_date).'</a>';
				echo '</li>';
			}
			echo '</ul>';
		}
	}
	
	function &get_possible_registration_dates()
	{
		$possible_dates_str = $this->event->get_value('dates');
		$possible_dates = explode(", ", $possible_dates_str);
		$cur_date = get_mysql_datetime();
		$time_frag = substr($this->event->get_value('datetime'), 10);
		foreach ($possible_dates as $k=>$v)
		{
			$working_date = $v . $time_frag;
			if ($cur_date > $working_date)
			{
				unset($possible_dates[$k]);
			}
		}
		return $possible_dates;
	}
	
	function show_registration_form()
	{
		$slot_entity = get_entity_by_id($this->request['slot_id']);
		echo '<div class="form">'."\n";
		echo '<h3>Register for '.$this->event->get_value('name').' ('.$slot_entity['name'].')'.'</h3>'."\n";
		
		$class_name = (isset($GLOBALS[ '_slot_registration_view_class_names' ][ basename( $this->params['form_include'], '.php') ]))
					? $GLOBALS[ '_slot_registration_view_class_names' ][ basename( $this->params['form_include'], '.php') ]
					: 'EventSlotRegistrationForm';
		
		$form = new $class_name($this->event, $this->request, $this->delimiter1, $this->delimiter2, $this->gen_cancel_link());
		$possible_date =& $this->get_possible_registration_dates();
		if (count($possible_date) > 1)
		{
			$form->show_date_change_link();
		}
		$form->run();
		echo '</div>'."\n";
	}

	function show_admin_view()
	{
		$slot = get_entity_by_id($this->request['slot_id']);
		echo '<h3>Administrative Info for '.$slot['name'].'</h3>'."\n";
		echo '<div class="admin">'."\n";
		echo '<ul>'."\n";
		echo '<li><strong>Description: </strong>'.$slot['slot_description'].'</li>'."\n";
		echo '<li><strong>Spaces Available: </strong>'.$this->get_spaces_available($slot).'</li>'."\n";
		echo '<li><strong>Capacity: </strong>'.$slot['registration_slot_capacity'].'</li>'."\n";
		echo '</ul>'."\n";
		$all_registrants = explode($this->delimiter1, $slot['registrant_data']);
		$registrants = $this->get_registrants_for_this_date($all_registrants);
		if (count($registrants) > 0)
		{
			echo '<div id="registrant_data">'."\n";
			echo '<table>'."\n";
			echo '<summary> <h4>Current Registrants: </h4></summary>'."\n";
			echo '<tr>'."\n".'<th id="name" scope="col">Name</th>'."\n".
				 '<th id="email" scope="col">Email Address</th>'."\n".
				 '<th id="date_registered" scope="col">Date Registered</th>'."\n".
				 '<th id="delete_registrant" scope="col">Action</th>'."\n".
				 '</tr>'."\n";
			ksort($registrants);
			$thisrow = 'odd';
			foreach($registrants as $registrant)
			{
				$registrant_pieces = explode($this->delimiter2, $registrant);
				echo '<tr class='.$thisrow.'>'."\n";	
				echo '<td>'.htmlspecialchars($registrant_pieces[1], ENT_QUOTES).'</td>'."\n";
				echo '<td>'.htmlspecialchars($registrant_pieces[2], ENT_QUOTES).'</td>'."\n";
				echo '<td>'.date('m/d/Y', $registrant_pieces[3]).'</td>'."\n";
				$link_vars = array('event_id'=>$this->request['event_id'], 'date'=>$this->request['date'], 'slot_id'=>$slot['id'], 'admin_view'=>'true', 'delete_registrant'=>md5($registrant));
				echo '<td><a href="'.$this->construct_link($link_vars).'" title = "Delete '.htmlspecialchars($registrant_pieces[1], ENT_QUOTES).'">Delete this registrant</a></td>'."\n";
				echo '</tr>'."\n";
				$thisrow = ($thisrow == 'odd') ? 'even' : 'odd';
			}
			echo '</table>'."\n";
			echo '</div>';
			echo $this->admin_messages;
		}
		else echo '<p>There are currently no registrations for this event.</p>';
		$link = carl_make_link(array('admin_view' => ''));
		echo '<p><a href="'.$link.'">Leave administrative view</a></p>';
		echo '</div>'."\n";
	}

	function get_spaces_available($slot_values, $date = '')
	{
		$event_entity = get_entity_by_id($this->request['event_id']);
		$capacity = $slot_values['registration_slot_capacity'];
		$registrant_str = $slot_values['registrant_data'];
		
		if($event_entity['recurrence'] != 'none')
		{
			//if the last occurence of this event hasn't already happened, figure out which registrants registered for the next date.
			if($event_entity['last_occurence'] >= date('Y-m-d'))
			{
				if(empty($registrant_str))
				{
					return $capacity;
				}
				$all_registrants = explode($this->delimiter1, $registrant_str);
				$registrants = $this->get_registrants_for_this_date($all_registrants, $date);

			}
			//if the last occurence of this event has already happened, there aren't any spaces available.
			else
				return 0;
		}
		else
		{
			if(empty($registrant_str))
			{
				return $capacity;
			}
			$registrants = explode($this->delimiter1, $registrant_str);
		}
		return ($capacity - count($registrants));
	}
	
	function get_registrants_for_this_date($all_registrants, $date = '')
	{
		$date = (!empty($date)) ? $date : $this->request['date'];
		$registrants = array();
		foreach($all_registrants as $registrant)
		{
			$registrant_pieces = explode($this->delimiter2, $registrant);
			$event_date = $registrant_pieces[0];
			if($event_date == $date)					
			{
				//use date/time signed up and name as the key for the $registrants array
				$registrants[$registrant_pieces[3]] = $registrant;
			}
		}
		return $registrants; 				
	}
	
	function user_is_admin($force_login = true)
	{
		if ($force_login)
		{
			$netid = reason_require_authentication();
			return reason_username_has_access_to_site($netid, $this->site_id);
		}
		else return reason_check_access_to_site($this->site_id);
	}
 	
	function delete_registrant()
	{	
		$slot = get_entity_by_id($this->request['slot_id']);
		$registrants = explode($this->delimiter1, $slot['registrant_data']);
		$changed = false;
		foreach($registrants as $key=>$registrant)
		{
			if(md5($registrant) == $this->request['delete_registrant'])
			{
				$old_data[] = $registrants[$key];
				unset($registrants[$key]);
				$changed = true;
			}
		}
		
		if($changed)
		{
			$values = array ( 'registrant_data' => implode($this->delimiter1, $registrants));
			
			$update_user = $this->user_is_admin();
			if(empty($update_user))
				$update_user = get_user_id('event_agent');
			$successful_update = reason_update_entity( $this->request['slot_id'], $update_user, $values );
			
			if($successful_update)
			{
				// redirect on successful delete
				$link = carl_make_redirect(array('delete_registrant' => ''));
				header("Location: " . $link );
				exit;
			}
			else
			{
				$this->admin_messages .=  '<h4>Sorry</h4><p>Deletion unsuccesful. The Web Services group has been notified of this error - please try again later.</p>';
				$this->send_delete_error_message( print_r($old_data, true) );
			}
		}
		else
			$this->admin_messages .=  '<h4>Sorry</h4><p>Could not find registrant to delete - most likely they were already deleted.</p>';

	}
	
	function send_delete_error_message($registrant_data)
	{
		$to = 'nwhite@acs.carleton.edu';
		$subject = 'Slot registration deletion error';
		$body = "There was an error deleting a registrant for ".$this->event->get_value('name').'.'."\n\n";
		$body .= "The following person was not successfully deleted\n\n";
		$body .= $registrant_data . "\n";
		mail($to,$subject,$body,"From: event_agent@carleton.edu");		
	}

}	
	
?>
