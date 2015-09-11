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
$GLOBALS[ '_social_integrator_class_names' ][ basename( __FILE__, '.php' ) ] = 'ReasonInstagramIntegrator';

/**
 * A class that provides ReasonCMS with Instagram integration.
 *
 * This is intended to be used as a singleton - obtain it like this:
 *
 * <code>
 * $sih = reason_get_social_integration_helper();
 * $Instagram_integrator = $sih->get_integrator('instagram');
 * </code>
 *
 * Currently this provides content manager integration and also implements:
 *
 * - SocialAccountProfileLinks
 * - SocialSharingLinks
 *
 * @todo move oauth stuff into this class and modify the instagram feed models to use it.
 * @author Nathan White
 */
class ReasonInstagramIntegrator extends ReasonSocialIntegrator implements SocialAccountProfileLinks, SocialSharingLinks
{
	/****************** SocialAccountProfileLinks implementation ********************/
	public function get_profile_link_text($social_entity_id)
	{
		return 'Visit on Instagram';
	}
	
	public function get_profile_link_href($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		$username = $social_entity->get_value('account_id');
		return 'http://www.instagram.com/'.$username;
	}

	/****************** SocialSharingLinks implementation ***********************/
	public function get_sharing_link_icon()
	{
		return REASON_HTTP_BASE_PATH . 'modules/social_account/images/instagram.png';
	}
	
	public function get_sharing_link_text()
	{
		return 'Instagram';
	}
	
	/**
	 * Return a URL encoded view of the current URL, right?
	 * 
	 * @param string URL if null we assume the current URL.
	 */
	public function get_sharing_link_href($url = NULL)
	{
		$url = (!is_null($url)) ? urlencode($url) : urlencode(get_current_url('http'));
		return 'https://instagram.com/share?url=' . $url;
	}
	
	/****************** SocialAccountContentManager implementation *********************/
	
	/**
	 * Add / modify for elements for Instagram integration.
	 */
	function social_account_on_every_time($cm)
	{
		$cm->change_element_type('account_type', 'protected');
		$cm->change_element_type('account_details', 'protected');
		$cm->set_display_name('account_id', 'Instagram username');
		$cm->add_required('account_id');
			
		// lets add a field showing the current link if one is available.		
		$account_id = $cm->get_value('account_id');
		if (!empty($account_id))
		{
			$link = 'http://www.instagram.com/'.$account_id;
			$comment_text = '<a href="'.$link.'">'.$link.'</a>';
			$cm->add_element('account_link', 'commentWithLabel', array('text' => $comment_text));
		}
	}
	
	function social_account_pre_show_form($cm)
	{
		echo '<p>Add/edit a Instagram profile.</p>';
	}
	
	/**
	 * Run error checks
	 *
	 * @todo make sure account is valid and unprotected via API
	 */
	function social_account_run_error_checks($cm)
	{
		$account_id = $cm->get_value('account_id');
		if ( !check_against_regexp($account_id, array('naturalnumber')) && !check_against_regexp($account_id, array('/^[a-z\d.]*$/i')) )
		{
			$cm->set_error('account_id', 'Invalid format for instagram username. Please enter a valid username');
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