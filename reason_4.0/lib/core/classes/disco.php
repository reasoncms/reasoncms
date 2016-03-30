<?php
	/**
	 *  @package reason
	 *  @subpackage classes
	 */
	 
	 /**
	  * include Reason libraries
	  */
	 include_once('reason_header.php');
	 /**
	  * Include parent class -- disco
	  */
	include_once (DISCO_INC . 'disco.php');
	/**
	*  Disco Reason 2
	*
	*  disco reason is a slightly modified DiscoDB that takes care of some of the Reason CMS stuff automatically.
	*
	*  example: you can give a content type name or id to the load function to auto load the tables for that type instead of using the other functions
	*
	* There was once DiscoReason; hence the name
	*
	*  @author dave hendler
	*/
	class DiscoReason2 extends Disco
	{
		/**
		 * list of elements that tidy should not touch
		 * Use this array with caution, since it can result in bad HTML on your pages
		 *
		 * @var array
		 */
		var $_no_tidy = array(
			'id',
			'unique_name',
			'last_modified',
			'creation_date',
			'state',
			'department',
			'extra_head_content',
			'thor_content'
		);
		var $_log_errors = true;
		
		/**
		 * Stores which elements are relationship elements so that the appropriate actions can be taken during the process phase
		 *
		 * format: array('element_name'=>array('type_id'=>int $type_id,'rel_id'=>int $rel_id,'direction'=>string $direction,'options'=>array($id_1,$id_2,...),'related_at_prep'=>array($id_1,$id_2,...) ) )
		 * 
		 * @var array
		 */
		var $_relationship_elements = array();
		
		/**
		 * Stores the list of database fields used by the entity. Populated in load_by_type(), this list is
		 * used by process() to determine what should run through tidy.
		 * 
		 * @var array
		 */
		var $_entity_fields = array();
		
		var $strip_tags_from_user_input = true;
		var $allowable_HTML_tags = REASON_DEFAULT_ALLOWED_TAGS;

		var $hidden_field_cutoffs = array();
		
		function is_new_entity() // {{{
		{
			$temp = new entity( $this->get_value('id'),false );
			return $temp->get_value( 'new' );
		} // }}}
		function load_by_type( $type_id, $id, $user_id ) // {{{
		{
			$this->_id = $id;

			$this->_original = new entity( $this->_id );

			// load all fields used by this type
			$q = 'DESC entity';
			$r = db_query( $q, 'Unable to get entity description' );
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			{
				list( $type, $args ) = $this->plasmature_type_from_db_type( $row['Field'], $row['Type'] );
				$this->add_element( $row['Field'], $type, $args );
			}

			// get tables associated with this type
			$es = new entity_selector;
			$es->description = 'disco reason: load_by_type: tables to type';
			$es->add_type( id_of('content_table') );
			$es->add_right_relationship( $type_id, relationship_id_of( 'type_to_table' ) );
			$tables = $es->run_one();
			unset( $es );

			// make an element for each field the type has
			foreach( $tables AS $tid => $table )
			{
				// grab the type's entity tables and fields
				$es = new entity_selector;
				$es->description = 'disco reason 2: load_by_type: fields associated with table '.$table->get_value('name');
				$es->add_type( id_of('field') );
				$es->add_left_relationship( $tid, relationship_id_of( 'field_to_entity_table' ) );
				$fields = $es->run_one('','Live','field es');
				unset( $es );

				foreach( $fields AS $fid => $field )
				{
					$args = array();
					$type = '';
					// set the plasmature type if specified by the field, otherwise look up the default for the database type
					list( $type, $args ) = $this->plasmature_type_from_db_type( $field->get_value('name'), $field->get_value('db_type') );
					if ( $field->get_value( 'plasmature_type' ) )
						$type = $field->get_value( 'plasmature_type' );
				
					// hook for plasmature arguments here
				
					$this->add_element( $field->get_value('name'), $type, $args,$field->get_value( 'db_type' ) );
					$this->_entity_fields[$field->get_value('name')] = $field->get_value( 'db_type' );
				}
			}

			// load values
			$elements = $this->_original->get_values();
			foreach( $elements AS $key => $val )
				if (isset($val)) $this->set_value( $key, $val );

			$this->init();

			$this->change_element_type( 'type','hidden' );
			$this->set_value( 'type', $type_id );
			$this->change_element_type( 'last_edited_by','hidden' );
			$this->set_value( 'last_edited_by', $user_id );
			if( !reason_user_has_privs( $user_id, 'edit_unique_names' ) )
				$this->change_element_type( 'unique_name','hidden' );
			elseif($this->get_value('unique_name'))
				$this->change_element_type( 'unique_name','solidText' );
		} // }}}
		function process() // {{{
		{			
			// ignoring last_edited_by avoids the problem of updating entities by just viewing them.
			// i believe entities will only be updated when some of the actual CONTENT of the entity is changed.
			// phew.
			$this->_process_ignore[] = 'id';
			$this->_process_ignore[] = 'last_edited_by';
			$this->_process_ignore[] = 'last_modified';

			// collect values of entity
			$values = array();
			foreach($this->get_element_names() as $element_name)
			{
				if( !in_array( $element_name, $this->_process_ignore ) )
					$values[ $element_name ] = $this->get_value($element_name);
			}

			// tidy any values that are actually being written to the database and aren't
			// in the _no_tidy list.
			foreach( $values AS $key => $el )
			{
				if( isset($this->_entity_fields[$key]) && !in_array( $key, $this->_no_tidy ) )
				{
					$values[ $key ] = tidy( $el );
				}
			}
			
			// always reason_update_entity since we created when user clicks "Add"
			$this->has_changed = reason_update_entity( $this->_id, $this->admin_page->user_id, $values, false );
				// the last argument determines whether or not to archive the entity.  if it's new, don't worry about it.  otherwise, archive
				// the $changed var grabs the result, true if changed, false if not

			// commented out nwhite 11-02-07
			// if a site is borrowing an item and the tiems sharing goes to private, the item is still borrowed by sites that had it ...
			// the owner will need to contact those sites and get them to manually remove the item from the list of those that are available.
			//
			//delete borrow relationships if no_share is true
			//if( $this->_elements[ 'no_share' ] )
			//if($this->_is_element('no_share'))
			//{
			//	if( $this->get_value( 'no_share' ) )
			//	{
			//		$d = new DBSelector;
			//		$d->add_table( 'ar' , 'allowable_relationship' );
			//		$d->add_table( 'r' , 'relationship' );
			//		$d->add_field( 'r' , 'id' , 'id' );
			//		$d->add_relation( 'r.type = ar.id' );
			//		$d->add_relation( 'ar.name = "borrows"' );
			//		$d->add_relation( 'ar.relationship_a = ' . id_of( 'site' ) );
			//		$d->add_relation( 'ar.relationship_b = ' . $this->admin_page->type_id );
			//		$d->add_relation( 'r.entity_b = ' . $this->admin_page->id );
			//		$x = $d->run();
			//		foreach( $x AS $rel )
			//			db_query( 'DELETE FROM relationship WHERE id = ' . $rel[ 'id' ] , 'Error deleting borrowed relationship' );
			//	}
			//}
			
			$this->_process_relationship_elements();
		
		} // }}}
		function show_form() // {{{
		{
			parent::show_form();
			foreach($this->hidden_field_cutoffs as $field_name => $cutoff_id)
			{
				echo '<span style="display:none" id="field_cutoffs'.$cutoff_id[0].'">'.$cutoff_id[1].'</span>';
			}
		} // }}}
		
		/**
		 * Runs all required error checks, defined checks, and user checks.
		 * Called by {@link run_process_phase()}.
		 * @access private
		 */
		function _run_all_error_checks()
		{
			if (!isset($this->_run_all_error_checks_called))
			{
				parent::_run_all_error_checks();
				$this->_check_unique_name_field();
				$this->_run_all_error_checks_called = true;
			}
		}
		
		
		/**
		 * We introduce a hack here. Clicking the "Finish" button on the left hand menu of the editor content manager has historically checked
		 * for errors, and if they exist, "submitted" the form to trigger error messages. This causes problems with CSRF protection, which 
		 * verifies that the csrf_token was submitted as a valid $_POST request before doing other error checks.
		 *
		 * So we do this:
		 *
		 * - If $_GET['submitted'] == 1 and $_POST is empty
		 * - Run _run_all_error_checks.
		 * - If we have errors, report that this could not be CSRF (form won't submit anyway since it has errors).
		 */
		function could_be_csrf()
		{
			if ( (isset($_GET['submitted']) && ($_GET['submitted'] == 1)) && empty($_POST) )
			{
				$this->_run_all_error_checks();
				if ($this->has_errors()) return false;
			}
			parent::could_be_csrf();
		}
		
		function _check_unique_name_field()
		{
			if( $this->get_value('unique_name') )
			{
				if(!reason_unique_name_valid_string($this->get_value('unique_name') ) )
				{
					$this->set_error('unique_name','Unique name must be just numbers, letters, and/or underscores. Please make sure the unique name doesn\'t contain any other characters.');
				}
				else
				{
					$id = id_of($this->get_value('unique_name'), true, false);
					if(!empty($id) && $id != $this->_id)
					{
						$entity = new entity($id);
						$this->set_error('unique_name','The unique name "'.$this->get_value('unique_name').'" is already used by the item "'.$entity->get_value('name').'". Please choose another.');
					}
				}
			}
		}
		

		/**
		 * Gets all the relationship info about an entity's relationship
		 * @param int $r_id actual id in ar table
		 * @return void
		 */
		function get_relationship_info( $r_id ) // {{{
		{
			$q = 'SELECT * FROM allowable_relationship WHERE id = ' . $r_id;
			$r = db_query( $q , 'error getting relationship info' );

			$row = mysql_fetch_array( $r , MYSQL_ASSOC );
			return $row;
		} // }}}
		
		
		function add_relationship_element($name, $type_id, $rel_id, $direction = 'right', $element_type = 'checkbox',$limit_to_site = true,$sort = 'entity.name ASC',$smart_cutoff=0)
		{
			static $directions = array('right','left');
			static $element_types = array(
					'checkbox'=>array(
									'plasmature_type'=>'checkboxgroup_no_sort',
									),
					'radio'=>array(
									'plasmature_type'=>'radio_no_sort',
									),
					'select'=>array(
									'plasmature_type'=>'select_no_sort',
									'args'=>array('add_empty_value_to_top' => true,),
									),
					'multiple_select'=>array(
									'plasmature_type'=>'select_no_sort',
									'args'=>array('size'=>7,'multiple'=>true),
									'comment'=>'Control-click (PC) or Command-click (Mac) to choose multiple items',
									),
			);
			static $single_item_element_types = array('radio','select');
			if(!array_key_exists($element_type,$element_types))
			{
				trigger_error($element_type.' is not an acceptable parameter for add_relationship_element(). Try one of the following: '.implode(', ',$element_types));
				return;
			}
			if(!in_array($direction,$directions))
			{
				trigger_error($direction.' is not an acceptable parameter for add_relationship_element(). Try one of the following: '.implode(', ',$directions));
				return;
			}
			$rel_info = $this->get_relationship_info( $rel_id );
			if(($direction == 'right' && $rel_info['connections'] == 'one_to_many') ||
				($direction == 'left' && $rel_info['connections'] == 'many_to_one'))
			{
				$can_relate_multiple_items = false;
			}
			else
			{
				$can_relate_multiple_items = true;
			}
			if(!empty($rel_info))
			{
				if(!in_array($element_type,$single_item_element_types))
				{
					if(!$can_relate_multiple_items)
					{
						trigger_error('Rel type mismatch -- only these element types can be used with a one_to_many relationship: '.implode(', ',$single_item_element_types));
						return;
					}
				}
			}
			else
			{
				trigger_error($rel_id.' does not appear to be an allowable relationship ID');
				return;
			}
			
			if($limit_to_site)
			{
				$es = new entity_selector($this->get_value( 'site_id' ));
			}
			else
			{
				$es = new entity_selector();
			}
			$es->add_type($type_id);
			if($this->get_value('site_id'))
			{
				$es->set_env( 'site' , $this->get_value('site_id') );
			}
			$rel_es = carl_clone($es);
			
			if($direction == 'right')
			{
				$rel_es->add_right_relationship($this->get_value('id'),$rel_id);
			}
			else
			{
				$rel_es->add_left_relationship($this->get_value('id'),$rel_id);
				$rel_es->add_right_relationship_field('owns','entity','id','owner_id');
			}
			if(in_array($element_type, $single_item_element_types))
			{
				$rel_es->set_num(1);
			}
			//$rel_es->add_field('relationship','id','rel_id');
			if($sort=='smart')
			{
				$rel_es->set_order('entity.name ASC');
			}
			else
			{
				$rel_es->set_order($sort);
			}	
			$rel_es->add_field('relationship','site','rel_site_id');
			$related_entities = $rel_es->run_one();
			
			$related_keys = array();
			$untouchables = array();
			foreach($related_entities as $entity)
			{
				if($direction == 'right' || $entity->get_value('owner_id') == $this->get_value('site_id') || $entity->get_value('rel_site_id') == $this->get_value('site_id'))
				{
					$related_keys[] = $entity->id();
				}
				else
				{
					$untouchables[$entity->id()] = strip_tags($entity->get_display_name());
				}
			}
			
			if(!empty($untouchables))
			{
				$es->add_relation('entity.id NOT IN ('.implode(',',array_keys($untouchables)).')');
			}
			if($sort!='smart')
			{
				$es->set_order($sort);
			}
			$entities = $es->run_one();
			if($sort=='smart')
			{
				$sort_entities = $this->sort_entities_by_relationships($entities,$type_id,$rel_id,$smart_cutoff);
				$entities = $sort_entities['entities'];
				$first_cutoff = $sort_entities['first_cutoff'];
				if(!empty($first_cutoff))
				{
					$this->hidden_field_cutoffs[] = array($name,$first_cutoff);
				}
			}
			$values = array();
			foreach($entities as $id=>$entity)
			{
				if($id!=0)
				{
					$values[$entity->id()] = strip_tags($entity->get_display_name());
				}
				else
				{
					$values[0] = 'hidden_options';
				}
			}
			$args = array();
			if(!empty($element_types[$element_type]['args']))
			{
				$args = $element_types[$element_type]['args'];
			}
			$args['options'] = $values;
			$this->add_element($name , $element_types[$element_type]['plasmature_type'], $args );
			if(!empty($related_keys))
			{
				if(in_array($element_type, $single_item_element_types))
				{
					$this->set_value($name, current($related_keys));
				}
				else
				{
					$this->set_value($name, $related_keys);
				}
			}
			if($direction == 'right' && $rel_info['required'] == 'yes')
			{
				if(!empty($entities))
				{
					$this->add_required($name); 
				}
				else
				{
					$this->add_comments($name, '<em>None available</em>');
				}
			}
			if(!empty($untouchables))
			{
				$this->add_comments($name,'<div class="otherSiteRelation"><strong>Attached to this item by another site:</strong><ul class="smallText"><li>'.implode('</li><li>',$untouchables).'</li></ul></div>','before');
			}
			if(!empty($element_types[$element_type]['comment']))
			{
				$this->add_comments($name,form_comment($element_types[$element_type]['comment']));
			}
			$this->_relationship_elements[$name] = array('type_id'=>$type_id,'rel_id'=>$rel_id,'direction'=>$direction,'options'=>array_keys($entities),'related_at_prep'=>$related_keys );
			if(!in_array($name, $this->_no_tidy))
			{
				$this->_no_tidy[] = $name;
			}
			
		}
		
		function sort_entities_by_relationships($entities, $type_id,$rel_id,$cutoff)
		{
			$temp_entities = array();
			foreach($entities as $entity)
			{
				$es = new entity_selector($this->get_value('site_id'));
				$es->add_type($this->get_value('type'));
				$es->add_left_relationship($entity->get_value('id'),$rel_id);
				$count = $es->run_one();
				$checked = False;
				foreach($count as $id=>$entity_thing)
				{
					if($id == $this->get_value('id'))
					{
						$checked = True;
					}
				}
				$count = count($count);
				$temp_entities[] = array('entity'=>$entity,'count'=>$count,'checked'=>$checked);
			}
			usort($temp_entities,array($this,'_cmp_entities'));
			$entities = array();
			$first_cutoff_entity = '';
			$above_cutoff_entities = array();
			$below_cutoff_entities = array();
			$zero = false;
			foreach($temp_entities as $pair)
			{
				if($pair['count']>$cutoff)
				{
					$above_cutoff_entities[$pair['entity']->id()] = $pair['entity'];
				}
				else
				{
					
					if($pair['checked'])
					{
						$above_cutoff_entities[$pair['entity']->id()] = $pair['entity'];
					}
					else
					{
						if(!$zero)
						{
							$first_cutoff_entity = $pair['entity']->id();
							$zero = true;
						}	
						$below_cutoff_entities[$pair['entity']->id()] = $pair['entity'];
					}
					$this->head_items->add_javascript('/reason_package/reason_4.0/www/js/category_sort_disclosure.js');
				}
			}
			// array_merge doesn't preserve keys, array_replace doesn't preserve order, this is hacky but it does both
			if(empty($above_cutoff_entities))
			{
				$first_cutoff_entity = '';
			}
			foreach($above_cutoff_entities as $id=>$entity)
			{
				$entities[$id] = $entity;
			}
			foreach($below_cutoff_entities as $id=>$entity)
			{
				$entities[$id] = $entity;
			}
			return array('entities'=>$entities,'first_cutoff'=>$first_cutoff_entity);
		}

		function _cmp_entities($a,$b)
		{
			return $b['count']-$a['count'];
		}

		function _process_relationship_elements()
		{
			foreach($this->_relationship_elements as $name=>$info)
			{
				$this->_process_relationship_element($name,$info);
			}
		}
		function _get_rel_site_value($id)
		{
			$e = new entity($id);
			$owner = $e->get_owner();
			if($owner->id() == $this->get_value('site_id'))
			{
				return 0;
			}
			else
			{
				return $this->get_value('site_id');
			}
		}
		function _process_relationship_element($name,$info)
		{
			$values = $this->get_value($name);
			if(!is_array($values))
			{
				$values = array($values);
			}
			foreach($info['options'] as $id)
			{
				if(in_array($id,$values))
				{
					if(!in_array($id,$info['related_at_prep']))
					{
						// this neeeds a little more mojo to be site context aware
						if($info['direction'] == 'right')
						{
							create_relationship( $this->get_value('id'), $id, $info['rel_id']);
						}
						else
						{
							// if $id is not owned by current site, add site id to relationship
							create_relationship( $id, $this->get_value('id'), $info['rel_id'], array('site'=>$this->_get_rel_site_value($id) ) );
						}
					}
				}
				else
				{
					if(in_array($id,$info['related_at_prep']))
					{
						if($info['direction'] == 'right')
						{
							$conditions = array(
											'entity_a'=>$this->get_value('id'),
											'entity_b'=>$id,
											'type'=>$info['rel_id'],
											'site'=>0,
										);
						}
						else
						{
							$conditions = array(
											'entity_a'=>$id,
											'entity_b'=>$this->get_value('id'),
											'type'=>$info['rel_id'],
											'site'=>$this->_get_rel_site_value($id),
										);
						}
						delete_relationships( $conditions );
					}
				}
			}
		}
	}
?>
