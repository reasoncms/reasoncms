<?
	include_once( 'submodule.php');
	reason_include_once ( 'function_libraries/url_utils.php' );
	
	$GLOBALS[ '_submodule_class_names' ][ basename( __FILE__, '.php' ) ] = 'news_display_comments';
	
	////////////////////////
	//COMMENTS SUBMODULE
	///////////////////////
	/**
	* Display comments associated with a blog post.
	*/
	class news_display_comments extends submodule
	{
		var $comments = array();
		var $params = array(	'title'=>'Comments',
								'title_tag'=>'h4',
								'no_items_message'=>'There are no comments yet for this post', 
								'date_format' => 'F j Y \a\t g:i a',
								'back_link' => '',
							);
		
		function init($request, $news_item)
		{
			$es = new entity_selector();
			$es->description = 'Selecting comments for news item';
			$es->add_type( id_of('comment_type') );
			$es->add_relation('show_hide.show_hide = "show"');
			$es->add_right_relationship( $news_item->id(), relationship_id_of('news_to_comment') );
			$es->set_order( 'dated.datetime ASC' );
			$this->comments = $es->run_one();
		}
		
		function has_content()
		{
			if(!empty($this->comments))
				return true;
			else
				return false;
		}
	
		function get_content()
		{
			$back_link = '';
			$list_parts = array();
			$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		 
			if(!empty($this->comments))
			{
				$back_link = $this->params['back_link'];
				$content = '<ul>';
				foreach($this->comments as $comment)
				{
					$content .= '<li id="comment'.$comment->id().'">';
					/* if($comment->get_value('name'))
					{
						$content .= '<div class="name"><strong>'.$comment->get_value('name').'</strong></div>';
					} */
					$content .= '<div class="datetime">'.prettify_mysql_datetime($comment->get_value('datetime'), $this->params['date_format']).'</div>';
					$content .= '<div class="author">'.$comment->get_value('author').'</div>';
					$content .= '<div class="commentContent">'.$comment->get_value('content').'</div>';
					$content .= '</li>';
				}
				$content .= '</ul>';
			}
			else
			{
				$content = '<p>'.$this->params['no_items_message'].'</p>';
			}
			
			return $back_link.'<a name="comments">'.$title.'</a>'."\n".$content;
		}
		
		function get_return_link()
		{
			$title_addition = '';
			if(!empty($this->additional_vars['publication']))
			{
				$title_addition = ' '.$this->additional_vars['publication']->get_value('name');
			}
			elseif(!empty($this->additional_vars['blog']))
			{
				$title_addition = ' '.$this->additional_vars['blog']->get_value('name');
			}
			else
				$title_addition = ' list';				
			
#			$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].$title_addition.'</'.$this->params['title_tag'].'>';
			$title = '<p> Return to '.$title_addition.'</p>';

			return '<a href="'.make_link(array('story_id'=>'')).'">'.$title.'</a>';
		}
	}
?>
