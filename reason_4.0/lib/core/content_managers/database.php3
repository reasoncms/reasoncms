<?php
/**
 * A content manager for database entities
 * (e.g. links to & information about academic databases and/or other off-site resources)
 * @package reason
 * @subpackage content_managers
 */

/**
 * Include the parent class
 */
	reason_include_once('content_managers/default.php3');

/**
 * Save the class name in the globals so that the admin page can use this content manager
 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'databaseManager';

/**
 * A content manager for database entities
 * 
 * Database entities wrap up links to & information about academic databases 
 * and/or other off-site resources.
 *
 * This content manager customizes the interface for managing these entities.
 */
	class databaseManager extends ContentManager
	{
		/**
		 * Customizes the disco form
		 *
		 * Sets the order of form elements and provides user-friendly labels and comments
		 */
		function alter_data()
		{	
			$this->set_display_name('datetime','Date Added');
			$this->set_comments('datetime',form_comment('mm/dd/yyyy') );
			$this->set_display_name('date_string','Dates Covered');
			$this->set_display_name('output_parser','EndNote Filter');

			$this->set_order(
				array(
					'name',
					'description',
					'datetime',
					'date_string',
					'keywords',
					'output_parser',
				)
			);
		}
	}
?>
