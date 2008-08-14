<?php

	reason_include_once( 'minisite_templates/modules/form/controllers/default.php' );
	$GLOBALS[ '_form_controller_class_names' ][ basename( __FILE__, '.php') ] = 'ThorDefaultFormController';

	/**
	 * ThorDefaultFormController
	 *
	 * Framework to init and run a thor form - a thor form that functions as the "view" is required.
	 *
	 * The controller basically inits and runs scenarios for the following cases:
	 *
	 * 1. The user is an administrator and has requested the administrative view
	 * 2. The user has proper privileges and just completed the form
	 * 3. The user has proper privileges and wants to fill out a new form 
	 * 4. The user has proper privileges and wants to see a listing of completed forms
	 * 5. The user has proper privileges and wants to edit a particular form.
	 * 6. The user is unauthorized (or not logged in) and cannot see the form
	 *
	 * The controller in this case gives the thor view a lot of power ... 
	 *
	 * This is done because disco wraps logic and presentation fairly tightly - to allow for fairly easy extension we let the
	 * thor view (which is disco based) do as little or as much as it wants. The controller provides a default scenario for all cases, 
	 * but will defer to the particular thor view selected in the content manager if it provides an alternative.
	 *
	 * The controller will check if the thor view has any of these methods and use them in place of its default methods. For basic
	 * modifications, defining the get*html methods should be sufficient, though the init and run methods themselves can also be
	 * defined in a view class.
	 *
	 * Overloadable for minor changes - just define the method in the view - simple:
	 *
	 * 1. get_view_submission_list_html		// link to view list of user submissions
	 * 2. get_create_new_submission_html	// link to create a new submission
	 * 3. get_module_header_html			// opening div tag
	 * 4. get_module_footer_html			// closing div tag
	 * 5. get_top_links_html				// consists of links to enter / exit administrative view in the default
	 * 6. get_bottom_links_html				// consists of the login / logout bar in the default view
	 * 7. get_return_link_html				// link to edit/create an instance of the just submitted form
	 * 8. get_submitted_data_html			// data just submitted - submitter view
	 * 9. get_thank_you_html				// the html to display after successful form submission
	 * 10. get_unauthorized_header_html 	// the header of the unauthorized message
	 * 11. get_unauthorized_html 			// the body of the unauthorized message after the header
	 *
	 * Overloadable for major changes - just define the method in the view - more complicated (these may include calls to the html methods above):
	 *
	 * 1. init_admin_view
	 * 2. init_form_complete_view
	 * 3. init_summary_view
	 * 4. init_form_view
	 * 5. init_unauthorized_view
	 * 6. run_admin_view
	 * 7. run_form_complete_view
	 * 8. run_summary_view
	 * 9. run_form_view
	 * 10. run_unauthorized_view
     *
	 * This allows for basically the whole framework to be extended by the form view, though this isn't necessarily the best approach.
	 *
	 * More involved modules based upon thor forms could use of a custom page type that specifies explicitly a model, view, and controller. 
	 *
	 * @author Nathan White
	 *
	 */
	class ThorDefaultFormController extends DefaultFormController
	{
		var $cleanup_rules = array('thor_success' => array('function' => 'check_against_array', 'extra_args' => array('true')),
								   'form_admin_view' => array('function' => 'check_against_array', 'extra_args' => array('true')),
								   'form_id' => array('function' => 'turn_into_int'),
								   'netid' => array('function' => 'turn_into_string'));
		
		/**
		 * The ThorDefaultFormController init method does the following:
		 *
		 * 1. Setup the model based upon user input - including spoofed netids for administrators
		 * 2. Setup the view by asking the model for the view selected in the content manager
		 * 3. Invoke the appropriate init method (admin, form, or unauthorized)
		 */
		function init( )
		{
			// spoof the netid if it is allowed!
			if (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE) $this->_set_spoofed_netid_if_allowed();
			
			// setup the model according to parameters
			$model =& $this->get_model();
			if ($this->admin_view_requested() && $model->admin_view_requires_login()) reason_require_authentication();
			if ($this->admin_view_requested()) $model->set_user_requested_admin_view(true);
			if ($this->form_submission_appears_complete()) $model->set_form_submission_appears_complete(true);
			if ($this->form_id_is_valid()) $model->set_form_id($this->request['form_id']);
			
			// ask the model for the thor view - which is set in the content manager
			$view =& $model->get_thor_view();
			$this->set_view($view);
			
			// ask the model some questions to determine how to proceed
			if ($model->user_requested_admin_view() && $model->user_has_administrative_access())
			{
				$method_name = 'init_admin_view';
			}
			elseif ($model->form_submission_appears_complete() && $model->form_submission_is_complete())
			{
				$method_name = 'init_form_complete_view';
			}
			elseif ($model->form_allows_multiple() && $model->user_has_submitted() && !$model->get_form_id() && ($model->get_form_id() !== 0) )
			{
				$method_name = 'init_summary_view';
			}
			elseif ($model->user_has_access_to_fill_out_form())
			{
				$method_name = 'init_form_view';
			}
			else
			{
				$method_name = 'init_unauthorized_view';
			}
			
			// invoke the appropriate init method
			$this->check_view_and_invoke_method($method_name);
		}
		
		/**
		 * Checks if the view has a method with name method - if so, run that method, otherwise run the controller method
		 * @param string method name to invoke
		 */
		function check_view_and_invoke_method($method)
		{
			$view =& $this->get_view();
			$method_exists = method_exists($view, $method);
			if (method_exists($view, $method)) $view->$method();
			else
			{
				return $this->$method();
			}
		}
		
		function init_admin_view()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/hide_nav.css');
			$thor_admin =& $model->get_thor_admin_object();
			$thor_admin->init_thor_admin();
		}

		function init_form_complete_view()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
		}
		
		/**
		 * Default summary view inits a table admin object
		 */
		function init_summary_view()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
			$thor_summary =& $model->get_thor_summary_object();
			$user_values = $model->get_values_for_user_summary_view();
			$thor_summary->set_data_from_array($user_values);		
		}
		
		function init_form_view()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_error.css');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
			$view =& $this->get_view();
			$view->init();
		}
	
		
		function init_unauthorized_view()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
		}
		
		function run()
		{
			$model =& $this->get_model();
			
			echo $this->check_view_and_invoke_method('get_module_header_html');
			echo $this->check_view_and_invoke_method('get_top_links_html');
			
			// ask the model some questions to determine how to proceed
			if ($model->user_requested_admin_view() && $model->user_has_administrative_access())
			{
				$method_name = 'run_admin_view';
			}
			elseif ($model->form_submission_appears_complete() && $model->form_submission_is_complete())
			{
				$method_name = 'run_form_complete_view';
			}
			elseif ($model->form_allows_multiple() && $model->user_has_submitted() && !$model->get_form_id() && ($model->get_form_id() !== 0) )
			{
				$method_name = 'run_summary_view';
			}
			elseif ($model->user_has_access_to_fill_out_form())
			{
				$method_name = 'run_form_view';
			}
			else
			{
				$method_name = 'run_unauthorized_view';
			}
			echo $this->check_view_and_invoke_method($method_name);
			echo $this->check_view_and_invoke_method('get_bottom_links_html');
			echo $this->check_view_and_invoke_method('get_module_footer_html');
		}
		
		function run_admin_view()
		{
			$model =& $this->get_model();
			$thor_admin =& $model->get_thor_admin_object();
			$thor_admin->run();
		}
		
		function run_form_complete_view()
		{
			$model =& $this->get_model();
			if ($model->form_allows_multiple() && $this->model->user_has_submitted())
			{
				echo $this->check_view_and_invoke_method('get_view_submission_list_html');
			}
			if ($model->get_thank_you_message())
			{
				echo $this->check_view_and_invoke_method('get_thank_you_html');
			}
			if ($model->should_display_return_link())
			{
				echo $this->check_view_and_invoke_method('get_return_link_html');
			}
			if ($model->should_show_submitted_data())
			{
				echo $this->check_view_and_invoke_method('get_submitted_data_html');
			}
		}
		
		function run_form_view()
		{
			$model =& $this->get_model();
			if ($this->model->form_allows_multiple() && $this->model->user_has_submitted())
			{
				echo $this->check_view_and_invoke_method('get_view_submission_list_html');
			}
			$view =& $model->get_thor_view();
			$view->run();
		}
		
		/**
		 * 
		 */
		function run_summary_view()
		{
			$model =& $this->get_model();
			echo $this->check_view_and_invoke_method('get_create_new_submission_html');
			$thor_summary =& $model->get_thor_summary_object();
			$thor_summary->run();
		}
		
		function run_unauthorized_view()
		{
			echo $this->check_view_and_invoke_method('get_unauthorized_header_html');
			echo $this->check_view_and_invoke_method('get_unauthorized_html');
		}
		
		function form_submission_appears_complete()
		{
			$request =& $this->get_request();
			return (isset($request['thor_success']) && ($request['thor_success'] == 'true'));
		}
		
		function form_id_is_valid()
		{
			$model =& $this->get_model();
			$request =& $this->get_request();
			$form_id = (isset($request['form_id'])) ? $request['form_id'] : NULL;
			return $model->form_id_is_valid($form_id);
		}
		
		function admin_view_requested()
		{
			$request =& $this->get_request();
			return (isset($request['form_admin_view']) && ($request['form_admin_view'] == 'true'));
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
		
		function get_return_link_html()
		{
			$return_link = carl_make_link(array('thor_success' => ''));
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
			
			$html = '<div class="submitted_data">';
        	$html .= '<h3>You submitted:</h3>';
        	$html .= $tyr->make_html_table($model->get_values_for_show_submitted_data(), false);
        	$html .= '</div>';
        	return $html;
		}
		
		function get_thank_you_html()
		{
			$model =& $this->get_model();
			return $model->get_thank_you_message();
		}
	 
		function get_view_submission_list_html()
		{
			$view_link = carl_construct_link( array('form_id' => ''), array('textonly', 'netid') );
			return '<p class="summary_view_link"><a href="'.$view_link.'">View Your Submission List</a></p>';
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
			return '<div id="form">';
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
		 * Return a login / logout interface if one of these conditions is true
		 *
		 * - magic string autofill is available
		 * - the form is editable
		 * - the form has a viewing group that requires authentication
		 *
		 */
		function get_bottom_links_html()
		{
			$model =& $this->get_model();
			if ($model->get_magic_string_autofill() || $model->is_editable() || $model->form_requires_authentication())
			{
				$netid = reason_check_authentication();
				$ret = '<div class="loginlogout">';
				$qs_array = ($netid) ? array('logout' => 'true', 'dest_page' => get_current_url()) : array('dest_page' => get_current_url());
				$qs = carl_make_link($qs_array, '', 'qs_only', true, false);
				if ($netid) $ret .= 'Logged in: '.$netid.' <a href="'.REASON_LOGIN_URL.$qs.'">Log Out</a>';
				else $ret .= '<a href="'.REASON_LOGIN_URL.$qs.'">Log In</a>';
				$ret .= '</div>';
			}
			else $ret = '';
			return $ret;
		}
		
		/**
		 * On development instances, we allow admins to specify a netid other than their own for testing purposes and set it in the model
		 */
		function _set_spoofed_netid_if_allowed()
		{
			$netid = reason_check_authentication();
			$request =& $this->get_request();
			$requested_netid = (isset($this->request['netid'])) ? $request['netid'] : false;
			if (!empty($requested_netid) && !empty($netid) && ($requested_netid != $netid))
			{
				$user_id = get_user_id($netid);
				if (reason_user_has_privs($user_id, 'pose_as_other_user'))
				{
					$model =& $this->get_model();
					$model->set_user_netid($requested_netid);
				}
			}
		}
	}
?>