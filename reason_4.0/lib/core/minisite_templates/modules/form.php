<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
	/**
	 * Include parent class & dependencies
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'function_libraries/url_utils.php' );

	/**
	 * Register module with Reason
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'FormMinisiteModule';

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
	class FormMinisiteModule extends DefaultMinisiteModule
	{
		var $form_controller;
		var $form_model;
		var $form_view;
				
		var $acceptable_params = array('form_model' => false,
									   'form_controller' => false,
									   'form_view' => false,
									   'form_admin_view' => false,
									   'force_login' => false,
									   'force_secure' => true);
		
		function _init_legacy()
		{
			// prep items to always do for backwards compatibility with old form module
			$this->_check_force_secure_parameter();
			$this->_check_force_login_parameter();
			$this->_redirect_old_style_url();
		}
		
		/** 
		 * Invokes the controller init method
		 */
		function init( $args=array() )
		{
			$this->_init_legacy();	
			if ($this->model_is_usable())
			{
				$controller =& $this->get_form_controller();
				$controller->init();
			}
			else parent::init();
		}
		
		/**
		 * Invokes the controller run method
		 */
		function run()
		{
			if (reason_maintenance_mode() && !reason_check_privs('db_maintenance'))
			{
				echo '<div id="form">';
				echo '<p><em>This web site is currently in maintenance mode, so forms are temporarily disabled. Please try again later.</em></p>';
				echo '</div>';
			}
			else if ($this->model_is_usable())
			{
				$controller = $this->get_form_controller();
				$controller->run();
			}
			else // present a somewhat friendly error message
			{
				echo '<div id="form">';
				echo '<p>This page should display a form, but is not set up correctly. Please try again later.</p>';
				echo '</div>';
			}
		}

		function model_is_usable()
		{
			if (!isset($this->model_is_usable))
			{
				$model =& $this->get_form_model();
				$this->model_is_usable = $model->is_usable();
			}
			return $this->model_is_usable;
		}
		
		/**
		 * Get the form model - this must be specified as a page type parameter, otherwise the thor model is used.
		 */
		function &get_form_model()
		{
			if (!isset($this->form_model))
			{
				$default_model_filename = (defined('REASON_FORMS_THOR_DEFAULT_MODEL')) ? REASON_FORMS_THOR_DEFAULT_MODEL : 'thor.php';
				if (!empty($this->params['form_model']) && is_array($this->params['form_model'])) $model_filename = $this->params['form_model']['model'];
				elseif (!empty($this->params['form_model'])) $model_filename = $this->params['form_model'];
				else $model_filename = $default_model_filename;
				
				if (reason_file_exists('minisite_templates/modules/form/models/'.$model_filename))
				{
					reason_include_once('minisite_templates/modules/form/models/'.$model_filename);
				}
				elseif (reason_file_exists($model_filename))
				{
					reason_include_once($model_filename);
				}
				elseif (file_exists($model_filename))
				{
					include_once($model_filename);
				}
				else trigger_error('The forms module was unable to load a model - the model_filename in get_form_model is ' . $model_filename, FATAL);
				$model_name = $GLOBALS[ '_form_model_class_names' ][ basename($model_filename, '.php')];	
				$model = new $model_name();
				$model->init_from_module($this);
				$this->form_model =& $model;
			}
			return $this->form_model;
		}

		/**
		 *  Get the form controller - if a thor model is being used we use our REASON_FORMS_THOR_DEFAULT_CONTROLLER by default.
		 */
		function &get_form_controller()
		{
			if (!isset($this->form_controller))
			{
				// lets check if the model is (or is based on) thor.
				$model =& $this->get_form_model();
				if ((get_class($model) == 'ThorFormModel') || (is_subclass_of($model, 'ThorFormModel')))
				{
					$default_controller_filename = (defined('REASON_FORMS_THOR_DEFAULT_CONTROLLER')) ? REASON_FORMS_THOR_DEFAULT_CONTROLLER : 'thor.php';
				}
				else $default_controller_filename = 'default.php';
				
				$controller_filename = (!empty($this->params['form_controller'])) ? $this->params['form_controller'] : $default_controller_filename;
				if (reason_file_exists('minisite_templates/modules/form/controllers/'.$controller_filename))
				{
					reason_include_once('minisite_templates/modules/form/controllers/'.$controller_filename);
				}
				elseif (reason_file_exists($controller_filename))
				{
					reason_include_once($controller_filename);
				}
				elseif (file_exists($controller_filename))
				{
					include_once($controller_filename);
				}
				else trigger_error('The forms module was unable to load a controller - the controller_filename in get_form_controller is ' . $controller_filename, FATAL);
				$model =& $this->get_form_model();
				$view =& $this->get_form_view();
				$admin_view =& $this->get_form_admin_view();
				$controller_name = $GLOBALS['_form_controller_class_names'][basename($controller_filename, '.php')];
				$controller = new $controller_name();
				$controller->set_model($model);
				if ($view) $controller->set_view($view);
				if ($admin_view) $controller->set_admin_view($admin_view);
				$this->form_controller =& $controller;
			}
			return $this->form_controller;
		}
		
		/**
		 * Get the form view from page type parameter if it exists - in some cases (like thor) the controller may handle view selection.		
		 */
		function &get_form_view()
		{
			if (!isset($this->form_view))
			{
				$view_filename = (!empty($this->params['form_view'])) ? $this->params['form_view'] : false;
				if ($view_filename)
				{
					if (reason_file_exists('minisite_templates/modules/form/views/'.$view_filename))
					{
						reason_include_once('minisite_templates/modules/form/views/'.$view_filename);
					}
					elseif (reason_file_exists($view_filename))
					{
						reason_include_once($view_filename);
					}
					elseif (file_exists($view_filename))
					{
						include_once($view_filename);
					}
					else trigger_error('The forms module was unable to load the view specified (' . $view_filename . ')', FATAL);
					$view_name = $GLOBALS['_form_view_class_names'][basename($view_filename, '.php')];
					$view = new $view_name();
					$model =& $this->get_form_model();
					$view->set_model($model);
				}
				else $view = false;
				$this->form_view =& $view;
			}
			return $this->form_view;
		}
		
		/**
		 * Get the form view from page type parameter if it exists - in some cases (like thor) the controller may handle view selection.		
		 */
		function &get_form_admin_view()
		{
			if (!isset($this->form_admin_view))
			{
				$admin_view_filename = (!empty($this->params['form_admin_view'])) ? $this->params['form_admin_view'] : false;
				if ($admin_view_filename)
				{
					if (reason_file_exists('minisite_templates/modules/form/admin_views/'.$admin_view_filename))
					{
						reason_include_once('minisite_templates/modules/form/admin_views/'.$admin_view_filename);
					}
					elseif (reason_file_exists($admin_view_filename))
					{
						reason_include_once($admin_view_filename);
					}
					elseif (file_exists($admin_view_filename))
					{
						include_once($admin_view_filename);
					}
					else trigger_error('The forms module was unable to load the admin view specified (' . $admin_view_filename . ')', FATAL);
					$admin_view_name = $GLOBALS['_form_admin_view_class_names'][basename($admin_view_filename, '.php')];
					$admin_view = new $admin_view_name();
					$model =& $this->get_form_model();
					$admin_view->set_model($model);
				}
				else $admin_view = false;
				$this->form_admin_view =& $admin_view;
			}
			return $this->form_admin_view;
		}
		
		/**
		 * People may have admin URLs bookmarked from the old form module ... 
		 *
		 * ... in this case a permanent redirect is sent to the correct admin URL
		 *
		 * This method is included only for backwards compatibility - 
		 * the query string parameter data_view should now be form_admin_view
		 *
		 * @access private
		 */
		function _redirect_old_style_url()
		{
			if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'data_view')
			{
				$redirect = carl_make_redirect(array('mode' => '', 'form_admin_view' => 'true'));
				header("Location: " . $redirect);
				exit;
			}
			if (isset($_REQUEST['thor_success']) && $_REQUEST['thor_success'] == 'true')
			{
				$redirect = carl_make_redirect(array('thor_success' => '', 'submission_key' => 'true'));
				header("Location: " . $redirect);
				exit;
			}
		}
		
		/**
		 * The old form module supported a force_login parameter - we will continue to support it though really the models
		 * are probably a better place to force login.
		 *
		 * @access private
		 */
		function _check_force_login_parameter()
		{
			if ($this->params['force_login'])
			{
				reason_require_authentication('form_login_msg');
			}
		}
		
		/**
		 * The old form module always would force a secure connection if available. We will maintain this as the default,
		 * but allow the force_secure parameters to also be set to false, thus disabling force_secure.
		 *
		 * @access private
		 */
		function _check_force_secure_parameter()
		{
			if ($this->params['force_secure'])
			{
				force_secure_if_available();
			}
		}
	}
?>
