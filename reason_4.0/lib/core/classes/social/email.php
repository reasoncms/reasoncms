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
$GLOBALS[ '_social_integrator_class_names' ][ basename( __FILE__, '.php' ) ] = 'ReasonEmailSignupIntegrator';

/**
 * A class that provides ReasonCMS with Email Signup integration.
 *
 * This is intended to be used as a singleton - obtain it like this:
 *
 * <code>
 * $sih = reason_get_social_integration_helper();
 * $email_integrator = $sih->get_integrator('email');
 * </code>
 *
 * Currently this provides content manager integration and also implements:
 *
 * - SocialAccountProfileLinks
 * 
 *
 * @author Gage Dykema
 */
class ReasonEmailSignupIntegrator extends ReasonSocialIntegrator implements SocialAccountProfileLinks
{
	/****************** SocialAccountProfileLinks implementation ********************/
	public function get_profile_link_text($social_entity_id)
	{
		return 'Sign up for our email newsletter';
	}
	
	public function get_profile_link_href($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		$userid = $social_entity->get_value('account_id');
		return $userid;
	}

	/****************** SocialSharingLinks implementation ***********************/
	public function get_sharing_link_icon()
	{
		return REASON_HTTP_BASE_PATH . 'modules/social_account/images/email.png';
	}
	
	public function get_sharing_link_text()
	{
		return 'Email Signup';
	}
	
	/**
	 * Return a URL encoded view of the current URL, right?
	 * 
	 * @param string URL if null we assume the current URL.
	 */
	public function get_sharing_link_href($url = NULL)
	{
		$url = (!is_null($url)) ? urlencode($url) : urlencode(get_current_url('http'));
		//return 'https://plus.google.com/share?url=' . $url;
		return NONE;
	}
	
	/****************** SocialAccountContentManager implementation *********************/
	
	/**
	 * Add / modify for elements for Email integration.
	 */
	function social_account_on_every_time($cm)
	{
		$cm->change_element_type('account_type', 'protected');
		$cm->change_element_type('account_details', 'protected');
		$cm->set_display_name('account_id', 'Signup Page URL');
		$cm->add_required('account_id');
		$cm->add_comments('account_id', form_comment(''));

		// lets add a field showing the current link if one is available.		
		$account_id = $cm->get_value('account_id');
		if (!empty($account_id))
		{
			$link = $account_id;
			$comment_text = '<a href="'.$link.'">'.$link.'</a>';
			$cm->add_element('account_link', 'commentWithLabel', array('text' => $comment_text));
		}
	}
	
	function social_account_pre_show_form($cm)
	{
		echo '<p>Add/edit an Email Signup.</p>';
	}
	
	/**
	 * Run error checks
	 *
	 * @todo make sure account is valid and unprotected via API
	 */
	function social_account_run_error_checks($cm)
	{
		$account_id = $cm->get_value('account_id');  
	if ( !check_against_regexp($account_id, array('naturalnumber')) && !check_against_regexp($account_id, array('/^[a-z\d.\S]*$/i')) ) //'/^[\.&:\=\/\?%a-z\d.]*$/i'
		{
			$cm->set_error('account_id', 'Invalid format for Signup URL. Please enter a valid username');
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