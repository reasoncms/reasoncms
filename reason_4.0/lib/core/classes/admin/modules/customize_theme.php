<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once(DISCO_INC.'disco.php');
	
	/**
	 * The administrative module that produces the UI for customizing themes
	 *
	 * To create a theme customizer, add a class to the theme_customizers directory that implements
	 * the reasonThemeCustomizerInterface, similar to theme_customizers/example.php. Specify the
	 * name of this file on the theme in the Reason web interface, and 
	 *
	 * @author Matt Ryan
	 */
	class CustomizeThemeModule extends DefaultModule
	{
		protected $theme;
		protected $site;
		protected $form;
		protected $customizer;
		
		function CustomizeThemeModule( &$page )
		{
			$this->admin_page =& $page;
		}
		
		function get_site()
		{
			if(!isset($this->site))
			{
				$this->site = new entity($this->admin_page->site_id);
			}
			return $this->site;
		}
		
		function get_theme()
		{
			if(!isset($this->theme))
			{
				$site = $this->get_site();
				$es = new entity_selector();
				$es->add_type( id_of( 'theme_type' ) );
				$es->add_right_relationship( $site->id() , relationship_id_of( 'site_to_theme' ) );
				$es->set_num(1);
				$tmp = $es->run_one();
				if(!empty($tmp))
					$this->theme = current( $tmp );
				else
					$this->theme = false;
			}
			return $this->theme;
		}
		
		function get_form()
		{
			if(!isset($this->form))
			{
				$site = $this->get_site();
				
				$this->form = new Disco();
				
				$this->form->add_element('site_id','hidden');
				$this->form->set_value('site_id',$site->id());
				
				$this->form->add_element('cur_module','hidden');
				$this->form->set_value('cur_module','CustomizeTheme');
				
				$this->form->add_callback(array($this,'save_customization_data'),'process');
				
				$this->form->add_callback(array($this,'form_where_to'),'where_to');
				
				if($customizer = $this->get_customizer())
				{
					$customizer->modify_form($this->form);
				}
			}
			return $this->form;
		}
		
		function get_customizer()
		{
			if(!isset($this->customizer))
			{
				$this->customizer = reason_get_theme_customizer($this->get_site(), $this->get_theme());
			}
			return $this->customizer;
		}
		
		function get_all_customization_data($site)
		{
			if($site->get_value('theme_customization'))
			{
				$customization_data = json_decode($site->get_value('theme_customization'));
			}
			else
			{
				$customization_data = new stdClass;
			}
			return $customization_data;
		}
		
		function init()
		{
			$this->admin_page->title = 'Customize Theme';
		}
		
		function run()
		{
			$customizer = $this->get_customizer();
			if(empty($customizer))
			{
				echo '<p>This site\'s theme cannot be customized.</p>'."\n";
				return;
			}
			if(!reason_user_has_privs( $this->admin_page->user_id, 'customize_all_themes' ) && !$customizer->user_can_customize($this->admin_page->user_id))
			{
				echo '<p>Sorry; you don\'t have privileges to customize this theme.</p>'."\n";
				return;
			}
			$site = $this->get_site();
			if(!reason_user_has_privs( $this->admin_page->user_id, 'bypass_locks' ) && $site->field_has_lock('theme_customization') )
			{
				echo '<p>Sorry; this theme\'s customizations are currently locked. Contact an administrator to change this.</p>'."\n";
				return;
			}
			$form = $this->get_form();
			if(!empty($_GET['success']))
				echo '<p>Customizations saved.</p>'."\n";
			$form->run();
		}
		
		function save_customization_data($disco)
		{
			if($customizer = $this->get_customizer())
			{
				$theme = $this->get_theme();
				$theme_id = $theme->id();
				$data = $customizer->get_customizaton_data($disco);
				$all_data = $this->get_all_customization_data($this->get_site());
				$theme = $this->get_theme();
				$all_data->$theme_id = $data;
				$string = json_encode($all_data);
				$site = $this->get_site();
				if($string != $site->get_value('theme_customization'))
				{
					reason_update_entity($site->id(),$this->admin_page->user_id,array('theme_customization'=>$string));
				}
			}
		}
		
		function form_where_to($disco)
		{
			return carl_make_redirect(array('success'=>1));
		}
	} // }}}

?>
