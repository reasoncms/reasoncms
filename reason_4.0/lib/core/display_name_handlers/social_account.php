<?php
/**
 * @package reason
 * @subpackage display_name_handlers
 */
	
/**
 * Register display name handler with Reason
 */
$display_handler = 'reason_social_account_display_name_handler';
$GLOBALS['display_name_handlers']['social_account.php'] = $display_handler;

if( !defined( 'DISPLAY_HANDLER_SOCIAL_ACCOUNT_PHP' ) )
{
	define( 'DISPLAY_HANDLER_SOCIAL_ACCOUNT_PHP',true );

	reason_include_once( 'classes/entity.php' );
	reason_include_once( 'classes/social.php' );

	/**
	 * A display name handler for social accounts
	 *
	 * Includes the social network's icon
	 *
	 * @param mixed $id Reason ID or entity
	 * @return string
	 */
	function reason_social_account_display_name_handler( $id )
	{
		if( !is_object( $id ) )
			$e = new entity( $id );
		else $e = $id;
		
		if($account_type = $e->get_value('account_type'))
		{
			$helper = new ReasonSocialIntegrationHelper();
			$integrator = $helper->get_integrator($account_type, 'SocialAccountProfileLinks');
			if($url = $integrator->get_profile_link_icon($e->id()))
			{
			    $integrators = $helper->get_available_integrators();
			    $alt = isset($integrators[$account_type]) ? $integrators[$account_type] : $account_type;
			    return '<img src="'.reason_htmlspecialchars($url).'" alt="'.reason_htmlspecialchars($alt).'" height="24" width="24" /> '.$e->get_value('name');
			}
		}
		return $e->get_value('name');
	}
}

?>