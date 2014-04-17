<?php
/**
 * @package reason
 * @subpackage classes
 * @author Nathan White
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
	 * @todo could generate dynamically by reading social directory - or maybe by reading a config file.
	 *
	 * @return array
	 */
	function get_available_integrators()
	{
		return array('facebook' => 'Facebook',
					 'twitter' => 'Twitter',
					 'googleplus' => 'Google+',
					 'youtube' => 'YouTube');
	}
	
	/**
	 * Returns the integrator class for an social_account entity.
	 *
	 * @return mixed object that implements the ReasonSocialIntegrator interface or boolean false
	 */
	function get_social_account_integrator($entity_id, $required_interface_support = NULL)
	{
		$social_integrator = new entity($entity_id);
		$social_integrator_type = $social_integrator->get_value('account_type');
		if ($integrator = $this->get_integrator($social_integrator_type))
		{
			if (is_null($required_interface_support) || in_array($required_interface_support, class_implements($integrator)))
			{
				return $integrator;
			}
		}
		return false;
	}
	
	/**
	 * Return all the integrators that implement a particular interface.
	 *
	 * @return array
	 */
	function get_social_integrators_by_interface($interface)
	{
		if (!isset($this->_integrators_by_interface[$interface]))
		{
			$integrators_by_interface[$interface] = array();
			$integrator_types = $this->get_available_integrators();
			foreach ($integrator_types as $integrator_type => $integrator)
			{
				if ($integrator = $this->get_integrator($integrator_type))
				{
					if (in_array($interface, class_implements($integrator)))
					{
						$this->_integrators_by_interface[$interface][$integrator_type] = $integrator;
					}
				}
			}
		}
		return $this->_integrators_by_interface[$interface];
	}
	
	/**
	 * @return mixed integrator object or false if it couldn't be loaded.
	 */
	function get_integrator($account_type, $required_interface_support = NULL)
	{
		if (empty($account_type))
		{
			trigger_error('The get_integrator account_type parameter cannot be empty');
			return false;
		}
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
}

abstract class ReasonSocialIntegrator implements SocialAccountContentManager
{
	/**
	 * Return the account_type field from the social account entity.
	 *
	 * @param int
	 * @return string
	 */
	public function get_profile_link_type($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		return $social_entity->get_value('account_type');
	}
	
	/**
	 * Return a 300x300 png from www/modules/social_account/images/ folder.
	 *
	 * The filename should correspond to the social account entity account_type value (plus .png).
	 *
	 * @param int
	 * @return string
	 */
	public function get_profile_link_icon($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		$account_type = $social_entity->get_value('account_type');
		return REASON_HTTP_BASE_PATH . 'modules/social_account/images/'.$account_type.'.png';
	}
	
	/**
	 * @param object
	 */
	public function social_account_on_every_time($cm)
	{
	}
	
	/**
	 * @param object
	 */
	public function social_account_pre_show_form($cm)
	{
	}
	
	/**
	 * @param object
	 */
	public function social_account_run_error_checks($cm)
	{
	}
}

/**
 * We define interfaces that a ReasonSocialIntegrator may implement.
 */
interface SocialAccountContentManager
{
	public function social_account_on_every_time($cm);
	public function social_account_pre_show_form($cm);
	public function social_account_run_error_checks($cm);
}

/**
 * If the social account provides profile links it should implement this interface.
 */
interface SocialAccountProfileLinks
{
	public function get_profile_link_type($social_entity_id);
	public function get_profile_link_icon($social_entity_id);
	public function get_profile_link_text($social_entity_id);
	public function get_profile_link_href($social_entity_id);
}

/**
 * If the social account provides sharing links it should implement this interface.
 */
interface SocialSharingLinks
{
	public function get_sharing_link_icon();
	public function get_sharing_link_text();
	public function get_sharing_link_href($url = NULL);
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
