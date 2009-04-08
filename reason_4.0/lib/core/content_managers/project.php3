<?php
/**
 * @package reason
 * @subpackage content_managers
 */
 
/**
 * Register directory service
 */
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

/**
 * Register content manager with Reason
 */
$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'ProjectManager';

/**
 * A content manager for projects
 */
class ProjectManager extends ContentManager
{
	var $email_addresses = array();
	var $pretty_names = array(
			'author'=>'Lead(s)',
			'datetime'=>'Target Completion Date',
			'bug_state'=>'Project State',
			'content'=>'Comments/Discussion',
			'url'=>'Url for more info',
			'bug_client'=>'Client(s)',
		);
	
	function alter_data()
	{
		foreach($this->pretty_names as $name=>$pretty_name)
		{
			$this->set_display_name( $name, $pretty_name );
		}
		$this->set_comments('description',form_comment('A lay person\'s quickie summary of the project.<br /><br />'));
		$this->set_comments('content',form_comment('Notes on the project.  Feel free to add any thoughts you have; this is like a project wiki and bulletin board rolled into one.  please preface your line with the current date, and initial your contribution.<br /><br /><br />'));
		$this->set_comments('author',form_comment('Net IDs of people who are leading this project.  E.g.: hendlerd, mryan, etc.<br />You can specify multiple people by separating their names with commas.'));
		$this->set_comments('assigned_to',form_comment('Net IDs of people who will be working on the project.  E.g.: hendlerd, mryan, etc.<br />You can specify multiple people by separating their names with commas.'));
		$this->set_comments('bug_client',form_comment('Net IDs of people who will are the contact/lead clients for the project. Note that this is not the same as the client department, which is selected elsewhere.<br />You can specify multiple people by separating their names with commas.'));
		
		$this->set_comments('datetime',form_comment('Either the project deadline, or, in less deadline-driven projects, an idea of a reasonable completion date.  Leave this blank if you do not have a clear deadline.'));
		$this->set_comments('priority',form_comment('A general sense of the project\'s priority level.  Please note that this is not the primary means of establishing project priority.  To do that, choose "sort these items" when you are done working on this project'));
		
		$this->set_comments('url',form_comment('Place a url here for the project\'s development location, or to provide access to other assets that pertain to the project '));
		
		$this->change_element_type('priority','select_no_sort');
		
		$this->change_element_type('bug_owner','hidden');
		$this->change_element_type('bug_type','hidden');
		$this->change_element_type('keywords','hidden');
		$this->change_element_type('time_estimate','hidden');

		$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
		
		//$this->add_required( 'author' );
		
		$this->set_order(
			array(
				'name',
				'bug_state',
				'description',
				'content',
				'author',
				'assigned_to',
				'datetime',
				'priority',
				'url',
				'keywords',
				'time_estimate',
			)
		);

		$this->original = new entity( $this->get_value( 'id' ) );
		$this->original->get_values();
	}
	function on_every_time()
	{
		if( !$this->get_value('bug_owner') )
			$this->set_value( 'bug_owner', $this->get_value( 'last_edited_by' ) );
	}

	function run_error_checks()
	{
		if( $this->get_value( 'author' ) )
		{
			$this->check_users('author');
		}
		if( $this->get_value( 'assigned_to' ) )
		{
			$this->check_users('assigned_to');
		}
	}
	function check_users( $field_name )
	{
		$users = split( ',',$this->get_value( $field_name ) );
		foreach( $users AS $user )
		{
			$user = trim( $user );
			if(!$this->get_email_addr( $user ))
			{
				$this->set_error( $field_name, 'The user '.$user.', in '.$field_name.' field, does not have an e-mail address defined in available directory services.' );
			}
		}
	}
	function finish()
	{
		$this->email_users();
	}
	function email_users()
	{
		$mail_ignores_fields = array(
			'last_modified',
			'creation_date',
			'last_edited_by',
			'no_share',
			'bug_owner',
			'keywords',
			'state',
			'unique_name',
			'type',
			'id',
			'sort_order',
			'bug_type',
		);
		$addresses = $this->get_addresses();
		$updater = new entity( $this->get_value( 'last_edited_by' ) );
		
		if( !$this->is_new_entity() AND $this->has_changed )
		{
			$action = 'Updated';
		}
		// new bug
		else
		{
			$action = 'New';
		}
		
		$subject = '[Projects] '.$action.': '.$this->get_value( 'name' );
		$body = $action.' Project: '.$this->get_value('name')."\n";
		$body .= 'Project updated by: '.$updater->get_value( 'name' )."\n";
		$body .= "\n".'--- Project Team ---'."\n";
		$body .= 'Lead(s): '.$this->get_value( 'author' )."\n";
		if($this->get_value( 'assigned_to'))
			$body .= 'Assignees: '.$this->get_value( 'assigned_to')."\n";
		
		$body .= "\n".'--- Admin URL ---'."\n";
		$body .= 'http://'.$_SERVER['HTTP_HOST'].unhtmlentities($this->admin_page->make_link(array('user_id'=>'')))."\n";
		
		$body .= "\n".'--- Details ---'."\n\n";
		foreach( $this->original->_values AS $el => $orig_value )
		{
			if( !in_array( $el, $mail_ignores_fields ) )
			{
				$orig_value = trim($orig_value);
				$new_value = trim($this->get_value( $el ));
				if( !empty($orig_value) || !empty($new_value) )
				{
					if( $new_value != $orig_value )
						$append = ' ** Changed ** ';
					else
						$append = '';
					if(array_key_exists($el,$this->pretty_names))
						$name = $this->pretty_names[$el];
					else
						$name = prettify_string( $el );
					if(!empty($new_value))
						$value = strip_tags($new_value);
					else
						$value = '[Empty]';
					$body .= $name.$append.': '.$value."\n\n";
				}
			}
		}
		$additional_headers = 'From: Reason Projects <webmaster@'.$_SERVER['SERVER_NAME'].'>'."\r\n";
		
		mail( join($addresses,','), $subject,$body,$additional_headers );
	}
	function get_addresses()
	{
		$owner = new entity( $this->get_value( 'bug_owner' ) );
		$updater = new entity( $this->get_value( 'last_edited_by' ) );
		
		$owner_updater = array( $owner->get_value('name'), $updater->get_value('name'), );
		$leads = split( ',',$this->get_value( 'author' ) );
		$assignees = split( ',',$this->get_value( 'assigned_to' ) );
		
		$users = array_merge( $owner_updater, $leads, $assignees );
		
		$addresses = array();
		
		foreach($users as $user)
		{
			$address = $this->get_email_addr( $user );
			if(!empty($address))
			{
				$addresses[$user] = $address;
			}
		}
		return $addresses;
		
	}
	function get_email_addr( $user )
	{
		if(!empty($this->email_addresses[$user]))
		{
			return $this->email_addresses[$user];
		}
		else
		{
			$dir = new directory_service();
			if ($dir->search_by_attribute('ds_username', $user, array('ds_email','ds_fullname')))
			{
				$this->email_addresses[$user] = $dir->get_first_value('ds_email');
				return $dir->get_first_value('ds_email');
			}
			return false;
		}
	}
}
?>
