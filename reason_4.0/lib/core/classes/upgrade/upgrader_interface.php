<?php
/**
 * Interfaces and abstract classes for upgraders.
 * 
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
 * Interface for upgrader classes
 *
 * Upgraders that support this interface are standalone upgraders that are provided a disco form and must declare these methods:
 *
 * - init (given a disco form, the upgrader should define callbacks at appropriate stages)
 */
interface reasonUpgraderInterfaceAdvanced
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
	/**
	 * Init the upgrader
	 * @return string HTML report
	 */
	public function init($disco, $head_items);
}

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

/**
 * The user_id function is the same in every upgrader so lets make an abstract class we can extend
 * if we don't want to copy and paste the user_id function every time.
 */
abstract class reasonUpgraderDefault
{
	/**
 	 * Set and get the Reason ID of the current user
	 * @param integer $user_id
	 * @return integer $user_id
	 */
	function user_id($user_id = NULL)
	{
		if(!empty($user_id))
		{
			return $this->user_id = $user_id;
		}
		else
		{
			return $this->user_id;
		}
	}	
}