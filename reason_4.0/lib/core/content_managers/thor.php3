<?php
	reason_include_once ( 'function_libraries/url_utils.php' );
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'FormManager';

	class FormManager extends ContentManager
	{
		var $form_prefix = 'form_'; // default prefix for thor db tables
		var $type = 'email';

                function init()
                {
                        parent::init();
			$this->ensure_temp_db_table_exists();
                }
		
		function alter_data()
		{
			$this->set_allowable_html_tags('thor_content','all');
			//$this->add_required( 'email_of_recipient' ); // only required if db_flag == 'no'
			$this->add_required( 'thor_content' );
			$this->add_required( 'thank_you_message' );
		
			$this->set_comments( 'email_of_recipient', form_comment('When a user submits the form, the form\'s information will be sent here. You are encouraged to use '.SHORT_ORGANIZATION_NAME.' netids instead of complete '.SHORT_ORGANIZATION_NAME.' email addresses. Multiple addresses or netids may be separated by commas. This field is required if this form does not save results to a database.') );
			$this->set_comments( 'thank_you_message', form_comment('After a user submits the form, this message will be displayed on the generic confirmation page.') );
			$this->set_comments( 'display_return_link', form_comment('This option toggles whether the thank you message page displays a link to return to the form or not.') );
			$this->set_comments( 'show_submitted_data', form_comment('This option allows you to display a copy of the submitted information on the thank you page.'));

			$this->set_display_name( 'email_of_recipient', 'Email of Recipient' );
			$this->set_display_name( 'thor_content', 'Form Content' );
			$this->set_display_name( 'db_flag', 'Save to Database?' );
			$this->set_display_name( 'display_return_link', 'Display Return Link?' );

			
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
			$this->add_element('magic_string_autofill_note','comment',array('text'=>'<h3>Autofilling of Fields</h3><p>If you choose one of the "Autofill" options below, the will form automatically fill in personal information for the person submitting the form. The special field names that can be autofilled are: "Your Full Name", "Your Name", "Your First Name", "Your Last Name", "Your Department", "Your Email", "Your Home Phone", "Your Work Phone", and "Your Title".</p><p><strong>Note: The autofill feature will only work if the visitor is logged in.</strong></p>') );
			$this->add_element('thank_you_note','comment',array('text'=>'<h3>Thank You Note</h3><p>This information is displayed after someone submits the form.</p>') );
			$this->change_element_type( 'unique_name', 'hidden' );
			$this->change_element_type( 'thor_content', 'thor', array('thor_db_conn_name' => THOR_FORM_DB_CONN) );
			$this->set_order (array ('name', 'db_flag', 'email_of_recipient', 'thor_content','magic_string_autofill_note','magic_string_autofill', 'thank_you_note', 'thank_you_message', 'display_return_link'));
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
			}
		}
		
		function run_error_checks()
		{
			$email_of_recipient = $this->get_value('email_of_recipient');
			$db_flag = $this->get_value('db_flag');
			if (($db_flag == 'no') && (empty($email_of_recipient) == true))
			{
				$this->set_error('email_of_recipient', 'Because the data is not being saved to a database, you must provide an valid e-mail address or netID' );
			}
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
				$data_manager_link = unhtmlentities($this->admin_page->make_link( array( 'cur_module' => 'ThorData' )));
				echo '<p><strong>There is stored data that is linked to this form.</strong></p><p>To edit the <strong>contents</strong> of the form, you will first need to delete the data that is associated with this form.</p>';
				echo '<p><a href="'.$data_manager_link.'">View / Delete stored data</a></p>';
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
