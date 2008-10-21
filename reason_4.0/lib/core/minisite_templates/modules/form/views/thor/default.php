<?
include_once(THOR_INC . 'thor.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultThorForm';

/**
 * DefaultThorForm is an extension of Disco used to work with Thor forms.
 *
 * Prior to init, this class should be passed a reference to a model via the set_model method. Using the model and parameters set in the model 
 * by the controller, the forms sets itself up during the thor_init method. All the core disco methods are available for use in extending the
 * thor from, including:
 *
 * 1. on_every_time
 * 2. run_error_checks
 * 3. pre_error_check_actions
 * 4. post_error_check_actions
 * 5. process
 *
 * Note that the process method is invoked just prior to the thor_process method, and is intended for custom process actions.
 *
 * In custom thor views, if you want to modify the typical thor processes, you should overload these methods:
 *
 * 1. email_form_data
 * 2. email_form_submitter
 * 3. save_form_data
 * 4. show_submitted_data
 *
 * Methods you'll want to use in custom views:
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
	 * @var boolean whether or not the clear button should be shown
	 */
	var $show_clear_button; // set to true if you want the clear button to be available
	
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
	
	/**
	 * Default thor process actions
	 */
	function thor_process()
	{
		$model =& $this->get_model();
		if ($model->should_save_form_data()) $this->save_form_data();
		if ($model->should_email_submitter()) $this->email_form_submitter();
		if ($model->should_email()) $this->email_form_data();
		if ($model->should_show_submitted_data()) $this->save_submitted_data_to_session();
	}
	
	/**
	 * We use (abuse) the disco finish method to do our thor processing, leaving the process method for custom views
	 */
	function finish()
	{
		$this->thor_process();
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
	
	// the following are get methods for optional parameters
	
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
	
	function get_show_clear_button()
	{
		return (isset($this->show_clear_button)) ? $this->show_clear_button : false;
	}
}
?>
