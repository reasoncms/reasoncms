<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.2_to_4.3']['remove_loki_1'] = 'ReasonUpgrader_43_RemoveLoki1';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

/**
 * This script removes the Loki 1 HTML Editor from the database.
 *
 * @author Nathan White
 */
class ReasonUpgrader_43_RemoveLoki1 implements reasonUpgraderInterface
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
		return 'Remove Loki 1 HTML Editor';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = "<p>This upgrade removes the Loki 1 HTML Editor entity from the Master Admin site. Any sites using it will revert to using the default HTML editor.</p>";
		return $str;
	}
	
	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{
		if ($loki_1_entity = $this->get_loki_1_entity())
		{
			return '<p>Would delete the loki 1 html editor entity.</p>';
			
		}
		else return '<p>Your instance doesn\'t have the loki 1 html editor entity - this script has probably been run.</p>';
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if ($loki_1_entity = $this->get_loki_1_entity())
		{
			reason_expunge_entity($loki_1_entity->id(), $this->user_id());
			return '<p>Deleted the loki 1 html editor entity.</p>';
		}
		else return '<p>Your instance doesn\'t have the loki 1 html editor entity - this script has probably been run.</p>';
	}
	
	/**
	 * @return object loki 1 entity if it exists
	 */
	protected function get_loki_1_entity()
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('html_editor_type'));
		$es->add_relation('entity.name = "Loki 1"');
		$es->set_num(1);
		$result = $es->run_one();
		return (!empty($result)) ? reset($result) : FALSE;
	}
}
?>