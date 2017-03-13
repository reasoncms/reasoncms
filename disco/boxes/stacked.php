<?php
/** 
 * @package disco
 * @subpackage boxes
 */

/**
 * Include dependencies
 */
include_once( 'paths.php');
include_once( CARL_UTIL_INC . 'basic/date_funcs.php' );
include_once( DISCO_INC . 'boxes/boxes.php' );

/**
* Stacked Box class that does not use tables for overall layout
*/
class StackedBox extends Box // {{{
{
	/**
	* Begins the form table.
	*/
	function head() // {{{
	{
		echo '<div id="discoLinear">'."\n";
	} // }}}
	
	function box_item_open( $label, $required, $error, $key, $use_label, $label_target_id = false )
	{
		$id = str_replace("_", "", $key);
		
		if( $use_label)
		{
			$stripped_label = preg_replace( '/&nbsp;/' , '' , $label );
			$stripped_label = trim( $stripped_label ); //if someone wants to leave an intentionally blank label, let them do so by using a nbsp as a label
			//$label_punct = (substr($stripped_label, -1) == ':' || substr($stripped_label, -1) == '?') ? '' : ':';	
		}
		
		$markup = '<div class="formElement';
		if($error) 
			$markup .= ' error';
		$markup .= '"';
		 if(!empty($id)) 
			$markup .= ' id="'.$id.'Item"';
		$markup .= '>'."\n";
		// drop in a named anchor for error jumping
		$markup .= '<a name="'.$key.'_error"></a>'."\n";
		$markup .= '<div class="words">';
		if($use_label)
		{
			if(!empty($label_target_id))
				$markup .= '<label for="'.htmlspecialchars($label_target_id).'">';
			if(!empty($stripped_label)) 
				$markup  .= '<span class="labelText">'.$label.'</span>';
			if($required) 
				$markup .= '<span class="requiredIndicator">'.$this->get_required_indicator().'</span>';
			if(!empty($label_target_id))
				$markup .= '</label>';
		}
		$markup .= '</div>'."\n";
		$markup .= '<div class="element">'."\n";
		echo $markup;	
	}
	
	function box_item_close()
	{
		echo '</div>'."\n";
		echo '</div>'."\n";
	}
	
	/**
	 * Produce a box item without a label
	 *
	 * This method replaces row_text_span() and box_item_text_span()
	 *
	 * @param string $content The markup for the element
	 * @param boolean $error Does this element have an error?
	 * @param string $key The key for the element
	 */
	function box_item_no_label($content, $error, $key)
	{
		if (!empty($key))
			$id = str_replace("_", "", $key);
		
		$markup = '<div class="formElement noLabel';
		if($error) 
			$markup .= ' error';
		$markup .= '"';
		
		if(!empty($id)) 
			$markup .= ' id="'.$id.'Item"';
		$markup .= '>'."\n";
		$markup .= $content."\n";
		$markup .= '</div>'."\n"."\n";
		echo $markup;
	}
	
	/**
	* Displays the buttons for this form and closes the form table.
	* @param array $buttons The names and values for the buttons of this table ($name => $value)
	*/
	function foot( $buttons = '' ) // {{{
	{
		$markup = ''; // init markup so no error is thrown in no buttons are present
		if ( $buttons )
		{
			$markup .= '<div class="submitSection">'."\n";
			if( !is_array( $buttons ) )
			{
				$tmp = $buttons;
				$buttons = array();
				$buttons[$tmp] = $tmp;
			}
			foreach($buttons as $name => $value)
			{
				if($name == '__button_reset')
				{
					$type = 'reset';
				}
				else
				{
					$type = 'submit';
				}
				$markup .= '<input type="'.$type.'" name="'.$name.'" value=" '.$value.' " />&nbsp;&nbsp;';
			}
		}
		$markup .= '</div>';
		$markup .= '</div>';
		echo $markup;
	}
}
?>
