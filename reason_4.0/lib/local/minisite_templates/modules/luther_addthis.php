<?php
	include_once( 'reason_header.php' );
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'function_libraries/user_functions.php' );

	/**
	* Register module with Reason
	*/
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherAddthisModule';
	
	class LutherAddthisModule extends DefaultMinisiteModule
	// insert "addthis" capability to luther pages linking to facebook,
	// twitter, delicious, etc.
	{
		/**
		 * Tells the template that this module always contains content
		 */
		function has_content() 
		{
			return true;
		}
		
		function run() 
		{
			echo '<!-- AddThis Button BEGIN -->'."\n";
			echo '<div class="addthis_toolbox addthis_default_style">'."\n";
			echo '<a href="//www.addthis.com/bookmark.php?v=250&amp;pub=lutheraddthis" class="addthis_button_compact"></a>'."\n";
			echo '<span class="addthis_separator">|</span>'."\n";
			echo '<a class="addthis_button_facebook"></a>'."\n";
			echo '<a class="addthis_button_twitter"></a>'."\n";
			echo '<a class="addthis_button_email"></a>'."\n";
			echo '<a class="addthis_button_print"></a>'."\n";
			echo '</div>'."\n";
			echo '<script type="text/javascript" src="//s7.addthis.com/js/250/addthis_widget.js#pub=lutheraddthis">'."\n";
			echo 'var addthis_share = {'."\n";
			echo 'url : ' . get_current_url() . ','."\n";
			echo '}'."\n";
			echo '</script>'."\n";
			echo '<!-- AddThis Button END -->'."\n";
		}
	}
?>
