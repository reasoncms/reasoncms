<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
/**
 * Include parent class
 */
reason_include_once( 'minisite_templates/modules/publication/item_markup_generators/responsive.php' );
/**
 * Item markup generator that hides date of post
 */
class NoDateItemMarkupGenerator extends ResponsiveItemMarkupGenerator
{	
	function should_show_date_section()
	{
		return false;
	}
}
?>