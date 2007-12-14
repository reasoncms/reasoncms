<?php
include_once(DISCO_INC.'disco.php');
include_once (CARL_UTIL_INC . 'db/table_admin.php');

$GLOBALS[ '_classified_module_view' ][ module_basename( __FILE__) ] = 'ClassifiedView';

/**
 * The classified view handles HTML generation as well as the standard operations of the Disco Form
 *
 * @todo Seems that the class is always sending the "needs approval" message and making items Pending even if no approval group exists
 *
 * @author Nathan White
 */
class ClassifiedView extends Disco
{
	var $model;
	
	// these are placeholders for views that extend this class to modify display_names and comments
	var $classified_display_names = array(
			'content' => 'Posting description',
			'classified_date_available' => 'Date available',
			'name' => 'Posting title',
			'author' => 'Contact name',
			'classified_contact_email' => 'Contact email',
			'display_contact_info' => 'Show contact info',
			'datetime' => 'Date posted'
		);
		
	var $classified_comments = array(
			'name' => 'This should be a short description, e.g. "3BR house" or "Studio apartment." Please <strong>do not</strong> use ALL CAPS.',
			'location' => 'Specific address (123 Main St.) <strong>or</strong> a general location (3 blocks from Carleton).',
			'classified_date_available' => 'MM / DD / YYYY',
			'content' => 'Plaintext only; no HTML.',
			'display_contact_info' => 'Make sure to include contact information in your classified if you choose "No"',
		);
	
	// basic options for classified views
	var $enable_preview = true;
	var $submit_button_text = 'Submit';
	var $preview_button_text = 'Preview Classified';
	
	// we setup the basic format for the elements
	var $elements = array(
			'category',
			'price' => array('type' => 'money', 'size' => 7),
			'location' => array('type' => 'hidden'),
			'classified_date_available' => 'hidden',
			'name' => array('type' => 'text', 'size' => 50, 'maxlength' => 50),
			'content' => array('type' => 'textarea', 'rows' => 10, 'cols' => 60),
			'classified_print_content' => 'hidden',
			'author' => array('type' => 'text', 'size' => 35),
			'classified_contact_email' => array('type' => 'text', 'size' => 35),
			'display_contact_info' => array(
				'type' => 'radio',
				'options' => array('No', 'Yes')
			)
		);		
	
	// we setup what is required by default
	var $required = array('category', 
						  'price',
						  'location',
						  'classified_date_available',
						  'name',
						  'content',
						  'author',
						  'classified_contact_email',
						  'display_contact_info');
	
	// various bits that can be set directly or will be initialized in get functions
	var $header_text_string;
	var $footer_text_string;
	var $form_header_text_string;
	var $form_footer_text_string;
	var $email_subject;
	var $email_from;
	var $email_to;
	var $email_reply_to;
	
	/**
	 * TableAdmin object
	 */
	var $table;
	
	// enable tag stripping so no tags are allowed to be saved to database
	var $strip_tags_from_user_input = true;
	
	var $actions;
	
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
	}
	
	/**
	 * Initializes the table_admin object
	 */
	function init_table_display()
	{
		$this->table = new TableAdmin();
		$this->table->set_show_actions_first_cell(false);
		$this->table->set_show_actions_last_cell(false);
		$this->table->set_allow_filters(false);
		$this->table->set_allow_export(false);
		$this->table->set_show_header(false);
		$this->table->set_fields_to_entity_convert($this->get_table_fields_to_entity_convert());
		$this->table->set_fields_to_show($this->get_table_fields_to_show());
		$this->table->set_fields_that_allow_sorting($this->get_table_fields_that_allow_sorting());
		
		// this is where we get a bit ugly with calls to private methods - should be fixable in table admin class
		$this->table->_display_values = $this->get_table_display_values();
		$this->table->_set_params_from_request();
	}
	
	/**
	 * Sets up the table column headers and names
	 */
	function get_table_display_values()
	{
		return array('category' => array('type' => 'text', 'label' => 'Category'),
					 'price' => array ('type' => 'text', 'label' => 'Price'),
					 'description' => array ('type' => 'text', 'label' => 'Brief Description'), 
					 'datetime' => array( 'type' => 'datetime', 'label' => 'Date Posted'));
	}
	
	function get_table_fields_to_entity_convert()
	{
		return array('category', 'price', 'datetime');
	}
	
	function get_table_fields_to_show()
	{
		return array('category', 'description', 'price', 'datetime');
	}
	
	function get_table_fields_that_allow_sorting()
	{
		return array('price', 'datetime');
	}
	
	/**
	 * Hook for functions which alter classified elements before user data is populated
	 */
	function alter_classified_elements()
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
		echo '<p>Your classified ad was successfully submitted. It will be posted on the website after it is approved.</p>';
		echo '</div>';
	}

	// Overloadable methods for item preview
	function show_preview(&$preview_items)
	{
		echo '<div class="classifiedPreview">';
		echo '<h3>Preview</h3>';
		echo '<ul id="classifiedView">';
		echo '<li>'.implode("</li><li>",$preview_items).'</li>';
		echo '</ul>';
		echo '</div>';
	}
	
	/**
	 * Would be ideal to strip out more HTML into overloadable methods
	 */
	function show_list(&$items)
	{
		//pray ($items);
		//die;
		foreach ($items as $id => $item)
		{
			// htmlspecialchars the name and description
			$item_name = htmlspecialchars($item->get_value('name'),ENT_QUOTES,'UTF-8'); 
			$item_description = htmlspecialchars($item->get_value('description'),ENT_QUOTES,'UTF-8');
			$link_to_item = carl_make_link(array('item_id' => $id));
			$item_title = '<a href="'.$link_to_item.'">'.$item_name.'</a>';
			$table_row['id'] = $id;
			$table_row['category'] = $this->model->get_category_names_for_classified_as_string($id);
			$table_row['description'] = $item_title . '<br/>'.$item_description;
			$table_row['price'] = $item->get_value('price');
			$table_row['classified_date_available'] = prettify_mysql_datetime($item->get_value('classified_date_available'));
			$table_row['datetime'] = prettify_mysql_datetime($item->get_value('datetime'));
			$table_rows[] = $table_row;
		}
		
		//bit ugly
		$this->table->custom_build_data($table_rows);
		echo $this->table->gen_table_html($table_rows);	
	}
	
	/** 
	 * Would be ideal to strip out more HTML into overloadable methods
	 */
	function show_item(&$item)
	{
		echo '<ul id="classifiedView">';
		$this->show_list_items($item);
		echo '</ul>';
	}
	
	function show_list_items(&$item)
	{
		$this->show_item_value_datetime($this->get_classified_display_name('datetime'), $item);
		$this->show_item_value_price($this->get_classified_display_name('price'), $item);
		$this->show_item_value_classified_print_content($this->get_classified_display_name('classified_print_content'), $item);
		$this->show_item_value_classified_date_available($this->get_classified_display_name('classified_date_available'), $item);
		$this->show_item_value_classified_duration_days($this->get_classified_display_name('classified_duration_days'), $item);
		$this->show_item_value_description($this->get_classified_display_name('description'), $item);
		$this->show_item_value_content($this->get_classified_display_name('content'), $item);
		$this->show_item_value_location($this->get_classified_display_name('location'), $item);
		$this->show_item_value_author($this->get_classified_display_name('author'), $item);
		$this->show_item_value_contact_email($this->get_classified_display_name('classified_contact_email'), $item);
		$this->show_item_value_category($this->get_classified_display_name('category'), $item);
	}
	
	function show_item_value_datetime($display_name, &$item)
	{
		echo '<li><strong>'.$display_name.'</strong>: ' . prettify_mysql_datetime($item->get_value('datetime')) . '</li>';
	}
	
	function show_item_value_price($display_name, &$item)
	{
		echo '<li><strong>'.$display_name.'</strong>: $' . $item->get_value('price') . '</li>';
	}
	
	function show_item_value_classified_print_content($display_name, &$item)
	{
		echo '';
	}
	
	function show_item_value_classified_date_available($display_name, &$item)
	{
		echo '<li><strong>'.$display_name.'</strong>: ' . prettify_mysql_datetime($item->get_value('classified_date_available')) . '</li>';
	}
	
	function show_item_value_classified_duration_days($display_name, &$item)
	{
		echo '';
	}
	
	function show_item_value_description($display_name, &$item)
	{
		echo '';
	}
	
	function show_item_value_content($display_name, &$item)
	{
		echo '<li><strong>'.$display_name.'</strong>: ' . $item->get_value('content') . '</li>';
	}
	
	function show_item_value_author($display_name, &$item)
	{
		if ($item->get_value('display_contact_info'))
		{
			echo '<li><strong>'.$display_name.'</strong>: ' . $item->get_value('author') . '</li>';
		}
	}
	
	function show_item_value_contact_email($display_name, &$item)
	{
		if ($item->get_value('display_contact_info'))
		{
			echo '<li><strong>'.$display_name.'</strong>: ' . $item->get_value('classified_contact_email') . '</li>';
		}
	}
	
	function show_item_value_location($display_name, &$item)
	{
		echo '<li><strong>'.$display_name.'</strong>: ' . $item->get_value('location') . '</li>';
	}
	
	function show_item_value_category($display_name, &$item)
	{
		$category_string = $this->model->get_category_names_for_classified_as_string($item->id());
		echo '<li><strong>'.$display_name.'</strong>: ' . $category_string . '</li>';
	}
	
	function get_classified_display_name($value)
	{
		if (isset($this->classified_display_names[$value])) return $this->classified_display_names[$value];
		else return prettify_string($value);
	}
	
	/**
	 * Each element will be run through this - to hide an item from the preview, modify this and have the method return false for the item you want to hide
	 */
	function get_preview_list_item_html($element_name, $display_value)
	{
		$pretty_name = prettify_string($element_name);
		$html = '<strong>'.$pretty_name.'</strong>: ' . $display_value;
		return $html;
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
		
		if (!empty($this->classified_comments))
		foreach ($this->classified_comments as $k=>$v)
		{
			$this->set_comments($k, form_comment($v));
		}
		
		if (!empty($this->classified_display_names))
		foreach ($this->classified_display_names as $k=>$v)
		{
			if ($this->get_element($k)) // make sure it is an element
			{
				$this->set_display_name($k, $v);
			}
		}
		$this->alter_classified_elements();		
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
			$preview_items =& $this->get_preview_list_item_array();
			$this->show_preview($preview_items);
		}
	}
	
	function process()
	{
		$this->classified_pre_process();
		if ($this->chosen_action == 'submit')
		{
			$this->classified_save_item();
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
			
			// provided we have at least a "to" and "from" - pass the parts to the model for e-mailing
			if( !empty($email_info['to']) && !empty($email_info['from']) )
			{
				$this->model->email_classified($email_info);
			}
			else trigger_error('The classified model did not have a to and from address - the email notification could not be sent.');
		}
	}
	
	function where_to()
	{
		if ($this->chosen_action == 'submit')
		{
			return $this->model->get_successful_submission_redirect();
		}
	}
	
	// parse the request, return array of preview items
	function &get_preview_list_item_array()
	{
		$values = $this->get_element_names();
		foreach($values as $element_name)
		{
			$display_value = $this->get_value_for_display($element_name);
			$html = $this->get_preview_list_item_html($element_name, $display_value);
			if ($html !== false) $list_item_array[] = $html;
		}
		return $list_item_array;
	}
}
?>
