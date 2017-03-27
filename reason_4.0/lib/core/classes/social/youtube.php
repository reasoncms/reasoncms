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
$GLOBALS[ '_social_integrator_class_names' ][ basename( __FILE__, '.php' ) ] = 'ReasonYouTubeIntegrator';

/**
 * A class that provides ReasonCMS with YourTube integration.
 *
 * This is intended to be used as a singleton - obtain it like this:
 *
 * <code>
 * $sih = reason_get_social_integration_helper();
 * $twitter_integrator = $sih->get_integrator('instagram');
 * </code>
 *
 * Currently this provides content manager integration and also implements:
 *
 * - SocialAccountProfileLinks
 * - SocialSharingLinks
 *
 * @author Matt Ryan
 */
class ReasonYouTubeIntegrator extends ReasonSocialIntegrator implements SocialAccountProfileLinks, SocialAccountPlatform
{
	/****************** SocialAccountPlatform implementation ********************/
	public function get_platform_name()
	{
		return 'YouTube';
	}
	public function get_platform_icon()
	{
		return REASON_HTTP_BASE_PATH . 'modules/social_account/images/youtube.png';
	}
	/****************** SocialAccountProfileLinks implementation ********************/
	public function get_profile_link_text($social_entity_id)
	{
		return 'YouTube videos';
	}
	
	public function get_profile_link_href($social_entity_id)
	{
		$social_entity = new entity($social_entity_id);
		$username = $social_entity->get_value('account_id');
		return 'http://www.youtube.com/user/'.$username;
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
		return 'YouTube';
	}
	
	/**
	 * YouTube doesn't seem to support sharing of arbitrary web pages...
	 * 
	 * @param string URL if null we assume the current URL.
	 */
	public function get_sharing_link_href($url = NULL)
	{
		return NULL;
	}
	
	/****************** SocialAccountContentManager implementation *********************/
	
	/**
	 * Add / modify for elements
	 */
	function social_account_on_every_time($cm)
	{
		$cm->change_element_type($this->element_prefix.'account_type', 'protected');
		$cm->change_element_type($this->element_prefix.'account_details', 'protected');
		$cm->set_display_name($this->element_prefix.'account_id', 'YouTube username');
		$cm->add_required($this->element_prefix.'account_id');
			
		// lets add a field showing the current link if one is available.		
		$account_id = $cm->get_value($this->element_prefix.'account_id');
		if (!empty($account_id))
		{
			$link = 'http://www.youtube.com/user/'.$account_id;
			$comment_text = '<a href="'.reason_htmlspecialchars($link).'">'.reason_htmlspecialchars($link).'</a>';
			$cm->add_element($this->element_prefix.'account_link', 'commentWithLabel', array(
					'text' => $comment_text,
					'display_name' => 'Account Link'));
		}
	}
	
	function social_account_pre_show_form($cm)
	{
		echo '<p class="platformInfo"><img src="'.htmlspecialchars($this->get_platform_icon()).'" alt="YouTube icon" width="25" height="25" class="platformIcon" /> Add/edit a YouTube account.</p>';
	}
	
	/**
	 * Run error checks
	 *
	 * @todo make sure account is valid and unprotected via API
	 */
	function social_account_run_error_checks($cm)
	{
		$account_id = $cm->get_value($this->element_prefix.'account_id');
		if ( !check_against_regexp($account_id, array('/^[a-z\d._]*$/i')) )
		{
			$cm->set_error($this->element_prefix.'account_id', 'Invalid format for YouTube username. Please enter a valid username');
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
?>