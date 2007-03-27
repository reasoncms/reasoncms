<?
	include_once( 'submodule.php');
	include_once( 'news_comment_submission_form.php');
	reason_include_once ('classes/group_helper.php');

	$GLOBALS[ '_submodule_class_names' ][ basename( __FILE__, '.php' ) ] = 'news_add_comments';
		
	////////////////////////
	//ADD COMMENTS SUBMODULE
	///////////////////////
	/**
	* If user is authorized to comment on a news item, displays the Disco commentForm
	*/
	class news_add_comments extends submodule
	{
		//how to add a default group that will be everyone?
		var $params = array( 'title'=>'Add a Comment', 
							 'title_tag'=>'h4', 
							 'public'=>true, 
							 'comments_off_message'=>'Comments for this post are turned off',
							 'not_authorized_to_comment_headline'=>'Commenting', 
							 'comments_moderated' => false, 
							 'group' => '',
							 'back_link' => '');
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
			if(!empty($this->params['group']))
			{
				$this->group = $this->params['group'];
			}
			//else, instantiate an empty group entity?
			else
			{
#				this doesn't seem right ... does it really work?
				$this->group = new entity(id_of('group_type'));
			}
			
			$this->make_group_helper();
		//return new entity(id_of('group_type'));
		}
	
		function get_content()
		{
			$content = '';
			$back_link = '';

			if($this->news_item->get_value('commenting_state') == 'off' || !$this->group_helper->group_has_members())
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
					
				#  SURROUND BY IF THIS GROUP IS NOT EMPTY CLAUSE ... && FIGURE OUT WHY IT WASN'T WORKING BEFORE, BECAUSE THAT STILL DOENS'T MAKE SENSE.
					if($this->group_helper->requires_login()) // login required to comment
					{
						$content .= 'Please <a href="'.REASON_LOGIN_URL.'"> login </a> to comment.';
					}
					else // no login required to comment
					{
						$back_link = $this->params['back_link'];
						$content .= $this->build_form();
					}
				}
				else // logged in
				{
					if($this->check_authorization())
					{
						$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
						$back_link = $this->params['back_link'];
						$content .= $this->build_form();
					}
					else
					{
						$title = '<'.$this->params['title_tag'].'>'.$this->params['not_authorized_to_comment_headline'].'</'.$this->params['title_tag'].'>';
						$content .= '<p>Adding comments is restricted on this blog.</p>';
					}
				}
			}
			
			return $back_link."\n".$title."\n".$content;
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
//				$group_obj = $this->get_comment_group();
				$this->group_helper = new group_helper();
				$this->group_helper->set_group_by_entity($this->group);
			}
		}
	
		/**
		* Helper function to get_content() - returns the HTML for the Disco commentForm.
		*/
		function build_form()
		{		
			//use an output buffer so that the form will appear as part of the submodule's content.
			ob_start();
			$this->form = new commentForm($this->site->_id, $this->news_item, $this->params['comments_moderated']);
			$this->form->set_username($this->user_netID);
			$this->form->run();
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	

	}
?>
