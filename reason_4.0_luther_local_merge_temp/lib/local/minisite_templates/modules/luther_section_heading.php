<?php
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherSectionHeadModule';
	
	class LutherSectionHeadModule extends DefaultMinisiteModule
	// writes out name of minisite to be used as a reference on all pages within minisite
	{
		function run()
		{
			$bc = $this->parent->_get_breadcrumbs();

			$sbtitle = $bc[0]["page_name"];
			$sbtitle = preg_replace("|(^.*?)\s\((w?o?m?en)\)$|", "\\2's \\1", $sbtitle);
			$sblink = $bc[0]["link"];
			echo '<a class="blue" href="' . $sblink . '" id="section-sign">'."\n";
			echo '<div><header><h2>' . $sbtitle . '</h2></header></div></a>'."\n";
		}

		function has_content()
		{
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home')
			{
				return false;
			}
			else 
			{
				return true;
			}
		} 
	}
?>
	