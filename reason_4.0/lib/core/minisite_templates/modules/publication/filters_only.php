<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include parent class
 */
reason_include_once('minisite_templates/modules/publication/module.php');

/**
 * Register the module with Reason
 *
 * PLEASE NOTE: the blog directory is being hard-coded in this array. If the file is moved, or if the directory is renamed, this module will stop working.
 */
$GLOBALS[ '_module_class_names' ][ 'publication/'.basename( __FILE__, '.php' ) ] = 'PublicationFiltersOnlyModule';

/**
 * Show just the filters for a blog
 *
 * Developed so that the filters could be contained in a different place on the page
 * than the rest of the blog module
 *
 * @author Matt Ryan & Henry Gross
 * @todo Develop a better way to break publication parts up into separate modules -- this solution
 *       is fairly hacky, requirs "neutering" of things like crumb and feed modification, and will
 *       give poor performance (lots of doubling of work going on here.)
 */
class PublicationFiltersOnlyModule extends PublicationModule
{
	/**
	 * Overloads the generic run function and only calls show_filtering
	 * rather than doing the full logic normally performed in the blog module
	 */
	function run()
	{
		echo '<div id="blog_filters">'."\n";
		$this->show_filtering();
		echo $this->get_login_logout_link();
		echo '</div>';
	}
	function add_feed_to_head()
	{
		// do nothing
	}
	function add_crumb()
	{
		// do nothing
	}
}

?>
