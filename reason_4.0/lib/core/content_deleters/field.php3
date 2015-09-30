<?php
/**
 * @package reason
 * @subpackage content_deleters
 */
 	/**
 	 * Register deleter with Reason and include dependencies
 	 */
	$GLOBALS[ '_reason_content_deleters' ][ basename( __FILE__) ] = 'field_deleter';
	
	reason_include_once( 'classes/admin/admin_disco.php' );
	reason_include_once( 'classes/entity.php' );

	/**
	 * A content deleter that handles expungement of fields
	 */
	class field_deleter extends deleteDisco
	{
		function pre_show_form() // {{{
		{
			$e = new entity( $this->get_value( 'id' ) );
			if( $e->get_value( 'state' ) == 'Deleted' )
				$x = 'expunge';
			else $x = 'delete';
			echo '<h3>Do you really want to '.$x.' the field '.$e->get_value( 'name' ).'?</h3>'."\n";
			if($x == 'expunge')
			{
				echo '<p>Expunging this entity will delete all the data stored in the field. If you do not wish to lose the data in the field, you may wish to consider altering the type(s)\' content manager(s) to hide the field rather than deleting it from the database.</p>'."\n";
			}
			$tables = $e->get_relationship( 'field_to_entity_table' );
			
			if (!empty($tables))
			{
				$table = current($tables);
				$es = new entity_selector();
				$es->add_type(id_of('type'));
				$es->add_left_relationship($table->id(),relationship_id_of('type_to_table'));
				$types = $es->run_one();
				if(!empty($types))
				{
					echo '<p>Types currently using this field:</p>';
					echo '<ul>'."\n";
					foreach($types as $type)
					{
						echo '<li>'.$type->get_value('name').'</li>'."\n";
					}
					echo '</ul>'."\n";
				}
			}
		} // }}}
		function delete_entity() // {{{
		{
			$e = new entity( $this->get_value( 'id' ) );
			if($e->get_value('state') == 'Live' || $e->get_value('state') == 'Deleted')
			{
				$tmp = $e->get_relationship( 'field_to_entity_table' );
				if (!empty($tmp))
				{
					list( , $tmp ) = each( $tmp );
					$table_id = $tmp->id();
	
					$tables = get_entities_by_type_name( 'content_table' );

					$q = 'ALTER TABLE `'.$tables[ $table_id ][ 'name' ].'` DROP `'.$e->get_value( 'name' ).'`';
					db_query( $q, 'Unable to drop field from table.' );
				}
			}
			parent::delete_entity();
		} // }}}
	}
?>
