<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'miniEventsModule';

/**
 * A minisite module that creates a minimal "sidebar" style event listing, linking to the main events page on the site
 */
class miniEventsModule extends EventsModule
{
	var $ideal_count = 7;
	var $div_id = 'miniCal';
	var $snap_to_nearest_view = false;
	var $events_page;
	var $default_list_chrome_markup = 'minisite_templates/modules/events_markup/mini/mini_events_list_chrome.php';
	var $default_list_markup = 'minisite_templates/modules/events_markup/mini/mini_events_list.php';
	/**
	 * An array of page types that this module should link to
	 *
	 * @var array
	 * @deprecated use config/module_sets instead
	 */
	var $events_page_types = array();
	/**
	 * An array of additional module names that this module should link to
	 *
	 * @var array
	 * @deprecated use config/module_sets instead
	 */
	var $_events_modules = array();
	
	 /**
	 * Accept the params from the page type, with local additions to acceptable params
	 *
	 * THis method is overloaded so that we can add an additional acceptable parameter:
	 *
	 * 'no_content_message' (string) A message to be used if the module has 
	 * no content to display. Note that the default events module *always* 
	 * has content, so it will only have effect in a subclass that may or
	 * may not have content to display.
	 *
	 * The message string can be in one of two formats: plain string message,
	 * or a uniquely named text blurb identified in this format: 
	 * 'unique_name:text_blurb_unique_name' (case sensitive).
	 * @param array $params
	 **/
	function handle_params( $params )
	{
		if(!isset($this->acceptable_params['no_content_message']))
			$this->acceptable_params['no_content_message'] = '';
		
		if(!isset($this->acceptable_params['title']))
			$this->acceptable_params['title'] = '';
		
		parent::handle_params( $params );
	}
	function init( $args = array() ) // {{{
	{
		parent::init( $args );
		$this->find_events_page();
		
	} // }}}
	function has_content() // {{{
	{
		if($this->_has_content_to_display())
			return true;
		elseif($this->_get_no_content_message())
			return true;
		return false;
	} // }}}
	function _has_content_to_display() // {{{
	{
		if(!empty($this->events_page_url) && !empty($this->calendar))
		{
			$events = $this->calendar->get_all_events();
			if(!empty($events))
				return true;
		}
		return false;
	} // }}}
	
	function _get_no_content_message()
	{
		if(!empty($this->params['no_content_message']))
		{
			$indicator = 'unique_name:';
			if(strpos($this->params['no_content_message'],$indicator) === 0)
			{
				$uname = substr($this->params['no_content_message'],strlen($indicator));
				if(!empty($uname) && reason_unique_name_exists($uname))
				{
					return get_text_blurb_content( $uname );
				}
			}
			else
				return $this->params['no_content_message'];
		}
		return '';
	}
	function run()
	{
		if($this->_has_content_to_display())
		{
			parent::run();
		}
		elseif($msg = $this->_get_no_content_message())
		{
			echo '<div class="eventsNoContentMessage">'.$msg.'</div>'."\n";
		}
	}
	function display_list_title()
	{
		echo '<h3><a href="'.$this->events_page_url.'">'.$this->_get_list_title().'</a></h3>'."\n";
	}

	function _get_list_title()
	{
		if(!empty($this->params['title']))
			return $this->params['title'];
		else
			return $this->events_page->get_value('name');
	}
	function _get_events_module_names()
	{
		reason_include_once( 'classes/module_sets.php' );
		$ms =& reason_get_module_sets();
		return array_unique(array_merge($ms->get('event_display'),$this->_events_modules));
	}
	function find_events_page()
	{
		$module_names = $this->_get_events_module_names();
		reason_include_once( 'minisite_templates/nav_classes/default.php' );
		$ps = new entity_selector($this->parent->site_id);
		$ps->add_type( id_of('minisite_page') );
		$rels = array();
		$page_types = $this->events_page_types;
		foreach($module_names as $module_name)
		{
			$page_types = array_merge($page_types, page_types_that_use_module($module_name));
		}
		$page_types = array_map('addslashes',array_unique($page_types));
		$ps->add_relation('page_node.custom_page IN ("'.implode('","', $page_types).'")');
		$page_array = $ps->run_one();
		reset($page_array);
		$this->events_page = current($page_array);
		if (!empty($this->events_page))
		{
			$ret = $this->parent->pages->get_full_url($this->events_page->id());
		}
		if(!empty($ret))
			$this->events_page_url = $ret;
	}
}
?>
