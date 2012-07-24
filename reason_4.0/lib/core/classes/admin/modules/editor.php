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
	 * The administrative module that produces the UI for editing entities
	 *
	 * Note that this module is essentially a wrapper for content managers.
	 */
	class EditorModule extends DefaultModule // {{{
	{
		function EditorModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		function init() // {{{
		{
			$this->type_entity = new entity( $this->admin_page->type_id );
			if (!reason_site_can_edit_type($this->admin_page->site_id, $this->admin_page->type_id))
			{
				echo 'This site does not have permission to edit '.$this->type_entity->get_value('plural_name').'.';
				die();
			}
			if( empty( $this->admin_page->id ) )
			{
				if(reason_user_has_privs($this->admin_page->user_id, 'add' ))
				{
					$new_id = create_entity( $this->admin_page->site_id, $this->admin_page->type_id, $this->admin_page->user_id, '', array( 'entity' => array( 'state' => 'Pending' ) ) );
					header( 'Location: '.unhtmlentities($this->admin_page->make_link( array( 'id' => $new_id ), true ) ) );
					die();
				}
				else
				{
					echo 'You do not have the privileges needed to add a '.$this->type_entity->get_value('name');
					die();
				}
			}
			
			$this->entity = new entity( $this->admin_page->id );

			if($this->_cm_ok_to_run())
			{
				$this->_do_admin_page_prep();
				$this->disco_item = $this->_build_content_manager();
			}
			$this->head_items->add_javascript(JQUERY_UI_URL, true);
                        $this->head_items->add_javascript(JQUERY_URL, true);
                        $this->head_items->add_stylesheet(JQUERY_UI_CSS_URL);
                        $this->head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'change_detection.js');
			
		} // }}}
		
		function _cm_ok_to_run()
		{
			switch($this->entity->get_value('state'))
			{
				case 'Live':
					return reason_user_has_privs($this->admin_page->user_id, 'edit' );
				case 'Pending':
					return reason_user_has_privs($this->admin_page->user_id, 'edit_pending' );
				default:
					return false;
			}
		}
		
		function _do_admin_page_prep()
		{
			// get type name and item name for the page title
			$type_name = $this->type_entity->get_value( 'name' );
			if( !($this->entity->get_value( 'name' ) ) AND !(strlen($this->entity->get_value( 'name' )) > 0)) // AND statement handles case of '0'
				$this->admin_page->title = 'Adding '.$type_name;
			else
				$this->admin_page->title = 'Editing "'.$this->entity->get_value('name').'" ('.$type_name.')';

			$this->admin_page->set_show( 'title',false );
			$this->admin_page->set_show( 'breadcrumbs', false );
		}
		
		function _build_content_manager()
		{
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
			
			if(!class_exists($content_handler))
			{
				$filename = $this->type_entity->get_value( 'custom_content_handler' ) ? $this->type_entity->get_value( 'custom_content_handler' ) : 'default.php3';
				trigger_error('Content manager class name provided for '.$filename.' ('.$content_handler.') not found', HIGH);
				die();
			}
			
			$disco_item = new $content_handler;
			$disco_item->admin_page =& $this->admin_page;
			$disco_item->set_head_items( $this->head_items );
			$disco_item->prep_for_run( $this->admin_page->site_id, $this->admin_page->type_id, $this->admin_page->id, $this->admin_page->user_id );
			$disco_item->init();
			return $disco_item;
		}
		
		function run() // {{{
		{
			if($this->_cm_ok_to_run())
			{
				echo '<div class="editor">'."\n";
				echo '<h3 class="pageTitle editor">'.$this->admin_page->title.'</h3>';
				$this->disco_item->run();
				echo '<div id="dialog_confirm" title="Unsaved Changes"><p id="unsaved_changes">You have unsaved changes. How would you like to proceed?</p>';
				echo '<p class="ui-helper-hidden" id="changes_saving">Please wait... <img src="'. REASON_HTTP_BASE_PATH . 'ui_images/reason_admin/wait.gif"/></p></div>';
				echo '</div>'."\n";
			}
			else
			{
				if(!empty($this->admin_page->request['submitted']))
				{
					echo '<p>This item may have errors, but you do not have editing rights to this item.</p>';
					echo '<p><a href="'.$this->admin_page->make_link( array( 'id' => '','site_id' => $this->admin_page->site_id , 'type_id' => $this->admin_page->type_id , 'cur_module' => 'Lister', 'state' => 'pending' ) ).'">Exit this item without editing</a></p>';
				}
				elseif ($this->entity->get_value('state') == 'Deleted')
				{
					echo '<p>This item has been deleted and cannot be edited.</p>';
				}
				else
				{
					echo '<p>Sorry. You do not have the privileges to edit this item.</p>';
				}
			}
		} // }}}
		
		function should_run_api()
		{
			if($this->_cm_ok_to_run())
			{
				return $this->disco_item->should_run_api();
			}
			return false;
		}
		
		/**
		 * We will enforce the same basic rules in _cm_ok_to_run() but return generic API mode errors.
		 *
		 * @todo we should return a 403 when this capability is implemented in CarlUtilAPI
		 */
		function run_api()
		{
			if($this->_cm_ok_to_run())
			{
				$this->disco_item->run_api();
			}
			else
			{
				// this will spit out a 404 - we should actually do a 403.
				$api = new CarlUtilAPI('html');
				$api->run();
			}
			exit();
		}
	} // }}}

?>
