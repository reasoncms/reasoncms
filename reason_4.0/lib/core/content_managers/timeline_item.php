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
	var $box_class = 'stackedBox';
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
		
		// Get image, media work, and location associated with this timeline items
		$image_string = $this->get_preview_string('image', 'image', 'timeline_item_to_image');
		$media_work_string = $this->get_preview_string('media work', 'av', 'timeline_item_to_media_work');
		$location_string = $this->get_preview_string('location', 'loction_type', 'timeline_item_to_location');
				
		$this->add_element('image', 'comment', array('text' => $image_string));
		$this->add_element('media_work', 'comment', array('text' => $media_work_string));
		$this->add_element('location', 'comment', array('text' => $location_string));
		
		$this->set_display_name('other_media', 'Media URL');
		$this->set_display_name('background', 'Background Color');


		$this->set_comments('name', form_comment('Title of the timeline item'));
		$this->set_comments('start_date', form_comment('What is the first date of this item?'));
		$this->set_comments('end_date', form_comment('If this item represents a span of time, what is the last date?'));
		$this->set_comments('display_date', form_comment('If the default date formatting is not desired, enter the way you want the date to appear here'));
		$this->set_comments('text', form_comment('Text to display with the timeline item'));
		$this->set_comments('autolink', form_comment('Turn URLs in the text automatically into links?'));
		$this->set_comments('media', form_comment('What kind of media (if any) do you want on this item?'));
		$this->set_comments('group', form_comment('If you want multiple items grouped together, give them a group name in this field'));


		$this->change_element_type('media', 'select_no_sort', array('options' => 
			array(
				'' => 'None', 
				'reason_image' => 'Image in Reason', 
				'reason_media_work' => 'Media Work in Reason', 
				'reason_location' => 'Location in Reason', 
				'other' => 'Other'
			)
		));
		
		$this->change_element_type('background', 'colorpicker');
		
		// Create a list of all groups used by timeline items on this site
		$groups = array();
		$es = new entity_selector();
		$es->add_type(id_of('timeline_item_type'));
		$es->add_right_relationship($this->get_value('site_id'), relationship_id_of('site_owns_timeline_item_type'));
		$timeline_items = $es->run_one();
		
		foreach ($timeline_items as $timeline_item) {
			if (!empty($timeline_item->get_value('group'))) {
				$groups[] = $timeline_item->get_value('group');
			}
		}
		
		$groups = array_unique($groups);
		
		// Use the list of groups to make a radio choice of groups
		$this->change_element_type('group', 'radio_with_other', array('options' => $groups));

		$this->set_order(
			array(
				'timelines',
				'name',
				'start_date',
				'end_date',
				'display_date',
				'text',
				'autolink',
				'media',
				'image',
				'media_work',
				'location',
				'other_media',
				'group',
				'background'
			)
		);
	
	}
	

	function run_error_checks() {
		$media = $this->get_element('media')->value;
		$background = $this->get_element('background')->value;
		
		// Make sure that other_media has valid URL if 'other' is selected
		if ($media == 'other') {
			$media_url = $this->get_element('other_media')->value;
			
			if (filter_var($media_url, FILTER_VALIDATE_URL) === false) {
				$this->set_error('other_media', 'Not a valid URL: ' . htmlspecialchars($media_url));
			}
		}
				
		// Make sure that background has a valid hex color
		if (!empty($background)) {
			if ($background[0] != '#') {
				$background = '#'.$background;
			}
			
			if (!preg_match('/^#[a-f0-9]{6}$/i', $background)) {
				$this->set_error('background', 'Not a valid hex color: ' . htmlspecialchars($background));
			}
		}
	}
	
	
	function init_head_items() {
		$this->head_items->add_javascript(JQUERY_URL, true);
		$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH .'content_managers/timeline_item.js');
	}
	
	function get_preview_string($name, $type, $relationship) {
		$es = new entity_selector();
		$es->add_type(id_of($type));
		$es->add_right_relationship($this->get_value('id'), relationship_id_of($relationship));
		$results = $es->run_one();
		
		if (!empty($results)) {
			return 'Current ' . $name . ':<br>' . reset($results)->get_display_name();
		} else {
			return 'No ' . $name . ' associated with this timeline item.';
		}
	}

}