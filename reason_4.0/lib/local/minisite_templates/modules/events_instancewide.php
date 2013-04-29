<?php
/**
* @package reason
* @subpackage minisite_modules
*/
 
 /**
* include the base class and register the module with Reason
*/
reason_include_once( 'minisite_templates/modules/luther_events.php' );
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
class EventsInstancewideModule extends LutherEventsModule
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

	function get_all_categories() // {{{
	{
		$ret = '';
		$cs = new entity_selector();
		$cs = new entity_selector($this->parent->site_id);
		$cs->set_site($this->parent->site_id);
		$cs->description = 'Selecting all categories on the site';
		$cs->add_type(id_of('category_type'));
		$cs->set_order('entity.name ASC');
		$cs->set_cache_lifespan($this->get_cache_lifespan_meta());
		$cats = $cs->run_one();
		// don't check categories on the events minisite
		if ($this->parent->site_id != id_of('events'))
			$cats = $this->check_categories($cats);
		if(empty($cats))
			return '';
		$ret .= '<div class="categories';
		if ($this->calendar->get_view() == "all")
			$ret .= ' divider';
		$ret .= '">'."\n";
		$ret .= '<h4>Event Categories</h4>'."\n";
		$ret .= '<ul>'."\n";
		$ret .= '<li>';
		$used_cats = $this->calendar->get_categories();
		if (empty( $used_cats ))
			$ret .= '<strong>All</strong>';
		else
			$ret .= '<a href="'.$this->construct_link(array('category'=>'','view'=>'')).'" title="Events in all categories">All</a>';
		$ret .= '</li>';
		foreach($cats as $cat)
		{	
			$ret .= '<li>';
			if (array_key_exists($cat->id(), $this->calendar->get_categories()))
				$ret .= '<strong>'.$cat->get_value('name').'</strong>';
			else
				$ret .= '<a href="'.$this->construct_link(array('category'=>$cat->id(),'view'=>'','no_search'=>'1')).'" title="'.reason_htmlspecialchars(strip_tags($cat->get_value('name'))).' events">'.$cat->get_value('name').'</a>';
			$ret .= '</li>';
		}
		
		$ret .= '<li></li>';
		$ret .= '</ul>'."\n";
		$ret .= '</div>'."\n";
		return $ret;
	}
}
?>