<?php
/**
 * Filter-By-Date Filter Display
 * @package reason
 * @subpackage filter_displayers
 */
/**
 * Include the parent class & register filter displayer with Reason
 */
reason_include_once('minisite_templates/modules/filter_displayers/gallery_specific.php');
$GLOBALS['_reason_filter_displayers'][basename(__FILE__)] = 'filterByDayFilterDisplay';
 
/**
 * A filter-by-day filter markup generation class
 *
 * Takes the needed raw data from a reason module and builds html interfaces
 * for the search box and relationship filtering
 *
 * @author Ben Cochran
 * @date 2007-04-16
 */
class filterByDayFilterDisplay extends galleryFilterDisplay
{	
	// reference to the module that instanciates the filter displayer
	var $module_ref;
	
	var $dates = array();
	
	function get_date_array()
	{
		if (!empty($this->module_ref))
			if (is_a($this->module_ref,'Gallery2Module'))
				if (method_exists($this->module_ref,'get_distinct_date_array'))
					$this->dates = $this->module_ref->get_distinct_date_array();
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
		$this->get_date_array();
		
		if(!empty($this->search_value))
			$v = htmlspecialchars( $this->search_value, ENT_COMPAT, 'UTF-8');
		else
			$v = '';
		$ret = '';
		$ret .= '<form method="get">'."\n";
		foreach($this->filters as $key=>$vals)
		{
			$ret .= '<input type="hidden" name="filters['.$key.'][type]" value="'.$vals['type'].'">';
			$ret .= '<input type="hidden" name="filters['.$key.'][id]" value="'.$vals['id'].'">';
		}
		if (!empty($this->textonly))
			$ret .= '<input type="hidden" name="textonly" value="1">';
		$ret .= 'Search: <input name="search" value="'.$v.'" size="'.$this->search_field_size.'" />'."\n";
		if (!empty($this->dates))
		{
			$ret .= 'Pick a Day: <select name="search_date">'."\n";
			$ret .= '<option value="">All Dates</option>'."\n";
			foreach( $this->dates AS $dt => $display )
			{
				$ret.= '<option value="'. $dt .'"'.(!empty($this->psearch_values['date']) && $dt == $this->psearch_values['date'] ? ' selected="selected"':'').'>'.$display.'</option>'."\n";
			}
			$ret .= '</select>'."\n";
		}
		$ret .= ' <input name="go" type="submit" value="Go">'."\n";
		if(!empty($this->search_value))
		{
			$link = '?';
			if(!empty($this->default_links))
				$link .= implode('&amp;', $this->default_links);
			if (!empty($this->textonly))
				$link .= '&amp;textonly=1';
			$ret .= ' <a href="'.$link.'" title="Remove this search term">Remove</a>'."\n";
		}
		$ret .= '</form>'."\n";
		return $ret;
	}
}
?>