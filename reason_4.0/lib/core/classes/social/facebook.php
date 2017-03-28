<?php
/**
 * @package reason
 * @subpackage classes
 */

/**
 * Load dependencies.
 */
include_once('reason_header.php');
include_once(SETTINGS_INC . 'facebook_api_settings.php');
reason_include_once( 'classes/social.php' );

/**
 * Register the integrator with Reason CMS.
 */
$GLOBALS[ '_social_integrator_class_names' ][ basename( __FILE__, '.php' ) ] = 'ReasonFacebookIntegrator';

/**
 * A class that provides ReasonCMS with Facebook integration.
 *
 * This is intended to be used as a singleton - obtain it like this:
 *
 * <code>
 * $sih = reason_get_social_integration_helper();
 * $facebook_integrator = $sih->get_integrator('facebook');
 * </code>
 *
 * Currently this provides content manager integration and also implements
 *
 * - SocialAccountProfileLinks
 * - SocialSharingLinks
 *
 * The idea here is that this file is the only spot where we directly deal with facebook APIs.
 *
 * @author Nathan White
 */
class ReasonFacebookIntegrator extends ReasonSocialIntegrator implements SocialAccountProfileLinks, SocialSharingLinks, SocialAccountPlatform
{
	/****************** SocialAccountPlatform implementation ********************/
	public function get_platform_name()
	{
		return 'Facebook';
	}
	public function get_platform_icon()
	{
		return REASON_HTTP_BASE_PATH . 'modules/social_account/images/facebook.png';
	}
	/****************** SocialAccountProfileLinks implementation ********************/
	public function get_profile_link_text($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		$details = json_decode($social_entity->get_value('account_details'), true);
		return 'Visit on Facebook';
	}
	
	public function get_profile_link_href($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		$details = json_decode($social_entity->get_value('account_details'), true);
		return $details['link'];
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
		return 'Facebook';
	}
	
	/**
	 * Return a URL encoded view of the current URL, right?
	 * 
	 * @param string URL if null we assume the current URL.
	 */
	public function get_sharing_link_href($url = NULL)
	{
		$url = (!is_null($url)) ? urlencode($url) : urlencode(get_current_url('http'));
		return 'https://www.facebook.com/sharer/sharer.php?u=' . $url;
	}

	/****************** SocialAccountContentManager implementation *********************/
	
	/**
	 * Add / modify for elements for Facebook integration.
	 */
	function social_account_on_every_time($cm)
	{
		$cm->change_element_type($this->element_prefix.'account_type', 'protected');
		$cm->change_element_type($this->element_prefix.'account_details', 'protected');
		$cm->set_display_name($this->element_prefix.'account_id', 'Facebook ID');
		$cm->add_required($this->element_prefix.'account_id');
		$cm->set_comments($this->element_prefix.'account_id', form_comment('This is usually the number at the end of your Facebook profile or page. If you cannot find it, try your username instead.'));
			
		// lets add a field showing the current link if one is available.
		
		$account_details = $cm->get_value($this->element_prefix.'account_details');
		if (!empty($account_details))
		{
			$details = json_decode($account_details, true);
			if (isset($details['link']))
			{
				$comment_text = '<a href="'.$details['link'].'">'.$details['link'].'</a>';
				$cm->add_element($this->element_prefix.'account_link', 'commentWithLabel', array(
					'text' => $comment_text,
					'display_name' => 'Account Link'));
			}
		}
	}
	
	function social_account_pre_show_form($cm)
	{
		echo '<p class="platformInfo"><img src="'.htmlspecialchars($this->get_platform_icon()).'" alt="Facebook icon" width="25" height="25" class="platformIcon" /> Add/edit a Facebook page or profile that has a public link available.</p>';
	}
	
	/**
	 * Run error checks
	 *
	 * - validate the account id - autoconvert to id from username if possible.
	 * - populate account_details field so it is saved when process phase runs.
	 */
	function social_account_run_error_checks($cm)
	{
		$account_id = $cm->get_value($this->element_prefix.'account_id');
		if ( !$this->validate_account_id($account_id) )
		{
			$cm->set_error($this->element_prefix.'account_id', 'Invalid format for Facebook ID. Please enter a numeric ID or a valid Facebook username');
		}
		else
		{
			if (!$this->update_form_from_graph_info($account_id, $cm))
			{
				$cm->set_error($this->element_prefix.'account_id', 'Facebook does not recognize the ID that you entered.');
			}
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
	
	/**
	 * Determine whether the passed account ID conforms to Facebook standards
	 * 
	 * @param string $account_id
	 * @return boolean
	 */
	public function validate_account_id($account_id)
	{
		return (check_against_regexp($account_id, array('naturalnumber')) || check_against_regexp($account_id, array('/^[a-z\d.]*$/i')));
	}
	
	/**
	 * Given an account ID, look it up using the Facebook Graph API and set the account_details
	 * field appropriately.  Returns false on failure.
	 * 
	 * IMPORTANT NOTE: The Graph API as used here can only return information about completely 
	 * public pages and groups. Closed groups or groups that are defined within another organization
	 * will fail, even if they're perfectly valid.
	 * 
	 * @param string $account_id
	 * @param object $cm
	 * @return boolean
	 */
	public function update_form_from_graph_info($account_id, $cm)
	{
		if ($details = $this->get_graph_info($account_id))
		{
			if (isset($details['link']))
			{
				$existing_details = json_decode($cm->get_value($this->element_prefix.'account_details'), true);
				$existing_details['link'] = $details['link'];
				$cm->set_value($this->element_prefix.'account_details', json_encode($existing_details));
				if (isset($details['id']) && ($details['id'] != $account_id))
				{
					$cm->set_value($this->element_prefix.'account_id', $details['id']);
				}
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Get info on a facebook graph id.
	 *
	 * @return mixed array key value pairs for facebook graph id or boolean FALSE
	 */
	public function get_graph_info($id)
	{
		$url = 'https://graph.facebook.com/'.$id;
		if (defined('FACEBOOK_API_APP_ID')) $url .= '?access_token='.FACEBOOK_API_APP_ID.'|'.FACEBOOK_API_APP_SECRET;
		$json = carl_util_get_url_contents($url, false, '', '', 10, 5, true, false);

		if ($json) return json_decode($json, true);
		else return false;
	}
}