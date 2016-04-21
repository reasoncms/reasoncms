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
$GLOBALS[ '_social_integrator_class_names' ][ basename( __FILE__, '.php' ) ] = 'ReasonLinkedInIntegrator';
/**
 * A class that provides ReasonCMS with Linkedin integration.
 *
 * This is intended to be used as a singleton - obtain it like this:
 *
 * <code>
 * $sih = reason_get_social_integration_helper();
 * $linkedin_integrator = $sih->get_integrator('linkedin');
 * </code>
 *
 * Currently this provides content manager integration and also implements:
 *
 * - SocialAccountProfileLinks
 *
 *
 * @author Gage Dykema
 */
class ReasonLinkedInIntegrator extends ReasonSocialIntegrator implements SocialAccountProfileLinks
{
	/****************** SocialAccountProfileLinks implementation ********************/
	public function get_profile_link_text($social_entity_id)
	{
		return 'Visit on LinkedIn';
	}

	public function get_profile_link_href($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		$userid = $social_entity->get_value($this->element_prefix.'account_id');
		return $userid;
	}
	/****************** SocialSharingLinks implementation ***********************/


	/****************** SocialAccountContentManager implementation *********************/

	/**
	 * Add / modify for elements for Email integration.
	 */
	function social_account_on_every_time($cm)
	{
		$cm->change_element_type($this->element_prefix.'account_type', 'protected');
		$cm->change_element_type($this->element_prefix.'account_details', 'protected');
		$cm->set_display_name($this->element_prefix.'account_id', 'LinkedIn page URL');
		$cm->add_required($this->element_prefix.'account_id');
		$cm->add_comments($this->element_prefix.'account_id', form_comment(''));
		// lets add a field showing the current link if one is available.
		$account_id = $cm->get_value($this->element_prefix.'account_id');
		if (!empty($account_id))
		{
			$link = $account_id;
			$comment_text = '<a href="'.$link.'">'.$link.'</a>';
			$cm->add_element($this->element_prefix.'account_link', 
				'commentWithLabel', 
				array('text' => $comment_text, 'display_name' => 'Account Link'));
		}
	}

	function social_account_pre_show_form($cm)
	{
		echo '<p>Add/edit a LinkedIn Account.</p>';
	}

	/**
	 * Run error checks
	 *
	 * @todo make sure account is valid and unprotected via API
	 */
	function social_account_run_error_checks($cm)
	{
		$account_id = $cm->get_value($this->element_prefix.'account_id');
		if ( !check_against_regexp($account_id, array('naturalnumber')) && !check_against_regexp($account_id, array('/^[a-z\d.\S]*$/i')) ) //'/^[\.&:\=\/\?%a-z\d.]*$/i'
		{
			$cm->set_error($this->element_prefix.'account_id', 'Invalid format for LinkedIn URL. Please enter a valid URL');
		}
		// if we have a problem with account_id lets remove the account_link field.
		if ($cm->has_error($this->element_prefix.'account_id'))
		{
			if ($cm->is_element($this->element_prefix.'account_link'))
			{
				$cm->remove_element($this->element_prefix.'account_link');
			}
		}
	}
}