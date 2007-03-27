<?
include_once( 'submodule.php');

$GLOBALS[ '_submodule_class_names' ][ basename( __FILE__, '.php' ) ] = 'news_images';

class news_images extends submodule
{
	var $images = array();
	var $params = array( 'title'=>'Images', 'title_tag'=>'h4' );
	var $die_without_thumbnail = false;
	var $show_popup_link = true;
	var $show_description = true;
	var $additional_text = '';
	var $textonly = false;
	
	function init($request, $news_item)
	{
		parent::init($request);
		$es = new entity_selector();
		if(method_exists ( $es, 'set_env' ))
		{
			$es->set_env( 'site' , $this->site->id() );
		}
		$es->description = 'Selecting images for news item';
		$es->add_type( id_of('image') );
		$es->add_right_relationship( $news_item->id(), relationship_id_of('news_to_image') );
		$es->add_rel_sort_field( $news_item->id(), relationship_id_of('news_to_image') );
		$es->set_order('rel_sort_order');
		$this->images = $es->run_one();
		if(!empty($this->request['textonly']))
		{
			$this->textonly = $this->request['textonly'];
		}
	}
	function has_content()
	{
		if(!empty($this->images))
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
		foreach($this->images as $image)
		{
			ob_start();
			show_image( $image, $this->die_without_thumbnail, $this->show_popup_link, $this->show_description, $this->additional_text, $this->textonly );
			$list_parts[] = ob_get_contents();
			ob_end_clean();
		}
		$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		$content = '<ul>'."\n".'<li>'.implode('</li>'."\n".'<li>',$list_parts).'</li>'."\n".'</ul>'."\n";
		return $title."\n".$content;
	}
}
?>
