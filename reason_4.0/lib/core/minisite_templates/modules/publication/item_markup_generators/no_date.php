<?php
reason_include_once( 'minisite_templates/modules/publication/item_markup_generators/default.php' );

class NoDateItemMarkupGenerator extends PublicationItemMarkupGenerator
{	
	function should_show_date_section()
	{
		return false;
	}
}
?>