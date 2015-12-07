<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once( DISCO_INC . 'disco.php');
	reason_include_once('classes/event.php');
	/**
	 * Split an event into its component occurrences
	 */
	
	class ReasonEventSplitModule extends DefaultModule // {{{
	{
		var $_event;
		var $_should_run = true;
		var $_no_run_msg = '';
		var $_split_events = array();
		var $_event_split_report = '';
		function EventSplitModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * Standard Module init function
		 * 
		 * @return void
		 */
		function init() // {{{
		{
			if(!empty($this->admin_page->id))
			{
				$this->_event = new entity( $this->admin_page->id );
			}
			else
			{
				$this->_should_run = false;
				$this->_no_run_msg = 'No event ID provided.';
				return;
			}
			if(!reason_user_has_privs($this->admin_page->user_id, 'add' ) || !reason_user_has_privs($this->admin_page->user_id, 'edit' ))
			{
				$this->_should_run = false;
				$this->_no_run_msg = 'You do not have the privileges to duplicate an event.';
				return;
			}
			if(empty($this->_event) || !$this->_event->get_values() || $this->_event->get_value('type') != id_of('event_type'))
			{
				$this->_should_run = false;
				$this->_no_run_msg = 'The item you are trying to split up is not an event.';
				return;
			}
			$owner = $this->_event->get_owner();
			if($owner->id() != $this->admin_page->site_id)
			{
				$this->_should_run = false;
				$this->_no_run_msg = 'The event you are trying to split up is not owned by the current site.';
				return;
			}
			$dates = $dates = $this->_get_dates_from_event($this->_event);
			if(count($dates) < 2)
			{
				$this->_should_run = false;
				$this->_no_run_msg = 'The event you are trying to split up only occurs on one date.';
				return;
			}
			$this->admin_page->title = 'Split Up Event: "'.$this->_event->get_value('name').'"';
			
		} // }}}
		
		
		/**
		 * Run the event similarity form
		 * 
		 * @return void
		 */
		function run() // {{{
		{
			if(!$this->_should_run)
			{
				echo '<p>'.$this->_no_run_msg.'</p>'."\n";
				return;
			}
			
			$d = new disco();
			$d->add_element('referer','hidden', array('userland_changeable' => true));
			if($_SERVER['HTTP_REFERER'])
			{
				$d->set_value('referer',$_SERVER['HTTP_REFERER']);
			}
			$d->set_actions( array('split'=>'Split Into Separate Event Items'));
			$d->add_callback(array(&$this,'event_split_callback'), 'process');
			$d->add_callback(array(&$this,'get_event_info_html'),'pre_show_form');
			$d->add_callback(array(&$this,'get_cancel_html'),'post_show_form');
			$d->add_callback(array(&$this,'get_event_split_report'),'no_show_form');
			$d->run();
		}
		function get_cancel_html(&$disco)
		{
			if($_SERVER['HTTP_REFERER'] && $_SERVER['HTTP_REFERER'] != get_current_url())
			{
				$link = htmlspecialchars($_SERVER['HTTP_REFERER'],ENT_QUOTES);
			}
			else
			{
				$link = $this->admin_page->make_link(array( 'cur_module' => 'Editor' ));
			}
			return '<div class="eventSplitCancel"><a href="'.$link.'">Cancel split (Leave things as-is)</a></div>'."\n";
		}
		function get_event_info_html(&$disco)
		{
			$dates = $this->_get_dates_from_event($this->_event);
			$ret = '<p>The event "'.$this->_event->get_value('name').'" occurs on '.count($dates).' dates:</p>'."\n";
			$ret .= '<ul>'."\n";
			foreach($dates as $date)
				$ret .= '<li>'.prettify_mysql_datetime($date).'</li>'."\n";
			$ret .= '</ul>'."\n";
			$ret .= '<p>Would you like to break this event up into '.count($dates).' separate event items, one for each occurrence?</p>'."\n";
			$ret .= '<p>Note that you only need to do this if you want to change the name/description/time for some (but not all) of the occurrences.</p>'."\n";
			return $ret;
		}
		function _get_dates_from_event($event)
		{
			return explode(', ',$event->get_value('dates'));
		}
		function event_split_callback(&$disco)
		{
			if($disco->chosen_action == 'split')
			{
				$ids = reason_split_event($this->_event,$this->admin_page->user_id);
			
				$this->_event_split_report = '<p>Event split into separate items.</p>'."\n";
			
				$disco->show_form = false;
			}
		}
		function get_event_split_report(&$disco)
		{
			$ret = $this->_event_split_report;
			$ret .= '<p><a href="'.$this->admin_page->make_link(array( 'cur_module' => 'Finish' )).'">Return to events listing</a></p>'."\n";
			return $ret;
		}
	} // }}}
	
	/**
	 * Split a repeating event into separate entities
	 *
	 * If event is not repeating, nothing will happen and the returned array will contain just the 
	 * existing event's ID.
	 *
	 * @param object $event entity
	 * @param integer $user_id
	 * @return array updated/created IDs
	 *
	 * @todo determine a way to integrate with Carleton event calendar
	 */
	function reason_split_event($event, $user_id)
	{
		$user_id = (integer) $user_id;
		if(empty($user_id))
		{
			trigger_error('User ID required to split an event');
			return array();
		}
		$ret = array($event->id());
		$dates = explode(', ',$event->get_value('dates'));
		sort($dates);
		if(count($dates) > 1)
		{
			$i = 1;
			foreach($dates as $date)
			{
				$overrides = array(
					'datetime'=>$date.' '.substr($event->get_value('datetime'),11),
					'dates' => $date,
					'recurrence' => 'none',
					'last_occurence' => $date,
					'frequency' => '',
					'week_of_month' => '',
					'month_day_of_week' => '',
					'monthly_repeat' => '',
					
				);
				if($i < 2) // first date -- just modify existing event
				{
					reason_update_entity( $event->id(), $user_id, $overrides);
				}
				else // rest of dates - create new events
				{
					$overrides['unique_name'] = '';
					$overrides['last_modified'] = date('Y-m-d h:i:s');
					$overrides['last_edited_by'] = $user_id;
					$overrides['creation_date'] = date('Y-m-d h:i:s');
					$overrides['created_by'] = $user_id;
					$ret[] = duplicate_entity( $event->id(), true, false, $overrides );
				}
				$i++;
			}
		}
		return $ret;
	}
?>
