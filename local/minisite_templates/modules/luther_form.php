<?php
/**
* @package reason
* @subpackage minisite_modules
*/
 
	/**
	* Include parent class & dependencies
	*/
	reason_include_once( 'minisite_templates/modules/form.php' );
	
	/**
	* Register module with Reason
	*/
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherFormMinisiteModule';
	
	/**
	* Form 2.0
	*
	* Reason Form Module - intended to be used to build interfaces around thor or custom forms, while maintaining backwards
	* compatibility with Reason's old thor form module.
	*
	* Common usage would involve instantiation of a model, view (optional), admin_view (optional) and controller.
	*
	* If no parameters are provided, then the default thor form model, view, and admin_view, and controller will be used.
	*
	* The model is passed a reference to the module at the time of instantiation, so that head items, the cur_page object,
	* or other items available to the module can be localized into the model. The controller handles cleanup rules and request
	* variables, just like a module would. Essentially, the controller serves as a sub-module.
	*
	* The controller itself will be provided the view and admin view if these are provided parameter, but they are optional.
	*
	* @author Nathan White
	*/
	class LutherFormMinisiteModule extends FormMinisiteModule
	{
		function run()
		{
			if ($this->model_is_usable())
			{
				$controller = $this->get_form_controller();
				$controller->run();
			}
			else // no message -- override default behavior
			{
				//echo '<div id="form">';
				//echo '<p>This page should display a form, but is not set up correctly. Please try again later.</p>';
				//echo '</div>';
			}
		}
	}

?>
