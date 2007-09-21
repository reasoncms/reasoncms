<?php
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'classes/admin/modules/editor.php' );
	reason_include_once( 'classes/admin/admin_disco.php' );
	reason_include_once( 'function_libraries/images.php' );
	class DeleteModule extends DefaultModule // {{{
	{
		var $deletable = false;
		function DeleteModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}		
		function init() // {{{
		{
			if(empty($this->admin_page->id))
			{
				return false;
			}
			$this->deletable = $this->admin_page->is_deletable();
			if( !isset( $this->admin_page->request[ 'undelete' ] ) )
			{
				if($this->deletable)
				{
					$deleter = 'deleteDisco';
					$type = new entity( $this->admin_page->type_id );
					if( $type->get_value( 'custom_deleter' ) )
					{
						reason_include( 'content_deleters/' . $type->get_value( 'custom_deleter' ) );
						if(!empty($GLOBALS[ '_reason_content_deleters' ][ $type->get_value( 'custom_deleter' ) ] ) )
							$deleter = $GLOBALS[ '_reason_content_deleters' ][ $type->get_value( 'custom_deleter' ) ];
						else
							trigger_error($type->get_value( 'custom_deleter' ).' needs to record its class name in $GLOBALS[ "_reason_content_deleters" ].');
					}
					$this->disco_item = new $deleter;
					$this->disco_item->actions = array();
					$this->disco_item->set_page( $this->admin_page );
					$this->disco_item->actions[ 'delete' ] = 'Yes, Delete and Go Back to List';
					
					$this->disco_item->actions[ 'cancel' ] = 'No, Cancel';	
					$this->disco_item->grab_info( $this->admin_page->id , $graph );
					$this->disco_item->init();
					$this->admin_page->set_show( 'leftbar', false );
				}

			}
			else
			{
				$q = 'UPDATE entity SET state = "Live", last_edited_by = "'.$this->admin_page->user_id.'" where id = ' . $this->admin_page->id;
				db_query( $q , 'Error setting state as live in DeleteModule::init()' );

				if( get_class( $graph->nodes[ $graph->start ] ) == 'admin_lister_node' 
					AND isset( $_SESSION[ 'listers' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ] ) 
				  )
					$link = unhtmlentities( $_SESSION[ 'listers' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ] ).
						'&unique_id=' . $this->admin_page->unique_id;
				else 	
					$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Lister' , 'id' => '' , 'state' => 'deleted' ) ) );
				header( 'Location: ' . $link );
				die();
			}
		} // }}}
		function run() // {{{
		{
			if(empty($this->admin_page->id))
			{
				echo '<p>Unable to delete item. Item may already have been deleted (sometimes this happens if you click twice on the delete button)</p>';
				return false;
			}
			if($this->deletable)
			{
				$this->disco_item->run();
			}
			else
			{
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'NoDelete' ) ) );
				header( 'Location: ' . $link );
				die();
			}
		} // }}}
	} // }}}
?>