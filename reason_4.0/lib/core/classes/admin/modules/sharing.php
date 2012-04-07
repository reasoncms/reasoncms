<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/associator.php');
	
	/**
	 * An administrative module that provides an interface to borrow entities from another site
	 *
	 * You'd think this would be called BorrowModule, but you would be incorrect.
	 */
	class SharingModule extends AssociatorModule // {{{
	{
		function SharingModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function should_run()
		{
			return true;
		}
		function init() // {{{
		{
			$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.'sharing.css');
			reason_include_once( 'classes/sharing_filter.php' );
			reason_include_once( 'content_listers/sharing.php' );

			$type = new entity( $this->admin_page->type_id );
			// save the type entity in an object scope
			$this->rel_type = $type;
			$this->get_views( $type->id() );
			if( empty( $this->views ) )//add generic lister if not already present
				$this->views = array();
			else
			{
				reset( $this->views );
				$c = current( $this->views );
				if( $c )
				{
					$lister = $c->id();
					$this->admin_page->request[ 'lister' ] = $lister;
				}
				else
					$lister = '';
			}	
			$this->admin_page->title = ( $type->get_value( 'plural_name' ) ? $type->get_value( 'plural_name' ) : $type->get_value('name') );
			if($icon_url = reason_get_type_icon_url($type,false))
			{
				$this->admin_page->title = '<img src="'.$icon_url.'" alt="" /> '.$this->admin_page->title;
			}
			if( $this->admin_page->is_second_level() )
				$this->admin_page->set_show( 'leftbar' , false );

			$this->viewer = new sharing_viewer;
			$this->viewer->set_page( $this->admin_page );
			if( !isset( $lister ) ) $lister = '';
			$this->viewer->init( $this->admin_page->site_id, $type->id(), $lister ); 
			
			$this->filter = new sharing_filter;
			$this->filter->set_page( $this->admin_page );
			$this->filter->grab_fields( $this->viewer->filters );

		} // }}}
		function run() {
			echo $this->_produce_borrowing_nav();
			parent::run();
		}
		
		function _produce_borrowing_nav()
		{
			$ret = '';
			$nes = new entity_selector( );
			$nes->add_type( id_of('type') );
			$nes->add_right_relationship( $this->admin_page->site_id, relationship_id_of( 'site_cannot_edit_type' ) );
			$nes->add_relation('`entity`.`id` = "'.addslashes($this->admin_page->type_id).'"');
			$nes->set_num(1);
			$nes->limit_tables();
			$nes->limit_fields();
			$ns = $nes->run_one();
			$show_edit = reason_user_has_privs($this->admin_page->user_id,'edit') && !$this->admin_page->is_second_level() && empty($ns) ? true : false;
			
			/* $type = new entity($this->admin_page->type_id);
			$name = $type->get_value('plural_name') ? $type->get_value('plural_name') : $type->get_value('name');
			if(function_exists('mb_strtolower'))
				$name = mb_strtolower($name);
			else
				$name = strtolower($name); */
			$ret .= '<div class="borrowNav">'."\n";
			$ret .= '<ul>';
			if($show_edit)
				$ret .= '<li><a href="'.$this->admin_page->get_owned_list_link($this->admin_page->type_id).'"><img src="'.REASON_HTTP_BASE_PATH.'silk_icons/bullet_edit.png" alt="" /> Add &amp; edit</a></li>';
			$ret .= '<li class="current"><strong><img src="'.REASON_HTTP_BASE_PATH.'silk_icons/car.png" alt="" /> Borrow</strong></li>';
			$ret .= '</ul>'."\n";
			$ret .= '</div>'."\n";
			// if(reason_user_has_privs($this->admin_page->user_id,'edit'))
			return $ret;
		}

		function show_next_nodes() // {{{
		{
			$finish_link = $this->admin_page->make_link( array( 'cur_module' => 'Lister' ) );
			
			echo '<a href="'.$finish_link.'">Back to Lister</a><br />';
		} // }}}
	} // }}}
?>