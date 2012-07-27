<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AdmissionsSubNav2Module';
	
	class AdmissionsSubNav2Module extends DefaultMinisiteModule
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
                        if ($this->cur_page->get_value( 'custom_page' ) == 'admissions_home')
			{
                        	echo '<ul class="nav picnav clearfix">'."\n";
                        	echo '<li id="music"><a href="http://music.luther.edu">Music</a></li>'."\n";
                        	echo '<li id="sports"><a href="http://sports.luther.edu">Sports</a></li>'."\n";
                        	echo '</ul>'."\n";

			}

			return;
		}
	}
?>
