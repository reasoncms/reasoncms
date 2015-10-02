<?php
/**
 * Events markup class -- the default item markup
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once('classes/media/factory.php');

reason_include_once('minisite_templates/modules/events_markup/interfaces/events_item_admin_interface.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/default/events_item_admin.php'] = 'defaultEventsItemAdminMarkup';
/**
 * Markup class for showing the single item
 */
class defaultEventsItemAdminMarkup implements eventsItemAdminMarkup
{
	/**
	 * The function bundle
	 * @var object
	 */
	protected $bundle;
	/**
	 * Modify the page's head items, if desired
	 * @param object $head_items
	 * @return void
	 */
	public function modify_head_items($head_items, $event = null)
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
	 * Get the admin panel markup for a given event
	 * @param object $event
	 * @return string markup
	 */
	public function get_markup($event)
	{
		if(empty($this->bundle))
		{
			trigger_error('Call set_bundle() before calling get_markup()');
			return '';
		}
		$ret = '';
		if($this->bundle->cur_user_is_reason_editor())
		{
			$ret .= '<div class="borrowThis">';
			if($link = $this->bundle->borrow_this_link($event))
				$ret .= '<a href="'.$link.'" class="borrowThisLink">Borrow this event</a>';
			else
				$ret .= '<span class="borrowThisNotAvailable">Borrowing not available (this is not a shared event).</span>';
			$ret .= '</div>';
		}
		return $ret;
	}
}
