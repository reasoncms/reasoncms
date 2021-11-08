<?php

reason_include_once( 'entity_delegates/abstract.php' );
reason_include_once('classes/thor_to_gravity.php');

$GLOBALS['entity_delegates']['entity_delegates/form.php'] = 'formDelegate';

class formDelegate extends entityDelegate
{
	function get_gravity_forms_json_and_messages()
	{
		$transformer = new reasonFormToGravityJson();
		return array(
			'json' => $transformer->get_json($this->entity),
			'messages' => $transformer->get_messages(),
		);
	}
	function get_export_generated_data()
	{
		$ret = array();
		$gravity_data = $this->get_gravity_forms_json_and_messages();
		$ret['gravity_forms_json'] = $gravity_data['json'];
		$ret['gravity_forms_messages'] = implode("\n", $gravity_data['messages']);
		return $ret;
	}
}