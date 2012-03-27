<?php
/**
 * Class that produces RSS feeds from Reason data
 *
 * @package reason
 * @subpackage classes
 */

	/**
	 * Include dependencies
	 */
	include_once('reason_header.php');
	reason_include_once( 'classes/entity_selector.php' );
	include_once(CARL_UTIL_INC.'basic/date_funcs.php');
	include_once(CARL_UTIL_INC.'basic/cleanup_funcs.php');
	
	/**
	 * quick function to convert a date field into RFC 822, required by RSS 2.0
	 */
	function mysql_to_rfc_date( $date )
	{
		if ($date == "0000-00-00 00:00:00")
			return null;
		return carl_date( 'r', get_unix_timestamp( $date ) );
	}
	
	/**
	 * checks if the author field is an e-mail address and returns it if so
	 */
	function valid_rss_author( $value )
	{
		return check_against_regexp($value, array('email'));
	}

/**
 *	Reason RSS class
 *	@author dave hendler
 *	
 *
 *	Basically, a quick and dirty class to make an RSS feed from some Reason data.
 *	Things to set up:
 *		site_id - which site the data is coming from
 *		type_id - which type to use
 *		es - the es can be modified as a public data member for special queries
 *		set_channel_attribute - public method to add/set channel attributes, like language, title, link, etc
 *		set_item_field_handler - public method to set a handler for an RSS field/attribute
 *		set_item_field_map - public method to map an RSS field to a Reason field
 *			for example, if we want the <title> element of the RSS doc to be the "name" field of some type,
 *			use set_item_field_map( 'title', 'name' );
 *		set_item_field_validator - public method to attach an error checking function to an RSS field
 *
 *		finally, run get_rss() to get the rss feed
 *
 *	I apologize for some of the confusion of the wording in this class
 *
 *	Generally,
 *		channel: the RSS channel
 *		channel attributes: fields/attributes that pertain to the channel itself, not one of the items.
 *			 - so, the channel's title is a channel attribute
 *		
 *
 */
	class ReasonRSS
	{
	
		var $site_id;
		var $type_id;
		// entity selector used by ReasonRSS - public so devs can muck with it
		var $es;

		// required attributes of a channel
		var $_req_channel_attributes = array(
			'title',
			'link',
			'description',
		);

		// instance of channel attr values
		var $_channel_attr_values;

		// maps an RSS item attribute to the entity field 
		// for example, if the map 'title' => 'name' exists,
		// then the ReasonRSS object will insert the name of 
		// the entity into the title field of the RSS document
		var $_item_field_map = array(
			'title' => 'release_title',
			'description' => 'description',
			'author' => 'author',
			'pubDate' => 'datetime'
		);

		// key: RSS field
		// value: function name to munge the value from Reason
		var $_item_field_handlers = array(
			'pubDate' => 'mysql_to_rfc_date',
		);
		
		// This is slightly hacky, but it allows more full-featured use of class functions -- MR
		var $_item_field_handler_rules = array();

		// key: RSS field
		// value: function name that checks a value to see if it is valid or not
		//		function returns true if field is valid or false if it is not valid
		var $_item_field_validator = array(
			'author' => 'valid_rss_author',
		);
		
		var $bad_strings_search = array('&nbsp;');
		var $bad_strings_replace = array(' ');
		var $_custom_output_handlers = array('enclosure'=>'make_enclosure');

		// public methods
		
		function ReasonRSS( $site_id, $type_id = '' ) // {{{
		{
			$this->init( $site_id, $type_id );
		} // }}}
		function init( $site_id, $type_id = '' )
		{
			$this->site_id = $site_id;
			if(!empty( $type_id ))
				$this->type_id = $type_id;
			else
				die('No type id set');

			$this->es = new entity_selector( $this->site_id );
			$this->es->description = 'RSS news entity selector for site '.$this->site_id.' and type '.$this->type_id.' defined on or about line '.__LINE__.' in file '.__FILE__;
			$this->es->add_type( $this->type_id );
			$this->es->set_num( 15 );
			$this->es->set_order( 'last_modified DESC' );
			
		}
		function set_items( $items )
		{
			$this->items = $items;
		}
		function set_channel_attribute( $attr, $value ) // {{{
		{
			$this->_channel_attr_values[ $attr ] = $value;
		} // }}}
		function get_channel_attr( $attr ) // {{{
		{
			
			return !empty($this->_channel_attr_values[ $attr ]) ? $this->_channel_attr_values[ $attr ] : false;
		} // }}}
		function set_item_field_map( $rss_field, $reason_field ) // {{{
		{
			$this->_item_field_map[ $rss_field ] = $reason_field;
		} // }}}
		function set_item_field_handler( $field, $func, $class_variable = false ) // {{{
		{
			$this->_item_field_handlers[ $field ] = $func;
			$this->_item_field_handler_rules[ $field ]['class_variable'] = $class_variable;
		} // }}}
		function set_custom_output_handler( $field, $func ) // {{{
		{
			$this->_custom_output_handlers[ $field ] = $func;
		} // }}}
		function set_item_field_validator( $field, $func ) // {{{
		{
			$this->_item_field_validator[ $field ] = $func;
		} // }}}
		function alter_es() // {{{
		{
		} // }}}
		function get_rss() // {{{
		{
			// make sure required fields are set
			$this->_verify_values();

			// build the rss doc and store it in $this->_out
			$this->_build_rss();

			return $this->_out;
		} // }}}

		// private methods
		
		function _verify_values() // {{{
		{
			// check to make sure required fields are filled out
			foreach( $this->_req_channel_attributes AS $req )
			{
				if( !$this->get_channel_attr( $req ) )
				{
					$old_mode = error_handler_config('script_mode', true);
					trigger_error( '"'.$req.'" is a required RSS channel field.  Use set_channel_attribute( "'.$req.'", "blah" ) to set it.' );
					error_handler_config('script_mode', $old_mode);
					$this->send_internal_error();
					die();
				}
			}
		} // }}}
		function _clean_value( $val ) // {{{
		{
			// clean up values to make sure they validate in XML
			$val = trim( htmlspecialchars( $val, ENT_COMPAT, 'UTF-8' ) );
			return str_replace($this->bad_strings_search, $this->bad_strings_replace, $val);
		} // }}}
		function _get_items()
		{
			if(!isset($this->items))
			{
				$this->items = $this->es->run_one();
			}
		}
		function _build_rss() // {{{
		{
			$this->_get_items();
			//pray($this->items);
			//echo $this->es->get_one_query();

			$this->_out = '<?xml version="1.0" encoding="UTF-8"?'.'>'."\n".'<rss version="2.0">'."\n".'<channel>'."\n\n";
			foreach( $this->_channel_attr_values AS $attr => $value )
				$this->_out .= '<'.$attr.'>'.$this->_clean_value( $value ).'</'.$attr.'>'."\n";
			$this->_out .= "\n";
			
			if( !empty( $this->items ) )
			{
				foreach( $this->items AS $item )
				{
					$this->generate_item( $item );
				}
			}

			$this->_out .= '</channel>'."\n".'</rss>';
			
		} // }}}
		function generate_item( $item )
		{
			$this->_out .= ('<item>'."\n");
			foreach( $this->_item_field_map AS $attr => $field )
			{	
				// grab a handler if one is set
				$this->_out .= $this->get_element_output($item, $attr, $field);
			}
			$this->_out .= ('</item>'."\n\n");
		}
		function get_element_output($item, $attr, $field)
		{
			$return = '';
			
			if( !empty( $this->_item_field_handlers[ $attr ] ) )
				$handler = $this->_item_field_handlers[ $attr ];
			else
				$handler = '';

			$value = (!empty($field)) ? $item->get_value( $field ) : ''; // only get value if a field is named - NW
			if(!empty($value))
			{

				// run the handler if it is set
				if( !empty( $handler ) )
				{
					if(!empty($this->_item_field_handler_rules[ $attr ]['class_variable']))
						$value = $this->$handler( $value );
					else
						$value = $handler( $value );
				}

				// load field validator if one was set
				if( !empty( $this->_item_field_validator[ $attr ] ) )
					$validate_func = $this->_item_field_validator[ $attr ];
				else
					$validate_func = '';

				// run field validator if set, otherwise, set valid to true
				if( !empty( $validate_func ) )
					$valid = $validate_func( $value );
				else
					$valid = true;
				
				// show the field if a value is set
				if( !empty( $value ) )
				{
					// make sure value is also valid
					if( $valid )
					{
						if(array_key_exists($attr,$this->_custom_output_handlers))
						{
							$output_handler = $this->_custom_output_handlers[$attr];
							$return .= $this->$output_handler($item, $attr, $value);
						}
						else
						{
							$return .= $this->_default_output_handler($item, $attr, $value);
						}
					}
				}
				return $return;
			}
		}
		
		// can be overriden and customized in child classes
		function _default_output_handler($item, $attr, $value)
		{
			$return = '<'.$attr.'>';
			// call a field handler if one is set up
			$return .= $this->_clean_value( $value );
			$return .= '</'.$attr.'>'."\n";
			return $return;
		}
		
		function make_enclosure($item, $attr, $value)
		{
			$additional_attrs = $this->get_additional_enclosure_arributes( $item );
			return '<'.$attr.' url="'.$value.'" '.implode(' ',$additional_attrs).' />'."\n";
		}
		// This is meant to be overloaded so that the rss feed can get the appropriate attributes
		function get_additional_enclosure_arributes( $item )
		{
			return array();
		}
		function send_internal_error()
		{
			http_response_code(500);
			echo $this->get_error_rss('Internal Server Error');
		}
		function send_not_found()
		{
			http_response_code(404);
			echo $this->get_error_rss('Not Found');
		}
		function send_gone()
		{
			http_response_code(410);
			echo $this->get_error_rss('Gone');
		}
		function get_error_rss($text)
		{
			$ret = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
			$ret .= '<rss version="2.0">'."\n";
			$ret .= '<channel>'."\n";
			$ret .= '<title>'.$this->_clean_value($text).'</title>'."\n";
			$ret .= '</channel>'."\n";
			$ret .= '</rss>'."\n";
			return $ret;
		}

	}

?>
