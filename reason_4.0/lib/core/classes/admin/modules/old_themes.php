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
	 * Old Themes Module
	 * A module that lists themes created before a user-defined date
	 * @author Matt Ryan
	 */
	class OldThemesModule extends DefaultModule // {{{
	{
		function OldThemesModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * @return array
		 */
		function get_themes($created_before)
		{
			$es = new entity_selector();
			$es->add_type(id_of('theme_type'));
			$es->add_relation('`entity`.`creation_date` < "'.reason_sql_string_escape($created_before).'"');
			$es->set_order( '`entity`.`creation_date` DESC' );
			return $es->run_one();
		}
		
		/**
		 * Standard Module init function
		 * 
		 * @return void
		 */
		function init() // {{{
		{
			parent::init();
			
			$this->admin_page->title = 'Old Themes';
			
		} // }}}
		/**
		 * @return void
		 */
		function run() // {{{
		{
			if(!reason_user_has_privs( $this->admin_page->user_id, 'view_sensitive_data') )
			{
				echo '<p>Sorry, you don\'t have access to this report.</p>';
				return;
			}
			$d = new Disco();
			$d->add_element('created_before','textDateTime');
			$d->add_required('created_before');
			$d->run();
			
			if($created_before = $d->get_value('created_before'))
			{
				$themes = $this->get_themes($created_before);
				if(!empty($themes))
					echo $this->get_themes_list($themes);
			}
		} // }}}
		
		/**
		 * @return string
		 */
		function get_themes_list($themes)
		{
			$ret = '';
			$ret .= '<ul>';
			foreach($themes as $theme)
			{
				$ret .= '<li>';
				$ret .= $this->get_theme_report($theme);
				$ret .= '</li>';
			}
			$ret .= '</ul>';
			return $ret;
		}
		/**
		 * @return string
		 */
		function get_theme_report($theme)
		{
			//static $base_link_array = array('site_id'=>id_of('master_admin'),'type_id'=>id_of('theme_type'),'cur_module'=>'Edit');
			//static $base_link_array = array('site_id'=>5,'type_id'=>12,'cur_module'=>'Edit');
			$link_array = array('site_id'=>id_of('master_admin'),'type_id'=>id_of('theme_type'),'cur_module'=>'Edit','id'=>$theme->id());
			$sites = $theme->get_right_relationship('site_to_theme');
			
			$ret = '';
			$ret .= '<strong><a href="'.$this->admin_page->make_link($link_array ).'">'.$theme->get_value('name').'</a></strong> <em>'.substr($theme->get_value('creation_date'),0,10).'</em>';
			if(!empty($sites))
			{
				$ret .= '<ul>';
				foreach($sites as $site)
				{
					$opacity = ($site->get_value('site_state') == 'Live') ? '1' : '0.5';
					$ret .= '<li style="opacity:'.$opacity.'"><a href="'.$site->get_value('base_url').'">'.$site->get_value('name').'</a> '.$site->get_value('site_state').'</li>';
				}
				$ret .= '</ul>';
			}
			else
			{
				$ret .= ' Unused';
			}
			return $ret;
		}
		
	} // }}}
?>