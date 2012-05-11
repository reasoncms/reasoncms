<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Include the parent class
	 */
	reason_include_once( 'content_managers/default.php3' );
	
	/**
	 * Register the content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'OfficesDeptsManager';

	/**
	 * A content manager for offices/departments (i.e. organizational units)
	 */
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
			
			$this->change_element_type('sync_name', 'hidden');
			$this->change_element_type('office_department_code', 'hidden');
			
			$this->add_required( 'office_department_type' );
			$this->set_display_name('office_department_type', 'Type');
			
			$this->set_order(array('name','sync_name','office_department_code','office_department_type','show_hide'));
			
		} // }}}
		
	}
?>
