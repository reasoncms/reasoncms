<?php
/**
 * List Filter Display
 * @package reason
 * @subpackage filter_displayers
 */
/**
 * Include parent class & register filter displayer with Reason
 */
reason_include_once('minisite_templates/modules/filter_displayers/default.php');
$GLOBALS['_reason_filter_displayers'][basename(__FILE__)] = 'listNavFilterDisplay';

/**
 * A filter displayer that lists items as links rather than using select elements
 */
class listNavFilterDisplay extends defaultFilterDisplay
{
	/**
	 * Avoid adding unnecessary javascript
	 * @access public
	 */
	function set_head_items()
	{
		// intentionally left blank
	}
	/**
	 * Assemble the markup for the relationship filtering interface
	 * @return string
	 * @access private
	 *
	 * This function may be overloaded in extensions of this class to provide different markup and/or interface for the relationship filtering feature
	 */
	function _build_filter_interface()
	{
		$ret = '';
		if(!empty($this->filter_types) && !empty($this->filter_entities))
		{
			$ret .= $this->_build_filter_set(1);
		}
		return $ret;
	}
	/**
	 * Assemble the markup for a particular filter selector
	 * @return string
	 * @access private
	 */
	function _build_filter_set($key)
	{
		$ret = '';
		$other_filter_links = $this->default_links;
		unset($other_filter_links[$key]);
		$combined_other_filter_links = implode('&amp;',$other_filter_links);
	
		$ret .= '<div class="filters">';
		foreach($this->filter_types as $filter_name=>$filter_type)
		{
			if(!empty($this->filter_entities[$filter_name]))
			{
				if($type_id = id_of($filter_type['type']))
				{
					$type = new entity($type_id);
					$name = $type->get_value('plural_name');
				}
				else
				{
					$name = prettify_string($filter_name);
				}
				$ret .= '<h4>'.$name.'</h4>'."\n";
				$ret .= '<ul>';
				
				if(!empty($this->filters[$key]))
				{
					$link = '?';
					if(!empty($this->search_value))
						$link .= 'search='.urlencode($this->search_value).'&amp;';
					if(!empty($other_filter_links))
						$link .= $combined_other_filter_links;
					$ret .= '<li><a href="'.$link.'">All</a></li>'."\n";
				}
				foreach($this->filter_entities[$filter_name] as $entity)
				{
					$ret .= '<li>';
					if(!empty($this->filters[$key]) && $this->filters[$key]['type'] == $filter_name && $this->filters[$key]['id'] == $entity->id())
					{
						$ret .= '<strong>'.$entity->get_value('name').'</strong>'."\n";
					}
					else
					{
						$link = '?';
						if(!empty($other_filter_links))
							$link .= $combined_other_filter_links.'&amp;';
						if(!empty($this->search_value))
							$link .= 'search='.urlencode($this->search_value).'&amp;';
						$link .= 'filter'.$key.'='.$filter_name.'-'.$entity->id();
						if (!empty($this->textonly))
							$link .= '&amp;textonly=1';
						$ret .= '<a href="'.$link.'">'.$entity->get_value('name').'</a>'."\n";
					}
					$ret .= '</li>';
				}
				$ret .= '</ul>';
			}
		}
		$ret .= '</div>'."\n";
		return $ret;
	}
}
?>
