<?php
/**
 * A content manager for categories
 * @package reason
 * @subpackage content_managers
 */
 
  /**
   * Store the class name so that the admin page can use this content manager
   */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'categoryManager';

	/**
	 * A content manager for categories
	 *
	 * This class customizes the editing interface for category entities
	 *
	 */
	class categoryManager extends ContentManager
	{
		function alter_data()
		{
			if ($this->_is_element('campus_pipeline_equivalent'))
			{
				$this->change_element_type ('campus_pipeline_equivalent', 'hidden');
				trigger_error('Your database contains an obsolete category field called campus_pipeline_equivalent. 
						       Please run the upgrade script: '.REASON_HTTP_BASE_PATH.'
						       scripts/upgrade/4.0b7_to_4.0b8/update_types.php to remove the field.');
			}
			if ($this->_is_element('old_calendar_equivalent'))
			{
				$this->change_element_type('old_calendar_equivalent','hidden');
				trigger_error('Your database contains an obsolete category field called old_calendar_equivalent. 
						       Please run the upgrade script: '.REASON_HTTP_BASE_PATH.'
						       scripts/upgrade/4.0b7_to_4.0b8/update_types.php to remove the field.');
			}
			$this->change_element_type('keywords','hidden');
			$this->change_element_type('description','hidden');
			$this->alter_slug_field();
		}
		
		function run_error_checks()
		{				
			parent::run_error_checks();
			if ($this->_is_element('slug') && reason_user_has_privs( $this->admin_page->user_id, 'edit_fragile_slugs' ) && $slug = $this->get_value('slug'))
			{
				if (!preg_match('/^[0-9a-z-_]+$/', $slug))
					$this->set_error('slug', 'Slugs may only contain numbers, lowercase letters, hyphens, and underscores.');
			}
		}


		function alter_slug_field()
		{
			if ($this->_is_element('slug'))
			{
				if (!reason_user_has_privs( $this->admin_page->user_id, 'edit_fragile_slugs' )) 
					$this->change_element_type('slug','hidden');
				else
				{
					if ($this->get_value('slug'))
						$this->set_comments('slug', form_comment('Warning: changing slugs may cause existing links to break. Proceed with caution.'));
				}
			}
		}
	}
?>
