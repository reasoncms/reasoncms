<?php
/**
 * A content manager for database fields
 * @package reason
 * @subpackage content_managers
 */
 
 /**
  * Include dependencies
  */
	reason_include_once( 'content_managers/associator.php3' );
	
 /**
  * Store the class name so that the admin page can use this content manager
  */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'FieldManager';
	
	/**
	 * A content manager for database fields
	 *
	 * @todo Stop inheriting from the associator manager so we can remove the associator manager entirely -- 
	 *       use the add_relationship_element() functionality built in to all content managers 
	 *       to take care of the field-to-table relationship.
	 */
	class FieldManager extends associatorManager
	{
		function alter_data() // {{{
		{
			$this->add_required( 'field_to_entity_table' );
			$this->add_required( 'db_type' );
		} // }}}
		function on_every_time() // {{{
		{

			$this->change_element_type( 'is_required','select_no_sort',array('options'=>array('0'=>'false','1'=>'true') ) );
			$this->change_element_type( 'admin_only','select_no_sort',array('options'=>array('0'=>'false','1'=>'true') ) );

			if( $this->_id )
			{
				$tmp = new entity( $this->_id );
				$this->original = $tmp->get_values();
				$foo = $tmp->get_relationship( 'field_to_entity_table' );
				if( $foo )
				{
					list( ,$gar ) = each( $foo );
					$this->original_rel = $gar->get_values();
				}
				else $this->original_rel = array();
			}
		} // }}}
		function finish() // {{{
		{
			// alter tables accordingly
			$tables = get_entities_by_type_name( 'content_table' );
			if( empty($this->original_rel['id']) || $this->get_value( 'field_to_entity_table' ) != $this->original_rel['id'] )
			{
				if( !$this->is_new_entity() )
				{
					$q = "ALTER TABLE ".$tables[ $this->original_rel['id'] ]['name']." DROP ".$this->original['name'];
					$r = db_query( $q, 'Umm.  Something bad happened.  Could not drop column from original table' );
				}
				$q = "ALTER TABLE ".$tables[ $this->get_value('field_to_entity_table')]['name']." ADD ".$this->get_value('name').' '.$this->get_value( 'db_type' );
				$r = db_query( $q, 'Umm.  Something _really_ bad happened. Dropped original column but could not add column to new table.  All data from original table\'s column lost.' );
			}
			elseif( ($this->get_value( 'db_type' ) != $this->original['db_type']) OR ($this->get_value( 'name' ) != $this->original['name']) )
			{
				$q = 'ALTER TABLE '.$tables[ $this->get_value( 'field_to_entity_table' ) ][ 'name' ].' CHANGE '.$this->original['name'].' '.$this->get_value('name').' '.$this->get_value('db_type');
				db_query( $q, 'Unable to change column.' );
			}

			// run associator finish actions
			if( !$this->get_value( 'id' ) )
				$this->set_value( 'id' , $this->_inserted_id );
			$this->delete_existing_relationships();
			$this->add_new_relationships();
		
			return $this->CMfinish();
		} // }}}
	}