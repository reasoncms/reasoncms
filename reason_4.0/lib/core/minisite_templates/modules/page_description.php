<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
	/**
	 * Register module with Reason and include dependencies
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PageDescriptionModule';

	/**
	 * A minisite module that displays the description of the current page
	 * (normally only displayed in a meta tag or in listings on other pages.)
	 */
	class PageDescriptionModule extends DefaultMinisiteModule
	{
		function has_content()
		{
			if($this->cur_page->get_value('description'))
				return true;
			else
				return false;
		}
		function run()
		{
			echo '<div id="pageDescription">'."\n";
			echo $this->cur_page->get_value('description');
			echo '</div>'."\n";
		}
	}
?>
