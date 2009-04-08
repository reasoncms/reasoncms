<?php
/**
 * @package reason
 * @subpackage scripts
 */
/**
 * Create a publication
 */
class MigratorScreen3 extends MigratorScreen
{
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		if (!$this->site_id) $this->redirect_to_screen(1);
		$this->user_id = $this->helper->get_user_id();
		$this->site_publication_names_by_id = $this->helper->get_site_publication_names_by_id();
	}
	
	function step_pre_show_form()
	{
		echo '<h2>Create a Publication</h2>';
		if (!empty($this->site_publication_names_by_id))
		{
			$pub_string = (count($this->site_publication_names_by_id) == 1) ? 'publication' : 'publications';
			$link = carl_make_link(array('active_screen' => 2));
			echo '<p>...Or <a href="'.$link.'">attach news items to existing '.$pub_string.'</a></p>';
		}
	}

	function on_every_time()
	{
		$this->add_element('pub_name', 'text', array('display_name' => 'Publication Name'));
		$this->add_required('pub_name');
		$this->add_element('pub_description', 'textarea', array('display_name' => 'Publication Description'));
		$this->set_comments('pub_description', form_comment('Any text entered here will be displayed at the top of the primary page for the publication'));
		$this->add_element('pub_rss_feed_url', 'text', array('display_name' => 'Publication RSS Feed URL'));
		$this->add_required('pub_rss_feed_url');
		$this->add_element('pub_posts_per_page', 'text', array('display_name' => 'Posts per page'));
		$this->add_required('pub_posts_per_page');
		$this->add_element('pub_unique_name', 'text', array('display_name' => 'Publication Unique Name'));
		$this->add_element( 'date_format', 'select_no_sort', array('options' => array('F j, Y \a\t g:i a' => date('F j, Y \a\t g:i a'),
																								  'n/d/y \a\t g:i a' => date('n/d/y \a\t g:i a'),
																								  'l, F j, Y' => date('l, F j, Y'),
																								  'F j, Y' => date('F j, Y'),
																								  'n/d/y' => date('n/d/y'), 
																								  'n.d.y' => date('n.d.y'),
																								  'j F Y' => date('j F Y'),
																								  'j F Y \a\t  g:i a' => date('j F Y \a\t  g:i a'),
																								  'j F Y \a\t  g:i a' => date('j F Y \a\t  H:i'), )));
		// if the site does not have any publications yet, we'll guess at certain values
		if (empty($this->site_publication_names_by_id))
		{
			$this->set_value('pub_rss_feed_url', $this->helper->guess_desired_publication_rss_feed_url());
			$this->set_value('pub_description', $this->helper->guess_desired_publication_description());
			$this->set_value('pub_name', $this->helper->guess_desired_publication_name());
		}
		
		// we always guess at posts per page
		$this->set_value('pub_posts_per_page', $this->helper->guess_desired_publication_posts_per_page());
	}
	
	function run_error_checks()
	{
		$posts_per_page = $this->get_value('pub_posts_per_page');
		if (is_numeric($posts_per_page) == false)
		{
			$this->set_error('pub_posts_per_page', 'The number of posts per page must be numeric');
		}
	}
	
	function process()
	{
		//prep site
		$this->helper->ensure_type_is_on_site(id_of('publication_type'));
		$this->helper->ensure_type_is_on_site(id_of('group_type'));
		$this->helper->ensure_nobody_group_is_on_site();
		
		// gather core information
		$pub_type_id = id_of('publication_type');
		$name = trim(strip_tags($this->get_value('pub_name')));
		
		// populate values array
		$values['new'] = 0;
		$values['description'] = trim(get_safer_html($this->get_value('pub_description')));
		$values['unique_name'] = trim(strip_tags($this->get_value('pub_unique_name')));
		$values['state'] = 'Live';
		$values['hold_comments_for_review'] = 'no';
		$values['posts_per_page'] = turn_into_int($this->get_value('pub_posts_per_page'));
		$values['blog_feed_string'] = trim(strip_tags($this->get_value('pub_rss_feed_url')));
		$values['publication_type'] = 'Newsletter';
		$values['has_issues'] = 'no';
		$values['has_sections'] = 'no';
		$values['date_format'] = $this->get_value('date_format');
		
		// create the publication
		$pub_id = reason_create_entity($this->site_id, $pub_type_id, $this->user_id, $name, $values);
		
		// associate with nobody posting and commenting groups
		create_relationship($pub_id, id_of('nobody_group'), relationship_id_of('publication_to_authorized_posting_group'));
		create_relationship($pub_id, id_of('nobody_group'), relationship_id_of('publication_to_authorized_commenting_group'));
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "2", 'site_id' => $this->site_id);
		return $values;
	}
}
?>