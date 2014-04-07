<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include base class & other dependencies
 */
reason_include_once( 'minisite_templates/modules/form/models/abstract.php' );
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

/**
 * Register model with Reason
 */
$GLOBALS[ '_form_model_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultFormModel';

/**
 * The DefaultFormModel provides the basis for our default form interaction model in the reason environment.
 *
 * It provides is_* methods for our basic form modes:
 *
 * 'admin', 'form_complete', 'summary', 'form', 'unauthorized', 'closed'
 *
 * 1. admin - The user wants to administer a form
 * 2. form_complete - The user has just completed the form
 * 3. summary - The user wants to see a listing of completed forms 
 * 4. form - The user wants to create or edit a form
 * 5. unauthorized - The user wants to do something but is unauthorized or not logged in
 * 6. closed - The form is closed to submissions
 *
 * The default is_* methods return false for everything - so nothing will be done.
 * 
 * We add an init_from_module(&$module) method that causes the following to happen
 * 
 * 1. The model site_id variable will be set to the module site_id
 * 2. The model page_id variable will be set to the module page_id
 * 3. The model head_items variable will be be set to refer to the module head_items variable
 * 4. The model params will be set to refer to the module params
 * 5. The module object will be passed to a method called localize that does nothing by default
 *
 * Form models that extend the default should define several methods - these are:
 *
 * is_usable - should return whether or not the form is usable ... can trigger errors for developers
 * transform_form - transforms the view according to the particular model
 * validate_request - should set the internal form_id if get_requested_form_id() returns a form that is valid in the current environment
 *                    this method might also perform redirects if the request vars provided suggest a misconfiguration or access control problem
 *
 * Models may also define various "should" methods that provide defaults for what should be shown or hidden, or whether or not a particular process
 * action should be run. If defaults are not defined in the model for should_* methods, they will be required in all views which use the model.
 *
 */
 
class DefaultFormModel extends AbstractFormModel
{	
	var $_site_id;
	var $_page_id;
	var $_requested_form_id;
	var $_form_id;
	var $_head_items;
	var $_params;
	var $_form_submission_key;
	var $_disco_field_name = array();
	
	function DefaultFormModel()
	{
	}
	
	function init()
	{
	}
	
	function init_from_module(&$module)
	{
		if (isset($module->site_id)) $this->set_site_id($module->site_id);
		if (isset($module->page_id)) $this->set_page_id($module->page_id);
		if (isset($module->parent->head_items)) $this->set_head_items($module->parent->head_items);
		if (isset($module->params)) $this->set_params($module->params);
		$this->localize($module);
	}
	
	/**
	 * Process the request vars that are passed from the controller
	 */
	function handle_request_vars(&$request_vars)
	{
		if (isset($request_vars['netid']) && !empty($request_vars['netid'])) $this->set_spoofed_netid_if_allowed($request_vars['netid']);
		if (isset($request_vars['form_admin_view']) && ($request_vars['form_admin_view'] == 'true')) $this->set_user_requested_admin(true);
		if (isset($request_vars['form_id']) && (strlen($request_vars['form_id']) > 0)) $this->set_requested_form_id($request_vars['form_id']);
		if (isset($request_vars['submission_key']) && !empty($request_vars['submission_key'])) $this->set_form_submission_key($request_vars['submission_key']);
	}
	
	/**
 	 * Should validate the request - the outcome would typically result in setting the internal form_id
	 */
	function validate_request()
	{
		return false;
	}

	function is_admin()
	{
		return false;
	}
	
	function is_form_complete()
	{
		return false;
	}
	
	function is_summary()
	{
		return false;
	}
	
	function is_form()
	{
		return false;
	}
	
	function is_unauthorized()
	{
		return false;
	}

	function is_closed()
	{
		return false;
	}
	
	function should_save_form_data()
	{
		return false;
	}
	
	function should_email_form_data_to_submitter()
	{
		return false;
	}
	
	function should_email_form_data()
	{
		return false;
	}
	
	function should_save_submitted_data_to_session()
	{
		return false;
	}
	
	function set_site_id($site_id)
	{
		$this->_site_id = $site_id;
	}
	
	function get_site_id()
	{
		return $this->_site_id;
	}
	
	function set_page_id($page_id)
	{
		$this->_page_id = $page_id;
	}

	function get_page_id()
	{
		return $this->_page_id;
	}
	
	function set_requested_form_id($requested_form_id)
	{
		$this->_requested_form_id = $requested_form_id;
	}

	function get_requested_form_id()
	{
		return $this->_requested_form_id;
	}

	function set_form_id($form_id)
	{
		$this->_form_id = $form_id;
	}
	
	function get_form_id()
	{
		return $this->_form_id;
	}
	
	function set_form_submission_key($key)
	{
		$this->_form_submission_key = $key;
	}
	
	function get_form_submission_key()
	{
		return $this->_form_submission_key;
	}
	
	function set_head_items(&$head_items)
	{
		$this->_head_items =& $head_items;
	}
	
	function &get_head_items()
	{
		return $this->_head_items;
	}
	
	function set_params(&$params)
	{
		$this->_params =& $params;
	}
	
	function &get_params()
	{
		return $this->_params;
	}

	function &get_top_links()
	{
		$top_links = array();
		return $top_links;
	}
	
	/**
	 * If the actual user has the privilege "pose_as_other_user" this method
	 * will set the internal netid returned by get_user_netid to the spoofed
	 * netid passed into this method. Very useful for testing purposes.
	 */
	function set_spoofed_netid_if_allowed($requested_netid = false)
	{
		$netid = reason_check_authentication();
		if (!empty($requested_netid) && !empty($netid) && ($requested_netid != $netid))
		{
			$user_id = get_user_id($netid);
			if (reason_user_has_privs($user_id, 'pose_as_other_user'))
			{
				$this->set_user_netid($requested_netid);
			}
		}
	}
	
	/**
	 * Gets the user_netid - sets it to the logged in users netid if it has not been set
	 */
	function get_user_netid()
	{
		if (!isset($this->_user_netid))
		{
			$this->set_user_netid(reason_check_authentication());
		}
		return $this->_user_netid;
	}
	
	/**
	 * Gets the email of the recipient - by default the logged in users netid
	 */
	function get_email_of_recipient()
	{
		return $this->get_user_netid();
	}
	
	/**
	 * Sets the working user_netid
	 */
	function set_user_netid($netid = false)
	{
		$this->_user_netid = $netid;
	}

	function get_redirect_url()
	{
		if (!isset($this->_redirect_url))
		{
			$form_id = ($this->get_form_id()) ? $this->get_form_id() : '';
			$this->_redirect_url = carl_make_redirect(array('submission_key' => $this->create_form_submission_key(), 'form_id' => $form_id));
		}
		return $this->_redirect_url;
	}
	
	function create_form_submission_key()
	{
		return md5(uniqid(rand()));
	}
	
	/**
	 * Gets directory information for the current user_netid
	 *
	 * @param array attributes - custom attributes to return from search
	 * @param array params - key value pairs of custom search params (see dir_service for details)
	 * @todo handle for cases where attribute set requested is different
	 */
	function &get_directory_info($attributes = false, $params = false)
	{
		$netid = $this->get_user_netid();
		if ($netid && !isset($this->_directory_info[$netid]))
		{
			$dir = new directory_service();
			if (is_array($params))
			{
				list($service, $pars) = each($params);
				$dir->set_search_params($service, $params);
			}
			if ($attributes) $dir->search_by_attribute('ds_username', $netid, $attributes);
			else $dir->search_by_attribute('ds_username', $netid);
			$this->_directory_info[$netid] = $dir->get_first_record();
		}
		if (!$netid)
		{
			$ret = false;
			return $ret;
		}
		return $this->_directory_info[$netid];
	}

	/**
	 * Uses Tyr to send an e-mail, given an array of key value pairs where the key is the item display name, and the value is the value
	 *
	 * The options array can define any of the following keys - if none are defined default behaviors will be used
	 * 
	 * - to: netid, array of netids, or comma separated string indicating who the e-mails should go to
	 * - from: netid or full e-mail address for the from field
	 * - reply-to: netid or full e-mail address for the reply-to field
	 * - subject: string indicating the subject line
	 * - header: string containing header line for the first line of the e-mail
	 * - dislaimer: boolean indicating whether or not to add a dislaimer - defaults to true
	 * - origin_link: link indicating where the URL where the form was filled out
	 * - access_link: link indicating where the form can be accessed for view/edit
	 * - email_empty_fields: boolean indicating whether or not to email empty fields
	 * - attachments: array of filename => filepath for message attachments
	 *
	 * @param array data - key/value pairs of the data to e-mail
	 * @param array options - allows various parameters to be optionally passed
	 * @todo remove this weird mini system and the tyr "message" framework and use e-mail class directly
	 */
	function send_email(&$data, $options = array())
	{
		$to = (isset($options['to'])) ? $options['to'] : $this->get_email_of_recipient();
		$to = (is_array($to)) ? implode(",", $to) : $to;
		if (strlen(trim($to)) > 0)
		{
			if (isset($options['origin_link'])) $messages['all']['form_origin_link'] = $options['origin_link'];
			if (isset($options['access_link'])) $messages['all']['form_access_link'] = $options['access_link'];
			$messages['all']['hide_empty_values'] = (isset($options['email_empty_fields'])) ? !$options['email_empty_fields'] : true;
			$messages['all']['form_title'] = (isset($options['header'])) ? $options['header'] : '';
			$messages[0]['to'] = $to;
			$messages[0]['from'] = (isset($options['from'])) ? $options['from'] : TYR_REPLY_TO_EMAIL_ADDRESS;
			$messages[0]['reply-to'] = (isset($options['reply-to'])) ? $options['reply-to'] : TYR_REPLY_TO_EMAIL_ADDRESS;
			$messages[0]['subject'] = (isset($options['subject'])) ? $options['subject'] : 'Response to Form';
			if (!empty($options['attachments'])) $messages['0']['attachments'] = $options['attachments'];
			$tyr = new Tyr($messages, $data);
			$tyr->add_disclaimer = (isset($options['disclaimer']) && ($options['disclaimer'] == false) ) ? false : true;
			$tyr->run();
		}
	}sned_email(
	
	/**
	 * Live person search
	 *
	 * @todo broaden to do more than just first record
	 */
	function &find_one_person($search_field, $search_value, $attributes = NULL)
	{
		$dir = new directory_service();
		if ($attributes) $dir->search_by_attribute($search_field, $search_value, $attributes);
		else $dir->search_by_attribute($search_field, $search_value);
		$result = $dir->get_first_record();
		return $result;
	}
	
	/**
	 * Localize module variables upon instantiation if needed
	 */
	function localize(&$object)
	{
		return false;
	}
	
	/**
	 * @return boolean whether or not the model can be used (performs any needed integrity checks)
	 */
	function is_usable()
	{
		return false;
	}
}
?>
