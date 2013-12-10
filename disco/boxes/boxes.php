<?php
	include_once( 'paths.php');
	include_once( CARL_UTIL_INC . 'basic/date_funcs.php' );
	include_once( DISCO_INC . 'boxes/stacked.php' );

/**
* Simple box class to facilitate quick creation of good-looking forms.
* 
* This box class uses a table to display the elements of a form.  In general, this table uses two columns to display 
* each element; the first column displays the label (referred to as a display name in Disco) for the element, and the
* second displays the markup for the input element itself.  Elements may also be displayed as "spanning" elements. 
* Spanning elements are not labelled and span more than one column of the table.
*
* <code>
* //To start a box class:
* $box = new Box;
* $box->head();
* //To display a normal (two-column) element:
* $box->row($label, $content, $required, $error, $key);
* //Alternative way to display a two-column element:
* $box->row_open($label, $required, $error, $key);
* echo 'Content';
* $box->row_close();
* //To display a spanning element:
* $box->row_text_span($content, $colspan, $error, $key);
* //To end the box class:
* $box->foot( $buttons );
* </code>
*
* Basic Structure:
* Box
* |---Head
* |	`---Title
* |---Body
* |	`---Rows
* |		|---Open
* |		|	`---Label
* |		|---Content
* |		`---Close
* `---Foot
* `---Buttons
* 	
* @author Dave Hendler
* @package disco
* @subpackage boxes
*
* @todo Modify the class so that spanning elements can have labels and indicators that they are required. 
* @todo Add requirement indicators for normal elements that have $use_label set to false
* @todo Add capability to position the display name to the top, to the left, or to the right.
* @todo Rewrite box class system and disco integration to allow for tableless forms - improved handling of spanned elements
*/
	class Box // {{{
	{
		var $_has_required_fields = false;
		
		var $_required_indicator = '<span title="required">*</span>';
		
		function has_required_fields()
		{
			$this->_has_required_fields = true;
		}
		
		function set_required_indicator($required_indicator)
		{
			$this->_required_indicator = $required_indicator;
		}
		
		function get_required_indicator()
		{
			return $this->_required_indicator;
		}
		/**
		* Begins the form table.
		*/
		function head() // {{{
		{
			echo '<table border="0" cellpadding="6" cellspacing="0" id="discoTable">'."\n";
		} // }}}
		
		/**
		* Begins a normal row of the form table and displays the label for the element in this row if $use_label is true.
		*
		* @param string $label  The string that should be used as the label/display name for this element.
		* @param boolean $required Whether or not this element is required (optional - default false)
		* @param boolean $error Whether or not this element has an error (optional - default false)
		* @param string $key The id of this row (optional)
		* @param boolean $use_label Whether or not the label should be displayed (optional - default true)
		*
		* @todo Add a way to indicate that an element is required if $use_label is false.
		*/
		function row_open( $label, $required = false, $error = false, $key = false, $use_label = true ) // {{{
		{
			$this->box_item_open( $label, $required, $error, $key, $use_label );
		} // }}}
		
		function box_item_open( $label, $required, $error, $key, $use_label )
		{
			$id = str_replace("_", "", $key);
			
			if( $use_label)
			{
				$stripped_label = preg_replace( '/&nbsp;/' , '' , $label );
				$stripped_label = trim( $stripped_label ); //if someone wants to leave an intentionally blank label, let them do so by using a nbsp as a label
				$label_punct = (substr($stripped_label, -1) == ':' || substr($stripped_label, -1) == '?') ? '' : ':';	
			}
			
			$markup = '<tr valign="top" ';
			if($error) 
				$markup .= 'class="error" ';
			 if(!empty($id)) 
			 	$markup .= 'id="'.$id.'Row"';
			$markup .= '>'."\n";
			$markup .= '<td align="right" class="words">';
			if($use_label)
			{
				if(!empty($stripped_label)) 
					$markup  .= '<span class="labelText">'.$label.$label_punct.'</span>';
				if($required) 
					$markup .= '<span class="requiredIndicator">'.$this->get_required_indicator().'</span>';
			}
			$markup .= '</td>'."\n";
			$markup .= '<td align="left" class="element">'."\n";
			echo $markup;		
		}
		
		/**
		* Closes a row of the form table.
		*/
		function row_close() // {{{
		{
			$this->box_item_close();
		} // }}}
		
		function box_item_close()
		{
			echo '</td>'."\n".'</tr>'."\n"."\n";
		}
		
		/**
		* Opens, closes, and displays the content of a normal row in the the form table.
		* Calls on {@link row_open()} and {@link row_close}.
		*
		* @param string $label  The string that should be used as the label/display name for this element.
		* @param string $content The markup for the element itself.
		* @param boolean $required Whether or not this element is required (optional - default fakse)
		* @param boolean $error Whether or not this element has an error (optional - default false)
		* @param string $key The id of this row (optional)
		* @param boolean $use_label Whether or not the label should be displayed (optional - default true)
		* @deprecated this is no longer used as far as I can tell
		*/
		function row( $label, $content, $required = false, $error = false, $key = false, $use_label = true) // {{{
		{
			$this->box_item_open( $label, $required, $error, $key, $use_label);
			echo $content;
			$this->box_item_close();
		} // }}}

		/**
		* Opens, closes, and displays the content of an element that spans more than one column of the form table.
		* @param string $content The markup for the element itself.
		* @param int $colspan The number of columns this element should span (default 2)
		* @param boolean $error Whether or not this element has an error (optional)
		* @param string $key The id of this row (optional)
		* @deprecated
		* @todo Add error when called
		*/
		function row_text_span( $content, $colspan = 2, $error = false,  $key = false ) // {{{
		{
			// trigger_error('row_text_span() is deprecated. Please use box_item_no_label() instead.');
			$this->box_item_no_label($content, $error, $key);
		} // }}}
		
		/**
		 * @deprecated
		 * @todo Add error when called
		 */
		function box_item_text_span( $content, $colspan, $error = false,  $key = false ) // {{{
		{
			// trigger_error('box_item_text_span() is deprecated. Please use box_item_no_label() instead.');
			$this->box_item_no_label($content, $error, $key);
		} // }}}
		
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
			$markup = '<tr valign="top" ';
			if($error) 
				$markup .= 'class="error" '; 
			if(!empty($id)) 
				$markup .= 'id="'.$id.'Row">'."\n";
			$markup .= '<td colspan="2" class="words">'."\n";
			$markup .= $content."\n";
			$markup .= '</td>'."\n";
			$markup .= '</tr>'."\n"."\n";
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
				if( !is_array( $buttons ) )
				{
					$tmp = $buttons;
					$buttons = array();
					$buttons[$tmp] = $tmp;
				}
				
				$markup .= '<tr id="discoSubmitRow">'."\n".'<td align="right">&nbsp;</td>'."\n".'<td align="left" class="element">'."\n";
				
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
					$markup .= '<input type="'.$type.'" name="'.$name.'" value="'.$value.'" />&nbsp;&nbsp;';
				}
				$markup .= '</td>'."\n".'</tr>'."\n";
			}
			$markup .= '</table>'."\n";
			echo $markup;
		}
	}

?>
