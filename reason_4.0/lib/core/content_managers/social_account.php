<?php
/**
 * @package reason
 * @subpackage content_managers
 */

/**
 * Register content manager with Reason
 */
$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'SocialAccountManager';

/**
 * Load dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/social.php');
		
/**
 * A content manager for social accounts. Currently we support the following -
 *
 * - Facebook
 * - Twitter
 *
 * @todo support pinterest
 */
class SocialAccountManager extends ContentManager
{
	protected $social_helper;
	
	/**
	 * Get/set our social helper class
	 */
	private function social_helper()
	{
		if (!isset($this->social_helper))
		{
			$this->social_helper = reason_get_social_integration_helper();
		}
		return $this->social_helper;
	}
	
	/**
	 * Lets add our callbacks here for integration with our social integrator.
	 */
	function alter_data()
	{
		if ($account_type = $this->get_account_type())
		{
			$integrator = $this->social_helper()->get_integrator($account_type);
			$this->change_element_type('account_type', 'protected');
			$this->set_value('account_type', $account_type);
			
			$this->add_callback(array($integrator, 'social_account_pre_show_form'), 'pre_show_form');
			$this->add_callback(array($integrator, 'social_account_on_every_time'), 'on_every_time');
			$this->add_callback(array($integrator, 'social_account_run_error_checks'), 'run_error_checks');
		}
		else $this->show_form = false;
	}
	
	/**
	 * Get (and validate) account_type from the form or the URL if it hasn't yet been set.
	 *
	 * @return mixed string representing key of the account type or boolean FALES
	 */
	private function get_account_type()
	{
		if (!isset($this->_account_type))
		{
			$this->_account_type = $this->get_value('account_type');
			if (empty($account_type) && isset($_GET['account_type']))
			{
				if ($integrators = $this->social_helper()->get_available_integrators())
				{
					if (isset($integrators[$_GET['account_type']]))
					{
						$this->_account_type = $_GET['account_type'];
					}
				}
			}
			if (empty($this->_account_type)) $this->_account_type = false;
		}
		return $this->_account_type;
	}
	
	/**
	 * Account type is not set - we force selection of a valid account type.
	 */
	function no_show_form()
	{
		$options = $this->social_helper()->get_available_integrators();
		if (!empty($options))
		{
			echo '<p>Which social account would you like to create?</p>';
			echo '<ul>';
			foreach ($options as $k => $v)
			{
				$link = carl_make_link(array('account_type' => $k));
				echo '<li><a href="'.$link.'">'.$v.'</a></li>';
			}
			echo '</ul>';
		}
		else echo '<p>This installation of Reason CMS does not have any social account options enabled.</p>';
	}
}
?>