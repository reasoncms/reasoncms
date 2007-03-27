<?php 
reason_include_once( 'minisite_templates/modules/events.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsInstancewideModule';

class EventsInstancewideModule extends EventsModule
{
	var $limit_to_current_site = false;

        function show_feed_link()
        {
                $type = new entity(id_of('event_type'));
                if($type->get_value('feed_url_string'))
                        echo '<div class="feedInfo"><a href="/'.REASON_GLOBAL_FEEDS_PATH.
			'/'.$type->get_value('feed_url_string').'" title="RSS feed for this site\'s events">xml</a></div>';
        }
}
?>
