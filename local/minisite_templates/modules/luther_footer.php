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
			if ($theme->get_value( 'name' ) == 'luther2010')
			{	
				echo '<footer class="site-info">'."\n";
    			echo '<nav class="site-info"><ul><li class="about"><a href="/siteinfo/">About This Site</a></li><li class="about"><a href="http://emergency.luther.edu">Emergency Info</a></li><li class="about"><a href="/privacy/">Privacy Statement</a></li><li><a href="/contact/">Contact</a></li> </ul></nav>'."\n";

				echo '<div class="vcard">'."\n";
				echo '<span class="copyright">Copyright '.date("Y").' </span> &#8226;'."\n";
     			echo '<span class="fn org">Luther College</span> &#8226;'."\n";
     			echo '<span class="adr">'."\n";
      			echo '<span class="street-address">700 College Drive</span> &#8226;'."\n";
				echo '<span class="locality">Decorah</span>,'."\n";
				echo '<span class="region">Iowa</span>'."\n";

				echo '<span class="postal-code">52101</span>'."\n";
				echo '<span class="country-name">USA</span>'."\n";
				echo '</span>'."\n";
				echo '<div>Phone: 563-387-2000 or 800-4 LUTHER (<span class="tel">800-458-8437</span>)</div>'."\n";
				echo '</div>'."\n";
				//luther_social_media();
				echo '</footer>'."\n";
				
				echo '<script src="/javascripts/jquery-1.6.1.min.js" type="text/javascript"></script>'."\n";
				//echo '<script src="/javascripts/jquery-ui-1.8.2.custom.min.js" type="text/javascript"></script>'."\n";
				echo '<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js"></script>'."\n";
				echo '<script src="/javascripts/jquery.tmpl.js" type="text/javascript"></script>'."\n";
				echo '<script src="/javascripts/jquery.metadata.js" type="text/javascript"></script>'."\n";
				echo '<script src="/javascripts/tablesorter.min.js" type="text/javascript"></script>'."\n";
				echo '<script src="/javascripts/jquery.hoverIntent.min.js" type="text/javascript"></script>'."\n";
				echo '<script src="/javascripts/cluetip/jquery.cluetip.js" type="text/javascript"></script>'."\n";
				echo '<script src="/javascripts/jquery.init.js" type="text/javascript"></script>'."\n";
                                echo '<script src="/reason/jquery.watermark-3.1.3/jquery.watermark.min.js" type="text/javascript"></script>'."\n";
				
			}
			elseif ($theme->get_value( 'name' ) == 'admissions')
			{
                        echo '<div class="footer">'."\n";
                        echo '<ul class="nav">'."\n";
                        echo '<li><a href="/siteinfo/">About This Site</a></li>'."\n";
                        echo '<li><a href="/privacy/">Privacy Statement</a></li>'."\n";
                        echo '<li><a href="mailto:www@luther.edu">Contact Us</a></li>'."\n";
                        echo '</ul>'."\n";
                        echo '<p>Copyright '.date("Y").' Luther College &bull; 700 College Drive Decorah, Iowa 52101  USA
        <br />Phone: 563-387-2000 or 800-4 LUTHER (800-458-8437)</p>'."\n";
                        echo '</div>'."\n";

			}
			else
			{
				
			echo '<div id="foot">'."\n";
    			echo '<ul><li><a href="/siteinfo/">About This Site</a></li>'."\n";
    			echo '<li><a href="/privacy/">Privacy Statement</a></li></ul>'."\n";

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
			if (!preg_match("/^localhost$/", REASON_HOST, $matches))
			{
				google_analytics();
			}
		}
	}
?>
