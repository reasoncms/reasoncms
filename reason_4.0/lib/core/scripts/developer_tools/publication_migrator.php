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
 * Not all page types can be known, and some sites will require some manual work to migrate. In the initial incarnation,
 * this should only be used on sites that need a publication without issues or sections.
 *
 * @author Nathan White
 */
class MigratorScreen extends Disco
{
	var $helper;
	
	function init()
	{
		$this->step_init();
		parent::init(true);
	}
	
	function step_init()
	{
	}
	
	function pre_show_form()
	{
		echo '<h3>Publication Migrator Wizard</h3>';
		$this->step_pre_show_form();
	}
	
	function step_pre_show_form()
	{
	}
	
	function where_to()
	{
		$values =& $this->get_values_to_pass();
		return carl_make_redirect($values);
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
		echo '<h4>Select a Site</h4>';
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
		echo '<h4>Attach News Items to site ' . $this->site_name. '</h4>';
		echo '<p>In this step, choose a publication and select unattached published news items to attach to the publication.</p>';
	}
	
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		$this->site_name = $this->helper->get_site_name();
		$this->site_publication_names_by_id = $this->helper->get_site_publication_names_by_id();
		$this->new_publication_link = carl_make_link(array('active_screen' => "3"));
		if (empty($this->site_publication_names_by_id)) $this->redirect_to_screen("3");
		else
		{
			$this->news_item_names_by_id = $this->helper->get_unattached_news_item_names_by_id();
			if (empty($this->news_item_names_by_id)) $this->redirect_to_screen("4");
		}
	}
	
	/**
	 * @todo add javascript hooks to check / uncheck all
	 */
	function on_every_time()
	{
		$this->add_element('publication_id', 'select_no_sort', array('options' => $this->site_publication_names_by_id, 'display_name' => 'Choose a Publication'));
		$this->set_comments('publication_id', form_comment('<p>...Or <a href="'.$this->new_publication_link.'">create a new publication<a/></p>'));
		$this->add_element('news_items', 'checkboxgroup', array('options' => $this->news_item_names_by_id, 'display_name' => 'Choose News Items to Attach'));
		$this->set_value('news_items', array_keys($this->news_item_names_by_id)); // check all by default
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
		$pub_id = $this->get_value('publication_id');
		$news_items_to_link = $this->get_value('news_items');
		foreach ($news_items_to_link as $item_id)
		{
			create_relationship($item_id, $pub_id, relationship_id_of('news_to_publication'));
		}
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
		$this->user_id = $this->helper->get_user_id();
	}
	
	function step_pre_show_form()
	{
		echo '<h4>Create a Publication</h4>';
	}

	function on_every_time()
	{
		$this->add_element('pub_name', 'text', array('display_name' => 'Publication Name'));
		$this->set_value('pub_name', $this->helper->guess_desired_publication_name());
		$this->add_required('pub_name');
		$this->add_element('pub_description', 'textarea', array('display_name' => 'Publication Description'));
		$this->set_value('pub_description', $this->helper->guess_desired_publication_description());
		$this->set_comments('pub_description', form_comment('Any text entered here will be displayed at the top of the primary page for the publication'));
		$this->add_element('pub_rss_feed_url', 'text', array('display_name' => 'Publication RSS Feed URL'));
		$this->set_value('pub_rss_feed_url', $this->helper->guess_desired_publication_rss_feed_url());
		$this->add_required('pub_rss_feed_url');
		$this->add_element('pub_posts_per_page', 'text', array('display_name' => 'Posts per page'));
		$this->set_value('pub_posts_per_page', $this->helper->guess_desired_publication_posts_per_page());
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
		$this->user_id = $this->helper->get_user_id();
	}
	
	function step_pre_show_form()
	{
		echo '<h3>NOT YET IMPLEMENTED</h3>';
		echo '<h4>Modify Page Types</h4>';
		echo '<p>This phase does some analysis of page types and allows you to modify the pages that currently use old-style news to use page types 
			  compatible with the new publications module. This process is imperfect - many custom page types cannot be mapped directly onto a 
			  publication page type and will require the creation of new page types. Also, CSS files may need to be updated depending upon whether 
			  or not the site was using custom CSS for the display of news pages/sidebars.</p>';
			  
	}
}

class PublicationMigratorHelper
{
	var $cleanup_rules = array('active_screen' => array('function' => 'check_against_array', 'extra_args' => array("1","2","3","4")),
							   'site_id' => array('function' => 'turn_into_int'));
	
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
	
	/**
	 * @return array site entities that appear to need migration
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
			$es = new entity_selector($site_id);
			$es->limit_tables();
			$es->limit_fields();
			$es->add_type(id_of('news'));
			$es->add_left_relationship_field('news_to_publication', 'entity', 'id', 'news_id');
			$attached_news_items = $es->run_one();
			
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
		return user_is_a( $reason_user_id, id_of('admin_role') );
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
	
	/**
	 * GUESS METHODS - return best guess for a variety of things and an empty guess if nothing makes sense
	 */
	function guess_desired_publication_name()
	{
		return "";
	}

	function guess_desired_publication_posts_per_page()
	{
		return "12";
	}
	
	function guess_desired_publication_description()
	{
		return "";
	}
	
	function guess_desired_publication_rss_feed_url()
	{
		return "";
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
$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
$html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
$html .= '<head>'."\n";
$html .= $head_items->get_head_item_markup();
$html .= '</head>'."\n";
$html .= '<body>'."\n";

if (!$pmg->authenticate())
{
	$html .= '<h3>Unauthorized</h3><p>You must be a Reason administrator to use this tool</p>';
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