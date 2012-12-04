<?
/**
 * This file contains the SelectIncludes disco form step for use in the 
 * newsletter builder admin module. 
 * 
 * @see NewsletterModule
 * @author Andrew Bacon
 * @author Nate White
 * @package reason
 * @subpackage admin
 */

/**
 * Disco multi-step form step that asks the user to pick
 * date ranges and data sources with which to populate the
 * newsletter.
 * 
 * The newsletter is prepopulated with items from two data sources:
 * publications and events.
 * This step:
 * <ul><li>Checks to see if there are publications or events attached to the site</li>
 * <li>Asks which publications to import from</li>
 * <li>Asks for publication and event date ranges</li>
 * </ul>
 * And then sends these choices on to the next step.
 * 
 * @see NewsletterModule
 * 
 */
class SelectIncludes extends FormStep
{
	// the usual disco member data
	var $elements = array();
	var $required = array();
	var $error_header_text = 'Please check your form.';
	
	/**
	 * Grabs a list of all publications and events attached to the site, offers them to 
	 * 
	 * The bulk of this form step. 
	 * 
	 * 
 	 */
	function init($args=array())
	{
		parent::init($args);
		
		$site_id = (integer) $_REQUEST['site_id'];
		
		// Only do this init if we're on the step that needs it.
		if ($this->controller->get_current_step() != 'SelectIncludes')
			return;

		//////////////// PUBLICATIONS /////////////////
		// Select all publications that are attached to this site.
		$pub_factory = new PubHelperFactory();
		$es = new entity_selector($site_id);
		$es->add_type(id_of('publication_type'));
		// Add the page_id to which the pub belongs (so we can get url)
		$es->add_right_relationship_field('page_to_publication', 'entity', 'id', 'page_id');
		$es->enable_multivalue_results();
		$es->set_entity_factory($pub_factory);
		$pub_helper_entities = $es->run_one();
		if ($pub_helper_entities) {
			$this->add_element('pub_posts_header1', 'comment', array('text' => '<h2 class="region">Publications</h2>'));
			foreach ($pub_helper_entities as $ph)
			{
				$name = $ph->get_value('name');
				$entityID = $ph->get_value('id');
				$page_id = $ph->get_value('page_id');
				if (is_array($page_id))
				{
					$strlength = 0;
					$page_url = '';
					foreach ($page_id as $one_id)
					{
						$page_entity = new entity($one_id);
						if ($page_entity->get_value('state') == 'Live')
						{
							$owner = $page_entity->get_owner();
							if ($owner->get_value('state') == 'Live')
							{
								$url = reason_get_page_url($one_id);
								if (strlen($url) > $strlength)
								{
									$strlength = strlen($url);
									$page_url = $url;
								}
							}
						}
					}
					$box_name = '<a target="_blank" href="' . $page_url . '">' . $name . '</a>';
					$opts[$entityID] = $box_name;
				} else {
					$page_entity = new entity($page_id);
					if ($page_entity->get_value('state') == 'Live')
					{
						$owner = $page_entity->get_owner();
						if ($owner->get_value('state') == 'Live')
						{
							$page_url = reason_get_page_url($page_id);
							$box_name = '<a target="_blank" href="' . $page_url . '">' . $name . '</a>';
							$opts[$entityID] = $box_name;
						}
					}
				}
			}
			$this->add_element('selected_publications', 'checkboxgroup', array('options' => $opts));
			$this->set_value('selected_publications', array_keys($opts));
			$this->set_display_name('selected_publications', 'Select the publications from which you wish to draw posts');
			$this->add_element('publication_start_date', 'textDate');
			$monthAgo = date("Y-m-d", strtotime("-1 month"));			
			$today = date("Y-m-d", time());
			$this->set_value('publication_start_date', $monthAgo);
			$this->set_display_name('publication_start_date', 'From this date');
			$this->add_element('publication_end_date', 'textDate');
			$this->set_value('publication_end_date', $today);
			$this->set_display_name('publication_end_date', 'To this date');
		}
		
		
		//////////////// EVENTS ///////////////////
		// !todo: Find any page on the site $site_id which uses the module 'events', and list
		// 		  that page title as a 'calendar' (there are apparently no calendar entities,
		//		  only the events module which acts as a calendar.
		//		  The extra information should be found in the page_type def for the page containing
		//		  the events module. 
		/* $eh = new reasonCalendar();
		
		$site = new entity($site_id);
		$cal = new reasonCalendar(array('site'=>$site));
		$cal->run();
		$events = $cal->get_all_events(); */
		
		$es = new entity_selector($site_id);
		$es->add_type(id_of('event_type'));
		$es->set_num(1);
		$es->limit_tables();
		$es->limit_fields();
		$events = $es->run_one();

		if ($events)
		{
			$this->add_element('events_header1', 'comment', array('text' => '<h2 class="region">Calendars</h2>'));
			$this->add_element('events_start_date', 'textDate');
			$monthAhead = date("Y-m-d", strtotime("+1 month"));
			$today = date("Y-m-d", time());
			$this->set_value('events_start_date', $today);
			$this->set_display_name('events_start_date', 'From this date');
			$this->add_element('events_end_date', 'textDate');
			$this->set_value('events_end_date', $monthAhead);
			$this->set_display_name('events_end_date', 'To this date');
		}
		if (!$events && !$pub_helper_entities)
			$this->add_element('sucks_to_be_you', 'comment', array('text'=>'<h3>There are no publications or calendars associated with this site. Press continue if you would like to use the newsletter builder anyway.'));
		
	}
	
	function on_every_time()
	{
//		$this->controller->destroy_form_data();
		
	}
	

	function pre_show_form()
	{
		echo "<h1>Step One &#8212; Select Publications and Calendar Dates</h1><p>This tool makes it easy to assemble an email newsletter.</p><p>To begin, specify the dates for the items you want to include in your newsletter.</p>";
	}
	
	function process()
	{
	}
}


?>