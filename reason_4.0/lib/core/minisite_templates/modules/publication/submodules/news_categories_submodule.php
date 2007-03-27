<?
include_once( 'submodule.php');

$GLOBALS[ '_submodule_class_names' ][ basename( __FILE__, '.php' ) ] = 'news_categories_submodule';

////////////////////////
//Categories Submodule
///////////////////////
/**
* Display categories associated with a news item
*/
class news_categories_submodule extends submodule
{
	var $categories = array();
	var $params = array( 'title'=>'Categories', 'title_tag'=>'h4' );
	var $textonly = false;
	
	function init($request, $news_item)
	{
		parent::init($request);
		$es = new entity_selector();
		$es->description = 'Selecting categories for news item';
		$es->add_type( id_of('category_type') );
		$es->set_env('site',$this->site->id());
		$es->add_right_relationship( $news_item->id(), relationship_id_of('news_to_category') );
		$es->set_order( 'entity.name ASC' );
		$this->categories = $es->run_one();
		
		if(!empty($this->request['textonly']))
		{
			$this->textonly = $this->request['textonly'];
		}
	}
	
	function has_content()
	{
		if(!empty($this->categories))
			return true;
		else
			return false;
	}

	function get_content()
	{
		$list_parts = array();
		$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		$content = '<ul>';
		foreach($this->categories as $category)
		{
			$link = '?filters[1][type]=category&filters[1][id]='.$category->id();
			if($this->textonly)
			{
				$link .= '&amp;textonly=1';
			}
			$content .= '<li><a href="'.$link.'">'.$category->get_value('name').'</a></li>';
		}
		$content .= '</ul>';
		return '<a name="comments">'.$title.'</a>'."\n".$content;
	}
}
?>