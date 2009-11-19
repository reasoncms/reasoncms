<?php
/**
 *	Class for managing head items
 *	@package reason
 *	@subpackage classes
 */

/**
 *	Inputs and outputs head items.
 * 
 *  Methods:
 *  - add head items of various types
 *  - selectively remove head items whether they exist or not when method is called
 *  - output html markup of head items
 *
 *  Sample usage
 *
 *  <code>
 *  	$head_item = new HeadItems();
 *		$head_item->add_stylesheet('mycss.css');
 *		$head_html = $head_item->get_head_items_html();
 *  </code>
 *
 *  @author Nathan White and the author(s) of functions that I lifted from the default template
 */
class HeadItems
{
	/**
	 * @var array _head_items
	 * @access private
	 */
	var $_head_items = array();

	/**
	 * @var array _top_head_items
	 * @access private
	 */
	var $_top_head_items = array();
	
	/**
	 * @var array _to_remove
	 * @access private
	 */
	var $_to_remove = array();
	
	var $allowable_elements = array('base','link','meta','script','style','title');
	var $elements_that_may_have_content = array('meta','script','style','title');
	var	$elements_that_may_not_self_close = array('script','title');
	
	function HeadItems()
	{
	}
	
	/**
	 * Adds a head item to the internal head items array
	 * @param string $element name of element to add (ie. link or script)
	 * @param array $attributes element attributes
	 * @param string $content content to appear between element open and close tags
	 * @param boolean $add_to_top if true, places element at start of array rather than end
	 * 
	 */
	function add_head_item($element, $attributes, $content = '', $add_to_top = false)
	{
		$element = strtolower($element);
		if(in_array($element, $this->allowable_elements))
		{
			if (!empty($content) && (!in_array($element, $this->elements_that_may_have_content)))
			{
				trigger_error('The head item element ' . $element . ' had its content (' . $content . ') removed because it is not in the array of elements that may have content');
				$content = '';
			}
			$item = array('element'=>$element,'attributes'=>$attributes,'content'=>$content);
			if($add_to_top)
			{
				array_unshift($this->_head_items, $item);
				array_unshift($this->_top_head_items, $item); 
			}
			else
			{
				$this->_head_items[] = $item;
			}
		}
		else trigger_error('The head item element ' . $element . ' was not added because it is not in the allowable elements array');
	}
	
	/**
	 * Quick interface to add_head_item for adding stylesheets
	 * @param string $url
	 * @param string $media optional media attribute
	 * @param boolean $add_to_top
	 */
	function add_stylesheet( $url, $media = '', $add_to_top = false )
	{
		$attrs = array('rel'=>'stylesheet','type'=>'text/css','href'=>$url);
		if(!empty($media))
		{
			$attrs['media'] = $media;
		}
		$this->add_head_item('link', $attrs, '', $add_to_top);
	}

	/**
	 * Quick interface to add_head_item for adding javascript
	 * @param string $url
	 * @param boolean $add_to_top
	 */	
	function add_javascript( $url, $add_to_top = false )
	{
		$attrs = array('type' => 'text/javascript', 'src' => $url);
		$this->add_head_item('script', $attrs, '', $add_to_top);
	}
	
	/**
	 * Selectively removes head items by element type and attribute(s)
	 * @param string $element type of head item to remove
	 * @param array $attribute_limiter optional array of key / value pairs which must correspond to the attributes of an item to be deleted
	 * @return void
	 * @access private
	 * @author Nathan White
	 */
	function _remove_head_item($element, $attribute_limiter = false)
	{
		$head_items =& $this->_head_items;
		foreach ($head_items as $k=>$item)
		{
			if (strtolower($element) === $item['element'])
			{
				$diff_array = is_array($attribute_limiter) ? array_diff_assoc($attribute_limiter, $item['attributes']) : array();
				{
					if (empty($diff_array))
					{
						unset ($head_items[$k]);
						if (isset($this->_top_head_items[$k])) unset ($this->_top_head_items[$k]);
					}
				}
			}
		}
	}
	
	/**
	 * Remove head items by element type and attribute(s) just before head is displayed
	 * @param string $element type of head item to remove
	 * @param array $attribute_limiter optional array of key / value pairs which must correspond to the attributes of an item to be deleted
	 * @return void
	 * @access public
	 * @author Nathan White
	 */	
	function remove_head_item($element, $attribute_limiter = false)
	{
		$this->_to_remove[] = array('e' => $element, 'a_l' => $attribute_limiter);
	}
	
	/**
	 * @access private
	 */
	function _remove_head_items_at_end()
	{
		if (!empty($this->_to_remove))
		{
			foreach ($this->_to_remove as $v)
			{
				$this->_remove_head_item($v['e'], $v['a_l']);
			}
		}
	}
	
	/**
	 * Returns head items array
	 * @return array head items
	 */
	function &get_head_item_array()
	{
		return $this->_head_items;
	}
	
	/**
	 * Returns html for head items
	 * @return string html of head items
	 */
	function get_head_item_markup()
	{
		if (empty($this->_head_items)) return '';
		$this->_remove_head_items_at_end();
		$allowable_elements =& $this->allowable_elements;
		$elements_that_may_have_content =& $this->elements_that_may_have_content;
		$elements_that_may_not_self_close =& $this->elements_that_may_not_self_close;
		$html_items = array();
		foreach($this->_head_items as $item)
		{
			$html_item = '<'.$item['element'];
			foreach($item['attributes'] as $attr_key=>$attr_val)
			{
				$html_item .= ' '.reason_htmlspecialchars($attr_key).'="'.reason_htmlspecialchars($attr_val).'"';
			}
			if(!empty($item['content']) )
			{
				$html_item .= '>'.$item['content'].'</'.$item['element'].'>';
			}
			elseif(in_array($item['element'],$elements_that_may_not_self_close))
			{
				$html_item .= '></'.$item['element'].'>';
			}
			else
			{
				$html_item .= ' />';
			}
			$html_items[] = $html_item;
		}
		$this->handle_duplicates($html_items);
		return implode("\n",$html_items)."\n";
	}
	
	/**
	 * Modifes the html items array to remove exact duplicates - "add to top" items remain at the top when duplicates are found,
	 * while the last instance of regular items is preserved. This is important because while javascript files such as jquery
	 * may need to be at the top of the head items, when CSS duplicates are found the last instance will override rules in previous files.
	 * @param array html_items
	 * @return void
	 * @author Nathan White
	 */
	function handle_duplicates(&$html_items)
	{
		$top_head_items_count = count($this->_top_head_items);
		if ($top_head_items_count > 0)
		{
			$top_html_items = array_unique(array_slice($html_items, 0, $top_head_items_count));
			$non_top_html_items = array_diff($html_items, $top_html_items);
			$html_items = array_merge($top_html_items, $non_top_html_items);
		}
		$html_items = array_reverse(array_unique(array_reverse($html_items))); // removes duplicates - leaving only last instance of a string
	}
}
