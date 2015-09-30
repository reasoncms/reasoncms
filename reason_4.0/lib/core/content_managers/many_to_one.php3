<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register the content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'many_to_oneManager';

	/**
	 * A content manager that includes elements to select entities across all relationships
	 *
	 * This is very old code. Use the add_relationship_element() method
	 * instead of extending this class.
	 *
	 * @deprecated
	 * @todo Remove from the Reason core
	 */
	class many_to_oneManager extends ContentManager
	{	
		function prep_for_run( $site_id, $type_id, $id, $user_id ) // {{{
		{
			trigger_error('The many_to_one content manager is deprecated. Use add_relationship_element() instead.');
			parent::prep_for_run( $site_id, $type_id, $id, $user_id );
			$this->get_relationships();
		} // }}}
	function show_associations()  // {{{
	{
		//we don't want to show associations since they are already shown in a menu
	} // }}}
	function get_relationships() // {{{
		{
			$q = "SELECT id, relationship_b, name from allowable_relationship where relationship_a = " . $this->get_value( 'type_id' );
			$r = db_query( $q , "Error selecting relationships" );
			
			while( $row = mysql_fetch_array( $r ))
			{
				//get values for menu
				$entity = get_entities_by_type( $row[ 'relationship_b' ] );
				reset( $entity );
				$options = array();
				while( list( , $value ) = each( $entity ))
				{
					$options[ $value[ 'id' ] ] = $value[ 'name' ];
				}
				//get name of relationship
				if( $this->get_value( 'id' ) )
				{
					$q = "SELECT entity_b FROM relationship where entity_a = " . $this->get_value( 'id' ) . " AND type = " .
						 $row[ 'id' ];
					$r2 = db_query( $q , "error retrieving existing relationship" );
					if($row2 = mysql_fetch_array( $r2 ) )
						$default = $row2[ 'entity_b' ];
					mysql_free_result( $r2 );
				}
				else $default = 0;
				
				$this->add_element( $row[ 'name' ] , 'select' , array( 'default' => $default , 'options' => $options) );	
			}
			mysql_free_result( $r );
			$this->update_relationships();
		} // }}}
		function update_relationships() // {{{
		{
			//overload me
		} // }}}
		function delete_existing_relationships( $omit = false ) // {{{
		//if given a value, the elements of omit should contain the id's of omitted associations
		//this allows given elements to retains their values
		{
			$where = "";
			if( $omit )
			{
				reset( $omit );
				while( list( , $value ) = each ( $omit ) )
					$where .= " AND type != " . $value;
			}	
			
			$q = "DELETE from relationship where entity_a = " . $this->get_value( 'id' ) . $where;
			$r = db_query( $q , "Error selecting relationships" );
			
		} // }}}
		function add_new_relationships( $omit = false ) // {{{
		//if given a value, the elements of omit should contain the id's of omitted associations
		//this allows given elements to not be added
		{
			$where = "";
			if( $omit )
			{
				reset( $omit );
				while( list( , $value ) = each ( $omit ) )
					$where .= " AND id != " . $value;
			}
			
			$q = "SELECT id, name from allowable_relationship where relationship_a = " . $this->get_value( 'type_id' ). $where;
			$r = db_query( $q , "Error selecting relationships" );
			while( $row = mysql_fetch_array( $r ) )
			{
				if(($this->get_value( 'id' ) && $this->get_value( $row[ 'name' ] ) && $row[ 'id' ]))
				{
					create_relationship( $this->get_value( 'id' ) ,
							     $this->get_value( $row[ 'name' ] ),
							     $row['id']
							   );
				}
			}
		} // }}}
		function finish() // {{{
		{
			if( !$this->get_value( 'id' ) )
				$this->set_value( 'id' , mysql_insert_id() );
			$this->delete_existing_relationships();
			$this->add_new_relationships();
			return $this->CMfinish();
		} // }}}
	}
	
?>
