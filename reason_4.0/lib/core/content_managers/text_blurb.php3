<?php
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'text_blurb';

	class text_blurb extends ContentManager
	{
		function alter_data()
		{
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
		}
	}
?>
