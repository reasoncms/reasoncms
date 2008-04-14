<?
include_once('reason_header.php');
include_once(DISCO_INC . 'disco.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/head_items.php');
reason_include_once('function_libraries/user_functions.php');

/**
 * The publication migrator wizard helps transition sites using old style news to use
 * the publications module.
 *
 * It basically works as follows:
 * 
 * - Identify sites using page types from the old publications framework
 * - Screen 1: Allow selection of a site to "migrate."
 * - Screen 2: Allow association of news items to an existing or new publication.
 * - Screen 3: Map known page types from old page type to new page type and relate publication.
 *
 * Not all page types can be known, and some sites will require some manual work to migrate.
 *
 * While this migrator references some Carleton specific sites and page_types, it should work just fine
 * on non-Carleton old style news.
 *
 * Even for sites that require manual work, it may be worthwhile to extend this tool to handle them...
 *
 * @author Nathan White
 */
class MigratorScreen extends Disco
{
	var $helper;
	var $redirect_after_process = true;
	
	function init( $externally_setup = true )
	{
		$this->step_init();
		parent::init( $externally_setup );
	}
	
	function step_init()
	{
	}
	
	function pre_show_form()
	{
		echo '<h1>Publication Migration Wizard</h1>';
		$this->show_site_status();
		$this->step_pre_show_form();
	}
	
	function step_pre_show_form()
	{
	}
	
	function where_to()
	{
		if ($this->redirect_after_process)
		{
			$values =& $this->get_values_to_pass();
			return carl_make_redirect($values);
		}
	}
	
	function redirect_to_screen($screen)
	{
		$redirect = carl_make_redirect(array('active_screen' => $screen));
		header("Location: " . $redirect );
		exit;
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => '');
		return $values;
	}
	
	function show_site_status()
	{
		$site_id = $this->helper->get_site_id();
		if ($site_id > 0)
		{
			$status_html[] = '<strong>Active site is ' . $this->helper->get_site_name() . '</strong>';
			$publications = $this->helper->get_site_publication_names_by_id();
			$pub_count = (!empty($publications)) ? count($publications) : '0';
			$pub_string = ($pub_count == 1) ? 'publication' : 'publications';
			$status_html[] = 'Site has ' . $pub_count . ' ' . $pub_string;
			
			$unattached_news_items = $this->helper->get_unattached_news_item_names_by_id();
			
			if (!empty($unattached_news_items)) $status_html[] = 'Site has ' . count($unattached_news_items) . ' unattached news items';
			$attached_news_items = $this->helper->get_attached_news_item_names_by_id();
			
			
			if (!empty($attached_news_items)) $status_html[] = 'Site has ' . count($attached_news_items) . ' attached news items';
			
			$unattached_issues = $this->helper->get_unattached_issue_names_by_id();
			if (!empty($unattached_issues)) $status_html[] = 'Site has ' . count($unattached_issues) . ' unattached issues';
			$attached_issues = $this->helper->get_attached_issue_names_by_id();
			if (!empty($attached_issues)) $status_html[] = 'Site has ' . count($attached_issues) . ' attached issues';
			
			$unattached_sections = $this->helper->get_unattached_section_names_by_id();
			if (!empty($unattached_sections)) $status_html[] = 'Site has ' . count($unattached_sections) . ' unattached news sections';
			$attached_sections = $this->helper->get_attached_section_names_by_id();
			if (!empty($attached_sections)) $status_html[] = 'Site has ' . count($attached_sections) . ' attached news sections';
			
			
			$status_html = '<ul><li>' . implode('</li><li>', $status_html) . '</li></ul>';
			$start_over_link = true;
		}
		else
		{
			$status_html = '<ul><li>No site is selected</li></ul>';
		}
		echo '<div id="status">';
		echo '<h2>Status</h2>';
		echo $status_html;
		if (isset($start_over_link)) echo '<p><a href="'.carl_construct_link().'">Start Over</a></p>';
		echo '</div>';
	}
}

class MigratorScreen1 extends MigratorScreen
{
	var $actions = array('Continue');
	var $site_names_by_id;
	
	function step_init()
	{
		$this->site_names_by_id = $this->helper->get_site_names_by_id();
	}
	
	function on_every_time()
	{
		$this->add_element('active_screen', 'hidden');
		$this->set_value('active_screen', 1);		
		$this->add_element('site_id', 'select_no_sort', array('options' => $this->site_names_by_id, 'display_name' => 'Choose a Site'));
	}
	
	function step_pre_show_form()
	{
		echo '<h2>Select a Site</h2>';
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "2", 'site_id' => $this->get_value('site_id'));
		return $values;
	}
}

class MigratorScreen2 extends MigratorScreen
{
	function step_pre_show_form()
	{
		echo '<h2>Attach News Items</h2>';
		if (!empty($this->unattached_news_item_names_by_id))
		{
			echo '<p>In this step, choose a publication and select unattached published news items to attach to the publication.</p>';
		}
		else
		{
			if (empty($this->unattached_news_items_names_by_id))
			{
				echo '<p>All news items on the site are attached to a publication.</p>';
			}
			if ($this->site_has_issue_type)
			{
				if (empty($this->unattached_issue_names_by_id)) echo '<p>All issues on the site are attached to a publication.</p>';
				else $options[] = '<a href="'.carl_make_link(array('active_screen' => 6)).'">Attach issues to a publication</a>';
			}
			if ($this->site_has_section_type)
			{
				if (empty($this->unattached_section_names_by_id)) echo '<p>All news sections attached to a publication.</p>';
				else $options[] = '<a href="'.carl_make_link(array('active_screen' => 7)).'">Attach news sections to a publcation</a>';
			}
			echo '<p>You may proceed do the following:</p>';
			$options[] = '<a href="'.carl_make_link(array('active_screen' => 4)).'">Modify page types on the site</a>';
			$options[] = '<a href="'.carl_make_link(array('active_screen' => 3)).'">Create a new publication</a>';
			$options[] = '<a href="'.carl_construct_link().'">Choose another site</a>';
			echo '<ul><li>'.implode('</li><li>',$options).'</li></ul>';
		}
	}
	
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		if (!$this->site_id) $this->redirect_to_screen(1);
		$this->user_id = $this->helper->get_user_id();
		$this->site_name = $this->helper->get_site_name();
		$this->site_has_issue_type = $this->helper->does_site_have_issue_type();
		$this->site_has_section_type = $this->helper->does_site_have_section_type();
		$this->site_publication_names_by_id = $this->helper->get_site_publication_names_by_id();
		$this->new_publication_link = carl_make_link(array('active_screen' => "3"));
		if (empty($this->site_publication_names_by_id)) $this->redirect_to_screen("3");
		else
		{
			$this->unattached_news_item_names_by_id = $this->helper->get_unattached_news_item_names_by_id();
			if ($this->site_has_issue_type)
			{
				$this->unattached_issue_names_by_id = $this->helper->get_unattached_issue_names_by_id();
			}
			if ($this->site_has_section_type)
			{
				$this->unattached_section_names_by_id = $this->helper->get_unattached_section_names_by_id();
			}
		}
	}
	
	/**
	 * @todo add javascript hooks to check / uncheck all
	 */
	function on_every_time()
	{
		if (!empty($this->unattached_news_item_names_by_id))
		{
			$this->add_element('publication_id', 'select_no_sort', array('options' => $this->site_publication_names_by_id, 'display_name' => 'Choose a Publication'));
			$this->set_comments('publication_id', form_comment('<p>...Or <a href="'.$this->new_publication_link.'">create a new publication<a/></p>'));
			$this->add_element('news_items', 'checkboxgroup', array('options' => $this->unattached_news_item_names_by_id, 'display_name' => 'Choose News Items to Attach'));
			$this->set_value('news_items', array_keys($this->unattached_news_item_names_by_id)); // check all by default
		}
		else
		{
			$this->actions = array();
		}
	}
	
	function run_error_checks()
	{
		if (!$this->get_value('news_items'))
		{
			$this->set_error('news_items', 'You must select at least one news item to attach to the publication.');
		}
	}
	
	function process()
	{
		if (!empty($this->unattached_news_item_names_by_id))
		{
			$pub_id = $this->get_value('publication_id');
			$news_items_to_link = $this->get_value('news_items');
			foreach ($news_items_to_link as $item_id)
			{
				create_relationship($item_id, $pub_id, relationship_id_of('news_to_publication'));
				/* Carleton specific custom code for dean of the college laird news pub */
				if (reason_unique_name_exists('dean_of_the_college_office') && ($this->helper->get_site_id() == id_of('dean_of_the_college_office')))
				{
					$this->process_doc($item_id, $pub_id);
				}
			}
		}
	}
	
	/**
	 * Carleton specific custom code for dean of the college laird news pub
	 *
	 * If the news type field is populated, make sure the section it maps to exists and is associated with the publication.
	 * Also, make sure the news item is associated with the publication. 
	 *
	 * ...should this also zap the news_type field from each entity? This will mean the page is broken temporarily until the page type is changed
	 *
	 * 'Press Release'=>'General News',
	 * 'Kudos'=>'Congratulations',
	 * 'Grants'=>'Grants and Fellowships',
	 * 'Conferences'=>'Conferences and Workshops',
	 */
	function process_doc($item_id, $pub_id)
	{
		$type_map = array('Press Release' => 'General News',
						  'Kudos'=>'Congratulations',
						  'Grants'=>'Grants and Fellowships',
						  'Conferences'=>'Conferences and Workshops');

		$item = new entity($item_id);
		$type = $item->get_value('news_type');
		if (isset($type_map[$type]))
		{
			$this->helper->ensure_type_is_on_site(id_of('news_section_type'));
			$section_id = $this->helper->get_section_id_by_name($type_map[$type]);
			if (!$section_id) // create the section
			{
				$section_type_id = id_of('news_section_type');
				$name = $type_map[$type];
				$order_array = array_keys($type_map);
				$values['new'] = 0;
				$values['sort_order'] = array_search($type, $order_array);
				$section_id = reason_create_entity($this->site_id, $section_type_id, $this->user_id, $name, $values);
				//create_relationship($section_id, $pub_id, relationship_id_of('news_section_to_publication'));
			}
			create_relationship($item_id, $section_id, relationship_id_of('news_to_news_section')); // relate news item to section
		}
	}
	
	// we'll jump to same screen in case there are others to associate ... if finished, the init will bounce us on to the next phase
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "2", 'site_id' => $this->site_id);
		return $values;
	}
}

/**
 * Issues
 */
class MigratorScreen6 extends MigratorScreen
{
function step_pre_show_form()
	{
		echo '<h2>Attach Issues</h2>';
		if (!empty($this->unattached_news_item_names_by_id))
		{
			echo '<p>In this step, choose a publication and select unattached issues to attach to the publication.</p>';
		}
	}
	
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		if (!$this->site_id) $this->redirect_to_screen(1);
		$this->user_id = $this->helper->get_user_id();
		$this->site_name = $this->helper->get_site_name();
		$this->new_publication_link = carl_make_link(array('active_screen' => "3"));
		$this->site_publication_names_by_id = $this->helper->get_site_publication_names_by_id();
		$this->unattached_issue_names_by_id = $this->helper->get_unattached_issue_names_by_id();
		if (empty($this->site_publication_names_by_id)) $this->redirect_to_screen("3");
		if (empty($this->unattached_issue_names_by_id)) $this->redirect_to_screen("2");
	}
	
	/**
	 * @todo add javascript hooks to check / uncheck all
	 */
	function on_every_time()
	{
		$this->add_element('publication_id', 'select_no_sort', array('options' => $this->site_publication_names_by_id, 'display_name' => 'Choose a Publication'));
		$this->set_comments('publication_id', form_comment('<p>...Or <a href="'.$this->new_publication_link.'">create a new publication<a/></p>'));
		$this->add_element('issues', 'checkboxgroup', array('options' => $this->unattached_issue_names_by_id, 'display_name' => 'Choose Issues to Attach'));
		$this->set_value('issues', array_keys($this->unattached_issue_names_by_id)); // check all by default
	}
	
	function run_error_checks()
	{
		if (!$this->get_value('issues'))
		{
			$this->set_error('issues', 'You must select at least one issue to attach to the publication.');
		}
	}
	
	function process()
	{
		$pub_id = $this->get_value('publication_id');
		$issues_to_link = $this->get_value('issues');
		foreach ($issues_to_link as $issue_id)
		{
			create_relationship($issue_id, $pub_id, relationship_id_of('issue_to_publication'));
		}
		
		// update the publication - set has_issues to "Yes"
		reason_update_entity($pub_id, $this->user_id, array('has_issues' => 'yes'));
	}
	
	// we'll jump to same screen in case there are others to associate ... if finished, the init will bounce us on to the next phase
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "2", 'site_id' => $this->site_id);
		return $values;
	}
}

/**
 * Sections
 */
class MigratorScreen7 extends MigratorScreen
{
function step_pre_show_form()
	{
		echo '<h2>Attach News Sections</h2>';
		if (!empty($this->unattached_sections_names_by_id))
		{
			echo '<p>In this step, choose a publication and select unattached news sections to attach to the publication.</p>';
		}
	}
	
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		if (!$this->site_id) $this->redirect_to_screen(1);
		$this->user_id = $this->helper->get_user_id();
		$this->site_name = $this->helper->get_site_name();
		$this->new_publication_link = carl_make_link(array('active_screen' => "3"));
		$this->site_publication_names_by_id = $this->helper->get_site_publication_names_by_id();
		$this->unattached_section_names_by_id = $this->helper->get_unattached_section_names_by_id();
		if (empty($this->site_publication_names_by_id)) $this->redirect_to_screen("3");
		if (empty($this->unattached_section_names_by_id)) $this->redirect_to_screen("2");
	}
	
	/**
	 * @todo add javascript hooks to check / uncheck all
	 */
	function on_every_time()
	{
		$this->add_element('publication_id', 'select_no_sort', array('options' => $this->site_publication_names_by_id, 'display_name' => 'Choose a Publication'));
		$this->set_comments('publication_id', form_comment('<p>...Or <a href="'.$this->new_publication_link.'">create a new publication<a/></p>'));
		$this->add_element('sections', 'checkboxgroup', array('options' => $this->unattached_section_names_by_id, 'display_name' => 'Choose News Sections to Attach'));
		$this->set_value('sections', array_keys($this->unattached_section_names_by_id)); // check all by default
	}
	
	function run_error_checks()
	{
		if (!$this->get_value('sections'))
		{
			$this->set_error('sections', 'You must select at least one issue to attach to the publication.');
		}
	}
	
	function process()
	{
		$pub_id = $this->get_value('publication_id');
		$sections_to_link = $this->get_value('sections');
		foreach ($sections_to_link as $section_id)
		{
			create_relationship($section_id, $pub_id, relationship_id_of('news_section_to_publication'));
		}
		
		// update the publication - set has_sections to "Yes"
		reason_update_entity($pub_id, $this->user_id, array('has_sections' => 'yes'));
	}
	
	// we'll jump to same screen in case there are others to associate ... if finished, the init will bounce us on to the next phase
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "2", 'site_id' => $this->site_id);
		return $values;
	}
}

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

class MigratorScreen4 extends MigratorScreen
{
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		if (!$this->site_id) $this->redirect_to_screen(1);
		$this->user_id = $this->helper->get_user_id();
		$this->site_publication_names_by_id = $this->helper->get_site_publication_names_by_id();
		$this->pages_using_news_modules = $this->helper->get_pages_using_news_modules();
		$this->publication_module_page_types = $this->helper->get_publication_module_page_types();
		$this->recommended_page_type_map = $this->helper->get_recommended_page_type_mapping();
	}
	
	function step_pre_show_form()
	{
		echo '<h2>Modify Page Types</h2>';
		if (!empty($this->pages_using_news_modules))
		{
			echo '<p>This phase does some analysis of page types and allows you to modify the pages that currently use old-style news to use page types 
				  compatible with the new publications module. This process is imperfect - many custom page types cannot be mapped directly onto a 
			      publication page type and will require the creation of new page types. Also, CSS files may need to be updated depending upon whether 
			      or not the site was using custom CSS for the display of news pages/sidebars.</p>';
		}
		else
		{
			echo '<p>There are no pages on the site using old-style news. You are probably finished!</p>';
			$options[] = '<a href="'.carl_construct_link().'">Choose another site</a>';
			echo '<ul><li>'.implode('</li><li>',$options).'</li></ul>';
		}	  
	}
	
	function on_every_time()
	{
		if (!empty($this->pages_using_news_modules))
		{
			foreach ($this->pages_using_news_modules as $k=>$page)
			{
				$grp_name = 'page' . $k;
				$cpt_name = 'cpt_page'.$k;
				$npt_name = 'npt_page'.$k;
				$pt_value = $page->get_value('custom_page');
			
				$this->add_element($cpt_name, 'solidtext');
				$this->set_value($cpt_name, $page->get_value('custom_page'));
				$this->add_element($npt_name, 'select_no_sort', array('options' => $this->publication_module_page_types, 'add_null_value_to_top' => true));
				$this->add_element_group('table', $grp_name, array($cpt_name, $npt_name), array('use_element_labels' => false, 
																								'rows' => array('Current Page Type: ', 'New Page Type: ')) );
				$this->set_display_name($grp_name, '<h3>'.$page->get_value('name').'</h3>');
				
				if (isset($this->recommended_page_type_map[$pt_value]))
				{
					$this->set_value($npt_name, $this->recommended_page_type_map[$pt_value]);
				}
				$this->add_element('pubs_for_page'.$k, 'checkboxgroup', array('options' => $this->site_publication_names_by_id, 'display_name' => 'Publication(s) to relate to page'));
			}
		}
		else
		{
			$this->actions = array();
		}
	}
	
	function pre_error_check_actions()
	{
		foreach ($this->pages_using_news_modules as $k=>$page)
		{
			
		}
	}
	
	function run_error_checks()
	{
		foreach ($this->pages_using_news_modules as $k=>$page)
		{
			$npt_name = 'npt_page'.$k;
			$ar_name = $this->helper->get_allowable_relationship_for_page_type($this->get_value($npt_name));
			$pubs_for_page = $this->get_value('pubs_for_page'.$k);
			if (empty($pubs_for_page))
			{
				$this->set_error('pubs_for_page'.$k, 'You must choose a publication to relate to ' . $page->get_value('name'));
			}
			elseif ( (count($pubs_for_page) > 1) && ($this->helper->get_allowable_relationship_for_page_type($this->get_value($npt_name)) == 'page_to_publication') )
			{
				$this->set_error('pubs_for_page'.$k, 'You can only choose one publication to related to the page for the new page type ' . $npt_name);
			}
		}
	}
	
	function process()
	{
		foreach ($this->pages_using_news_modules as $k=>$page)
		{
			$npt_name = 'npt_page'.$k;
			$ar_name = $this->helper->get_allowable_relationship_for_page_type($this->get_value($npt_name));
			$pubs_for_page = $this->get_value('pubs_for_page'.$k);
			
			reason_update_entity($k, $this->user_id, array('custom_page' => $this->get_value($npt_name)));
			foreach ($pubs_for_page as $pub_id)
			{
				create_relationship($k, $pub_id, relationship_id_of($ar_name)); 
			}
		}
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "4", 'site_id' => $this->site_id);
		return $values;
	}
}

class MigratorScreen5 extends MigratorScreen
{
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		$this->user_id = $this->helper->get_user_id();
	}
	
	function step_pre_show_form()
	{
		$link = carl_construct_link();
		echo '<h4>Finished</h4>';
		echo '<p><a href="'.$link.'">Start Over</a></p>';		  
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "5", 'site_id' => $this->site_id);
		return $values;
		//return array();
	}
}

class PublicationMigratorHelper
{
	var $cleanup_rules = array('active_screen' => array('function' => 'check_against_array', 'extra_args' => array("1","2","3","4","5","6","7")),
							   'site_id' => array('function' => 'turn_into_int'));
	
	/**
	 * Old style news modules
	 * @var array
	 */
	var $news_modules = array('news', 'news_mini', 'news_via_categories', 'news_by_category', 'news_rand', 'news_all',
	                          'news_one_at_a_time', 'news_proofing', 'news_proofing_multipage', 
	                          'news2', 'news2_mini', 'news2_mini_random', 'news_doc');
	
	var $publication_modules = array('publication');
	
	var $page_types_with_main_post_news = array('news', 'news_doc', 'news_one_at_a_time', 'news_rand', 'news_all', 'news2');
	
	/**
	 * Defines known suggested page type mappings from old style news to publication module
	 * @var array
	 */
	var $recommended_page_type_mapping = array('news' => 'publication', 
											   'events_and_news_sidebar' => 'events_and_publication_sidebar',
											   'show_children_and_news_sidebar' => 'show_children_and_publication_sidebar',
											   'events_and_news_sidebar_show_children_nagios_status' => 'events_and_publication_sidebar_show_children_nagios_status',
											   'news_sidebar' => 'publication_sidebar',
											   'children_and_grandchildren_full_names_sidebar_blurbs_no_title_random_news_subnav' => 'international_students_information_front_page',
											   'news_doc' => 'publication_doc',
											   'news_proofing' => 'publication');
	/**
	 * Determine state and init the appropriate migrator screen
	 */
	function init()
	{
		$this->request = carl_clean_vars($_REQUEST, $this->cleanup_rules);
	}
	
	function get_site_id()
	{
		return (isset($this->request['site_id'])) ? $this->request['site_id'] : 0;
	}
	
	function get_user_id()
	{
		static $user_id;
		if (!isset($user_id))
		{
			$user_netid = reason_require_authentication();
			$user_id = get_user_id($user_netid);
		}
		return $user_id;
	}
	
	function get_site_name()
	{
		static $site_name;
		if (!isset($site_name))
		{
			$site_id = $this->get_site_id();
			if ($site_id)
			{
				$site = new entity($site_id);
				$site_name = $site->get_value('name');
			}
			else $site_name = '';
		}
		return $site_name;
	}
	
	function does_site_have_issue_type()
	{
		$site_id = $this->get_site_id();
		$es = new entity_selector();
		$es->add_type(id_of('type'));
		$es->add_right_relationship($this->get_site_id(),relationship_id_of('site_to_type'));
		$es->add_relation('entity.id = "'.id_of('issue_type').'"');
		$es->set_num(1);
		$type = $es->run_one();
		return ($type);
	}
	
	function does_site_have_section_type()
	{
		$site_id = $this->get_site_id();
		$es = new entity_selector();
		$es->add_type(id_of('type'));
		$es->add_right_relationship($this->get_site_id(),relationship_id_of('site_to_type'));
		$es->add_relation('entity.id = "'.id_of('news_section_type').'"');
		$es->set_num(1);
		$type = $es->run_one();
		return ($type);	
	}
	
	function get_allowable_relationship_for_page_type($page_type)
	{
		$pt = ($GLOBALS['_reason_page_types'][$page_type]);
		foreach ($pt as $section)
		{
			if (is_array($section))
			{
				if (isset($section['related_mode']) && ($section['related_mode'] == true)) return 'page_to_related_publication';
			}
		}
		return 'page_to_publication';
	}
	
	/**
	 * @return array site entities that appear to need migration
	 *
	 * @todo be more discriminating currently lists all sites with news items
	 */
	function &get_sites_that_need_migration()
	{	
		static $sites;
		if (!isset($sites))
		{
			$es = new entity_selector();
			$es->limit_tables();
			$es->limit_fields('entity.name');
			$es->add_type(id_of('site'));
			$es->add_left_relationship(id_of('news'), relationship_id_of('site_to_type'));
			$es->set_order('entity.name ASC');
			$sites = $es->run_one();
		}
		return $sites;
	}

	function get_site_names_by_id()
	{
		$sites = $this->get_sites_that_need_migration();
		if (!empty($sites))
		{
			foreach ($sites as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function &get_site_publications()
	{
		static $publications;
		$site_id = $this->get_site_id();
		if (!isset($publications))
		{
			$es = new entity_selector($site_id);
			$es->limit_tables();
			$es->limit_fields('entity.name');
			$es->add_type(id_of('publication_type'));
			$es->set_order('entity.name ASC');
			$publications = $es->run_one();
		}
		return $publications;
	}
	
	function get_site_publication_names_by_id()
	{
		$publications =& $this->get_site_publications();
		if (!empty($publications))
		{
			foreach ($publications as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function &get_unattached_news_items()
	{
		static $unattached_news_items;
		$site_id = $this->get_site_id();
		if (!isset($unattached_news_items))
		{
			$attached_news_items =& $this->get_attached_news_items();
			$es2 = new entity_selector($site_id);
			$es2->limit_tables(array('entity', 'press_release', 'status'));
			$es2->limit_fields(array('release_title'));
			$es2->add_type(id_of('news'));
			$es2->add_relation('status.status = "published"');
			if ($attached_news_items)
			{
				$es2->add_relation('entity.id NOT IN ('.implode(",",array_keys($attached_news_items)).')');
			}
			$unattached_news_items = $es2->run_one();
		}
		return $unattached_news_items;
	}
	
	function &get_attached_news_items()
	{
		static $attached_news_items;
		$site_id = $this->get_site_id();
		if (!isset($attached_news_items))
		{
			if ($site_pubs =& $this->get_site_publications())
			{
				$es = new entity_selector($site_id);
				$es->limit_tables();
				$es->limit_fields();
				$es->add_type(id_of('news'));
				$es->add_left_relationship_field('news_to_publication', 'entity', 'id', 'pub_id', array_keys($site_pubs));
				$attached_news_items = $es->run_one();
			}
			else $attached_news_items = false;
		}
		return $attached_news_items;
	}
	
	function get_unattached_news_item_names_by_id()
	{
		$unattached_news_items =& $this->get_unattached_news_items();
		if (!empty($unattached_news_items))
		{
			foreach ($unattached_news_items as $k=>$v)
			{
				$result[$k] = $v->get_value('release_title');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function get_attached_news_item_names_by_id()
	{
		$attached_news_items =& $this->get_attached_news_items();
		if (!empty($attached_news_items))
		{
			foreach ($attached_news_items as $k=>$v)
			{
				$result[$k] = $v->get_value('release_title');
			}
		}
		return (isset($result)) ? $result : '';
	}

	function &get_unattached_sections()
	{
		static $unattached_sections;
		$site_id = $this->get_site_id();
		if (!isset($unattached_sections))
		{
			$attached_sections =& $this->get_attached_sections();
			$es2 = new entity_selector($site_id);
			$es2->limit_tables();
			$es2->limit_fields();
			$es2->add_type(id_of('news_section_type'));
			if ($attached_sections)
			{
				$es2->add_relation('entity.id NOT IN ('.implode(",",array_keys($attached_sections)).')');
			}
			$unattached_sections = $es2->run_one();
		}
		return $unattached_sections;
	}
	
	function &get_attached_sections()
	{
		static $attached_sections;
		$site_id = $this->get_site_id();
		if (!isset($attached_sections))
		{
			if ($site_pubs =& $this->get_site_publications())
			{
				$es = new entity_selector($site_id);
				$es->limit_tables();
				$es->limit_fields();
				$es->add_type(id_of('news_section_type'));
				$es->add_left_relationship_field('news_section_to_publication', 'entity', 'id', 'pub_id', array_keys($site_pubs));
				$attached_sections = $es->run_one();
			}
			else $attached_sections = false;
		}
		return $attached_sections;
	}
	
	function get_unattached_section_names_by_id()
	{
		$unattached_sections =& $this->get_unattached_sections();
		if (!empty($unattached_sections))
		{
			foreach ($unattached_sections as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function get_attached_section_names_by_id()
	{
		$attached_sections =& $this->get_attached_sections();
		if (!empty($attached_sections))
		{
			foreach ($attached_sections as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}

	function &get_unattached_issues()
	{
		static $unattached_issues;
		$site_id = $this->get_site_id();
		if (!isset($unattached_issues))
		{
			$attached_issues =& $this->get_attached_issues();
			$es2 = new entity_selector($site_id);
			$es2->limit_tables();
			$es2->limit_fields();
			$es2->add_type(id_of('issue_type'));
			if ($attached_issues)
			{
				$es2->add_relation('entity.id NOT IN ('.implode(",",array_keys($attached_issues)).')');
			}
			$unattached_issues = $es2->run_one();
		}
		return $unattached_issues;
	}
	
	function &get_attached_issues()
	{
		static $attached_issues;
		$site_id = $this->get_site_id();
		if (!isset($attached_issues))
		{
			if ($site_pubs =& $this->get_site_publications())
			{
				$es = new entity_selector($site_id);
				$es->limit_tables();
				$es->limit_fields();
				$es->add_type(id_of('issue_type'));
				$es->add_left_relationship_field('issue_to_publication', 'entity', 'id', 'pub_id', array_keys($site_pubs));
				$attached_issues = $es->run_one();
			}
			else $attached_issues = false;
		}
		return $attached_issues;
	}
	
	function get_unattached_issue_names_by_id()
	{
		$unattached_issues =& $this->get_unattached_issues();
		if (!empty($unattached_issues))
		{
			foreach ($unattached_issues as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function get_attached_issue_names_by_id()
	{
		$attached_issues =& $this->get_attached_issues();
		if (!empty($attached_issues))
		{
			foreach ($attached_issues as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function get_section_id_by_name( $name )
	{
		static $section_ids_by_name;
		echo 'given name ' .$name;
		if (!isset($section_ids_by_name[$name]))
		{
			$sections =& $this->get_sections( true );
			if ($sections)
			{
				foreach ($sections as $section)
				{
					$section_name = $section->get_value('name');
					$id = $section->id();
					$section_ids_by_name[$section_name] = $id;
				}
			}
		}
		return (isset($section_ids_by_name[$name])) ? $section_ids_by_name[$name] : false;
	}
	
	function &get_sections( $refresh = false )
	{
		static $sections;
		if (!isset($sections) || $refresh)
		{
			$site_id = $this->get_site_id();
			$es = new entity_selector($site_id);
			$es->add_type(id_of('news_section_type'));
			$sections = $es->run_one();
			$sections = ($sections) ? $sections : false;
		}
		return $sections;
	}
	
	function &get_pages_using_news_modules()
	{
		static $pages_using_news_modules;
		if (!isset($pages_using_news_modules))
		{
			foreach ($this->news_modules as $module)
			{
				$valid_page_types = (isset($valid_page_types))
									? array_unique(array_merge($valid_page_types, page_types_that_use_module($module)))
									: page_types_that_use_module($module);
			}
			foreach (array_keys($valid_page_types) as $k) quote_walk($valid_page_types[$k], NULL);
		
			$site_id = $this->get_site_id();
			$es = new entity_selector($site_id);
			$es->add_type(id_of('minisite_page'));
			$es->add_relation('page_node.custom_page IN ('.implode(",", $valid_page_types).')');
			$pages_using_news_modules = $es->run_one();
		}
		return $pages_using_news_modules;
	}
	
	function &get_publication_module_page_types()
	{
		static $publication_module_page_types;
		if (!isset($publication_module_page_types))
		{
			foreach ($this->publication_modules as $module)
			{
				$valid_page_types = (isset($valid_page_types))
									? array_unique(array_merge($valid_page_types, page_types_that_use_module($module)))
									: array_unique(page_types_that_use_module($module));
			}
			foreach ($valid_page_types as $page_type)
			{
				$publication_module_page_types[$page_type] = $page_type;
			}
		}
		return $publication_module_page_types;
	}
	
	/**
	 * @todo search for all page_types that have a news module in main_post instead of using page_types_with_main_post_news
	 */
	function &get_news_minisite_page()
	{
		static $news_minisite_page;
		if (!isset($news_minisite_page))
		{
			$pub_module_page_types =& $this->get_publication_module_page_types();
			$page_types = implode('","', $this->page_types_with_main_post_news);
			$site_id = $this->get_site_id();
			$es = new entity_selector($site_id);
			$es->add_type(id_of('minisite_page'));
			$es->add_relation('page_node.custom_page IN ("'.$page_types.'")');
			$es->set_num(1);
			$result = $es->run_one();
			if (!empty($result))
			{
				$news_minisite_page = current($result);
			}
			else
			{
				$news_minisite_page = false;
			}
		}
		return $news_minisite_page;
	}
	
	/**
	 * @return object disco form
	 */
	function &get_form()
	{
		$active_form_num = (isset($this->request['active_screen'])) ? $this->request['active_screen'] : "1";
		$migrator_form_name = "MigratorScreen" . $active_form_num;
		$form = new $migrator_form_name;
		$form->helper = $this;
		return $form;
	}
	
	function authenticate()
	{
		$reason_user_id = $this->get_user_id();
		return reason_user_has_privs( $reason_user_id, 'upgrade' );
	}
	
	function ensure_type_is_on_site($type_id)
	{
		$site_id = $this->get_site_id();
		if ($site_id)
		{
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$es->add_right_relationship($this->get_site_id(),relationship_id_of('site_to_type'));
			$es->add_relation('entity.id = "'.$type_id.'"');
			$es->set_num(1);
			$type = $es->run_one();
			if(empty($type))
			{
				create_relationship( $site_id, $type_id, relationship_id_of('site_to_type'));
			}
			return true;
		}
		return false;
	}
	
	function ensure_nobody_group_is_on_site()
	{
		$site_id = $this->get_site_id();
		if ($site_id)
		{
			if(!(site_borrows_entity( $site_id, id_of('nobody_group')) || site_owns_entity( $site_id, id_of('nobody_group'))))
			{
				// borrow it
				create_relationship( $site_id, id_of('nobody_group'), get_borrow_relationship_id(id_of('group_type')));
			}
			return true;
		}
		return false;
	}
	
	function &get_recommended_page_type_mapping()
	{
		return $this->recommended_page_type_mapping;
	}
	
	/**
	 * GUESS METHODS - return best guess for a variety of things and an empty guess if nothing makes sense
	 */
	function guess_desired_publication_name()
	{
		$page =& $this->get_news_minisite_page();
		return ($page) ? $this->get_minisite_page_value($page, 'name') : '';
	}

	function guess_desired_publication_posts_per_page()
	{
		return "12";
	}
	
	function guess_desired_publication_description()
	{
		$page =& $this->get_news_minisite_page();
		return ($page) ? strip_tags($this->get_minisite_page_value($page, 'content')) : '';
	}
	
	function guess_desired_publication_rss_feed_url()
	{
		$page =& $this->get_news_minisite_page();
		$page_name = ($page) ? $this->get_minisite_page_value($page, 'name') : '';
		$rss_feed_url = ($page_name) ? strtolower(str_replace(" ", "_", $page_name)) : '';
		return $rss_feed_url;
	}
	
	function get_minisite_page_value(&$page, $value)
	{
		return $page->get_value($value);
	}
	
	function report()
	{
	}
}

// instantiate relevant classes
$head_items = new HeadItems();
$pmg = new PublicationMigratorHelper();

// add needed head items
$head_items->add_head_item('title',array(),'Publication Migration Wizard',true);
$head_items->add_javascript('//' . REASON_HOST . JQUERY_URL);
$head_items->add_javascript('//' . REASON_HOST . REASON_HTTP_BASE_PATH . 'js/publication_migrator/publication_migrator.js');
$head_items->add_stylesheet('//' . REASON_HOST . REASON_HTTP_BASE_PATH . 'css/publication_migrator/publication_migrator.css');
$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
$html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
$html .= '<head>'."\n";
$html .= $head_items->get_head_item_markup();
$html .= '</head>'."\n";
$html .= '<body>'."\n";

if (!$pmg->authenticate())
{
	$html .= '<h3>Unauthorized</h3><p>You must have Reason upgrade privileges to use this tool.</p>';
}
else
{
	
	$pmg->init();
	$form =& $pmg->get_form();
	ob_start();
	$form->run();
	$html .= ob_get_contents();
	ob_end_clean();
}
$html .= '</body>';
$html .= '</html>';
echo $html;
?>