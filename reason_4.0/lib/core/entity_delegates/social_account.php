<?php

reason_include_once( 'entity_delegates/abstract.php' );
reason_include_once( 'classes/social.php' );

$GLOBALS['entity_delegates']['entity_delegates/social_account.php'] = 'socialAccountDelegate';

class socialAccountDelegate extends entityDelegate
{
	function get_display_name()
	{
		if($account_type = $this->entity->get_value('account_type'))
		{
			$helper = new ReasonSocialIntegrationHelper();
			$integrator = $helper->get_integrator($account_type, 'SocialAccountProfileLinks');
			if($url = $integrator->get_profile_link_icon($this->entity->id()))
			{
			    $integrators = $helper->get_available_integrators();
			    $alt = isset($integrators[$account_type]) ? $integrators[$account_type] : $account_type;
			    return '<img src="'.reason_htmlspecialchars($url).'" alt="'.reason_htmlspecialchars($alt).'" height="24" width="24" /> '.$this->entity->get_value('name');
			}
		}
		return $this->entity->get_value('name');
	}
}