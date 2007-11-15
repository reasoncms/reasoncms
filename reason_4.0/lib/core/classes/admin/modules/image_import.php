<?php
	/**
	 * Image Import Module
	 *
	 * Handles batch importing of images
	 *
	 * @package reason
	 * @author Matt Ryan, mryan@acs.carleton.edu
	 */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once('classes/image_import.php');
	/**
	 * This admin module allows users to import multiple images at once
	 */
	class ImageImportModule extends DefaultModule // {{{
	{
		var $image_import_form_class_name = 'PhotoUploadForm';
		function ImageImportModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->admin_page->title = 'Batch Import Images';
		} // }}}
		/**
		 * Run form if it is OK
		 */
		function run() // {{{
		{
			if($this->site_can_manage_images($this->admin_page->site_id))
			{
				$this->run_form();
			}
			else
			{
				echo '<p>Sorry; this site is not set up to manage images.</p>'."\n";
			}
		} // }}}
		/**
		 * Determine if a site has access to the image type
		 * @param integer $site_id site to test
		 * @return boolean
		 */
		function site_can_manage_images($site_id)
		{
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$es->add_right_relationship($site_id,relationship_id_of('site_to_type'));
			$es->add_relation('entity.unique_name = "image"');
			$es->set_num(1);
			$types = $es->run_one();
			if(empty($types))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		/**
		 * Set up and run the actual form used to import the images
		 */
		function run_form()
		{
			$f = new $this->image_import_form_class_name;
			$f->username = $this->admin_page->user->get_value('name');
			$f->user_id = $this->admin_page->user->id();
			$f->site_id = $this->admin_page->site_id;
			$f->add_element('cancel_text','comment',array('text'=>'<a href="'.$this->admin_page->make_link(  array( 'cur_module' => 'Lister' , 'id' => '') ).'">Cancel batch import</a>'));
			$f->run();
		}
	} // }}}
?>