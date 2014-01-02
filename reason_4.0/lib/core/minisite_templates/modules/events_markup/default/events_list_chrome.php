<?php
/**
 * Events markup class -- the default list chrome markup
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once('minisite_templates/modules/events_markup/interfaces/events_list_chrome_interface.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/default/events_list_chrome.php'] = 'defaultEventsListChromeMarkup';
/**
 * Class that generates overall chome layout for events module
 *
 * This class essentially takes a set of precomposed HTML blocks and organizes them into a whole.
 */
class defaultEventsListChromeMarkup implements eventsListChromeMarkup
{
	/**
	 * The function bundle
	 * @var object
	 */
	protected $bundle;
	/**
	 * Internal cache of markup
	 *
	 * This allows the class to output the same markup multiple times without having to regenerate it
	 *
	 * Use get_section_markup($section) to access this automatically.
	 *
	 * @var array
	 */
	protected $markups = array();
	/**
	 * Modify the page's head items, if desired
	 * @param object $head_items
	 * @return void
	 */
	public function modify_head_items($head_items)
	{
	}
	/**
	 * Set the function bundle for the markup to use
	 * @param object $bundle
	 * @return void
	 */
	public function set_bundle($bundle)
	{
		$this->bundle = $bundle;
	}
	/**
	 * Get the markup for a given section
	 *
	 * Standard sections: list, view_options, navigation, calendar_grid, date_picker, search, options, focus, list_title, ical_links, rss_links
	 *
	 * @param string $section
	 * @return string markup
	 */
	protected function get_section_markup($section)
	{
		if(isset($this->markups[$section]))
			return $this->markups[$section];
		$function = $section.'_markup';
		if($markup = $this->bundle->$function())
			$this->markups[$section] = $markup;
		else
			$this->markups[$section] = '';
		
		return $this->markups[$section];
	}
	/**
	 * Get the list chrome markup
	 * @return string markup
	 */
	public function get_markup()
	{
		$ret = '';
		$ret .= $this->get_section_markup('view_options');
		
		$ret .= '<div class="gridAndOptions">'."\n";
		$ret .= $this->get_section_markup('calendar_grid');
		$ret .= $this->get_section_markup('date_picker');
		$ret .= $this->get_section_markup('search');
		$ret .= $this->get_section_markup('options');
		$ret .= '</div>'."\n";
		
		$ret .= $this->get_section_markup('navigation');
		
		$ret .= $this->get_section_markup('focus');
		$ret .= $this->get_section_markup('list_title');
		
		$ret .= $this->get_section_markup('list');
		
		$ret .= '<div class="foot">'."\n";
		$ret .= $this->get_section_markup('navigation');
		$ret .= $this->get_section_markup('ical_links');
		$ret .= $this->get_section_markup('rss_links');
		$ret .= '</div>'."\n";
		return $ret;
	}
}