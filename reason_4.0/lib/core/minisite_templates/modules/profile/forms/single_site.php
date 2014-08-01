<?php
/**
 * @package reason_local
 * @subpackage minisite_modules
 */

/**
 * Include the reason header, and register the module with Reason
 */
include_once( 'reason_header.php' );

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/profile/forms/default.php' );

/**
 * Single site edit form.
 *
 * - Edit a single web address.
 * - In the background we use the "Personal Website" name - but for display purposes we use the section label ie. "Faculty Web Site"
 * - We do the above so that if we ever switch to allowing multiple sites for faculty things will just work by switching the form used.
 */
class singleSiteProfileEditForm extends defaultProfileEditForm
{
	function on_every_time()
	{
		$this->add_element('web_address', 'text');
		$person = $this->get_person();
		if ($sites = $person->get_sites())
		{
			foreach ($sites as $name => $site_url)
			{
				if ($name == 'Personal Website')
				{
					$this->set_value('web_address', $site_url);
				}
			}
		}
	}
	
	/**
	 * Lets do some munging ...
	 *
	 * - If we haven't begun the string with http:// or https:// append that to the start.
	 */
	function pre_error_check_actions()
	{
		if ($url = $this->get_value('web_address')) // we dynamically add http:// if that makes the URL valid where it wasn't before.
		{
			if (!$this->validate_url($url) && $this->validate_url('http://' . $url))
			{
				$this->set_value('web_address', 'http://' . $url);
			}
		}
	}
	
	/**
	 * @todo what is left?
	 */
	function run_error_checks()
	{
		$url = $this->get_value('web_address');
		if (!$this->validate_url($url))
		{
			$this->set_error($elm_name_url, 'You need to provide a valid web address.');
		}
	}
	
	private function validate_url($url)
	{
		return (filter_var($url, FILTER_VALIDATE_URL));
	}
	
	/**
	 * Save new / updated sites as external urls using profile person methods.
	 *
	 * - Call sync
	 */
	function process()
	{
		$person = $this->get_person();
		$person->sync_sites(array('Personal Website' => $this->get_value('web_address')));
	}
}