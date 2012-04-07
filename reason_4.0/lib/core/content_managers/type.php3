<?php
/**
 * A content manager for type entities
 * @package reason
 * @subpackage content_managers
 */
	
	/**
	 * Save the class name so that the admin page can use this content manager
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'TypeManager';
	
	/**
	 * Include dependencies
	 */
	reason_include_once('function_libraries/file_finders.php');
	
	/**
	 * A content manager for type entities
	 *
	 * Provides custom behavior and interface for managing types
	 *
	 * @todo add automatic amputee fixing upon finish here or somewhere else
	 *       (manual amputee fixing is one of the least intuitive aspects of reason db management)
	 */
	class TypeManager extends ContentManager
	{
		function alter_data()
		{
			$this->set_display_name( 'custom_content_handler','Content Manager' );
			$this->remove_element( 'custom_content_lister' );
		}
		function on_every_time()
		{
			$include_path = REASON_PATH;
			$this->change_element_type( 'custom_content_handler','select',array('options'=>$this->get_merged_file_array('content_managers')) );
			$this->change_element_type( 'custom_deleter','select',array('options'=>$this->get_merged_file_array('content_deleters')) );
			$this->change_element_type( 'custom_post_deleter','select',array('options'=>$this->get_merged_file_array('content_post_deleters')) );
			$this->change_element_type( 'custom_previewer','select',array('options'=>$this->get_merged_file_array('content_previewers')) );
			$this->change_element_type( 'custom_sorter','select',array('options'=>$this->get_merged_file_array('content_sorters')) );
			$this->change_element_type( 'display_name_handler','select',array('options'=>$this->get_merged_file_array('display_name_handlers')) );
			$this->change_element_type( 'finish_actions','select',array('options'=>$this->get_merged_file_array('finish_actions')) );
			$this->change_element_type( 'custom_feed','select',array('options'=>$this->get_merged_file_array('feeds')) );
			$this->set_comments( 'finish_actions', form_comment('This simply includes a file in the finish_actions directory in the Reason library.  These happen after all other actions in the Finish module and immediately before the redirect.') );
			//$this->change_element_type( 'unique_name','text' );
			$this->add_required( 'unique_name' );

			$this->set_comments( 'custom_post_deleter', form_comment('The custom post deleter is a script that will run after the entity has changed state from Live to Deleted.  This will not be run on expunge.') );
			$this->set_comments( 'feed_url_string', form_comment('If there is a feed url string, this type will be published as an RSS feed and that feed will be publicized.  Do not use this field if this is not information that can be made publicly available.') );

			$this->remove_element( 'custom_content_lister_dev' );
			$this->remove_element( 'custom_content_handler_dev' );
			
			$this -> set_order (array ('name', 'plural_name', 'unique_name', 'type_type', 'custom_content_handler', 'custom_deleter', 'custom_post_deleter', 'custom_previewer', 'custom_sorter', 'display_name_handler', 'finish_actions', 'custom_feed', 'feed_url_string', ));
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
		function finish()
		{
			$ret = $this->CMfinish();

			// when adding a new type, add the site to type ownership relationship for entities of this type that will be created
			if ( $this->is_new_entity() )
			{
				create_default_rels_for_new_type($this->_id);
			}
			
			reason_include_once('classes/url_manager.php');
			$urlm = new url_manager(0,false,true);
			$urlm->update_rewrites();
			
			return $ret;
		}
	}

?>
