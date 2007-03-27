<?
$GLOBALS[ '_submodule_class_names' ][ basename( __FILE__, '.php' ) ] = 'news_assets';

include_once( 'submodule.php');

class news_assets extends submodule
{
	var $assets = array();
	var $params = array( 'title'=>'Assets', 'title_tag'=>'h4' );
	function init($request, $news_item)
	{
		$es = new entity_selector();
		$es->description = 'Selecting assets for news item';
		$es->add_type( id_of('asset') );
		$es->add_right_relationship( $news_item->id(), relationship_id_of('news_to_asset') );
		$this->assets = $es->run_one();
	}
	function has_content()
	{
		if(!empty($this->assets))
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
		reason_include_once( 'function_libraries/asset_functions.php' );
		$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		$content = make_assets_list_markup( $this->assets, $this->site );
		return $title."\n".$content;
	}
}
?>
