<?php
/**
 * @package reason
 * @subpackage content_managers
 */
 	/**
 	 * Include dependecies and register content manager with Reason
 	 */
	reason_include_once ( 'function_libraries/url_utils.php' );
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'FormManager';
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

	/**
	 * @todo zap the php3 extension
	 * @todo move table exists stuff to thor core
	 */
	class FormManager extends ContentManager
	{
		var $form_prefix = 'form_'; // default prefix for thor db tables
		var $type = 'email';
		var $box_class = 'stackedBox';

		function init( $externally_set_up = false)
		{
			parent::init();

			if (USE_THOR_VERSION == THOR_VERSION_FLASH)
			{
				$this->ensure_temp_db_table_exists();
			}
		}

		function init_head_items()
		{
			if (USE_THOR_VERSION == THOR_VERSION_JS_OLD)
			{
				$this->head_items->add_javascript(JQUERY_UI_URL, true);
				$this->head_items->add_javascript(JQUERY_URL, true);
				$this->head_items->add_stylesheet(JQUERY_UI_CSS_URL);        
				$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH . 'formbuilder/js/formbuilder_translation.js');
				$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH . 'formbuilder/js/jquery.formbuilder.js');
				$this->head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH . 'formbuilder/css/jquery.formbuilder.css');
			}
			$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY . 'content_managers/stacked_box.css');
			$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY . 'content_managers/form.css');
		}

		function alter_data()
		{
			$this->set_allowable_html_tags('thor_content','all');
			$this->add_required( 'thor_content' );
			$this->add_required( 'thank_you_message' );

			$this->add_element( 'thor_comment', 'hidden');
		
			$this->set_comments( 'email_of_recipient', form_comment('When a user submits the form, their responses will be sent here. You are encouraged to use '.SHORT_ORGANIZATION_NAME.' usernames instead of complete '.SHORT_ORGANIZATION_NAME.' email addresses. Multiple addresses or usernames may be separated by commas. This field is required if this form does not save responses in Reason.') );
			$this->set_comments( 'submission_limit', form_comment('To limit the number of submissions to this form, make sure the form is saving its data in Reason and enter a maximum number of submissions here. The form will stop accepting submissions when this limit is reached. A value of 0 indicates no limit.'));
			$this->set_comments( 'open_date', form_comment('If this value is set, the form will not accept submissions before this date and time.'));
			$this->set_comments( 'close_date', form_comment('If this value is set, the form will not accept submissions after this date and time.'));
	
			$this->set_display_name( 'email_of_recipient', 'Email of Recipient' );
			$this->set_display_name( 'thor_content', 'Form Fields' );
			$this->set_display_name( 'db_flag', 'Form Response Options' );
			$this->set_display_name( 'display_return_link', 'Display a "Return to form" link after the Thank You Note?' );
			$this->set_display_name( 'show_submitted_data', 'Display the submitted form data after the Thank You Note?' );

			$this->add_required('db_flag');
			$this->change_element_type('db_flag', 'radio_no_sort', array('options'=> array(
				'yes'=>'Save form responses in Reason <span class="smallText">(allows you to browse and export form data)</span>',
				'no'=>'Only email form responses to the recipient(s) listed below; do not save in Reason.',
					)));
			$this->change_element_type( 'thank_you_message' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			
			$db_flag = $this->get_value('db_flag');
			$display_return_link = $this->get_value('display_return_link');
			if (empty($db_flag)) $this->set_value('db_flag', 'no');
			if (empty($display_return_link)) $this->set_value('display_return_link', 'yes');
			if (empty($display_return_link)) $this->set_value('display_return_link', 'yes');
			if(!$this->get_value('magic_string_autofill')) $this->set_value('magic_string_autofill','none');
			if(!$this->get_value('show_submitted_data')) $this->set_value('show_submitted_data','no');
			$this->change_element_type('magic_string_autofill','radio_no_sort',array('options'=>array('none'=>'None <span class="smallText">-- do not fill in the site visitor\'s information in any fields</span>',
			'editable'=>'Autofill (Editable) <span class="smallText">-- fill in visitor information and allow them to alter that information</span>',
			'not_editable'=>'Autofill (Not Editable) <span class="smallText">-- fill in the visitor information and keep them from altering that information</span>')));
			$this->set_display_name('magic_string_autofill','Autofill Options');
			$this->add_element('magic_string_autofill_note','comment',array('text'=>
				'<h3>Autofilling of Fields</h3><p>If you choose one of the "Autofill" options below, 
				the form will automatically fill in personal information for the person submitting the form. 
				The special field names that can be autofilled are: 
				"Your Full Name", "Your Name", "Your First Name", "Your Last Name", "Your Department", 
				"Your Email", "Your Home Phone", "Your Work Phone", and "Your Title".</p>
				<p><strong>Note: The autofill feature will only work if the visitor is logged in.</strong>
				To require logging in, associate a Group with this form or the page the form is placed on.</p>') );
			$this->add_element('thank_you_note','comment',array('text'=>'<h3>Thank You Note</h3>') );
			$this->set_display_name('thank_you_message','This information is displayed after someone submits the form:');
			$this->add_element('limiting_note','comment',array('text'=>'<h3>Limiting and Scheduling</h3>') );
			$this->set_element_properties('submission_limit', array('size'=>'4'));
			// echo "<HR>using thor version...[" . USE_THOR_VERSION . "]<hr>";
			if (USE_THOR_VERSION == THOR_VERSION_FLASH)
			{
				include_once( THOR_INC . 'plasmature/flash.php' );
				$this->change_element_type( 'thor_content', 'thor', array('thor_db_conn_name' => THOR_FORM_DB_CONN) );
			}
			else if (USE_THOR_VERSION == THOR_VERSION_JS_OLD)
			{
				$this->change_element_type( 'thor_content', 'formbuilder');
			}
			else if (USE_THOR_VERSION == THOR_VERSION_JS_FORMBUILDER)
			{
				include_once( THOR_INC . 'plasmature/formbuilder2.php' );
				$this->change_element_type( 'thor_content', 'formbuilder2');
			}
			else
			{
				die("Fatal Error: USE_THOR_VERSION is configured with an invalid value [" . USE_THOR_VERSION . "]");
			}
			$this->alter_data_advanced_options();
			$this->set_order (array ('name', 'db_flag', 'email_of_recipient', 'thor_content', 'thor_comment', 'magic_string_autofill_note',
									 'magic_string_autofill', 'thank_you_note', 'thank_you_message', 'display_return_link', 'show_submitted_data', 
									 'limiting_note', 'submission_limit', 'open_date', 'close_date',
									 'advanced_options_header', 'thor_view', 'thor_view_custom', 'is_editable', 'allow_multiple', 'email_submitter', 'email_link', 'email_data', 'email_empty_fields', // advanced options
									 'unique_name', 'tableless'));
		}

		/**
		 * Some of these new features should trickle down to use level control but they are where they are for now.
		 */
		function alter_data_advanced_options()
		{
			$advanced_option_display_names = array(
				'thor_view' => 'Choose Thor View:',
				'is_editable' => 'Are Submissions Editable by the Submitter?',
			 	'allow_multiple' => 'Allow Multiple Submissions per User?',
			  	'email_submitter' => 'Email Form Results to Submitter?',
			  	'email_link' => 'Include Edit Link When Possible?',
			  	'email_data' => 'Include Submitted Data in E-mails?',
			  	'email_empty_fields' => 'Include Empty Fields in E-mails?');
			if(reason_user_has_privs($this->admin_page->user_id, 'edit_form_advanced_options'))
			{
				$this->add_element('advanced_options_header','comment',array('text'=>'<h3>Advanced Options</h3>') );
				foreach ($advanced_option_display_names as $k=>$v)
				{
					$this->set_display_name($k, $v);
				}
				$this->setup_thor_view_element();
			}
			else
			{
				foreach($advanced_option_display_names as $k=>$v)
				{
					$this->remove_element($k);	
				}
			}
			$this->setup_tableless_element();
		}
		
		function pre_error_check_advanced_options()
		{
		}
		
		function run_error_checks_advanced_options()
		{
			if(reason_user_has_privs($this->admin_page->user_id, 'edit_form_advanced_options'))
			{
				$custom_view = $this->get_value('thor_view_custom');
				if (!empty($custom_view)) // lets make sure the file exists, no?
				{
					if (!reason_file_exists($custom_view) && !file_exists($custom_view))
					{
						$this->set_error('thor_view_custom', 'The custom thor view file you entered does not appear to exist.');
					}
					else $this->set_value('thor_view', $custom_view); // it is official - set it.
				}
			}
		}
		/**
		 * Grab the value of the thor_view element - if it corresponds to something in the folder, then set the value
		 * of the pull down menu accordingly. If not, 
		 */
		function setup_thor_view_element()
		{
			foreach(reason_get_merged_fileset('minisite_templates/modules/form/views/thor/') as $k=>$v)
			{
				$name = basename($v, 'php');
				$name = basename($name, 'php3');
				$options[$k] = str_replace('.','',$name);
			}
			$form_entity = new entity ($this->admin_page->id);
			$thor_view_value = $form_entity->get_value('thor_view');
			$this->change_element_type('thor_view', 'select', array('options' => $options));
			
			$comment_str = form_comment('Enter the fully qualified path <strong>OR</strong> a relative path 
				from your core / local directory to a thor_view file. Paths entered here will clear any view selected above.');
			$this->add_element('thor_view_custom','text', array('display_name' => 'Custom Thor View Path', 'size' => '75', 'comments' => $comment_str));
			if (!empty($thor_view_value) && !isset($options[$thor_view_value]))
			$this->set_value('thor_view_custom', $thor_view_value);
		}
		
		/**
		 * New in Reason 4.4, if this is present and set to 1 the form module will use the tableless stacked box class.
		 *
		 * For a new form, we set this to 1 - for existing forms we maintain the default of 0.
		 *
		 * We only show the toggle if the user has the edit_form_advanced_options privilege.
		 */
		function setup_tableless_element()
		{
			if ($this->is_element('tableless'))
			{
				if (strlen($this->get_value('name')) == 0) $this->set_value('tableless', 1);
				if(reason_user_has_privs($this->admin_page->user_id, 'edit_form_advanced_options'))
				{
					$this->change_element_type('tableless', 'checkbox', array('checked_value' => 1, 'description' => 'Use tableless display for this form.'));
				}
				else $this->change_element_type('tableless', 'hidden');	
			}
			else
			{
				trigger_error('The field "tableless" needs to be added to the form table. Please run the 4.3 to 4.4 upgrade scripts.');
			}
		}
		
		function pre_error_check_actions()
		{
			if ($this->db_table_exists_check())
			{
				$form_entity = new entity ($this->admin_page->id);
				$old_thor_content = $form_entity->get_value( 'thor_content' );
				$new_thor_content = ($this->get_value( 'thor_content' )) ? $this->get_value( 'thor_content' ) : $old_thor_content;
				if ($new_thor_content != $old_thor_content)
				{
				$data_manager_link = unhtmlentities($this->admin_page->make_link( array( 'cur_module' => 'ThorData' )));
						$this->set_error( 'thor_content', 'Changes could not be saved because of associated data. You can change the form contents if you first <a href="'.$data_manager_link.'">delete the data</a> associated with the form.');
						$this->show_error_jumps = false;
				}
				$this->remove_element('thor_content');
				$data_manager_link = $this->admin_page->make_link( array( 'cur_module' => 'ThorData' ));
				$data_comment= '<div id="manageDataNote"><p><strong>This form has stored data. </strong><a href="'.$data_manager_link.'">Manage stored data</a></p>';
				$data_comment.='<p>To edit this form, you will first need to delete the stored data.</p></div>';	
				$this->change_element_type('thor_comment','comment',array('text'=>$data_comment));	
			}
			$this->pre_error_check_advanced_options();
		}
		
		function run_error_checks()
		{
			$email_of_recipient = $this->get_value('email_of_recipient');
			$db_flag = $this->get_value('db_flag');
			if (($db_flag == 'no') && (empty($email_of_recipient) == true))
			{
				$this->set_error('email_of_recipient', 'Because the data is not being saved to a database, you must provide an valid e-mail address or netID' );
			}
			
			if($this->get_value('email_of_recipient'))
			{
				$bad_usernames = array();
				$addresses = explode(',',$this->get_value('email_of_recipient'));
				foreach($addresses as $address)
				{
					$address = trim($address);
					$num_results = preg_match( '/^([-.]|\w)+@([-.]|\w)+\.([-.]|\w)+$/i', $address );
					if ($num_results <= 0)
					{
						$dir = new directory_service();
						$result = $dir->search_by_attribute('ds_username', $address, array('ds_email'));
						$dir_value = $dir->get_first_value('ds_email');
						if(empty($dir_value))
						{
							$bad_usernames[] = htmlspecialchars($address,ENT_QUOTES,'UTF-8');
						}
					}
				}
				if(!empty($bad_usernames))
				{
					$joined_usernames = '<em>'.implode('</em>, <em>',$bad_usernames).'</em>';
					if(count($bad_usernames) > 1 )
					{
						$msg = 'The usernames '.$joined_usernames.' do not have a email addresses associated with them. Please try different usernames or full email addresses.';
					}
					else
					{
						$msg = 'The username '.$joined_usernames.' does not have an email address associated with it. Please try a different username or a full email address.';
					}
					$this->set_error('email_of_recipient',$msg);
				}
			}
			
			if ($this->get_value('submission_limit') && $db_flag == 'no')
			{
				$this->set_error('submission_limit','You have set a submission limit, but this form is not saving data to a database. Please enable the database option or remove the submission limit.');
			}
			$this->run_error_checks_advanced_options();
		}
		
		function pre_show_form()
		{
			parent::pre_show_form();
			$page_id = (!empty($this->admin_page->request['__old_id'])) ? turn_into_int($this->admin_page->request['__old_id']) : '';
			if ($this->type == 'db')
			{
				$es = new entity_selector($this->admin_page->site_id);
				$es->add_type(id_of('minisite_page'));
				$es->add_left_relationship($this->admin_page->id, relationship_id_of('page_to_form'));
				$result = $es->run_one();
				
				$page = (isset($result[$page_id])) ? $result[$page_id]: current($result);	
			}
		}
		
		function db_table_exists_check()
		{
			$table = $this->form_prefix . $this->admin_page->id;
			// connect with thor database defined in settings.php3
			connectDB(THOR_FORM_DB_CONN);
			$q = 'check table ' . $table . ' fast quick' or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
  			$res = mysql_query($q);
  			$results = mysql_fetch_assoc($res);
  			if (strstr($results['Msg_text'],"doesn't exist") ) $ret = false;
  			else 
			{
				$ret = true;
				$this->type = 'db';
			}
 			connectDB(REASON_DB);
			return $ret;
		}

		function ensure_temp_db_table_exists()
		{
			connectDB(THOR_FORM_DB_CONN);
                        $q = 'check table thor fast quick' or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
                        $res = mysql_query($q);
                        $results = mysql_fetch_assoc($res);
                        if (strstr($results['Msg_text'],"doesn't exist") )
			{
				// create table thor
				$q = 'create table thor	(
					id int(10) NOT NULL auto_increment,
					content text NULL, PRIMARY KEY (id))';
				$res = db_query($q, 'could not create thor temporary data storage using db connection '.THOR_FORM_DB_CONN);			
			}
                        connectDB(REASON_DB);
		}
	}

?>
