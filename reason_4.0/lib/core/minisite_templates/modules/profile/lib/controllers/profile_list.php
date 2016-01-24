<?php
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );

$GLOBALS[ '_profiles_controller' ][ basename( __FILE__, '.php' ) ] = 'DefaultProfileListController';

/** 
 * Include model and view
 */
reason_include_once('minisite_templates/modules/profile/lib/models/profile_list.php');
reason_include_once('minisite_templates/modules/profile/lib/views/profile_list.php');

/**
 * The default uses a basic model and view and doesn't respond to any parameters.
 */
class DefaultProfileListController extends ReasonMVCController
{
	
	function run()
	{
		if (!$this->config('model')) $this->model(new DefaultProfileListModel());
		if (!$this->config('view')) $this->view(new DefaultProfileListView());
		
		$view = $this->view();
		$model = $this->model();
		$model->config('site_id', $this->config('site_id'));
		
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
}
?>