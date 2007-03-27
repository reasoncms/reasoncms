<?php
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'function_libraries/images.php' );
	class CancelModule extends DefaultModule // {{{
	{
		function CancelModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}		
		function init() // {{{
		{
			if( !empty( $this->admin_page->id ) )
			{
				/* figure out if this is a new entity and store the answer */
				
				$temp = new entity( $this->admin_page->id,false );
				if( $temp->get_value( 'new' ) )
					$this->new_entity = true;
				else
					$this->new_entity = false;

			if( $this->new_entity )
				delete_entity( $this->admin_page->id );
			}
			
			if( !empty( $this->admin_page->request[ CM_VAR_PREFIX.'type_id' ] ) )
			{
				// associate this new entity with the original entity if this is new
				$old_vars = array();	
				foreach( $this->admin_page->request AS $key => $val )
					if( substr( $key, 0, strlen( CM_VAR_PREFIX ) ) == CM_VAR_PREFIX )
					{
						$old_vars[ substr( $key, strlen( CM_VAR_PREFIX ) ) ] = $val;
						$old_vars[ $key ] = '';
					}
				foreach( $this->admin_page->default_args AS $arg )
					if( !isset( $old_vars[ $arg ] ) )
						$old_vars[ $arg ] = '';
				$link = $this->admin_page->make_link( $old_vars );
			}
			else if( isset($_SESSION[ 'listers' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ]) )
			{
				$link = $_SESSION[ 'listers' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ];
			}
			else
			{
				$link = $this->admin_page->make_link( 
					array( 'id' => '', 'site_id' => $this->admin_page->site_id,'type_id' => $this->admin_page->type_id , 'cur_module' => 'Lister' ) );
			}
			header( 'Location: '.unhtmlentities( $link ) );
			die();
		} // }}}
		function run() // {{{
		{
		} // }}}
	} // }}}
?>