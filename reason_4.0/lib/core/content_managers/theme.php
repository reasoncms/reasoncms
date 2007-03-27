<?php
	
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'ThemeManager';

	class ThemeManager extends ContentManager
	{
		function alter_data() // {{{
		{
			$this->add_relationship_element('template', id_of('minisite_template'), 
			relationship_id_of('theme_to_minisite_template'),'right','select');
		} // }}}
		
	}
?>
