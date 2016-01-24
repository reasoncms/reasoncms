<?php
/**
 * The default model for event gallery items
 *
 * @package reason
 * @subpackage modules
 */
 
/**
 * Register model
 */
$GLOBALS['reason_event_gallery_models'][basename(__FILE__, '.php')] = 'eventGalleryItem';

reason_include_once('classes/date_range_formatters/functions.php');

/**
 * The default model for event gallery items
 */
class eventGalleryItem
{
	protected $_event;
	protected $_image;
	protected $_url;
	public function __construct($event)
	{
		$this->_event = $event;
	}
	public function get_value($key)
	{
		if(!empty($this->_event))
			return $this->_event->get_value($key);
		else
			return NULL;
	}
	public function get_relationship($relationship_name, $direction)
	{
		if(!empty($this->_event))
		{
			switch($direction)
			{
				case 'right':
					return $this->_event->get_right_relationship($relationship_name);
				case 'left':
					return $this->_event->get_left_relationship($relationship_name);
				default:
					trigger_error('$direction passes to get_relationship must be either "left" or "right"');
			}
		}
		return NULL;
	}
	public function get_main_title()
	{
		if(!empty($this->_event))
		{
			list($main) = explode(':',$this->_event->get_value('name'));
			return $main;
		}
		return NULL;
	}
	public function get_subtitle()
	{
		$parts = explode(':',$this->_event->get_value('name'),2);
		if(isset($parts[1]))
			return $parts[1];
		return NULL;
	}
	public function get_image()
	{
		if(!isset($this->_image))
		{
			$es = new entity_selector();
			$es->add_type(id_of('image'));
			$es->add_right_relationship($this->_event->id(),relationship_id_of('event_to_image'));
			$es->add_rel_sort_field($this->_event->id(), relationship_id_of('event_to_image'));
			$es->set_order('rel_sort_order ASC');
			$es->set_num(1);
			$images = $es->run_one();
			if(!empty($images))
				$this->_image = current($images);
			else
				$this->_image = false;
		}
		return $this->_image;
	}
	/**
	 * 'past','current','future'
	 */
	public function temporal_status()
	{
		if($this->_event->get_value('datetime') <= carl_date('Y-m-d h:i:s'))
		{
			if($this->_event->get_value('last_occurence') < carl_date('Y-m-d'))
				return 'past';
			return 'current';
		}
		return 'future';
	}
	public function temporal_phrase()
	{
		$status = $this->temporal_status();
		switch($status)
		{
			case 'past':
				return 'Archived';
			case 'current':
				return 'Current';
			case 'future':
				return 'Upcoming';
			default:
				trigger_error('Unexpected value from temporal_status(): '.$status);
				return NULL;
		}
	}
	/**
	 * Get a phrase representing a date range
	 *
	 * @param string $format
	 *
	 */
	public function date_range_phrase($format = 'default')
	{
		return format_event_date_range($this->_event, $format);
	}
	/**
 	 * @todo find events page
	 */
	public function get_url()
	{
		if(!isset($this->_url))
		{
			if($this->_event->get_value('url'))
				return $this->_event->get_value('url');
			elseif($owner = $this->_event->get_owner())
			{
				static $cache = array();
				if(!isset($cache[$owner->id()]))
				{
					reason_include_once('classes/module_sets.php');
					$ms =& reason_get_module_sets();
					$modules = $ms->get('event_display');
					if(!empty($modules))
					{
						$page_types = page_types_that_use_module($modules);
						if(!empty($page_types))
						{
							array_walk($page_types,'db_prep_walk');
							$es = new entity_selector($owner->id());
							$es->add_type(id_of('minisite_page'));
							$es->add_relation('`custom_page` IN ('.implode(',',$page_types).')');
							$es->set_num(1);
							$pages = $es->run_one();
							if(!empty($pages))
							{
								$page = current($pages);
								$cache[$owner->id()] = build_URL_from_entity($page);
							}
						}
					}
				}
				if(empty($cache[$owner->id()]))
				{
					$cache[$owner->id()] = false;
					$this->_url = '';
				}
				else
				{
					$this->_url = $cache[$owner->id()].'?event_id='.$this->_event->id();
				}
			}
			else
			{
				$this->_url = '';
			}
		}
		return $this->_url;
	}
}

?>
