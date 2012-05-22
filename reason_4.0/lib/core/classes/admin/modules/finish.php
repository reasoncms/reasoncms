<?php
/**
 * @package reason
 * @subpackage admin
 */
 
/**
 * Include the default module and other needed utilities
 */
reason_include_once('classes/admin/modules/default.php');
	
/**
 * Administrative module that handles final error checks and marks new entities as live
 */
class FinishModule extends DefaultModule // {{{
{
	function FinishModule( &$page ) // {{{
	{
		$this->admin_page =& $page;
	} // }}}		
	function init() // {{{
	{
		if (!$this->admin_page->id) return false;
		
		//these next few lines check the entity to make sure it has everything it needs
		$this->load_content_manager();
		$this->check_entity_values(); 
		$this->get_required_relationships();
		if( !empty( $this->req_rels ) )
			$this->check_required_relationships();
			
		
		
		/* the new finish stuff */
		// new_entity stuff
		// figure out if the entity is new and store that so we can change the data in the database but still know what's going on
		$temp = new entity( $this->admin_page->id,false );
		if( $temp->get_value( 'new' ) )
			$this->new_entity = true;
		else
			$this->new_entity = false;
		
		// when finishing an entity, we want to ensure that it is live and not new (unless it is a page)
		if ( ($this->admin_page->type_id != id_of('minisite_page')) && ($temp->get_value('state') == 'Pending') && reason_user_has_privs($this->admin_page->user_id, 'publish') )
		{
			$update_values['state'] = 'Live';
			if(!empty($this->disco_item)) $this->disco_item->set_value('state', "Live");
		}
		if ($temp->get_value('new') != '0')
		{
			$update_values['new'] = 0;
			if(!empty($this->disco_item)) $this->disco_item->set_value('new_entity', 0);
		}
		if (!empty($update_values))
		{
			reason_update_entity($this->admin_page->id, $this->admin_page->user_id, $update_values, false); // archive, yes?
		}
		
		$original = new entity( $this->admin_page->id,false );
		$original->get_values();

		// get archive relationship id
		$q = 'SELECT id FROM allowable_relationship WHERE name LIKE "%archive%" AND relationship_a = '.$this->admin_page->type_id.' AND relationship_b = '.$this->admin_page->type_id;
		$r = db_query( $q, 'Unable to get archive relationship.' );
		$row = mysql_fetch_array( $r, MYSQL_ASSOC );
		$this->rel_id = $row['id'];

		// get archives
		$es = new entity_selector( $this->admin_page->site_id );
		$es->add_type( $this->admin_page->type_id );
		$es->add_right_relationship( $this->admin_page->id, $this->rel_id );
		$es->add_relation('last_modified = "'.$original->get_value( 'last_modified' ).'"');
		$es->set_num(1);
		$similar_archived = $es->run_one('','Archived');

		// if the entity has in fact been changed, actually create the relationship
		if(empty($similar_archived))
		{
			$archived_id = duplicate_entity( $original, false, true, array( 'state' => 'Archived' ) );
			create_relationship( $this->admin_page->id, $archived_id, $this->rel_id);
		}

		// DETERMINE WHERE TO GO

		if( !empty( $this->admin_page->request[ CM_VAR_PREFIX.'type_id' ] ) )
		{
			// this code block is intended to associate a new entity with the context entity upon finish - it once created backwards relationships IF we reached an 
			// entity with get_value('new') = 1 from the reverse associator. This needs fixin. We do so by running this section of code only in the case where
			// the "old" module was the associator (the only module that allows the creation of new entities than need a relationship back to the a side entity
			// whose context is relevant.
			if( $this->new_entity && ($this->admin_page->request[ CM_VAR_PREFIX.'cur_module' ] == 'Associator') )
			{
				$rel_info = reason_get_allowable_relationship_info( $this->admin_page->request[ CM_VAR_PREFIX.'rel_id' ] );
				$entity_a = new entity($this->admin_page->request[ CM_VAR_PREFIX.'id' ]);
				$entity_b = new entity($this->admin_page->request[ 'id' ]);
				
				// lets do a bit of additional sanity checking.
				if ( ($rel_info[ 'relationship_a'] == $entity_a->get_value('type')) && ($rel_info[ 'relationship_b'] == $entity_b->get_value('type')) )
				{
					if( $rel_info[ 'connections' ] == 'one_to_many' ) $this->delete_existing_relationships();
					create_relationship( $entity_a->id(), $entity_b->id(), $rel_info['id'] );
				}
			}
			$old_vars = array();	
			foreach( $this->admin_page->request AS $key => $val )
				if( substr( $key, 0, strlen( CM_VAR_PREFIX ) ) == CM_VAR_PREFIX )
				{
					$old_vars[ substr( $key, strlen( CM_VAR_PREFIX ) ) ] = $val;
					$old_vars[ $key ] = '';
				}
			foreach( $this->admin_page->default_args AS $arg )
				if( !isset( $old_vars[ $arg ] ) )
					$old_vars[ $arg ] = '';
			$link = $this->admin_page->make_link( $old_vars );
		}
		elseif( !empty( $this->admin_page->request[ 'next_entity' ] ) )
		{
			$link = $this->admin_page->make_link( array('cur_module'=>'Editor', 'id' => $this->admin_page->request['next_entity']) );
		}
		else
		{
			$link = $this->admin_page->make_link( array( 'id' => '',/*'new_entity' => '',*/'site_id' => $this->admin_page->site_id , 'type_id' => $this->admin_page->type_id , 'cur_module' => 'Lister' ) );
		}			
		// before redirecting, check to see if there are any custom finish actions associated with this type.
		// the entity_type variable is declared earlier in the check_entity_values method.
		
		//Run any custom finish actions specified in the content manager
		if(!empty($this->disco_item)) $this->disco_item->run_custom_finish_actions( $this->new_entity );

		if( $this->entity_type->get_value( 'finish_actions' ) )
		{
			$finish_actions_filename = $this->entity_type->get_value( 'finish_actions' );
		}
		else
		{
			$finish_actions_filename = 'default.php';
		}
		reason_include_once ('finish_actions/'.$finish_actions_filename );
		$finish_action_class_name = $GLOBALS['_finish_action_classes'][$finish_actions_filename];
		$fac = new $finish_action_class_name();
		$vars = array( 'site_id'=>$this->admin_page->site_id,
						'type_id'=>$this->admin_page->type_id,
						'id'=>$this->admin_page->id,
						'user_id'=>$this->admin_page->user_id,
					);
		$fac->init($vars);
		$fac->run();
		
		header( 'Location: '.unhtmlentities( $link ) );
		die();
	} // }}}
	function load_content_manager()  // {{{
	{
		/*
		 * load_content_manager(): finds the appropriate content manager for the entity
		 * 	and does everything up to the error checks
		 * 
		 */
		reason_include_once( 'content_managers/default.php3' );
		$content_handler = $GLOBALS[ '_content_manager_class_names' ][ 'default.php3' ];
		$type = new entity( $this->admin_page->type_id );

		// set up a data member that init can get to after this method is called
		$this->entity_type = $type;
		if ( $type->get_value( 'custom_content_handler' ) )
		{
			$include_file = $type->get_value( 'custom_content_handler' );
			if( !preg_match( '/(php|php3)$/' , $include_file ) )
				$include_file .= '.php3';
			$include_path = 'content_managers/'.$include_file;
			reason_include_once( $include_path );
			$content_handler = $GLOBALS[ '_content_manager_class_names' ][ $include_file ];
		}
		$this->disco_item = new $content_handler;
		$this->disco_item->admin_page =& $this->admin_page;
		$this->disco_item->set_head_items( $this->head_items );
		$this->disco_item->prep_for_run( $this->admin_page->site_id, $this->admin_page->type_id, $this->admin_page->id, $this->admin_page->user_id );
		$this->disco_item->init();

		$this->disco_item->on_every_time();

		$this->disco_item->pre_error_check_actions();
	} // }}}
	function delete_existing_relationships() // {{{
	{
		$q = 'DELETE FROM relationship WHERE entity_a = ' . $this->admin_page->request[ CM_VAR_PREFIX.'id'] . 
			 ' AND type = ' . $this->admin_page->request[ CM_VAR_PREFIX.'rel_id'];
		$r = db_query( $q , 'Error deleting existing relationships in FinishModule::delete_existing_relationships()' );
	} // }}}
	function get_required_relationships() // {{{
	{
		//getquery
		$d = new DBSelector;

		$d->add_table('ar','allowable_relationship' );
	
		$d->add_table( 'allowable_relationship' );
		$d->add_table( 'relationship' );
		$d->add_table( 'entity' );
		
		$d->add_relation( 'allowable_relationship.name = "site_to_type"' );
		$d->add_relation( 'allowable_relationship.id = relationship.type' );
		$d->add_relation( 'relationship.entity_a = '.$this->admin_page->site_id );
		$d->add_relation( 'relationship.entity_b = ar.relationship_b' );
		$d->add_relation( 'entity.id = ar.relationship_b' );
		
		$d->add_field( 'entity' , 'id' , 'e_id' );
		$d->add_field( 'entity' , 'name' , 'e_name' );
		$d->add_field('ar','*');

		$d->add_relation( 'ar.relationship_a = ' . $this->admin_page->type_id );
		if (reason_relationship_names_are_unique())
		{
			$d->add_relation('ar.type = "association"');
		}
		else
		{
			$d->add_relation('ar.name != "owns"');
		}
		$d->add_relation( '(ar.custom_associator IS NULL OR ar.custom_associator = "")');
		$d->add_relation( 'ar.required = "yes"' );
		$r = db_query( $d->get_query() , 'Error selecting relationships' );

		$return_me = array();
		while( $row = mysql_fetch_array( $r , MYSQL_ASSOC ) )
			$return_me[ $row[ 'id' ] ] = $row;
		$this->req_rels = $return_me;
	} // }}}
	function check_required_relationships() // {{{
	{
		foreach( $this->req_rels AS $rel )
		{
			$d = new DBSelector;
			
			$d->add_table( 'r' , 'relationship' );
			$d->add_table( 'ar' , 'allowable_relationship' );
			
			$d->add_relation( 'r.type = ar.id' );
			$d->add_relation( 'ar.id = ' . $rel[ 'id' ] );
			$d->add_relation( 'r.entity_a = ' . $this->admin_page->id );

			$r = db_query( $d->get_query() , "Can't do query in FinishModule::check_required_relationships()" );
			if( !( $row = mysql_fetch_array( $r ) ) )
			{
				$link = $this->admin_page->make_link( array( 'cur_module' => 'Associator' , 'rel_id' => $rel[ 'id' ] , 'error_message' => 1 ) );
				header( 'Location: ' . unhtmlentities( $link ) );
				die( '' );
			}
		}
	} // }}}
	function check_entity_values() // {{{
	{
		
		$this->disco_item->_run_all_error_checks();
		if( $this->disco_item->_has_errors() )
		{
			$link = $this->admin_page->make_link( array( 'cur_module' => 'Editor' , 'submitted' => true ) );
			header( 'Location: ' . unhtmlentities( $link ) );
			die( '' );
		}
	} // }}}
	function _user_can_edit_item($user_id, $id)
	{
		$entity = new entity($id);
		return $entity->get_value('state') == 'Pending' ? reason_user_has_privs($user_id,'edit_pending') : reason_user_has_privs($user_id,'edit');
	}
	function run() // {{{
	{
		if (!$this->admin_page->id)
		{
			echo '<p>The entity could not be finished because it does not exist in the database. You may have chosen cancel or delete prior to finishing the entity.</p>';
		}
	} // }}}
} // }}}
?>