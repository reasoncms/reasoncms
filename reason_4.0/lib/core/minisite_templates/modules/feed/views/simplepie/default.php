<?php
/**
 * @package reason
 * @subpackage classes
 */
 
/**
 * Include the base class
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );

/**
 * Register MVC component with Reason
 */
$GLOBALS[ '_reason_mvc_view_class_names' ][ reason_basename(__FILE__) ] = 'ReasonSimplepieDefaultFeedView';

/**
 * ReasonSimplepieDefaultFeedView shows feed items and supports a few optional parameters.
 *
 * This class assumes that the models used return a SimplePie feed object.
 *
 * - num_to_show (int default 4)
 * - randomize (boolean default false)
 *
 * @author Nathan White
 */
 
class ReasonSimplepieDefaultFeedView extends ReasonMVCView
{
	var $config = array('num_to_show' => 4,
						'randomize' => false,
						'title' => NULL,
						'description' => NULL);
	
	function get()
	{
		$feed = $this->data();
		$title = (!is_null($this->config('title'))) ? $this->config('title') : $feed->get_title();
		$description = (!is_null($this->config('description'))) ? $this->config('description') : $feed->get_description();
		$str = '<h3>'.$title.'</h3>';
		$str .= '<p>'.$description.'</p>';
		if ($items = $feed->get_items())
		{
			if ($this->config('randomize')) shuffle($items);
			$str .= '<ul>';
			foreach ($items as $item)
			{
				$num = (!isset($num)) ? 1 : ($num + 1);
				$str .= '<li><a href="'.$item->get_permalink().'">'.$item->get_title().'</a></li>';
				if ($num == $this->config('num_to_show')) break;
			}
			$str .= '</ul>';
		}
		return $str;
	}
}
?>