<?php
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
	public function date_range_phrase()
	{
		if(substr($this->_event->get_value('datetime'),0,10) == $this->_event->get_value('last_occurence'))
		{
			if(mb_strlen(prettify_mysql_datetime($this->_event->get_value('datetime'),'F')) > 3)
				$format = 'M. j, Y';
			else
				$format = 'M j, Y';
			return prettify_mysql_datetime($this->_event->get_value('datetime'),$format);
		}
		
		$diff_years = true;
		$diff_months = true;
		
		$start_year = substr($this->_event->get_value('datetime'),0,4);
		$end_year = substr($this->_event->get_value('last_occurence'),0,4);
		if($start_year == $end_year)
		{
			$diff_years = false;
			$start_month = substr($this->_event->get_value('datetime'),5,2);
			$end_month = substr($this->_event->get_value('last_occurence'),5,2);
			if($start_month == $end_month)
				$diff_months = false;
		}
		if(mb_strlen(prettify_mysql_datetime($this->_event->get_value('datetime'),'F')) > 3)
			$start_format = 'M. j';
		else
			$start_format = 'M j';
		if($diff_years)
			$start_format .= ', Y';
			
		if($diff_months)
		{
			if(mb_strlen(prettify_mysql_datetime($this->_event->get_value('last_occurence'),'F')) > 3)
				$end_format = 'M. j, Y';
			else
				$end_format = 'M j, Y';
		}
		else
			$end_format = 'j, Y';
			
		$ret = prettify_mysql_datetime($this->_event->get_value('datetime'),$start_format);
		
		$ret .= ' '.html_entity_decode('&#8211;',ENT_NOQUOTES,'UTF-8').' ';
		
		$ret .= prettify_mysql_datetime($this->_event->get_value('last_occurence'),$end_format);
		
		return $ret;
		
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
