<?php
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'function_libraries/images.php' );
	class PreviewModule extends DefaultModule // {{{
	{
		function PreviewModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
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
		function run() // {{{
		{
			$this->previewer->run();
		} // }}}
	} // }}}
?>