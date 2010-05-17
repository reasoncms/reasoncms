<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
reason_include_once( 'minisite_templates/modules/events.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsInstancewideModule';

/**
 * A minisite module that creates a calendar from all sites in this Reason instance
 *
 * If the current site is live it will only show events from live sites
 *
 * Note: this module has some performance issues on large Reason installations; it is probably
 * a good idea to turn on page caching for the site that contains this module until these 
 * performance issues are resolved.
 */
class EventsInstancewideModule extends EventsModule
{
	var $limit_to_current_site = false;

		function _get_sites()
		{
			return $this->_get_sharing_sites($this->site_id);
		}
		function _get_sharing_mode()
		{
			return 'shared_only';
		}
        function show_feed_link()
        {
                $type = new entity(id_of('event_type'));
                if($type->get_value('feed_url_string'))
                        echo '<div class="feedInfo"><a href="/'.REASON_GLOBAL_FEEDS_PATH.
			'/'.$type->get_value('feed_url_string').'" title="RSS feed for this site\'s events">xml</a></div>';
        }
}
?>
