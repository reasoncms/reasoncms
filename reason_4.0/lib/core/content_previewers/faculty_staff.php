<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	/**
	 * Register previewer with Reason & include dependencies
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'facultyStaffPreviewer';
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	
	/**
	 * A content previewer for faculty/staff entities
	 */
	class facultyStaffPreviewer extends default_previewer
	{
		var $ldap_field_mapping = array(
										'ds_fullname'=>'Name',
										'ds_phone'=>'Phone',
										'ds_email'=>'Email Address',
									);
		var $ldap_function_mapping = array(
										'ds_email'=>'make_email_link',
									);
		function post_show_entity() // {{{
		{
			$dir = new directory_service();
			if ($dir->search_by_attribute('ds_username',$this->_entity->get_value('name'),
					array_keys($this->ldap_field_mapping)))	
				$this->show_person($dir->get_first_record());
		} // }}}
		function show_person($person) //{{{
		{
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
			echo '<h3>Directory information about this person</h3>'."\n";
			echo '<ul>'."\n".implode("\n",$lines).'</ul>'."\n";
			}
		} // }}}
		function make_email_link($email) //{{{
		{
			return ('<a href="mailto:'.$email.'>'.$email.'</a>');
		} // }}}
	}
?>
