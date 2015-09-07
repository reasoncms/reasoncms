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
class ReasonFacebookIntegrator extends ReasonSocialIntegrator implements SocialAccountProfileLinks, SocialSharingLinks
{
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

	/****************** SocialSharingLinks implementation ***********************/
	public function get_sharing_link_icon()
	{
		return REASON_HTTP_BASE_PATH . 'modules/social_account/images/facebook.png';
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
		$cm->change_element_type('account_type', 'protected');
		$cm->change_element_type('account_details', 'protected');
		$cm->set_display_name('account_id', 'Facebook ID');
		$cm->add_required('account_id');
		$cm->set_comments('account_id', form_comment('This is usually the username of your public Facebook profile or page.'));
			
		// lets add a field showing the current link if one is available.
		
		$account_details = $cm->get_value('account_details');
		if (!empty($account_details))
		{
			$details = json_decode($account_details, true);
			if (isset($details['link']))
			{
				$comment_text = '<a href="'.$details['link'].'">'.$details['link'].'</a>';
				$cm->add_element('account_link', 'commentWithLabel', array('text' => $comment_text));
			}
		}
	}
	
	function social_account_pre_show_form($cm)
	{
		echo '<p>Add/edit a Facebook page or profile that has a public link available.</p>';
	}
	
	/**
	 * Run error checks
	 *
	 * - validate the account id - autoconvert to id from username if possible.
	 * - populate account_details field so it is saved when process phase runs.
	 */
	function social_account_run_error_checks($cm)
	{
		$account_id = $cm->get_value('account_id');
		if ( !check_against_regexp($account_id, array('naturalnumber')) && !check_against_regexp($account_id, array('/^[a-z\d.]*$/i')) )
		{
			$cm->set_error('account_id', 'Invalid format for Facebook ID. Please enter a numeric ID or a valid Facebook username');
		}
		else
		{
			if ($details = $this->get_facebook_url($account_id))
			{
				$cm->set_value("account_details", '{"link":"https://www.facebook.com/'.$account_id.'"}');
			}
			else
			{
				$cm->set_error('account_id', 'Facebook does not recognize the ID that you entered.');
			}
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
	
	/**
	 * Get info on a facebook username.
	 *
	 * @return facebook url based on username or boolean FALSE
	 */
	private function get_facebook_url($id)
	{
		exec("curl -s -o /dev/null -I -w \"%{http_code}\" https://www.facebook.com/".$id, $ret);
		return ($ret[0] == 200 || $ret[0] == 301) ? "https://www.facebook.com/".$id : false;
	}
}
?>