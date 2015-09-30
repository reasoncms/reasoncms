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
	 * An administrative module that produces a raw(ish) view of an entity's data
	 *
	 * This module is essentially a wrapper for content_previewers
	 */
	class PreviewModule extends DefaultModule // {{{
	{
		var $_ok_to_run;
		function PreviewModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			if(!$this->_ok_to_run_module())
				return;
			
			reason_include_once( 'content_previewers/default.php' );
			$previewer = $GLOBALS[ '_content_previewer_class_names' ][ 'default.php' ];
			
			$ent = new entity( $this->admin_page->id );
			
			$this->admin_page->title = 'Previewing ' . $ent->get_value( 'name' );
			$type = new entity( $this->admin_page->type_id );
			if( $type->get_value( 'custom_previewer' ) )
			{
				reason_include_once( 'content_previewers/' . $type->get_value( 'custom_previewer' ) );
				$previewer = $GLOBALS[ '_content_previewer_class_names' ][ $type->get_value( 'custom_previewer' ) ];
			}
			$this->previewer = new $previewer;
			$this->previewer->init( $this->admin_page->id , $this->admin_page );
		} // }}}
		
		function _ok_to_run_module()
		{
			if($this->_ok_to_run !== true && $this->_ok_to_run !== false)
			{
				$this->_ok_to_run = false;
				
				if(!$this->admin_page->id)
				{
					return $this->_ok_to_run;
				}
			
				$owner_site = get_owner_site_id( $this->admin_page->id );
			
				$entity = new entity($this->admin_page->id);
			
				if($owner_site == $this->admin_page->site_id)
				{
					$this->_ok_to_run = true;
					return $this->_ok_to_run;
				}
				
				if(site_borrows_entity( $this->admin_page->site_id, $entity->id() ))
				{
					$this->_ok_to_run = true;
					return $this->_ok_to_run;
				}
				
				if(site_shares_type($owner_site, $entity->get_value('type')) && $entity->get_value('no_share') == 0 )
				{
					$this->_ok_to_run = true;
					return $this->_ok_to_run;
				}
			}
			return $this->_ok_to_run;
		}
		function run() // {{{
		{
			if(!$this->_ok_to_run_module())
				echo 'Sorry; preview not available.';
			else
				$this->previewer->run();
		} // }}}
	} // }}}
?>