<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.1_to_4.2']['remove_database_cruft'] = 'ReasonUpgrader_41_RemoveDatabaseCruft';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_41_RemoveDatabaseCruft implements reasonUpgraderInterface
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
		return 'Remove database cruft';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = "<p>This upgrade drops the page_cache_log_archive table, which has not been used in Reason for many versions.</p>";
		return $str;
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if(!$this->table_exists())
		{
			return '<p>This script has already run.</p>';
		}
		else
		{
			return '<p>Would drop the table page_cache_log_archive.</p>';
		}
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if(!$this->table_exists())
		{
			return '<p>This script has already run.</p>';
		}
		else
		{
			$table = 'page_cache_log_archive';
			$q = 'DROP TABLE IF EXISTS `page_cache_log_archive`';
  			$result = db_query($q);
  			
  			if (!$this->table_exists())
  			{
  				return '<p>Successfully dropped page_cache_log_archive.</p>';
  			}
  			else
  			{
  				return '<p>Dropping the table did not appear to work - you may have to manually run the query: DROP TABLE IF EXISTS `page_cache_log_archive`.</p>';
  			}
		}
	}
	
	protected function table_exists()
	{
		$table = 'page_cache_log_archive';
		$q = 'check table ' . $table . ' fast quick' or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
  		$result = db_query($q);
  		$results = mysql_fetch_assoc($result);
  		if (strstr($results['Msg_text'],"doesn't exist") ) $ret = false;
  		else return true;
	}
}
?>