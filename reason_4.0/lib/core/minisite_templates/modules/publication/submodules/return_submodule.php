<?
	////////////////////////
	//COMMENTS SUBMODULE
	///////////////////////
	/**
	* Display comments associated with a blog post.
	*/
	class news_comments_submodule extends submodule
	{
		var $comments = array();
		var $params = array(	'title'=>'Comments',
								'title_tag'=>'h4',
								'no_items_message'=>'There are no comments yet for this post', 
								'date_format' => 'F j Y \a\t g:i a',
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
			$list_parts = array();
			$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		 
			if(!empty($this->comments))
			{
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
			return '<a name="comments">'.$title.'</a>'."\n".$content;
		}
	}
?>