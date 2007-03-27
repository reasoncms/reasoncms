<?php

	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'EntityTableManager';
	
	class EntityTableManager extends ContentManager
	{
		function finish()
		{
			if( $this->is_new_entity() )
			{
				$q = "CREATE TABLE ".$this->get_value('name')." (id int unsigned primary key)" ;
				db_query( $q, 'Unable to create new table' );
			}
			
			return $this->CMfinish();
		}
	}
?>
