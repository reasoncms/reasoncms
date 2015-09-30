<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.1_to_4.2']['facstaff_data_structure'] = 'ReasonUpgrader_42_FacstaffDataStructure';

class ReasonUpgrader_42_FacstaffDataStructure implements reasonUpgraderInterface
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
		return 'Simplify the Faculty/Staff data structure';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script moves the Faculty/Staff contents of the chunk table into the faculty_staff table for better database performance.</p>';
	}
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		$tables = get_entity_tables_by_type( id_of('faculty_staff'), false );
		if(!in_array('chunk',$tables))
		{
			return '<p>This upgrade has already been run.</p>';
		}
		else
		{
			return '<p>This upgrade has not yet been run. It will add new author and content fields to the faculty_staff table, move the content of those fields from the chunk table, and delete the faculty_staff rows from the chunk table.</p>
			<h4>Notes</h4>
			<ol>
			<li>It is especially important to make a backup of your Reason instance before running this script. An unexpected server falilure during this upgrade step will result in a corrupted Reason database.</li>
			<li>Turn off your administrative interface (by changing DISABLE_REASON_ADMINISTRATIVE_INTERFACE to true in settings/reason_settings.php) before running this script.</li>
			<li>Any local database queries on the Faculty/Staff type that reference the chunk table directly will need to be updated to either not reference a table (preferred) or to reference the faculty_staff table instead.</li>
			</ol>
			';
		}
	}
	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{
		$tables = get_entity_tables_by_type( id_of('faculty_staff'), false );
		if(!in_array('chunk',$tables))
		{
			return '<p>This upgrade has already been run. There is nothing to do.</p>';
		}
		else
		{
			// This should take considerably less time than one hour!
			// This should make sure things aren't killed partway through
			set_time_limit(3600); 
			$success = reason_move_table_fields( id_of('faculty_staff'), 'chunk', 'faculty_staff', $this->user_id() );
			if($success)
				return '<p>Successfully moved faculty/staff content in the chunk table to the faculty_staff table.</p>';
			else
			{
				$ret = '<p>Unable to move the content from the chunk to the faculty/staff table. Please look in your PHP error logs for information about the cause of this error. This error may cause problems with Reason modules or queries; we recommend restoring your latest backup, identifying and fixing the cause on a testing server, and re-running this upgrade.</p>';
				$err = error_get_last();
				if(!empty($err))
					$ret .= '<p>Last error: "'.htmlspecialchars($err['message']).'"</p>';
				return $ret;
			}
		}
	}
}
?>