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
                        	echo '<div class="block keydates events">'."\n";
                        	echo '<h2>Key Admission Dates</h2>'."\n";
                        	echo '<dl>'."\n";
                        	echo '<dt>April 15, 2009</dt>'."\n";
                        	echo '<dd>Lorem ipsum dolor sit amet, <a href="#">consectetur</a> adipisicing elit. Donec sed justo et orci sodales.</dd>'."\n";
                        	echo '<dt>June 15, 2009</dt>'."\n";
                        	echo '<dd>Nullam venenatis, est in ultrices pretium, tortor turpis varius eros, sed iaculis nisi.</dd>'."\n";
                        	echo '<dt>August 15, 2009</dt>'."\n";
                        	echo '<dd>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Donec sed justo et orci sodales.</dd>'."\n";
                        	echo '</dl>'."\n";
                        	echo '</div>'."\n";
			}
                       	echo '</div class="sidebar">'."\n";

			return;
		}
	}
?>
