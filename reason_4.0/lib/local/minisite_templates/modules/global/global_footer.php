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
							<h3>Menu Heading</h3>
							<ul>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							</ul>
						</div>
						<div class="column">
							<h3>Menu Heading</h3>
							<ul>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							</ul>
						</div>
						<div class="column">
							<h3>Menu Heading</h3>
							<ul>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							</ul>
						</div>
						<div class="column">
							<h3>Menu Heading</h3>
							<ul>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							</ul>
						</div>
						<div class="column">
							<h3>Menu Heading</h3>
							<ul>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							</ul>
						</div>
						<div class="column">
							<h3>Menu Heading</h3>
							<ul>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
							<li><a href="#">Lorem ipsum</a></li>
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
						<p class="copyright">&copy; <?php echo date("Y"); ?> Luther College. All rights reserved.</p>
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