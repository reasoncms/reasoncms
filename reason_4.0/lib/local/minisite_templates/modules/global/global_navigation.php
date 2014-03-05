<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'global/'.basename( __FILE__, '.php' ) ] = 'GlobalNavigation';
	
	class GlobalNavigation extends DefaultMinisiteModule
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
	
			<nav id="global-nav" class="left-off-canvas-menu" role="navigation">
				<h1 class="screenreader">Main Navigation</h1>
				
				<ul class="audiences">
					<li><a href="#">Parents</a></li>
					<li><a href="#">Alumni &amp; Friends</a></li>
					<li><a href="#">Faculty &amp; Staff</a></li>
					<li><a href="#">Students</a></li>
				</ul>
				
				<ul class="sections">
					<li>
						<a href="#"><i class="fa fa-user fa-fw"></i>Admissions</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#"><i class="fa fa-book fa-fw"></i>Academics</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li><a href="#">
						<i class="fa fa-users fa-fw"></i>Student Life</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#"><i class="fa fa-trophy fa-fw"></i>Athletics</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#"><i class="fa fa-music fa-fw"></i>Music</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#"><i class="fa fa-rocket fa-fw"></i>Giving</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<!--<li>
						<a href="#"><i class="fa fa-map-marker fa-fw"></i>Decorah</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>-->
					<li>
						<a href="#"><i class="fa fa-search fa-fw"></i>About</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#"><i class="fa fa-twitter fa-fw"></i>Connect</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
				</ul>	
			</nav>	
		
		
		<?php 

		}
	}
?>