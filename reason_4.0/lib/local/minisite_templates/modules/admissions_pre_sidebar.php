<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AdmissionsPreSidebarModule';
	
	class AdmissionsPreSidebarModule extends DefaultMinisiteModule
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
				// Spotlights
                        	echo '<div class="supplemental block block-1">'."\n";
                        	echo '<h2>Luther Spotlight</h2>'."\n";
                        	echo '<p><img src="/images/admissions/spotlight-image.jpg" class="caption" alt="Evan Waylonson" />Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nulla <a href="#">venenatis</a> nunc sit amet Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla</p>'."\n";
                        	echo '<p class="links">'."\n";
                        	echo '<a href="#" class="more">Read more</a>'."\n";
                        	echo '<a href="#" class="all">See all spotlights</a>'."\n";
                        	echo '</p>'."\n";
                        	echo '</div>'."\n";
			}

			return;
		}
	}
?>
