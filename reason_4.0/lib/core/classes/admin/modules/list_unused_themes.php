<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * List Unused Themes Module
	 * A module that lists themes that are not currently in use
	 * @author Ben White
	 */
	class ListUnusedThemesModule extends DefaultModule
	{
		var $unused_themes = array();
		var $unused_by_live_themes = array();
		var $unused_templates = array();
		var $unused_by_live_templates = array();
		var $unused_css = array();
		var $unused_by_live_css = array();
		
		function ViewUsersModule( &$page )
		{
			$this->admin_page =& $page;
		}

		function get_unused_themes_array()
		{
			if(empty($this->unused_themes)||empty($this->unused_by_live_themes))
			{
				$es = new entity_selector();
				$es->add_type(id_of('theme_type'));
				$themes = $es->run_one();
				foreach($themes as $theme_id => $theme)
				{
					$sites_using_theme = ($theme->get_right_relationship('site_to_theme'));
					if(empty($sites_using_theme))
					{
						$this->unused_themes[$theme_id]=$theme;
					}
					else
					{
						foreach($sites_using_theme as $site_id => $site)
						{
							if($site->get_value('site_state')=='Not Live')
							{
								unset($sites_using_theme[$site_id]);
							}
						}
						if(empty($sites_using_theme))
						{
							$this->unused_by_live_themes[$theme_id]=$theme;
						}
					}
				}
			}
			return array('unused'=>$this->unused_themes,'not_live'=>$this->unused_by_live_themes);
		}

		function get_unused_templates_array()
		{
			if(empty($this->unused_templates)||empty($this->unused_by_live_templates))
			{
				$es = new entity_selector();
				$es->add_type(id_of('minisite_template'));
				$templates = $es->run_one();
				$unused_themes_array = $this->get_unused_themes_array();
				$unused_themes=$unused_themes_array['unused'];
				$unused_by_live_themes=$unused_themes_array['not_live'];	
				foreach($templates as $template_id => $template)
				{
				        $themes_using_templates = ($template->get_right_relationship('theme_to_minisite_template'));	
					if(empty($themes_using_templates))
       					{
        				        $this->unused_templates[$template_id]=$template;
				        }
				        else
				        {
				                foreach($themes_using_templates as $theme_id => $theme)
				                {
				                        if(!empty($unused_themes[$theme->id()]))
				                        {
				                                unset($themes_using_templates[$theme_id]);
				                        }
				                }
				                if(empty($themes_using_templates))
				                {
				                        $this->unused_templates[$template_id]=$template;
				                }
						else
						{
							foreach($themes_using_templates as $theme_id => $theme)
				                	{
				                        	if(!empty($unused_by_live_themes[$theme->id()]))
				                        	{
				                                	unset($themes_using_templates[$theme_id]);
				                       		}
				                	}
        	        				if(empty($themes_using_templates))
				                	{
				                	        $this->unused_by_live_templates[$template_id]=$template;
				                	}
						}
				        }
				}	
			}
			return array('unused'=>$this->unused_templates,'not_live'=>$this->unused_by_live_templates);
		}
		
		function get_unused_css_array()
		{
			if(empty($this->unused_css)||empty($this->unused_by_live_css))
			{
				$es = new entity_selector();
				$es->add_type(id_of('css'));
				$css = $es->run_one();
				$unused_themes_array = $this->get_unused_themes_array();
				$unused_themes=$unused_themes_array['unused'];
				$unused_by_live_themes=$unused_themes_array['not_live'];	
				foreach($css as $css_id => $css_individual)
				{
			 	       $themes_using_css = ($css_individual->get_right_relationship('theme_to_external_css_url'));
					if(empty($themes_using_css))
				        {
				                $this->unused_css[$css_id]=$css_individual;
				        }
				        else
				        {					
				                foreach($themes_using_css as $theme_id => $theme)	
						{	
							if(!empty($unused_themes[$theme->id()]))
        				                {
        				                        unset($themes_using_css[$theme_id]);
        				                }
        				        }
        				        if(empty($themes_using_css))
        				        {
        				                $this->unused_css[$css_id]=$css_individual;
        				        }
        				        else
        				        {
        				                foreach($themes_using_css as $theme_id => $theme)
        				                {
        				                        if(!empty($unused_by_live_themes[$theme->id()]))
        				                        {
        				                                unset($themes_using_css[$theme_id]);
        				                        }
        				                }
        				                if(empty($themes_using_css))
        				                {
        				                        $this->unused_by_live_css[$css_id]=$css_individual;
        				                }
        				        }
        				}
				}
			}
			return array('unused'=>$this->unused_css,'not_live'=>$this->unused_by_live_css);
		}

		/**
		 * Standard Module init function
		 *
		 * Sets up the entity selectors and grabs the site lists
		 * 
		 * @return void
		 */
		function init()
		{
			parent::init();
			$this->site = new entity( $this->admin_page->site_id );
			$this->admin_page->title = 'Unused Theme Listing';
		}
		/**
		 * Lists the sites, the non-live list depending on admin role
		 * 
		 * @return void
		 */
		function run()
		{
			$unused_themes_array = $this->get_unused_themes_array();
			$unused_templates_array = $this->get_unused_templates_array();
			$unused_css_array = $this->get_unused_css_array();
			echo '<h3>Unused by any site</h3>'."\n";
			echo '<h4>Themes:</h4>'."\n";
			echo '<ul>';
			foreach($unused_themes_array['unused'] as $theme_id=>$theme)
			{
				echo '<li>'.$theme->get_value('name').' <a href="'.carl_make_link(array('site_id'=>id_of('master_admin'),'type_id'=>id_of('theme_type'),'id'=>$theme->id(),'cur_module'=>'Editor')).'">Edit</a></li>'."\n";
			}
			echo '</ul>';
			echo '<h4>Templates:</h4>'."\n";
			echo '<ul>';
			foreach($unused_templates_array['unused'] as $template_id=>$template)
			{
				echo '<li>'.$template->get_value('name').' <a href="'.carl_make_link(array('site_id'=>id_of('master_admin'),'type_id'=>id_of('minisite_template'),'id'=>$template->id(),'cur_module'=>'Editor')).'">Edit</a></li>'."\n";
			}
			echo '</ul>';
			echo '<h4>External CSS:</h4>'."\n";
			echo '<ul>';
			foreach($unused_css_array['unused'] as $css_id=>$css)
			{
				echo '<li>'.$css->get_value('name').' <a href="'.carl_make_link(array('site_id'=>id_of('master_admin'),'type_id'=>id_of('css'),'id'=>$css->id(),'cur_module'=>'Editor')).'">Edit</a></li>'."\n";
			}	
			echo '</ul>';		
			echo '<h3>Used only by non-live sites</h3>'."\n";
			echo '<h4>Themes:</h4>'."\n";
			echo '<ul>';
			foreach($unused_themes_array['not_live'] as $theme_id=>$theme)
			{
				echo '<li>'.$theme->get_value('name').' <a href="'.carl_make_link(array('site_id'=>id_of('master_admin'),'type_id'=>id_of('theme_type'),'id'=>$theme->id(),'cur_module'=>'Editor')).'">Edit</a></li>'."\n";
			}
			echo '</ul>';
			echo '<h4>Templates:</h4>'."\n";
			echo '<ul>';
			foreach($unused_templates_array['not_live'] as $template_id=>$template)
			{
				echo '<li>'.$template->get_value('name').' <a href="'.carl_make_link(array('site_id'=>id_of('master_admin'),'type_id'=>id_of('minisite_template'),'id'=>$template->id(),'cur_module'=>'Editor')).'">Edit</a></li>'."\n";
			}
			echo '</ul>';
			echo '<h4>External CSS:</h4>'."\n";
			echo '<ul>';
			foreach($unused_css_array['not_live'] as $css_id=>$css)
			{
				echo '<li>'.$css->get_value('name').' <a href="'.carl_make_link(array('site_id'=>id_of('master_admin'),'type_id'=>id_of('css'),'id'=>$css->id(),'cur_module'=>'Editor')).'">Edit</a></li>'."\n";
			}
			echo '</ul>';	
		}
			
	}
?>
