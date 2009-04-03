<?php
/**
 * @package reason
 * @subpackage content_deleters
 */
	/**
	 * Register deleter with Reason & include parent class
	 */
	$GLOBALS[ '_reason_content_deleters' ][ basename( __FILE__) ] = 'entity_table_deleter';

	reason_include_once( 'classes/admin/admin_disco.php' );

	/**
	 * A content deleter for entity tables
	 *
	 * This deleter drops tables when they are expunged from Reason
	 *
	 * This deleter throws an error and dies if a table that is part of a Reason type or is otherwise
	 * protected.
	 */
	class entity_table_deleter extends deleteDisco
	{
		function delete_entity() // {{{
		{
			$e = new entity( $this->get_value( 'id' ) );
			// don't drop table if this is dending or archived -- this might just be a duplicate
			if($e->get_value('state') == 'Live' || $e->get_value('state') == 'Deleted')
			{
				if(in_array($e->get_value('name'), reason_get_protected_tables()) )
				{
					trigger_error('Unable to zap protected table ('.$e->get_value('name').')');
					die();
				}
				if($e->has_right_relation_of_type('type_to_table'))
				{
					trigger_error('Unable to zap Live or Deleted entity table that is part of a type ('.$e->get_value('name').')');
					die();
				}
				$r = db_query( 'SHOW TABLES' );
				while($row = mysql_fetch_array($r, MYSQL_ASSOC))
				{
					$all_tables[] = current($row);
				}
				if(in_array($e->get_value('name'), $all_tables) )
				{
					$r = db_query( 'DROP TABLE `'.$e->get_value('name').'`' );
				}
			}
			parent::delete_entity();
		} // }}}
	}
?>
