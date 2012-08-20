<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'function_libraries/images.php' );

	/**
	 * Theme choosing module for backend
	 */
	class ChooseThemeModule extends DefaultModule // {{{
	{
		/**
		 * Used to prevent redundant queries for current theme
		 * Use the get_current_theme function instead of accessing this variable
		 * @access private
		 */
		var $_current_theme = false;

		/**
		 * Standard constructor
		 * 
		 * @param AdminPage $page
		 * @return void
		 */
		function ChooseThemeModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * Standard Module init function
		 *
		 * Sets up page variables
		 * 
		 * @return void
		 */
		function init() // {{{
		{
			parent::init();
			$this->site = new entity( $this->admin_page->site_id );
			if( ALLOW_REASON_SITES_TO_SWITCH_THEMES && $this->site->get_value( 'allow_site_to_change_theme' ) == 'true' && reason_user_has_privs( $this->admin_page->user_id, 'switch_theme' ) )
			{
				$this->self_change = true;
				$this->admin_page->title = 'Select a Site Theme';
			}
			else
			{
				$this->self_change = false;
				$this->admin_page->title = 'Site Theme';
			}
		} // }}}
		/**
		 * Lists the current and available themes (if appropriate to do so) otherwise, changes the theme
		 * if the user has selected a new one
		 * 
		 * @return void
		 */
		function run() // {{{
		{
			if(!empty($this->admin_page->request[ 'chosen_theme' ] ) )
			{
				if( $this->self_change )
				{
					//create a relationship of type site_has_had_theme (if needed)
					$oldies = $this->get_previously_selected_themes();
					$site_type_themes = $this->get_site_type_themes(false);
					$e = $this->get_current_theme();

					if( $e )
					{
						if( !entity_in_array($oldies,$e->id()) AND !entity_in_array($site_type_themes, $e->id()))
							create_relationship( $this->admin_page->site_id ,
									     $e->id() ,
									     relationship_id_of( 'site_has_had_theme' ) );
					}
	
					//do relationship adding/deleting
					delete_relationships( array( 'entity_a' => $this->admin_page->site_id,
								     'type' => relationship_id_of( 'site_to_theme' ) )
							    );
					create_relationship( $this->admin_page->site_id ,
							     $this->admin_page->request[ 'chosen_theme' ] ,
							     relationship_id_of( 'site_to_theme' ) );
				}
				$link = $this->admin_page->make_link( array( 'cur_module' => 'ChooseTheme', 'chosen_theme' => '' ));
				header( 'Location: ' . unhtmlentities( $link ) );
			}
			else
			{
				$this->list_available_themes();
			}
			
		} // }}}
		/**
		 * Gets a list of themes associated with the site type of the current theme
		 * 
		 * @param boolean $omit_current defaults to true, set to false if you want to include the current theme as well
		 * @return array
		 */
		function get_site_type_themes($omit_current = true) //{{{
		{
			$current_theme = $this->get_current_theme();
			//get site types
			$e = new entity( $this->admin_page->site_id);
			$e->set_env( 'site' , $this->admin_page->site_id);
			$site_types = $e->get_left_relationship( 'site_to_site_type' );
			if(empty($site_types))
				return array();
			
			$site_type_ids = array();
			foreach( $site_types AS $st )
			{
				$site_type_ids[] = $st->id();
			}

			//get themeses
			$es = new entity_selector();
			$es->add_type( id_of( 'theme_type' ) );
			$es->add_right_relationship( $site_type_ids , relationship_id_of( 'site_type_to_theme' ) );
			if( $current_theme AND $omit_current)
				$es->add_relation( 'entity.id !=' . $current_theme->id() );
			$es->set_order('entity.name ASC');
			return $es->run_one();
		} // }}}

		/**
		 * Gets a list of previously selected themes
		 *
		 * @param mixed $themes if passed an array of theme entities, will omit these from the search
		 * @return array 
		 */
		function get_previously_selected_themes($themes = false) //{{{
		{
			$current_theme = $this->get_current_theme();
			//add previously selected themes
			$es = new entity_selector();
			$es->add_type( id_of( 'theme_type' ) );
			$es->add_right_relationship( $this->admin_page->site_id , relationship_id_of( 'site_has_had_theme' ) );
			
			//omit redundancy
			$es->add_relation( 'entity.id != ' . $current_theme->id());
			if( $themes )
				foreach( $themes AS $t)
					$es->add_relation( 'entity.id !=' . $t->id() );

			$es->set_order('entity.name ASC');
			return $es->run_one();

		} // }}}
		function list_available_themes() //{{{
		{
			$current_theme  = $this->get_current_theme();
			$themes = $this->get_site_type_themes();	
			$themes = array_merge( $themes , $this->get_previously_selected_themes($themes) );
			entity_sort( $themes , 'name' );


			//display themeses
			
			echo $this->generate_markup( $current_theme, $themes );
		} // }}}
		/**
		 * Generates the markup for the page if we aren't currently selecting an entity
		 *
		 * @return string HTML for page
		 */
		function generate_markup( $current_theme = NULL, $other_themes = NULL ) //{{{
		{
			$ret = '<div id="themeSelection">'."\n";
			if(!empty($current_theme))
			{
				$image_markup = $this->get_theme_image( $current_theme );
				$ret .= '<h4>Current Theme</h4>'."\n";
				$ret .= '<ul><li>';
				if(!empty($image_markup))
				{
					$ret .= '<div class="image">'.$image_markup.'</div>';
				}
				$ret .= '<div class="name"><strong>'.$current_theme->get_value( 'name' ) . '</strong></div>';
				if($this->theme_can_be_customized($current_theme))
				{
					$ret .= '<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'CustomizeTheme' ) ).'">Customize</a>'."\n";
				}
				$ret .= '</li></ul>'."\n";
			}
			if( !$this->self_change )
			{
				$ret .= '<br /><br />You are currently not allowed to change themes.  In most cases, this is because ';
				$ret .= 'your site has been given a custom theme.  If you have further questions about this, please contact ';
				$ret .= REASON_CONTACT_INFO_FOR_CHANGING_USER_PERMISSIONS . "\n";
			}
			elseif(!empty($other_themes))
			{
				if(!empty($current_theme))
				{
					$head_text = 'Other Available Themes';
				}
				else
				{
					$head_text = 'Available Themes';
				}
				
				$ret .= '<h4>'.$head_text.'</h4>'."\n";
				
				$ret .= '<ul>';
				foreach($other_themes AS $theme)
				{
					$image_markup = $this->get_theme_image( $theme );
					$link = $this->admin_page->make_link( array( 'chosen_theme' => $theme->id() ) );
					
					$ret .= '<li>';
					if(!empty($image_markup))
					{
						$ret .= '<div class="image">'.$image_markup.'</div>';
					}
					$ret .= '<div class="name"><strong>'.$theme->get_value( 'name' ) . '</strong></div>';
					$ret .= '<div class="action"><a href="'.$link.'" title="Select '.htmlspecialchars($theme->get_value( 'name' )).'">Select this theme</a></div>';
					$ret .= '</li>'."\n";
					
				}
				$ret .= '</ul>';
			}
			$ret .= '</div>'."\n";
			return $ret;
		} // }}}
	
		/**
		 * Returns the HTML for displaying a given theme's primary image
		 * 
		 * @param entity $theme the themes whose image we're selecting
		 * @return string HTML for image
		 */
		function get_theme_image( $theme ) //{{{
		{
			$image = $theme->get_left_relationship( 'theme_to_primary_image' );		
			$image = empty($image[0])? '':$image[0];
			
			if(!empty($image))
			{
				ob_start();
				show_image( $image, true, true, false );
				$ret = ob_get_contents();
				ob_end_clean();
				return $ret;
			}
			else
				return '';
		} // }}}
		/**
		 * Gets the current theme
		 * 
		 * Stores current theme in a class variable if it finds one in order to 
		 * avoid redundant queries
		 * 
		 * @return entity The current theme
		 */
		function get_current_theme()
		{
			if( $this->_current_theme )
				return $this->_current_theme;
			$es = new entity_selector();
			$es->add_type( id_of( 'theme_type' ) );
			$es->set_num(1);
			$es->add_right_relationship( $this->admin_page->site_id , relationship_id_of( 'site_to_theme' ) );
			$theme = $es->run_one();
			if( !empty( $theme ) )
			{
				$this->_current_theme = current($theme);
				return $this->_current_theme;
			}
			return false;
		}
		
		function theme_can_be_customized($theme)
		{
			$customizer = reason_get_theme_customizer($this->admin_page->site_id, $theme);
			if($customizer &&
				(	reason_user_has_privs( $this->admin_page->user_id, 'customize_all_themes' )
					||
					$customizer->user_can_customize($this->admin_page->user_id)
				)
			)
			{
				return true;
			}
			return false;
		}
	}
?>
