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
					<li><a href="/alumni">Alumni &amp; Friends</a></li>
					<li><a href="/faculty-staff">Faculty &amp; Staff</a></li>
					<li><a href="/students">Students</a></li>
					<li><a href="/parents">Parents</a></li>
				</ul>
				
				<ul class="sections">
					<li>
						<a href="/admissions">Admissions</a>
					</li>
					<li>
						<a href="/academics">Academics</a>
					</li>
					<li>
						<a href="/studentlife/">Student Life</a>
					</li>
					<li>
						<a href="/sports">Athletics</a>
					</li>
					<li>
						<a href="/music">Music</a>
					</li>
					<li>
						<a href="/outcomes">Outcomes</a>
					</li>
					<li>
						<a href="/giving">Giving</a>
					</li>
					<li>
						<a href="/about">About</a>
					</li>
				</ul>	
			</nav>	
		
		
		<?php 

		}
	}
?>