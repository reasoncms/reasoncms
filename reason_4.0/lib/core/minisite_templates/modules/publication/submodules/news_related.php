<?
include_once( 'submodule.php');

class news_related extends submodule
{
	var $related = array();
	var $params = array( 'title'=>'Related News Items', 'title_tag'=>'h4' );
	function init($request, $news_item)
	{
		$es = new entity_selector();
		$es->description = 'Selecting related news for news item';
		$es->add_type( id_of('news') );
		$es->add_right_relationship( $news_item->id() , relationship_id_of( 'news_to_news' ) );
		$es->add_relation( 'status.status = "published"' );
		$this->related = $es->run_one();
	}
	function has_content()
	{
		if(!empty($this->related))
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
		$list_parts = array();
		foreach($this->related as $related_item)
		{
			$list_parts[] = '<a href="?item_id='.$related_item->id().'">'.$related_item->get_value('release_title').'</a>';
		}
		$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		$content = '<ul>'."\n".'<li>'.implode('</li>'."\n".'<li>',$list_parts).'</li>'."\n".'</ul>'."\n";
		return $title."\n".$content;
	}
}
?>