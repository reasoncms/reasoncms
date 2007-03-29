<?

	reason_include_once( 'minisite_templates/modules/blog/comment_submission_form.php');
	
	////////////////////////
	//ADD COMMENTS SUBMODULE
	///////////////////////
	/**
	* If user is authorized to comment on a blog, displays the Disco commentForm
	*/
	class news_add_comments extends submodule
	{
		var $params = array( 'title'=>'Add a Comment', 'title_tag'=>'h4', 'public'=>true, 'comments_off_message'=>'Comments for this post are turned off','not_authorized_to_comment_headline'=>'Commenting');
		var $form;
		var $news_item;
		var $user_netID;
		var $session;
		var $group;
		var $group_helper;
		
		function init($request, $news_item)
		{
			$this->news_item = $news_item;
			$this->session =& get_reason_session();
		}
	
		function get_content()
		{
			$content = '';
			if($this->news_item->get_value('commenting_state') == 'off')
			{
				$title = '<'.$this->params['title_tag'].'>'.$this->params['comments_off_message'].'</'.$this->params['title_tag'].'>';
			}
			else
			{
				$user_has_credentials = $this->check_authentication();
				
				if(!$user_has_credentials) // not logged in
				{
					$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
					
					$this->make_group_helper();
					
					if($this->group_helper->requires_login()) // login required to comment
					{
						$content .= 'Please <a href="'.REASON_LOGIN_URL.'"> login </a> to comment.';
					}
					else // no login required to comment
					{
						$content .= $this->build_form();
					}
				}
				else // logged in
				{
					if($this->check_authorization())
					{
						$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
						$content .= $this->build_form();
					}
					else
					{
						$title = '<'.$this->params['title_tag'].'>'.$this->params['not_authorized_to_comment_headline'].'</'.$this->params['title_tag'].'>';
						$content .= '<p>Adding comments is restricted on this blog.</p>';
					}
				}
			}
			
			return $title."\n".$content;
		}
		function get_comment_group()
		{
			if(empty($this->group))
			{
				$blog = $this->additional_vars['blog'];
				$es = new entity_selector( $this->site->_id );
				$es->description = 'Getting groups for this blog';
				$es->add_type( id_of('group_type') );
				$es->add_right_relationship( $blog->id(), relationship_id_of('publication_to_authorized_commenting_group') );
				$es->set_num(1);
				$groups = $es->run_one();
				if(!empty($groups))
				{
					$this->group = current($groups);
					return $this->group;
				}
				else
				{
					trigger_error('No commenting group assigned to blog id '.$this->additional_vars['blog']);
					return false;
				}
			}
			return $this->group;
		}
	
		function check_authentication()
		{
			if(!empty($_SERVER['REMOTE_USER']))
			{
				$this->user_netID = $_SERVER['REMOTE_USER'];
				//return 'Basic authentication ='.$this->user_netID;
				return true;
			}
			else
			{
				if($this->session->has_started() )
				{
					$this->user_netID = $this->session->get( 'username' );
					//return 'Reason login = '.$this->user_netID;
					return true;
				}
				else
				{
					return false;
				}
			}
		}
		
		function check_authorization()
		{
			$this->make_group_helper();
			if($this->group_helper->is_username_member_of_group($this->user_netID))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		
		function make_group_helper()
		{
			if(empty($this->group_helper))
			{
				$group_obj = $this->get_comment_group();
				if(!empty($group_obj))
				{
					$this->group_helper = new group_helper();
					$this->group_helper->set_group_by_entity($group_obj);
				}
			}
		}
	
		/**
		* Helper function to get_content() - returns the HTML for the Disco commentForm.
		*/
		function build_form()
		{
			//use an output buffer so that the form will appear as part of the submodule's content.
			ob_start();
			$this->form = new commentForm($this->site->_id, $this->news_item, $this->additional_vars['blog']);
			$this->form->set_username($this->user_netID);
			$this->form->run();
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}
?>
