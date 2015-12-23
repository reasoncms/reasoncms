<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include base class & register controller with Reason
	 */
	reason_include_once( 'minisite_templates/modules/form/controllers/abstract.php' );
	$GLOBALS[ '_form_controller_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultFormController';

	/**
	 * DefaultFormController - controller for form submission and administration in Reason.
	 *
	 * The controller inits and runs scenarios for the following supported modes:
	 *
	 * 'api', 'admin', 'form_complete', 'summary', 'form', 'unauthorized', 'closed'
	 *
	 * 1. api - The form has received an ajax API request
	 * 2. admin - The user wants to administer a form
	 * 3. form_complete - The user has just completed the form
	 * 4. summary - The user wants to see a listing of completed forms 
	 * 5. form - The user wants to create or edit a form
	 * 6. unauthorized - The user wants to do something but is unauthorized or not logged in
	 * 7. closed - The form is closed to submissions
	 *
	 * Simple customization:
	 *
	 * get_*_html customization - methods defined in the view will be used instead of the default controller methods
	 *
	 * 1.  get_view_submission_list_html	// link to view list of user submissions
	 * 2.  get_create_new_submission_html	// link to create a new submission
	 * 3.  get_module_header_html			// opening div tag
	 * 4.  get_module_footer_html			// closing div tag
	 * 5.  get_top_links_html				// consists of links to enter / exit administrative view in the default
	 * 6.  get_bottom_links_html			// consists of the login / logout bar in the default view
	 * 7.  get_return_link_html				// link to edit/create an instance of the just submitted form
	 * 8.  get_submitted_data_html			// data just submitted - submitter view
	 * 9.  get_thank_you_html				// the html to display after successful form submission
	 * 10. get_unauthorized_header_html 	// the header of the unauthorized message
	 * 11. get_unauthorized_html 			// the body of the unauthorized message after the header
	 *
	 * should_* methods - methods defined in the view (checked first) OR model will be used instead of the default controller methods
	 *
	 * 1.  should_show_submission_list_link
	 * 2.  should_show_thank_you_message
	 * 3.  should_display_return_link
	 * 4.  should_show_submitted_data
	 * 5.  should_show_login_logout
	 *
	 * Init and run phase customizations - should be less necessary:
	 *
	 * Init - the controller should provide a default init method for each supported_mode. If the *MODEL* being used defines a method with
	 *        the same name, that init method will be used in place of the controller default.
	 *
	 * Run - the controller should provide a default run method for each supported_mode. If the *VIEW* being used defines a method with
	 *       the same name, that run method will be used in place of the controller default.
	 *
	 * @author Nathan White
	 */
	class DefaultFormController extends AbstractFormController
	{
		var $cleanup_rules = array('submission_key' => array('function' => 'turn_into_string'),
									'form_admin_view' => array('function' => 'check_against_array', 'extra_args' => array('true')),
									'form_id' => array('function' => 'turn_into_int'),
									'netid' => array('function' => 'turn_into_string'),
									'module_api' => array( 'function' => 'turn_into_string' ),
									'module_identifier' => array( 'function' => 'turn_into_string' ),
									'module_api_action' => array( 'function' => 'turn_into_string' ),
								   );
								   
		var $supported_modes = array('api', 'admin', 'form_complete', 'summary', 'closed', 'form', 'unauthorized');
		
		function init_api()
		{
			$view =& $this->get_view();
			$view->init();		
		}
		
		function init_admin()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form.css');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/hide_nav.css');
			if (method_exists($model, 'init_admin_object')) $model->init_admin_object();
		}

		function init_form_complete()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form.css');
		}
		
		/**
		 * Default summary view gets a table admin object and sets its data
		 */
		function init_summary()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form.css');
			if (method_exists($model, 'init_summary_object')) $model->init_summary_object();
			//$summary =& $model->get_summary_object();
			//$summary->init();
		}
		
		function init_form()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form.css');
			$head_items->add_javascript(JQUERY_URL, true);
			$head_items->add_javascript(WEB_JAVASCRIPT_PATH .'disable_submit.js?id=disco_form&reset_time=60000');
			$view =& $this->get_view();
			$view->init();
		}
			
		function init_unauthorized()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
		}
		
		function init_closed()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form.css');
		}
		
		function run_api()
		{
			$view =& $this->get_view();
			$view->run_api();
		}

		function pre_run()
		{
			if ($this->run_mode == 'run_api') return;
			echo $this->check_view_and_invoke_method('get_module_header_html');
			echo $this->check_view_and_invoke_method('get_top_links_html');
		}
		
		function run_admin()
		{
			$model =& $this->get_model();
			$admin =& $model->get_admin_object();
			$admin->run();
		}
		
		function run_form_complete()
		{
			if ($this->check_view_and_model_and_invoke_method('should_show_submission_list_link'))
			{
				echo $this->check_view_and_invoke_method('get_view_submission_list_html');
			}
                        if ($this->check_view_and_model_and_invoke_method('should_show_submission_limit_message'))
                        {
                                echo $this->check_view_and_invoke_method('get_view_submission_limit_html');
                        }
 			if ($this->check_view_and_model_and_invoke_method('should_show_thank_you_message'))
			{
				echo $this->check_view_and_invoke_method('get_thank_you_html');
			}
			if ($this->check_view_and_model_and_invoke_method('should_display_return_link'))
			{
				echo $this->check_view_and_invoke_method('get_return_link_html');
			}
			if ($this->check_view_and_model_and_invoke_method('should_show_submitted_data'))
			{
				echo $this->check_view_and_invoke_method('get_submitted_data_html');
			}
		}
		
		function run_form()
		{
			if ($this->check_view_and_model_and_invoke_method('should_show_submission_list_link'))
			{
				echo $this->check_view_and_invoke_method('get_view_submission_list_html');
			}
			$view =& $this->get_view();
			$view->run();
		}
		
		/**
		 * 
		 */
		function run_summary()
		{
			$model =& $this->get_model();
			echo $this->check_view_and_invoke_method('get_create_new_submission_html');
			$summary =& $model->get_summary_object();
			$summary->run();
		}
		
		function run_unauthorized()
		{
			echo $this->check_view_and_invoke_method('get_unauthorized_header_html');
			echo $this->check_view_and_invoke_method('get_unauthorized_html');
		}

		function run_closed()
		{
			$model =& $this->get_model();
			echo $this->check_view_and_invoke_method('get_closed_html');
			if ($model->user_has_administrative_access())
			{
				echo '<p>[As someone with administrative access to this form, you can see the form even though it is closed. Regular users only see the message above.]</p>';
				$this->run_form();	
			}
		}

		function post_run()
		{
			if ($this->check_view_and_model_and_invoke_method('should_show_login_logout'))
			{
				echo $this->check_view_and_invoke_method('get_bottom_links_html');
			}
			echo $this->check_view_and_invoke_method('get_module_footer_html');
		}

		function get_unauthorized_header_html()
		{
			return '<h3>Access to this form is restricted</h3>';
		}
		
		function get_unauthorized_html()
		{
			$model =& $this->get_model();
			$user_netid = $model->get_user_netid();
			if (empty($user_netid))
			{
				return '<p>You are not currently logged in. If you have access to this form, the contents will be displayed after you login.</p>';
			}
		}
		
		function get_closed_html()
		{
			return '<h3>This form is closed.</h3>';
		}	
		
		function get_return_link_html()
		{
			$return_link = carl_make_link(array('submission_key' => ''));
			return '<p>You may <a href="'.$return_link.'">return to the form</a>.</p>';
		}
		
		/**
		 * @todo remove tyr dependency - icky.
		 */
		function get_submitted_data_html()
		{
			include_once(TYR_INC.'tyr.php');
			$tyr = new Tyr();
			$model =& $this->get_model();
			$html = '';
			if ($values = $model->get_values_for_show_submitted_data())
			{
				$html .= '<div class="submitted_data">';
				$html .= '<h3>You submitted:</h3>';
				$html .= $tyr->make_html_table($values, false);
				$html .= '</div>';
			}
			return $html;
		}
		
		function get_thank_you_html()
		{
			return '<p>Thank you for your submission.</p>';
		}
	 
		function get_view_submission_list_html()
		{
			$view_link = carl_construct_link( array('form_id' => ''), array('textonly', 'netid') );
			return '<p class="summary_view_link"><a href="'.$view_link.'">View Your Submission List</a></p>';
		}
		
                function get_view_submission_limit_html()
                {
                        return '<p class="submission_limit">This form permits only one submission per user.</p>';
                }

		function get_create_new_submission_html()
		{
			$create_link = carl_construct_link( array('form_id' => '0'), array('textonly', 'netid') );
			return '<p class="create_new"><a href="'.$create_link.'">Create New Form Submission</a></p>';
		}
		
		function get_required_fields_html()
		{
			return '<p class="required_indicator">* = required field</p>';
		}
		
		function get_module_header_html()
		{
			$model = $this->get_model();
			$module = $model->get_module();
			return '<div id="form" class="'.$module->get_api_class_string().'">';
		}
		
		function get_module_footer_html()
		{
			return '</div>';
		}
		
		/** 
		 *
		 */
		function get_top_links_html()
		{
			$model =& $this->get_model();
			$top_links =& $model->get_top_links();
			
			if (!empty($top_links))
			{
				foreach ($top_links as $name => $link)
				{
					$top_links_html[] = '<a href="'.$link.'">'.$name.'</a>';
				}
				$html = '<div id="formAdminControlBox"><p>' . implode(" | ", $top_links_html) . '</p></div>';
			}
			return (isset($html)) ? $html : '';
		}
		
		/**
		 *
		 */
		function get_bottom_links_html()
		{
			$netid = reason_check_authentication();
			$ret = '<div class="loginlogout">';
			$qs_array = ($netid) ? array('logout' => 'true', 'dest_page' => get_current_url()) : array('dest_page' => get_current_url());
			$qs = carl_make_link($qs_array, '', 'qs_only', true, false);
			if ($netid) $ret .= 'Logged in: '.$netid.' <a href="'.REASON_LOGIN_URL.$qs.'">Log Out</a>';
			else $ret .= '<a href="'.REASON_LOGIN_URL.$qs.'">Log In</a>';
			$ret .= '</div>';
			return $ret;
		}
		
		function should_show_submission_list_link()
		{
			return false;
		}
		
		function should_show_thank_you_message()
		{
			return true;
		}
		
		function should_display_return_link()
		{
			return true;
		}
		
		function should_show_submitted_data()
		{
			return false;
		}
		
		function should_show_login_logout()
		{
			return true;
		}
	}
?>
