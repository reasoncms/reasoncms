<?
include_once( 'submodule.php');

$GLOBALS[ '_submodule_class_names' ][ basename( __FILE__, '.php' ) ] = 'news_content';

class news_content extends submodule
{
	var $item;
	var $params = array('date_format'=>'j F Y');
	function init($request, $news_item)
	{
		parent::init($request);
		$this->item = $news_item;
	}
	function has_content()
	{
		if($this->item->get_value('content') || $this->item->get_value('description'))
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
		$ret = '';
		if( $this->item->get_value( 'datetime' ) )
		{
			$ret .= '<p class="date">'.prettify_mysql_datetime($this->item->get_value( 'datetime' ), $this->params['date_format']).'</p>';
		}
		if( $this->item->get_value( 'author' ) )
		{
			$ret .= '<p class="author">By '.$this->item->get_value( 'author' ).'</p>';
		}
		if( $this->item->get_value( 'content' ) )
		{
			$ret .= str_replace(array('<h3>','</h3>'), array('<h4>','</h4>'), $this->item->get_value( 'content' ) );
		}
		else
		{
			$ret .= $this->item->get_value('description');
		}
		return $ret;
	}
}
?>