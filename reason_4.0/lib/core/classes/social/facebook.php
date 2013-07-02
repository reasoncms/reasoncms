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
 * Currently this provides content manager integration and implements the SocialAccountProfileLinks interface.
 *
 * The idea here is that this file is the only spot where we directly deal with facebook APIs.
 *
 * @author Nathan White
 */
class ReasonFacebookIntegrator extends ReasonSocialIntegrator implements SocialAccountProfileLinks
{
	/****************** SocialAccountProfileLinks implementation ********************/
	public function get_profile_link_text($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		$details = json_decode($social_entity->get_value('account_details'), true);
		return 'Visit on FaceBook';
	}
	
	public function get_profile_link_src($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		$details = json_decode($social_entity->get_value('account_details'), true);
		return $details['link'];
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
		$cm->set_comments('account_id', form_comment('This is usually the number at the end of your facebook profile or page. If you cannot find it, try your username instead.'));
			
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
			$cm->set_error('account_id', 'Invalid format for facebook ID. Please enter a numeric ID or a valid facebook username');
		}
		else
		{
			// lets actually look this up at graph search.
			if ($details = $this->get_graph_info($account_id))
			{
				if (isset($details['link']))
				{
					$existing_details = json_decode($cm->get_value('account_details'), true);
					$existing_details['link'] = $details['link'];
					$cm->set_value('account_details', json_encode($existing_details));
					if (isset($details['id']) && ($details['id'] != $account_id))
					{
						$cm->set_value('account_id', $details['id']);
					}
				}
				else
				{
					$cm->set_error('account_id', 'Facebook does have a public link associated with that Facebook ID. Make sure you entered the ID correctly.');
				}
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
	 * Get info on a facebook graph id.
	 *
	 * @return mixed array key value pairs for facebook graph id or boolean FALSE
	 */
	private function get_graph_info($id)
	{
		$url = 'http://graph.facebook.com/'.$id;
		$json = carl_util_get_url_contents($url, false, '', '', 10, 5, true, false);
		if ($json) return json_decode($json, true);
		else return false;
	}
}
?>