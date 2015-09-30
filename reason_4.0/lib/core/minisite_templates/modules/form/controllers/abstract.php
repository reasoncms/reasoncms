<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Register controller with Reason
	 */
	$GLOBALS[ '_form_controller_class_names' ][ basename( __FILE__, '.php') ] = 'AbstractFormController';

	/**
	 * Abstract Form Controller
	 *
	 * Form controllers need a model in order to function, and optionally may use a view.
	 *
	 * A form controller should define an instance variable called supported_modes.
	 *
	 * init_$mode methods needs to be defined in the model or controller
	 * run_$mode methods need to be defined in the view or controller
	 *
	 * The model must minimally support is_$mode methods for each supported controller mode.
	 *
	 * @author Nathan White
	 */
	class AbstractFormController
	{
		/**
		 * @var object form model
		 */
		var $model;
		
		/**
		 * @var object form view
		 */
		var $view;
		
		/**
		 * @var object admin view
		 */
		var $admin_view;
		
		/**
		 * A list of modes supported by the form controller
		 * @var array supported_modes
		 */
		var $supported_modes;

		/**
		 * Cleanup rules for user input. Input that passes becomes a part of $this->request
		 * @var array cleanup_rules
		 */
		var $cleanup_rules;
		
		/**
		 * @var array request
		 */
		var $request;

		/**
		 * @var string run_mode
		 */
		var $run_mode;
		
		function AbstractFormController()
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
		
		/**
		 * Init method does the following:
		 *
		 * 1. Runs setup controller
		 * 2. Runs setup model
		 * 3. Runs setup view
		 * 4. Invoke the appropriate init method obtained with the get_init_method method
		 */
		function init( )
		{
			$this->setup_mvc();
			$init_mode = $this->_get_mode_with_prefix('init_');
			if ($init_mode) $this->check_model_and_invoke_method($this->_get_mode_with_prefix('init_'));
		}
		
		function setup_mvc()
		{
			$this->setup_model();
			$this->setup_view();
			$this->setup_admin_view();
			$this->setup_controller();
			$this->check_view_and_invoke_model_method('validate_request');
		}
		/**
		 * Provide the request variables to the model
		 */
		function setup_model()
		{
			// setup the model according to parameters
			$model =& $this->get_model();
			$request =& $this->get_request();
			$model->handle_request_vars($request);
		}
		
		/**
		 * If we do not have a view - ask the model to setup the view (if $model->setup_view exists)
		 *
		 * Otherwise pass a reference to the view if the model has a set_view method.
		 */
		function setup_view()
		{
			$model =& $this->get_model();
			$view =& $this->get_view();
			if (!isset($view) || ($view == false))
			{
				if (method_exists($model, 'setup_view'))
				{
					$view =& $model->setup_view();
					$this->set_view($view);
				}
			}
			elseif (method_exists($model, 'set_view'))
			{
				$model->set_view($view);
			}
		}
		
		/**
		 * If we do not have an admin view - ask the model to setup the admin view (if $model->setup_admin_view exists)
		 * 
		 * Otherwise pass a reference to the admin view if the model has a set_admin_view method.
		 */
		function setup_admin_view()
		{
			$model =& $this->get_model();
			$admin_view =& $this->get_admin_view();		
			if (!isset($admin_view) || ($admin_view == false))
			{
				if (method_exists($model, 'setup_admin_view'))
				{
					$admin_view =& $model->setup_admin_view();
					$this->set_admin_view($admin_view);
				}
			}
			elseif (method_exists($model, 'set_admin_view')) $model->set_admin_view($admin_view);
		}

		/**
		 * Hook for extra controller setup prior to our init method
		 */
		function setup_controller()
		{
			return false;
		}
		
		function run()
		{
			$this->run_mode = $this->_get_mode_with_prefix('run_');
			echo $this->check_view_and_invoke_method('pre_run');
			if ($this->run_mode) echo $this->check_view_and_invoke_method($this->run_mode);
			echo $this->check_view_and_invoke_method('post_run');
		}

		/**
		 * Run action that always occurs prior to the run method specific to the mode
		 */
		function pre_run()
		{
			return false;
		}

		/**
		 * Run action that always occurs after the run method specific to the mode
		 */
		function post_run()
		{
			return false;
		}
		
		/**
		 * Checks if the view has a method with name method - if so, run that method, otherwise run the controller method
		 * @param string method name to invoke
		 */
		function check_view_and_invoke_method($method, $show_errors = true)
		{
			$view =& $this->get_view();
			if (method_exists($view, $method)) return $view->$method();
			elseif (method_exists($this, $method)) return $this->$method();
			elseif ($show_errors) trigger_error('The form controller called a method ' . $method . ' that does not exists in the view or controller');
			return false;
		}
		
		/**
		 * Checks if the model has a method with name method - if so, run that method, otherwise run the controller method
		 * @param string method name to invoke
		 */
		function check_model_and_invoke_method($method)
		{
			$model =& $this->get_model();
			if (method_exists($model, $method)) return $model->$method();
			elseif (method_exists($this, $method)) return $this->$method();
			else trigger_error('The form controller called a method ' . $method . ' that does not exists in the model or controller');
			return false;
		}
		
		/**
		 * Checks if the view has a method with name method - if so, run that method, otherwise run the model method
		 * @param string method name to invoke
		 */
		function check_view_and_invoke_model_method($method, $show_errors = true)
		{
			$model =& $this->get_model();
			$view =& $this->get_view();
			if (method_exists($view, $method)) return $view->$method();
			elseif (method_exists($model, $method)) return $model->$method();
			elseif ($show_errors) trigger_error('The form controller called a method ' . $method . ' that does not exists in the view or model');
			return false;
		}
		
		/**
		 * Checks if the view or model has a method with name method - if so, run that method, otherwise run the controller method
		 * @param string method name to invoke
		 */
		function check_view_and_model_and_invoke_method($method, $show_errors = true)
		{
			$model =& $this->get_model();
			$view =& $this->get_view();
			if (method_exists($view, $method)) return $view->$method();
			elseif (method_exists($model, $method)) return $model->$method();
			elseif (method_exists($this, $method)) return $this->$method();
			elseif ($show_errors) trigger_error('The form controller called a method ' . $method . ' that does not exists in the view, model, or controller');
			return false;
		}

		/**
		 * Runs is_$mode methods to determine what the "mode" of the module is - returns appropriate method with a given prefix.
		 * @param string mode prefix
		 * @return string module mode with prefix
		 * @access private
		 */
		function _get_mode_with_prefix($prefix)
		{
			$modes =& $this->get_supported_modes();
			if (!empty($modes))
			{
				$model =& $this->get_model();
				foreach ($modes as $mode)
				{
					$is_method = "is_" . $mode;
					if (method_exists($model, $is_method))
					{
						if ($model->$is_method()) return $prefix.$mode;
					}
					else trigger_error('The form controller supports the mode ' . $mode . ' but the model used does not have an is_' . $mode . ' method defined');
				}
			}
			return false;
		}
			
		// GETTERS AND SETTERS
		function set_supported_modes(&$supported_modes)
		{
			$this->supported_modes =& $supported_modes;
		}
		
		function &get_supported_modes()
		{
			return $this->supported_modes;
		}
		
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

		function set_admin_view(&$admin_view)
		{
			$this->admin_view =& $admin_view;
		}
		
		function &get_view()
		{
			return $this->view;
		}

		function &get_admin_view()
		{
			return $this->admin_view;
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
