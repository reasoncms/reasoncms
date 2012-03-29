<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.1_to_4.2']['db_type_data_structure'] = 'ReasonUpgrader_42_DBTypeDataStructure';

class ReasonUpgrader_42_DBTypeDataStructure implements reasonUpgraderInterface
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
		return 'Simplify the Database Type data structure';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return 'This script moves the Database Type contents of the meta, dated, and date_string tables into the db table for better database performance.';
	}
	
	protected function _get_tables_to_move()
	{
		return array('meta','dated','date_string');
	}
	
	protected function _get_unmoved_tables()
	{
		$unmoved_tables = array();
		
		$tables = get_entity_tables_by_type( id_of('database_type'), false );
		
		foreach($this->_get_tables_to_move() as $t)
		{
			if(in_array($t,$tables))
				$unmoved_tables[] = $t;
		}
		
		return $unmoved_tables;
	}
	
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		$ret = '';
		
		$notes = '<h4>Notes</h4><ol><li>It is especially important to make a backup of your Reason instance before running this script. An unexpected server failure during this upgrade step will result in a corrupted Reason database.</li>
		<li>We recommend that you turn off your administrative interface (by changing DISABLE_REASON_ADMINISTRATIVE_INTERFACE to true in settings/reason_settings.php) before running this script.</li>
		<li>Any database queries for external css in local Reason modifications that reference the url or meta table directly will need to be updated to either not reference a table (preferred) or to reference the external_css table instead.</li></ol>'."\n";
		
		$unmoved_tables = $this->_get_unmoved_tables();
		
		if(empty($unmoved_tables))
		{
			$ret .= '<p>This script has already been run.</p>'."\n";
		}
		elseif(count($unmoved_tables) < count($this->_get_tables_to_move()))
		{
			$ret .= '<p>This script is partially run. The following tables still need to be moved: '.implode(', ',$unmoved_tables).'</p>'."\n";
			$ret .= $notes;
		}
		else
		{
			$ret .= '<p>This script has not been run. It will move the following tables: '.implode(', ',$unmoved_tables).'</p>'."\n";
			$ret .= $notes;
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
		
		$unmoved_tables = $this->_get_unmoved_tables();
		
		if(empty($unmoved_tables))
		{
			$ret .= '<p>This upgrade has already been run. There is nothing to do.</p>';
		}
		else
		{
			foreach($unmoved_tables as $table)
			{
				set_time_limit(3600);
				$ret .= $this->_move_table_fields($table);
			}
		}
		return $ret;
	}
	
	protected function _move_table_fields($from)
	{
		$ret = '';
		set_time_limit(3600); 
		$success = reason_move_table_fields( id_of('database_type'), $from, 'db', $this->user_id() );
		if($success)
			$ret .= '<p>Successfully moved Database Type content in the '.$from.' table to the db table.</p>';
		else
		{
			$ret = '<p>Unable to move the content from the '.$from.' table to the db table. Please look in your PHP error logs for information about the cause of this error. This error may cause problems with Reason modules or queries; we recommend restoring your latest backup, identifying and fixing the cause on a testing server, and re-running this upgrade.</p>';
			$err = error_get_last();
			if(!empty($err))
				$ret .= '<p>Last error: "'.htmlspecialchars($err['message']).'"</p>';
		}
		return $ret;
	}
}
?>