<?php

reason_include_once( 'entity_delegates/abstract.php' );

$GLOBALS['entity_delegates']['entity_delegates/field.php'] = 'fieldDelegate';

class fieldDelegate extends entityDelegate
{
	function get_display_name()
	{
		if($tables = $this->entity->get_left_relationship('field_to_entity_table'))
		{
			$table = current($tables);
			return $table->get_value('name').'.'.$this->entity->get_value('name');
		}
		return $this->entity->get_value('name');
	}
}