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
 * An MVC based module for presenting social accounts. Currently it does just two things.
 *
 * - Sets a default model and view to display profile links.
 * - Passes the site_id as a configuration parameter to the model.
 *
 * @todo theme customizer integration to pick social icons via theme options.
 *
 * @author Nathan White
 */
class ReasonSocialAccountModule extends ReasonMVCModule
{
	var $model = 'minisite_templates/modules/social_account/models/profile_links.php';
	var $view = 'minisite_templates/modules/social_account/views/profile_links.php';
	
	// set everything up and get the model data.
	function init( $args = array() )
	{
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