<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/luther.php' );
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'Luther2014SportsTemplate';
	
	class Luther2014SportsTemplate extends Luther2014Template
	{
		
		function alter_reason_page_type($page_type)
		{
			parent::alter_reason_page_type($page_type);

			// Adds the sports affiliations blurb to pre_foot on every page
			$page_type->set_region('pre_foot', 'blurb', 'blurb.php', array('blurb_unique_names_to_show' => 'luther_sports_affiliations'));
		}

		function do_org_head_items()
		{
			parent::do_org_head_items();

			// Adds custom sports stylesheet
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/sites/sports.css');
			if (preg_match('/sports\/(men|women)\/.+\/headlines/', get_current_url()))
			{
				$this->head_items->add_stylesheet('/reason/local/luther_2014/javascripts/vendor/jquery.cluetip.css');
			}
		}

		function do_org_foot()
		{
			parent::do_org_foot();
			if (preg_match('/sports\/(men|women)\/.+\/headlines/', get_current_url()))
			{
				echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/vendor/jquery.hoverIntent.min.js"></script>'."/n";
				echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/vendor/jquery.cluetip.min.js"></script>'."/n";
				echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/luther-cluetip.js"></script>'."/n";
			}
		}

		function get_body_tag_classes()
		{
			// Adds the class lutherSports to the body tag on all pages of the site
			$classes = array();
			$classes[] = 'body lutherSports';
			if($this->pages->root_node() == $this->page_id)
				$classes[] = 'body lutherSports siteHome';
			if($this->page_info->get_value('unique_name'))
				$classes[] = 'lutherSports uname_'.$this->page_info->get_value('unique_name');
			return $classes;
		}
		function you_are_here($delimiter = ' <span>&raquo;</span> ')
                {
                        echo '<div class="breadcrumbs">';
                        echo '<a href="/"><span class="screenreader">Home</span><i class="fa fa-home"></i></a> <span>&raquo;</span> ';
			$old_arr = $this->_get_breadcrumbs();
			$new_sub_arr = array();
			$new_arr = array(); 
			foreach ($old_arr as $val)
			{
				foreach ($val as $ky => $vl)
				{
					if ($ky == 'page_name')
					{
						if (preg_match('/(men|women)/', $vl))
						{
							$new_sub_arr['page_name'] = preg_replace ("/\([^)]+\)/","", $vl); 	
						}
						else 
						{
							$new_sub_arr['page_name'] = $vl;
						}
					}
					if ($ky == 'link')
					{
						$new_sub_arr['link'] = $vl;
					}
				}
				$new_arr[] = $new_sub_arr;

			}
                                echo $this->_get_breadcrumb_markup($new_arr, $this->site_info->get_value('base_breadcrumbs'), $delimiter);
	                        echo '</div>'."\n";
                }
	}
?>
