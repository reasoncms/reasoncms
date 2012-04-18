<?php
/**
 * @package reason
 * @subpackage classes
 */

/**
 * Interface for upgrader classes
 */
interface reasonUpgraderInterface
{
	/**
 	 * Set and get the Reason ID of the current user
	 * @param integer $user_id
	 * @return integer $user_id
	 */
	function user_id($user_id = NULL);
	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title();
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description();
	/**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test();
	/**
	 * Run the upgrader
	 * @return string HTML report
	 */
	public function run();
}

/**
 * @package reason
 * @subpackage classes
 */

/**
 * Interface for upgrader information classes
 */
interface reasonUpgraderInfoInterface
{
	/**
	 * Output information.
	 * @return string HTML report
	 */
	public function run();
}
?>
