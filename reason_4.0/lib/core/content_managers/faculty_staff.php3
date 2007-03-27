<?php
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'faculty_staff_handler';

	class faculty_staff_handler extends ContentManager 
	{
		var $ldap_field_mapping = array(
										'ds_fullname'=>'Name',
										'ds_phone'=>'Phone',
										'ds_email'=>'Email Address'
									);
		var $ldap_function_mapping = array(
										'ds_email'=>'make_email_link',
									);
		function alter_data()  // {{{
		{
			$this->set_display_name ('name', 'Username');
			$this->change_element_type( 'degrees' , 'hidden' );
			$this->remove_element( 'author' );
			$this->hide_elements();
			
			$info = $this->get_person_info();
			$pre = '';
			$post = '';
			if(!empty($info))
			{
				$this->add_element('directory_info','solidtext');
				$this->set_value('directory_info',$info);
				$pre = 'Additional ' ;
				$post = '<br /><span class="smallText">(This will appear below directory info)</span>';
			}
			$this->set_display_name('content',$pre.'Personal Information'.$post);
			
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			
			if( !user_is_a( $this->admin_page->user_id , id_of( 'admin_role' ) ) )
				$this->change_element_type( 'ldap_created' , 'hidden' );
			else
				$this->set_display_name ('ldap_created', 'Automatically Created');

			$this->set_order( array(
				'comment',
				'name',
				'directory_info',
				'degrees',
				'affiliation',
				'content',
			) );
		} // }}}

		function hide_elements()
		{
			$this->change_element_type( 'affiliation' , 'hidden' );
		}

		function get_person_info() //{{{
		{
			$ret = '';
			if($this->get_value('name'))
			{
				include_once(CARL_UTIL_INC.'dir_service/directory.php');
				$dir = new directory_service();
				if ($dir->search_by_attribute('ds_username',$this->get_value('name'),
						array_keys($this->ldap_field_mapping)))
				$person = $dir->get_first_record();
				foreach($this->ldap_field_mapping as $ldap_term=>$english_term)
				{
					if(!empty($person[$ldap_term]))
					{
						if(!empty($this->ldap_function_mapping[$ldap_term]))
						{
							$function = $this->ldap_function_mapping[$ldap_term];
							$value = $this->$function($person[$ldap_term][0]);
						}
						else
						{
							$value = $person[$ldap_term][0];
						}
						$lines[] = '<li><strong>'.$english_term.':</strong> '.$value.'</li>';
					}
				}
				if(!empty($lines))
				{
					$ret = '<ul>'."\n".implode("\n",$lines).'</ul>'."\n";
				}
			}
			return $ret;
		} // }}}
		function make_email_link($email) //{{{
		{
			return '<a href="mailto:'.$email.'">'.$email.'</a>';
		} // }}}
	}
?>
