<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'cloak/'.basename( __FILE__, '.php' ) ] = 'GlobalHeader';
	
	class GlobalHeader extends DefaultMinisiteModule
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
			<div id="cloakHeader">
				<h1 id="globalLogo">
					<a href="#">
						<span>Reason College</span>
					</a>
				</h1>
				<nav id="utilityNav">
					<ul id="audienceNav">
						<li><a href="#">Prospective Students</a></li>
						<li><a href="#">Alumni</a></li>
						<li><a href="#">Current Students</a></li>
						<li><a href="#">Parents</a></li>
						<li><a href="#">Faculty &amp; Staff</a></li>
					</ul>
					<ul id="searchNav">
						<li><a href="#">Search</a></li>
						<li><a href="#">Directory</a></li>
						<li><a href="#">A to Z Index</a></li>
					</ul>
				</nav>
				<nav id="sectionNav">
					<ul>
						<li><a href="#">Admissions</a></li>
						<li><a href="#">Academics</a></li>
						<li><a href="#">Student Life</a></li>
						<li><a href="#">Athletics</a></li>
						<li><a href="#">Giving</a></li>
						<li><a href="#">About</a></li>
					</ul>
				</nav>
			</div>
			
			<?php
		}
	}
?>