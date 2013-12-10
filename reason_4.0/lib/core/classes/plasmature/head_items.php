<?php
/**
 * This plasmature type provides an interface for creating structured head items & storing the
 * information as JSON.
 *
 * @package reason
 * @subpackage classes
 */
include_once( DISCO_INC.'plasmature/plasmature.php' ); 
 
class head_itemsType extends defaultType
{
	var $max_head_items = 10;
	/**
	 * Returns the markup for this element.
	 * @return string HTML to display this element.
	 */
	function get_display()
	{
		$ret = '';
		$values = $this->get_decoded_value();
		for($i=1; $i <= $this->max_head_items; $i++)
		{
			$type = 'css';
			if(!empty($values->$i->type))
				$type = $values->$i->type;
			$url = '';
			if(!empty($values->$i->url))
				$url = $values->$i->url;
			$base_name = $this->name.'_'.$i;
			$ret .= '<div class="headItem">';
			$ret .= '<select name="'.$base_name.'_type'.'" class="headItemType">';
			$selected = 'css' == $type ? ' selected="selected"' : '';
			$ret .= '<option value="css" '.$selected.'>CSS</option>';
			$selected = 'js' == $type ? ' selected="selected"' : '';
			$ret .= '<option value="js" '.$selected.'>Javascript</option></select> ';
			$ret .= '<input type="text" name="'.$base_name.'_url'.'" value="'.htmlspecialchars($url, ENT_QUOTES).'" size="50"  class="headItemUrl" />';
			$ret .= '</div>'."\n";
		}
		return $ret;
	}
	/**
	 * Finds the value of this element from userland (in {@link _request}) and returns it
	 * @return mixed array, integer, or string if available, otherwise NULL if no value from userland
	 */
	function grab_value()
	{
		$http_vars = $this->get_request();
		$ret = array();
		$actual_num = 0;
		for($i=1; $i <= $this->max_head_items; $i++)
		{
			$base_name = $this->name.'_'.$i;
			if ( !empty( $http_vars[ $base_name.'_url' ] ) )
			{
				$actual_num++;
				$ret[$actual_num] = array();
				$ret[$actual_num]['type'] = $http_vars[ $base_name.'_type' ];
				$ret[$actual_num]['url'] = $http_vars[ $base_name.'_url' ];
			}
		}
		if(!empty($ret))
			return json_encode($ret);
		return NULL;
	}
	
	function get_decoded_value()
	{
		$val = $this->get();
		if(empty($val))
			return array();
		return json_decode($val);
	}
}


?>
