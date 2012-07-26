<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherUsernameModule';
	
	class LutherUsernameModule extends DefaultMinisiteModule
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
    			echo '<ul><li><a href="http://www.luther.edu/about.aspx">About This Site</a></li>'."\n";
    			echo '<li><a href="http://www.luther.edu/privacy.aspx">Privacy Statement</a></li></ul>'."\n";

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
	}
?>
