<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * register pagination displayer with reason & include parent class
 */
$GLOBALS['_reason_pagination_displayers'][basename(__FILE__)] = 'nextPrevPaginationDisplay';
reason_include_once('minisite_templates/modules/pagination_displayers/default.php');
 
/**
 * A pagination displayer with very simple next/previous markup.
 *
 * @author Nathan White
 * @date 2007-03-08
 */
class nextPrevPaginationDisplay extends defaultPaginationDisplay
{
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
			$out[] = '<a href="'.$this->pages[$prev_page]['url'].'" title="'.$this->previous_item_text.'" class="previous">&#171; '.$this->previous_item_text.'</a>';
		}
		$next_page = $this->current_page+1;
		if(array_key_exists($next_page,$this->pages) && array_key_exists($prev_page,$this->pages))
		{
			$out[] = ' | ';
		}
		if(array_key_exists($next_page,$this->pages))
		{
			$out[] = '<a href="'.$this->pages[$next_page]['url'].'" title="'.$this->next_item_text.'" class="previous">'.$this->next_item_text.' &#187;</a>';
		}
		return implode(' ', $out);
	}
}
?>