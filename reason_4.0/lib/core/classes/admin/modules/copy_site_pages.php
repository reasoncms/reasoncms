<?php
/**
 * @package reason
 * @subpackage admin
 */
 
/**
 * Include the default module and other needed utilities
 */
reason_include_once('classes/admin/modules/default.php');
reason_include_once('function_libraries/url_utils.php');
reason_include_once('function_libraries/root_finder.php');
reason_include_once('classes/url_manager.php');
include_once( DISCO_INC .'disco.php');

/**
 * An administrative module that will duplicate all the pages of a site into another site.
 * Other types and relationships are not preserved, but this can be a timesaver when cloning a
 * site (like the academic catalog) that duplicates a lot of content from site to site.
 * 
 * Since this was built originally for duplicating academic catalogs, it also has some hidden
 * support for copying other types that the catalog uses. This could be extended to create a more
 * general purpose tool.
 * 
 * @author Mark Heiman
 *
 */
class ReasonCopySitePagesModule extends DefaultModule// {{{
{
	protected $test_mode = false;
	protected $source_site;
	protected $destination_site;
	protected $allowed_related = array(
		'page_to_course_catalog_block',
	);
	protected $copied_ids = array();
	
	function ReasonCopySitePagesModule( &$page )
	{
		$this->admin_page =& $page;
	}

	function init()
	{
		parent::init();
		$this->admin_page->title = 'Copy Site Pages';
		$this->admin_page->set_breadcrumbs( array(''=> 'Copy Site Pages' ) );
		force_secure_if_available();
		$current_user = check_authentication();
		$this->user_id = get_user_id($current_user);
	}
	
	function run()
	{
		if (empty( $this->user_id ) )
		{
			die('<h1>Sorry.</h1><p>You do not have permission to move entities among sites.</p><p>Only Reason admins may do that.</p></body></html>');
		}

		echo '<p>This script provides a way to <em>make a copy</em> of all of the pages from one 
			site in another (new) site. To avoid complications, <em>the destination site should be empty</em> -- 
			otherwise you can end up with conflicting URLs, orphaned pages, and other bad things.<p>';
		echo '<p>If the destination site has a home page, you can choose below how you want to place
			the copied pages with regard to the existing home page.<p>';
		echo '<p>This tool does not move any other types. All relationships pages may have with other
			types are discarded.</p>';
		
		$site_options = $this->get_user_sites();
		
		$this->form = new Disco;
		$this->form->add_element('test_mode', 'checkboxfirst', array(
			'display_name' => 'Run in test mode (just list the content that would be copied).',
			));
		$this->form->add_element('source_site', 'select', array('options' => $site_options));
		$this->form->add_element('destination_site', 'select', array('options' => $site_options));
		$this->form->add_element('home_page_handling', 'radio_no_sort', array(
			'display_name' => 'If the destination site has an existing home page:',
			'options' => array(
				'delete' => 'Replace the destination home page with the source home page. The 
					destination home page will be deleted and any child pages will be orphaned.',
				'replace' => 'Attach the child pages of the source site home page to the home page of the
					destination site (the source site home page will not be copied).',
				'attach' => 'Attach the home page of the source site (with its children) as a child 
					of the existing destination home page.'
				),
			'default' => 'delete',
			));
		$this->form->add_required('source_site');
		$this->form->add_required('destination_site');
		$this->form->actions = array('Copy Pages');
		$this->form->add_callback(array(&$this, 'run_error_checks'),'run_error_checks');
		$this->form->add_callback(array(&$this, 'process_copy'),'process');
		$this->form->run();
	}
	
	function get_user_sites()
	{
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_left_relationship($this->user_id, relationship_id_of('site_to_user'));
		$es->set_order('entity.name ASC');
		$sites = $es->run_one();

		$site_options = array();
		foreach( $sites AS $site )
			$site_options[$site->id()] = $site->get_value('name');
			
		return $site_options;
	}

	/**
	 * Make sure both sites are valid and that the user has access to edit them.
	 */
	function run_error_checks()
	{
			$source_site = new entity($this->form->get_value('source_site'));
			if (!(reason_is_entity($source_site, 'site') && reason_check_access_to_site($this->form->get_value('source_site')) && reason_check_privs('edit')))
			{
				$this->form->set_error('source_site', 'Invalid site or insufficient access.');
			}
		
			$destination_site = new entity($this->form->get_value('destination_site'));
			if (!(reason_is_entity($destination_site, 'site') && reason_check_access_to_site($this->form->get_value('destination_site')) && reason_check_privs('edit')))
			{
				$this->form->set_error('destination_site', 'Invalid site or insufficient access.');
			}
			
			if ($source_site === $destination_site)
			{
				$this->form->set_error('destination_site', 'Source and destination must be different sites');
			}
	}
	
	function process_copy()
	{
		$this->test_mode = $this->form->get_value('test_mode');
		$this->source_site = $this->form->get_value('source_site');
		$this->destination_site = $this->form->get_value('destination_site');
		$home_page_handling = $this->form->get_value('home_page_handling');
		
		$source_root = root_finder($this->source_site);
		$destination_root = root_finder($this->destination_site);
		
		echo '<ul>';
		
		if ($destination_root)
		{
			// Delete the destination home page and start copying from the source root.
			if ($home_page_handling === 'delete')
			{
				if (!$this->test_mode) reason_expunge_entity($destination_root, $this->user_id);
				$this->copy_page_tree($source_root, null);
			}
			// Attach the children of the source root to the destination home page.
			else if ($home_page_handling === 'replace')
			{
				$source_root_page = new entity($source_root);
				if ($children = $source_root_page->get_right_relationships_info('minisite_page_parent'))
				{
					foreach ($children['minisite_page_parent'] as $child_rel)
					{
						if ($child_rel['entity_a'] === $child_rel['entity_b']) continue;
						$this->copy_page_tree($child_rel['entity_a'], $destination_root, $child_rel['rel_sort_order']);
					}
				}				
			}
			// Attach the source root as a child of the destination root.
			else if ($home_page_handling === 'attach')
			{
				$this->copy_page_tree($source_root, $destination_root);
			}
		}
		else
		{
			$this->copy_page_tree($source_root, null);
		}
		
		echo '</ul>';
		if (!$this->test_mode)
		{
			echo '<p>Updating rewrites...</p>';
			$url_mgr = new url_manager($this->destination_site);
			$url_mgr->update_rewrites();

			$this->form->show_form = false;

			echo '<p>Copy complete.</p>';
		}
	}

	/**
	 * This method accepts a page ID and recursively copies that page and all of its children into
	 * a new site, preserving the sort order. Call it with a site's root page, and all of the pages
	 * will be copied.
	 * 
	 * @param int $source_root_id The ID of the page to be copied
	 * @param int $destination_parent_id The ID of the site the page is being copied to
	 * @param int $rel_sort An optional sort order for the page
	 */
	function copy_page_tree($source_root_id, $destination_parent_id = null, $rel_sort = 0)
	{
		$source_root = new entity($source_root_id);
		echo '<li>' . $source_root->get_value('name');
		
		$overrides = array('unique_id' => '');
		// If there's no URL fragment (meaning it's a page tree root) but we're attaching it to a 
		// parent page, we need to give it a fragment.
		if ($destination_parent_id && !$source_root->get_value('url_fragment'))
			$overrides['url_fragment'] = 'copied_root';
		
		if (!$this->test_mode)
		{
			$destination_root_id = duplicate_entity( $source_root->id(), false, true, $overrides, $this->destination_site );

			// Attach this page to the appropriate parent
			if ($destination_parent_id)
			{
				create_relationship( $destination_root_id, $destination_parent_id, relationship_id_of('minisite_page_parent'), array('rel_sort_order'=>$rel_sort));
			}
			// Or create a root node
			else
			{
				create_relationship( $destination_root_id, $destination_root_id, relationship_id_of('minisite_page_parent'));
			}
		}
		else
		{
			$destination_root_id = 0;
		}
		
		$this->copy_related_entities($source_root, $destination_root_id);
		
		// Find the children of this page and recurse down.
		if ($children = $source_root->get_right_relationships_info('minisite_page_parent'))
		{
			echo '<ul>';
			foreach ($children['minisite_page_parent'] as $child_rel)
			{
				if ($child_rel['entity_a'] === $child_rel['entity_b']) continue;
				$this->copy_page_tree($child_rel['entity_a'], $destination_root_id, $child_rel['rel_sort_order']);
			}
			echo '</ul>';
		}
		echo '</li>';
	}
	
	/**
	 * This method provides a mechanism to bring along certain related entities with a copied page.
	 * What is copied is based on the relationship names found in $this->allowed_related.
	 * 
	 * @param object $source_page
	 * @param int $destination_page_id
	 */
	function copy_related_entities($source_page, $destination_page_id)
	{
		$overrides = array('unique_id' => '');
		
		if ($rels = $source_page->get_left_relationships_info())
		{
			foreach ($this->allowed_related as $rel_type)
			{
				if (isset($rels[$rel_type]))
				{
					echo '<ul>';
					// @todo list the entities
					echo '<li>'.$rel_type.': '.count($rels[$rel_type]).'</li>';
					foreach ($rels[$rel_type] as $rel)
					{
						if (!$this->test_mode)
						{
							// If we haven't already duplicated this entity, make a copy on the new site
							if (!isset($this->copied_ids[$rel['entity_b']]))
							{
								$entity_id = duplicate_entity( $rel['entity_b'], false, true, $overrides, $this->destination_site );
								$this->copied_ids[$rel['entity_b']] = $entity_id;
							}
							// Relate the new entity to the destination page
							create_relationship( $destination_page_id, $this->copied_ids[$rel['entity_b']], relationship_id_of($rel_type), array('rel_sort_order'=>$rel['rel_sort_order']));
						}
					}
					echo '</ul>';
				}
			}
		}
	}
}