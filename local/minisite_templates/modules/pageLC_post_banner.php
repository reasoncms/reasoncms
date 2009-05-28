<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PageLCPostBannerModule';
	
	class PageLCPostBannerModule extends DefaultMinisiteModule
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

            </div class="column span-60 last">
          </div id="logosearch" class="container">
          <div class="column span-77 last">

            <ul id="navglobal"><li class="ng1"><a href="/academics">Academics</a></li><li class="ng2"><a href="/admissions">Admissions</a></li><li class="ng3"><a href="/student-life">Student Life</a></li><li class="ng4"><a href="/news">News & events</a></li><li class="ng5"><a href="/giving">Giving</a></li><li class="ng6"><a href="/about">About Luther</a></li><li class="ng7"><a href="/contact">Contact</a></li></ul>
          </div class="column span-77 last">
    </div id="head">
	
		<?php
		}
	}
?>
