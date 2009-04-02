<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	/**
	 * Register previewer with Reason
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'type_previewer';

	/**
	 * A content previewer for types
	 *
	 * includes a handy display of the tables and fiels that make up the type
	 */
	class type_previewer extends default_previewer
	{
		function post_show_entity()
		{
			echo '<h3>Cookie Cutter</h3>';

			// the name is the only relevant field included by default, so it is hard-coded
			echo "\n".'<table border="0" cellpadding="2" cellspacing="0"><tr><th class="listRow1" align="right">entity table:</th><th class="listRow1" align="left">field &lt;database type&gt;</th></tr><tr><td class="listRow2" align="right">&nbsp;<strong>default:</strong></td><td class="listRow2" align="left"><table border="0" cellpadding="2" cellspacing="0"><tr><td class="listRow2" align="right">name</td><td class="listRow2">&nbsp;&nbsp;</td><td class="listRow2" align="left">&lt;tinytext&gt;</td></tr></table></td></tr>';

			// get all of this type's entity tables
			$ets = new entity_selector( $this->admin_page->site_id );
			$ets->description = "Get entity tables associated with the type.";
			$ets->add_type( id_of('content_table') );
			$ets->add_right_relationship($this->_entity->id(), relationship_id_of('type_to_table') );
			$entities = $ets->run_one();

			$a = 0;
			// get the fields of each entity table
			foreach( $entities AS $entity )
			{
				$fs = new entity_selector();
				$fs->description = "Get fields associated with the entity table.";
				$fs->add_type( id_of('field') );
				$fs->add_left_relationship( $entity->id(), relationship_id_of('field_to_entity_table') );
				$fields = $fs->run_one();

				echo '<tr><td class="listRow'.($a%2+1).'" align="right">&nbsp;<strong>'.$entity->get_value('name').':</strong></td><td class="listRow'.($a%2+1).'" align="left"><table border="0" cellpadding="2" cellspacing="0">';
				$b = 0;
				// output each field and its database type
				foreach( $fields AS $field )
				{
					echo '<tr><td class="listRow'.($b%2+1).'" align="right">'.$field->get_value('name').'</td><td class="listRow'.($b%2+1).'" align="left">&lt;'.$field->get_value('db_type').'&gt;</td></tr>';
					$b++;
				}
				echo '</table></tr>';
				$a++;
			}
			echo '<br />';

			/*echo '<h3>Right Relationships</h3>';
			$right_rels = $this->_entity->get_right_relationships(); 

			foreach( $right_rels AS $key => $r )
			{
				if( !empty( $r ) )
				{
					if( !is_int( $key ) )
					{
						echo '<strong>'.$key.'</strong><br />';
						foreach( $r AS $actual_rel )
						{
							echo $actual_rel->get_value( 'name' ).'<br />';
						}
						echo '<br />';
					}
				}
			} */
		}
	}
?>
