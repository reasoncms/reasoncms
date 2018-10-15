<?php
/**
 * @package reason
 * @subpackage admin
 */
 
/**
 * Include the default module and other needed utilities
 */
reason_include_once('classes/admin/modules/default.php');
include_once( DISCO_INC . 'disco.php' );
reason_include_once( 'content_listers/tree.php3' );
reason_include_once( 'minisite_templates/nav_classes/default.php' );

/**
 * An administrative module that displays basic type-by-type statistics about usage by type
 * 
 * @author Matt Ryan
 */
class GroupUsageModule extends DefaultModule// {{{
{
	protected $form;
	function GroupUsageModule( &$page )
	{
		$this->admin_page =& $page;
	}

	function init()
	{
		parent::init();
		$this->admin_page->title = 'Group Usage';
	}
	function get_module_info()
	{
		return '<p>This module produces a report of how groups are used in this instance of Reason.</p>';
	}
	function run()
	{
		echo $this->get_module_info();
		$form = $this->get_form();
		
		$form->run();
		
		
		
		if($form->successfully_submitted())
		{
			$groups = $this->get_groups($form);
			echo '<p>'.count($groups) . ' groups total</p>';
			$logins = array();
			$just_logins = array();
			$subsets = array();
			$audiences = array();
			$audience_types = array();
			$usernames = array();
			$ldap_filters = array();
			$ldap_groups = array();
			$kinds = array();
			$direct_pages = array();
			$assets = array();
			$form_submits = array();
			$form_data = array();
			foreach($groups as $id=> $group)
			{
				$num_kinds = 0;
				if('true' == $group->get_value('require_authentication'))
				{
					$logins[$id] = $group;
					if('true' == $group->get_value('limit_authorization'))
					{
						$subsets[$id] = $group;
						$audiences_on_this_group = $group->get_left_relationship(relationship_id_of('group_to_audience'));
						if(!empty($audiences_on_this_group))
						{
							$audiences[$id] = $group;
							$num_kinds++;
							foreach($audiences_on_this_group as $audience_on_this_group)
							{
								$audience_types[$audience_on_this_group->get_value('name')][] = $group;
							}
						}
						if($group->get_value('authorized_usernames'))
						{
							$usernames[$id] = $group;
							$num_kinds++;
						}
						if($group->get_value('arbitrary_ldap_query'))
						{
							$ldap_filters[$id] = $group;
							$num_kinds++;
						}
						if($group->get_value('ldap_group_filter') || $group->get_value('ldap_group_member_fields'))
						{
							$ldap_groups[$id] = $group;
							$num_kinds++;
						}
						$kinds[$num_kinds][$id] = $group;
					}
					else
					{
						$just_logins[$id] = $group;
					}
					$pages = $group->get_right_relationship(relationship_id_of('page_to_access_group'));
					if(!empty($pages))
					{
						$direct_pages += $pages;
					}
					$limited_assets = $group->get_right_relationship(relationship_id_of('asset_access_permissions_to_group'));
					if(!empty($limited_assets))
					{
						$assets += $limited_assets;
					}
					$limited_form_submits = $group->get_right_relationship(relationship_id_of('form_to_authorized_viewing_group'));
					if(!empty($limited_form_submits))
					{
						$form_submits += $limited_form_submits;
					}
					$limited_form_datas = $group->get_right_relationship(relationship_id_of('form_to_authorized_results_group'));
					if(!empty($limited_form_datas))
					{
						$form_data += $limited_form_datas;
					}
				}
			}
			
			echo '<p>'.count($logins) . ' require login</p>';
			echo '<p>'.count($just_logins).' only require login</p>';
			echo '<p>'.count($subsets) . ' are more specific than "anyone who can log in"</p>';
			echo '<p>'.count($audiences) . ' include at least one broad affiliation group (e.g. faculty, alumni, students)</p>';
			echo '<ul>';
			foreach($audience_types as $audience_name => $aud_groups)
			{
				echo '<li>' . count($aud_groups) . ' include all ' . $audience_name . '</li>';
			}
			echo '</ul>';
			echo '<p>'.count($usernames) . ' specify a list of usernames that are included</p>';
			echo '<p>'.count($ldap_filters) . ' specify a dynamic set based on an LDAP filter</p>';
			echo '<p>'.count($ldap_groups) . ' specify a dynamic set based on an LDAP group</p>';
			echo '<ul>';
			ksort($kinds);
			foreach($kinds as $num => $kinds_groups)
			{
				echo '<li>' . count($kinds_groups) . ' use ' . $num . ' kind(s) of inclusion</li>';
			}
			echo '</ul>';
			
		// Gah, for some reason the other way is not giving us accurate numbers
			$es = new entity_selector(array_keys($this->get_sites($form)));
			$es->add_type(id_of('minisite_page'));
			$es->add_left_relationship_field('page_to_access_group', 'entity', 'id', 'group_id');
			$pages = $es->run_one();
		
			echo '<p>' . count($pages) . ' pages are directly access controlled (not counting children pages)</p>';
		
			$descendants = array();
			foreach($pages as $page)
			{
				$descendants = array_merge($descendants, $this->get_descendants($page));
			}
			echo '<p>' . count($descendants) . ' pages total are access controlled</p>';
		
			echo '<p>' . count($assets) . ' assets are directly access controlled (not counting security by obscurity by placing link on access-controlled page)</p>';
			echo '<p>' . count($form_submits) . ' forms are directly access controlled for submission (not counting page-level access control)</p>';
			echo '<p>' . count($form_data) . ' forms are directly access controlled for data viewing beyond site admin access (not counting page-level access control)</p>';
		}
	}
	
	function get_groups($form)
	{
		$es = new entity_selector(array_keys($this->get_sites($form)));
		$es->add_type(id_of('group_type'));
		return $es->run_one();
	}
	
	function get_sites($form)
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('site'));
		$es->add_relation('site_state = "Live"');
		if($exclude_str = $form->get_value('exclude_site_unique_names'))
		{
			$excludes = explode(',', str_replace(' ','',$exclude_str));
			$es->add_relation('unique_name NOT IN ("'.implode('","', $excludes).'")');
		}
		return $es->run_one();
	}
	
	function get_form()
	{
		if(!isset($this->form))
		{
			$d = new Disco();
			$d->add_element('exclude_site_unique_names');
			$d->set_actions(array('Run'));
			$this->form = $d;
		}
		return $this->form;
	}
	
	function get_descendants($page) {
		$tree = $this->get_tree($page);
		return $tree->get_descendants($page->id());
	}
	
	function get_tree($page)
	{
		static $trees = array();
		$site = $page->get_owner();
		if(isset($trees[$site->id()]))
		{
			return $trees[$site->id()];
		}
		$trees[$site->id()] = new MinisiteNavigation();
		$trees[$site->id()]->site_info = $site;
		$trees[$site->id()]->init( $site->id(), id_of('minisite_page') );
		return $trees[$site->id()];
	}
}
