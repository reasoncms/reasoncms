<?php
	reason_include_once( 'content_managers/default.php3' );
	
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'OfficesDeptsManager';

	class OfficesDeptsManager extends ContentManager
	{
		function alter_data() // {{{
		{
			$this->set_comments('name', form_comment('This is the public name of the office/department'));
			
			$this->add_required( 'show_hide' );
			$this->set_display_name('show_hide', 'Show or Hide?');
			$this->set_comments('show_hide', form_comment('Use this to hide defunct/inappropriate depts or offices'));
			if(!$this->get_value( 'show_hide' ))
				$this->set_value('show_hide','show');
			
			$this->add_required( 'sync_name' );
			$this->set_display_name('sync_name', 'Synchronization Name');
			$this->set_comments('sync_name', form_comment('This should be the same as the LDAP department name.  It is sometimes different from the public name'));
			
			$this->add_required( 'office_department_code' );
			$this->set_display_name('office_department_code', 'Colleague/Registrar Code(s)');
			$this->set_comments('office_department_code', form_comment('This is a comma-delimited list of Registrar codes used by the department'));
			
			$this->add_required( 'office_department_type' );
			$this->set_display_name('office_department_type', 'Type');
			
			$this->set_order(array('name','sync_name','office_department_code','office_department_type','show_hide'));
			
		} // }}}
		
	}
?>
