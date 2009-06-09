<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AdmissionsFooterModule';
	
	class AdmissionsFooterModule extends DefaultMinisiteModule
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
			echo '<div class="footer">'."\n";
			echo '<ul class="nav">'."\n";
			echo '<li><a href="#">About This Site</a></li>'."\n";
			echo '<li><a href="#">Privacy Statement</a></li>'."\n";
			echo '<li><a href="#">Contact Us</a></li>'."\n";
			echo '</ul>'."\n";
			echo '<p>Copyright '.date("Y").' Luther College &bull; 700 College Drive Decorah, Iowa 52101  USA
	<br />Phone: 563-387-2000 or 800-4 LUTHER (800-458-8437)</p>'."\n";
			echo '</div>'."\n";
		}
	}
?>
