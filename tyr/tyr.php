<?php
/**
 * A class designed to format data and email it
 * @package tyr
 */

/**
 * Include general paths info
 */
include_once('paths.php');
/**
 * Include the tyr settings
 */
include_once(SETTINGS_INC.'tyr_settings.php');

/**
 * Include a debugging helper
 */
include_once(CARL_UTIL_INC.'dev/prp.php');

/**
 * Include the emailer class
 */
include_once('email.php');

/**
 * A class designed to format data and email it
 *
 * Helpful for form submission data, among other things
 *
 * "When Thor and Tyr traveled to the giant Hymir hall to brew ale for the gods, ..."
 *
 * Example usage: 
 * <code>
 *   $tyr = new Tyr($_POST['messages'], $_POST);
 *   $tyr->run();
 *   $tyr->finish();
 * </code>
 *
 * See Thor for more information
 */
class Tyr
{
	var $_messages = Array(); // Array of message arrays--i.e., directive as to how to format Emails
	var $_fields = Array(); // Array of fields--i.e., data to use in Emails
	var $admin_email = TYR_ADMIN_EMAIL;
	var $add_disclaimer = true;

	function Tyr($messages = Array(), $fields = Array())
	{
		$this->set_messages($messages);
		$this->set_fields($fields);
	}

	function set_messages($messages)
	{
// 		prp($messages, 'messages');
		$this->_messages = $messages;
	}

	function set_fields($fields)
	{
		$this->_fields = $fields;

		// Copy deprecated fields into corresponding fields in $this->_messages
		if ( !empty($_REQUEST['MAILTO']) )
		{
			$this->_messages[uniqid('deprecated_mailto_')] =
				 Array( 'to' => $_REQUEST['MAILTO'] . "," . $_REQUEST['CCTO'],
						'from' => $_REQUEST['02_Email'],
						'subject' => $_REQUEST['FORMNAME'] );
		}


		unset($this->_fields['messages'],
			  $this->_fields['PHPSESSID'],
			  $this->_fields['MAILTO'],
			  $this->_fields['CCTO'],
			  $this->_fields['Submit'],
			  $this->_fields['submitted']);
	}

	function run()
	{
		$this->_check_for_messages();
		$this->_format_messages();
		$this->_make_bodies();
		$this->_make_subjects();
		$this->_make_froms();
		$this->_send_messages();
	}

	function finish()
	{
// 		if ( empty($this->_messages['all']['no_thankyou']) )
// 		{
// 		prp($this->_fields, 'tyr->_fields');
// 		prp($this->_messages, 'tyr->_messages');
			if ( !empty($this->_messages['all']['next_page']) )
			{
				header( 'Location: ' . $this->_messages['all']['next_page'] );
				die();
			}
			else
			{
				$this->_echo_thankyou();
			}
// 		}
	}

	// Dies if there aren't any sendable messages
	function _check_for_messages()
	{
		if ( !is_array($this->_messages) )
			die( 'Tyr: I don\'t have any messages to send! ($messages not an array)' );

		$a_message_exists = false;
		foreach ($this->_messages as $mkey => $message)
		{
			if ( $mkey !== 'all' )
			{
				if ( empty($message['to']) )
					die( 'Tyr: You\'re trying to send a message to no one! I can\'t do that.' );
				else {
					$a_message_exists = true;}
			}
		}
		if ( !$a_message_exists )
			die( 'Tyr: I don\'t have any messages to send! ($messages an array)' );
	}

	// Replace all %s 's in $raw_message['format'] with their
	// corresponding fields from $raw_message['fields'], and copy the
	// result into $formatted_messages_field.
	//    N.B.: At time of writing, $do_urlencode is set to true only for next_page
	function _format_field($raw, $do_urlencode)
	{
		if ( is_array($raw) && !empty($raw['format']) && !empty($raw['fields']) )
		{
			$fields_array = explode(',', $raw['fields']);
			foreach ( $fields_array as $field_name )
			{
				if ( $do_urlencode )
					$raw['format'] = preg_replace( '/\%s/', urlencode($_REQUEST[trim($field_name)]), $raw['format'], 1 );
				else
					$raw['format'] = preg_replace( '/\%s/', $_REQUEST[trim($field_name)], $raw['format'], 1 );					
			}
			return $raw['format'];

		}
		elseif ( is_array($raw) && !empty($raw['format']) )
			return $raw['format'];
		elseif ( is_array($raw) )
			return '';
		else
			return $raw;
	}

	// This function formats each message, by replacing %s's and \n's
	function _format_messages()
	{
		foreach ($this->_messages as $mkey => $message)
		{
			foreach ($message as $fkey => $field)
			{
				// 1. Replace all %s 's in the field['format'] with
				// their corresponding fields from field['fields'].
				// If this field key is 'next_page', urlencode() the
				// value of each field denoted by a %s before
				// replacing.
				if ( $fkey == 'next_page' )
					$field = $this->_format_field($field, true);
				if ( $fkey == 'attachments' )
					continue;
				else
					$field = $this->_format_field($field, false);

				// 2. Replace each literal '\n' with an actual new line
				$field = str_replace( '\n', "\n", $field );

				// 3. Copy the field back into $this->_messages
				$this->_messages[$mkey][$fkey] = $field;
			}
		}
	}

	function _make_body($add_disclaimer = true)
	{
		$message = '';
		
		$hide_empty_values = false;
		if(!empty($this->_messages['all']['hide_empty_values']) )
		{
			$hide_empty_values = true;
		}
		if(!empty($this->_messages['all']['alpha_sort']) )
		{
			ksort($this->_fields);
		}
		
		if(!empty($this->_messages['all']['form_origin_link']) )
		{
			$message .= "\n\nThe form was submitted from this web address:\n\n".$this->_messages['all']['form_origin_link']."\n\n";
		}
		
		if(!empty($this->_messages['all']['form_access_link']) )
		{
			$message .= "\n\nYou can access this form submission online at:\n\n".$this->_messages['all']['form_access_link']."\n\n";
		}
		
		if (!empty($this->_fields))
		{
			foreach ( $this->_fields as $key => $value )
			{
				// Replace key with value[label], and value with value[value]
				//   N.B.: We do this here, rather than earlier, because $value['label']
				//   doesn't have to be unique, but the key of an associative array
				//   such as $this->_fields would have to be unique.
				if ( is_array($value) && array_key_exists('label', $value) )
				{
					$key = $value['label'];
					$value = ( array_key_exists('value', $value) ) ? $value['value'] : '';
				}	
	
				// Write out arrays
				if ( is_array($value) )
				{
					$new_value = '';
					foreach ( $value as $sub_key => $sub_value )
					{
						if ( is_int($sub_key) )
							$new_value .= '    ' . $sub_value . "\n";
						else
							$new_value .= '    ' . $sub_key . "\n    " . $sub_value . "\n";
					}
					$value = $new_value;
				}
				$show_item = true;
				if(trim($value) == '')
				{
					if($hide_empty_values)
					{
						$show_item = false;
					}
					else
					{
						$value = '(no value)';
					}
				}
				//$value = ( trim($value) == '' ) ? '(no value)' : $value;
				if($show_item)
				{
					$value = str_replace( "\'", "'", $value );
					$key = str_replace( '_', " ", $key );
					$key = $key . ( (substr($key, -1) != ':' && substr($key, -1) != '?') ? ':' : '' );
					
					$message .= "\n__________________________________\n\n" . $key . "\n" . $value;
				}
	
			}
		}	
		
		if ( $add_disclaimer )
		{
			if (isset($_SERVER['SERVER_NAME']) && isset($_SERVER['REQUEST_URI']))
				$where = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
			else
				$where = "the ".FULL_ORGANIZATION_NAME." website";
			$message .= "\n\n\n\n-*-*-*-*-*-*-*-*-*-*-*-*-*-*\n\nThis message was automatically generated when someone submitted a form to you from ".$where.". Please direct problems or questions about this service to ".$this->admin_email." .\n";
		}
		return $message;
	}

	// At time of writing, the $add_disclaimer flag is only set to false in order to generate the thank-you note
	function _make_html_body($add_disclaimer = true)
	{
		$message = '<html><head>';
		$message .= '<title>';
		if ( !empty($this->_messages['all']['form_title']) )
			$message .= htmlspecialchars( $this->_messages['all']['form_title'], ENT_COMPAT, 'UTF-8' );
		else
			$message .= 'Auto-generated form response';
		$message .= '</title>';
		$message .= '</head><body>'."\n";
		
		$hide_empty_values = false;
		if(!empty($this->_messages['all']['hide_empty_values']) )
		{
			$hide_empty_values = true;
		}
	
		if ( !empty($this->_messages['all']['form_title']) )
		{
			$message .= '<h2>' . htmlspecialchars( $this->_messages['all']['form_title'], ENT_COMPAT, 'UTF-8' ) . '</h2>'."\n";
		}

		if(!empty($this->_messages['all']['form_origin_link']) )
		{
			$message .= '<p>The form was submitted from this web address:</p><p><a href="'.$this->_messages['all']['form_origin_link'].'">'.$this->_messages['all']['form_origin_link'].'</a><p>';
		}
		
		if(!empty($this->_messages['all']['form_access_link']) )
		{
			$message .= '<p>You can access this form submission online at:</p><p><a href="'.$this->_messages['all']['form_access_link'].'">'.$this->_messages['all']['form_access_link'].'</a><p>';
		}
		
		if (!empty($this->_fields)) $message .= $this->make_html_table($this->_fields, $hide_empty_values);

		if ( $add_disclaimer )
		{
			if (isset($_SERVER['SERVER_NAME']) && isset($_SERVER['REQUEST_URI']))
			{
				$html_url = 'http://'.htmlspecialchars($_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
				$where = ' at <a href="'.$html_url.'">'.$html_url.'</a>';
			}
			else
				$where = '';
			$message .= '<p>This message was automatically generated when someone submitted a form to you from the '.FULL_ORGANIZATION_NAME.' website'.$where.'. Please direct problems or questions about this service to <a href="mailto:'.$this->admin_email.'">'.$this->admin_email.'</a>.</p>'."\n";
		}
		$message .= '</body></html>'."\n";
	
		return $message;
	}
	
	function make_html_table($values, $hide_empty_values)
	{
		$message = "<table border='0' cellspacing='0' cellpadding='7'>\n";

		foreach ( $values as $key => $value )
		{
			// Replace key with value[label], and value with value[value]
			//   N.B.: We do this here, rather than earlier, because $value['label']
			//   doesn't have to be unique, but the key of an associative array
			//   such as $this->_fields would have to be unique.
			if ( is_array($value) && array_key_exists('label', $value) )
			{
				$key = $value['label'];
				$value = ( array_key_exists('value', $value) ) ? $value['value'] : '';
			}

			// Write out arrays
			if ( is_array($value) )
			{
				$new_value = '';
				foreach ( $value as $sub_key => $sub_value )
				{
					if ( is_int($sub_key) )
						$new_value .= $sub_value . "\n";
					else
						$new_value .= $sub_key . ": " . $sub_value . "\n";
				}
				$value = $new_value;
			}
			
			$value = htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
				
			$show_item = true;
			
			if (trim($value == ''))
			{
				if($hide_empty_values)
				{
					$show_item = false;
				}
				else
				{
					$value = '(no value)';
				}
			}
			if($show_item)
			{
				$value = str_replace( "\n", "<br />\n", $value );
				$value = str_replace( "\'", "'", $value );
				$key = str_replace( '_', " ", $key );
				$key = $key . ( (substr($key, -1) != ':' && substr($key, -1) != '?') ? ':' : '' );
				$message .= "<tr><td align='right' valign='top'><strong>" . htmlspecialchars( $key, ENT_COMPAT, 'UTF-8' ) . " </strong></td><td valign='top'>" . $value . "<br /></td></tr>\n";
			}
			
		}
		$message .= "</table>\n";
		return $message;
	}

	function _make_bodies() {
		foreach ($this->_messages as $mkey => $message)
		{
			if ($mkey !== 'all' && empty($message['body']))
			{
				$this->_messages[$mkey]['body'] = $this->_make_body($this->add_disclaimer);
				$this->_messages[$mkey]['html_body'] = $this->_make_html_body($this->add_disclaimer);
			}
		}
	}

	function _make_froms()
	{
		foreach ($this->_messages as $mkey => $message)
		{
			if ( $mkey !== 'all' )
			{
				if ( empty($message['from']) )
				{
					$this->_messages[$mkey]['from'] = TYR_REPLY_TO_EMAIL_ADDRESS;
				}
			}
		}
	}

	function _make_subjects()
	{
		foreach ($this->_messages as $mkey => $message)
		{
			if ( $mkey !== 'all' )
			{
				if ( empty($message['subject']) )
				{
					$this->_messages[$mkey]['subject'] = 'Response to form' .
						 (empty($this->_messages['all']['form_title']) ? '' : ': ' . $this->_messages['all']['form_title']) .
						 "\n";
				}
			}
		}
	}

	function _send_messages()
	{
		foreach ($this->_messages as $mkey => $message)
		{
			if ( $mkey !== 'all' )
			{
				$email = new Email( empty($message['to']) ? '' : $message['to'],
									empty($message['from']) ? '' : $message['from'],
									empty($message['reply-to']) ? '' : $message['reply-to'],
									empty($message['subject']) ? '' : $message['subject'],
									empty($message['body']) ? '' : $message['body'],
									empty($message['html_body']) ? '' : $message['html_body'],
									empty($message['address_types']) ? 'mixed' : $message['address_types'],
									empty($message['attachments']) ? null : $message['attachments']);
				$email->send();
// 				prp('just tyr::_send_messages, to' . $message['to']);
			}
		}
	}

	function _echo_thankyou()
	{
		$data = $this->_make_html_body(false);
		include(TYR_THANKYOU_TEMPLATE_PATH);
	 }

}

?>
