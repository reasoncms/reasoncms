<?php
/**
 * A bunch of various disco classes for use in the administrative interface
 * @todo break these up into separate files and perhaps come up with a better organization
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include base disco class and admin actions, which most of these forms use
  */
	include_once( DISCO_INC . 'disco.php' );
	reason_include_once( 'function_libraries/admin_actions.php' );
	
	/**
	 * The admin form that confirms the deletion of an item
	 */
	class deleteDisco extends Disco // {{{
	{
		function set_page( &$page ) // {{{
		{
			$this->admin_page = $page;
		} // }}}
		function pre_show_form() // {{{
		{
			$e = new entity($this->get_value('id'));
			if ($e->get_value('state') == 'Deleted')
			{
				$action = 'expunge';
				$noun = 'expungement';
			}
			else
			{
				$action = 'delete';
				$noun = 'deletion';
			}
			
			$name = $e->get_value("name");
			if (!$name)
				$name = "<i>(untitled)</i>";
			
			echo "<h3>Do you really want to $action $name?</h3>";
			
			if($e->field_has_lock('state') && reason_user_has_privs($this->admin_page->user_id,'manage_locks'))
			{
				echo '<div class="lockNotice"><img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="locked" width="12" height="12" /> Note: '.$noun.' is locked for some users.</div>';
			}
		} // }}}
		function grab_all_page_requests() // {{{
		{
			foreach( $this->admin_page->request AS $request => $value )
			{
				if( !$this->_is_element($request) && ($request != 'submitted'))
#				if( !isset( $this->_elements[ $request ] ) )
				{
					$this->add_element( $request , 'hidden' );
					$this->set_value( $request , $value );
				}
			}
		} // }}}
		function grab_info( $id , &$graph) // {{{
		{
			$this->graph =& $graph;
			$this->add_element( 'id' , 'hidden' );
			$this->set_value( 'id' , $id );
			$this->grab_all_page_requests();

		} // }}}
		function finish() // {{{
		{
			if( $this->chosen_action != 'cancel' )
			{
				$id = $this->get_value( 'id' );
				if( $id )
				{
					$e = new entity( $id );
					if( $e->get_value( 'state' ) == 'Live'  )
					{
						reason_update_entity( $id, $this->admin_page->user_id, array('state' => 'Deleted'), false );
						$type = new entity( $this->admin_page->type_id );
						$post_deleter_filename = $type->get_value( 'custom_post_deleter' );
						if(!(empty($post_deleter_filename)))
						{
							reason_include_once ( 'content_post_deleters/' . $post_deleter_filename );
							$post_deleter_class_name = $GLOBALS['_content_post_deleter_classes'][$post_deleter_filename];
							$pd = new $post_deleter_class_name();
								$vars = array( 'site_id'=>$this->admin_page->site_id,
											   'type_id'=>$this->admin_page->type_id,
							 				   'id'=>$this->admin_page->id,
											   'user_id'=>$this->admin_page->user_id );
							$pd->init($vars, $e);
							$pd->run();
						}
					}
					else
					{
						// grab the state of the item before deleting
						$this->del_entity_state = $e->get_value( 'state' );
						$this->delete_entity();
					}
				}
			}
		} // }}}
		function delete_entity() // {{{
		{
			reason_include_once( 'function_libraries/admin_actions.php' );
			reason_expunge_entity( $this->get_value( 'id' ), $this->admin_page->user_id );
		} // }}}
		function where_to() // {{{
		{
			if( $this->chosen_action != 'cancel' )
			{
				if( !empty($this->del_entity_state) && strtolower($this->del_entity_state) == 'deleted' )
					$link = unhtmlentities($this->admin_page->make_link( array( 'cur_module' => 'Lister' , 'id' => '', 'state' => 'deleted') ) );
				else
					$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Lister' , 'id' => '' ) ) );
			}
			else
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Preview' ) ) );
			return $link;
		} // }}}
	} // }}}

	/**
	 * The admin form that handles associating entities
	 */
	class doAssociateDisco extends Disco // {{{
	{
		var $actions = array( 'yes' => 'Yes' , 'cancel' => 'No' );
		var $direction = '';
		function set_page( &$page ) // {{{
		{
			$this->admin_page = $page;
		} // }}}
		
		function do_error( $err_message ) // {{{
		{
			die( $err_message );
		} // }}}
		function get_entities() // {{{
		{
			$page_vars = $this->admin_page->request;
			
			if( !empty( $page_vars[ 'entity_a' ] ) AND !empty( $page_vars[ 'entity_b' ] ) )
			{
				$entity_a = new entity( $page_vars[ 'entity_a' ] );
				$entity_b = new entity( $page_vars[ 'entity_b' ] );
			}
			elseif( !empty( $page_vars[ 'entity_a' ] ) )
			{
				$entity_a = new entity( $page_vars[ 'entity_a' ] );
				$entity_b = new entity( $this->admin_page->id );
				$this->direction = 'b_to_a';
			}
			elseif( !empty( $page_vars[ 'entity_b' ] ) )
			{
				$entity_a = new entity( $this->admin_page->id );
				$entity_b = new entity( $page_vars[ 'entity_b' ] );
				$this->direction = 'a_to_b';
			}
			else $this->do_error( 'Not all entities needed are present' );

			if( empty( $page_vars[ 'rel_id' ] ) )
				$this->do_error( 'Rel ID is not set' );

			$q = 'select * from allowable_relationship where id=' . $page_vars[ 'rel_id' ];
			$result = db_query( $q , 'Error retrieving allowable relationships' );
			$row = mysql_fetch_array( $result , MYSQL_ASSOC );

			if( $row[ 'relationship_a' ] != $entity_a->get_value( 'type' ) )
				$this->do_error( 'The types of entity_a and relationship_a do not match up. ' );

			if( $row[ 'relationship_b' ] != $entity_b->get_value( 'type' ) )
				$this->do_error( 'The types of entity_b and relationship_b do not match up. ' );
			$x = array( $entity_a , $entity_b , $row );
			return $x;
		} // }}}
		function pre_show_form() // {{{
		{
			list( $entity_a , $entity_b , $rel_info ) = $this->get_entities();
			
			$entity_a_type = new entity($entity_a->get_value( 'type' ));
			$entity_b_type = new entity($entity_b->get_value( 'type' ));
			$entity_a_type_name = carl_strtolower($entity_a_type->get_value('name'),'UTF-8');
			$entity_b_type_name = carl_strtolower($entity_b_type->get_value('name'),'UTF-8');
			echo '<p>Only one '.$entity_b_type_name.' may be associated with a '.$entity_a_type_name.' in this way.<br />Pressing "Yes" will replace the previously related '.$entity_b_type_name.' with "'.$entity_b->get_value( 'name' ).'"</p>';
			echo '<p>Would you like to continue?</p>';
			
		} // }}}
		
		function grab_all_page_requests( &$page ) // {{{
		{
			$this->admin_page = &$page;
			foreach( $page->request AS $request => $value )
			{
#				if( !isset( $this->_elements[ $request ] ) )
				if( !$this->_is_element($request) && ($request != 'submitted'))
				{
					$this->add_element( $request , 'hidden' );
					$this->set_value( $request , $value );
				}
			}
		} // }}}
		function finish() // {{{
		{
			if( $this->chosen_action != 'cancel' )
			{
				$this->do_associate();
			}
		} // }}}
		function do_associate() // {{{
		{
			list( $entity_a , $entity_b , $rel_info ) = $this->get_entities();
			
			
			if(!$this->_cur_user_has_privs($entity_a, $entity_b, $rel_info))
			{
				return false;
			}

			//put entity id into site id rather than site id if entity_a is owned by this site
			$own = $entity_a->get_owner();
			if( $this->admin_page->site_id == $own->id() )
				$site_id = 0;
			else
				$site_id = $this->admin_page->site_id;

			if( $rel_info[ 'connections' ] == 'one_to_many' )
				$this->remove_relationships( $entity_a , $rel_info );
			
			// check whether the allowable relationship is sortable. insert appropriate increment for rel_sort_order
			if ($rel_info[ 'is_sortable' ] == 'yes')
			{
				$es = new entity_selector();
        		$es->add_type($rel_info[ 'relationship_b' ] );
        		$es->set_sharing( 'owns,borrows' );
        		$es->add_right_relationship( $entity_a->id(), $rel_info[ 'id' ] );
        		$es->add_rel_sort_field( $entity_a->id());
				$es->set_order('rel_sort_order DESC');
				$es->set_num(1);
				$result = $es->run_one();
				if (count($result) == 1)
				{
					$e = current($result);
					$new_rel_sort = $e->get_value('rel_sort_order') + 1;
				}
				else $new_rel_sort = 1;
			}
			if (isset($new_rel_sort)) create_relationship($entity_a->id(),$entity_b->id(),$rel_info['id'],array('site'=>$site_id, 'rel_sort_order'=>$new_rel_sort), true);
			else create_relationship($entity_a->id(),$entity_b->id(),$rel_info['id'],array('site'=>$site_id), true);
		} // }}}
		
		function _cur_user_has_privs($entity_a, $entity_b, $rel_info)
		{
			if($entity_a->get_value('state') == 'Pending' || $entity_b->get_value('state') == 'Pending')
			{
				$priv = 'edit_pending';
			}
			else
			{
				$priv = 'edit';
			}
			if(!reason_user_has_privs($this->admin_page->user_id, $priv))
				return false;
			else
			{
				$user = new entity($this->admin_page->user_id);
				if($entity_a->user_can_edit_relationship($rel_info['id'], $user, 'right') && $entity_b->user_can_edit_relationship($rel_info['id'], $user, 'left'))
					return true;
				else
					return false;
			}
		}
		
		function remove_relationships( $entity_a , $rel_info ) // {{{
		{
			$q = 'DELETE FROM relationship where entity_a = ' . $entity_a->id() . ' AND type = ' . $rel_info[ 'id' ];
			db_query( $q , 'Error removing existing relationships' );
		} // }}}
		function where_to() // {{{
		{
			if($this->direction == 'b_to_a') // reverse associator
				$link = unhtmlentities( $this->admin_page->make_link( array( 'entity_a' => '' , 'entity_b' => '' , 'cur_module' => 'ReverseAssociator' ) ) );
			else
				$link = unhtmlentities( $this->admin_page->make_link( array( 'entity_a' => '' , 'entity_b' => '' , 'cur_module' => 'Associator' ) ) );
			return $link;
		} // }}}
	} // }}}

	/**
	 * The admin form that handles disassociating entities from each other
	 */
	class doUnassociateDisco extends doAssociateDisco // {{{
	{
		function pre_show_form() // {{{
		{
			list( $entity_a , $entity_b , $rel_info ) = $this->get_entities();
			if( $rel_info[ 'connections' ] == 'one_to_many' )
			{
				echo $rel_info[ 'name' ] . ' is a one-to-many relationship, this means you are not allowed to disassociate it.  Hit Cancel to return.';
				unset( $this->actions[ 'yes' ] );
			}
			else
			{
				echo 'Do you wish to disassociate "' . $entity_a->get_value( 'name' ) . '" with "' . $entity_b->get_value( 'name' ) . '"? ';
				echo '<span class="smallText">( Type: ' . $rel_info[ 'name' ] . ' )</span>';
			}
		} // }}}
		function finish() // {{{
		{
			if( $this->chosen_action != 'cancel' )
			{
				$this->do_unassociate();
			}
		} // }}}
		function do_unassociate() // {{{
		{
			list( $entity_a , $entity_b , $rel_info ) = $this->get_entities();
			if(!$this->_cur_user_has_privs($entity_a, $entity_b, $rel_info))
			{
				return false;
			}
			delete_relationships(array('entity_a' => $entity_a->id(),
									   'entity_b' => $entity_b->id(),
									   'type' => $rel_info['id']));
		}
	}
?>
