<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/upgrade/denormalization_helper.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.1_to_4.2']['policy_data_structure'] = 'ReasonUpgrader_42_PolicyDataStructure';

class ReasonUpgrader_42_PolicyDataStructure implements reasonUpgraderInterface
{
	protected $user_id;
	protected $helper;
	protected $approvals_policies;
	
	public function user_id( $user_id = NULL)
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
		return 'Simplify and upgrade the Policy data structure';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script simplifies and upgrades the Policy data structure.</p>';
	}
	
	protected function _destination_table()
	{
		return 'policies';
	}
	
	protected function _source_tables()
	{
		return array('meta','dated','chunk','show_hide','list_styles');
	}
	
	protected function _type_unique_name()
	{
		return 'policy_type';
	}
	protected function _get_helper()
	{
		if(!isset($this->helper))
		{
			$this->helper = new denormalizationUpgradeHelper();
			$this->helper->user_id($this->user_id());
			$this->helper->destination_table($this->_destination_table());
			$this->helper->source_tables($this->_source_tables());
			$this->helper->type_unique_name($this->_type_unique_name());
		}
		return $this->helper;
	}
	
	
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		$helper = $this->_get_helper();
		$message = $helper->test();
		
		$message .= $this->_test_allowable_relationships();
		
		$message .= $this->_test_fields();
		
		$message .= $this->_test_approvals();
		
		return $message;
	}
	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{
		$helper = $this->_get_helper();
		$message = 'The field mover says:'.$helper->run();
		$message .= $this->_create_allowable_relationships();
		$message .= $this->_add_fields();
		$message .= $this->_migrate_approvals();
		return $message;
	}
	
	protected function _test_allowable_relationships()
	{
		$rels = array('policy_to_access_group','policy_to_relevant_audience','policy_to_responsible_department');
		$to_create = array();
		foreach($rels as $rel)
		{
			if(!relationship_id_of( $rel, false, false))
				$to_create[] = $rel;
		}
		if(empty($to_create))
		{
			return '<p>Relationships already exist. They do not need to be created.</p>';
		}
		return '<p>The following relationships will be created: '.implode(', ',$to_create).'</p>';
	}
	protected function _create_allowable_relationships()
	{
		$msg = '';
		if(!relationship_id_of( 'policy_to_access_group', false, false))
		{
			create_allowable_relationship( id_of('policy_type'), id_of('group_type'), 'policy_to_access_group', array('required'=>'no','connections'=>'one_to_many','display_name'=>'Access Group','is_sortable'=>'no','custom_associator'=>'Content Manager'));
			$msg .= '<p>Created the policy_to_access_group allowable relationship</p>'."\n";
		}
		
		if(!relationship_id_of( 'policy_to_relevant_audience', false, false))
		{
			create_allowable_relationship( id_of('policy_type'), id_of('audience_type'), 'policy_to_relevant_audience', array('required'=>'no','connections'=>'many_to_many','is_sortable'=>'no','custom_associator'=>'Content Manager'));
			$msg .= '<p>Created the policy_to_relevant_audience allowable relationship</p>'."\n";
		}
		
		if(!relationship_id_of( 'policy_to_responsible_department', false, false))
		{
			create_allowable_relationship( id_of('policy_type'), id_of('office_department_type'), 'policy_to_responsible_department', array('required'=>'no','connections'=>'one_to_many','display_name'=>'Responsible Department','is_sortable'=>'no'));
			$msg .= '<p>Created the policy_to_responsible_department allowable relationship</p>'."\n";
		}
		if(empty($msg))
			$msg .= '<p>The allowable relationships already exist, so none were created.</p>'."\n";
		return $msg;
	}
	
	protected function _test_fields()
	{
		$unadded_fields = array();
		$updater = new FieldToEntityTable('policies');
		foreach($this->_get_fields_to_add() as $field_name=>$field_info)
		{
			if(!$updater->field_exists($field_name))
				$unadded_fields[] = $field_name;
		}
		if(empty($unadded_fields))
		{
			return '<p>All fields added.</p>'."\n";
		}
		return '<p>These fields will be added to the policy type: '.implode(', ',$unadded_fields).'</p>'."\n";
	}
	protected function _add_fields()
	{
		$updater = new FieldToEntityTable('policies', $this->_get_fields_to_add());
		$updater->update_entity_table();
		ob_start();
		$updater->report();
		return ob_get_clean();
	}
	protected function _get_approvals_policies()
	{
		if(is_null($this->approvals_policies))
		{
			$es = new entity_selector();
			$es->add_type(id_of('policy_type'));
			$es->add_relation('( datetime > "0000-00-00 00:00:00" OR ( author != "" AND author IS NOT NULL ) )');
			$es->limit_tables(array('entity','policies'));
			$this->approvals_policies = array_merge($es->run_one(), $es->run_one('','Pending'), $es->run_one('','Deleted'), $es->run_one('','Archived') );
		}
		return $this->approvals_policies;
	}
	protected function _test_approvals()
	{
		$policies_table_entity = $this->_get_policies_table_entity();
		if(empty($policies_table_entity))
		{
			return '<p>Approvals data will be migrated to new "approvals" free text field.</p>'."\n";
		}
		$raw_fields = get_fields_by_content_table( 'policies', false );
		$fields_deleted = false;
		if(!in_array('datetime',$raw_fields) || !in_array('author',$raw_fields))
			$fields_deleted = true;
		
		if(!$fields_deleted)
			$policies = $this->_get_approvals_policies();
		else
			$policies = array();
		
		if(!empty($policies))
			return '<p>Approvals data will be migrated to new "approvals" free text field.</p>'."\n";
		elseif(!$fields_deleted)
			return '<p>Approvals data migrated, but old datetime & author fields not deleted. These fields will be deleted on run.</p>'."\n";
		return '<p>Approvals data migration already done.</p>'."\n";
	}
	protected function _migrate_approvals()
	{
		$raw_fields = get_fields_by_content_table( 'policies', false );
		if(in_array('datetime',$raw_fields) && in_array('author',$raw_fields))
			$policies = $this->_get_approvals_policies();
		else
			$policies = array();
		$msg = '';
		foreach($policies as $policy)
		{
			$str = $this->_get_approvals_string($policy);
			if(!empty($str))
			{
				reason_update_entity($policy->id(), $this->user_id, array('approvals'=>$str,'datetime'=>'','author'=>'',), false);
				$msg = '<p>Moved all datetime/author data into new approvals field</p>'."\n";
			}
		}
		if(empty($msg))
			$msg .= '<p>No policies needed their approvals field migrated.</p>'."\n";
		
		$policies_table_entity = $this->_get_policies_table_entity();
		if(empty($policies_table_entity))
		{
			trigger_error('Upgrade script has has a major error. The policies table should exist, but does not!');
			$msg .= '<p>There has been a major error in this upgrade. The policies table should have been created, but it has not. Please restore your Reason instance from a backup and try troubleshooting.</p>'."\n";
			die();
		}
		
		$es = new entity_selector();
		$es->add_type(id_of('field'));
		$es->add_left_relationship($policies_table_entity->id(), relationship_id_of('field_to_entity_table'));
		$es->add_relation('name = "datetime"');
		$es->set_num(1);
		$datetime_fields = $es->run_one();
		
		if(!empty($datetime_fields))
		{
			$datetime_field = current($datetime_fields);
			reason_expunge_entity($datetime_field->id(), $this->user_id);
			$msg .= '<p>Deleted the policies.datetime field entity.</p>'."\n";
		}
		
		if(in_array('datetime',$raw_fields))
		{
			$q = 'ALTER TABLE `policies` DROP `datetime`';
			db_query( $q, 'Unable to drop datetime field from policies table.' );
			$msg .= '<p>Dropped the datetime field from the policies table.</p>'."\n";
		}
		
		$es = new entity_selector();
		$es->add_type(id_of('field'));
		$es->add_left_relationship($policies_table_entity->id(), relationship_id_of('field_to_entity_table'));
		$es->add_relation('name = "author"');
		$es->set_num(1);
		$author_fields = $es->run_one();
		
		if(!empty($author_fields))
		{
			$author_field = current($author_fields);
			reason_expunge_entity($author_field->id(), $this->user_id);
			$msg .= '<p>Deleted the policies.author field entity.</p>'."\n";
		}
		
		if(in_array('author',$raw_fields))
		{
			$q = 'ALTER TABLE `policies` DROP `author`';
			db_query( $q, 'Unable to drop author field from policies table.' );
			$msg .= '<p>Dropped the author field from the policies table.</p>'."\n";
		}
		return $msg;
	}
	
	protected function _get_policies_table_entity()
	{
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "policies"');
		$es->set_num(1);
		$tables = $es->run_one();
		if(empty($tables))
			return null;
		return current($tables);
	}
	
	protected function _get_approvals_string($policy)
	{
		$str = '';
		if($policy->get_value('author') || $policy->get_value('datetime') > '0000-00-00 00:00:00')
		{
			$str .= '<p>Approved';
			if($policy->get_value('author'))
				$str .= ' by '.$policy->get_value('author');
			if($policy->get_value('datetime') > '0000-00-00 00:00:00')
				$str .= ' on '.prettify_mysql_datetime($policy->get_value( 'datetime' ), 'F j, Y');
			$str .= '.</p>'."\n";
		}
		return $str;
	}
	
	protected function _get_fields_to_add()
	{
		return array('approvals' => array('db_type' => 'text'),
						'last_revised_date' => array('db_type' => 'date'),
						'last_reviewed_date' => array('db_type' => 'date'),
				);
	}
}
?>