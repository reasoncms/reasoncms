<?php

reason_include_once('minisite_templates/modules/blog/module.php');
/* PLEASE NOTE: the blog directory is being hard-coded in this array. If the file is moved, or if the directory is renamed, this module will stop working. */
$GLOBALS[ '_module_class_names' ][ 'blog/'.basename( __FILE__, '.php' ) ] = 'BlogFiltersOnlyModule';

/**
 * Show just the filters for a blog
 * Developed so that the filters could be contained in a different place on the page
 * than the rest of the blog module
 * @author Matt Ryan & Henry Gross
 */
class BlogFiltersOnlyModule extends BlogModule
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
}

?>
