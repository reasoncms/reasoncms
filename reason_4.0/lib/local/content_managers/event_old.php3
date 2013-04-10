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
	reason_include_once('classes/event.php');
	
	/**
	 * A content manager for event entities
	 *
	 * Provides a custom interface for adding and editing events in Reason
	 */
	class event_handler2 extends ContentManager 
	{
	
		var $years_out = 3;
		var $sync_vals = array();
		var $registration_page_types = array('event_registration','event_signup',);
		
		var $statesAP = array(
                       'AL' => 'Ala.',
                       'AK' => 'Alaska',
                       'AZ' => 'Ariz.',
                       'AR' => 'Ark.',
                       'CA' => 'Calif.',
                       'CO' => 'Colo.',
                       'CT' => 'Conn.',
                       'DE' => 'Del.',
                       'DC' => 'D.C.',
                       'FL' => 'Fla.',
                       'GA' => 'Ga.',
                       'HI' => 'Hawaii',
                       'ID' => 'Idaho',
                       'IL' => 'Ill.',
                       'IN' => 'Ind.',
                       'IA' => 'Iowa',
                       'KS' => 'Kan.',
                       'KY' => 'Ky.',
                       'LA' => 'La.',
                       'ME' => 'Maine',
                       'MD' => 'Md.',
                       'MA' => 'Mass.',
                       'MI' => 'Mich.',
                       'MN' => 'Minn.',
                       'MS' => 'Miss.',
                       'MO' => 'Mo.',
                       'MT' => 'Mont.',
                       'NE' => 'Neb.',
                       'NV' => 'Nev.',
                       'NH' => 'N.H.',
                       'NJ' => 'N.J.',
                       'NM' => ' N.M.',
                       'NY' => 'N.Y.',
                       'NC' => 'N.C.',
                       'ND' => ' N.D.',
                       'OH' => 'Ohio',
                       'OK' => ' Okla.',
                       'OR' => 'Ore.',
                       'PA' => 'Pa.',
                       'RI' => 'R.I.',
                       'SC' => 'S.C.',
                       'SD' => 'S.D.',
                       'TN' => 'Tenn.',
                       'TX' => 'Texas',
                       'UT' => 'Utah',
                       'VT' => 'Vt.',
                       'VA' => 'Va.',
                       'WA' => 'Wash.',
                       'WV' => 'W.Va.',
                       'WI' => 'Wis.',
                       'WY' => 'Wyo.',
		);
		
		function init_head_items()
		{
			$this->head_items->add_javascript(JQUERY_URL, true); // uses jquery - jquery should be at top
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH .'event.js');
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
			if ($this->is_element('geopoint'))
			{
				$this->remove_element('geopoint'); // never want to set this directly.
			}
			$this->check_for_recurrence_field_existence();
			//test_reason_repeating_events($this->get_value('id'));
			$site = new entity( $this->get_value( 'site_id' ) );
			$is_sport = preg_match("/^sport.*?/", $site->get_value('unique_name'));

			// create all additional elements
			$this->add_element('hr1', 'hr');
			$this->add_element('hr2', 'hr');
			$this->add_element('hr3', 'hr');
			$this->add_element('hr4', 'hr');
			
			if ($is_sport)
			{
				$this->change_element_type( 'contact_organization', 'hidden' );
				$this->add_element( 'post_to_results', 'checkbox', array('description' => 'Check when event has concluded.'));
				$this->set_display_name( 'post_to_results', 'Post to results' );
				$this->set_value('post_to_results', preg_match("/post_to_results/", $this->get_value('contact_organization')));
			}
			
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
			$this->add_element('info_head', 'comment', array('text'=>'<h4>General Information</h4>'));

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
											'add_null_value_to_top' => false,
					) );
			$this->change_element_type( 'minutes', 'select_no_sort', array('options'=>$minutes) );
			$this->change_element_type( 'hours', 'select_no_sort', array('options'=>$hours) );
			$this->change_element_type( 'frequency', 'text', array('size'=>3) );
			$this->change_element_type( 'week_of_month','hidden' );
			$this->change_element_type( 'month_day_of_week','hidden' );
			$this->change_element_type( 'term_only','hidden' );
			$this->change_element_type( 'author', 'hidden');
			$this->change_element_type( 'end_date', 'textDate' );
			$this->change_element_type( 'last_occurence', 'hidden' );
			$this->change_element_type( 'no_share', 'select', array( 'options' => array( 'Shared', 'Private' ), 'add_null_value_to_top' => false, ) );
			$this->change_element_type( 'dates', $this->get_value( 'dates' ) ? 'solidtext' : 'hidden' );

			// format the elements
			if ($is_sport)
			{
				$this->set_display_name( 'name', 'Opponent' );
				$this->set_display_name( 'description', 'Time/Results' ); //get default loki type
				$this->set_comments('description', form_comment( 'Override the time before the event (e.g. "All day" or "Noon and 5 PM"). Enter results after the event. Enter live stats link during the event.' ) );
			}
			else
			{
				$this->set_display_name( 'name', 'Event Title' );
				$this->set_display_name( 'description', 'Brief Description of Event' ); //get default loki type
				$this->set_comments(	 'description', form_comment( 'A brief summary of the event' ) );
			}
			//$this->set_display_name( 'name', 'Event Title' );
			$this->set_display_name( 'sponsor', 'Sponsoring department or organization' );
			$this->set_display_name( 'contact_username', 'Username of Contact Person' );
			$this->set_display_name( 'contact_organization', 'Contact Department or Group' );
			$this->set_display_name( 'datetime', 'Date &amp; time of event' );
			$this->set_comments(	 'datetime', form_comment( 'Month/Day/Year' ) );
			//$this->set_display_name( 'description', 'Brief Description of Event' ); //get default loki type
			//$this->set_comments(	 'description', form_comment( 'A brief summary of the event' ) );
			$this->set_display_name( 'content', 'Full Event Information' );
			$this->set_comments(	 'content', form_comment( 'Here is where you can enter all of the important information about the event.' ) );
			$this->set_display_name( 'url', 'URL for More Info' );
			$this->set_comments(	 'url', form_comment( 'If this event has a site dedicated to it, enter that URL here.' ) );
			$this->set_display_name( 'hours', 'Duration' );
			$this->set_comments(	 'hours', ' Hours');
			$this->set_display_name( 'minutes', ' ' );
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
			$this->set_display_name( 'show_hide', 'Show or Hide?' );
			$this->set_comments(	 'show_hide', form_comment( 'Hidden items will not show up in the events listings.' ));
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
				$this->change_element_type( 'registration', 'hidden' );
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
			if( !$this->get_value('contact_username') )
			{
					$user = new entity( $this->admin_page->user_id );
					$this->set_value( 'contact_username', $user->get_value('name') );
			}
			if( !$this->get_value('recurrence') )
				$this->set_value( 'recurrence', 'none' );
			if( !$this->get_value('term_only') )
				$this->set_value('term_only', 'no');
			if( !$this->get_value('show_hide') )
				$this->set_value('show_hide', 'show');
			if( !$this->get_value('registration') )
				$this->set_value('registration', 'none');
				
			$this->add_element('this_event_is','hidden');
			$this->add_element('this_event_is_comment','hidden');
			
			// strip out stored local clueTip information from content
			if ($is_sport)
			{
				$c = $this->get_value('content');
				$c = preg_replace("/<div id=\"athlete\d+\">(.|\s)*/", "", $c);
				$this->set_value('content', $c);
			}
			//pray($this);
			$this->set_event_field_order();
		} // }}}
		
		function set_event_field_order()
		{
			$this->set_order (array ('this_event_is_comment','this_event_is', 'date_and_time', 'datetime', 'hours', 'minutes', 'recurrence', 'frequency', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'monthly_repeat', 'week_of_month', 'month_day_of_week', 'end_date', 'term_only', 'dates', 'hr1', 'info_head', 'name', 'description', 'post_to_results', 'location', 'sponsor', 'contact_username', 'contact_organization', 'url', 'content', 'keywords', 'categories', 'hr2', 'audiences_heading','audiences',  'show_hide', 'no_share', 'hr3', 'registration',  ));
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
		}
		function do_event_processing()
		{
			$rev = new reasonEvent();
			$dates = $rev->find_occurrence_dates($this->get_values());
			$this->set_value( 'dates', implode( ', ',$dates ) );
			$this->set_value( 'last_occurence', end($dates) );
			
			// add post to results to hidden contact organization field
			$site = new entity( $this->get_value( 'site_id' ) );
			if (preg_match("/^sport.*?/", $site->get_value('unique_name')))
			{
				if ($this->get_value('post_to_results'))
				{				
					$this->set_value('contact_organization', 'post_to_results');
				}
				else 
				{
					$this->set_value('contact_organization', '');
				}
			}
		}
		
		function process()
		{
			$this->do_event_processing();
			$this->sports_athlete_links();
			parent::process();
		}
		
		function sports_athlete_links()
		// finds name of athlete in main content and automatically inserts a link to the athlete's bio
		{			
			$site_id = new entity( $this->get_value( 'site_id' ) );
			$site_name = $site_id->get_value('unique_name');			
			if (!preg_match("/sport_\w+_w?o?men/", $site_name))
			{
				return;
			}			
						
			// get a list of athletes
			$es = new entity_selector($this->get_value('site_id'));
			$es->add_type(id_of('athlete_type'));
			$es->add_relation('athlete_hide != "yes"');
			$es->add_left_relationship_field( 'athlete_to_image', 'entity', 'id', 'image_id', false); // get images and those with no image - uses union query
			$players = $es->run_one();

			
			if (!empty($players))
			{
				$url = $site_id->get_value('base_url') . "roster/";
				$c = $this->get_value('content');
				$ct = "";   // appended cluetip information
				foreach ($players as $k=>$v)
				{
					$pv = $v->get_values();
					//print_r ($pv);
					if (!preg_match("/<a href=.*?>".$pv['athlete_first_name']."\s+".$pv['athlete_last_name']."<\/a>/", $c))	
					{
						
						//$image = get_entity_by_id($pv['image_id']);
						//$url = WEB_PHOTOSTOCK . $pv['image_id'] . '.' . $image['image_type'];
						//$c = preg_replace("|".$pv['athlete_first_name']."\s+".$pv['athlete_last_name']."|",
						//"<a href=".$url.">".$pv['athlete_first_name']." ".$pv['athlete_last_name']."</a>", $c, 1);
						$c = preg_replace("|".$pv['athlete_first_name']."\s+".$pv['athlete_last_name']."|",
						//"<a href=".$url."?id=".$pv['id'].">".$pv['athlete_first_name']." ".$pv['athlete_last_name']."</a>", $c, 1);
						"<a href=\"".$url."?id=".$pv['id']. "\" class=\"cluetip_athlete\" title=\"". $pv['athlete_first_name']." ".$pv['athlete_last_name'] ."\" rel=\"#athlete".$pv['id']."\">".$pv['athlete_first_name']." ".$pv['athlete_last_name']."</a>", $c, 1);
						

					}
					//if (preg_match("/<a href=\"".$url."\?id=".$pv['id']."\"/", $c))
					if (preg_match("/id=".$pv['id']."/", $c))
					{
						$ct .= "<div id=\"athlete".$pv['id']."\">";
						$ct .= "<p class=\"athlete_position_event\">". $pv['athlete_position_event'];
						if (!empty($pv['image_id']))
						{
							$image = get_entity_by_id($pv['image_id']);
							$thumb = WEB_PHOTOSTOCK . $pv['image_id'] . '_tn.' . $image['image_type'];
							$ct .= "<img class=\"athlete_image\" src=\"" . $thumb . "\" />";
						}
						$ct .= "</p>";					
						$ct .= "<p class=\"athlete_class_year\">". $pv['athlete_class_year']."</p>";
						$ct .= "<p class=\"athlete_hometown\">". $pv['athlete_hometown_city'].", ". $this->statesAP[$pv['athlete_hometown_state']]."</p>";
						$ct .= "<p class=\"athlete_high_school\">". $pv['athlete_high_school']."</p>";		
						$ct .= "</div>";
					}
				}
				$this->set_value('content', $c . $ct);
			}
			
		}
	}	
?>
