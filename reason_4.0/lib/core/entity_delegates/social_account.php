<?php
/**
 * A delegate for the social account type
 * @package reason
 * @subpackage entity_delegates
 */

/**
 * Include dependencies
 */
reason_include_once( 'entity_delegates/abstract.php' );
reason_include_once( 'classes/social.php' );

/**
 * Register delegate
 */
$GLOBALS['entity_delegates']['entity_delegates/social_account.php'] = 'socialAccountDelegate';

/**
 * A delegate for the social account type
 */
class socialAccountDelegate extends entityDelegate
{
	protected $integrator;
	/**
	 * Get the integrator class for this social account
	 */
	function get_integrator($required_interface_support = NULL)
	{
		if(!isset($this->integrator))
		{
			if($account_type = $this->entity->get_value('account_type'))
			{
				$helper = reason_get_social_integration_helper();
				$this->integrator = $helper->get_integrator($account_type, $required_interface_support);
			}
			else
			{
				$this->integrator = false;
			}
		}
		return $this->integrator;
	}
	/**
	 * Get the display name for this social account
	 */
	function get_display_name()
	{
		if($integrator = $this->entity->get_integrator('SocialAccountProfileLinks'))
		{
			if($url = $integrator->get_profile_link_icon($this->entity->id()))
			{
				$helper = reason_get_social_integration_helper();
			    $integrators = $helper->get_available_integrators();
			    $alt = isset($integrators[$account_type]) ? $integrators[$account_type] : $account_type;
			    return '<img src="'.reason_htmlspecialchars($url).'" alt="'.reason_htmlspecialchars($alt).'" height="24" width="24" /> '.$this->entity->get_value('name');
			}
		}
		return $this->entity->get_value('name');
	}
}