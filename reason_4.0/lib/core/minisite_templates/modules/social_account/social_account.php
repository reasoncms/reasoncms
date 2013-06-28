<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include the base class & dependencies, and register the module with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'minisite_templates/modules/mvc.php' );
$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__, '.php' ) ] = 'ReasonSocialAccountModule';

/**
 * An MVC based module for presenting social accounts.
 *
 * You may pass other models and views via the page type.
 *
 * @author Nathan White
 */
class ReasonSocialAccountModule extends ReasonMVCModule
{
	// set everything up and get the model data.
	function init( $args = array() )
	{
		// if we use the module without configuration we'll use the profile_link model and view
		if (empty($this->params['model'])) $this->params['model'] = 'minisite_templates/modules/social_account/models/profile_links.php';
		if (empty($this->params['view'])) $this->params['view'] = 'minisite_templates/modules/social_account/views/profile_links.php';
		$controller = $this->get_controller();
		$model = $controller->model();
		$model->config('site_id', $this->site_id);
	}

	function has_content()
	{
		$controller = $this->get_controller();
		return ($controller->model()->get());
	}
	
	function run()
	{
		$controller = $this->get_controller();
		$content = $controller->run();
		echo $content;
	}
}
?>