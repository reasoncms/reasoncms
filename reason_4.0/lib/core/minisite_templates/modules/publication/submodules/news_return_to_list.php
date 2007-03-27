<?
	include_once( 'submodule.php');
	reason_include_once ( 'function_libraries/url_utils.php' );

	$GLOBALS[ '_submodule_class_names' ][ basename( __FILE__, '.php' ) ] = 'news_return_to_list';

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

			return '<a href="'.make_link(array('story_id'=>'')).'">'.$title.'</a>';
		}
}		
?>
