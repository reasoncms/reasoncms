<?php
/**
 * A content manager for audience entities
 * @package reason
 * @subpackage content_managers
 */
 
 /**
  * Include dependencies
  */
	reason_include_once( 'content_managers/default.php3' );
 
 /**
  * Define the class name so that the admin page can use this content manager
  */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'audienceManager';

	/**
	 * A content manager for audience entities
	 * 
	 * This content manager costomizes the interface and error checking for managing audiences
	 *
	 * @todo Make sure that this content manager has actually been attached to the audience type via an upgrade script
	 */
	class audienceManager extends ContentManager
	{
		function alter_data()
		{
			$this->add_required('directory_service_value');
			$this->_no_tidy[] = 'audience_filter';
			$this->add_comments('directory_service_value',form_comment('If Reason is integrated with a directory service, this should be the same as the string that identifies the affiliation in your directory service.'));
		}
		function run_error_checks() // {{{
		{
			parent::run_error_checks();
			$es = new entity_selector();
			$es->add_relation('audience_integration.directory_service_value = "'.reason_sql_string_escape($this->get_value('directory_service_value')).'"');
			$es->add_relation('entity.id != '.$this->get_value('id'));
			$es->set_num(1);
			$conflicts = $es->run_one(id_of('audience_type'));
			if(!empty($conflicts))
			{
				$this->set_error( 'directory_service_value', 'The Directory Service Value you entered ("'.$this->get_value('directory_service_value').'") is already in use. Each audience must have a unique directory service value.' );
			}
		} // }}}
	}
?>
