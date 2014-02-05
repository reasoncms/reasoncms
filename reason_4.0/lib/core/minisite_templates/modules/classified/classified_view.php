<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include parent class(es)
 */
include_once(DISCO_INC.'disco.php');
include_once (CARL_UTIL_INC . 'db/table_admin.php');

/**
 * Register the view with Reason
 */
$GLOBALS[ '_classified_module_view' ][ module_basename( __FILE__) ] = 'ClassifiedView';

/**
 * The classified view handles HTML generation as well as the standard operations of the Disco Form
 *
 * Extensions of the class can define a method that modifies the display of a field at preview or display as follows
 *
 * get_display_value_field_name($value, &$item, $summary_mode)
 *
 * The base class defines clean_value methods for classified_date_available, datetime, and price to format these appropriately on display
 *
 * @author Nathan White
 */
class ClassifiedView extends Disco
{	

	/**
	 * SUMMARY AND DETAIL VIEW SETTINGS
	 */
	 
	// Summary and item detail view text strings that can be set directly and will otherwise be requested from the model
	var $header_text_string;
	var $footer_text_string;

	/**
	 * SUMMARY VIEW SETTINGS
	 */
	 
	// Customizable details of the summary view
	var $show_list_with_details = false;
	var $jump_to_item_if_only_one_result = false;
	var $default_sort_field = 'dated.datetime';
	var $default_sort_order = 'desc';
	var $use_filters = true;
	
	/**
	 * Sets order, display name, and visibility of item fields for summary view
	 */
	var $item_summary_fields_to_display = array(
		'category' => 'Category',
		'name' => 'Title',
		'description' => 'Brief Description',
		'price' => 'Price',
		'datetime' => 'Date Posted');
	
	/**
	 * Defines which summary fields allow sorting
	 */
	var $item_summary_fields_that_allow_sorting = array(
		'price',
		'name',
		'datetime');

	/**
	 * The table admin class will html_specialchar all field data when displayed except for fields in this array
	 */
	var $item_summary_fields_no_htmlspecialchars = array(
		'name'
	);
	
	/**
	 * DETAIL VIEW SETTINGS
	 */
	 
	// Customizable settings for the detail view
	var $show_item_detail_empty_fields = false;	
	
	/**
	 * Sets order, display name, and visibility of item fields for detail view and preview
	 */
	var $item_detail_fields_to_display = array(
		'category' => 'Category',
		'content' => 'Posting description',
		'price' => 'Price',
		'author' => 'Classified Contact Name',
		'classified_contact_email' => 'Classified Contact Email',
		'datetime' => 'Date of Posting'
		);
		
	/**
	 * E-MAIL SETTINGS
	 */
	// Customizable e-mail settings that can be set directly and will otherwise be requested from the model
	var $email_subject;
	var $email_from;
	var $email_to;
	var $email_reply_to;
	
	/**
	 * FORM VIEW SETTINGS
	 */
	var $form_header_text_string;
	var $form_footer_text_string;

	/**
	 * Make a preview option available
	 * @var boolean
	 */
	var $enable_preview = true;

	/**
	 * Text of the form submit button
	 * @var string
	 */
	var $submit_button_text = 'Submit';
	
	/**
	 * Text of the preview submit button
	 * @var string
	 */
	var $preview_button_text = 'Preview Classified';
	
	/**
	 * Setup for the form submission view
	 */
	var $classified_form_display_names = array(
			'content' => 'Posting description',
			'classified_date_available' => 'Date available',
			'name' => 'Posting title',
			'author' => 'Contact name',
			'classified_contact_email' => 'Contact email',
			'display_contact_info' => 'Show contact info',
		);
		
	var $classified_form_comments = array(
			'name' => 'This should be a short description, e.g. "3BR house" or "Studio apartment." Please <strong>do not</strong> use ALL CAPS.',
			'location' => 'Specific address (123 Main St.) <strong>or</strong> a general location (3 blocks from Carleton).',
			'classified_date_available' => 'MM / DD / YYYY',
			'content' => 'Plaintext only; no HTML.',
			'display_contact_info' => 'Make sure to include contact information in your classified if you choose "No"',
		);
	
	// disco form settings - should generally not be changed directly - change instead using alter_classified_elements method
	/**
	 * If this is set to false, be very careful about making sure that your preview methods properly sanitize user input before displaying it
	 */
	var $strip_tags_from_user_input = true;	
	
	var $elements = array(
			'category',
			'price' => array('type' => 'money', 'size' => 7),
			'location' => array('type' => 'hidden'),
			'classified_date_available' => 'hidden',
			'name' => array('type' => 'text', 'size' => 50, 'maxlength' => 50),
			'content' => array('type' => 'textarea', 'rows' => 10, 'cols' => 60),
			'classified_print_content' => 'hidden',
			'author' => array('type' => 'text', 'size' => 35),
			'datetime' => 'hidden',
			'classified_contact_email' => array('type' => 'text', 'size' => 35),
			'display_contact_info' => array(
				'type' => 'radio',
				'options' => array('No', 'Yes')
			)
		);		
	
	var $required = array('category', 
						  'price',
						  'name',
						  'content',
						  'author',
						  'classified_contact_email',
						  'display_contact_info');
	
	var $actions;
	
	/**
	 * @var object classifiedModel object
	 */
	var $model;
	
	/**
	 * @var object tableAdmin object
	 */
	var $table;
	
/**
	 * Options for the item detail and preview
	 */
	
	function set_model(&$model)
	{
		$this->model =& $model;
	}
	function init_view_and_form()
	{
		$this->init(); // init form
		$this->init_head_items();
		$this->form_header_text_string = (!empty($this->form_header_text_string)) ? $this->form_header_text_string : $this->get_form_header_text();
		$this->form_footer_text_string = (!empty($this->form_footer_text_string)) ? $this->form_footer_text_string : $this->get_form_footer_text();	
	}
	
	function init_view()
	{
		$this->init_head_items();
		$this->init_table_display();
		$this->header_text_string = (!empty($this->header_text_string)) ? $this->header_text_string : $this->get_header_text();
		$this->footer_text_string = (!empty($this->footer_text_string)) ? $this->footer_text_string : $this->get_footer_text();	
	}
	
	/**
	 * Adds the appropriate head items - overload to use different style sheets, add javascript, etc
	 */
	function init_head_items()
	{
		$head_items =& $this->model->get_head_items();
		$head_items->add_stylesheet('/global_stock/css/classified/classified.css');
		$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
	}
	
	/**
	 * Initializes the table_admin object
	 */
	function init_table_display()
	{
		$display_columns = $this->get_item_summary_field_display_names();
		$this->table = new TableAdmin();
		$this->table->set_admin_form($this);
		$this->table->init_view_no_db($display_columns);
	}
	
	function get_item_summary_fields_no_htmlspecialchars()
	{
		return $this->item_summary_fields_no_htmlspecialchars;
	}	

	function get_item_summary_field_display_names()
	{
		return $this->item_summary_fields_to_display;
	}
	
	/**
	 * Called on by table admin
	 */
	function get_fields_to_entity_convert()
	{
		$a = $this->get_fields_to_show();
		$b = $this->get_item_summary_fields_no_htmlspecialchars();
		return array_diff($a, $b);
	}
	
	/**
	 * Called on by table admin
	 */
	function get_fields_to_show()
	{
		return array_keys($this->item_summary_fields_to_display);
	}
	
	/**
	 * Called on by table admin
	 */
	function get_fields_that_allow_sorting()
	{
		return $this->item_summary_fields_that_allow_sorting;
	}
	
	/**
	 * Called on by table admin
	 */
	function get_default_sort_order()
	{
		return $this->default_sort_order;
	}
	
	/**
	 * Called on by table admin
	 */
	function get_default_sort_field()
	{
		return $this->default_sort_field;
	}
	
	/**
	 * Sets sort order of classified entity selector based upon table settings
	 */
	function alter_es(&$es)
	{
		$sort_order = $this->table->get_sort_field() . ' ' . $this->table->get_sort_order();
		$es->set_order($sort_order);
	}
	
	/**
	 * THE FOLLOWING METHODS RETURN VARIOUS VIEW SETTINGS REQUESTED BY THE CLASSIFIED MODULE
	 */
	 
	/**
	 * @return boolean show_list_with_details setting
	 */
	function get_show_list_with_details()
	{
		return $this->show_list_with_details;
	}
	
	/**
	 * @return boolean use_filters setting
	 */	
	function get_use_filters()
	{
		return $this->use_filters;
	}
	
	/**
	 * @return boolean jump_to_item_if_only_one_result setting
	 */	

	function get_jump_to_item_if_only_one_result()
	{
		return $this->jump_to_item_if_only_one_result;
	}
	
	/**
	 * Hook for functions which alter classified elements before user data is populated
	 */
	function alter_classified_form_elements()
	{
	}
	
	/**
	 * Hook to do something directly before save/email
	 */
	function classified_pre_process()
	{
	}
	
	/**
	 * Hook to do something directly after save/email
	 */
	function classified_post_process()
	{
	}
	
	/**
	 * Ask the model if it has an header text string - may be stored in a text blurb
	 */
	function get_header_text()
	{
		return $this->model->get_header_text_as_string();
	}
	
	/**
	 * Ask the model if it has an footer text string - may be stored in a text blurb
	 */
	function get_footer_text()
	{
		return $this->model->get_footer_text_as_string();
	}

	/**
	 * Ask the model if it has a form header text string - may be stored in a text blurb
	 */	
	function get_form_header_text()
	{
		return $this->model->get_form_header_text_as_string();
	}
	
	/**
	 * Ask the model if it has a form footer text string - may be stored in a text blurb
	 */	
	function get_form_footer_text()
	{
		return $this->model->get_form_footer_text_as_string();
	}
	
	function get_email_to()
	{
		return $this->model->get_email_to();
	}
	
	function get_email_subject()
	{
		return $this->model->get_email_subject();
	}
	
	function get_email_from()
	{
		return $this->model->get_email_from();
	}
	
	function get_email_reply_to()
	{
		return $this->model->get_email_reply_to();
	}
	
	function get_classified_email_txt($email_link)
	{
		$txt = "A new classified submission has been received and needs review. You may review the submission using the following link:\n\n";
		$txt .= $email_link;
		return $txt;
	}
	
	function get_classified_email_html($email_link)
	{
		$html = '<p>A new classified submission has been received and needs review. You may review the submission using the following link</p>';
		$link_html = '<p><a href="'.$email_link.'">'.$email_link.'</a><p>';
		return $html . $link_html;
	}
	
	function show_submit_classified_text()
	{
		echo '<div class="classifiedSubmitLink">';
		echo '<p><a href="'.$this->model->get_submit_link().'">Submit a Classified</a></p>';
		echo '</div>';
	}
	
	function show_return_to_listing_text()
	{
		echo '<div class="classifiedReturnToListingLink">';
		echo '<p><a href="'.$this->model->get_return_to_listing_link().'">Return to Listing</a></p>';
		echo '</div>';
	}
	
	function show_delete_classified_text()
	{
		echo '<div class="classifiedDeleteLink">';
		echo '<p><a href="'.$this->model->get_delete_link().'">Delete This Ad</a></p>';
		echo '</div>';
	}
	
	function show_header_text()
	{
		if (!empty($this->header_text_string))
		{
			echo '<div class="classifiedHeaderText">';
			echo $this->header_text_string;
			echo '</div>';
		}
	}
	
	function show_footer_text()
	{
		if (!empty($this->footer_text_string))
		{
			echo '<div class="classifiedFooterText">';
			echo $this->footer_text_string;
			echo '</div>';
		}
	}
	
	function show_form_header_text()
	{
		if (!empty($this->form_header_text_string))
		{
			echo '<div class="classifiedFormHeaderText">';
			echo $this->form_header_text_string;
			echo '</div>';
		}
	}
	
	function show_form_footer_text()
	{
		if (!empty($this->form_footer_text_string))
		{
			echo '<div class="classifiedFormFooterText">';
			echo $this->form_footer_text_string;
			echo '</div>';
		}
	}
	
	function show_successful_submit_text()
	{
		echo '<div class="classifiedSuccessfulSubmitText">';
		if ($this->model->get_classified_requires_approval())
		{
			echo '<p>Your classified ad was successfully submitted. It will be posted on the website after it is approved.</p>';
		}
		else
		{
			echo '<p>Your classified ad was successfully submitted and should be available on the website shortly.</p>';
		}
		echo '</div>';
	}

	function show_successful_delete_text()
	{
		echo '<div class="classifiedSuccessfulDeleteText">';
		echo '<p>Your classified ad was successfully deleted.</p>';
		echo '</div>';
	}

	// Overloadable methods for item preview
	function show_preview()
	{
		$name = reason_htmlspecialchars($this->get_value('name')); // htmlspecialchars for now should use same methods as model
		echo '<div class="classifiedPreview">';
		echo '<h3>'.$name.'</h3>';
		$items = $this->get_values();
		$this->show_item($items);
		echo '</div>';
	}
	
	function show_item(&$item)
	{
		echo '<ul id="classifiedView">';
		$this->show_item_values($item);
		echo '</ul>';
		if (is_object($item) && $this->model->get_user_can_delete($item->id()))
		{
			$this->show_delete_classified_text();
		}
	}

	function show_item_value_default($display_name, $display_value)
	{
		echo '<li><strong>'.reason_htmlspecialchars($display_name).'</strong>: ' . reason_htmlspecialchars($display_value) . '</li>';
	}

	function show_summary_list(&$items)
	{
		foreach ($items as $id => $item)
		{
			$item_values = $this->get_item_display_values($item, true);
			$data[] = $item_values;
		}
		$this->table->set_data_from_array($data);
		$this->table->run();
	}
	
	/**
	 * This method returns a standardized values array for a classified item when given an item entity or an array of pure item values
	 *
	 * The array returned will have a key for each field in the classified type and an additional field to indicate the category
	 *
	 * @param mixed item entity or array
	 * @param boolean summary_mode are the values being called for display in summary mode
	 */
	function get_item_display_values(&$item, $summary_mode = false)
	{
		$fields_to_process = $this->get_fields_to_process($summary_mode);
		$item_values = (is_object($item)) ? $item->get_values() : $item;
		foreach ($fields_to_process as $field)
		{
			if (isset($item_values[$field]))
			{
				$method_name = 'get_display_value_'.$field;
				if (method_exists($this, $method_name))
				{
					$display_values[$field] = $this->$method_name($item_values[$field], $item, $summary_mode);
				}
				else
				{
					$display_values[$field] = $item_values[$field];
				}
			}
			elseif ($field == 'category')
			{
				$display_values['category'] = $this->get_display_value_category('', $item, $summary_mode);
			}
			else $display_values[$field] = '';
		}
		return $display_values;
	}
	
	function get_fields_to_process($summary_mode)
	{
		if ($summary_mode)
		{
			$fields = array_keys($this->item_summary_fields_to_display);
			$fields[] = 'id';
			return $fields;
		}
		else return array_keys($this->item_detail_fields_to_display);
	}
	
	function show_item_values(&$item)
	{
		$item_values = $this->get_item_display_values($item);
		foreach ($this->item_detail_fields_to_display as $k=>$display_name)
		{
			$method_name = (method_exists($this, 'show_item_value_' . $k)) ? 'show_item_value_' . $k : 'show_item_value_default';
			if (($this->show_item_detail_empty_fields && empty($item_values[$k])) || !empty($item_values[$k]))
			{
				$this->$method_name($display_name, $item_values[$k]);
			}
		}
	}
	function get_display_value_category($value, &$item, $summary_mode)
	{
		if (is_object($item))
		{
			return $this->model->get_category_names_for_classified_as_string($item->id());
		}
		else
		{
			return $this->get_value_for_display('category');
		}
	}
	
	function get_display_value_name($value, &$item, $summary_mode)
	{
		if ($summary_mode) // we want to return a link in this case
		{
			$item_name = htmlspecialchars($value,ENT_QUOTES,'UTF-8'); 
			$link_to_item = carl_make_link(array('item_id' => $item->id()));
			return '<a href="'.$link_to_item.'">'.$item_name.'</a>';
		}
		else return $value;
	}
	
	function get_display_value_datetime($value, &$item, $summary_mode)
	{
		$value = (empty($value)) ? carl_date('Y-m-d') : $value;
		return prettify_mysql_datetime($value);
	}
	
	function get_display_value_classified_date_available($value, &$item, $summary_mode)
	{
		return prettify_mysql_datetime($value);
	}
	
	function get_display_value_price($value, &$item, $summary_mode)
	{
		if (!empty($value))
		{
			return '$'.$value;
		}
		else return '';
	}
	
	function get_display_value_author($value, &$item, $summary_mode)
	{
		$display_contact_info = (is_object($item)) ? $item->get_value('display_contact_info') : $item['display_contact_info'];
		if (!empty($display_contact_info)) return $value;
		else return '';
	}
	
	function get_display_value_classified_contact_email($value, &$item, $summary_mode)
	{
		$display_contact_info = (is_object($item)) ? $item->get_value('display_contact_info') : $item['display_contact_info'];
		if (!empty($display_contact_info)) return $value;
		else return '';
	}
	
	function get_classified_display_name($value)
	{
		if (isset($this->classified_form_display_names[$value])) return $this->classified_form_display_names[$value];
		else return prettify_string($value);
	}
	
	function show_disco_form()
	{
		$this->run();
	}

	// Disco Stuff - this is only invoked when the disco form is shown
	function on_every_time()
	{
		$this->setup_default_actions();
		$category_options = $this->model->get_classified_category_names();
		$this->change_element_type('category', 'radio', array('options' => $category_options));
		
		if (!empty($this->classified_form_comments))
		foreach ($this->classified_form_comments as $k=>$v)
		{
			$this->set_comments($k, form_comment($v));
		}
		
		if (!empty($this->classified_form_display_names))
		foreach ($this->classified_form_display_names as $k=>$v)
		{
			if ($this->get_element($k)) // make sure it is an element
			{
				$this->set_display_name($k, $v);
			}
		}
		$this->alter_classified_form_elements();		
	}

	function setup_default_actions()
	{
		if (!isset($this->actions))
		{
			$this->actions['submit'] = $this->submit_button_text;
			$this->actions['preview'] = $this->preview_button_text;
		}
	}
	/**
	 * Optionally shows preview
	 */
	function pre_show_form()
	{
		if (!$this->_has_errors() && ($this->chosen_action == 'preview'))
		{
			$this->show_preview();
		}
	}
	
	function run_error_checks()
	{
		$this->run_classified_default_error_checks();
		$this->run_classified_custom_error_checks();
	}
	
	/**
	 * Basic classified error checks that apply to all views
	 */
	function run_classified_default_error_checks()
	{
		if ($this->get_value('price') && ($this->get_value('price') <= 0)) $this->set_error('price', 'Price is too low');
		if ($this->get_value('classified_contact_email') && !check_against_regexp($this->get_value('classified_contact_email'), array('email')))
		{
			$this->set_error('classified_contact_email', 'Malformed email address');
		}
		if (strlen($this->get_value('name')) > 50)
		{
			$this->set_error('name', 'Title exceeds maximum length of 50');
		}
	}
	
	/**
	 * Hook for custom error checks
	 */ 
	function run_classified_custom_error_checks()
	{
	}
	
	function process()
	{
		$this->classified_pre_process();
		if ($this->chosen_action == 'submit')
		{
			$this->classified_save_item();
			if ($this->model->send_posting_notification || $this->model->classified_requires_approval)
				$this->classified_email_notification();
		}
		$this->classified_post_process();
	}
	
	function classified_save_item()
	{
		$values = $this->get_values();
		$this->model->save_classified($values);
	}
	
	/**
	 * If the model returns an array of e-mail addresses, the parts of the e-mail are assembled and then sent to the model for e-mailing
	 */
	function classified_email_notification()
	{
		$email_info['to'] = (!empty($this->email_to)) ? $this->email_to : $this->get_email_to();
		if (!empty($email_info['to'])) // we have a recipient
		{
			$email_link = $this->model->get_classified_entity_link_string();
			$email_info['from'] = (!empty($this->email_from)) ? $this->email_from : $this->get_email_from();
			$email_info['subject'] = (!empty($this->email_subject)) ? $this->email_subject : $this->get_email_subject();
			$email_info['reply_to'] = (!empty($this->email_reply_to)) ? $this->email_reply_to : $this->get_email_reply_to();
			$email_info['txt'] = $this->get_classified_email_txt($email_link);
			$email_info['html'] = $this->get_classified_email_html($email_link);
			$this->model->email_classified($email_info);	
		}
		else
		{
			trigger_error('The classified model did not have a to address - the email notification could not be sent.');
		}
	}
	
	function where_to()
	{
		if ($this->chosen_action == 'submit')
		{
			return $this->model->get_successful_submission_redirect();
		}
	}
}
?>
