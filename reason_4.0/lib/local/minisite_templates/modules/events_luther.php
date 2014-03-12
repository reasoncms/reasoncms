<?php

reason_include_once( 'minisite_templates/modules/events.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsLutherModule';

/**
 * Luther extension of Reason Events module
 *
 * By default, this module shows upcoming events on the current site,
 * and proves an interface to see past events
 */

class EventsLutherModule extends EventsModule
{
	
	public function is_sports_event($sponsor)
	// Is the event from one of the Luther sports minisites?
	{
		$url = get_current_url();
		if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/sports\/?/", $url)
				|| preg_match("/([Bb]aseball|[Bb]asketball|[Cc]ross [Cc]ountry|[Ff]ootball|[Gg]olf|[Ss]occer|[Ss]oftball|[Ss]wimming|[Tt]ennis|[Tt]rack|[Vv]olleyball|[Ww]restling)/", $sponsor))
		{
			return true;
		}
		return false;
	}
	
}
