<?php

	reason_include_once('content_managers/default.php3');

	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'databaseManager';

	class databaseManager extends ContentManager
	{
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
