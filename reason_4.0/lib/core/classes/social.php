<?php
/**
 * @package reason
 * @subpackage classes
 */

/**
 * Load dependencies.
 */
include_once('reason_header.php');

/**
 * A class that provides ReasonCMS with social integration.
 *
 * Integration classes should be in the social folder, and the filename must be the key of the integrator 
 * returned by the get_available_integrators method, followed by the php extension (eg - facebook.php).
 *
 * @todo add integration with something in config so you can turn on / off integrators and add local ones.
 */
class ReasonSocialIntegrationHelper
{
	/**
	 * Returns an array describing available social accounts.
	 *
	 * @return array
	 */
	function get_available_integrators()
	{
		return array('facebook' => 'FaceBook');
	}
	
	/**
	 * @return mixed integrator object or false if it couldn't be loaded.
	 */
	function get_integrator($account_type)
	{
		if (!isset($this->_integrators[$account_type]))
		{
			if (reason_file_exists('classes/social/'.$account_type.'.php'))
			{
				reason_include_once('classes/social/'.$account_type.'.php');
				if (isset($GLOBALS[ '_social_integrator_class_names' ][ $account_type ]))
				{
					$this->_integrators[$account_type] = new $GLOBALS[ '_social_integrator_class_names' ][ $account_type ]();
				}
				else
				{
					trigger_error('The integrator could not be instantiated - it may not be registering itself properly.');
					$this->_integrators[$account_type] = false;
				}
			}
			else
			{
				trigger_error('The integrator for account type ' . $account_type . ' could not be found.');
				$this->_integrators[$account_type] = false;
			}
		}
		return $this->_integrators[$account_type];
	}
	
	/**
	 * Adds the elements we need for facebook integration setup to a disco form.
	 */
	function on_every_time(&$disco)
	{
	
	}
	
	function run_error_checks(&$disco)
	{
	
	}
	
	/**
	 * @return json
	 */
	function process(&$disco)
	{
	
	}
}

/**
 * Get the singleton social integration object
 *
 * @return object
 */
function reason_get_social_integration_helper()
{
	static $si;
	if(empty($si))
	{
		$si = new ReasonSocialIntegrationHelper();
	}
	return $si;
}
?>