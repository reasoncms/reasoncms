<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AdmissionsNavigationModule';
	
	class AdmissionsNavigationModule extends DefaultMinisiteModule
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
			echo '<div class="sidebar">'."\n";
			echo '<div class="logo">'."\n";
			echo '<h1><a href="#">Luther College</a></h1>'."\n";
			echo '<h2><a href="#">Admissions</a></h2>'."\n";
			echo '</div>'."\n";
                        if ($this->cur_page->get_value( 'custom_page' ) == 'admissions_home')
			{

                        	echo '<ul class="nav picnav clearfix">'."\n";
                        	echo '<li id="music"><a href="#">Music</a></li>'."\n";
                        	echo '<li id="sports"><a href="#">Sports</a></li>'."\n";
                        	echo '</ul>'."\n";
			}

			return;
		}
	}
?>
