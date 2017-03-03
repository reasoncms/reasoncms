<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'UserManager';
	
	/**
	 * The content manager form used to edit Reason user entities
	 */
	class UserManager extends ContentManager
	{
		var $nice_labels = array(
								'name'=>'Username',
								'user_surname'=>'Surname or Family Name',
								'user_given_name'=>'Given/First Name',
								'user_authoritative_source'=>'Authoritative Source',
								'site_window_pref'=>'Public Site Link Preference',
								'user_email'=>'Email',
								'user_phone'=>'Phone',
								'user_popup_alert_pref'=>'Logout Notification Preference',
								);
		var $autocompletes = array(
				'name' => 'username-for-other-user',
				'user_surname' => 'family-name-for-other-user',
				'user_given_name' => 'given-name-for-other-user',
				'user_email' => 'email-for-other-user',
				'user_phone' => 'tel-for-other-user',
				'password' => 'new-password-for-other-user',
				'confirm_password' => 'new-password-for-other-user',
		);
		function init_head_items()
		{
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'user_content_manager.js?v=2');
		}
		function alter_data()
		{
			if (!( $this->get_value('user_popup_alert_pref') ))
			{
				if (DEFAULT_TO_POPUP_ALERT)
				{
					$this->set_value('user_popup_alert_pref', 'yes');
				}
				else
				{
					$this->set_value('user_popup_alert_pref', 'no');
				}
			}
			$this->change_element_type('user_popup_alert_pref', 'select', array('options'=>array('yes'=>'Javascript alert (more accessible)','no'=>'In-page (less obtrusive)'),'add_empty_value_to_top' => false));
			
			
			
			$this->add_element('password', 'password');
			$this->add_element('confirm_password', 'password');
			$this->change_element_type('user_password_hash', 'cloaked');
			if($this->get_value('user_password_hash'))
			{
				$this->set_display_name('password','Change Password');
				$this->add_comments('password',form_comment('(This user currently has a password)'));
			}
			else
			{
				$this->set_display_name('password','Set Password');
				$this->add_comments('password',form_comment('(This user does not currently have a password. They will not be able to log in until they have a password.)'));
			}
			include_once(CARL_UTIL_INC.'dir_service/directory.php');
			$dir_service = new directory_service();
			$srvcs = $dir_service->get_available_services();
			if(empty($srvcs))
			{
				trigger_error('Major problem -- no directory services appear to be set up. Reason needs at least one directory service to function. Fr a basic setup, add this line to the directory service config file: $available_services = array(\'reason\');');
			}
			$reason_is_available_dir_service = false;
			$other_dir_service_available = false;
			$reason_keys = array_keys($srvcs,'reason');
			if(!empty($reason_keys))
			{
				$reason_is_available_dir_service = true;
				foreach($reason_keys as $key)
					unset($srvcs[$key]);
			}
			if(!empty($srvcs)) // there's something else there...
			{
				$other_dir_service_available = true;
			}
			$auth_source_options = array();
			if(($reason_is_available_dir_service && $other_dir_service_available) || ($this->get_value('user_authoritative_source') == 'reason' && !$reason_is_available_dir_service) || ($this->get_value('user_authoritative_source') == 'external' && !$other_dir_service_available))
			{
				$this->change_element_type('user_authoritative_source','select_no_sort',array('options'=>array('reason'=>'Reason','external'=>'External'),'add_empty_value_to_top'=>false));
				
				if($this->get_value('user_authoritative_source') == 'reason' && !$reason_is_available_dir_service)
				{
					trigger_error('User '.$this->get_value('name').' is set to use Reason as their authoritative source, but Reason is not an available directory service.');
					$this->add_comments('user_authoritative_source',form_comment('Note: this user may not be able to log in, because Reason is not an available login method. You may need to set this user to have an external authority.'));
				}
				elseif($this->get_value('user_authoritative_source') == 'external' && !$other_dir_service_available)
				{
					trigger_error('User '.$this->get_value('name').' is set to use an external authoritative source, but there are no external directory services available.');
					$this->add_comments('user_authoritative_source',form_comment('Note: this user may not be able to log in, because there are no external authorities set up for this instance of Reason. You may ned to set this field to "Reason" before they can log in.'));
				}
			}
			else
			{
				$this->change_element_type('user_authoritative_source','hidden');
				if(!$reason_is_available_dir_service)
				{
					foreach(array('user_given_name','user_surname','user_email','user_phone','password','confirm_password') as $field)
					{
						$this->change_element_type($field,'hidden');
					}
				}
			}
			if(!$this->get_value('user_authoritative_source'))
			{
				if(REASON_USERS_DEFAULT_TO_AUTHORITATIVE)
				{
					if($reason_is_available_dir_service)
						$this->set_value('user_authoritative_source','reason');
					else
					{
						$error_string = 'The setting REASON_USERS_DEFAULT_TO_AUTHORITATIVE is set to true, but Reason is not an available directory service. You should either set REASON_USERS_DEFAULT_TO_AUTHORITATIVE to false, or add "reason" to the set of available directory services in the dir_service config file.';
						trigger_error($error_string);
						$this->add_element('conflict_notice','comment',array('text'=>$error_string));
					}
				}
				elseif($other_dir_service_available)
				{
					$this->set_value('user_authoritative_source','external');
				}
				else
				{
					$error_string = 'The setting REASON_USERS_DEFAULT_TO_AUTHORITATIVE is set to false, but there are no external directory services configured. You should either set REASON_USERS_DEFAULT_TO_AUTHORITATIVE to true, or add at least one additional directory service in the dir_service config file.';
					trigger_error($error_string);
					$this->add_element('conflict_notice','comment',array('text'=>$error_string));
				}
			}
			$this->add_relationship_element('role', id_of('user_role'), relationship_id_of('user_to_user_role'),'right','select');
			if(!$this->get_value('role'))
			{
				$this->set_value('role',id_of('editor_user_role'));
			}
			foreach($this->nice_labels as $name=>$label)
			{
				$this->set_display_name($name,$label);
			}
			foreach($this->autocompletes as $name=>$autocomplete)
			{
				$this->set_element_properties( $name, array('autocomplete' => $autocomplete) );
			}
			$this->set_order(array('conflict_notice','name','role','site_window_pref','user_popup_alert_pref','user_authoritative_source','user_given_name','user_surname','user_email','user_phone','password','confirm_password'));
		}
		
		function run_error_checks()
		{
				$es = new entity_selector;
				$es->description = 'User Content Manager: seeing if username already exists';
				$es->add_type( id_of('user') );
				$es->add_relation('entity.name = "'.$this->get_value('name').'"');
				$es->add_relation('entity.id != "'.$this->_id.'"');
				$same_named_users = $es->run_one();

				if(!empty($same_named_users))
				{
					$this->set_error( 'name','This user has already been added to Reason.' );
				}
				
				if ($this->get_value( 'password' ))
				{
					if ($this->get_value( 'password' ) != $this->get_value( 'confirm_password' ))
						$this->set_error( 'password', 'The passwords you typed did not match.' );
					else
						$this->check_password_strength();
				}
						
				parent::run_error_checks();		
		}
		
		function process() // {{{
		{
			if ($this->get_value( 'password' ))
				$this->set_value('user_password_hash', sha1($this->get_value( 'password' )));
			
			parent::process();
		}
		
		function check_password_strength()
		{
			/* Put whatever password strength checks you want here */
			if (strlen($this->get_value( 'password' )) < 5)
				$this->set_error( 'password', 'Your password needs to be at least 5 characters long.' );
			if (trim($this->get_value( 'password' )) == '')
				$this->set_error( 'password', 'Your password needs to contain something other than spaces.' );
		}
		
	}
?>
