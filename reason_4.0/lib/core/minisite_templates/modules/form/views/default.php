<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once(DISCO_INC . 'disco.php');

/**
 * Register form with Reason
 */
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultForm';

/**
 * DefaultForm is an extension of Disco used to work with database backed forms.
 *
 * Prior to init, this class should be passed a reference to a model via the set_model method. Using the model and parameters set in the model 
 * by the controller, the forms sets itself up during a private _init method. All the core disco methods are available for use in extending the
 * form, including:
 *
 * 1. on_every_time
 * 2. run_error_checks
 * 3. pre_error_check_actions
 * 4. post_error_check_actions
 * 5. process
 *
 * A couple new methods are available - notably:
 *
 * 1. custom_init
 *
 * Note that the process method is invoked just prior to the _process method, and is intended for custom process actions.
 *
 * To create universal views that work with thor forms, use these methods to get the name of elements:
 * 
 * 1. get_element_name_from_label - returns the name of the disco element that correspons to a label
 * 2. get_value_from_label - returns the value of the disco element that corresponds to a label
 *
 * Class variables you may want to overload:
 *
 * 1. custom_magic_transform_attributes - extra fields to grab from the directory service for the logged in user
 * 2. custom_magic_transform_methods - custom mappings from normalized elements labels to methods that return an autofill value
 * 
 * Class variables which replace defaults set by the model - overloading these should rarely be necessary:
 *
 * 1. magic_transform_attributes - fields to grab from the directory service used by magic transform methods
 * 2. magic_transform_methods - mappings of normalized elements to methods that return an autofill value
 * 3. submitted_data_hidden_fields - fields to hide when showing submitted data to e-mail recipients and those with viewing privs for form data
 * 4. submitted_data_hidden_fields_submitter_view - same as above, but when the viewer is the submitter
 *
 * Class methods that you can optionally define:
 *
 * get_custom_options_for_email - should return an array with any send_email custom options for the recipient of submitted form data
 * get_custom_options_for_email_submitter_view - should return an array with any send_email custom options for the email that goes to the submitter
 *
 * Example of a custom subject line. Define get_custom_options_for_email(). The method should return array('subject' => 'My custom subject line');
 *
 * MODEL REQUIREMENTS
 *
 * The DefaultForm view assumes the availability of these model methods
 *
 * - transform_form()
 * - get_disco_field_name($label)
 * - get_redirect_url()
 * 
 * In addition, each method in the array process_actions should be represented with two methods in the model, which define default behavior:
 *
 * - should_$process_action AND
 * - $process_action
 *
 * If, however, the above methods are defined in the view, the view methods will be preferred.
 *
 * @author Nathan White
 */

class DefaultForm extends Disco
{
	var $_model; // thor model

	/**
	 * @var array extra directory service attributes - merged with magic transform attribues
	 */
	var $custom_magic_transform_attributes;
	
	/**
	 * @var array extra magic transform methods - merged with magic transform methods array
	 */
	var $custom_magic_transform_methods;

	/**
	 * @var array directory service attributes needed by magic transform methods - if defined, replaces model defaults
	 */
	var $magic_transform_attributes; // in most cases do not define this - the model has defaults

	/**
	 * @var array directory service search parameters needed by magic transform methods - if defined, replaces directory service defaults
	 */
	var $magic_transform_params; // in most cases do not define this - the directory service has defaults

	/**
	 * @var array maps normalized element names (lower case, spaces replaced with _) to magic transform methods - if defined, replaces model defaults
	 */
	var $magic_transform_methods; // in most cases do not define this - the model has defaults
	
	/**
	 * @var array fields that should be hidden from the submitter in screen and e-mail views of data - if defined, replaces model defaults
	 */
	var $submitted_data_hidden_fields_submitter_view; // in most cases do not define this - the model has defaults

	/**
	 * @var array fields that should be hidden from everyone in screen and e-mail views of data - if defined, replaces model defaults
	 */
	var $submitted_data_hidden_fields; // in most cases do not define this - the model has defaults
	
	/**
	 * @var array of process actions
	 */
	var $process_actions = array('save_form_data', 'email_form_data_to_submitter', 'email_form_data', 'save_submitted_data_to_session');
	
	/**
	 * Inits the Disco Form
	 */
	function init( $externally_set_up = false )
	{
		$this->_init();
		parent::init();
	}
	
	/**
	 * Runs the model method transform_form.
	 */
	function _init()
	{		
		if (!isset($this->__inited)) // only run once
		{
			if (method_exists($this, 'custom_init')) $this->custom_init();
			$model =& $this->get_model();
			$model->transform_form($this);
			$this->__inited = true;
		}
	}
	
	function run_load_phase()
	{
		parent::run_load_phase();
		$this->add_required_field_comment();
	}
	
	// check for required elements at end of load phase and add the * = required field comment
	function add_required_field_comment()
	{
		if (!empty($this->required))
		{
			$order = $this->get_order();
			$this->add_element('_required_text', 'comment', array('text' => '<p class="required_indicator"><span class="requiredSymbol">*</span> = required field</p>'));
			$this->set_order(array('_required_text' => '_required_text') + $this->get_order());
		}
	}
	
	/**
	 * Default process actions
	 */
	function _process()
	{
		if ($actions = $this->get_process_actions())
		{
			foreach ($actions as $action)
			{
				if (!empty($action))
				{
					if ($this->check_and_invoke_view_or_model_method('should_'.$action)) $this->check_and_invoke_view_or_model_method($action);
				}
			}
		}
	}
		
	/**
	 * We use (abuse) the disco finish method to do our processing, leaving the process method for custom views
	 */
	function finish()
	{
		$this->_process();
	}
	
	function has_content()
	{
		return true;
	}
	
	function where_to()
	{
		$model =& $this->get_model();
		return $model->get_redirect_url();
	}

	/**
	 * Handles ajax api requests to the form. If an action is passed in the module_api_action
	 * parameter, look for and run a corresponding api_[action] method on the view or the model.
	 */
	function run_api()
	{
		$model =& $this->get_model();
		$request = $model->get_request();
		if (isset($request['module_api_action']))
		{
			$fn = 'api_'.$request['module_api_action'];
			if (method_exists($this, $fn)) $this->$fn();
			else if (method_exists($model, $fn)) $model->$fn();
		exit;
		}
	}

	function api_run_error_checks()
	{
		header('Content-type: application/json');
		$this->run_load_phase();
		$this->_run_all_error_checks();
		if ( $this->_has_errors() )
		{
			echo json_encode(array('header_text' => $this->error_header_text, 'errors' => $this->get_errors()));
		} else {
			echo json_encode(array('errors' => false));
		}
		exit;
	}

	/**
	 * Checks if the view or model has a method with name method - if so, run that method, otherwise trigger an error
	 * @param string method name to invoke
	 */
	function check_and_invoke_view_or_model_method($method)
	{
		$model =& $this->get_model();
		if (method_exists($this, $method)) return $this->$method();
		elseif (method_exists($model, $method)) return $model->$method();
		else
		{
			trigger_error('The form view or model needs to have the method ' . $method . ' defined to support all the process actions defined for the form.');
		}
		return false;
	}
		
	function get_element_name_from_label($label)
	{
		$model =& $this->get_model();
		return $model->get_disco_field_name($label);
	}
		
	function get_value_from_label( $element_label ) // {{{
	{
		if($element_name = $this->get_element_name_from_label($element_label))
		{
			return $this->get_value($element_name);
		}
		else return false;
	}
	
	/**
	 * Takes thor XML format as given by transform_thor_values_for_display and spits out a basic table view of it.
	 *
	 * This is basically a copy of Tyr make_html_table - put here for convenience.
	 */
	function get_html_table($values, $hide_empty_values = false)
	{
		$message = "<table border='0' cellspacing='0' cellpadding='7'>\n";
		foreach ( $values as $key => $value )
		{
			// Replace key with value[label], and value with value[value]
			//   N.B.: We do this here, rather than earlier, because $value['label']
			//   doesn't have to be unique, but the key of an associative array
			//   such as $this->_fields would have to be unique.
			if ( is_array($value) && array_key_exists('label', $value) )
			{
				$key = $value['label'];
				$value = ( array_key_exists('value', $value) ) ? $value['value'] : '';
			}

			// Write out arrays
			if ( is_array($value) )
			{
				$new_value = '';
				foreach ( $value as $sub_key => $sub_value )
				{
					if ( is_int($sub_key) )
						$new_value .= $sub_value . "\n";
					else
						$new_value .= $sub_key . ": " . $sub_value . "\n";
				}
				$value = $new_value;
			}
			
			$value = htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
				
			$show_item = true;
			
			if (trim($value == ''))
			{
				if($hide_empty_values)
				{
					$show_item = false;
				}
				else
				{
					$value = '(no value)';
				}
			}
			if($show_item)
			{
				$value = str_replace( "\n", "<br />\n", $value );
				$value = str_replace( "\'", "'", $value );
				$key = str_replace( '_', " ", $key );
				$key = $key . ( (substr($key, -1) != ':' && substr($key, -1) != '?') ? ':' : '' );
				$message .= "<tr><td align='right' valign='top'><strong>" . htmlspecialchars( $key, ENT_COMPAT, 'UTF-8' ) . " </strong></td><td valign='top'>" . $value . "<br /></td></tr>\n";
			}
			
		}
		$message .= "</table>\n";
		return $message;
	}
	
	/**
	 * DefaultForm must be provided with a model
	 */
	function set_model(&$model)
	{
		$this->_model =& $model;
	}
	
	function &get_model()
	{
		return $this->_model;
	}
	
	/**
	 * Allow the view process actions to be set - this could potentially be called by a controller
	 */
	function set_process_actions($process_actions)
	{
		$this->process_actions = $process_actions;
	}
	
	function get_process_actions()
	{
		return (isset($this->process_actions)) ? $this->process_actions: false;
	}
	
	function get_custom_magic_transform_attributes()
	{
		return (isset($this->custom_magic_transform_attributes)) ? $this->custom_magic_transform_attributes : false;
	}
	
	function get_custom_magic_transform_methods()
	{
		return (isset($this->custom_magic_transform_methods)) ? $this->custom_magic_transform_methods : false;
	}
	
	function get_magic_transform_attributes()
	{
		return (isset($this->magic_transform_attributes)) ? $this->magic_transform_attributes : false;
	}
	
	function get_magic_transform_params()
	{
		return (isset($this->magic_transform_params)) ? $this->magic_transform_params : false;
	}

	function get_magic_transform_methods()
	{
		return (isset($this->magic_transform_methods)) ? $this->magic_transform_methods : false;
	}
	
	function get_submitted_data_hidden_fields_submitter_view()
	{
		return (isset($this->submitted_data_hidden_fields_submitter_view)) ? $this->submitted_data_hidden_fields_submitter_view : false;
	}
	
	function get_submitted_data_hidden_fields()
	{
		return (isset($this->submitted_data_hidden_fields)) ? $this->submitted_data_hidden_fields : false;
	}
}
?>
