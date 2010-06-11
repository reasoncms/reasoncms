<?php
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherFooterModule';

	class LutherFooterModule extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{

		}
		function has_content()
		{
			return true;
		}
		function run()
		{
			echo '<div id="foot">'."\n";
                        echo '<center> Email burkaa01@luther.edu with Questions/Comments/Suggestions</center>'."\n";

  			echo '<center><div>'."\n";
  			echo '<div>Copyright '.date("Y").' &#8226'.'Luther College'."\n".'</div>';
      			echo '<span class="street-address">700 College Drive Decorah,IA 52101</span>'."\n";
	                echo '<div>Phone: 563-387-2000 or 800-4 LUTHER (<span class="tel">800-458-8437</span>)</div></div></center>'."\n";
			google_analytics();
		}
	}
?>