<?php
	reason_include_once('classes/admin/modules/default.php');
	class EditorModule extends DefaultModule // {{{
	{
		function EditorModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			if( empty( $this->admin_page->id ) )
			{
				$new_id = create_entity( $this->admin_page->site_id, $this->admin_page->type_id, $this->admin_page->user_id, '', array( 'entity' => array( 'state' => 'Pending' ) ) );
				header( 'Location: '.unhtmlentities($this->admin_page->make_link( array( 'id' => $new_id ), true ) ) );
				die();
			}
			$this->type_entity = new entity( $this->admin_page->type_id );
			$this->entity = new entity( $this->admin_page->id );

			// get type name and item name for the page title
			$type_name = $this->type_entity->get_value( 'name' );
			if( !($this->entity->get_value( 'name' ) ) AND !(strlen($this->entity->get_value( 'name' )) > 0)) // AND statement handles case of '0'
				$this->admin_page->title = 'Adding '.$type_name;
			else
				$this->admin_page->title = 'Editing "'.$this->entity->get_value('name').'" ('.$type_name.')';

			$this->admin_page->set_show( 'title',false );
			$this->admin_page->set_show( 'breadcrumbs', false );
		} // }}}
		function run() // {{{
		{
/*			echo '<table cellpadding="4"><tr><td valign="top">';
			$this->show_editor_navigation();
			echo '</td><td valign="top">';*/
			reason_include_once( 'content_managers/default.php3' );
			$content_handler = $GLOBALS[ '_content_manager_class_names' ][ 'default.php3' ];
			if ( $this->type_entity->get_value( 'custom_content_handler' ) )
			{
				$include_file = 'content_managers/'.$this->type_entity->get_value( 'custom_content_handler' );
				reason_include_once( $include_file );
				if(!empty($GLOBALS[ '_content_manager_class_names' ][ $this->type_entity->get_value( 'custom_content_handler' ) ]))
				{
					$content_handler = $GLOBALS[ '_content_manager_class_names' ][ $this->type_entity->get_value( 'custom_content_handler' ) ];
				}
				else
				{
					trigger_error('Content handler not found in '.$include_file);
				}
			}
			$this->disco_item = new $content_handler;
			$this->disco_item->admin_page =& $this->admin_page;
			$this->disco_item->prep_for_run( $this->admin_page->site_id, $this->admin_page->type_id, $this->admin_page->id, $this->admin_page->user_id );
			$this->disco_item->init();
			echo '<h3 class="pageTitle editor">'.$this->admin_page->title.'</h3>';

			$this->disco_item->run();
		} // }}}
	} // }}}

?>
