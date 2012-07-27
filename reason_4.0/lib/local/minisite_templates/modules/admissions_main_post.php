<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AdmissionsMainPostModule';
	
	class AdmissionsMainPostModule extends DefaultMinisiteModule
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
                       	echo '<div class="content clearfix">'."\n";
                       	echo '<div class="highlight clearfix">'."\n";
                        if ($this->cur_page->get_value( 'custom_page' ) == 'admissions_home')
			{
                        	echo '<div class="highlightItem">'."\n";
                        	echo '<img src="/images/admissions/baker-village.jpg" />'."\n";
                        	echo '<div class="text">'."\n";
                        	echo '<blockquote><p><span class="openingQuote">&#8216;&#8216;</span>Living in Baker Village means doing what it takes to live a green life.&#8221;</p></blockquote>'."\n";
                        	echo '<p class="cite">John Thomas Mayer, &#8217;08</p>'."\n";
                        	echo '</div>'."\n";
                        	echo '</div>'."\n";
                        	//echo '<div class="highlightItem">'."\n";
                        	//echo '<img src="/images/admissions/softball.jpg" />'."\n";
                        	//echo '<div class="text">'."\n";
                        	//echo '<p>Luther offers 19 varsity sports and has won more than 200 conference titles.</p>'."\n";
                        	//echo '</div>'."\n";
                        	//echo '</div>'."\n";
			}
                       	//echo '</div class="highlight clearfix">'."\n";


			return;
		}
	}
?>
