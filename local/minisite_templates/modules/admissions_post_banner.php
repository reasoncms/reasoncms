<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AdmissionsPostBannerModule';
	
	class AdmissionsPostBannerModule extends DefaultMinisiteModule
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
                        	echo '<div class="banner">'."\n";
                        	echo '<ul class="nav picnav">'."\n";
                        	echo '<li id="photoTour"><a href="http://www.luther.edu/about/campus/tour">Photo Tour</a></li>'."\n";
                        	echo '<li id="visitLuther"><a href="#">Visit Luther</a></li>'."\n";
                        	echo '<li id="getInfo"><a href="#">Get Info</a></li>'."\n";
                        	echo '<li id="applyNow"><a href="#">Apply Now</a></li>'."\n";
                        	echo '</ul>'."\n";

                        	echo '<div class="info">'."\n";
                        	echo '<h2>Information for&hellip;</h2>'."\n";
                        	echo '<div class="infonav">'."\n";
                        	echo '<ul class="nav rowOne hasThree clearfix">'."\n";
                        	echo '<li class="applicants"><a href="#">Applicants</a></li>'."\n";
                        	echo '<li class="acceptedStudents"><a href="#">Accepted Students</a></li>'."\n";
                        	echo '<li class="parents"><a href="#">Parents</a></li>'."\n";
                        	echo '</ul>'."\n";
                        	echo '<ul class="nav rowTwo hasTwo clearfix">'."\n";
                        	echo '<li class="transferStudents"><a href="#">Transfer Students</a></li>'."\n";
                        	echo '<li class="internationalStudents"><a href="#">International Students</a></li>'."\n";
                        	echo '</ul>'."\n";
                        	echo '</div>'."\n";
                        	echo '</div>'."\n";
                        	echo '<div class="images">'."\n";
                        	echo '<div class="row1">'."\n";
                        	echo '<img src="/images/admissions/1.jpg" class="wide" />'."\n";
                        	echo '<img src="/images/admissions/2.jpg" />'."\n";
                        	echo '</div>'."\n";
                        	echo '<div class="row2">'."\n";
                        	echo '<img src="/images/admissions/3.jpg" />'."\n";
                        	echo '<img src="/images/admissions/4.jpg" class="wide" />'."\n";
                        	echo '</div>'."\n";
                        	echo '<div class="row3">'."\n";
                        	echo '<img src="/images/admissions/5.jpg" class="wide" />'."\n";
                        	echo '<img src="/images/admissions/6.jpg" />'."\n";
                        	echo '</div>'."\n";
                        	echo '</div>'."\n";
                        	echo '</div>'."\n";
                        	echo '</div>'."\n";
			}

			return;
		}
	}
?>
