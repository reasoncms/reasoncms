<?php
/**
 * Default Pagination Display
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Register pagination displayer with Reason
 */
$GLOBALS['_reason_pagination_displayers'][basename(__FILE__)] = 'defaultPaginationDisplay';
 
/**
 * The default pagination markup generation class
 *
 * Takes the needed raw data from a reason module and builds html interfaces
 * for pagination
 *
 * @author Matt Ryan <mryan@acs.carleton.edu>
 * @date 2006-12-04
 */
class defaultPaginationDisplay
{
	var $current_page = 1;
	var $pages = array();
	var $previous_item_text = 'previous';
	var $next_item_text = 'next';
	var $previous_symbol = '&#x25C4;';
	var $next_symbol = '&#x25BA;';
	/**
	 * Set the current page
	 * @param integer $current_page
	 * @access public
	 * @return void
	 */
	function set_current_page($current_page)
	{
		$this->current_page = $current_page;
	}
	/**
	 * Provide information for pages
	 *
	 * Format (must be keyed on integers):
	 * array(1=>array('url'=>'/foo/bar/','text'=>'optional text of link','title'=>'optional title of link'));
	 *
	 * @param array $pages
	 * @access public
	 * @return void
	 */
	function set_pages($pages)
	{
		$this->pages = $pages;
	}
	function set_previous_item_text($text)
	{
		$this->previous_item_text = $text;
	}
	function set_next_item_text($text)
	{
		$this->next_item_text = $text;
	}
	/**
	 * Get the markup for the pagination interface
	 * @return string
	 * @access public
	 * @param void
	 */
	function get_markup()
	{
		return $this->_build_markup();
	}
	/**
	 * Assemble the markup for the pagination interface
	 *
	 * This function may be overloaded in extensions of this class to provide different markup and/or interface for the pagination feature
	 * @return string
	 * @access private
	 */
	function _build_markup()
	{
		$out = array();
		$prev_page = $this->current_page-1;
		if(array_key_exists($prev_page,$this->pages))
		{
			$out[] = '<a href="'.$this->pages[$prev_page]['url'].'" title="'.$this->previous_item_text.'" class="previous">'.$this->previous_symbol.'</a>';
		}
		$out[] = '<span class="pages">';
		foreach($this->pages as $i=>$info)
		{
			$out[] = $this->_build_page_markup($i,$info);
		}
		$out[] = '</span>';
		$next_page = $this->current_page+1;
		if(array_key_exists($next_page,$this->pages))
		{
			$out[] = '<a href="'.$this->pages[$next_page]['url'].'" title="'.$this->next_item_text.'" class="next">'.$this->next_symbol.'</a>';
		}
		return implode(' ', $out);
	}
	
	function _build_page_markup($page,$info)
	{
		if(!empty($info['title']))
			$title = $info['title'];
		else
			$title = 'Page '.$page;
		if(!empty($info['text']))
			$text = $info['text'];
		else
			$text = $page;
		if($page == $this->current_page)
		{
			return '<strong>'.$text.'</strong>';
		}
		else
		{
			return '<a href="'.$info['url'].'" title="'.$title.'">'.$text.'</a>';
		}
	}
}
?>
