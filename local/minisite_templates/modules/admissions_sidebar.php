<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AdmissionsSidebarModule';
	
	class AdmissionsSidebarModule extends DefaultMinisiteModule
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
				// Highlights 
                        	echo '<div class="supplemental block block-2">'."\n";
                        	echo '<h2>College Highlights</h2>'."\n";
                        	echo '<p><img src="/images/admissions/highlight-image.jpg" class="caption" alt="Studying abroad" />Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nulla <a href="#">venenatis</a> nunc sit amet Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla</p>'."\n";
                        	echo '<p class="links">'."\n";
                        	echo '<a href="#" class="more">Read more</a>'."\n";
                        	echo '<a href="#" class="all">See all highlights</a>'."\n";
                        	echo '</p>'."\n";
                        	echo '</div>'."\n";
			}

			return;
		}
	}
?>
