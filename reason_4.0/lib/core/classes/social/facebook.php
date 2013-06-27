<?php
/**
 * @package reason
 * @subpackage classes
 */

/**
 * Load dependencies.
 */
include_once('reason_header.php');

/**
 * Register the integrator with Reason CMS.
 */
$GLOBALS[ '_social_integrator_class_names' ][ basename( __FILE__, '.php' ) ] = 'ReasonFacebookIntegrator';

/**
 * A class that provides ReasonCMS with Facebook integration.
 *
 * Includes content_manager_ methods that are used for integration with Social Account type.
 *
 * - get_account_id
 * - get_account_details
 * - 
 *
 * @todo this should probably implement an interface that includes the above described message.
 */
class ReasonFacebookIntegrator
{

	/******************** CONTENT MANAGER INTEGRATION ****************************/
	
	/**
	 * Add / modify for elements for Facebook integration.
	 */
	function social_account_on_every_time($cm)
	{
		$cm->change_element_type('account_type', 'protected');
		$cm->change_element_type('account_details', 'protected');
		$cm->set_display_name('account_id', 'Facebook ID');
		$cm->set_comments('account_id', form_comment('This is usually the number at the end of your facebook profile or page. If you cannot find it, try your username instead.'));		
	}
	
	function social_account_pre_show_form($cm)
	{
		echo '<p>Add/edit a Facebook page or profile that has a public link available.</p>';
	}
	
	function social_account_post_show_form($cm)
	{
		$account_details = $cm->get_value('account_details');
		if (!empty($account_details))
		{
			$details = json_decode($account_details);
		}
		if (isset($details['link']))
		{
			var_dump($details['link']);
		}
	}
	
	/**
	 * Run error checks
	 *
	 * Lets curl the graph API and make sure the ID exists. If it does, lets store the associated link as json in the account_details.
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
			if ($json_results = carl_util_get_url_contents('http://graph.facebook.com/'.$account_id))
			{
				$details = json_decode($json_results, true);
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
				$cm->set_error('account_id', 'Could not connect to Facebook Graph search to get info on the Facebook ID - try again later.');
			}
		}
	}
	
	/**
	 * Populate account details according to the facebook graph
	 */
	function social_account_process($cm)
	{
		
	}
	
	function get_graph_info($id)
	{
		
	}
}
?>