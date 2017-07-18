<?php

reason_include_once( 'entity_delegates/abstract.php' );
reason_include_once( 'classes/event_repeater.php' );

$GLOBALS['entity_delegates']['entity_delegates/event.php'] = 'eventDelegate';

/**
 */
class eventDelegate extends entityDelegate
{
	function get_occurrence_dates($from_definition = false)
	{
		if($from_definition)
		{
			$repeater = new reasonEventRepeater();
			$repeater->set_values($this->entity->get_values());
			return $repeater->get_occurrence_dates();
		}
		return $this->entity->get_value('occurrence_dates');
	}
}