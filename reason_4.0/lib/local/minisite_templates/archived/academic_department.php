<?php

/**
 * A sample Reason template, with minimal overloading of methods
 */
 
/**
 * Include the base template so we can extend it
 */
reason_include_once( 'minisite_templates/default.php' );
include_once('/usr/local/webapps/reason/reason_package_local/local/minisite_templates/luther.php');

/**
 * Register this new template with Reason
 */
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'AcademicDepartmentTemplate';

class AcademicDepartmentTemplate extends LutherTemplate
{
	// reorder sections so that navigation is first instead of last
	var $sections = array('navigation'=>'show_navbar','content'=>'show_main_content','related'=>'show_sidebar');
	public $luther_add_this_complete = false;

	function show_main_content_sections()
        {
        $home_url = $this->site_info->get_value('base_url');
		echo '<div class="column span-50 prepend-1 last">'."\n";

                $this->run_section( 'imagetop' );
                $home_url = $this->site_info->get_value('base_url');
                echo '<div class="subImageTopTabs">'."\n";
                echo '<ul>'."\n";
				echo '<li class="first">'."\n";
				echo '<a href="'.$home_url.'requirements/">Requirements</a>'."\n";
				echo '</li>'."\n";
				echo '<li>'."\n";
				echo '<a href="'.$home_url.'courses/">Courses</a>'."\n";
				echo '</li>'."\n";
				echo '<li>'."\n";
				echo '<a href="'.$home_url.'faculty/">Faculty</a>'."\n";
				echo '</li>'."\n";
				echo '<li>'."\n";
				echo '<a href="'.$home_url.'facilities/">Facilities</a>'."\n";
				echo '</li>'."\n";
				echo '<li class="last">'."\n";
				echo '<a href="'.$home_url.'careers/">Careers</a>'."\n";
				echo '</li>'."\n";
				echo '</ul>'."\n";
                echo '</div class="subImageTopTabs">'."\n";
		$this->luther_breadcrumbs();

                if ($this->has_content( 'main_head' ))
                {
                		$this->run_section( 'main_head' );
                        echo '</div>'."\n";
                }

		if ($this->has_related_section() && $this->cur_page->get_value( 'custom_page' ) != 'luther_pageLC' && $this->cur_page->get_value( 'custom_page' ) != 'spotlight_archive' && $this->cur_page->get_value( 'custom_page' ) != 'luther_publication')
		{
			echo '<div class="column span-33 append-1">'."\n";
		}
		// in two column layout place images just before the main content
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther_pageLC' && $this->has_content( 'sidebar' ))
                {
               //         echo '<div id="sidebar">'."\n";
                        $this->run_section( 'sidebar' );
                //        echo '</div>'."\n";
                }

                if ($this->has_content( 'main' ))
                {
                        echo '<div class="contentMain">'."\n";
                        $this->run_section( 'main' );
			if (!$this->luther_add_this_complete)
			{
				$this->luther_add_this();
			}
                        echo '</div>'."\n";
                }
                if ($this->has_content( 'main_post' ))
                {
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther_primaryLRC')
			{
				echo '<p><b>Luther College News</b></p>'."\n";
			}
                        echo '<div class="contentPost">'."\n";
                        $this->run_section( 'main_post' );
			if (!$this->luther_add_this_complete)
			{
				$this->luther_add_this();
			}
                        echo '</div>'."\n";
                }


		if ($this->cur_page->get_value( 'custom_page' ) == 'luther_pageLC')
		{
			echo '</div> <!-- class="column span..."-->'."\n";
		}
		// rough-in right column if there is no content
		if ($this->has_related_section() == false) {
			$this->show_sidebar_tableless();	
		}
        }
}

?>
