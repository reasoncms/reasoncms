<?
include_once(THOR_INC . 'thor.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultThorForm';

/**
 * DefaultThorForm is an extension of Disco used to work with Thor forms.
 *
 * Prior to init, this class should be passed a reference to a model via the set_model method. 
 *
 * Using the model and parameters set in the model by the controller, the forms sets itself up during the thor_init method.
 *
 * Key aspects of this class.
 *
 * 1. Prior to on_every_time, sets the display names of elements using the ThorCore class
 * 2. Adds a method get_element_name_from_label that returns the database name from a label using the ThorCore class
 *
 * Class variables you may want to overload:
 *
 * 1. magic_transform_attributes - fields to grab from the directory service for the logged in user
 * 2. magic_transform_methods - methods to load for normalized element names if they appear in the form (make sure you define these methods)
 * 3. submitted_data_hidden_fields - fields to hide when showing submitted data to e-mail recipients and those with viewing privs for form data
 * 4. submitted_data_hiiden_fields_submitter_view - same as above, but when the viewer is the submitter
 *
 * Methods you may want to overload:
 *
 * 1. email_form_data
 * 2. email_form_submitter
 * 3. save_form_data
 * 4. show_submitted_data
 *
 * General tips
 *
 * 1. Grab everything from the model, including the form entity (only if you truly need it) - add methods to the model if they may be broadly useful
 * 2. If your page type does really wild things, you may want to extend the model and view, then create a custom page type which specifies your model
 *
 * @todo the email system could use some serious improvements
 * @author Nathan White
 */

class DefaultThorForm extends Disco
{
	var $box_class = 'BoxThor2'; // modifies the table class to thorTable
	var $_model; // thor model
	//var $_thor_core; // thor core object
	
	// The list of attributes you want returned from directory service searches - everything needed by magic_transform_methods should be included
	var $magic_transform_attributes = array('ds_firstname',
								   			'ds_lastname',
								   			'ds_fullname',
								   			'ds_phone',
								   			'ds_email',
								   			'ou',
								   			'title',
								   			'homephone',
								   			'telephonenumber');
	
	/**
	 * Keys correspond to normalized element names, and the values correspond to class methods that return a string, or if the method
	 * is not present, the value itself.
	 */
	var $magic_transform_methods = array('your_full_name' => 'get_full_name',
								 'your_name' => 'get_full_name',
					 	   	     'your_first_name' => 'get_first_name',
							     'your_last_name' => 'get_last_name',
								 'your_email' => 'get_email',
								 'your_department' => 'get_department',
								 'your_title' => 'get_title',
								 'your_home_phone' => 'get_home_phone',
								 'your_work_phone' => 'get_work_phone');

	/**
	 * Specifies any fields that should be hiddein in screen and e-mail views of submitted data (when the viewer is the submitter)
	 */
	var $submitted_data_hidden_fields_submitter_view = array('id', 'submitted_by', 'submitter_ip', 'date_created', 'date_modified');

	/**
	 * Specifies any fields that should be hidden in screen and e-mail views of submitted data
	 */
	var $submitted_data_hidden_fields = array('id');
		
	/**
	 * Setup by the thor model in _thor_init so that magic transform methods can access the magic transform values
	 * @var array
	 * @access private
	 */
	var $_magic_transform_values;
	
	/**
	 * Inits the Disco Form
	 */
	function init( $externally_set_up = false )
	{
		$this->_thor_init();
		parent::init();
	}
	
	/**
	 * Performs the key transformations needed for a thor form - including magic transform.
	 */
	function _thor_init()
	{		
		if (!isset($this->_thor_inited)) // only run once
		{
			if (method_exists($this, 'thor_init')) $this->thor_init();
			$model =& $this->get_model();
			$model->transform_thor_form($this);
			$this->_thor_inited = true;
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
			$this->add_element('_required_text', 'comment', array('text' => '<p class="required_indicator">* = required field</p>'));
			$this->set_order(array('_required_text' => '_required_text') + $this->get_order());
		}
	}
	
	function process()
	{
		$model =& $this->get_model();
		if ($model->should_save_form_data()) $this->save_form_data();
		if ($model->should_email_submitter()) $this->email_form_submitter();
		if ($model->should_email()) $this->email_form_data();
		if ($model->should_show_submitted_data()) $this->save_submitted_data_to_session();
	}
	
	/**
	 * @todo update along with model to not use Tyr and to be simpler and more easily extended in the view
	 */
	function email_form_submitter()
	{
		$model =& $this->get_model();
		if ($model->get_email_of_submitter())
		{
			$email_data = $model->get_values_for_email_submitter_view($this); // grab the values
			if ($model->should_email_link())
			{
				$email_link = $model->get_link_for_email_submitter_view($this);
				if (!empty($email_link))
				{
					$options['access_link'] = $email_link;
				}
				else
				{
					$options['origin_link'] = carl_construct_link(array('')); // we use the origin if no link to edit is available
				}
			}
			$options['subject'] = 'Form Submission Confirmation: ' . $model->get_form_name(); // should i include form name
			$options['header'] = $model->get_form_name() . " - " . "Successfully Submitted " . carl_date('l, F jS Y \\a\t h:i:s A');
			$options['to'] = $model->get_email_of_submitter();
			$options['disclaimer'] = false;
			$model->send_email($email_data, $options);
		}
	}
	
	/**
	 * @todo work out how to handle email_link
	 */
	function email_form_data()
	{
		$model =& $this->get_model();
		$email_data = $model->get_values_for_email($this);
		$email_link = $email_link = $model->get_link_for_email($this);
		if ($model->get_email_of_recipient())
		{
			if (!empty($email_link)) $options['access_link'] = $email_link;
			$options['to'] = $model->get_email_of_recipient();
			$model->send_email($email_data, $options);
		}
		else
		{
			trigger_error('submitter e-mail could not be determined!');
		}
	}
	
	function save_form_data()
	{
		$model =& $this->get_model();
		$save_values = $model->get_values_for_save($this);
		$model->save_data($save_values);
	}

	function show_submitted_data()
	{
		$model =& $this->get_model();
		$model->get_values_for_show_submitted_data($this);
	}
	
	function save_submitted_data_to_session()
	{
		$model =& $this->get_model();
		$model->save_submitted_data_to_session($this);
	}

	function has_content()
	{
		return true;
	}
	
	function where_to()
	{
		$model =& $this->get_model();
		$form_id = ($model->is_editable() && $model->get_user_netid()) ? $model->get_form_id() : '';
		$redirect = carl_make_redirect(array('thor_success' => 'true', 'form_id' => $form_id));
		return $redirect;
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
		
	// magic string autofill methods
	function get_first_name()
	{
		return ($this->_get_simple_field_from_key('edupersonnickname')) 
			   ? $this->_get_simple_field_from_key('edupersonnickname') 
			   : $this->_get_simple_field_from_key('ds_firstname');
	}

	function get_full_name()
	{
		return $this->_get_simple_field_from_key('ds_fullname');
	}
		
	function get_last_name()
	{
		return $this->_get_simple_field_from_key('ds_lastname');
	}
	
	function get_email()
	{
		return $this->_get_simple_field_from_key('ds_email');
	}
	
	function get_department()
	{
		return $this->_get_multiple_fields_from_key('ou');
		
	}
	
	function get_title()
	{
		return $this->_get_multiple_fields_from_key('title');
	}
	
	function get_home_phone()
	{
		return $this->_get_clean_phone_number('homephone');
	}
	
	function get_work_phone()
	{
		return $this->_get_clean_phone_number('telephonenumber');
	}
	
	function _get_clean_phone_number($key)
	{
		$dir_info =& $this->get_magic_transform_values();
		if (!empty($dir_info[$key]))
		{
			$clean_phone = (is_array($dir_info[$key])) ? $dir_info[$key][0] : $dir_info[$key];
			$clean_phone = (substr($clean_phone, 0, 3) == '+1 ') ? str_replace(' ', '-', substr($clean_phone, 3)) : $clean_phone;
			return $clean_phone;
		}
	}
	
	function _get_simple_field_from_key($key)
	{
		$dir_info =& $this->get_magic_transform_values();
		return (!empty($dir_info[$key])) ? $dir_info[$key][0] : '';
	}
	
	function _get_multiple_fields_from_key($key)
	{
		$dir_info =& $this->get_magic_transform_values();
		if (!empty($dir_info[$key]))
		{
			if (count($dir_info[$key]) > 1)
			{
				$str = '';
				foreach ($dir_info[$key] as $k=>$v)
				{
					$str .= $v . '; ';
				}
				return substr($str, 0, -2);
			}
			else
			{
				return $dir_info[$key][0];
			}
		}
	}

	function &get_submitted_data_hidden_fields()
	{
		return $this->submitted_data_hidden_fields;
	}
	
	function &get_submitted_data_hidden_fields_submitter_view()
	{
		return $this->submitted_data_hidden_fields_submitter_view;
	}

 	function &get_magic_transform_methods()
 	{
 		return $this->magic_transform_methods;
 	}
 	
	function &get_magic_transform_attributes()
	{
		return $this->magic_transform_attributes;
	}
	
	function &get_magic_transform_values()
	{
		return $this->_magic_transform_values;
	}
	
	function set_magic_transform_values($values)
	{
		$this->_magic_transform_values =& $values;
	}
	
	/**
	 * DefaultThorForm must be provided with a thor model
	 */
	function set_model(&$model)
	{
		$this->_model =& $model;
	}
	
	function &get_model()
	{
		return $this->_model;
	}
}
?>
