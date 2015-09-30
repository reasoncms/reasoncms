<?php
/**
 * @package reason
 * @subpackage classes
 */

/**
 * Load dependencies.
 */
include_once('reason_header.php');
reason_include_once( 'classes/social.php' );

/**
 * Register the integrator with Reason CMS.
 */
$GLOBALS[ '_social_integrator_class_names' ][ basename( __FILE__, '.php' ) ] = 'ReasonGooglePlusIntegrator';

/**
 * A class that provides ReasonCMS with Google+ integration.
 *
 * This is intended to be used as a singleton - obtain it like this:
 *
 * <code>
 * $sih = reason_get_social_integration_helper();
 * $twitter_integrator = $sih->get_integrator('googleplus');
 * </code>
 *
 * Currently this provides content manager integration and also implements:
 *
 * - SocialAccountProfileLinks
 * - SocialSharingLinks
 *
 * @author Nathan White
 */
class ReasonGooglePlusIntegrator extends ReasonSocialIntegrator implements SocialAccountProfileLinks, SocialSharingLinks, SocialAccountPlatform
{
	/****************** SocialAccountPlatform implementation ********************/
	public function get_platform_name()
	{
		return 'Google+';
	}
	public function get_platform_icon()
	{
		return REASON_HTTP_BASE_PATH . 'modules/social_account/images/googleplus.png';
	}
	/****************** SocialAccountProfileLinks implementation ********************/
	public function get_profile_link_text($social_entity_id)
	{
		return 'Visit on Google+';
	}
	
	public function get_profile_link_href($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		$username = $social_entity->get_value('account_id');
		return 'http://plus.google.com/'.$username;
	}
	public function get_profile_link_icon($social_entity_id)
	{
		return $this->get_platform_icon();
	}

	/****************** SocialSharingLinks implementation ***********************/
	public function get_sharing_link_icon()
	{
		return $this->get_platform_icon();
	}
	
	public function get_sharing_link_text()
	{
		return 'Google+';
	}
	
	/**
	 * Return a URL encoded view of the current URL, right?
	 * 
	 * @param string URL if null we assume the current URL.
	 */
	public function get_sharing_link_href($url = NULL)
	{
		$url = (!is_null($url)) ? urlencode($url) : urlencode(get_current_url('http'));
		return 'https://plus.google.com/share?url=' . $url;
	}
	
	/****************** SocialAccountContentManager implementation *********************/
	
	/**
	 * Add / modify for elements for Facebook integration.
	 */
	function social_account_on_every_time($cm)
	{
		$cm->change_element_type('account_type', 'protected');
		$cm->change_element_type('account_details', 'protected');
		$cm->set_display_name('account_id', 'Google+ ID');
		$cm->add_required('account_id');
		$cm->add_comments('account_id', form_comment('Your Google+ ID is the set of numbers after plus.google.com/ in the URL when you view your profile.'));

		// lets add a field showing the current link if one is available.		
		$account_id = $cm->get_value('account_id');
		if (!empty($account_id))
		{
			$link = 'http://plus.google.com/'.$account_id;
			$comment_text = '<a href="'.$link.'">'.$link.'</a>';
			$cm->add_element('account_link', 'commentWithLabel', array('text' => $comment_text));
		}
	}
	
	function social_account_pre_show_form($cm)
	{
		echo '<p class="platformInfo"><img src="'.htmlspecialchars($this->get_platform_icon()).'" alt="Google+ icon" width="25" height="25" class="platformIcon" /> Add/edit a Google+ profile.</p>';
	}
	
	/**
	 * Run error checks
	 *
	 * @todo make sure account is valid and unprotected via API
	 */
	function social_account_run_error_checks($cm)
	{
		$account_id = $cm->get_value('account_id');
		if ( !check_against_regexp($account_id, array('naturalnumber')) )
		{
			$cm->set_error('account_id', 'Invalid format for google account id - should be all numbers.');
		}
		// if we have a problem with account_id lets remove the account_link field.
		if ($cm->has_error('account_id'))
		{
			if ($cm->is_element('account_link'))
			{
				$cm->remove_element('account_link');
			}
		}
	}
}
?>