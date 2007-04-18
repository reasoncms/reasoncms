<?php 
	reason_include_once( 'minisite_templates/modules/events_verbose.php' );
	reason_include_once( 'function_libraries/admin_actions.php');
	
	$GLOBALS[ '_module_class_names' ][ 'event_slot_registration' ] = 'EventSlotRegistrationModule';

class EventSlotRegistrationModule extends VerboseEventsModule
{
	var $delimiter1 = ';';			//the delimiter used to separate the information of different registrants in the registrant_data field
	var $delimiter2 = '|';			//the delimiter used to separate the different kinds of information of an individual registrant in the registrant_data field
	var $user_netID;
	var $session;
	var $admin_messages;

	var $extra_params = array('form_include' => 'minisite_templates/modules/event_slot_registration/event_slot_registration_form.php' );

	function init( $args )
	{
 		parent::init($args);
  		$this->parent->add_stylesheet(REASON_HTTP_BASE_PATH.'css/events/event_slot.css');
		reason_include_once($this->params['form_include']);
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
		$cleanup_rules['admin_id'] = array('function' => 'turn_into_int');
		$cleanup_rules['delete_registrant'] = array('function' => 'turn_into_int');
		return $cleanup_rules;
	}

	function show_event() // {{{
	{
		if (($this->event->get_values() && $this->event->get_value('type') == id_of('event_type')) && ($this->event->get_value('show_hide') == 'show'))
		{
			$this->show_event_details();
			$this->registration_logic();
			echo $this->get_login_logout_link();				
		}
		else
			$this->show_event_error();
	} // }}}
	
	function registration_logic()
	{
		echo '<div id="slotInfo">'."\n";
		if(!($this->event->get_value('last_occurence') < date('Y-m-d')))
		{
			if(!empty($this->request['delete_registrant']))
			{
				$this->delete_registrant();
			}
		
			if(!empty($this->request['admin_id']) && $this->request['admin_id'] == $this->user_is_admin() )
			{
				$this->show_admin_view();
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
	
	function gen_cancel_link()
	{
	
		$link = $this->events_page_url;
		$link .= $this->construct_link(array('event_id'=>$this->request['event_id'],'date'=>$this->request['date'],'view'=>$this->request['view'] ));
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
					echo '<li><a href="'.$this->construct_link($link_vars).'" title = "Register for '.$slot->get_value('name').'">Register Now</a></li>'."\n";;
				}
				//if user is admin of slot, display admin link
				$admin_id = $this->user_is_admin();
				if($admin_id)
				{
					$link_vars = array('event_id'=>$this->request['event_id'], 'date'=>$this->request['date'], 'slot_id'=>$slot->id(), 'admin_id'=>$admin_id);
					echo '<li><a href="'.$this->construct_link($link_vars).'" title = "Administer '.$slot->get_value('name').'">Administer '.$slot->get_value('name').'</a></li>'."\n";;
				}
				echo '</ul>'."\n";
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
		}
	}
	
	function show_registration_form()
	{
		$slot_entity = get_entity_by_id($this->request['slot_id']);
		echo '<div class="form">'."\n";
		echo '<h3>Register for '.$this->event->get_value('name').' ('.$slot_entity['name'].')'.'</h3>'."\n";
		$form = new EventSlotRegistrationForm($this->event, $this->request, $this->delimiter1, $this->delimiter2, $this->gen_cancel_link());
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
				echo '<td>'.$registrant_pieces[1].'</td>'."\n";
				echo '<td>'.$registrant_pieces[2].'</td>'."\n";
				echo '<td>'.date('m/d/Y', $registrant_pieces[3]).'</td>'."\n";
				$link_vars = array('event_id'=>$this->request['event_id'], 'date'=>$this->request['date'], 'slot_id'=>$slot['id'], 'admin_id'=>$this->request['admin_id'], 'delete_registrant'=>$registrant_pieces[3]);
				echo '<td><a href="'.$this->construct_link($link_vars).'" title = "Delete '.$registrant_pieces[1].'">Delete this registrant</a></td>'."\n";
				echo '</tr>'."\n";
				$thisrow = ($thisrow == 'odd') ? 'even' : 'odd';
			}
			echo '</table>'."\n";
			echo '</div>';
			echo $this->admin_messages;
		}
		else echo '<p>There are currently no registrations for this event</p>';
		echo '</div>'."\n";
	}

	function get_spaces_available($slot_values)
	{
		$event_entity = get_entity_by_id($this->request['event_id']);
		$capacity = $slot_values['registration_slot_capacity'];
		$registrant_str = $slot_values['registrant_data'];
		
		if($event_entity['repeat'] != 'none')
		{
			//if the last occurence of this event hasn't already happened, figure out which registrants registered for the next date.
			if($event_entity['last_occurence'] > date('Y-m-d'))
			{
				if(empty($registrant_str))
				{
					return $capacity;
				}
				$all_registrants = explode($this->delimiter1, $registrant_str);
				$registrants = $this->get_registrants_for_this_date($all_registrants);

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
	
	function get_registrants_for_this_date($all_registrants)
	{
		$registrants = array();
		foreach($all_registrants as $registrant)
		{
			$registrant_pieces = explode($this->delimiter2, $registrant);
			$event_date = $registrant_pieces[0];
			if($event_date == $this->request['date'])					
			{
				//use date/time signed up and name as the key for the $registrants array
				$registrants[$registrant_pieces[3]] = $registrant;
			}
		}
		return $registrants; 				
	}	
	
	function user_is_admin()
	{
		if($this->get_authentication())
		{
			$es = new entity_selector();
			$es->add_type( id_of( 'user' ) );
			$es->add_right_relationship( $this->site_id , relationship_id_of( 'site_to_user' ) );
			$admins = $es->run_one();
			foreach($admins as $user_entity)
			{
				if($user_entity->get_value('name') == $this->user_netID)
				{
					return $user_entity->id();
				}
			}
			return false;
		}
		else
			return false;
	}
 	
	function delete_registrant()
	{	
		$slot = get_entity_by_id($this->request['slot_id']);
		$all_registrants = explode($this->delimiter1, $slot['registrant_data']);
		$registrants = $this->get_registrants_for_this_date($all_registrants);
		
		if(!empty($registrants[$this->request['delete_registrant']]))
		{
			$old_data = $registrants[$this->request['delete_registrant']];
			unset($registrants[$this->request['delete_registrant']]);
			$flat_values = array (
				'registrant_data' => implode($this->delimiter1, $registrants), 
			);
					
			$tables = get_entity_tables_by_type(id_of('registration_slot_type'));	
	
			$successful_update = update_entity( 
				$this->request['slot_id'],
				get_user_id('event_agent'),
				values_to_tables($tables, $flat_values, $ignore = array() ) 
			);
			
			if($successful_update)
			{
				//$this->admin_messages .= '<h4>Success</h4><p>Successfully deleted registrant with the following information: '.$old_data.'</p>';
				$this->admin_messages .= '<h4>Success</h4><p>Successfully deleted registrant.</p>';
			}
			else
				{
					$this->admin_messages .=  '<h4>Sorry</h4><p>Deletion unsuccesful. The Web Services group has been notified of this error - please try again later.</p>';
					$this->send_delete_error_message($old_data);
				}
		}
		else
			$this->admin_messages .=  '<h4>Sorry</h4><p>Could not find registrant to delete - most likely they were already deleted.</p>';

	}


	
	/**	
	* Returns the current user's netID, or false if the user is not logged in.
	* @return string user's netID
	*/	
	function get_authentication()
	{
		if(empty($this->user_netID))
		{
			if(!empty($_SERVER['REMOTE_USER']))
			{
				$this->user_netID = $_SERVER['REMOTE_USER'];
				return $this->user_netID;
			}
			else
			{
				return $this->get_authentication_from_session();
			}
		}
		else
		{
			return $this->user_netID;
		}
	}
	function get_authentication_from_session()
	{
		$this->session =& get_reason_session();
		if($this->session->exists())
		{
			force_secure_if_available();
			if( !$this->session->has_started() )
			{
				$this->session->start();
			}
			$this->user_netID = $this->session->get( 'username' );
			return $this->user_netID;
		}
		else
		{
			return false;
		}
	}
	
	function get_login_logout_link()
	{
		$sess_auth = $this->get_authentication_from_session();
		$auth = $this->get_authentication();
		$ret = '<div class="loginlogout">';
		if(!empty($sess_auth))
		{
			$ret .= 'Logged in: '.$sess_auth.' <a href="'.REASON_LOGIN_URL.'?logout=true">Log Out</a>';
		}
		elseif(!empty($auth))
		{
			$ret .= 'Logged in as '.$auth;
		}
		else
		{
			$ret .= '<a href="'.REASON_LOGIN_URL.'">Log In</a>';
		}
		$ret .= '</div>'."\n";
		return $ret;
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
