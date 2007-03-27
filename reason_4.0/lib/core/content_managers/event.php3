<?php
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'event_handler';
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	
	class event_handler extends ContentManager 
	{
	
		var $years_out = 3;
		var $sync_vals = array();
		var $registration_page_types = array('event_registration','event_signup',);
	
		function init() // {{{
		{
			parent::init();
			
		} // }}}
			
		function alter_data() // {{{
		{
			$site = new entity( $this->get_value( 'site_id' ) );

			// create all additional elements
			$this->add_element('hr1', 'hr');
			$this->add_element('hr2', 'hr');
			$this->add_element('hr3', 'hr');
			$this->add_element('hr4', 'hr');
			$this->add_element('audiences_heading', 'comment', array('text'=>'<h4>Visibility</h4> To which groups do you wish to promote this event? (Please enter at least one)'));
			$this->add_relationship_element('audiences', id_of('audience_type'), 
relationship_id_of('event_to_audience'),'right','checkbox',REASON_USES_DISTRIBUTED_AUDIENCE_MODEL,'sortable.sort_order ASC');
			
			$old_audience_fields = array('prospective_students','new_students','students','faculty','staff','alumni','families','public');
			foreach($old_audience_fields as $field)
			{
				if($this->_is_element($field))
					$this->change_element_type($field,'hidden');
			}
			
			$this->add_relationship_element('categories', id_of('category_type'), 
relationship_id_of('event_to_event_category'),'right','checkbox',true,'entity.name ASC');
			$this->add_required('categories');
			
			$this->add_element('date_and_time', 'comment', array('text'=>'<h4>Date, Time, and Duration of Event</h4>'));
			//$this->add_element('repeat_head', 'comment', array('text'=>'<h4>Repeating Events</h4>'));
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

			$this->change_element_type( 'datetime','textDateTime_js',array( 'script_url' => REASON_HTTP_BASE_PATH.'js/ymd_to_dow_wom.js' ) );
			
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			$this->change_element_type( 'repeat', 'select_no_sort_js', 
				array(	'options' => array(	'none'=>'Never (One-Time Event)', 
											'daily'=>'Daily', 
											'weekly'=>'Weekly', 
											'monthly'=>'Monthly', 
											'yearly'=>'Yearly'), 
						'script_url' => REASON_HTTP_BASE_PATH.'js/event.js',
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
			$this->set_display_name( 'repeat', 'Repeat This Event' );
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

			// set requirements
			$this->add_required( 'datetime' );
			$this->add_required( 'repeat' );
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
			if( !$this->get_value('contact_username') && !empty($this->admin_page->request['user_id']) )
			{
					$user = new entity( $this->admin_page->request['user_id'] );
					$user->get_values();
					$this->set_value( 'contact_username', $user->get_value('name') );
			}
			if( !$this->get_value('repeat') )
				$this->set_value( 'repeat', 'none' );
			if( !$this->get_value('term_only') )
				$this->set_value('term_only', 'no');
			if( !$this->get_value('show_hide') )
				$this->set_value('show_hide', 'show');
			if( !$this->get_value('registration') )
				$this->set_value('registration', 'none');
			
			$this->set_order (array ('date_and_time', 'datetime', 'hours', 'minutes', 'repeat', 'frequency', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'monthly_repeat', 'week_of_month', 'month_day_of_week', 'end_date', 'term_only', 'dates', 'hr1', 'info_head', 'name', 'description', 'location', 'sponsor', 'contact_username', 'contact_organization', 'url', 'content', 'keywords', 'categories', 'hr2', 'audiences_heading','audiences',  'show_hide', 'no_share', 'hr3', 'registration',  ));
			//pray($this);
		} // }}}
		
		function run_error_checks() // {{{
		{
			parent::run_error_checks();
			
			if ($this->get_value( 'repeat' ) != 'none')
			{
				if (!$this->get_value('frequency'))
				{
					$repeat_vals = array( 'daily'=>'day', 'weekly'=>'week', 'monthly'=>'month', 'yearly'=>'year');
					$this->set_error( 'frequency', 'How often should this event repeat? If it repeats every '.$repeat_vals[ $this->get_value( 'repeat' ) ].', enter a 1.' );
				}
				if (!$this->get_value( 'term_only' ) )
					$this->set_error( 'term_only', 'Please indicate whether this event should occur only while classes are in session.' );
			}
			
			$d = $this->iffydate( $this->get_value( 'datetime' ) );
			if ($this->get_value( 'repeat' ) == 'weekly')
			{
				// there must be at least 1 day of the week the event recurs on
				$days = array ('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
				foreach ($days as $day)
				{
					if ( $this->get_value( $day ) )
					{
						$day_set = true;
						break;
					}
				}
				if (empty($day_set))
					$this->set_error( 'sunday', 'Please enter which day(s) of the week this event will occur.' );

				// the date of the event must be on one of those days of the week
				$day_o_week = date( 'l', $d['timestamp'] );
				if( !$this->get_value( strtolower( $day_o_week ) ) )
					$this->set_error( 'sunday','This event does not occur on the repeated days of the week' );
			}
			elseif ($this->get_value( 'repeat' ) == 'monthly')
			{
				// don't allow repetition on days that don't occur in every month
				if ($d['day'] > 28) 
					$this->set_error( 'datetime', 'There is not such a date in some months' );
			}
			elseif ( $this->get_value( 'repeat' ) == 'yearly' )
			{
				if( $d['month'] == 2 AND $d['day'] == 29 )
					$this->set_error( 'datetime', 'February 29th only occurs on leap-years' );
			}
		} // }}}
		function do_event_processing()
		{
			$this->dates = array();
			
			$this->frequency = $this->get_value( 'frequency' );

			if( $this->get_value( 'repeat' ) == 'monthly' AND $this->get_value( 'monthly_repeat' ) == 'semantic' )
			{
				$d = $this->iffydate( $this->get_value( 'datetime' ) );

				$this->set_value( 'month_day_of_week',date( 'l',$d['timestamp'] ) );
				$this->set_value( 'week_of_month',floor($d['day']/7)+1 );
			}
			else
			{
				$this->set_value( 'month_day_of_week','' );
				$this->set_value( 'week_of_month','' );
			}
			
			$s = $this->iffydate( $this->get_value( 'datetime' ) ); 
			$this->ystart = $s['year'];
			$this->mstart = $s['month'];
			$this->dstart = $s['day'];
			$this->ustart = $s['timestamp'];

			if( $this->get_value( 'end_date' ) )
				$e = $this->iffydate( $this->get_value( 'end_date' ) );
			else
				$e = $this->iffydate( $this->ystart+$this->years_out.'-'.$this->mstart.'-'.$this->dstart );
			$this->yend = $e['year'];
			$this->mend = $e['month'];
			$this->dend = $e['day'];
			$this->uend = $e['timestamp'];
			
			$days = array();
			
			// chunk out the dates appropriately
			if ( $this->get_value('repeat') == 'daily' )
				$this->get_days_daily();
			elseif ( $this->get_value('repeat') == 'weekly' )
				$this->get_days_weekly();
			elseif ( $this->get_value('repeat') == 'monthly' )
				$this->get_days_monthly();
			elseif ( $this->get_value('repeat') == 'yearly' )
				$this->get_days_yearly();
			else
				$this->get_days_norepeat();
			
			sort($this->dates);
			
			$this->set_value( 'dates', implode( ', ',$this->dates ) );
			
			$this->set_value( 'last_occurence', end($this->dates) );
		}
		function process() // {{{
		{
			$this->do_event_processing();
			parent::process();
		} // }}}
		function get_days_norepeat() // {{{
		{
			$this->dates[] = $this->ystart.'-'.$this->mstart.'-'.$this->dstart;
		} // }}}
		function get_days_daily() // {{{
		{
			for( $ucur = $this->ustart; $ucur <= $this->uend; $ucur = strtotime( '+'.$this->frequency.' days',$ucur ) )
				$this->dates[] = date( 'Y',$ucur ).'-'.date( 'm',$ucur ).'-'.date( 'd',$ucur );
		} // }}}
		function get_days_weekly() // {{{
		{
			
			if( $this->get_value( 'sunday' ) )
				$days[] = 'Sunday';
			if( $this->get_value( 'monday' ) )
				$days[] = 'Monday';
			if( $this->get_value( 'tuesday' ) )
				$days[] = 'Tuesday';
			if( $this->get_value( 'wednesday' ) )
				$days[] = 'Wednesday';
			if( $this->get_value( 'thursday' ) )
				$days[] = 'Thursday';
			if( $this->get_value( 'friday' ) )
				$days[] = 'Friday';
			if( $this->get_value( 'saturday' ) )
				$days[] = 'Saturday';

			// go through each day of the week to repeat on
			foreach( $days AS $day )
			{
				// start on the date of the event
				$ucur = $this->ustart;

				// advance until the first occurence of that day of the week
				while( $day != date( 'l',$ucur ) )
				{
					$ucur = strtotime( '+1 day',$ucur );
				}
				// now jump by the number of weeks to skip at a time until done
				while( $ucur <= $this->uend )
				{
					$this->dates[] = date( 'Y',$ucur ).'-'.date( 'm',$ucur ).'-'.date( 'd',$ucur );
					$ucur = strtotime( '+ '.$this->frequency.' weeks',$ucur );
				}
			}
		} // }}}
		function get_days_monthly() // {{{
		{
			$ucur = $this->ustart;
			
			while( $ucur <= $this->uend )
			{
				$this->dates[] = date( 'Y',$ucur ).'-'.date( 'm',$ucur ).'-'.date( 'd',$ucur );
				//echo '$ucur='.$ucur.'<br />';
				//die();
				$ucur = strtotime( '+'.$this->frequency.' months',$ucur );
				if( $this->get_value( 'monthly_repeat' ) == 'semantic' )
				{
					$cur_day = 1+7*($this->get_value( 'week_of_month' ) - 1);
					$ucur = get_unix_timestamp( date( 'Y',$ucur ).'-'.date( 'm',$ucur ).'-'.$cur_day );
					$foo = 0;
					while( date( 'l',$ucur ) != $this->get_value( 'month_day_of_week' ) )
					{
						$ucur = strtotime( '+1 day',$ucur );
					}
				}
			}
		} // }}}
		function get_days_yearly() // {{{
		{
			for( $ucur = $this->ustart; $ucur < $this->uend; $ucur = strtotime( '+'.$this->frequency.' years',$ucur ) )
				$this->dates[] = date( 'Y',$ucur ).'-'.date( 'm',$ucur ).'-'.date( 'd',$ucur );
		} // }}}
		function iffydate($iffydate) // {{{
		{
			// returns an array of year, month, day, timestamp
			$output = array();
			list( $idate ) = explode( ' ',$iffydate );
			list( $output['year'],$output['month'],$output['day']) = explode( '-',$idate );
			$output['timestamp'] = get_unix_timestamp( $output['year'].'-'.$output['month'].'-'.$output['day'] );
			return $output;
		} // }}}
	}
?>
