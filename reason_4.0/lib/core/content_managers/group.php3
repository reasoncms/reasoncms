<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'GroupManager';
	
	/**
	 * A content manager for groups
	 */
	class GroupManager extends ContentManager
	{
		var $triggers_for_limit_authorization_field = array(
					'audiences',
					//'course_identifier_strings',
					'authorized_usernames',
					'arbitrary_ldap_query',
					'ldap_group_filter',
				);
		function init_head_items()
		{
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'content_managers/group.js');
		}

		function pre_show_form()
		{
			parent::pre_show_form();
			if($this->get_value('state') == 'Live')
			{
					$link = $this->admin_page->make_link( array( 'cur_module' => 'GroupTester' ));
				echo '<p class="testGroup"><a href="'.$link.'">Test this group</a></p>';
			}
		}
		function on_every_time()
		{
			$this->_no_tidy[] = 'ldap_group_filter';
			$this->_no_tidy[] = 'ldap_group_member_fields';
			$this->_no_tidy[] = 'arbitrary_ldap_query';
		}
		function alter_data() // {{{
		{
			$this->change_element_type('group_has_members', 'hidden');
			if(!$this->get_value('group_has_members'))
			{
				$this->set_value('group_has_members','true');
			}
			$this->change_element_type( 'require_authentication','radio_no_sort',array( 'options' => array( 'false' => 'Everyone<br />(anybody on the web, no login required)','true' => 'Just '.SHORT_ORGANIZATION_NAME.' people<br /><span class="tinytext">(those with a '.SHORT_ORGANIZATION_NAME.'-supplied username and password)</span>' ) ) );
			$this->set_display_name( 'require_authentication', ' ' );
			//$this->set_display_name( 'require_authentication', 'Require '.SHORT_ORGANIZATION_NAME.' Login?' );
			$this->change_element_type( 'limit_authorization','radio_no_sort',array( 'options' => array( 'false' => 'All '.SHORT_ORGANIZATION_NAME.' people', 'true' => 'A subset' ) ) );
			//$this->change_element_type( 'limit_authorization', 'hidden' );
			$this->set_display_name( 'limit_authorization',' ' );
			$this->set_display_name( 'authorized_usernames','Specific Authorized Usernames' );
			
			$this->set_display_name( 'authorized_usernames','These specific people' );
			$this->set_comments( 'authorized_usernames',form_comment( 'Separate usernames with commas, like this: mryan, gibbsm, jlawrenc' ) );
			$this->set_display_name( 'arbitrary_ldap_query','People who match this LDAP filter' );
			if(!reason_user_has_privs($this->admin_page->user_id, 'edit_raw_ldap_filters'))
			{
				$fields = array('arbitrary_ldap_query','ldap_group_filter','ldap_group_member_fields');
				foreach($fields as $field)
				{
					if($this->get_value($field))
					{
						$this->change_element_type($field, 'solidText');
					}
					else
					{
						$this->change_element_type($field, 'hidden');
					}
				}
			}
			else
			{
				$this->add_comments( 'arbitrary_ldap_query',form_comment( 'You should only use this if you know what you are doing.' ) );
				$this->set_display_name( 'ldap_group_filter','People who are part of an LDAP group that matches this LDAP filter' );
				$this->add_comments( 'ldap_group_filter',form_comment( 'You should only use this if you know what you are doing.' ) );
				$this->set_display_name( 'ldap_group_member_fields','Group membership LDAP attributes' );
				$this->add_comments( 'ldap_group_member_fields',form_comment( 'Redefine what LDAP attributes to use when determining group membership. Use comma-separated attribute names. If left empty Reason will use the attribute ds_members.' ) );
			}
			$this->add_element('authentication_comment', 'comment', array('text'=>'<h3>Broadly define the group</h3><p>Does it include everyone in the world, or just '.SHORT_ORGANIZATION_NAME.' people?</p>'));
			$this->add_element('limit_comment', 'comment', array('text'=>'<h3>Focusing the group</h3>If you answered "Just '.SHORT_ORGANIZATION_NAME.' people" above: is this the group of all '.SHORT_ORGANIZATION_NAME.' people or just a subset?</p>'));
			$this->add_element('audiences_comment', 'comment', array('text'=>'<h3>The details</h3><p>If you answered "subset" above, please indicate the sets of '.SHORT_ORGANIZATION_NAME.' people that make up this group. These fields are additive -- if you enter "Alumni," "Students," and members of a certain class, the group will include <em>all</em> of those people. Please note that if <strong>all</strong> members of checked audiences will be considered part of this group.'));
			$this->add_relationship_element('audiences', id_of('audience_type'), 
relationship_id_of('group_to_audience'),'right','checkbox',REASON_USES_DISTRIBUTED_AUDIENCE_MODEL,'sortable.sort_order ASC');
			$old_audience_fields = array('prospective_students','new_students','students','faculty','staff','alumni','families','public');
			foreach($old_audience_fields as $field)
			{
				if($this->_is_element($field))
					$this->change_element_type($field,'hidden');
			}
			
			$this->add_element('name_comment', 'comment', array('text'=>'<h3>Please give this group a name:</h3>'));
			$this->set_comments( 'name',form_comment( 'Please choose somthing descriptive, like: "Alumni, Prosective Students, and Classics 237"' ) );
			$this->set_order( array(
					'authentication_comment',
					'require_authentication', 
					'limit_comment',
					'limit_authorization',
					'audiences_comment',
					'audiences',
					'authorized_usernames',
					'arbitrary_ldap_query',
					'ldap_group_filter',
					'ldap_group_member_fields',
					'name_comment',
					'name',
					'unique_name'));
		} // }}}
		function run_error_checks()
		{
			parent::run_error_checks();
			include_once(CARL_UTIL_INC.'dir_service/directory.php');
			$ds = new directory_service();
			if($this->get_value('arbitrary_ldap_query'))
			{
				if(!$ds->validate_search_filter($this->get_value('arbitrary_ldap_query')))
				{
					$this->set_error('arbitrary_ldap_query','The LDAP filter has a syntax problem. Please fix.');
				}
			}
			if($this->get_value('ldap_group_filter'))
			{
				if(!$ds->validate_search_filter($this->get_value('ldap_group_filter')))
				{
					$this->set_error('ldap_group_filter','The LDAP group filter has a syntax problem. Please fix.');
				}
			}
			else
			{
				$this->set_value('ldap_group_member_fields','');
			}
		}
	}


?>
