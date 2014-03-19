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
				<h1 class="screenreader">Luther College Site Navigation</h1>
				
				<ul class="audiences">
					<li><a href="#">Parents</a></li>
					<li><a href="#">Alumni &amp; Friends</a></li>
					<li><a href="#">Faculty &amp; Staff</a></li>
					<li><a href="#">Students</a></li>
				</ul>
				
				<ul class="sections">
					<li>
						<a href="#">Admissions</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#">Academics</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#">Student Life</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#">Athletics</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#">Music</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#">Giving</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#">About</a>
						<ul>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
							<li><a href="#">Dolor lorem</a></li>
							<li><a href="#">Lorem ipsum dolor</a></li>
						</ul>
					</li>
					<li>
						<a href="#">Connect</a>
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