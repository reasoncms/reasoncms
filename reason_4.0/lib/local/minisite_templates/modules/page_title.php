<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
	/**
	 * Register module with Reason and include dependencies
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PageTitleModule';

	/**
	 * A minisite module that displays the title of the current page
	 */
	class PageTitleModule extends DefaultMinisiteModule
	{
		function has_content()
		{
			return !empty( $this->parent->title );
		}
		function run()
		{			
			$theme = get_theme($this->site_id);
			if ($theme->get_value( 'name' ) == 'luther2010')
			{
				if (preg_match("/[&?]event_id=\d+/", get_current_url()))
				{
					return;
				}
				echo '<h1 class="page-title">'.$this->parent->title.'</h1>'."\n";
			}
			else
			{
  				echo '<h2 class="pageTitle"><span>'.$this->parent->title.'</span></h2>';
			}
		}
	}
?>
