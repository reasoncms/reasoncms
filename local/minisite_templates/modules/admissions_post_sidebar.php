<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AdmissionsPostSidebarModule';
	
	class AdmissionsPostSidebarModule extends DefaultMinisiteModule
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
				// Events 
                        	echo '<div class="supplemental block block-3 events">'."\n";
                        	echo '<h2>Upcoming Events</h2>'."\n";
                        	echo '<dl>'."\n";
                        	echo '<dt>April 15th, 2009</dt>'."\n";
                        	echo '<dd>Event Title here...</dd>'."\n";
                        	echo '<dt>June 15th, 2009</dt>'."\n";
                        	echo '<dd>Lorem ipsum dolor sit</dd>'."\n";
                        	echo '<dt>August 15th, 2009</dt>'."\n";
                        	echo '<dd>Lorem ipsum dolor sit</dd>'."\n";
                        	echo '<dt>October 15th, 2009</dt>'."\n";
                        	echo '<dd>Lorem ipsum dolor sit</dd>'."\n";
                        	echo '</dl>'."\n";
                        	echo '<p class="links">'."\n";
                        	echo '<a href="#" class="all">See all events</a>'."\n";
                        	echo '</p>'."\n";
                        	echo '</div>'."\n";
                        	echo '</div class="content clearfix">'."\n";
                        	echo '</div>'."\n";
                        	echo '</div>'."\n";
			}

			return;
		}
	}
?>
