<?php

	reason_include_once( 'minisite_templates/modules/content_base.php' );
	include_once( DISCO_INC . 'disco.php' );
	reason_include_once( 'function_libraries/admin_actions.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EditableContentModule';
	
	class EditableContentModule extends ContentModule
	{
		var $username;
		var $user_id;
		var $cleanup_rules = array(
			'edit' => array( 'function' => 'turn_into_int' ),
			'editable_content' => array( 'function' => 'turn_into_string' ),
		);
		
		function can_edit()
		{
			$can_edit = false;
            if( $this->session->has_started() )
            {
                $this->username = $this->session->get( 'username' );
                if( !empty( $this->username ) )
                {
					/*
                	// try to see if they are a user of the site
					// this code works but is commented out until we create 
					// a session-based toggle to show this stuff
                	$es = new entity_selector();
					$es->add_type(id_of('user'));
					$es->add_relation( 'entity.name = "'.$this->username.'"' );
					$es->add_right_relationship( $this->site_id, relationship_id_of( 'site_to_user' ) );
					$es->set_num(1);
					$users = $es->run_one();
					if(!empty($users))
					{
						$user = current( $users );
						$this->user_id = $user->id();
						$this->is_reason_user = true;
						return true;
					}
					else // fall back to site user to page relationship
					{
                	*/
						$es = new entity_selector( $this->site_id );
						$es->add_type( id_of( 'site_user_type' ) );
						$es->add_relation( 'entity.name = "'.$this->username.'"' );
						$es->add_right_relationship( $this->page_id, relationship_id_of( 'page_to_site_user' ) );
						$es->set_num(1);
						$site_users = $es->run_one();
						if( !empty( $site_users ) )
						{
							$site_user = current( $site_users );
							$userid = get_user_id( $this->username );
							if(!empty($userid))
							{
								$this->user_id = $userid;
								$this->is_reason_user = true;
							}
							else
							{
								$this->user_id = $site_user->id();
								$this->is_reason_user = false;
							}
							return true;
						}
					/* } */
                }
            }
            return false;
		}
		// inherit run from the content module
		function run_editable()
		{
			echo '<div id="pageContent" class="editable">'."\n";
			if( !empty( $this->request[ 'edit' ] ) )
			{
				if($this->is_reason_user)
				{
					$uid = $this->user_id;
				}
				else
				{
					$uid = 0;
				}
				$form = new EditContentForm;
				$form->init();
				// We use the name 'editable_content' instead of 'content' because on the front end, every page has an anchor named 'content', which
				// would conflict and cause problems for things like document.getElementById.
				$form->change_element_type( 'editable_content' , html_editor_name($this->site_id) , html_editor_params($this->site_id, $uid) );
				$form->set_display_name('editable_content',' ');
				$form->set_value( 'editable_content', $this->content );
				$form->set_value( 'edit', 1 );
				$form->set_page_id( $this->page_id );
				$form->set_user_id( $this->user_id );
				$form->run();
			}
			else
			{
				$output = $this->content;
				$link = '<p><a href="?edit=1" class="editThis">Edit This</a></p>';
				$pre = '<div class="editRegion">';
				$post = '</div>';
				$output = $pre . $output . $link . $post;
				echo $output;
			}
			echo '</div>'."\n";
		}
	}
	
	class EditContentForm extends Disco
	{
		var $elements = array(
			'editable_content' => 'textarea',
			'edit' => 'hidden'
		);
		var $actions = array(
			'save' => 'Save',
			'save_and_finish' => 'Save and Finish Editing',
		);
		var $page_id; // private, use getter/setter methods
		var $user_id; // private, use getter/setter
		
		function finish()
		{
			$id = $this->get_page_id();
			$values = array(
				'content' => $this->get_value( 'editable_content' )
			);
			$archive = false;
			if( $this->chosen_action == 'save_and_finish' )
			{
				$archive = true;
			}
			// update_entity
			update_entity( $id, $this->get_user_id(), values_to_tables( get_entity_tables_by_id( $id ), $values ), $archive );
		}
		function where_to()
		{
			if( $this->chosen_action =='save' )
			{
				$url = get_current_url();
			}
			else
			{
				$parts = parse_url( get_current_url() );
				$url = securest_available_protocol() . '://'.$parts['host'].$parts['path'];
			}
			header( 'Location: '.$url );
			die();
		}
		function get_page_id()
		{
			return $this->page_id;
		}
		function set_page_id( $id )
		{
			$this->page_id = $id;
		}
		function get_user_id()
		{
			return $this->user_id;
		}
		function set_user_id( $id )
		{
			$this->user_id = $id;
		}
	}
?>
