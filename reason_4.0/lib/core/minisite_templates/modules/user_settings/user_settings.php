<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
/**
 * Include parent class & other utils
 */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'function_libraries/url_utils.php');
reason_include_once( 'classes/user.php');

/**
 * Register module with Reason
 */
//$GLOBALS[ '_module_class_names' ][ 'user_settings' ] = 'UserSettingsModule';
$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__) ] = 'UserSettingsModule';

/**
 * A minisite module to handle user settings.
 *
 * Designed to be easy to expand and configure. Settings to be configured are defined in an array
 * of settings classes.
 *
 * @author Ben Cochran, Nathan White
 */
class UserSettingsModule extends DefaultMinisiteModule
{
	var $user;
	
	/**
	 * When you add a setting, you need to add it to the 'extra_args' array
	 * in the cleanup rules so it is allowed to be passed as a query string.
	 *
	 * @var array
	 **/
	var $cleanup_rules = array(
			'user_setting' => array('function' => 'check_against_array', 
									'extra_args' => array('password','popup_alert','name','email','phone')
								   ),
			);
	
	/**
	 * Maps the settings you want to use. Use the same name that you used in the
	 * cleanup_rules array above.
	 * The mapping scheme is as follows:
	 * class_filename: The filename of the class that controls that setting (ie. "name_setting_class.php")
	 * class_name: The name of the class that controls that setting (ie. "nameSettingClass")
	 * 
	 * @var array 
	 **/	
	var $settings_mapping = array(
			'popup_alert' => array('class_filename' => 'popup_alert_setting_class.php',
								'class_name' => 'popupAlertSettingClass',
								),
			'name' => array('class_filename' => 'name_setting_class.php',
								'class_name' => 'nameSettingClass',
								),
			'email' => array('class_filename' => 'email_setting_class.php',
								'class_name' => 'emailSettingClass',
								),
			'phone' => array('class_filename' => 'phone_setting_class.php',
								'class_name' => 'phoneSettingClass',
								),
			'password' => array('class_filename'=>'password_setting_class.php', 
								'class_name' => 'passwordSettingClass',
								),
		);
	
	var $user_setting_selection = '';
	var $current_setting_object;
	
	/**
	 * @var boolean auto_create_users if true, will create reason user entities for non-reason users that can authenticate
	 */
	var $auto_create_users = false;
	
	/**
	 * Gets current user's user entity and includes the necessary classes
	 * 
	 * @return void
	 **/
	function init( $args=array() )
	{
		$this->parent->add_stylesheet( REASON_HTTP_BASE_PATH.'css/user_settings/user_settings.css', '', true );
		
		$user_netid = $this->get_user_netid();
		$user_class = new User();
		$this->user = $user_class->get_user($user_netid);
		if (!$this->user && $this->auto_create_users)
		{
			$this->user = $user_class->create_user($user_netid);
		}
		if ($this->user)
		{
			if (isset($this->request['user_setting'])) $this->user_setting_selection = $this->request['user_setting'];
			foreach ($this->settings_mapping as $key => $value)
			{
				include_once($value['class_filename']);
				$this->settings_mapping[$key]['object'] = new $value['class_name']();
				$this->settings_mapping[$key]['object']->init();
				$this->settings_mapping[$key]['object']->user = $this->user;
			}
			if ($this->user_setting_selection)
			{
				$this->current_setting_object = $this->settings_mapping[$this->user_setting_selection]['object'];
			}
		}
	}
	
	/**
	 *
	 */
	function get_user_netid()
	{
		if (!isset($this->_user_netid))
		{
			$netid = reason_require_authentication();
			$requested_netid = (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE && isset($_REQUEST['netid'])) ? $_REQUEST['netid'] : '';
			if (!empty($requested_netid) && !empty($netid) && ($requested_netid != $netid))
			{
				$user_id = get_user_id($netid);
				if (reason_user_has_privs($user_id, 'pose_as_other_user'))
				{
					$this->_user_netid = $requested_netid;
				}
			}
			else $this->_user_netid = $netid;
		}
		return $this->_user_netid;
	}
	
	/**
	 * If a setting is selected, tells the selected setting class to run it's form.
	 * If the form is complete or there is no setting selected, shows the setting table.
	 * 
	 * @return void
	 **/
	function run()
	{
		if ($this->user)
		{
			echo '<div class="settingHead"><h3 class="settingsH3">Your Settings</h3><p class="userNameText">('.$this->user->get_value('name').')</p></div>'."\n";
			if ($this->user_setting_selection)
			{
				$this->current_setting_object->run_form();
				if ($this->current_setting_object->done_with_form)
					$this->show_settings_links();
				echo '<p><a href="'.carl_make_link(array('user_setting'=> '')).'">Cancel</a></p>';
			}
			else
			{
				$this->show_settings_links();
			}
		}
		else
		{
			echo '<div class="settingHead"><h3 class="settingsH3">User Settings not Available</h3>';
			echo '<p>It appears that you do not have a Reason user account - you need to have an account in Reason in order to manage your user settings.</p>';
		}
		echo '</div>';
	}
	
	/**
	 * Generates a table of the settings that can be changed by this user.
	 *
	 * @return void
	 **/
	function show_settings_links()
	{
		// Clever little array for the class names
		$even_odd = array('odd','even');
		echo '<table id="userSettingsTable" cellspacing="0" summary="Settings for '.$this->user->get_value('name').'">' . "\n";
		echo '<thead><th>Setting</th><th>Value</th><th>Edit</th></thead>'."\n";
		$i = 0;
		foreach ($this->settings_mapping as $link => $mapping)
		{
			if ($mapping['object']->conditional_run_function())
			{
				$data = $mapping['object']->show_current_value_and_edit_link();
				echo '<tr class="'.$even_odd[$i].'">';
				echo '<th>'.$data['title'].':</th>';
				echo '<td class="value">'.$data['value'].'</td>';
				echo '<td><a href="'. carl_make_link(array('user_setting'=>$data['link'])) .'" title="Change '.$data['title'].'">Change</a></td>';
				echo '</tr>'."\n";
				$i = ($i+1)%2;
			}
		}
		echo '</table>' . "\n";
	}
}
?>