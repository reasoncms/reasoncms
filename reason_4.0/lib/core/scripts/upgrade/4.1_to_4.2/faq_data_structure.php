<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.1_to_4.2']['faq_data_structure'] = 'ReasonUpgrader_42_FAQDataStructure';

class ReasonUpgrader_42_FAQDataStructure implements reasonUpgraderInterface
{
	protected $user_id;
	public function user_id( $user_id = NULL)
	{
		if(!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}
	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return 'Simplify the FAQ data structure';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script creates an FAQ table and moves the FAQ contents of the meta, dated, and chunk tables into the it for better database performance.</p>';
	}
	
	protected function _destination_table()
	{
		return 'faq';
	}
	
	protected function _source_tables()
	{
		return array('meta','dated','chunk');
	}
	
	protected function _type_unique_name()
	{
		return 'faq_type';
	}
	protected function _type_id()
	{
		return id_of($this->_type_unique_name());
	}
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		$ret = '';
		
		$tables = get_entity_tables_by_type( $this->_type_id(), false );
		
		if(!in_array($this->_destination_table(),$tables))
		{
			$ret .= '<p>Will add the '.$this->_destination_table().' table.</p>';
		}
		
		$to_move = array_intersect($this->_source_tables(), $tables);
		
		if(!empty($to_move))
		{
			$ret .= '<p>Will move the '.$this->_type_unique_name().' contents of these tables into the '.$this->_destination_table().' table:</p>';
			$ret .= '<ul>';
			foreach($to_move as $table)
			{
				$ret .= '<li>'.$table.'</li>';
			}
			$ret .= '</ul>';
			$ret .= '<h4>Notes</h4><ol><li>It is especially important to make a backup of your Reason instance before running this script. An unexpected server failure during this upgrade step will result in a corrupted Reason database.</li>
			<li>We recommend that you turn off your administrative interface (by changing DISABLE_REASON_ADMINISTRATIVE_INTERFACE to true in settings/reason_settings.php) before running this script.</li>
			<li>Any database queries for '.$this->_type_unique_name().' in local Reason modifications that reference the tables being moved will need to be updated to either not reference a table (preferred) or to reference the '.$this->_destination_table().' table instead.</li></ol>';
		}
		else
		{
			$ret .= '<p>The table move has been run.</p>';
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
		$ret = '';
		
		$tables = get_entity_tables_by_type( $this->_type_id(), false );
		
		if(!in_array($this->_destination_table(),$tables))
		{
			$ret .= $this->_add_table($this->_destination_table());
		}
		
		$to_move = array_intersect($this->_source_tables(), $tables);
		
		if(!empty($to_move))
		{
			foreach($to_move as $table)
			{
				$ret .= $this->_move_table_fields($table);
			}
		}
		
		if(empty($ret))
		{
			$ret .= '<p>This upgrade has already been run. There is nothing to do.</p>';
		}
		return $ret;
		
	}
	
	protected function _add_table($table_name)
	{
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "'.addslashes($table_name).'"');
		$es->set_num(1);
		$results = $es->run_one();
		
		if(empty($results))
		{
			create_reason_table($table_name, $this->_type_unique_name(), $this->user_id());
			$ret = '<p>Created the '.$table_name.' table.</p>';
		}
		else
		{
			$table_entity = current($results);
			create_relationship( $this->_type_id(), $table_entity->id(), relationship_id_of('type_to_table'));
			
			$ret = '<p>Added the '.$table_name.' table to the '.$this->_type_unique_name().' type.</p>'."\n";
		}
		
		reason_include_once('classes/amputee_fixer.php');
		$fixer = new AmputeeFixer();
		$fixer->fix_amputees($this->_type_id());
		
		return $ret;
	}
	
	protected function _move_table_fields($from)
	{
		$ret = '';
		set_time_limit(3600); 
		$success = reason_move_table_fields( $this->_type_id(), $from, $this->_destination_table(), $this->user_id() );
		if($success)
			$ret .= '<p>Successfully moved '.$this->_type_unique_name().' content in the '.$from.' table to the '.$this->_destination_table().' table.</p>';
		else
		{
			$ret = '<p>Unable to move the content from the '.$from.' table to the '.$this->_destination_table().' table. Please look in your PHP error logs for information about the cause of this error. This error may cause problems with Reason modules or queries; we recommend restoring your latest backup, identifying and fixing the cause on a testing server, and re-running this upgrade.</p>';
			$err = error_get_last();
			if(!empty($err))
				$ret .= '<p>Last error: "'.htmlspecialchars($err['message']).'"</p>';
		}
		return $ret;
	}
}
?>