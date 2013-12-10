<?php
/**
 * Default Pagination Display
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * register pagination displayer with Reason & include parent class
 */
$GLOBALS['_reason_pagination_displayers'][basename(__FILE__)] = 'windowPaginationDisplay';
reason_include_once('minisite_templates/modules/pagination_displayers/default.php');
 
/**
 * Displays pagination that only shows a "window" of pages arounf the current page
 *
 * This pagination class can handle large numbers of pages a little more gracefully
 * than the default, which lists every single page. This lists first, last and 5 before
 * and after the current page.
 *
 * @author Matt Ryan <mryan@acs.carleton.edu>
 * @date 2006-12-04
 */
class windowPaginationDisplay extends defaultPaginationDisplay
{
	var $window_size = 5;
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
			$out[] = '<a href="'.$this->pages[$prev_page]['url'].'" title="'.$this->previous_item_text.'" class="previous">&lt;&lt; '.$this->previous_item_text.'</a>';
		}
		$out[] = '<span class="pages">';
		reset($this->pages);
		$first = key($this->pages);
		end($this->pages);
		$last = key($this->pages);
		
		$window_start = $this->current_page - $this->window_size;
		if(!array_key_exists($window_start, $this->pages))
		{
			$window_start = $first;
		}
		$window_end = $this->current_page + $this->window_size;
		if(!array_key_exists($window_end, $this->pages))
		{
			$window_end = $last;
		}
		
		if($first < $window_start)
		{
			$out[] = $this->_build_page_markup($first,$this->pages[$first]);
			if($first < $window_start-1)
				$out[] = '<span class="ellipsis">…</span>';
		}
		
		for($i = $window_start; $i <= $window_end; $i++)
		{
			if(array_key_exists($i, $this->pages))
				$out[] = $this->_build_page_markup($i,$this->pages[$i]);
		}
		
		if($last > $window_end)
		{
			if($last > $window_end+1)
				$out[] = '<span class="ellipsis">…</span>';
			$out[] = $this->_build_page_markup($last,$this->pages[$last]);
		}
		$out[] = '</span>';
		$next_page = $this->current_page+1;
		if(array_key_exists($next_page,$this->pages))
		{
			$out[] = '<a href="'.$this->pages[$next_page]['url'].'" title="'.$this->next_item_text.'" class="next">'.$this->next_item_text.' &gt;&gt;</a>';
		}
		return implode(' ', $out);
	}
}
?>