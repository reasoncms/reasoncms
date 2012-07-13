<?php
	/**
	 * ContentManager is derived from Disco - its purpose to show and manage the content type editing screens for reason.
	 *
	 * Other content handlers are derived from this class to show specific form elements or any other necessary specific work.
	 *
	 * Other content handlers MUST SET $content_handler to the name of the class
	 * @author Brendon Stanton and Dave Hendler 2002 - 2003
	 * @package reason
	 * @subpackage content_managers
	 */

	/**
 	 * this line is important - make sure any content handlers have this variable set in their include files!!!!
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'ContentManager';
	
	/**
	 * Necessary Includes
	 */
	reason_include_once( 'classes/disco.php' );
	reason_include_once( 'classes/entity.php');
	reason_include_once( 'function_libraries/admin_actions.php' );
	include_once(CARL_UTIL_INC . 'api/api.php');

	//Form comment function
	//moved to disco.php3

	/**
	 * Default Content Manager Class
	 *
	 * This is the default content manager class for reason.  It handles getting
	 * the basic fields, and setting them up appropriately.  Basically, this should
	 * work fine for any form as long as you don't need to do anything special. 
	 */	
	class ContentManager extends DiscoReason2
	{
		/**
	   	 * @access private
	 	 */
		var $_required_relationships = array();

		var $left_assoc_display_names = array();
		var $left_assoc_omit_relationship = array();
		var $left_assoc_omit_link = array();
		
		var $right_assoc_display_names = array();
		var $right_assoc_omit_relationship = array();
		var $right_assoc_omit_link = array();
		
		var $actions = array( 'stay_here' => 'Save and Continue Editing', 'finish' => 'Save and Finish' );
		
		var $_locked_fields = array();
		var $_lock_indicated_fields = array();

		function init( $externally_set_up = false)
		{
			if ( !isset( $this->_inited_head_items ) OR empty( $this->_inited_head_items ))
			{
				$this->init_head_items();
				$this->_inited_head_items = true;
			}
			parent::init();
		}
		
		/**
		 * The editor module will ask the content manager about whether to call run() or run_api()
		 *
		 * @return boolean default false
		 */
		function should_run_api()
		{
			return false;
		}
		
		/**
		 * By default we run an API and do not set any content which should return a 404.
		 */
		function run_api()
		{
			$api = new CarlUtilAPI('html');
			$api->run();
			exit();
		}
		
		/**
		 * Add head items to the head_items object if head_items need to be added by the content manager
		 */
		function init_head_items()
		{
		}
		
		/**
		 * Monster function that sets up all the basics.
		 *
		 * Basically, there are a lot of fields we don't want to show, so we
		 * cut them out.  Also, it deals with a lot of the sharing stuff.
		 * This should probably never be overloaded.  If there's more stuff you 
		 * want to do on load, you should find some other place to do it.  If you 
		 * do need to overload it, you probably want to have:
		 * <code>
		 * parent::prep_for_run( $site_id , $type_id , $id , $user_id );
		 * </code>
		 * at the top of the function.
		 * @param int $site_id id of the site
		 * @param int $type_id id of the current type
		 * @param int $id id of the entity we're editing
		 * @param int $user_id id of the current user...this could bet the actual user or it could be the user the actual user is pretending to be if they're admin
		 * @return void
		 */
		function prep_for_run( $site_id, $type_id, $id, $user_id ) // {{{
		{
			$this->load_by_type( $type_id, $id, $user_id );
			$this->load_associations();

			if( !empty( $id ) )
			{
				$this->entity = new entity( $id, false );
				$this->entity->get_values();
				$this->entity->get_relationships();
			}
			
			
			// make sure to let MySQL auto handle the last_modified field - don't let the user see it
			$this->remove_element( 'last_modified');
			$this->remove_element( 'creation_date');
	
			// also hide the "new" field
			$this->remove_element( 'new' );

			// also hide the "created_by" field
			$this->remove_element( 'created_by' );

			// we now have sorting handled in its own place, so we don't need to show sort order
			if($this->_is_element('sort_order'))
				$this->remove_element( 'sort_order' );
	
			// maintain variables for site management navigation
			$this->add_element( 'type_id', 'hidden' );
			$this->set_value( 'type_id', $type_id );
			$this->add_element( 'site_id', 'hidden' );
			$this->set_value( 'site_id', $site_id );
			$this->add_required( 'name' );
			$this->change_element_type( 'state' , 'hidden' );

			if( site_shares_type($this->get_value( 'site_id' ), $this->get_value( 'type_id' )) )
			{
				$this->change_element_type( 'no_share', 'select', array( 'options' => array( 'Shared', 'Private' ) ) );
				$this->set_display_name( 'no_share', 'Sharing' );
				$new_order = $this->get_order();
				unset($new_order['no_share']);
				/*$new_order = array();			
				foreach( $this->_elements AS $k => $v )
				{
					if( $k != 'no_share' )
						$new_order[] = $k;
				} 
				$new_order[] = $k; */
				$this->set_order( $new_order );
				
				$sites_borrowing = get_sites_that_are_borrowing_entity($this->admin_page->id);
				$comments = 'Your site is currently sharing this type.  Select private to prevent other sites from borrowing this item. ';

				if( $sites_borrowing )
				{
					$comments .= 'This item is currently being borrowed by the following site';
					if( count( $sites_borrowing > 1 ) )
						$comments .= 's';
					$comments .= ': ';
					$first = true;
					foreach( $sites_borrowing AS $ent )
					{
						if( !$first )
							$comments .= ', ';
						else
							$first = false;

						$comments .=  $ent->get_value('name');
					}
					$comments .= '.';
				}
				else
					$comments .= 'No other site is currently borrowing this item.';
				$this->set_comments( 'no_share' , form_comment( $comments ) );
			}
			else
				$this->change_element_type( 'no_share', 'hidden' );

			//$this->set_assoc( $id , $type_id , $site_id );
			$this->alter_data();
			$this->alter_display_names();
			$this->alter_comments();
			
			/**
			 * Why do we turn all page requests into hidden elements? If there is not a good reason, don't call this and
			 * delete the method! It makes it pretty easy to stomp on legitimate hidden elements added by plasmature objects
			 */
			if( !empty( $this->admin_page->request ) ) $this->grab_all_page_requests();
			
			// if the state of the entity is pending, show the queue review actions
			// instead of the regular actions
			if( !$this->is_new_entity() AND $this->entity->get_value( 'state' ) == 'Pending' AND $this->admin_page->type_id == id_of( 'image' ) )
			{
				unset( $this->actions );
				// check for user role.  If contributor, change the name of the button.  The FinishModule is smart
				// enough to not set this item as live if the user is a contributor.
				if( reason_user_has_privs( $this->admin_page->user_id, 'publish' ) )
				{
					$this->actions[ 'publish_and_next' ] = 'Publish and go to Next';
				}
				else
				{
					$this->actions[ 'publish_and_next' ] = 'Save and go to Next';
				}
				$this->actions[ 'delete_and_next' ] = 'Delete and go to Next';
				$this->actions[ 'next' ] = 'Do Nothing and go to Next';
				$this->actions[ 'cancel' ] = 'Do Nothing and Return to the List';



				// grab this for the chosen actions section
				$this->next_entity = $this->admin_page->get_oldest_pending_entity( $this->admin_page->site_id,
																				   $this->admin_page->type_id,
																				   $this->entity->id(),
																				   $this->entity->get_value( 'last_modified' ) );
				// at the end of the queue.  go back to beginning.
				if( empty( $this->next_entity ) )
				{
					$this->next_entity = $this->admin_page->get_oldest_pending_entity( $this->admin_page->site_id,
																					   $this->admin_page->type_id );
					// if still nothing in the queue, we're done with all items
					if( empty( $this->next_entity ) )
					{
						// umm.  back to the list.

					}
				}
			}
			
			$this->_apply_locks();
		} // }}}
		
		/**
		 * Check to see if any fields are locked; if so, either lock them or indicate the presence of a lock (depending on the privs of the current user)
		 */
		function _apply_locks()
		{
			$user = new entity($this->admin_page->user_id);
			foreach(array_keys($this->entity->get_values()) as $field_name)
			{
				if(!$this->entity->user_can_edit_field($field_name, $user))
				{
					$this->lock_field($field_name);
				}
				elseif($this->entity->field_has_lock($field_name))
				{
					$this->add_lock_indicator($field_name);
				}
			}
			foreach($this->_relationship_elements as $name=>$info)
			{
				if(!$this->entity->user_can_edit_relationship($info['rel_id'], $user,$info['direction']))
				{
					$this->lock_field($name);
				}
				elseif($this->entity->relationship_has_lock($info['rel_id'],$info['direction']))
				{
					$this->add_lock_indicator($name);
				}
			}
		}
		/**
		 * Keep a given field from being edited due to a lock that has been placed on it
		 * @param string $field_name
		 * @return void
		 */
		function lock_field($field_name)
		{
			if($this->is_element($field_name) && !$this->element_is_hidden($field_name))
			{
				if(
					$this->is_required($field_name)
					&& 
					(
						'' === $this->get_value($field_name)
						||
						null === $this->get_value($field_name)
						||
						false === $this->get_value($field_name)
					)
				)
				{
					$this->add_comments($field_name, form_comment('This field will be locked once you have saved this form.') );
				}
				else
				{
					//echo $this->get_element_property($field_name, 'type');
					if(html_editor_name($this->admin_page->site_id) == $this->get_element_property($field_name, 'type'))
						$this->change_element_type($field_name,'wysiwyg_disabled');
					else
						$this->change_element_type($field_name,'solidtext');
					$this->set_comments($field_name, '');
					$this->set_comments($field_name, '<img 	class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="locked" width="12" height="12" />', 'before' );
					$this->_locked_fields[] = $field_name;
				}
			}
		}
		/**
		 * Add an indication that a given field is locked for other users
		 * @param string $field_name
		 * @return void
		 */
		function add_lock_indicator($field_name)
		{
			if($this->is_element($field_name) && !$this->element_is_hidden($field_name))
			{
				//$this->set_comments($field_name, '', 'before' );
				$this->add_comments($field_name, form_comment('<img 	class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="locked" width="12" height="12" />'), 'before' );
				$this->_lock_indicated_fields[] = $field_name;
			}
		}
		
		
		/**
		 * Accept a reference to the head items so that content managers can interact with head items directly
		 * @author Nathan White
		 */
		function set_head_items(&$head_items)
		{
			$this->head_items =& $head_items;
		}
		
		/**
		 * This function is used when you're editting an entity within editing another entity
		 * @return void
		 * @todo get rid of references to global variables and make them local 
		 */
		function load_associations() // {{{
		{
			global $rel_id , $rel_entity_a , $rel_entity_b;		

			if( $rel_id AND ( $rel_entity_a OR $rel_entity_b ) )
			{
				$this->add_element( 'rel_id' , 'hidden' );
				$this->set_value( 'rel_id' , $rel_id );
				
				if( $rel_entity_a )
				{
					$this->add_element( 'rel_entity_a' , 'hidden' );
					$this->set_value( 'rel_entity_a' , $rel_entity_a );
					$e = new entity( $rel_entity_a );
				}
				else
				{
					$this->add_element( 'rel_entity_b' , 'hidden' );
					$this->set_value( 'rel_entity_b' , $rel_entity_b );
					$e = new entity( $rel_entity_b );
				}
				
				$t = 'Save and return to editing ' . $e->get_value( 'name' );
				
				$this->actions = array( 'return' => $t );
			}
		} // }}}
		/**
		 * grabs request fields and pops them into the forms values if it finds them
		 * @return void
		 */
		function grab_all_page_requests() // {{{
		{
			foreach( $this->admin_page->request AS $request => $value )
			{
#				if( !isset( $this->_elements[ $request ] ) AND !in_array( $request, $this->_ignored_fields ) )
				if( !$this->_is_element($request) AND !in_array( $request, $this->_ignored_fields ) AND ($request != 'submitted') )
				{
					$this->add_element( $request , 'hidden' );
					$this->set_value( $request , $value );
				}
			}
		} // }}}
		
		/**#@+
		 * Overloadable function for classes that extend this
		 */
		function alter_data() // {{{
		{
			//overloadable function
		} // }}}
		function alter_display_names() // {{{
		{
			//overloadable function 
		} // }}}
		function alter_comments() // {{{
		{
			//overloadable function
		} // }}}
		/**#@-*/

		// site management page display functions

		/**
		 * Called before the form is drawn
		 *
		 * This can be overloaded in some cases, but is generally not used.
		 *
		 * This function was more important for non-reason uses of Disco
		 * where the form was responsible for creating a lot more of the page
		 * @return void
		 */
		function pre_show_form() // {{{
		{
			if($this->get_value('entity_saved') && !$this->_has_errors())
			{
				$e = new entity($this->get_value('id'));
				echo '<h4 class="saved">Saved at ' . prettify_mysql_timestamp( $e->get_value( 'last_modified' ), 'g:i A' ) . '</h4>';
			}
			
			if(!empty($this->_locked_fields))
				echo '<p><img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="locked" width="12" height="12" /> = Locked fields (info)</p>';
			
			if(!empty($this->_lock_indicated_fields))
				echo '<p><img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="locked" width="12" height="12" /> = Fields locked for some users</p>';
		
		} // }}}
	
		/**#@+
		 * Called right before the form finishes
		 *
		 * In old Disco, finish would either return a link or return true.
		 * If it returned true, it would redirect to the current page, otherwise
		 * it would redirect to wherever the link said.  However, this was a bit of 
		 * a strain on the finish function.  To fix this problem, the where_to()
		 * function was created.  The Content_Manager still pays attention (I think)
		 * to what this returns, but where_to() takes priority.  CMfinish was 
		 * built as an extension to finish specifically for reason.
		 * @return void
		 */
		function CMfinish() // {{{
		{
			return true;
		} // }}}
		function finish() // {{{
		{
			return $this->CMfinish();
		} // }}}
		/**#@-*/
		
		/**
		 * This is called by the Admin Finish Module.
		 * Overload it if you want something special to happen when the entity is finished.
		 * @return void
		 */
		function run_custom_finish_actions( $new_entity = false ) // {{{
		{
		} // }}}
		/**
		 * Deletes relationship info
		 * @param int $rid actual id in ar table
		 * @param int $ent_id entity's id, used to make sure current entity is actually part of that relationship so we don't erase something by accident
		 * @param string $side either 'right' or 'left' depending on what side the entity is supposed to be on
		 * @return void
		 */
		function delete_relationship_info( $r_id , $ent_id , $side ) // {{{
		{
			if( $side == 'left' )
			{
				$q = 'DELETE FROM relationship WHERE type = ' . $r_id . ' AND entity_a = ' . $ent_id;
				db_query( $q , 'Error deleting existing relationships' );
			}
			elseif( $side == 'right' )
			{
				$q = 'DELETE FROM relationship WHERE type = ' . $r_id . ' AND entity_b = ' . $ent_id;
				db_query( $q , 'Error deleting existing relationships' );
			}
		} // }}}
		/**
		 * pending queue has some actions that need to fire before error 
		 * checks are run to avoid coming back to the form when there is
		 * no reason to come back to the form
		 * @return void
		 */
		function pre_error_check_actions() //{{{
		{
			$link = '';
			// in pending queue, skip chosen
			if( $this->chosen_action == 'next' )
			{
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Editor', 'id' => $this->next_entity->id() ) ) );
			}
			elseif( $this->chosen_action == 'cancel' )
			{
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Lister', 'state' => 'pending', 'id' => '') ) );
			}
			// in pending queue, delete chosen
			elseif( $this->chosen_action == 'delete_and_next' )
			{
				// get id of next object 
				$q = 'UPDATE entity SET state = "Deleted" where id = ' . $this->entity->id();
				db_query( $q , 'Error setting state as deleted in deleteDisco::finish()' );
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Editor', 'id' => $this->next_entity->id() ) ) );
			}
			if( !empty( $link ) )
			{
				header( 'Location: '.$link );
				die();
			}
		} // }}}
		/**
		 * determine button pressed and route accordingly
		 * 
		 * see finish() and CMfinish()
		 * @return string link of where to go when form is done
		 */
		function where_to() // {{{
		{
			$page =& $this->admin_page;
			$link = null;
			$change_detection_redirect = ($this->is_element('change_detection_redirect')) 
									   ? $this->get_value('change_detection_redirect') 
									   : false;
			
			if ($change_detection_redirect)
			{
				$link = $change_detection_redirect;
			} 
			else if ($this->chosen_action == 'finish') 
			{
				$link = $page->make_link(array('cur_module' => 'Finish'), false, false);
			}
			else if ($this->chosen_action == 'publish_and_next')
			{
				// in pending queue, publish chosen:
				// transition to finish and make sure finish knows we're in
				// queue mode so that it can hand off the control to the next
				// editor
				$link = $page->make_link(array('cur_module' => 'Finish', 'next_entity' => $this->next_entity->id()), false, false);
			}
			else 
			{
				$params = array('id' => $this->_id, 'cur_module' => 'Editor', 'submitted' => false, 'entity_saved' => true);
				$params = array_merge($params, $this->get_continuation_state_parameters());
				$link = $page->make_link($params, false, false);
			}
			return $link; 
		} // }}}		
		
		/**
		 * Returns any additional query parameters that should be passed to
		 * the editing page on a "Save & Continue Editing" event.
		 * @access protected
		 * @return array
		 */
		function get_continuation_state_parameters()
		{
			return array();
		}
	}
?>
