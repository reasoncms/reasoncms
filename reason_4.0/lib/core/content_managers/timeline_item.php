<?php
/**
 * Content manager for timeline item
* @package reason
* @subpackage content_managers
*/

/**
 * Store the class name so that the admin page can use this content manager
*/
$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'TimelineItemManager';

/**
 * Content manager for Timeline item
 */
class TimelineItemManager extends ContentManager
{
	function alter_data()
	{
		$this->add_relationship_element('timelines', id_of('timeline_type'), 
				relationship_id_of('timeline_to_timeline_item'),'left','checkbox',true);
		
		if($this->_is_first_time() && !$this->has_errors() && $this->is_new_entity() && !$this->get_value('name') )
		{
			$timelines = $this->get_element_property('timelines', 'options');
			$selected_timelines = $this->get_value('timelines');
			if(empty($selected_timelines) && count($timelines) == 1)
			{
				reset($timelines);
				$this->set_value('timelines',array(key($timelines)));
			}
		}
	}
}