<?php
/**
 * Filter Display with Gallery2-Specific features
 * @package reason
 * @subpackage filter_displayers
 */
/**
 * Include parent class & register filter displayer with Reason
 */
reason_include_once('minisite_templates/modules/filter_displayers/default.php');
$GLOBALS['_reason_filter_displayers'][basename(__FILE__)] = 'galleryFilterDisplay';
 
/**
 * Gallery specific filter displayer
 *
 * @author Ben Cochran
 * @date 2007-04-16
 */
class galleryFilterDisplay extends defaultFilterDisplay
{
	/**
	 * The total number of items in the gallery
	 * @var integer
	 * @access private
	 */
	var $num_before_filter;
	
	/**
	 * The number of items in the gallery 
	 * after searching/filtering is applied
	 * @var integer
	 * @access private
	 */
	var $num_after_filter;
	
	function get_filter_numbers()
	{
		if (!empty($this->module_ref))
		{
			if (method_exists($this->module_ref,'get_total_num_images_after_user_input') &&
				method_exists($this->module_ref,'get_total_num_images_before_user_input'))
			{
				if (($this->num_after_filter = $this->module_ref->get_total_num_images_after_user_input()) &&
					($this->num_before_filter = $this->module_ref->get_total_num_images_before_user_input()))
				{
					return true;
				}
			}
		}
		return false;
	}
	
	function show_search()
	{
		if (!empty($this->module_ref))
		{
			if (method_exists($this->module_ref,'show_search'))
			{
				if ($this->module_ref->show_search())
				{
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Get the markup for the freetext search interface
	 * @return string
	 * @access public
	 * @param void
	 */
	function get_search_interface()
	{
		if ($this->show_search())
			return $this->_build_search_interface();
	}
	
	/**
	 * Assemble the markup for the freetext searching interface
	 *
	 * This function may be overloaded in extensions of this class to provide different markup and/or interface for the search feature
	 * @return string
	 * @access private
	 */
	function _build_search_interface()
	{
		if(!empty($this->search_value))
			$v = htmlspecialchars( $this->search_value, ENT_COMPAT, 'UTF-8');
		else
			$v = '';
		$ret = '';
		$ret .= '<div id="searchWrapper">'."\n";
		$ret .= '<form method="get">'."\n";
		foreach($this->filters as $key=>$vals)
		{
			$ret .= '<input type="hidden" name="filters['.$key.'][type]" value="'.$vals['type'].'">';
			$ret .= '<input type="hidden" name="filters['.$key.'][id]" value="'.$vals['id'].'">';
		}
		$ret .= '<label for="gallerySearchField" class="searchLabel">Search</label><span class="colon">:</span> <input name="search" value="'.$v.'" size="'.$this->search_field_size.'" id="gallerySearchField" type="text" />'."\n";
		$ret .= ' <input name="go" type="submit" value="Go">'."\n";
		if(!empty($this->search_value))
		{
			$link = '?';
			if(!empty($this->default_links))
				$link .= implode('&amp;', $this->default_links);
			$ret .= ' <a href="'.$link.'" title="Remove this search term">Remove</a>'."\n";
			if ($this->get_filter_numbers() && !empty($this->num_after_filter) && !empty($this->num_before_filter))
			{
				$ret .= '<span class="howManyFiltered">(Found ' . $this->num_after_filter . ' image';
				if ($this->num_after_filter > 1) $ret .= 's';
				$ret .= ' out of ' . $this->num_before_filter . ')</span>'."\n";
			}
		}
		$ret .= '</form>'."\n";
		$ret .= '</div>'."\n";
		return $ret;
	}
}
?>