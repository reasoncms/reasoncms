<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'ThemeManager';

	/**
	 * A content manager for themes
	 */
	class ThemeManager extends ContentManager
	{
		function alter_data()
		{
			$this->add_relationship_element('template', id_of('minisite_template'), 
			relationship_id_of('theme_to_minisite_template'),'right','select');
			$this->change_element_type( 'theme_customizer','select',array('options'=>$this->get_theme_customizers() ) );
		}
		function get_theme_customizers( )
		{
			$array = array();
			foreach(reason_get_merged_fileset('theme_customizers') as $k=>$v)
			{
				if($v != 'interface.php')
				{
					$name = basename($v, '.php');
					$array[$name] = $name;
				}
			}
			return prettify_array($array);
		}
		
	}
?>
