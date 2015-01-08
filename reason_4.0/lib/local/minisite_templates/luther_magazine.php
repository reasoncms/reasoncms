<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/luther.php' );
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'Luther2014MagazineTemplate';
	
	class Luther2014MagazineTemplate extends Luther2014Template
	{

		function alter_reason_page_type($page_type)
		{
			parent::alter_reason_page_type($page_type);

			// Adds the magazine footer to pre_foot on every page
			$page_type->set_region('pre_foot', 'blurb', 'blurb.php', array('blurb_unique_names_to_show' => 'magazine_footer_links'));
		
		}
		
		function do_org_head_items()
		{
			parent::do_org_head_items();
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/sites/magazine.css');
		}
		
		function get_body_tag_classes()
		{
			$classes = array();
			$classes[] = 'body lutherMagazine';
			if($this->pages->root_node() == $this->page_id)
				$classes[] = 'siteHome';
			if($this->page_info->get_value('unique_name'))
				$classes[] = 'uname_'.$this->page_info->get_value('unique_name');
			return $classes;
		}	

		function you_are_here()
		{
		}

		// function show_navbar()
		// {
		// 	$wrapperClasses = array();
		// 	if ($this->has_content( 'navigation' )) {
		// 		$wrapperClasses[] = 'hasNav';
		// 	}
		// 	if ($this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) || $this->has_content( 'sub_nav_3' ) || $this->has_content( 'contact_blurb' ) )
		// 	{
		// 		$wrapperClasses[] = 'hasSubNav';
		// 	}
		// 	if(!empty($wrapperClasses))
		// 	{
		// 		echo '<nav id="navWrap" class="'.implode(' ',$wrapperClasses).'" data-magellan-expedition="fixed">'."\n";
		// 		echo '<a class="toggle" href="#minisiteNavigation">'."\n";
		// 		echo '<h1><span class="screenreader">' . $this->site_info->get_value('name') . '</span> <span class="helper-text">Menu <i class="fa fa-arrow-right"></i></span></h1>'."\n";
		// 		echo '<i class="fa fa-bars"></i>'."\n";
		// 		echo '<i class="fa fa-times"></i>'."\n";
		// 		echo '</a>'."\n";
		// 	}

		// 	if ($this->has_content( 'navigation' ))
		// 	{
		// 		$this->run_section( 'navigation' );
		// 	}

		// 	if ($this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) || $this->has_content( 'sub_nav_3' ) || $this->has_content( 'contact_blurb' ) )
		// 	{
		// 		echo '<div class="subNavElements">'."\n";
		// 		if ($this->has_content( 'sub_nav' )) 
		// 		{ 
		// 			echo '<aside id="subNav" class="subNavBlock" role="complementary">'."\n";
		// 			$this->run_section( 'sub_nav' );
		// 			echo '</aside>'."\n";
		// 		}

		// 		$this->show_luther_contact_blurb();

		// 		if ($this->has_content( 'sub_nav_2' ))
		// 		{
		// 			echo '<aside id="subNav2" class="subNavBlock" role="complementary">'."\n";
		// 			$this->run_section( 'sub_nav_2' );
		// 			echo '</aside>'."\n";
		// 		}
		// 		if ($this->has_content( 'sub_nav_3' ))
		// 		{
		// 			echo '<aside id="subNav3" class="subNavBlock" role="complementary">'."\n";
		// 			$this->run_section( 'sub_nav_3' );
		// 			echo '</aside>'."\n";
		// 		}
		// 		echo '</div>'."\n";
		// 	}
		// 	if(!empty($wrapperClasses))
		// 		echo '</nav>'."\n";
		// }

		// function show_luther_global_navigation()
		// {
		// 	parent::show_luther_global_navigation();
		// 	echo '<div class="magellan-container fixed" data-magellan-expedition="fixed"> <dl class="sub-nav"> <dd><a href="#build">Build with HTML</a></dd> <dd><a href="#js">Arrival 2</a></dd> </dl> </div>';
		// }

		function do_org_foot()
		{
			parent::do_org_foot();
			// Isotope
			//echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/foundation/foundation.magellan.js"></script>';
			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/vendor/imagesloaded.pkgd.min.js"></script>';
			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/vendor/isotope.pkgd.min.js"></script>';
			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/sites/magazine.js"></script>';
			//echo '<script> $(document).foundation ({magellan : {threshold: 0} }); </script>';
		}
	}
?>