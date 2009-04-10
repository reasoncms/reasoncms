<?php

/**
 * @author Matt Ryan and Bedrich Rios
 * @package reason
 * @subpackage minisite_templates
 */

// include the MinisiteTemplate class
reason_include_once( 'minisite_templates/default.php' );
// this variable must be the same as the class name
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'bedrichTemplate';
	
class bedrichTemplate extends MinisiteTemplate
{
	function show_meat_tableless() // {{{
	{
		$hasSections = array();
		$blobclass = 'contains';
		foreach($this->sections as $section=>$show_function)
		{
			$has_function = 'has_'.$section.'_section';
			if($this->$has_function())
			{
				$hasSections[$section] = $show_function;
				$capsed_section_name = ucfirst($section);
				$classes[] = 'contains'.$capsed_section_name;
				$blobclass .= substr($capsed_section_name,0,3);
			}
		}
		echo '<div id="meat" class="'.implode(' ',$classes).' '.$blobclass.'">'."\n";
		foreach($hasSections as $section=>$show_function)
		{
			echo '<div id="'.$section.'">'."\n";
			$this->$show_function();
			echo '</div>'."\n";
		}
		echo '<div class="clear"></div>'."\n";
		echo '</div>'."\n";
	} // }}}
}
?>
