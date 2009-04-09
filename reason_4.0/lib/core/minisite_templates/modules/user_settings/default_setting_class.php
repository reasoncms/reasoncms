<?php
/**
 * A user setting class to be used by the user settings module.
 * Defines a form to be used to edit a setting as well as other
 * things about that setting, such as how to create links to edit it.
 *
 * @package reason
 * @subpackage minisite_modules
 *
 * @author Ben Cochran
 **/
class DefaultUserSettingClass
{
	/**
	 * The disco form that the setting uses
	 **/
	var $disco_form = 'default_setting_form.php';
	
	/**
	 * The name of the disco class that the setting uses
	 **/
	var $disco_class_name = 'defaultSettingForm';
	
	/**
	 * When changing a setting, this value is shown as
	 * a heading above the form the page
	 **/
	var $title;
	
	/**
	 * When changing a setting, this value is shown
	 * above the form, below the heading
	 **/
	var $title_paragraph;
	
	
	var $user;
	var $done_with_form = false;
	
	/**
	 * A function that is called directly after the class is instantiated
	 * Most settings won't need this, but a few might need to take care of
	 * setting some variables or building some arrays 
	 **/
	function init()
	{
		// most don't need this
	}
	
	/**
	 * A mapping class that is used to do the mapping on
	 * disco_reason_manual_map right before the form is run
	 *
	 * @param disco_form
	 * @return disco_form
	 **/
	function do_mapping($form)
	{
		return $form;
	}
	
	/**
	 * Includes and runs the disco form if the conditional_run_function
	 * doesn't prohibit it from doing so. See conditional_run_function()
	 * for more information on that.
	 **/
	function run_form()
	{
		if ($this->title)
			echo '<h4>'.$this->title.'</h4>'."\n";
		if ($this->title_paragraph)
			echo '<p class="preForm">'.$this->title_paragraph.'</p>'."\n";
		
		if (!$this->conditional_run_function())
		{
			$this->show_disabled_message();
		}
		else
		{
			include_once($this->disco_form);
			$class_name = $this->disco_class_name;
			$my_form = new $class_name();

			$my_form->entity = $this->user;
			
			$my_form = $this->do_mapping($my_form);
			
			$my_form->run();
			if ($my_form->completed)
			{
				$this->done_with_form = true;
			}
		}
	}

	/**
	 * Defines an array to be used by the user settings module
	 * to construct a table od the available settings.
	 * $array['title'] defines the name of the setting
	 * $array['value'] defines the current value of the setting
	 * $array['link'] defines the name of the setting that is used to
	 * 					construct the querystring. This must be the 
	 * 					same name that is used in the $cleanup_rules
	 * 					and $setting_mapping arrays in the module
	 *
	 * @return array
	 **/
	function show_current_value_and_edit_link()
	{
		$array['title'] = 'Setting Name';
		$array['value'] = '';
		$array['link'] = 'setting';
		return $array;
	}
	
	/**
	 * If you want your setting to only be accessible by some users
	 * you can do logic here that will lock some users out of a setting.
	 *
	 * @return boolean
	 **/
	function conditional_run_function()
	{
		return true;
	}
	
	/**
	 * The error message that is shown when the conditional_run_function
	 * returns false
	 **/
	function show_disabled_message()
	{
		echo '<p>You do not have access to this setting.</p>';
	}
	
}
?>