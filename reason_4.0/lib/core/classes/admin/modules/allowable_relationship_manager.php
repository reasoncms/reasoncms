<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once('classes/user.php');
	reason_include_once('function_libraries/util.php');
	include_once (CARL_UTIL_INC .'db/table_admin.php');

	/**
	 * Allowable Relationship Manager
	 *
	 * A Reason module that allows administrators with access to the master admin site to handle the creation, deletion, and editing of allowable relationships
	 *
	 * @author Nathan White
	 */
	 
	class AllowableRelationshipManagerModule extends DefaultModule// {{{
	{
		var $authenticated;
		
		function AllowableRelationshipManagerModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
			$this->admin_page->show['leftbar'] = false;
		}
		
		function init()
		{
			parent::init();
			$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
			$this->admin_page->title = 'Allowable Relationship Manager';
			
			if ($this->authenticate())
			{
				$disco_admin = new DiscoAllowableRelationshipManager;
				$this->table_admin = new TableAdmin();
 				$this->table_admin->set_allow_row_delete(true);
 				$this->table_admin->set_allow_edit(true);
 				$this->table_admin->set_allow_view(false);
 				$this->table_admin->set_allow_new(true);
 				$this->table_admin->set_admin_form($disco_admin);
				$this->table_admin->init(REASON_DB, 'allowable_relationship');
			}
		}
		
		function run()
		{
			$master_admin_link = carl_construct_link(array('site_id' => id_of('master_admin')));
			$link[] = '<a href="'.$master_admin_link.'">Return to master admin</a>';
			if ($this->authenticate() && $this->table_admin->get_table_row_action())
			{
				$url2 = carl_make_link($this->table_admin->get_menu_links_base_with_filters());
				$link[] = '<a href="'.$url2.'">Show allowable relationships list</a>';
			}
			
			echo implode(' | ', $link);
			
			if (!$this->authenticate())
			{
				echo '<h3>You do not have the proper privileges to use this module</h3>';
			}
			else
			{
				$this->table_admin->run();
			}
		}
		
		/**
		 * Ensure that the user is an admin with access to the master admin site.
		 */
		function authenticate()
		{
			if (!isset($this->authenticated))
			{
				if(!empty($this->admin_page->user_id))
				{
					$user_id = $this->admin_page->user_id;
					$user = new entity($user_id);
					$user_netid = $user->get_value('name');
				}
				else
				{
					$user_netid = reason_require_authentication();
					$user_id = get_user_id($user_netid);
				}
				if( reason_user_has_privs( $user_id, 'manage_allowable_relationships' ) )
				{
					$user_man = new User();
					$this->authenticated = $user_man->is_site_user($user_netid, id_of('master_admin'));
				}
			}
			return $this->authenticated;
		}
	}
	
	/**
	 * Custom display and handling of actions for the allowable relationship manager
	 * @author Nathan White
	 */
	class DiscoAllowableRelationshipManager extends DiscoDefaultAdmin
	{
		var $fields_to_show = array('id', 'relationship_a', 'relationship_b', 'name', 'required', 'connections', 'directionality', 'meta_type','meta_availability');
		var $field_display_names = array('relationship_a' => 'Left', 
										'relationship_b' => 'Right', 
										'id' => 'ID', 
										'required' => "Req'd", 
										'name' => 'Name', 
										'connections' => 'Connections', 
										'directionality' => 'Directionality',
										'meta_type' => 'Metadata Type',
										'meta_availability' => 'Metadata Availability');
										 
		var $form_transforms_data = true;
		
		function pre_show_form_edit()
		{
			echo '<h3>Editing Allowable Relationship</h3>';
		}
		
		function pre_show_form_delete()
		{
			echo '<h3>Deleting Allowable Relationship</h3>';
		}
		
		function on_every_time_default()
		{	
			$this->change_element_type('id','cloaked');
			$connection_description = '<h4>Connection Definitions</h4><dl>';
			$connection_description .= '<dt>Many to Many</dt><dd>Both entities A and B may have more than one relationship of this type</dd>';
			$connection_description .= '<dt>Many to One</dt><dd>Entity B may not have more than one relationship of this type, but entity A may be related to multiple entities B.</dd>';
			$connection_description .= '<dt>One to Many</dt><dd>Entity A may not have more than one relationship of this type, but entity B may be related to multiple entities A.</dd></dl>';
			$this->add_required( 'relationship_a' );
			$this->set_comments('relationship_a',form_comment('The type on the left side of the relationships created under this alrel. Note that the A side item is often considered the primary item (for example, B side items may be sortable within the contex of the A side item, but not vice versa.)'));
			$this->add_required( 'relationship_b' );
			$this->set_comments('relationship_b',form_comment('The type on the right side of the relationships created under this alrel. Note that the B side item is often considered the secondary item  (see note above.)'));
			$this->add_required( 'name' );
			$this->set_comments('name',form_comment('A unique name for the allowable relationship. This field may only contain letters, numbers, and underscores.'));
			$this->add_required( 'required' );
			$this->set_comments('required',form_comment('Setting Required to "yes" will force users to select at least one B entity across this allowable relationship when they create an A entity.'));
			$this->add_required( 'connections' );
			$this->set_comments('connections', form_comment($connection_description));
			$this->set_comments('display_name',form_comment('This is the text that will be used on the A side as the link to manage relationships of this type. (For example, if the relationship is page=>image, this text would be visible in the context of the page.) This text is also used as a heading above the list of B items when previewing an A item.'));
			$this->set_comments('display_name_reverse_direction',form_comment('This is the text that will be used on the B side as the link to manage relationships of this type. (For example, if the relationship is page=>image, this text would be visible in the context of the image.)'));
			$this->set_comments('custom_associator',form_comment('Entering text into this field will <strong>turn off</strong> Reason\'s automatic relationship features. Only enter text here if you have built some other method of managing the relationship.'));
			$this->set_comments('description',form_comment('More info about the relationship. Just used to help others understand what the allowable relationship is for.'));
			$this->add_required( 'directionality' );
			$this->set_comments('directionality',form_comment('Unidirectional relationships can only be created from the A side; Bidirectional relationships may be created from either the A side or the B side. Before you set a allowable relationship to be bidirectional you should audit the code to make sure that all entity selectors that select across the relationship are given the current site environment.'));
			$this->set_comments('description_reverse_direction',form_comment('The text that is used as a heading above the list of A items when previewing a B item.'));
			$this->add_required( 'is_sortable' );
			$this->set_comments('is_sortable',form_comment('Answering "yes" will enable relationship-based sorting across relationships of this type. You will still need to alter the appropriate code to pay attention to the relationship sort order field before this has any effect on the front end.'));
			$this->set_comments('meta_type',form_comment('The metadata entity type that can be associated with this type of relationship'));
			$this->set_comments('meta_availability',form_comment('If "global", metadata is available on this relationship wherever it occurs; if "by_site", only sites that have been assigned this metadata type will be able to use it.'));
			
			if (reason_relationship_names_are_unique())
			{
				$this->add_required( 'type' );
				$this->change_element_type('type', 'protected');
				$this->set_value('type', 'association');
			}
			
			// get types
			$es = new entity_selector();
			$es->add_type( id_of( 'type' ) );
			$tmp = $es->run_one();
			// format into a usable form
			$metadata_types = Array();
			foreach( $tmp AS $ent ) {
				if ($ent->get_value("variety") == "relationship_meta") {
					$metadata_types[ $ent->id() ] = $ent->get_value( 'name' );
				} else {
					$types[ $ent->id() ] = $ent->get_value( 'name' );
				}
			}
			$this->change_element_type( 'relationship_a','select',array('options'=>$types) );
			$this->change_element_type( 'relationship_b','select',array('options'=>$types) );
			$this->change_element_type( 'meta_type','select',array('options'=>$metadata_types) );

			$this->set_order(array('name','description','relationship_a','relationship_b','connections','directionality','required','is_sortable','display_name','display_name_reverse_direction','description_reverse_direction','type','custom_associator', 'meta_type','meta_availability'));
			parent::on_every_time_default();
		}
		
		/**
		 * Sets up sensible values in parallel with create_allowable_relationship method
		 */
		function on_every_time_new()
		{
			if (!$this->get_value('connections')) $this->set_value('connections', 'many_to_many');
			if (!$this->get_value('directionality')) $this->set_value('directionality', 'unidirectional');
			if (!$this->get_value('is_sortable')) $this->set_value('is_sortable', 'no');
			if (!$this->get_value('required')) $this->set_value('required', 'no');
			$this->on_every_time_default();
		}

		function transform_data(&$data_row)
		{
			static $types;
			if (empty($types))
			{
				$es = new entity_selector();
				$es->add_type(id_of('type'));
				$es->limit_tables();
				$es->limit_fields('name');
				$result = $es->run_one();
				foreach ($result as $type)
				{
					$types[$type->id()] = $type->get_value('name') . ' ('.$type->id().')';
				}
			}
			$data_row['relationship_a'] = isset($types[$data_row['relationship_a']]) ? $types[$data_row['relationship_a']] : 'No Type';
			$data_row['relationship_b'] = isset($types[$data_row['relationship_b']]) ? $types[$data_row['relationship_b']] : 'No Type';
			$data_row['meta_type'] = isset($types[$data_row['meta_type']]) ? $types[$data_row['meta_type']] : 'No Type';
			
			if (reason_relationship_names_are_unique())
			{
				if ($data_row['type'] != "association")
				{
					$data_row = false;
				}
			}
		}
		
		function run_error_checks_new()
		{
			$this->run_validation_checks();
			parent::run_error_checks_edit();
		}
		
		function run_error_checks_edit()
		{
			$this->run_validation_checks();
			parent::run_error_checks_edit();
		}
		
		function run_validation_checks()
		{
			if (!$this->dir_validation()) $this->set_error('directionality', 'One To Many and Many to One relationships cannot be bidirectional');
			if (!$this->name_validation()) $this->set_error('name', 'Names must contain only letters, numbers, and/or underscores.  Please make sure the name doesn\'t contain any other characters.');
			if (reason_relationship_names_are_unique() && !$this->reserved_names_validation()) $this->set_error('name', 'You cannot use "owns", "borrows", "archive", or "association" as an allowable relationship name.');
			if (reason_relationship_names_are_unique() && !$this->name_uniqueness_check()) $this->set_error('name', 'The name of the allowable relationship must be unique.');
		}
		
		function where_to()
		{
			reason_refresh_relationship_names(); // lets make sure this reflects changes.
			return carl_make_redirect(array('table_row_action' => '', 'table_action_id' => ''));
		}
		
		function dir_validation()
		{
			if( $this->get_value( 'directionality' ) == 'bidirectional' AND
			    ($this->get_value( 'connections' ) == 'one_to_many'  ||
			    $this->get_value( 'connections' ) == 'many_to_one' ))
			{
				return false;
			}
			return true;
		}
		
		function name_validation()
		{
			if(!preg_match( "|^[0-9a-z_]*$|i" , $this->get_value('name') ) )
			{
				return false;
			}
			return true;
		}
		
		function reserved_names_validation()
		{
			$name = $this->get_value('name');
			if (in_array($name, array('owns','borrows','archive','association'))) return false;
			return true;
		}
		
		function name_uniqueness_check()
		{
			if (reason_relationship_name_exists($this->get_value('name')) && relationship_id_of($this->get_value('name')) != $this->get_value('id') ) return false;
			else return true;
		}
	}