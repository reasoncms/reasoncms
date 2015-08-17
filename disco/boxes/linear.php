<?php
	include_once( 'paths.php');
	include_once( CARL_UTIL_INC . 'basic/date_funcs.php' );

/**
* Linear box class for simple forms.
* 
* This box class spits out elements one after the other - suitable for very simple forms
* such as a drop-down and go button.
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
*/
	class LinearBox extends Box // {{{
	{
		/**
		* Begins the form table.
		*/
		function head() // {{{
		{
			echo '<div id="discoLinear">'."\n";
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
			
			$markup = '<span ';
			if($error) 
				$markup .= 'class="error" ';
			 if(!empty($id)) 
			 	$markup .= 'id="'.$id.'Item"';
			$markup .= '>'."\n";
			$markup .= '<span class="words">';
			// drop in a named anchor for error jumping
			$markup .= '<a name="'.$key.'_error"></a>'."\n";
			if($use_label)
			{
				if(!empty($stripped_label)) 
					$markup  .= $label.$label_punct;
				if($required) 
					$markup .= '*';
			}
			$markup .= '</span>'."\n";
			echo $markup;	
		}
		
		function box_item_close()
		{
			echo '</span>'."\n";
		}
		
		function box_item_no_label($content, $error, $key)
		{
			if (!empty($key)) $id = str_replace("_", "", $key);
			$markup = '<span ';
			if($error) 
				$markup .= 'class="error" '; 
			if(!empty($id)) 
				$markup .= 'id="'.$id.'Item"';
			$markup .= '>'."\n";
			$markup .= $content."\n";
			$markup .= '</span>'."\n"."\n";
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
			echo $markup;
		}
	} // }}}

?>
