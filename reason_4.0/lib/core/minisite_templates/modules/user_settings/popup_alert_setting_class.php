<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
/**
 * Include parent class
 */
include_once('default_setting_class.php');

/**
 * A user setting class for changing a user's popup alert preference (what 
 * we're referring to on the front-end as the "logout notification method")
 *
 * @author Ben Cochran
 **/
class popupAlertSettingClass extends DefaultUserSettingClass
{
	var $popup_alert_default;
	var $choices_array = array();
	var $title = 'Changing Logout Notification Method';
	
	/**
	 * We need to set up the choices depending on the default value.
	 *
	 * @return void
	 **/
	function init()
	{
		$this->title_paragraph = 'You will be logged off automatically if you are inactive for '.REASON_SESSION_TIMEOUT.' minutes. This setting lets you choose how you are notified when you are logged out.';
		if (DEFAULT_TO_POPUP_ALERT)
		{
			$this->popup_alert_default = 'yes';
			$this->choices_array = array (
									'yes' => array('name' => 'Default', 'desc' => ' <span class="smallText">(Uses aggressive alert)</span>'),
									'no' => array('name' => 'Less Obtrusive', 'desc' => '<span class="smallText">(May not work with some assistive technologies)</span>'),
									);
		}
		else
		{
			$this->popup_alert_default = 'no';
			$this->choices_array = array (
									'no' => array('name' => 'Default', 'desc' => '<span class="smallText">(May not work with some assistive technologies)</span>'),
									'yes' => array('name' => 'High Accessibiliy', 'desc' => '<span class="smallText">(Uses aggressive alert)</span>'),
									);
		}
	}
	
	function do_mapping($form)
	{
		foreach ($this->choices_array as $key => $sub_array)
		{
			$options_array[$key] = implode(' ', $sub_array);
		}
		$form->add_element( 'popup_alert', 'radio', array('options'=>$options_array, 'display_name'=>'Logout Notification Method:' ) );
		$form->map('popup_alert','user_popup_alert_pref',$this->popup_alert_default);
		return $form;
	}

	function show_current_value_and_edit_link()
	{
		if ($this->user->get_value('user_popup_alert_pref'))
			$array['value'] = $this->choices_array[$this->user->get_value('user_popup_alert_pref')]['name'];
		else
			$array['value'] = $this->choices_array[$this->popup_alert_default]['name'];
		
		$array['link'] = 'popup_alert';
		$array['title'] = 'Logout Notification Method';
		return $array;
	}
}