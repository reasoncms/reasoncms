<?
include_once( 'submodule.php');

$GLOBALS[ '_submodule_class_names' ][ basename( __FILE__, '.php' ) ] = 'news_related_events';

class news_related_events extends submodule
{
	var $events = array();
	var $events_page_url = array();
	var $events_page_types = array('events','events_verbose','events_nonav','events_academic_calendar','event_registration','event_slot_registration', 'athletics_schedule');
	var $params = array( 'title'=>'Related Events', 'title_tag'=>'h4' );
	var $textonly = false;
	
	function init($request, $news_item)
	{
		parent::init($request);
		$es = new entity_selector();
		if(method_exists ( $es, 'set_env' ))
		{
			$es->set_env( 'site' , $this->site->id() );
		}
		$es->description = 'Selecting events for this news item';
		$es->add_type( id_of('event_type') );
		$es->add_left_relationship( $news_item->id(), relationship_id_of('event_to_news') );
		$es->add_rel_sort_field( $news_item->id(), relationship_id_of('event_to_news') );
		$es->set_order('rel_sort_order');
		$this->events = $es->run_one();
		if ($this->events && empty($this->events_page_url))
		{
			$this->find_events_page_url();
		}
		if(!empty($this->request['textonly']))
		{
			$this->textonly = $this->request['textonly'];
		}
	}
	
	function find_events_page_url()
	{
		$ps = new entity_selector($this->site->id() );
		$ps->add_type( id_of('minisite_page') );
		$rels = array();
		foreach($this->events_page_types as $page_type)
		{
			$rels[] = 'page_node.custom_page = "'.$page_type.'"';
		}
		$ps->add_relation('( '.implode(' OR ', $rels).' )');
		$page_array = $ps->run_one();
		reset($page_array);
		$this->events_page = current($page_array);

		if (!empty($this->events_page))
		{
			$ret = build_URL($this->events_page->id());
		}
		if(!empty($ret))
			$this->events_page_url = $ret;
	}
	
	function has_content()
	{
		if(!empty($this->events))
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
		$str = '';
		foreach($this->events as $event)
		{
			$str .= '<li>';
			$str .= '<a href="'.$this->events_page_url.'?event_id='.$event->id().'">'.$event->get_value('name').'</a>';
			$str .= '</li>';
		}
		$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		$content = ($str) ? '<ul>'.$str.'</ul>' : '';
		return $title."\n".$content;
	}
}
?>
