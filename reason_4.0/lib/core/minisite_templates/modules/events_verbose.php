<?php 
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'VerboseEventsModule';


class VerboseEventsModule extends EventsModule
{
	
	function show_event_list_item( $event_id, $day ) // {{{
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
}
?>
