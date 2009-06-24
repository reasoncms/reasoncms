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
			//print_r($this);
			$theme = get_theme($this->site_id);
			if ($theme->get_value( 'name' ) == 'admissions')
			{
                        echo '<div class="footer">'."\n";
                        echo '<ul class="nav">'."\n";
                        echo '<li><a href="www.luther.edu/about/">About This Site</a></li>'."\n";
                        echo '<li><a href="www.luther.edu/privacy/">Privacy Statement</a></li>'."\n";
                        echo '<li><a href="#mailto:www@luther.edu">Contact Us</a></li>'."\n";
                        echo '</ul>'."\n";
                        echo '<p>Copyright '.date("Y").' Luther College &bull; 700 College Drive Decorah, Iowa 52101  USA
        <br />Phone: 563-387-2000 or 800-4 LUTHER (800-458-8437)</p>'."\n";
                        echo '</div>'."\n";

			}
			else
			{
			echo '<div id="foot">'."\n";
    			echo '<ul><li><a href="http://www.luther.edu/about/">About This Site</a></li>'."\n";
    			echo '<li><a href="http://www.luther.edu/privacy/">Privacy Statement</a></li></ul>'."\n";

  			echo '<div class="vcard">'."\n";
  			echo '<div class="adr">Copyright '.date("Y").' &#8226'."\n";
	  		echo '<span class="fn org">Luther College</span> &#8226'."\n";
      			echo '<span class="street-address">700 College Drive</span> &#8226'."\n";
      			echo '<span class="locality">Decorah</span>,'."\n";
      			echo '<span class="region">Iowa</span>'."\n";
      			echo '<span class="postal-code">52101</span>'."\n";
          		echo '<span class="country-name">USA</span></div>'."\n";
	                echo 'Phone: 563-387-2000 or 800-4 LUTHER (<span class="tel">800-458-8437</span>)</div></div>'."\n";
			}
			google_analytics();
		}
	}
?>
