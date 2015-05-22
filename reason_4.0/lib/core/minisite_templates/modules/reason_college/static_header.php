<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'reason_college/'.basename( __FILE__, '.php' ) ] = 'staticHeaderModule';
	
	class staticHeaderModule extends DefaultMinisiteModule
	{
		
		function run()
		{
		
			?>

			<div id="cloakHeader">
				<div id="cloakMasthead">
					<h1 id="globalLogo">
						<a href="/">
							<span>Reason College</span>
						</a>
					</h1>
					<ul id="globalNavigationToggles">
						<li class="globalNavToggle">
							<a href="#globalNav" id="globalNavToggle">
								<span class="menuJumpText">Jump to global navigation</span>
							</a>
						</li>
						<li class="utilityNavToggle">
							<a href="#utilityNav" id="utilityNavToggle">
								<span class="utilityJumpText">Jump to search utility navigation</span>
							</a>
						</li>
					</ul>
				</div>
				<nav id="utilityNav" class="closed">
					<ul>
						<li class="search">
							<a href="#">Search</a>
							<form method="" action="" name="globalSearch" class="globalSearchForm open">
								<input type="text" name="" placeholder="Search Reason College" class="searchInputBox" />
								<input type="submit" name="" value="Search" class="searchSubmitLink" />
							</form>
						</li>
						<li class="directory"><a href="#">Directory</a></li>
						<li class="az"><a href="#">A to Z Index</a></li>
					</ul>
				</nav>
				<div id="globalNav" class="closed">
					<nav id="audienceNav">
						<ul>
							<li><a href="#">Prospective Students</a></li>
							<li><a href="#">Alumni</a></li>
							<li><a href="#">Current Students</a></li>
							<li><a href="#">Parents</a></li>
							<li><a href="#">Faculty &amp; Staff</a></li>
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
			</div>
			
			<?php
		}
	}
?>