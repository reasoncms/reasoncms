<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'global/'.basename( __FILE__, '.php' ) ] = 'GlobalFooter';
	
	class GlobalFooter extends DefaultMinisiteModule
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
		 	?>
			<footer id="global-footer">
				<div id="footerInner">
					<h2 class="screenreader">Complimentary Navigation</h2>
					
					<div id="global-footer-nav" role="navigation">
						<div class="column">
							<h3>Contact</h3>
							<ul>
							<li>Luther College</li>
							<li>700 College Dr.</li>
							<li>Decorah, IA 52101</li>
							<li><a href="tel:1-563-387-2000">563-387-2000</a></li>
							<li><a href="tel:1-800-458-8437">800-458-8437 (toll-free)</a></li>
							</ul>
						</div>
						<div class="column">
							<h3>Academics</h3>
							<ul>
							<li><a href="/registrar/calendar/">Academic Calendar</a></li>
							<li><a href="/academics/departments/">Academic Departments</a></li>
							<li><a href="/careers/">Career Center</a></li>
							<li><a href="/global-learning/">Global Learning</a></li>
							<li><a href="/lis/">Library &amp; Technology</a></li>
							</ul>
						</div>
						<div class="column">
							<h3>Campus</h3>
							<ul>
							<li><a href="/bookshop/">Book Store</a></li>
							<li><a href="/ministries/">College Ministries</a></li>
							<li><a href="/dining/">Dining Services</a></li>
							<li><a href="/shuttle/">Shuttle Service</a></li>
							<li><a href="https://tickets.luther.edu/Online/default.asp">Ticket Office</a></li>
							</ul>
						</div>
						<div class="column">
							<h3>Offices</h3>
							<ul>
							<li><a href="/marketing/">Communications</a></li>
							<li><a href="/financialaid/">Financial Aid</a></li>
							<li><a href="/hr/">Human Resources</a></li>
							<li><a href="/president/">President</a></li>
							<li><a href="/registrar/">Registrar’s Office</a></li>
							</ul>
						</div>
						<div class="column">
							<h3>Quick Links</h3>
							<ul>
							<li><a href="http://mail.luther.edu">Norse Mail</a></li>
							<li><a href="http://calendar.luther.edu">Norse Calendar</a></li>
							<li><a href="https://www.luther.edu/helpdesk/norseapps/">Norse Apps</a></li>
							<li><a href="https://katie.luther.edu/">KATIE</a></li>
							<li><a href="https://my.luther.edu/">my.luther.edu</a></li>
							</ul>
						</div>
						<div class="column">
							<h3>Connect</h3>
							<ul>
							<li><a href="/connect/">All Social Media</a></li>
							<li><a href="https://www.facebook.com/luthercollege" target="_blank">Facebook</a></li>
							<li><a href="http://instagram.com/luthercollege#" target="_blank">Instagram</a></li>
							<li><a href="https://twitter.com/luthercollege" target="_blank">Twitter</a></li>
							<li><a href="https://www.youtube.com/user/LutherCollegeMedia" target="_blank">YouTube</a></li>
							</ul>
						</div>
					</div>
					
					<div id="global-footer-info">	
					
						<div class="utility-nav" role="navigation">
							<ul>
								<li><a href="http://emergency.luther.edu"><span>Emergency Info</span></a></li>
								<li><a href="/privacy/"><span>Privacy Statement</span></a></li>
								<li><a href="/contact/"><span>Contact</span></a></li>
								<li><a href="/reportproblem/"><span>Report a Problem</span></a></li>
							</ul>
						</div>
						
						<p class="contact">
							<span class="adr">
								<span class="">Luther College</span> <span class="sep">&bull;</span>
								<span class="street-address">700 College Drive</span> <span class="sep">&bull;</span>
								<span class="locality">Decorah,</span>
								<span class="region">Iowa</span>
								<span class="postal-code">52101</span> <span class="sep">&bull;</span>
								<span class="country-name">USA</span>
							</span>
							<span class="phone">563-387-2000 or 800-4 LUTHER (<span class="tel">800-458-8437</span>)</span>
						</p>
					</div>
					
					<div id="post-footer">
						<p class="copyright">&copy; <?php echo date("Y"); ?> <a href="/">Luther College</a>. All rights reserved.</p>
						<p class="powered-by">Powered by <a href="#">Reason CMS</a>.</p>
					</div>

				</div>
					
				<!-- close the off-canvas menu -->
 				<a class="exit-off-canvas"></a>
				
			</footer>
			
			<?php
		}
	}
?>