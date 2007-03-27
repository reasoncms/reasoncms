<?
	include_once( 'submodule.php');

	$GLOBALS[ '_submodule_class_names' ][ basename( __FILE__, '.php' ) ] = 'news_comments_added';
	
	////////////////////////
	//COMMENTS HAVE BEEN ADDED SUBMODULE
	///////////////////////
	class news_comments_added extends submodule
	{
		var $params = array( 'title'=>'Your comment has been added.', 'title_tag'=>'h4', 'comment_held_text'=>'Comments are being held for review on this publication.  Please check back later to see if your comment has been posted.' );
		var $request;
		
		function init($request, $news_item)
		{
			$this->request = $request;
		}
		
		function has_content()
		{
			if(!empty($this->request['comment_posted_id']))
			{
				return true;
			}
			else
			{
				return false;		
			}
		}
		
		function get_content()
		{		
			if($this->additional_vars['publication']->get_value('hold_comments_for_review') == 'yes')
			{
				$title = '<'.$this->params['title_tag'].'>'.$this->params['comment_held_text'].'</'.$this->params['title_tag'].'>';
				$content = '';
			}
			else
			{
				$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
				$content = '<a href="#comment'.$this->request['comment_posted_id'].'">Jump to your comment</a>';
			}
			return $title.$content;
		}
		
	}
?>
