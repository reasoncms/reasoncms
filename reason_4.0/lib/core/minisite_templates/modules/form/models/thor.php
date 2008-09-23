<?php

reason_include_once( 'minisite_templates/modules/form/models/default.php' );
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
include_once(TYR_INC.'tyr.php');

$GLOBALS[ '_form_model_class_names' ][ basename( __FILE__, '.php') ] = 'ThorFormModel';


/**
 * The ThorFormModel is used by thor controllers and views and does the following:
 *
 * 1. Answers information requests from controller and view(s)
 * 2. Uses thor core to handle initial transformations of a thor form
 * 3. Processes database saves using thor core
 * 4. E-mails values using Tyr
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
	var $_thor_admin_obj;
	var $_thor_summary_obj;
	var $_is_usable;

	var $_user_has_administrative_access;	
	var $_user_requested_admin_view = false;
	var $_form_submission_appears_complete = false;
	
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
				trigger_error('The thor form model is not usable because it is being invoked on a page that does not contain a thor form.');
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
	
	function is_editable()
	{
		$form =& $this->get_form_entity();
		$is_editable = $form->get_value('is_editable');
		return ($is_editable === 'yes');
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
	 * This is a little odd logically, but 
	 */
	function should_email()
	{
		$form =& $this->get_form_entity();
		$email = $this->get_email_of_recipient();
		return (!empty($email) && ($this->should_email_link() || $this->should_email_data()) );
	}
	
	function should_email_submitter()
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
	
	function should_show_submitted_data()
	{
		$form =& $this->get_form_entity();
		$show_submitted_data = $form->get_value('show_submitted_data');
		return ($show_submitted_data == 'yes');
	}

	function should_display_return_link()
	{
		$form =& $this->get_form_entity();
		$display_return_link = $form->get_value('display_return_link');
		return ($display_return_link == 'yes');
	}
	
	function set_form_id_if_valid($form_id)
	{
		if ($this->form_id_is_valid($form_id)) $this->set_form_id($form_id);
	}
	
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
				if (isset($row['submitted_by']) && ($user_netid == $row['submitted_by'])) return true;
			}
		}
		elseif ($form_id && !$user_netid && $this->is_editable()) reason_require_authentication();
		elseif ($form_id == "0") // a form_id of 0 is valid if the user is allowed to create new entries
		{
			if ($this->form_allows_multiple()) return true;
		}
		
		// consider redirect cases
		$user_netid = $this->get_user_netid();
		$user_submissions = (!empty($user_netid)) ? $this->get_values_for_user($user_netid) : false;
		
		// redirect case 1 - user logged in, editable form, multiples not allowed, valid row exists
		if ($this->is_editable() && !$this->form_allows_multiple() && !empty($user_submissions))
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
	
	function form_submission_appears_complete()
	{
		return $this->_form_submission_appears_complete;
	}
	
	/**
	 * If the form submission was just completed - we should be able to retrieve the serialized submission data from the session
	 * If show submitted data is not enabled, we just return true.
	 */
	function form_submission_is_complete()
	{
		if ($this->should_show_submitted_data())
		{
			$values =& $this->get_values_for_show_submitted_data();
			return (!empty($values));
		}
		else return true;
	}

	function form_allows_multiple()
	{
		$form =& $this->get_form_entity();
		$allows_multiple = $form->get_value('allow_multiple');
		return ($allows_multiple == 'yes');
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
			if ($this->is_editable())
			{
				$edit_link = carl_construct_link(array('form_id' => $v['id']), array('textonly', 'netid'));
				$thor_values[$k]['Action'] = '<a href="'.$edit_link.'">Edit</a>';
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
	
	function &get_head_items()
	{
		return $this->head_items;
	}
	
	function &get_top_links()
	{
		if ($this->admin_view_is_available())
		{
			if (!$this->user_requested_admin_view() && $this->user_has_administrative_access())
			{
				$link['Enter administrative view'] = carl_construct_link(array('form_admin_view' => 'true'), array('textonly', 'netid'));
			}
			elseif ($this->user_requested_admin_view() && $this->user_has_administrative_access())
			{
				$link['Exit administrative view'] = carl_construct_link(array('form_admin_view' => ''), array('textonly', 'netid'));
			}	
		}
		else $link = array();
		return $link;
	}
	
	function admin_view_is_available()
	{
		$form =& $this->get_form_entity();
		return ($this->should_save_form_data() || $this->get_values());		
	}
	
	function &get_values_for_save(&$disco_obj)
	{
		$thor_core =& $this->get_thor_core_object();
		$thor_values = array_merge($thor_core->get_thor_values_from_form($disco_obj), $this->get_values_for_save_extra_fields());
		return $thor_values;
	}
	
	function &get_values_for_email(&$disco_obj)
	{
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
	
	function &get_values_for_email_submitter_view(&$disco_obj)
	{
		$form =& $this->get_form_entity();
		if ($form->get_value('email_data') == 'yes')
		{
			$values =& $this->get_values_for_submitter_view($disco_obj);
		}
		else $values = array();
		return $values;
	}
	
	function &get_values_for_submitter_view(&$disco_obj)
	{
		if (!isset($this->_values_for_submitter_view))
		{
			$thor_core =& $this->get_thor_core_object();
			$thor_values = $this->_get_values_and_extra_email_fields($disco_obj);
			$disco_hidden_fields =& $this->_get_disco_hidden_fields($disco_obj);
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
		$thor_values = array_merge($thor_core->get_thor_values_from_form($disco_obj), $this->get_values_for_email_extra_fields());
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
	function get_link_for_email(&$disco_obj)
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
	function get_link_for_email_submitter_view(&$disco_obj)
	{
		if ($this->should_email_link() && $this->should_save_form_data() && $this->is_editable())
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
		}
		return $this->_disco_admin_obj;
	}
	
	function &get_thor_admin_object()
	{
		if (!isset($this->_thor_admin_obj))
		{
			include_once( THOR_INC . 'thor_admin.php');
			$form =& $this->get_form_entity();
			$thor_core =& $this->get_thor_core_object();
			$disco_admin_obj =& $this->get_disco_admin_object();
			$xml = $form->get_value('thor_content');
			$table = 'form_' . $form->id();
			$this->_thor_admin_obj = new ThorAdmin();
			$this->_thor_admin_obj->set_thor_core($thor_core);
			$this->_thor_admin_obj->set_admin_form($disco_admin_obj);
		}
		return $this->_thor_admin_obj;
	}

	function &get_thor_summary_object()
	{
		if (!isset($this->_thor_summary_obj))
		{
			include_once( CARL_UTIL_INC . 'db/table_admin.php');
			// we want to entity convert all the fields that occur naturally 
			$thor_core =& $this->get_thor_core_object();
			$display_values =& $thor_core->get_column_labels_indexed_by_name();
			$this->_thor_summary_obj = new TableAdmin();
			$this->_thor_summary_obj->init_view_no_db($display_values);
			$this->_thor_summary_obj->set_fields_to_entity_convert(array_keys($display_values));
		 	$this->set_sort_field($this->_thor_summary_obj->get_sort_field());
		 	$this->set_sort_order($this->_thor_summary_obj->get_sort_order());
		}
		return $this->_thor_summary_obj;
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
			$es = new entity_selector($this->site_id);
			$es->description = 'Selecting form to display on a minisite page.';
			$es->add_type( id_of('form') );
			$es->add_right_relationship( $this->page_id, relationship_id_of('page_to_form') );
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
			$this->_values = $thor_core->get_rows();
		}
		return $this->_values;
	}
	
	function &get_values_for_user()
	{
		if (!isset($this->_values_for_user))
		{
			$thor_core =& $this->get_thor_core_object();
			$netid = $this->get_user_netid();
			$this->_values_for_user = (!empty($netid)) ? $thor_core->get_values_for_user($netid) : false;
		}
		return $this->_values_for_user;
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
	
	function get_field_name_from_normalized_label($normalized_label)
	{
		if (!isset($this->_field_name_from_normalized_label[$label]))
		{
			$thor_core =& $this->get_thor_core_object();
		}
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
	 * Uses Tyr to send an e-mail, given an array of key value pairs where the key is the item display name, and the value is the value
	 *
	 * The options array can define any of the following keys - if none are defined default behaviors will be used
	 * 
	 * - to: netid, array of netids, or comma separated string indicating who the e-mails should go to
	 * - from: netid or full e-mail address for the from fields
	 * - subject: string indicating the subject line
	 * - header: string containing header line for the first line of the e-mail
	 * - dislaimer: boolean indicating whether or not to add a dislaimer - defaults to true
	 * - origin_link: link indicating where the URL where the form was filled out
	 * - access_link: link indicating where the form can be accessed for view/edit
	 *
	 * @param array data - key/value pairs of the data to e-mail
	 * @param array options - allows various parameters to be optionally passed
	 * @todo remove this weird mini system and the tyr "message" framework and use e-mail class directly
	 */
	function send_email(&$data, $options = array())
	{
		$form =& $this->get_form_entity();
		$thor_core =& $this->get_thor_core_object();
		$to = (isset($options['to'])) ? $options['to'] : $form->get_value('email_of_recipient');
		$to = (is_array($to)) ? implode(",", $to) : $to;
		if (strlen(trim($to)) > 0)
		{
			if (!$this->should_email_empty_fields()) $messages['all']['hide_empty_values'] = true;
			if (isset($options['origin_link'])) $messages['all']['form_origin_link'] = $options['origin_link'];
			if (isset($options['access_link'])) $messages['all']['form_access_link'] = $options['access_link'];
			$messages['all']['form_title'] = (isset($options['header'])) ? $options['header'] : $form->get_value('name');
			$messages[0]['to'] = $to;
			$messages[0]['subject'] = (isset($options['subject'])) ? $options['subject'] : 'Response to Form: ' . $form->get_value('name');	
			$tyr = new Tyr($messages, $data);
			$tyr->add_disclaimer = (isset($options['disclaimer']) && ($options['disclaimer'] == false) ) ? false : true;
			$tyr->run();
		}
	}
	
	// do we need an options array? 
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
		
		if ( $form_id && $this->is_editable() ) // update
		{
			$thor_core->update_values_for_primary_key($form_id, $data);
			}
		else // insert
		{
			$insert_id = $thor_core->insert_values($data);
			$this->set_form_id($insert_id);
		}
	}
	
	function save_submitted_data_to_session(&$disco_obj)
	{
		$values =& $this->get_values_for_submitter_view($disco_obj);
		$session =& get_reason_session();
		if (!$session->has_started()) $session->start();
		$session->set('form_confirm', $values);
	}
	
	/**
	 * Returns the thor view selected in the content manager
	 */
	function &get_thor_view()
	{
		if (!isset($this->_thor_view))
		{
			if (!defined('REASON_FORMS_THOR_DEFAULT_VIEW'))
			{
				trigger_error('REASON_FORMS_THOR_DEFAULT_VIEW constant is not defined - using the default for now, but please set the constant in the reason_settings.php file.');
				define('REASON_FORMS_THOR_DEFAULT_VIEW', 'default.php'); // trigger error as well
			}
			$form =& $this->get_form_entity();
			$filename = $form->get_value('thor_view');
			$filename = (!empty($filename)) ? $filename : REASON_FORMS_THOR_DEFAULT_VIEW;
			if (reason_file_exists('minisite_templates/modules/form/views/thor/'.$filename))
			{
				reason_include_once('minisite_templates/modules/form/views/thor/'.$filename);
			}
			elseif (reason_file_exists($filename))
			{
				reason_include_once($filename);
			}
			elseif (file_exists($filename))
			{
				include_once($filename);
			}
			else trigger_error('The thor view (' . $filename . ') could not be loaded for a form (' . $form->get_value('name') . ') with id ' . $form->id() .  
							   ' Check the thor_view value in the content manager or the REASON_FORMS_THOR_DEFAULT_VIEW constant.', FATAL);
			
			$thor_view_name = $GLOBALS['_form_view_class_names'][basename($filename, '.php')];
			$this->_thor_view = new $thor_view_name();
			$this->_thor_view->set_model($this);
		}
		return $this->_thor_view;
	}

	function user_requested_admin_view()
	{
		return $this->_user_requested_admin_view;
	}
	
	/**
	 * @param object disco form
	 */
	function transform_thor_form(&$disco)
	{
		$thor_core =& $this->get_thor_core_object();
		$thor_core->append_thor_elements_to_form($disco);		
		
		// if it is editable, load data for the user (if it exists)
		if ($this->is_editable())
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
			if (!empty($transform_array)) $this->apply_magic_transform_to_form($disco, $transform_array, $editable); // zap this!
			return true;
		}
	}
	
	function apply_magic_transform_to_form(&$disco_obj, $transform_array, $editable = true)
	{
		$thor_core =& $this->get_thor_core_object();
		$display_values =& $thor_core->get_column_labels_indexed_by_name();
		foreach ($display_values as $key => $label)
		{
			$normalized_label = strtolower(str_replace(" ", "_", $label));
			if (isset($transform_array[$label]) || isset($transform_array[$normalized_label]))
			{
				$value = (isset($transform_array[$label])) ? $transform_array[$label] : $transform_array[$normalized_label];
				$disco_obj->set_value($key, $value);
				if (!$editable) $disco_obj->change_element_type($key, 'solidtext');
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
			if ( ($user_id) && reason_username_has_access_to_site($netid, $this->site_id) && reason_user_has_privs($user_id, 'edit')) $access = true;
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
	function admin_view_requires_login()
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
	 * Sets the working user_netid
	 */
	function set_user_netid($netid = false)
	{
		$this->_user_netid = $netid;
	}
	
	function &get_directory_info($attributes = false)
	{
		if (!isset($this->_directory_info))
		{
			$netid = $this->get_user_netid();	
	    	$dir = new directory_service();
			if ($attributes) $dir->search_by_attribute('ds_username', $netid, $attributes);
			else $dir->search_by_attribute('ds_username', $netid);
			$this->_directory_info = $dir->get_first_record();
		}
		return $this->_directory_info;
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
	
	function set_user_requested_admin_view($boolean)
	{
		if ($boolean == true)
		{
			if ($this->admin_view_requires_login()) reason_require_authentication();
		}
		$this->_user_requested_admin_view = $boolean;
	}
	
	function set_form_submission_appears_complete($boolean)
	{
		$this->_form_submission_appears_complete = $boolean;
	}
	
	function set_form_id($form_id)
	{
		$this->_form_id = $form_id;
	}
	
	function get_form_id()
	{
		return (isset($this->_form_id)) ? $this->_form_id : false;
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
 	 * @return array magic_transform_values
 	 * @todo change paramater to &$disco_obj=NULL when php 4 is no longer supported
 	 */	
	function &get_magic_transform_values($disco_obj = NULL)
	{
		if (!isset($this->_magic_transform_values)) // build internally if necessary
 		{
 			$attributes =& $this->get_magic_transform_attributes($disco_obj);
 			$this->_magic_transform_values =& $this->get_directory_info($attributes);
 		}
 		return $this->_magic_transform_values;
	}
}
?>
