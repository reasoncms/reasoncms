<?php
/**
 * This file contains the SelectItems disco multi-step form step for
 * NewsletterModule and a plasmature type used in the creation of a
 * more easily-styled elementTable.
 * 
 * 
 * @see NewsletterModule
 * @author Andrew Bacon
 * @author Nate White
 * @package reason
 * @subpackage admin
 * 
 */

/**
 * Disco multi-step form step for NewsletterModule that asks the 
 * user to pick items to include in their newsletter from a list
 * of events and publication items. 
 * 
 * This step:
 * <ul>
 * <li>Asks a user for a newsletter title and description</li>
 * <li>Checks to see if the user requested either pubs or events</li>
 * <li>Creates disco form elements for the posts or events from the 
 * date ranges/data types they requested</li>
 * </ul>
 * And then sends their choices on to the next step.
 *
 * Note the use of the checkboxgroup plasmature type for events and
 * posts. This makes display on this page more sensible, but getting
 * data from the form a real pain.
 * 
 * @see NewsletterModule
 */
class SelectItems extends FormStep
{
	// the usual disco member data
	var $elements = array(
		'basic_info_header' => array(
			'type' => 'comment',
			'text' => '<h2 class="region">Newsletter Information</h2>'
		),
		'newsletter_title' => array(
		    'display_name' => 'Newsletter Title',
		    'type' => 'text',
		),
		'newsletter_intro' => array(
		    'display_name' => 'Write a message to precede the body of the newsletter',
		    'type' => 'textarea',
		),
);

	var $required = array();
	var $error_header_text = 'Please check your form.';
	
	function init($args=array())
	{
	
		parent::init($args);
		if ($this->controller->get_current_step() != 'SelectItems')
			return;
		
		if ($this->controller->get_form_data('selected_publications') != '' || $this->controller->get_form_data('events_start_date') != '')
			$this->add_element('select_instructions', 'comment', array('text' => '<p>Items from the publications and calendar dates which you picked are displayed below. Select the posts and events that you would like to include in your newsletter from the lists below.</p>'));
		
		if ($this->controller->get_form_data('selected_publications') != '')
		{
			$pubs = $this->controller->get_form_data('selected_publications');
			$this->add_element('pub_items_header', 'comment', array('text' => '<h2 class="region">Posts</h2>'));
			foreach ($pubs as $pub)
			{
				$post_options = array();
				$ph_new = new PublicationHelper($pub);
				if ($startDate = $this->controller->get_form_data('publication_start_date'))
					$ph_new->set_start_date($startDate);
				if ($endDate = $this->controller->get_form_data('publication_end_date'))
					$ph_new->set_end_date($endDate);
				$pub_posts = $ph_new->get_published_items();
				$page_of_publication = $ph_new->get_right_relationship('page_to_publication');
				
				if ($pub_posts) foreach ($pub_posts as $pub_post)
				{
					$available_posts[$pub][$pub_post->get_value('id')] = array(
						'id' => $pub_post->get_value('id'),
						'name' => $pub_post->get_value('name'),
						'date_released' => $pub_post->get_value('datetime'),
						'created_by' => $pub_post->get_value('created_by'),
						'url' => reason_get_page_url($page_of_publication[0]->get_value('id')) . "?story_id=" . $pub_post->get_value('id'),
					);
				}
				if (!empty($available_posts))
				{
					if (empty($available_posts[$pub]))
					{
						$this->add_element("pub_items_number_$pub", 'comment', array('text' => '<p>No live posts were found for the publication "' . $ph_new->get_value('name') . '".</p>'));
					} else {
						$count = count($available_posts[$pub]);
						$text = "$count" . ($count > 1 ? ' posts were' : ' post was');
						$text .= ' found for the publication "' . $ph_new->get_value('name') . '"';

						foreach ($available_posts[$pub] as $post)
						{
							$post_options[$post['id'].'_post'] = $post['name'] . ' (<a target="_blank" href="' . $post['url'] . '">link</a>)';
	//						$this->add_element($item['id'].'_info', 'comment', array('text' => '<p>Date released: '.$item['date_released'].'</p>'));
						}
						$this->add_element("pub_posts_group_$pub", 'checkboxgroup', array('options'=> $post_options));
						$this->set_display_name("pub_posts_group_$pub", $text);
					}
				} else {
					$this->add_element("pub_items_none_$pub", 'comment', array('text' => '<p>No posts were found for "' . $ph_new->get_value('name') . '" during the selected timeframe.</p>'));
				}
			}
		}


		if ($this->controller->get_form_data('events_start_date') != '')
		{
			$site_id = (integer) $_REQUEST['site_id'];
			$site = new entity($site_id);
			$start_date = $this->controller->get_form_data('events_start_date');
			$end_date = $this->controller->get_form_data('events_end_date');
			$end_date = (!empty($end_date)) ? $end_date : date('Y-m-d');
			$cal = new reasonCalendar(array('site'=>$site,'start_date'=>$start_date,'end_date'=>$end_date));
			$cal->run();
			$events = $cal->get_all_events();
			$days = $cal->get_all_days();
			$this->add_element('events_header', 'comment', array('text' => '<h2 class="region">Calendar events</h2>'));
			if ($days)
			{
				// This is a bit of a hack. We want to know the number of deepest-level elements 
				// _before_ we've  iterated through the array, creating elements for each deepest-level
				// element... hence the double foreach.
				$count = 0;
				foreach ($days as $day=>$event_ID_array) foreach ($event_ID_array as $event_item) $count++;
				$text = "<p>$count" . ($count > 1 ? ' calendar events were found.</p>' : ' calendar event was found.</p>');
				$this->add_element('events_number', 'comment', array('text' => $text));

				foreach ($days as $day=>$event_ID_array)
				{
					$event_options = array();
					$events_from_today = array();
					
					foreach ($event_ID_array as $event_ID)
					{
						$events_from_today[$event_ID] = $events[$event_ID];
					}

					uasort($events_from_today, "sort_events");

					foreach ($events_from_today as $event_id => $event_item)
					{
						$prettyDate = date("g:i a", strtotime($event_item->get_value('datetime')));
						$eHelper = new EventHelper();
						@$eHelper->set_page_link($event_item);
						$eventURL = $event_item->get_value('url') . date("Y-m-d", strtotime($event_item->get_value('datetime')));
						$event_options[$event_id . '_event'] = '<strong>' . $prettyDate . '</strong> ' .  $event_item->get_value('name') . ' (<a target="_blank" href="' . $eventURL . '">link</a>)';
					}
					$this->add_element('events_group_day_'.$day, 'checkboxgroup_no_sort', array('options'=> $event_options));
					$rows[] = date("D, M j Y", strtotime($day));
					$elements_to_add_to_group['events_group_day_'.$day] = 'events_group_day_'.$day;
				}
				$args = array(
					'rows' => $rows,
					'use_element_labels' => false,
					'use_group_display_name' => false,
					'wrapper_class' => 'events_wrapper',
				);
				$this->add_element_group('wrappertable', 'events_group',$elements_to_add_to_group,$args);
			} else {
				$this->add_element('events_number', 'comment', array('text' => '<p>No events were found.</p>'));
			}
		}
	}
	
	function on_every_time()
	{
	}
	
	function pre_show_form()
	{
		echo "<h1>Step Two &#8212; Newsletter Details & Items</h1>";
		echo "<p>Fill out the title of your newsletter; add a longer message if you want; and specify which news and/or events you want in the email.</p>";
	}
	
	function process()
	{
	}
}


/**
 * A new plasmature type which wraps an ElementTable in a div.
 * 
 * @package disco
 * @subpackage plasmature
 * @author Matt Ryan
 */ 
class ElementWrapperTable extends ElementTable
{
	var $type = 'wrappertable';
	var $wrapper_class = 'ElementGroupWrapper';
	var $wrapper_id = '';
	function get_display()
	{
		$ret = '<div';
		if(!empty($this->wrapper_class))
			$ret .= ' class="'.$this->wrapper_class.'"';
		if(!empty($this->wrapper_id))
			$ret .= ' id="'.$this->wrapper_id.'"';
		$ret .= '>';
		$ret .= parent::get_display();
		$ret .= '</div>';
		return $ret;
	}
}

?>