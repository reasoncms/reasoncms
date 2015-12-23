<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include base class & other dependencies
 */
reason_include_once( 'minisite_templates/modules/form/models/default.php' );
reason_include_once( 'classes/object_cache.php' );
require_once( THOR_INC.'boxes_thor.php' );
include_once( TYR_INC.'tyr.php' );

/**
 * Register model with Reason
 */
$GLOBALS[ '_form_model_class_names' ][ basename( __FILE__, '.php') ] = 'ThorFormModel';

/**
 * The ThorFormModel is used by thor controllers and views and does the following:
 *
 * 1. Answers information requests from controller and view(s)
 * 2. Uses thor core to handle initial transformations of a thor form
 * 3. Processes database saves using thor core
 * 4. E-mails values using Tyr to submitter and recipients
 * 5. Saves data to session to assist with the display of the submitted data
 * 
 * @todo revamp to avoid use of Tyr - use the e-mail class directly and maintain the model send_email API
 *
 * @author Nathan White
 */
class ThorFormModel extends DefaultFormModel
{
	var $_form;
	var $_form_id; // active form id
	var $_disco_admin_obj;
	var $_admin_obj;
	var $_summary_obj;
	var $_is_usable;
	var $_is_editable;
	var $_view;
	var $_admin_view;

	var $_user_has_administrative_access;	
	var $_user_requested_admin = false;
	var $_requested_form_id;
	var $_form_submission_key;
	
	var $magic_transform_attributes = array('ds_firstname',
								   		 	'ds_lastname',
								   			'ds_fullname',
								   			'ds_phone',
								   			'ds_email',
								   			'ou',
								   			'title',
								   			'homephone',
								   			'telephonenumber');
								   			
	var $magic_transform_methods = array('your_full_name' => 'get_full_name',
								 		 'your_name' => 'get_full_name',
					 	   	     		 'your_first_name' => 'get_first_name',
							     		 'your_last_name' => 'get_last_name',
								 		 'your_email' => 'get_email',
								 		 'your_department' => 'get_department',
								 		 'your_title' => 'get_title',
								 		 'your_home_phone' => 'get_home_phone',
								 		 'your_work_phone' => 'get_work_phone');
								 		 
	var $magic_transform_params;
	
	var $submitted_data_hidden_fields_submitter_view = array('id', 'submitted_by', 'submitter_ip', 'date_created', 'date_modified');
	
	var $submitted_data_hidden_fields = array('id');
	
	/**
	 * Make sure there is a form on the page, otherwise return false
	 */
	function is_usable()
	{
		if (!isset($this->_is_usable))
		{
			$form =& $this->get_form_entity();
			if (!$form)
			{
				$this->_is_usable = false;
			}
			elseif (!array_key_exists('thor_view', $form->get_values()))
			{
				$link = carl_construct_link(array(), array(), REASON_HTTP_BASE_PATH . 'scripts/upgrade/4.0b6_to_4.0b7/forms.php');
				trigger_error('The form type needs to be upgraded to work with this module. Run the forms upgrade script: ' . $link);
				$this->_is_usable = false;
			}
			else $this->_is_usable = true;
		}
		return $this->_is_usable;
	}
	
	function validate_request()
	{
		$form_id = $this->get_requested_form_id();
		$form_id = (strlen($form_id) > 0) ? $form_id : NULL;
		$this->set_form_id_if_valid($form_id);
	}

	function _is_editable()
	{
		if (!isset($this->_is_editable))
		{
			$form =& $this->get_form_entity();
			$this->_is_editable = ($form->get_value('is_editable') === 'yes');
		}
		return $this->_is_editable;
	}

	function is_api()
	{
		return $this->api_request_is_present();
	}

	function is_admin()
	{
		return ($this->user_requested_admin() && $this->user_has_administrative_access() && $this->admin_view_is_available());
	}
		
	function is_form_complete()
	{
		return ( !$this->is_admin() &&
			($this->get_form_submission_key() && $this->form_submission_is_complete() )
			|| ( $this->user_has_submitted() && !$this->_is_editable() && !$this->form_allows_multiple() && !$this->should_show_summary() )
			);
	}
		
	function is_summary()
	{
		return ($this->should_show_summary() && $this->user_has_submitted() && !$this->get_form_id() && ($this->get_form_id() !== 0));
	}
		
	function is_form()
	{
		return ($this->user_has_access_to_fill_out_form() && !$this->is_admin() && !$this->is_form_complete() && !$this->is_summary());
	}
		
	function is_unauthorized()
	{
		return (!$this->user_has_access_to_fill_out_form());
	}
	
	function is_closed()
	{
		return ($this->submission_limit_is_exceeded() || $this->before_open_date() || $this->after_close_date());
	}
	
	function get_email_of_recipient()
	{
		$form =& $this->get_form_entity();
		$email_of_recipient = $form->get_value('email_of_recipient');
		return $email_of_recipient;
	}
	
	function get_email_of_submitter()
	{
		return $this->get_user_netid();
	}
	
	function get_thank_you_message()
	{
		$form =& $this->get_form_entity();
		$thank_you_message = $form->get_value('thank_you_message');
		return $thank_you_message;
	}
	
	function get_form_name()
	{
		$form =& $this->get_form_entity();
		$form_name = $form->get_value('name');
		return $form_name;
	}
	
	/**
	 * The model provides answers to a number of behavior-related "should scenarios"
	 */
	function should_email()
	{
		$form =& $this->get_form_entity();
		$email = $this->get_email_of_recipient();
		return (!empty($email) && ($this->should_email_link() || $this->should_email_data()) );
	}
	
		/**
	 * The model provides answers to a number of behavior-related "should scenarios"
	 */
	function should_email_form_data()
	{
		$form =& $this->get_form_entity();
		$email = $this->get_email_of_recipient();
		return (!empty($email) && ($this->should_email_link() || $this->should_email_data()) );
	}
	
	function should_email_form_data_to_submitter()
	{
		$form =& $this->get_form_entity();
		$value = $form->get_value('email_submitter');
		return ( ($value == 'yes') && ($this->should_email_link() || $this->should_email_data()) );
	}
	
	function should_email_link()
	{
		$form =& $this->get_form_entity();
		$value = $form->get_value('email_link');
		return ($value == 'yes');
	}

	function should_email_empty_fields()
	{
		$form =& $this->get_form_entity();
		$value = $form->get_value('email_empty_fields');
		return ($value == 'yes');
	}
	
	function should_email_data()
	{
		$form =& $this->get_form_entity();
		$value = $form->get_value('email_data');
		return ($value == 'yes');
	}
	
	function should_save_form_data()
	{
		$form =& $this->get_form_entity();
		$db_save = $form->get_value('db_flag');
		return ($db_save == 'yes');
	}
	
	function should_save_submitted_data_to_session()
	{
		$form =& $this->get_form_entity();
		$show_submitted_data = $form->get_value('show_submitted_data');
		return ($show_submitted_data == 'yes');
	}

	/** The following should_ methods provide defaults for the view - views can override these methods **/
	function should_display_return_link()
	{
		$form =& $this->get_form_entity();
		$display_return_link = $form->get_value('display_return_link');
		return ($display_return_link == 'yes');
	}

	function should_show_submitted_data()
	{
		$form =& $this->get_form_entity();
		$show_submitted_data = $form->get_value('show_submitted_data');
		return ($show_submitted_data == 'yes');
	}
	
	function should_show_login_logout()
	{
		return ($this->get_magic_string_autofill() || $this->_is_editable() || $this->form_requires_authentication());
	}
	
	function should_show_submission_list_link()
	{
		return ($this->should_show_summary() && $this->user_has_submitted());
	}
			
	function should_show_thank_you_message()
	{
		$form =& $this->get_form_entity();
		$thank_you_message = $form->get_value('thank_you_message');
		return ($thank_you_message); // does the thank you message have content?
	}

        function should_show_summary()
        {
                $form =& $this->get_form_entity();
                $should_show_summary = $form->get_value('allow_multiple');
                return ($should_show_summary == 'yes');
        }

        function should_show_submission_limit_message()
        {
		return ($this->user_has_submitted() && !$this->form_allows_multiple()); 
        }
	
	function set_form_id_if_valid($form_id)
	{
		if ($this->form_id_is_valid($form_id)) $this->set_form_id($form_id);
	}

	/**
	 * Does the following:
	 *
	 * Redirect case when/if form id spoofing is going on - 
	 * Returns true if the form_id makes sense to the model, redirects intelligently if it does not, otherwise returns false
	 *
	 * @todo how are forms that do not require login handled?
	 */
	function form_id_is_valid($form_id)
	{
		$thor_core =& $this->get_thor_core_object();
		$user_netid = $this->get_user_netid();
		if ($form_id && $user_netid) // only attempt retrieval if user is logged in!
		{
			$row = ($form_id) ? $thor_core->get_values_for_primary_key($form_id) : false;
			if ($row)
			{
				if (isset($row['submitted_by']) && (strtolower($user_netid) == strtolower($row['submitted_by']))) return true;
			}
		}
		elseif ($form_id && !$user_netid && $this->_is_editable()) reason_require_authentication();
		elseif ($form_id == "0") // a form_id of 0 is valid if the user is allowed to create new entries
		{
			if ($this->form_allows_multiple()) return true;
		}
		
		// consider redirect cases
		$user_netid = $this->get_user_netid();
		$user_submissions = (!empty($user_netid)) ? $this->get_values_for_user($user_netid) : false;
		
		// redirect case 1 - user logged in, editable form, multiples not allowed, valid row exists
		if ($this->_is_editable() && !$this->form_allows_multiple() && !empty($user_submissions))
		{
			$redirect_form_id = max(array_keys($user_submissions)); // highest id in the user submissions array
		}
		elseif ($form_id) // we have a form id but it was invalid
		{
			$redirect_form_id = '';
		}
		if (isset($redirect_form_id))
		{
			$redirect = carl_make_redirect(array('form_id' => $redirect_form_id));
			header("Location: " . $redirect);
			exit;
		}
	}

	/**
	 * Create and return a submission key generated from the current submission
	 * @param object disco_obj
	 */
	function create_form_submission_key()
	{
		$values = $this->get_values_for_save();
		$str = get_mysql_datetime();
		foreach ($values as $val) $str = $str . $val;
		$key = md5($str);
		$sk = new ReasonObjectCache($key, '60'); // only last for a minute
		$sk->set(true);
		return $key;
	}

	/**
	 * Determine whether an ajax API request is in progress
	 */	
	function api_request_is_present()
	{
		$api = $this->_module->get_api();
		return ($api && ($api->get_name() == 'standalone'));
	}
	
	/**
	 * If the form submission was just completed - we should have a valid submission_key passed in the request
	 */
	function form_submission_is_complete()
	{
		if (!isset($this->_form_submission_is_complete))
		{
			$submission_key = $this->get_form_submission_key();
			$sk_cache = new ReasonObjectCache($submission_key);
			$this->_form_submission_is_complete = ($sk_cache->fetch());
			if ($sk_cache->fetch()) $sk_cache->clear();
		}
		return $this->_form_submission_is_complete;
	}

	function form_allows_multiple()
	{
		$form =& $this->get_form_entity();
		$allows_multiple = $form->get_value('allow_multiple');
		return ($allows_multiple != 'no');
	}
	
	function form_requires_authentication()
	{
		if ($group =& $this->_get_group('form_to_authorized_viewing_group'))
		{
			$gh = new group_helper();
			$gh->set_group_by_entity($group);
			return $gh->requires_login();
		}
		else return false;
	}
	
	function form_is_tableless()
	{
		$form =& $this->get_form_entity();
		if ($form->has_value('tableless'))
		{
			return ($form->get_value('tableless') == 1);
		}
		else return false;
	}
	
	/**
	 * @return boolean whether or not there is a submission saved in the database for the user
	 */
	function user_has_submitted()
	{
		$rows =& $this->get_values_for_user();
		return ($rows) ? (count($rows) > 0) : false; // count(false) returns 1, so we check to see if rows is false first
	}
	
	/**
	 * This intelligently tries to prep up the raw thor data for the summary view table. It does the following:
	 *
	 * Unsets submitted_by, submitter_ip, and date_created, and any column for which there is no value for any row
	 * Limits display to a maximum of 6 columns
	 * Attempts to optimize for columns that provide most uniqueness between rows
	 * Also will not show particularly wide columns
	 *
	 * This code is a little obtuse - there are probably some better, quicker array functions to do the same thing.
	 */
	function get_values_for_user_summary_view()
	{
		$thor_core =& $this->get_thor_core_object();
		$sort_field = $this->get_sort_field();
		$sort_order = $this->get_sort_order();
		if (!empty($sort_field) && !empty($sort_order)) $thor_values =& $this->get_sorted_values_for_user($sort_field, $sort_order);
		else $thor_values =& $this->get_values_for_user();
		foreach ($thor_values as $k=>$v)
		{
			// we will add a row with an edit link if this is editable
			if ($this->_is_editable())
			{
				$edit_link = carl_construct_link(array('form_id' => $v['id']), array('textonly', 'netid'));
				$thor_values[$k] = array_merge(array('Action' => '<a href="'.$edit_link.'">Edit</a>'), $thor_values[$k]);
			}
			
			if (isset($v['submitted_by'])) unset ($thor_values[$k]['submitted_by']);
			if (isset($v['submitter_ip'])) unset ($thor_values[$k]['submitter_ip']);
			if (isset($v['date_created'])) unset ($thor_values[$k]['date_created']);
			if (isset($v['id'])) unset ($thor_values[$k]['id']);
			foreach ($thor_values[$k] as $k2=>$v2)
			{
				if (strlen($v2) > 0) $has_value[$k2] = true;
			}
		}
		foreach ($thor_values as $k=>$v)
		{
			foreach ($thor_values[$k] as $k2=>$v2)
			{
				if (!isset($has_value[$k2])) unset ($thor_values[$k][$k2]);
			}
		}
		return $thor_values;
	}
	
	/**
	 * Return editable or not_editable is magic string autofill is enabled, otherwise return false
	 */
	function get_magic_string_autofill()
	{
		$form =& $this->get_form_entity();
		$magic_string_autofill = $form->get_value('magic_string_autofill');
		return ( ($magic_string_autofill == 'editable') || ($magic_string_autofill == 'not_editable') )
			   ? $form->get_value('magic_string_autofill')
			   : false;
	}
	
	function &get_top_links()
	{
		if (!$this->user_requested_admin() && $this->user_has_administrative_access() && ($this->get_values()))
		{
			$link['Enter administrative view'] = carl_construct_link(array('form_admin_view' => 'true'), array('textonly', 'netid'));
		}
		elseif ($this->user_requested_admin() && $this->user_has_administrative_access())
		{
			$link['Exit administrative view'] = carl_construct_link(array('form_admin_view' => ''), array('textonly', 'netid'));
		}	
		else $link = array();
		return $link;
	}
	
	function admin_view_is_available()
	{
		return true;		
	}
	
	function &get_values_for_save()
	{
		$disco_obj =& $this->get_view();
		$thor_core =& $this->get_thor_core_object();
		$thor_values = array_merge($thor_core->get_thor_values_from_form($disco_obj), $this->get_values_for_save_extra_fields());
		return $thor_values;
	}
	
	function &get_values_for_email()
	{
		$disco_obj =& $this->get_view();
		$form =& $this->get_form_entity();
		if ($form->get_value('email_data') == 'yes')
		{
			$thor_core =& $this->get_thor_core_object();
			$thor_values = $this->_get_values_and_extra_email_fields($disco_obj);
			$fields_to_hide =& $this->get_submitted_data_hidden_fields($disco_obj);
			if (!empty($fields_to_hide)) $this->_hide_fields($thor_values, $fields_to_hide);
			$values = $thor_core->transform_thor_values_for_display($thor_values);
		}
		else $values = array();
		return $values;
	}
	
	function &get_values_for_email_submitter_view()
	{
		$form =& $this->get_form_entity();
		if ($form->get_value('email_data') == 'yes')
		{
			$values =& $this->get_values_for_submitter_view();
		}
		else $values = array();
		return $values;
	}
	
	function &get_values_for_submitter_view()
	{
		if (!isset($this->_values_for_submitter_view))
		{
			$disco_obj =& $this->get_view();
			$thor_core =& $this->get_thor_core_object();
			$thor_values = $this->_get_values_and_extra_email_fields($disco_obj);
			$disco_hidden_fields =& $this->_get_disco_hidden_fields($disco_obj);
			
			// If a view has specified dynamic fields to show, they should show even if
			// hidden, so subtract them from the list of disco_hidden_fields
			if (($dynamic = $disco_obj->get_show_submitted_data_dynamic_fields()) && is_array($dynamic) )
				$disco_hidden_fields = array_diff($disco_hidden_fields, $dynamic);
				
			$fields_to_hide =& $this->get_submitted_data_hidden_fields_submitter_view($disco_obj);
			if (!empty($fields_to_hide)) $this->_hide_fields($thor_values, $fields_to_hide);
			if (!empty($disco_hidden_fields)) $this->_hide_fields($thor_values, $disco_hidden_fields);
			$this->_values_for_submitter_view = $thor_core->transform_thor_values_for_display($thor_values);
		}
		return $this->_values_for_submitter_view;
	}
	
	function &get_values_for_show_submitted_data()
	{
		if (!isset($this->_values_for_show_submitted_data))
		{
			$session =& get_reason_session();
			if (!$session->has_started()) $session->start();
			$values = $session->get('form_confirm');
			$session->set('form_confirm', '');
			$this->_values_for_show_submitted_data = (!empty($values)) ? $values : '';
		}
		return $this->_values_for_show_submitted_data;
	}
	
	function get_values_for_save_extra_fields()
	{
		$submitted_by = $this->get_user_netid();
		$submitter_ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
		return array('submitted_by' => $submitted_by, 'submitter_ip' => $submitter_ip);
	}
	
	function get_values_for_email_extra_fields()
	{
		$submitted_by = $this->get_user_netid();
		$submitter_ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
		return array('submitted_by' => $submitted_by, 'submitter_ip' => $submitter_ip, 'submission_time' => get_mysql_datetime());
	}

	function &_get_values_and_extra_email_fields(&$disco_obj)
	{
		$thor_core =& $this->get_thor_core_object();
		$thor_values = $thor_core->get_thor_values_from_form($disco_obj);

		// add any submitted files - this will have things show up in confirmation screens and in emails.
		$attachmentSummaryData = $this->get_file_upload_summary_data();
		foreach ($attachmentSummaryData as $col_id => $asd) {
			$thor_values[$col_id] = $asd["filename"];
		}

		// Merge in any additional dynamic fields specified by the application
		if (($dynamic = $disco_obj->get_show_submitted_data_dynamic_fields()) && is_array($dynamic) )	
		{
			foreach ($dynamic as $element) 
			{
				if ($value = $disco_obj->get_value($element)) 
				{
					$thor_values[$element] = $value;
				}
			}
		}
		$thor_values = array_merge($thor_values, $this->get_values_for_email_extra_fields());
		return $thor_values;
	}
	
	function &_get_disco_hidden_fields($disco_obj)
	{
		if (!isset($this->_disco_hidden_fields))
		{
			$elements = $disco_obj->get_element_names();
			foreach ($elements as $elm)
			{
				$type = $disco_obj->get_element_property($elm, 'type');
				if ($type == 'hidden') $hidden[] = $elm;
			}
			$this->_disco_hidden_fields = (isset($hidden)) ? $hidden : array();
		}
		return $this->_disco_hidden_fields;
	}

	/**
	 * The recipients have administrative access to the data. We provide a link if the form is saved to a database.
	 */
	function get_link_for_email()
	{
		$form =& $this->get_form_entity();
		if ($this->should_email_link() && $this->should_save_form_data())
		{
			$link = carl_construct_link(array('form_admin_view' => 'true', 'table_row_action' => 'view', 'table_action_id' => $this->get_form_id()));
			return $link;
		}
		else return '';
	}
	
	/**
	 * We can only provide a link if the form saves to a database and is editable by the submitter
	 */
	function get_link_for_email_submitter_view()
	{
		if ($this->should_email_link() && $this->should_save_form_data() && $this->_is_editable())
		{
			$link = carl_construct_link(array('form_id' => $this->get_form_id()));
			return $link;
		}
		else return '';
	}
	
	function _hide_fields(&$values, $fields_to_hide)
	{
		foreach ($fields_to_hide as $key)
		{
			if (isset($values[$key]))
			{
				unset ($values[$key]);
			}
		}
	}
		
	function &get_disco_admin_object()
	{
		if (!isset($this->_disco_admin_obj))
		{
			include_once( THOR_INC . 'thor_admin.php');
			$form =& $this->get_form_entity();
			$netid = $this->get_user_netid();
			$this->_disco_admin_obj = new DiscoThorAdmin();
			$this->_disco_admin_obj->set_thor_core($thor_core);
			$this->_disco_admin_obj->set_user_netid($netid);
			
			// if the user has editing access to the site lets give the same view that we give there.
			if ($this->_user_has_site_editing_access())
			{
				$this->_disco_admin_obj->show_hidden_fields_in_edit_view = true;
			}
		}
		return $this->_disco_admin_obj;
	}
	
	function &get_admin_object()
	{
		if (!isset($this->_admin_obj))
		{
			include_once( THOR_INC . 'thor_admin.php');
			$form =& $this->get_form_entity();
			$thor_core =& $this->get_thor_core_object();
			$disco_admin_obj =& $this->get_disco_admin_object();
			$xml = $form->get_value('thor_content');
			$table = 'form_' . $form->id();
			$this->_admin_obj = new ThorAdmin();
			$this->_admin_obj->set_thor_core($thor_core);
			$this->_admin_obj->set_admin_form($disco_admin_obj);
			
			// if the user has editing access to the site lets give the same privileges that we give there.
			if ($this->_user_has_site_editing_access())
			{
				$this->_admin_obj->set_allow_delete(true);
				$this->_admin_obj->set_allow_edit(true);
				$this->_admin_obj->set_allow_new(true);
				$this->_admin_obj->set_allow_row_delete(true);
				$this->_admin_obj->set_allow_download_files(true);
			}
		}
		return $this->_admin_obj;
	}

	function &get_summary_object()
	{
		if (!isset($this->_summary_obj))
		{
			include_once( CARL_UTIL_INC . 'db/table_admin.php');
			// we want to entity convert all the fields that occur naturally 
			$thor_core =& $this->get_thor_core_object();
			$display_values =& $thor_core->get_column_labels_indexed_by_name();
			$this->_summary_obj = new TableAdmin();
			$this->_summary_obj->init_view_no_db($display_values);
			$this->_summary_obj->set_fields_to_entity_convert(array_keys($display_values));
		 	$this->set_sort_field($this->_summary_obj->get_sort_field());
		 	$this->set_sort_order($this->_summary_obj->get_sort_order());
		}
		return $this->_summary_obj;
	}
	
	function &get_thor_core_object()
	{
		if (!isset($this->_thor_core_obj))
		{
			include_once( THOR_INC . 'thor.php' );
			$form =& $this->get_form_entity();
			$xml = $form->get_value('thor_content');
			$table = 'form_' . $form->id();
			$this->_thor_core_obj = new ThorCore($xml, $table); 
		}
		return $this->_thor_core_obj;
	}
	
	/**
	 * Return mixed form entity object or false
	 * @access private
	 */
	function &get_form_entity()
	{
		if (!isset($this->_form))
		{
			$es = new entity_selector();
			$es->description = 'Selecting form to display on a minisite page.';
			$es->add_type( id_of('form') );
			$es->add_right_relationship( $this->get_page_id(), relationship_id_of('page_to_form') );
			$es->set_num(1);
			$result = $es->run_one();
			if ($result)
			{
 				$this->_form = reset($result);
 			}
  			else $this->_form = false;
  		}
  		return $this->_form;
	}
	
	function &get_values()
	{
		if (!isset($this->_values))
		{
			$thor_core =& $this->get_thor_core_object();
			$this->_values = ($thor_core->table_exists() && $thor_core->get_rows());
		}
		return $this->_values;
	}
	
	function &get_values_for_user()
	{
		$netid = $this->get_user_netid();
		if (isset($this->_values_for_user['submitted_by']) && $this->_values_for_user['submitted_by'] == $netid)
		{
			return $this->_values_for_user;
		}
		else
		{
			$thor_core =& $this->get_thor_core_object();
			$this->_values_for_user = (!empty($netid)) ? $thor_core->get_values_for_user($netid) : false;
		}
		return $this->_values_for_user;
	}
	
	function get_redirect_url()
	{
		if (!isset($this->_redirect_url))
		{
			$form_id = ($this->_is_editable() && $this->get_user_netid()) ? $this->get_form_id() : '';
			$this->_redirect_url = carl_make_redirect(array('submission_key' => $this->create_form_submission_key(), 'form_id' => $form_id));
		}
		return $this->_redirect_url;
	}
	
	function &get_sorted_values_for_user($sort_field, $sort_order)
	{
		if (!isset($this->_sorted_values_for_user[$sort_field][$sort_order]))
		{
			$thor_core =& $this->get_thor_core_object();
			$netid = $this->get_user_netid();
			$this->_sorted_values_for_user[$sort_field][$sort_order] = (!empty($netid)) ? $thor_core->get_values_for_user($netid, $sort_field, $sort_order) : false;
		}
		return $this->_sorted_values_for_user[$sort_field][$sort_order];
	}
	
	/**
	 * Check the label and normalized label
	 */
	function get_disco_field_name($label)
	{
		if (!isset($this->_disco_field_name[$label]))
		{
			$thor_core =& $this->get_thor_core_object();
			$this->_disco_field_name[$label] = ($thor_core->get_column_name($label)) 
											   ? $thor_core->get_column_name($label) 
											   : $thor_core->get_column_name_from_normalized_label($label);
		}
		return $this->_disco_field_name[$label];
	}
	
	/**
	 * This method appears to be incomplete
	 */
	function get_field_name_from_normalized_label($normalized_label)
	{
		if (!isset($this->_field_name_from_normalized_label[$label]))
		{
			$thor_core =& $this->get_thor_core_object();
		}
	}
	
	/**
	 * Find the label based on the internal field id.
	 * Uses the same _disco_field_name array as get_disco_field_name()
	 */
	function get_field_label($field)
	{
		if (!in_array($field, $this->_disco_field_name))
		{
			$thor_core =& $this->get_thor_core_object();
			if ($label = $thor_core->get_column_label($field))
			{
				$this->_disco_field_name[$label] = $field;	
			}
		}
		return array_search($field, $this->_disco_field_name);
	}
	
	
	function set_sort_field($field)
	{
		$this->_sort_field = $field;
	}
	
	function set_sort_order($order)
	{
		$this->_sort_order = $order;
	}
	
	function get_sort_field()
	{
		return (isset($this->_sort_field)) ? $this->_sort_field : '';
	}
	
	function get_sort_order()
	{
		return (isset($this->_sort_order)) ? $this->_sort_order : '';
	}

	/**
	 * @todo update to not use Tyr
	 */
	function email_form_data_to_submitter()
	{
		if ($this->get_email_of_submitter())
		{
			$email_data = $this->get_values_for_email_submitter_view(); // grab the values
			$email_options = $this->get_options_for_email_submitter_view(); // grab the options
			$this->send_email($email_data, $email_options);
		}
		else
		{
			trigger_error('submitter e-mail could not be determined!');
		}
	}
	
	function get_options_for_email_submitter_view()
	{
		if ($this->should_email_link())
		{
			$email_link = $this->get_link_for_email_submitter_view();
			if (!empty($email_link))
			{
				$options['access_link'] = $email_link;
			}
			else
			{
				$options['origin_link'] = carl_construct_link(array('')); // we use the origin if no link to edit is available
			}
		}
		$options['email_empty_fields'] = $this->should_email_empty_fields();
		$options['subject'] = 'Form Submission Confirmation: ' . $this->get_form_name(); // should i include form name
		$options['header'] = $this->get_form_name() . " - " . "Successfully Submitted " . carl_date('l, F jS Y \\a\t h:i:s A');
		$options['to'] = $this->get_email_of_submitter();
		$options['disclaimer'] = false;
		$view =& $this->get_view();
		if (method_exists($view, 'get_custom_options_for_email_submitter_view'))
		{
			$view_options = $view->get_custom_options_for_email_submitter_view();
			if (is_array($view_options))
			{
				$options = array_merge($options, $view_options);
			}
			else trigger_error('The method get_custom_options_for_email_submitter_view, if defined in the view, needs to return an array.');
		}
		return $options;
	}
	
	/**
	 * @todo work out how to handle email_link
	 */
	function email_form_data()
	{
		if ($this->get_email_of_recipient())
		{
			$email_data = $this->get_values_for_email();
			$email_options = $this->get_options_for_email();

			$this->send_email($email_data, $email_options);

			// clear out any attachments from the temp dir
			if (!$this->should_save_form_data()) {
				$attachmentSummaryData = isset($email_options["attachmentSummaryData"]) ? $email_options["attachmentSummaryData"] : Array();

				foreach($attachmentSummaryData as $col_id => $asd) {
					unlink($asd["path"]);
				}
			}
		}
		else
		{
			trigger_error('recipient e-mail could not be determined!');
		}
	}

	function get_options_for_email()
	{
		$email_link = $this->get_link_for_email();
		if (!empty($email_link)) $options['access_link'] = $email_link;
		$options['email_empty_fields'] = $this->should_email_empty_fields();
		$options['subject'] = 'Response to Form: ' . $this->get_form_name();
		$options['header'] = $this->get_form_name();
		$options['to'] = $this->get_email_of_recipient();
		$options['disclaimer'] = true;
		$view =& $this->get_view();
		if (method_exists($view, 'get_custom_options_for_email'))
		{
			$view_options = $view->get_custom_options_for_email();
			if (is_array($view_options))
			{
				$options = array_merge($options, $view_options);
			}
			else trigger_error('The method get_custom_options_for_email, if defined in the view, needs to return an array.');
		}

		if ($this->should_email_data()) {
			// were files a part of this submission? If saving to a database, they've already been relocated (see ThorCore::handle_file_uploads). If not,
			// they are in a tmp dir. Either way lets run through the form and extract any attachments.
			//
			// @todo - make this a configurable thing on the form? Maybe some people don't want every attachment emailed to them...
			// @todo - add a mapping that indicates which file came in as part of which column

			$attachmentSummaryData = $this->get_file_upload_summary_data();
			if (count($attachmentSummaryData) > 0) {
				$options["attachmentSummaryData"] = $attachmentSummaryData;

				// actually attach the data to the email
				$attachments = Array();
				foreach($attachmentSummaryData as $col_id => $asd) {
					$attachments[$asd["filename"]] = $asd["path"];
				}
				$options["attachments"] = $attachments; // store it here so the files actually get attached to the email
			}
		}

		return $options;
	}

	function get_file_upload_summary_data()
	{
		$rawXml = $this->get_form_entity()->get_value('thor_content');
		$formXml = new XMLParser($rawXml);
		$formXml->Parse();
		$attachmentData = Array();

		$tc = $this->get_thor_core_object();
		foreach ($formXml->document->tagChildren as $node) {
			if ($node->tagName == 'upload') {
				$col_id = $node->tagAttrs['id'];
				$col_label = $node->tagAttrs['label'];

				$disco_el = $this->get_view()->get_element($col_id);
				if ($disco_el->state == "received") {
					if (isset($_FILES[$col_id])) {
						// old way - before plupload. To test this flow, change reason_package/thor/thor.php to use the "upload" type instead of "ReasonUpload" in _transform_upload. Keep it around in case there are pages
						// that are using the old upload type
						$upload_data = $_FILES[$col_id];
						if ($upload_data["tmp_name"] != "") {
							$attachmentData[$col_id] = Array("label" => $col_label, "filename" => $upload_data["name"]);
							if ($this->should_save_form_data()) {
								$attachmentData[$col_id]["path"] = $tc->construct_file_storage_location($this->get_form_id(), $col_id, $upload_data["name"]);
							} else {
								$attachmentData[$col_id]["path"] = $disco_el->tmp_full_path;
							}
						}
					} else {
						// new way - $_FILES is empty when we use plupload
						$attachmentData[$col_id] = Array("label" => $col_label,
														"filename" => $disco_el->file["name"]);
						if ($this->should_save_form_data()) {
							$probePath = $tc->construct_file_storage_location($this->get_form_id(), $col_id, "");
							if (file_exists($probePath)) {
								$globResults = glob($probePath . "*");
								if (count($globResults) > 0) {
									$attachmentData[$col_id]["path"] = $globResults[0];
								}
							}
						} else {
							$attachmentData[$col_id]["path"] = $disco_el->tmp_full_path;
						}
					}
				}
			}
		}
		return $attachmentData;
	}
	
	function save_form_data()
	{
		$save_values = $this->get_values_for_save();
		$this->save_data($save_values);
	}

	function save_submitted_data_to_session()
	{
		$values =& $this->get_values_for_submitter_view();
		$session =& get_reason_session();
		if (!$session->has_started()) $session->start();

		$session->set('form_confirm', $values);
	}
	
	/**
	 * Saves the data in the appropriate way. Assumes should_save_form_data has been checked.
	 *
	 * Considerations include:
	 *
	 * - Is this form one that allows multiple submissions?
	 * - If so, is this an edit or a new submission?
	 * - Is this an edit of an existing submission
	 */
	function save_data(&$data, $options = array())
	{
		$form =& $this->get_form_entity();
		$thor_core =& $this->get_thor_core_object();
		$form_id = $this->get_form_id();
		
		if ( $form_id && $this->_is_editable() ) // update
		{
			$thor_core->update_values_for_primary_key($form_id, $data, $this->get_view()); // updating for uploads
			}
		else // insert
		{
			$insert_id = $thor_core->insert_values($data, $this->get_view());
			$this->set_form_id($insert_id);
		}
	}

	function &setup_view()
	{
		if (!isset($this->_view))
		{
			if (!defined('REASON_FORMS_THOR_DEFAULT_VIEW'))
			{
				trigger_error('REASON_FORMS_THOR_DEFAULT_VIEW constant is not defined - using the default for now, but please set the constant in the reason_settings.php file.');
				define('REASON_FORMS_THOR_DEFAULT_VIEW', 'default.php'); // trigger error as well
			}
			$form =& $this->get_form_entity();
			$custom_view_filename = $form->get_value('thor_view');
			if (!empty($custom_view_filename) && reason_file_exists('minisite_templates/modules/form/views/thor/'.$custom_view_filename))
			{
				reason_include_once('minisite_templates/modules/form/views/thor/'.$custom_view_filename);
			}
			elseif (!empty($custom_view_filename) && reason_file_exists($custom_view_filename))
			{
				reason_include_once($custom_view_filename);
			}
			elseif (reason_file_exists('minisite_templates/modules/form/views/thor/'.REASON_FORMS_THOR_DEFAULT_VIEW))
			{
				reason_include_once('minisite_templates/modules/form/views/thor/'.REASON_FORMS_THOR_DEFAULT_VIEW);
			}
			elseif (reason_file_exists('minisite_templates/modules/form/views/'.REASON_FORMS_THOR_DEFAULT_VIEW))
			{
				reason_include_once('minisite_templates/modules/form/views/'.REASON_FORMS_THOR_DEFAULT_VIEW);
			}
			elseif (file_exists(REASON_FORMS_THOR_DEFAULT_VIEW))
			{
				include_once(REASON_FORMS_THOR_DEFAULT_VIEW);
			}
			else trigger_error('The thor view (' . (!empty($custom_view_filename)) ? $custom_view_filename : REASON_FORMS_THOR_DEFAULT_VIEW . ') could not be loaded for a form (' . $form->get_value('name') . ') with id ' . $form->id() .  
							   ' Check the thor_view value in the content manager or the REASON_FORMS_THOR_DEFAULT_VIEW constant.', FATAL);
			
			$view_name = $GLOBALS['_form_view_class_names'][basename( (!empty($custom_view_filename)) ? $custom_view_filename : REASON_FORMS_THOR_DEFAULT_VIEW, '.php')];
			$this->_view = new $view_name();
			$this->_view->set_model($this);
			$this->apply_disco_plugins($this->_view, $form);
		}
		return $this->get_view();
	}
	
	function user_requested_admin()
	{
		return $this->_user_requested_admin;
	}
	
	/**
	 * @param object disco form
	 */
	function transform_form()
	{
		$disco =& $this->get_view();
		$box_class = ($this->form_is_tableless()) ? 'BoxThorTableless' : 'BoxThor';
		$disco->set_form_class($box_class);
		$thor_core =& $this->get_thor_core_object();
		$thor_core->append_thor_elements_to_form($disco);		
		// if it is editable, load data for the user (if it exists)
		if ($this->_is_editable())
		{
			$form_id = $this->get_form_id();
			$values = ($form_id) ? $thor_core->get_values_for_primary_key($form_id) : false;
			if ($values)
			{
				$thor_core->apply_values_for_primary_key_to_form($disco, $form_id);
				if ($this->get_magic_string_autofill() != 'not_editable') return true; // end here unless the autofill values are not editable in which case we enforce them
			}
		}
		if ($this->get_magic_string_autofill()) // is magic string autofill enabled?
		{
			$editable = ($this->get_magic_string_autofill() == 'editable');
			$transform_array = $this->get_magic_transform_array();
			if (!empty($transform_array)) $this->apply_magic_transform_to_form($disco, $transform_array, $editable); // zap this!
			return true;
		}
	}

	/**
	 * Apply plugins to thor forms depending on the specifications of the form.
	 * @param object $disco_obj: the disco form to which plugins will be applied.
	 * @param object $form_obj: thor form entity
	 * @return void
	 */
	function apply_disco_plugins($disco_obj, $form_obj)
	{
		// Only apply akismet spam filter if user is not logged in.
		if (!reason_check_authentication()) {
			$filter = (!$form_obj->get_value('apply_akismet_filter')) ? REASON_FORMS_THOR_DEFAULT_AKISMET_FILTER : $form_obj->get_value('apply_akismet_filter');
			if ($filter == 'true')
			{
				include_once INCLUDE_PATH . '/disco/plugins/akismet/akismet.php';
				$akismet_filter = new AkismetFilter($disco_obj);
			}
		}
	}	

	function get_magic_transform_array()
	{
		$disco =& $this->get_view();
		$methods =& $this->get_magic_transform_methods($disco);
		$attribute_values =& $this->get_magic_transform_values($disco);
		if (!empty($methods) && !empty($attribute_values))
		{
			foreach ($methods as $k => $v)
			{
				if (!empty($v)) // a method that maps to an empty string will not be run (allows one to turn off magic transform for a field)
				{
					if (method_exists($disco, $v)) $transform_array[$k] = $disco->$v();
					elseif (method_exists($this, $v)) $transform_array[$k] = $this->$v();
					else trigger_error('The magic transform method ' . $v . ' was not defined in the thor model or view - will not transform the field ' . $k);
				}
			}
		}
		return (isset($transform_array)) ? $transform_array : array();
	}
	
	function apply_magic_transform_to_form(&$disco_obj, $transform_array, $editable = true)
	{
		$thor_core =& $this->get_thor_core_object();
		$display_values =& $thor_core->get_column_labels_indexed_by_name();
		foreach ($display_values as $key => $label)
		{
			$normalized_label = strtolower(str_replace(array(' ',':'), array('_',''), $label));
			if (isset($transform_array[$label]) || isset($transform_array[$normalized_label]))
			{
				$value = (isset($transform_array[$label])) ? $transform_array[$label] : $transform_array[$normalized_label];
				$disco_obj->set_value($key, $value);
				if (!$editable && ($disco_obj->get_element_property($key, 'type') != "hidden")) $disco_obj->change_element_type($key, 'solidtext');
			}
		}
	}
	
	/**
	 * If the admin form provides an authenticate method, that method is used. Otherwise, admin access
	 * privileges are determined by checking (in order) if any of the following are true:
	 *
	 * 1. The person has access to administer the site (and has editing privileges)
	 * 2. The person is in the group that can see all the form results
	 * 3. The person is in the list of usernames that receive form submissions
	 *
	 * @return boolean
	 */		
	function user_has_administrative_access()
	{
		if (!isset($this->_user_has_administrative_access))
		{
			$netid = $this->get_user_netid();
			$user_id = ($netid) ? get_user_id($netid) : false;
			if ($this->_user_has_site_editing_access()) $access = true;
			elseif ($this->_user_is_in_admin_access_group()) $access = true;
			elseif ($this->_user_receives_email_results()) $access = true;
			else $access = false;
			$this->_user_has_administrative_access = $access;
		}
		return $this->_user_has_administrative_access;
	}
	
	/**
	 * This returns true in all cases except for the case where there is an admin access group and it does not require login
	 */
	function admin_requires_login()
	{
		if ($group =& $this->_get_group('form_to_authorized_results_group'))
		{
			$gh = new group_helper();
			$gh->set_group_by_entity($group);
			return $gh->requires_login();
		}
		return true;
	}
	
	function user_has_access_to_fill_out_form()
	{
		if (!isset($this->_user_has_access_to_fill_out_form))
		{
			$this->_user_has_access_to_fill_out_form = $this->_user_is_in_viewing_group();
		}
		return $this->_user_has_access_to_fill_out_form;
	}
	
	function submission_limit_is_exceeded()
	{
		$form =& $this->get_form_entity();
		// If we have a limit value...
		if ($cap = $form->get_value('submission_limit'))
		{
			$thor_core =& $this->get_thor_core_object();

			// and we have some rows in the database...
			if ($rows = $thor_core->get_rows())
			{
				// if we've passed the cap limit
				if (count($rows) >= $cap)
				{
					// and this isn't someone editing a preexisting entry
					if (!($this->_is_editable() && $this->get_form_id()))
					{
						return true;
					}
				}
			}
		}
		return false;				
	}
	
	function before_open_date()
	{
		$form =& $this->get_form_entity();
		if (($start = $form->get_value('open_date')) && $start != '0000-00-00 00:00:00')
		{		
			if (strtotime($start) > time()) return true;
		}
		return false;
	}
	
	function after_close_date()
	{
		$form =& $this->get_form_entity();
		
		if (($end = $form->get_value('close_date')) && $end != '0000-00-00 00:00:00')
		{		
			if (strtotime($end) < time()) return true;
		}
		return false;
	}
	
	function _user_has_site_editing_access()
	{
		$netid = $this->get_user_netid();
		$user_id = ($netid) ? get_user_id($netid) : false;
		return ( ($user_id) && reason_username_has_access_to_site($netid, $this->get_site_id()) && reason_user_has_privs($user_id, 'edit'));
	}
	
	function _user_is_in_admin_access_group()
	{
		if ($group =& $this->_get_group('form_to_authorized_results_group'))
		{
			$netid = $this->get_user_netid();
			$gh = new group_helper();
			$gh->set_group_by_entity($group);
			return $gh->has_authorization($netid);
		}
		else return false;
	}
	
	function _user_is_in_viewing_group()
	{
		if ($group =& $this->_get_group('form_to_authorized_viewing_group'))
		{
			$netid = $this->get_user_netid();
			$gh = new group_helper();
			$gh->set_group_by_entity($group);
			return $gh->has_authorization($netid);
		}
		else return true;
	}
		
	/**
	 * @access private
	 */
	function _user_receives_email_results()
	{
		$form =& $this->get_form_entity();
		$netid = $this->get_user_netid();
		$auth_usernames = (explode(',', $form->get_value( 'email_of_recipient' )));
		if ($netid && $auth_usernames)
		{
			return (in_array($netid, $auth_usernames));
		}
		else return false;
	}
		
	/**
	 * @access private
	 */
	function &_get_group($rel)
	{
		$form =& $this->get_form_entity();
		if (!isset($this->_groups[$rel]))
		{
			$es = new entity_selector();
			$es->description = 'Getting groups for this relationship';
			$es->add_type( id_of('group_type') );
			$es->add_right_relationship( $form->id(), relationship_id_of($rel) );
			$result = $es->run_one();
			if ($result)
			{
  				$this->_groups[$rel] = reset($result);
  			}
  			else $this->_groups[$rel] = false;
  		}
  		return $this->_groups[$rel];
	}
	
	function set_user_requested_admin($boolean)
	{
		if ($boolean == true)
		{
			if ($this->admin_requires_login()) reason_require_authentication();
		}
		$this->_user_requested_admin = $boolean;
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

	function &get_submitted_data_hidden_fields($disco_obj = NULL)
	{
		if (!isset($this->_submitted_data_hidden_fields))
		{
			if ($disco_obj != NULL)
			{
				$this->_submitted_data_hidden_fields = ($disco_obj->get_submitted_data_hidden_fields()) 
													 ? $disco_obj->get_submitted_data_hidden_fields() 
													 : $this->submitted_data_hidden_fields;
			}
			else $this->_submitted_data_hidden_fields = $this->submitted_data_hidden_fields;
		}
		return $this->_submitted_data_hidden_fields;
	}
	
	function &get_submitted_data_hidden_fields_submitter_view($disco_obj = NULL)
	{
		if (!isset($this->_submitted_data_hidden_fields_submitter_view))
		{
			if ($disco_obj != NULL)
			{
				$this->_submitted_data_hidden_fields_submitter_view = ($disco_obj->get_submitted_data_hidden_fields_submitter_view()) 
													 ? $disco_obj->get_submitted_data_hidden_fields_submitter_view() 
													 : $this->submitted_data_hidden_fields_submitter_view;
			}
			else $this->_submitted_data_hidden_fields_submitter_view = $this->submitted_data_hidden_fields_submitter_view;
		}
		return $this->_submitted_data_hidden_fields_submitter_view;
	}

 	/**
 	 * @return array magic_transform_methods from model (and possibly view)
 	 * @todo change paramater to &$disco_obj=NULL when php 4 is no longer supported
 	 */
 	function &get_magic_transform_methods($disco_obj = NULL)
 	{
 		if (!isset($this->_magic_transform_methods)) // if internal build hasn't been done then do it - merge methods from view
 		{
 			if ($disco_obj != NULL)
 			{
 				$magic_transform_methods = ($disco_obj->get_magic_transform_methods()) 
 										 ? $disco_obj->get_magic_transform_methods() 
 										 : $this->magic_transform_methods;
 				$this->_magic_transform_methods = ($disco_obj->get_custom_magic_transform_methods()) 
 											    ? array_merge($magic_transform_methods, $disco_obj->get_custom_magic_transform_methods())
 											    : $magic_transform_methods;
 			}
 			else $this->_magic_transform_methods = $this->magic_transform_methods;
 		}
 		return $this->_magic_transform_methods;
 	}
 	
 	/**
 	 * @return array magic_transform_attributes from model (and possibly view)
 	 * @todo change paramater to &$disco_obj=NULL when php 4 is no longer supported
 	 */
	function &get_magic_transform_attributes($disco_obj = NULL)
	{
		if (!isset($this->_magic_transform_attributes)) // if internal build hasn't been done then do it - merge methods from view
 		{
 			if ($disco_obj != NULL)
 			{
 				$magic_transform_attributes = ($disco_obj->get_magic_transform_attributes()) 
 											? $disco_obj->get_magic_transform_attributes() 
 											: $this->magic_transform_attributes;
 				$this->_magic_transform_attributes = ($disco_obj->get_custom_magic_transform_attributes()) 
 												   ? array_merge($magic_transform_attributes, $disco_obj->get_custom_magic_transform_attributes())
 												   : $magic_transform_attributes;
 			}
 			else $this->_magic_transform_attributes = $this->magic_transform_attributes;
 		}
 		return $this->_magic_transform_attributes;
	}

 	/**
 	 * @return array magic_transform_attributes from model (and possibly view)
 	 * @todo change paramater to &$disco_obj=NULL when php 4 is no longer supported
 	 */
	function &get_magic_transform_params($disco_obj = NULL)
	{
		if (!isset($this->_magic_transform_params)) // if internal build hasn't been done then do it - merge methods from view
 		{
 			if ($disco_obj != NULL)
 			{
 				$this->_magic_transform_params = ($disco_obj->get_magic_transform_params()) 
 											? $disco_obj->get_magic_transform_params() 
 											: $this->magic_transform_params; 			}
 			else $this->_magic_transform_params = $this->magic_transform_params;
 		}
 		return $this->_magic_transform_params;
	}

 	/**
 	 * @return array magic_transform_values
 	 * @todo change paramater to &$disco_obj=NULL when php 4 is no longer supported
 	 */	
	function &get_magic_transform_values($disco_obj = NULL)
	{
		if (!isset($this->_magic_transform_values)) // build internally if necessary
 		{
 			$attributes =& $this->get_magic_transform_attributes($disco_obj);
			$params =& $this->get_magic_transform_params($disco_obj);
 			$this->_magic_transform_values =& $this->get_directory_info($attributes, $params);
 		}
 		return $this->_magic_transform_values;
	}
}
?>
