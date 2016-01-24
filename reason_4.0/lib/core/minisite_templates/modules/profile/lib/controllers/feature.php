<?php
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );

$GLOBALS[ '_profiles_controller' ][ basename( __FILE__, '.php' ) ] = 'DefaultProfileFeatureController';

/** 
 * Just echo something for now.
 */
reason_include_once( 'config/modules/profile/config.php' );
reason_include_once('minisite_templates/modules/profile/lib/models/feature.php');
reason_include_once('minisite_templates/modules/profile/lib/views/feature.php');
 
/**
 * Gallery controller needs a profile username and a site context.
 *
 * @todo better framework for disco head items like reasonImageUploadCroppable
 */
class DefaultProfileFeatureController extends ReasonMVCController
{
	/**
	 * Add any feature id to the config if it is valid.
	 */
	function __construct()
	{
	
	}
	
	/**
	 * A chance to add head items.
	 *
	 * @todo should this method be deeper and less magical? know more about config?
	 */
	function append_head_items($head_items)
	{
		$head_items->add_javascript(JQUERY_URL, true);
		// this is a hack to get the head items in place - for reasonImageUploadCroppable things don't work
		// unless you get the head items in the <head> block - so we just invoke a dummy disco form in case we
		// need them - we need to work out better ways to handle this.
		$d = new disco();
		$params = array('head_items' => $head_items);			
		$d->add_element('badabing', 'reasonImageUploadCroppable', $params);
		unset($d);
		
		$head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH . 'FlexSlider/jquery.flexslider.js');
		$head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH . 'FlexSlider/flexslider.css');
		
		// crop plasmature element needs this ... should get this from that ...
		//$head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH . 'Jcrop/css/jquery.Jcrop.css');
		//$head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH . 'Jcrop/js/jquery.Jcrop.min.js');
		//$head_items->add_javascript(WEB_JAVASCRIPT_PATH.'image_crop.js');
		

	}
	
	function supports_editing()
	{
		return true;
	}
	
	function editing_active()
	{
		$request = $this->config('request');
		if (isset($request['edit_section']))
		{
			if ($request['edit_section'] == $this->config('section'))
			{
				return true;
			}
		}
		return false;
	}
	
	function run()
	{
		$model = $this->model(new DefaultProfileFeatureModel());
		$view = $this->view(new DefaultProfileFeatureView());
		
		// set site_id
		$model->config('site_id', $this->config('site_id'));
	
		// if editing is active set item
		if ($this->editing_active())
		{
			$view->config('edit', TRUE);
		}
			
		// get username from request if possible and set on the model
		$request = $this->config('request');
		
		if (!empty($request['username']))
		{
			$model->config('username', $request['username']);
		}
		
		// if we have a valid feature id validate it with the model - set on model and view
		if (isset($_GET['feature_id']) && $feature = filter_var($_GET['feature_id'], FILTER_SANITIZE_NUMBER_INT))
		{
			$feature_set = $model->get();
			if ($feature_set->id_is_in_featureset($feature))
			{
				$model->config('feature_id', $feature);
				$view->config('feature_id', $feature);
			}
		}
		
		if (!is_null($view) && !is_null($model))
		{
			$view->data($model->get());
			return $view->get();
		}
		elseif (!is_null($view))
		{
			return $view->get();
		}
	}

	function run_edit()
	{
		// echo 'BAHHHH I AM EDITING';
	}
	
	function run_display()
	{

	}
}
?>