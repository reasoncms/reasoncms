<?php

   /* 
	*  CLOAK Branded TEMPLATE
	*
	*   CloakBrandedTemplate (/lib/local/minisite_templates/cloak_branded.php)...
	*  	  extends CloakTemplate (/lib/local/minisite_templates/cloak.php)...
	*     which extends HTML5ResponsiveTemplate (/lib/core/minisite_templates/html5_responsive.php)...
	*     which extends DefaultTemplate (/lib/core/minisite_templates/default.php)
	*  
	*  To extend a function without duplicating the parent's code, use parent::functionName();
	*/

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/cloak.php' );
	//reason_include_once('classes/module_sets.php');
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__)  ] = 'CloakBrandedTemplate';
	
	class CloakBrandedTemplate extends CloakTemplate
	{

		function alter_reason_page_type($page_type)
		{
			parent::alter_reason_page_type($page_type);

			// $page_type->set_region('pre_foot', 'blurb', 'blurb.php', array('blurb_unique_names_to_show' => 'luther_sports_affiliations'));
			// $page_type->set_region('page_location', 'module', 'module_file_name.php', array('module_parameter' => 'paremeter_value'));
			// $page_type->set_region('pre_banner', 'global_header', 'global_header.php', array());
			// $page_type->set_region('pre_bluebar', 'page_title', 'page_title.php', array());

		}

		// Adds show_cloak_branded_header()
		// function show_body_tableless()
		// {
		// 	$class = 'fullGraphicsView';
		// 	echo '<div id="wrapper" class="'.$class.'">'."\n";
		// 	//$this->show_cloak_branded_header();
		// 	echo '<div id="bannerAndMeat">'."\n";
		// 	$this->show_banner();
		// 	$this->cloak_you_are_here();
		// 	//$this->cloak_show_main_head();
		// 	$this->show_meat();
		// 	echo '</div>'."\n";
		// 	$this->show_footer();
		// 	echo '</div>'."\n";
		// }
}
?>