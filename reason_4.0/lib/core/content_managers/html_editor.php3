<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'HTMLEditorManager';
	reason_include_once('function_libraries/file_finders.php');
	
	/**
	 * A content manager for HTML editors
	 */
	class HTMLEditorManager extends ContentManager
	{
		function alter_data()
		{
			$this->set_display_name( 'html_editor_filename','HTML Editor' );
			$this->remove_element( 'custom_content_lister' );
		}
		function on_every_time()
		{
			$include_path = REASON_PATH;
			$this->change_element_type( 'html_editor_filename','select',array('options'=>$this->get_merged_file_array('html_editors')) );
		}
		function get_merged_file_array( $path )
		{
			$array = reason_get_merged_fileset($path);
			foreach($array as $k=>$v)
			{
				$name = basename($v, 'php');
				$name = basename($name, 'php3');
				$array[$k] = str_replace('.','',$name);
			}
			return prettify_array($array);
		}
	}

?>
