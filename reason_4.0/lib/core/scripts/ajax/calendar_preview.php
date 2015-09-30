<?php
/**
 * Generate a view of the events on a particular day
 * 
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'classes/calendar.php' );
reason_include_once( 'classes/admin/admin_page.php' );


if (!reason_check_authentication())
{
	header('HTTP/1.1 400 Bad Request');
	echo '<html><head><title>Calendar did not work</title><meta name="robots" content="none" /></head><body><h1>Calendar did not work</h1><p>You must be logged in to use this script.</p></body></html>';
} 
else if (!empty($_REQUEST['date']))
{
	// normalize the date format
	$stamp = strtotime($_REQUEST['date']);
	$date = date('Y-m-d', $stamp);
	
	// Get and sort the events
	$calendar = new reasonCalendar(array('start_date' => $date, 'end_date' => $date, 'view'=>'daily', 'show_statuses'=>array('show','tentative','cancelled')));
	$calendar->run();
	$events = $calendar->get_all_events();
	usort( $events, 'compare_times' );
	
	// Figure out the URL for the borrow action
	parse_str(trim($_REQUEST['params'],'?'),$params);
	$editing_id = $params['id'];
	$params['cur_module']='DoBorrow';
	
	echo '<h4>Other events for '. date( 'l, F jS', $stamp) . ':</h4>';

	if (count($events))
	{
		// Ask the admin page class for the token that will allow us
		// to complete the borrow action.
		$admin = new AdminPage();
		$params['admin_token'] = $admin->get_admin_token();
		
		echo '<p>Click an event to add it to the <em>'.$_REQUEST['site'].'</em> calendar:</p>';
		echo '<ul class="preview_list">';
		foreach ($events as $event)
		{
			$classes = array($event->get_value( 'show_hide' ).'_status');
			if ($editing_id == $event->get_value( 'id' )) $classes[] = 'current';
			echo '<li class="'.join(' ', $classes).'">';
			$params['id'] = $event->get_value( 'id' );
			if(substr($event->get_value( 'datetime' ), 11) != '00:00:00')
				$time = prettify_mysql_datetime( $event->get_value( 'datetime' ), 'g:ia' );
			else
				$time = 'All day';
			
			if ($event->get_value( 'show_hide' ) == 'tentative')
				$time .= '<br /><span class="label">tentative</span>';
				
			echo '<span class="preview_time">'.$time.' </span>';
			echo '<span class="preview_name">';
			
			// Get the owner site for the event; we don't want events from the current site to look borrowable
			$owner = $event->get_owner();
			if ($params['site_id'] != $owner->id())
			{
				echo '<a class="nav" href="#" onclick = \'borrow_confirm("'.carl_make_link($params, $_REQUEST['path']).'", '.$event->get_value('id').'); return false;\' title="Add this event to your calendar">';
				echo $event->get_value('name').'</a>';
			} else {
				echo $event->get_value('name');
			}
			echo '</li>'."\n";	
		}
		echo '</ul>';
	}
	else
	{
		echo '<p>No events found.</p>';	
	}
	
	echo '<a href="/calendar/?date='.$date.'" target="_blank">Full Calendar</a>';
	
	echo '<div id="borrow_confirm">
		<div id="event_detail"></div>
		<hr>
		<p>Click below to borrow this event and place it on your site. 
		<em>This will cancel any changes to the event you are currently editing.</em></p>
		<p class="buttons">
		<a class="confirm" href="#">Borrow this Event</a> 
		<a class="cancel" href="#" onclick = "borrow_confirm_cancel()">Cancel</a></p></div>';
	echo '<div id="borrow_confirm_shade"></div>';

}
else if (!empty($_REQUEST['event_id']))
{
	if ( $event = new entity($_REQUEST['event_id']))
	{
		echo '<h3>'.$event->get_value('name').'</h3>';
		echo '<p>'.$event->get_value('location').'</p>';
		echo '<p>'.$event->get_value('description').'</p>';
		echo '<p>'.$event->get_value('sponsor').'<br />';
		echo 'Contact: '.$event->get_value('contact_username').'@carleton.edu</p>';
	} else {
		echo '<h3>Event Detail Not Available</h3>';	
	}
}
else
{
	header('HTTP/1.1 400 Bad Request');
	echo '<html><head><title>Calendar did not work</title><meta name="robots" content="none" /></head><body><h1>Calendar did not work</h1><p>Use the form "?date=YYYY-MM-DD"</p></body></html>';
} 
?>
