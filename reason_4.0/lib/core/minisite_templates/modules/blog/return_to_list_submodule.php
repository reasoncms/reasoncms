<?
	reason_include_once( 'minisite_templates/modules/blog/comment_submission_form.php');

	////////////////////////
	//RETURN TO LIST OF POSTS SUBMODULE
	///////////////////////

	class news_return_to_list extends submodule
	{
		var $params = array( 'title'=>'Return to List', 'title_tag'=>'p');
		var $request;
		
		function init($request, $news_item)
		{
			$this->request = $request;
		}
		
		function has_content()
		{
			if(!empty($this->request['story_id']))
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
			$title_addition = '';
			if(!empty($this->additional_vars['blog']))
			{
				$title_addition = ' '.$this->additional_vars['blog']->get_value('name');
			}
			$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].$title_addition.'</'.$this->params['title_tag'].'>';
			$link = '<a href="'.$this->build_link().'">'.$title.'</a>';
		
			return $link;
		}
		
		function build_link()
		{
			$should_be_passed = array();
			foreach($this->request as $request_var => $value)
			{
				if($request_var != 'story_id' && !empty($value))
				{
					$should_be_passed[] = $request_var.'='.$value;
				}
			}
			
			$request_vars = implode('&', $should_be_passed);
			if(!empty($request_vars))
			{
				$link = '?'.$request_vars;
			}
			else
			{
				$link = '';
			}
			return $link;
		}
	}
	
?>
