<?php
	//$deleter = 'entity_table_deleter';
	$GLOBALS[ '_reason_content_deleters' ][ basename( __FILE__) ] = 'entity_table_deleter';

	reason_include_once( 'classes/admin/admin_disco.php' );

	class entity_table_deleter extends deleteDisco
	{
		function delete_entity() // {{{
		{
			$e = new entity( $this->get_value( 'id' ) );
			// don't drop table if this is dending or archived -- this might just be a duplicate
			if($e->get_value('state') == 'Live' || $e->get_value('state') == 'Deleted')
			{
				if($e->has_right_relation_of_type('type_to_table'))
				{
					trigger_error('Unable to zap Live or Deleted entity table that is part of a type.');
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
