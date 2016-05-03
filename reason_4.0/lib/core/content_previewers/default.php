<?php
	/**
	 * Content Previewer
	 * @author Brendon Stanton
	 * @package reason
	 * @subpackage content_previewers
	 */

	/**
	 * Required Includes
	 */
	include_once( 'reason_header.php' );
	reason_include_once( 'classes/viewer.php' );

	/**
	 * Name of the previewer that will be used
	 *
	 * Make sure to set this variable at the top of another file if you're overloading
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'default_previewer';

	/**
	 * Should be good enough for most previews
	 * 
	 * Overloading for specific types might not be needed if you're doing it for just a specific
	 * field.  In fact, it might be better to overload the field here.
	 *
	 * A full blown overload will usually only be needed if you want to change the look of the 
	 * page.  Otherwise, if you want a field to show up differently than just displaying it as
	 * plain text, create a new function called show_item_<field> where <field> is the name of the
	 * field you're changing.
	 *
	 * When running, the previewer checks the names of the different fields to see if a function
	 * of that name exists.  If it does, that function is called.  Otherwise, the show_item_default
	 * function is called.  Even when overloading, it is often best to just call show_item_default
	 * but with a different value.  For instance, show_item_name is defined as follows:
	 * <code>
	 *  
         *	function show_item_name( $field , $value ) 
	 *	{
	 *		$this->show_item_default( $field , $this->_entity->get_display_name() );
	 *	} 
	 * </code>
	 * As you can see, the function just changes the value of the field and calls the default function.
	 * This prevents using redundant code and also allows flexibility if the previewer is extended
	 * since you won't have to worry about how the item will be displayed.
	 */
	class default_previewer
	{
		/**#@+
		 * @access private
		 */
		var $_id;
		var $_entity = array();
		var $_user;
		var $_fields = array();
		/**#@-*/

		/**
		 * Initializes the class with an id and an admin page
		 * @param int id
		 * @parm AdminPage $page
		 * @return void 
		 */
		function init( $id , &$page) // {{{
		{
			$this->_id = $id;
			$this->admin_page =& $page;
			$this->_entity = new entity( $id );
			$this->_user = new entity( $this->admin_page->user_id );
			$this->_fields = get_fields_by_type( $this->_entity->get_value( 'type' ) , true );
		} // }}}
		/**
		 * The main function
		 *
		 * Shows everything
		 * @return void 
		 */
		function run() // {{{
		{
			echo '<div id="preview">'."\n";
			$this->pre_show_entity();
			$this->display_entity();
			$this->pre_show_relationships();
			$this->display_relationships();
			$this->post_show_entity();
			echo '</div>'."\n";
		} // }}}

		/**
		 * Meant for overloading.  Will show something before the entity is previewed
		 */
		function pre_show_entity() // {{{
		{
		} // }}}
		/**
		 * Meant for overloading.  Will show something between the entity fields and relationships
		 */
		function pre_show_relationships() // {{{
		{
		} // }}}
		/**
		 * Meant for overloading.  Will show something after the entity is previewed
		 */
		function post_show_entity() // {{{
		{
		} // }}}
		
		/**
		 * Function called which contains the guts of the entity
		 */
		function display_entity() // {{{
		{
			echo '<div class="itemData">'."\n";
			$this->show_all_values( $this->_entity->get_values() );
			echo '</div>';
		} // }}}
		/**
		 * Function called which displays relationship info about the entity
		 */
		function display_relationships() //{{{
		{
			echo "\n".'<div id="previewRels">'."\n";
			$this->display_left_relationships();
			$this->display_right_relationships();
			echo '</div>'."\n";
		} // }}}
		function display_left_relationships() // {{{
		{
			$rels = $this->get_left_relationships();
			if(!empty($rels))
			{
				$associated_items = array();
				foreach( $rels AS $key=>$v )
				{
					$es = new entity_selector();
					$es->add_type( $v[ 'relationship_b' ] );
					$es->set_env( 'site' , $this->admin_page->site_id );
					$es->add_right_relationship( $this->admin_page->id , $v[ 'id' ] );
					$ass_items = $es->run_one();
					
					if(!empty($ass_items))
					{
						$associated_items[$key] = $ass_items;
					}
				}
				if(!empty($associated_items))
				{
					echo '<h3>Other information</h3>';
					echo '<ul>'."\n";
					foreach( $rels AS $key=>$v )
					{
						if(!empty($associated_items[$key]))
						{
							$row = $this->get_rel_info( $v[ 'name' ] );
							echo '<li><h4>';
							if( !$this->_entity->user_can_edit_relationship($v['id'], $this->_user, 'right') )
							{
								echo '<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="Locked" width="12" height="12" />';
							}
							elseif($this->_entity->relationship_has_lock($v['id'], 'right') && reason_user_has_privs($this->_user->id(), 'manage_locks'))
							{
								echo '<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="Locked for some users" title="Locked for some users" width="12" height="12" />';
							}
							if( $row )
							{
								echo !empty( $row['display_name'] ) ? $row['display_name' ] : $row[ 'entity_name' ];
							}
							else
							{
								echo !empty( $v[ 'display_name' ]) ? $v['display_name'] : $v['name'];
							}
							echo ':</h4>'."\n";
							echo '<ul>'."\n";
							foreach($associated_items[$key] AS $ent )
							{
								echo '<li>'.$this->_get_rel_list_display_name($ent,$v['name'],'left').'</li>'."\n";
							}
							echo '</ul>'."\n";
							echo '</li>'."\n";
						}
					}
					echo '</ul>'."\n";
				}
			}
		} // }}}
		function get_rel_info( $index ) // {{{
		{
			if( !empty( $this->admin_page->associations ) )
			{
				foreach( $this->admin_page->associations AS $side )
				{
					foreach( $side AS $ass )
					{
						if( $ass[ 'name' ] == $index )
							return $ass;
					}
				}
			}
			return array();
		} // }}}
		function display_right_relationships() // {{{
		{
			$rels = $this->get_right_relationships();
			if(!empty($rels))
			{
				$associated_items = array();
				foreach( $rels AS $key=>$v )
				{
					$es = new entity_selector();
					$es->add_type( $v[ 'relationship_a' ] );
					$es->set_env( 'site' , $this->admin_page->site_id );
					$es->add_left_relationship( $this->admin_page->id , $v[ 'id' ] );
					$es->set_env( 'restrict_site' , false );
					$es->add_right_relationship_field( 'owns', 'entity' , 'name' , 'owner_name' );
					$es->add_right_relationship_field( 'owns', 'entity' , 'id' , 'owner_id' );
					$ass_items = $es->run_one();
					
					if(!empty($ass_items))
					{
						$associated_items[$key] = $ass_items;
					}
				}
				if(!empty($associated_items))
				{
					echo '<h3>Usage</h3>'."\n";
					echo '<ul>'."\n";
					foreach( $rels AS $key=>$v )
					{
						if(!empty($associated_items[$key]))
						{
							$is_borrows_rel = (!reason_relationship_names_are_unique()) ? $v['name'] == 'borrows' : $v['type'] == 'borrows';
							if( $is_borrows_rel )
							{
								$type = new entity($this->_entity->get_value( 'type' ));
								$title = 'Sites That Are Borrowing This '.$type->get_value('name');
								$show_owner_site = false;
							}
							else
							{
								$show_owner_site = true;
								$row = $this->get_rel_info( $v[ 'name' ] );
								if( $row )
								{
									$title = !empty( $row['description_reverse_direction'] ) ? $row['description_reverse_direction' ] : $row[ 'entity_name' ];
								}
								else
								{
									$title = !empty( $v[ 'description_reverse_direction' ]) ? $v['description_reverse_direction'] : $v['name'];
								}
							}
							echo '<li><h4>';
							
							if( !$this->_entity->user_can_edit_relationship($v['id'], $this->_user, 'left') )
							{
								echo '<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="Locked" width="12" height="12" />';
							}
							elseif($this->_entity->relationship_has_lock($v['id'], 'left') && reason_user_has_privs($this->_user->id(), 'manage_locks'))
							{
								echo '<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="Locked for some users" title="Locked for some users" width="12" height="12" />';
							}
							echo $title.':</h4>'."\n";
							echo '<ul>'."\n";
							foreach($associated_items[$key] AS $ent )
							{
								echo '<li>'.$this->_get_rel_list_display_name($ent,$v['name'],'right');
								if($show_owner_site && $ent->get_value('owner_id') != $this->admin_page->site_id)
								{
									//echo ' <em>(<a href="http://'.REASON_HOST.$ent->get_value('owner_base_url').'">'.$ent->get_value('owner_name').'</a>)</em>';
									echo ' <em>('.$ent->get_value('owner_name').')</em>';
								}
								echo '</li>'."\n";
							}
							echo '</ul>'."\n";
							echo '</li>'."\n";
						}
					}
					echo '</ul>'."\n";
				}
			}
		} // }}}
		function _get_rel_list_display_name($entity,$rel_name,$direction)
		{
			return $entity->get_display_name() . ' (ID: '.$entity->id().')';
		}
		function get_left_relationships() // {{{
		{
			return $this->get_relationships();
		} // }}}
		function get_right_relationships() // {{{
		{
			return $this->get_relationships('right');
		} // }}}
		function get_relationships($dir = 'left') // {{{
		{
			$q = new DBSelector();
			$q->add_table( 'ar', 'allowable_relationship' );
			$q->add_table( 'e', 'entity' );
			//$q->add_table( 'site_own_alrel', 'allowable_relationship' );
			//$q->add_table( 'r', 'relationship' );

			$q->add_field( 'ar', '*' );
			$q->add_field( 'e', 'name', 'entity_name' );
			if($dir == 'left')
			{
				$q->add_relation( 'ar.relationship_a = '.$this->admin_page->type_id );
				$q->add_relation( 'ar.relationship_b = e.id' );
				if (!reason_relationship_names_are_unique())
				{
					$q->add_relation( 'ar.name != "borrows"' );
				}
				else
				{
					$q->add_relation( 'ar.type != "borrows"' );
				}
			}
			elseif($dir == 'right')
			{
				$q->add_relation( 'ar.relationship_a = e.id' );
				$q->add_relation( 'ar.relationship_b = '.$this->admin_page->type_id );
			}
			if (!reason_relationship_names_are_unique())
			{
				$q->add_relation( 'ar.name != "owns"' );
				$q->add_relation( 'ar.name NOT LIKE "%archive%"' );
			}
			else
			{
				$q->add_relation( 'ar.type != "owns"' );
				$q->add_relation( 'ar.type != "archive"' );
			}
			// make sure this site has access to the related type
			// we don't want to be able to associate with types that a site does not have access to
			/*
			$q->add_relation( 'site_own_alrel.relationship_a = '.id_of( 'site' ) );
			$q->add_relation( 'site_own_alrel.relationship_b = '.id_of( 'type' ) );
			$q->add_relation( 'site_own_alrel.name = "site_to_type"' );
			$q->add_relation( 'r.entity_a = '.$this->admin_page->site_id );
			$q->add_relation( 'r.entity_b = e.id' );
			$q->add_relation( 'r.type = site_own_alrel.id' );
			*/
			$r = db_query( $q->get_query(), 'Unable to get allowable relationships for this type.' );
			$x = array();
			while( $row = mysql_fetch_array( $r , MYSQL_ASSOC ) )
				$x[] = $row;
			return $x;
		} // }}}
		function start_table() // {{{
		{
		} // }}}
		function show_all_values( $values ) // {{{
		{
			$meth = get_class_methods( $this );
			foreach( $values AS $k => $v )
			{	
				$field = $this->_fields[ $k ];
				$field_type = preg_replace( '/\(.+\)/' , '' , $field[ 'Type' ] );
				$name_function = 'show_item_' . $k;
				$field_function = 'show_item_field_' . $field_type;
				
				if( in_array( $name_function , $meth ) )
					$this->$name_function( $k , $v );
				elseif( in_array( $field_function , $meth ) )
					$this->$field_function( $k , $v );
				else $this->show_item_default( $k , $v );
			}
		} // }}}
		function end_table() // {{{
		{
			trigger_error('end_table() is deprecated.');
		} // }}}
		
		/**
		 * This is the way an item is shown by default if the function show_item_$field is not defined
		 * It keeps track of whether the row is odd or even (for alternating stylesheets and puts out 
		 * the element's field and value in two tds
		 * @param string $field the name of the field, will show up in the left column
		 * @param string $value the value that will be displayed in the right column
		 */
		function show_item_default( $field , $value ) // {{{
		{
			echo '<div class="listRow">';
			echo '<h4 class="field"">';
			if($lock_str = $this->_get_lock_indication_string($field))
				echo $lock_str . '&nbsp;';
			echo prettify_string( $field );
			echo '</h4>';
			echo '<div class="value">' . ( ($value OR (strlen($value) > 0)) ? $value : '<em>(No value)</em>' ). '</div>';

			echo '</div>';
		} // }}}
		
		function _get_lock_indication_string($field)
		{
			if( $this->_entity->has_value($field) && $this->_entity->field_has_lock($field) )
			{
				if(!$this->_entity->user_can_edit_field($field, $this->_user))
				{
					return '<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="Locked" width="12" height="12" />';
				}
				elseif(reason_user_has_privs( $this->_user->id(), 'manage_locks' ) )
				{
					return '<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="Locked for some users" title="Locked for some users" width="12" height="12" />';
				}
			}
			return '';
		}
		
		/**#@+
		 * Overloaded function for a default type
		 * @param string $field the name of the field, will show up in the left column
		 * @param string $value the value that will be displayed in the right column
	 	 * @return void
		 */
		function show_item_name( $field , $value ) // {{{
		{
			$this->show_item_default( $field , $this->_entity->get_display_name() );
		} // }}}
		function show_item_no_share( $field , $value ) // {{{
		{
			$v = $value ? 'This item may not be borrowed by other sites' : 'This item may be borrowed by other sites';
			$this->show_item_default( 'Sharability' , $v );
		} // }}}
		function show_item_unique_name( $field , $value ) // {{{
		{
			if( reason_user_has_privs( $this->admin_page->user_id, 'view_sensitive_data' ) )
				$this->show_item_default( $field , $value );
		} // }}}
		function show_item_last_edited_by( $field , $value ) // {{{
		{
			if( $value )
			{
				$user = new entity( $value );
				if($user->get_values())
				{
					$name = $user->get_value('name');
				}
				else
				{
					$name = 'User ID '.$value;
				}
				$new_value =  $name . ' on ' . 
					prettify_mysql_timestamp( $this->_entity->get_value( 'last_modified' ), 'M j, Y \a\t g:i a' );
				$this->show_item_default( 'Last Edited By' , $new_value );
			}
			else
			{
				$new_value = prettify_mysql_timestamp( $this->_entity->get_value( 'last_modified' ), 'M j, Y \a\t g:i a' );
				
				$this->show_item_default( 'Last Edited On' , $new_value  );
			}
		} // }}}
		function show_item_last_modified( $field , $value ) // {{{
		{
		} // }}}
		function show_item_created_by( $field , $value ) // {{{
		{
			if( $value )
			{
				$user = new entity( $value );
				if(is_object($user) && $user->get_values())
				{
					$name = $user->get_value('name');
				}
				else
				{
					$name = 'User ID '.$value;
				}
				$new_value =  $name . ' on ' . 
						  prettify_mysql_timestamp( $this->_entity->get_value( 'creation_date' ), 'M j, Y \a\t g:i a' );
				$this->show_item_default( 'Created By' , $new_value );
			}
			else
			{
				$new_value = prettify_mysql_timestamp( $this->_entity->get_value( 'creation_date' ), 'M j, Y \a\t g:i a' );
				
				$this->show_item_default( 'Created On' , $new_value  );
			}
		} // }}}
		function show_item_creation_date( $field , $value ) // {{{
		{
		} // }}}
		function show_item_type( $field , $value ) // {{{
		{
			$e = new entity( $value );
			$this->show_item_default( 'Type' , $e->get_value( 'name' ) );
		} // }}}
		
		function show_item_field_datetime( $field , $value ) // {{{
		{
			$this->show_item_default( $field , prettify_mysql_datetime( $value, "M jS, Y, g:i a") );
		} // }}}
		function show_item_field_timestamp( $field , $value ) // {{{
		{
			$this->show_item_default( $field , prettify_mysql_timestamp( $value, "M jS, Y, g:i a") );
		} // }}}
		/**#@-*/
	
	}
?>
