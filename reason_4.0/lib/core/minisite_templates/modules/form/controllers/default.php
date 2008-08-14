<?php

	$GLOBALS[ '_form_controller_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultFormController';

	/**
	 * Default Form Controller
	 *
	 * The Default Form Controller should not be used directly. It defines the API that Form Controllers need to support.
	 *
	 * Form controllers need a model in order to function, and optionally may use a view.
	 *
	 * @author Nathan White
	 */
	class DefaultFormController
	{
		var $model;
		var $view;
		var $request;
		var $cleanup_rules;
		
		function DefaultFormController()
		{
			$request =& $this->get_request();
			$cleanup_rules =& $this->get_cleanup_rules();
			if (empty($request) && !empty($cleanup_rules))
			{
				$unclean_request = conditional_stripslashes($_REQUEST);
				$request = carl_clean_vars($unclean_request, $cleanup_rules);
                $this->set_request($request);
			}
		}
		
		function init()
		{
			return false;
		}
		
		function run()
		{
			return false;
		}
		
		// GETTERS AND SETTERS
		function set_model(&$model)
		{
			$this->model =& $model;
		}
	
		function &get_model()
		{
			return $this->model;
		}
		
		function set_view(&$view)
		{
			$this->view =& $view;
		}
	
		function &get_view()
		{
			return $this->view;
		}
		
		function set_cleanup_rules(&$cleanup_rules)
		{
			$this->cleanup_rules =& $cleanup_rules;
		}
		
		function &get_cleanup_rules()
		{
			return $this->cleanup_rules;
		}
		
		function set_request(&$request)
		{
			$this->request =& $request;
		}
		
		function &get_request()
		{
			return $this->request;
		}
	}