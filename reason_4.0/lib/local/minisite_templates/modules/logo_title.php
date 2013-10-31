<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LogoTitleModule';
	
	class LogoTitleModule extends DefaultMinisiteModule
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
			echo '<header class="global" role="banner">'."\n";
			echo '<h1 id="luther-logo"><a href="/" title="Luther College Home"><img alt="luther College" height="54" src="/images/luther2010/luther-college.png" width="289" /></a></h1>'."\n";
			echo '<h1 class="pageTitle"><span>'.$this->parent->title.'</span></h2>'."\n";  
			echo '</header>'."\n";
			//echo '<nav id="nav-content" role="navigation">'."\n";
			//luther2010_global_navigation();
			//echo '</nav>'."\n";
			return;
		}
	}
?>
