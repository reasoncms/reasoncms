<?php
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'UserManager';
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	
	class UserManager extends ContentManager
	{
		var $nice_labels = array(
								'name'=>'username',
								'user_surname'=>'Surname or Family Name',
								'user_given_name'=>'Given/First Name',
								'user_authoritative_source'=>'Authoritative Source',
								'site_window_pref'=>'New Window Preference',
								'user_email'=>'Email',
								'user_phone'=>'Phone',
								);
		function pre_show_form()
		{
			parent::pre_show_form();
			echo '<script language="JavaScript" src="'.REASON_HTTP_BASE_PATH.'js/user_content_manager.js"></script>'."\n";
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
			$this->change_element_type('user_popup_alert_pref', 'select', array('add_null_value_to_top' => false));
			
			$this->add_element('password', 'password');	
			$this->add_element('confirm_password', 'password');
			$this->change_element_type('user_password_hash', 'cloaked', array('display_style'=>'hidden'));
			if($this->get_value('user_password_hash'))
			{
				$this->set_display_name('password','Change Password');
				$this->add_comments('password',form_comment('(This user currently has a password)'));
			}
			else
			{
				$this->set_display_name('password','Set Password');
				$this->add_comments('password',form_comment('(This user does not currently have a password)'));
			}
			$this->change_element_type('user_authoritative_source','select_no_sort',array('options'=>array('reason'=>'Reason','external'=>'External'),'add_null_value_to_top'=>false));
			if(!$this->get_value('user_authoritative_source'))
			{
				if(REASON_USERS_DEFAULT_TO_AUTHORITATIVE)
				{
					$this->set_value('user_authoritative_source','reason');
				}
				else
				{
					$this->set_value('user_authoritative_source','external');
				}
			}
			foreach($this->nice_labels as $name=>$label)
			{
				$this->set_display_name($name,$label);
			}
			$this->set_order(array('name','site_window_pref','user_authoritative_source','user_given_name','user_surname','user_email','user_phone','password','confirm_password'));
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
