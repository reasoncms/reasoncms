<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.7_to_4.8']['add_ck_editor'] = 'ReasonUpgrader_48_AddCKEditor';

class ReasonUpgrader_48_AddCKEditor implements reasonUpgraderInterface
{
	protected $user_id;

	public function user_id($user_id = NULL)
	{
		if(!empty($user_id))
			return $this->user_id = $user_id;
		else
			return $this->user_id;
	}
	/**
	* Get the title of the upgrader
	* @return string
	*/
	public function title()
	{
		return 'Add CKEditor';
	}

	/**
	* Get a description of what this upgrade script will do
	*
	* @return string HTML description
	*/
	public function description()
	{
		return '<p>This script adds CKEditor as an HTML editor option.</p>';
	}

	/**
	* Do a test run of the upgrader
	* @return string HTML report
	*/
	public function test()
	{
		if ($this->_editor_exists())
		{
			return '<p>CKEditor has already been added.</p>';
		}
		else
		{
			return '<p>CKEditor has not yet been added.</p>';
		}
	}

	protected function _editor_exists()
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('html_editor_type'));
		$result = $es->run_one();
		foreach ($result as $editor) {
			if ( in_array("CKEditor", $editor->get_values()) )
				$ret = true;
			else
				$ret = false;
		}
		return $ret;

	}

	/**
	* Run the upgrader
	*
	* @return string HTML report
	*/
	public function run()
	{
		if ( !$this->_editor_exists() )
		{
			$id = reason_create_entity( id_of('master_admin'), id_of('html_editor_type'), $this->user_id, 'CKEditor', array('html_editor_filename'=>'ck_editor.php'));
			if(!empty($id))
			{
				echo '<p>Adding CKEditor was successful</p>';
			}
		} else {
			echo '<p>CKEditor already exists: skipping.</p>';
		}
	}
}