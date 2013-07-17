<?php
/**
 * @package carl_util
 * @subpackage basic
 */
 
 /**
  * Include the misc functions
  */
  include_once(dirname(__FILE__).'/misc.php');

 /**
  * Translate HTML entities back into their raw UTF-8 character values
  *
  * Note that in php4 this function only converts named entities in the html 
  * translation table. This does not include some entities, like &euro;, 
  * &trade;, &mdash;, etc.
  *
  * @param string $str html encoded string (named edities and/or utf-8 decimal or hex encoding)
  * @return string (plain utf-8, no html encoding)
  */
	function unhtmlentities( $str ) // {{{
	{
		if(carl_is_php5())
		{
			return html_entity_decode($str, ENT_QUOTES, 'UTF-8');
		}
		else // php4's html_entity_decode doesn't do utf-8 properly.
		{
			static $utf8_trans;
			if(!$utf8_trans)
			{
				$utf8_trans = array();
				foreach(get_html_translation_table( HTML_ENTITIES ) as $k=>$v)
				{
					$utf8_trans[$v] = utf8_encode($k);
				}
			}
			$str = preg_replace('~&#x([0-9a-f]+);~ei', 'carl_unichr(hexdec("\\1"))', $str);
			$str = preg_replace('~&#([0-9]+);~e', 'carl_unichr("\\1")', $str);
			return strtr( $str, $utf8_trans );
		}
	} // }}}
	
	/**
	 * Remove initial slash (if present) from the beginning of a string
	 *
	 * @param string $str
	 * @return string $str_with_first_slash_trimmed
	 */
	function strip_first_slash( $str ) // {{{
	{
		if( substr( $str, 0, 1 ) == '/' )
			return substr( $str, 1 );
		else
			return $str;
	} // }}}
	
	/**
	 * Remove slashes from the ends of a string
	 *
	 * @param string $str
	 * @return string $str_with_slashes_trimmed
	 */
	function trim_slashes( $str ) // {{{
	{
		if( substr( $str, 0, 1 ) == '/' )
			$str = substr( $str, 1 );
		if( substr( $str, -1 ) == '/' )
			$str = substr( $str, 0, -1 );
		return $str;
	} // }}}
	
	/**
	 * Strip slashes *if magic quotes are turned on*
	 *
	 * Most of the Reason and Carl Util tools expect to work with unescaped values
	 * and will double-escape values if they are written to the db that way.
	 * It is good practice to run userland values through this function so that code 
	 * will work whether or not magic quotes are on.
	 *
	 *
	 * @param string $string_that_may_be_escaped
	 * @return string $unescaped_string
	 */
	function conditional_stripslashes($value)
	{
		if (!get_magic_quotes_gpc())
		{
			return $value;
		}
		else 
		{
			if (is_array($value))
			{
				foreach($value as $k => $v)
				{
					$z = ((is_array($v)) ? array_map("conditional_stripslashes",$v) : conditional_stripslashes($v));
					$value[$k] = $z;
				}
				return $value;
			}
			else
			{
				return stripslashes($value);
			}
		}
	}

	/**
	 * Escape/encode reserved LDAP filter characters for inclusion in an LDAP filter
	 *
	 * replaces '\\','@','*','(', and ')' with, respectively, '\5c','\40',	'\2a', '\28', and '\29'.
	 *
	 * @param string $string_to_be_escaped
	 * @return string $escaped_string
	 */
	function ldap_escape($value)
	{
		return str_replace(array('\\','@','*','(',')'), array('\5c','\40',	'\2a', '\28','\29'), $value);
	}
	
	/**
	 * Replace escaped/encoded LDAP filter characters with the real ASCII characters they represent
	 *
	 * replaces '\5c', '\40', '\2a', '\28', and '\29' with, respectively, '\\', '@', '*', '(', and ')'.
	 *
	 * @param string $escaped_string
	 * @return string $unescaped_string
	 */
	function ldap_unescape($value)
	{
		return str_replace(array('\5c','\40',	'\2a', '\28','\29'),array('\\','@','*','(',')'), $value);
	}
	
	/**
	 * Moved from reason function_libraries/util.php
	 */
	function carl_clean_vars( $vars, $rules ) // {{{
	// Returns an array which takes the values of the keys in Vars of
	// the keys set in Settings, and runs the cleaning function
	// specified in the value of settings
	{
		$return = array();
		foreach ( $rules as $key => $func_and_args )
		{
			// very important that this is isset() and not empty().  if we use !empty(), the request that is sent to the
			// module is not quite right.  _REQUEST can have keys that have a set (perhaps empty) value.  This is
			// crucial for systems like Disco and Plasmature that need to know if a variable was set to nothing in the
			// form.  Otherwise, default values can come cropping back up unexpectedly. --dh
			if ( isset($vars[$key]) )
			{
				if(!is_array($func_and_args))
				{
					$func_and_args = array('function'=>$func_and_args);
				}
				$func = $func_and_args['function'];
				if(!function_exists($func))
				{
					trigger_error('Function '.$func. ' does not exist');
					$return[$key] = '';
				}
				else
				{
					if ( !empty($func_and_args['extra_args']) )
					{
						$extra_args = $func_and_args['extra_args'];
						$return[$key] = $func( $vars[$key], $extra_args );
					}
					else
						$return[$key] = $func( $vars[$key] );
				}
			}
		}
		return $return;
	} // }}}

	// 
	// The following functions should all conform to the following interface:
	//   [turn_into_|check_against_]xxxxxxx($val, $extra_[arg|args])
	//
	// If there's only one extra_arg, then it can expect whatever type is appropriate.
	// If there's more than one extra_arg, then $extra_args should be an associative array.
	//
	function check_against_array($val, $array) //{{{
	{
		if ( !empty($val) && !in_array($val, $array) )
			return NULL;
		else
			return $val;
	} // }}}

	function check_against_regexp($val, $array)
	{
		$common_regexp['alphanumeric'] = '/^[a-z0-9]*$/i';
		$common_regexp['naturalnumber'] = '/^[0-9]*$/i';
		$common_regexp['safechars'] = '/^[a-z0-9_-]*$/i';
		// This regex is not permissive enough -- it doesn't allow all kinds of legal chars in the username
		//$common_regexp['email'] = '/^[0-9A-Za-z_\-]+[@][0-9A-Za-z_\-]+([.][0-9A-Za-z]+)([.][A-Za-z]{2,3}){0,1}$/x';
		// This one has been used for several years. It's not perfect (probably somewhat overpermissive) but it should be better.
		$common_regexp['email'] = '/^([-.]|\w)+@([-.]|\w)+\.([-.]|\w)+$/i';
		foreach ($array as $this_regexp)
		{
			$this_regexp = isset($common_regexp[$this_regexp]) ? $common_regexp[$this_regexp] : $this_regexp;
			if (!preg_match( $this_regexp, $val)) return NULL;
		}
		return $val;
	}

	function turn_into_array($val, $dummy = NULL) //{{{
	{
		if ( is_array($val) )
			return $val;
		else
			return array();
	} // }}}
	function turn_into_int($val, $extra_args = NULL) //{{{
	{
		settype($val, 'integer');
		if (is_array($extra_args))
		{
			if (isset($extra_args['zero_to_null']) && ($extra_args['zero_to_null'] == 'true') && ($val == 0)) return NULL;
			if (isset($extra_args['default']) && ($val == 0)) return $extra_args['default'];
		}
		return $val;
	} // }}}
	
	function turn_into_string($val, $dummy = NULL) //{{{
	{
		settype($val, 'string');
		return $val;
	} // }}}
	function turn_into_date($val, $format = 'Y-m-d') //{{{
	{
		return prettify_mysql_datetime($val, $format);
	} // }}}
	function turn_into_yyyy_mm($val, $format = 'Y-m') //{{{
	{
		return prettify_mysql_datetime($val.'-01', $format);
	} // }}}
	/**
	 * Turn a value into a boolean
	 * @param mixed $val The value to coerce
	 * @param NULL $dummy a dummy second value so this can be used with
	 *                    cleanup tools
	 * @return boolean
	 */
	function turn_into_boolean($val, $dummy = NULL) //{{{
	{
		settype($val, 'boolean');
		return $val;
	} // }}}

	// val = value to sanitize
	// array (array('pattern' => $regexp, 'replace' => '_'))
	function sanitize_by_replace_regexp($val, $array)
	{
		$common_regexp['filename'] = '/[^a-z0-9._]/i';
		if (!is_array(current($array))) $rules_array[] = $array;
		else ($rules_array = $array);
		foreach ($rules_array as $this_regexp)
		{
			$this_regexp['pattern'] = isset($common_regexp[$this_regexp['pattern']]) 
											   ? $common_regexp[$this_regexp['pattern']]
											   : $this_regexp['pattern'];
			$val = preg_replace( $this_regexp['pattern'], $this_regexp['replace'], $val);
		}
		return $val;
	}
	/**
	 * Cleans up a filename so that it can be hosted on the web without worry about encoding wierdnesses
	 * Really just an easy-to-use wrapper for the more powerful sanitize_by_replace_regexp().
	 * @param string $filename the filename string needing sanitization
	 * @return string $clean_filename the filename with iffy chars replaced with underscores
	 */
	function sanitize_filename_for_web_hosting($filename)
	{
		$rules = array('pattern' => 'filename', 'replace' => '_');
		return sanitize_by_replace_regexp($filename, $rules);
	}
	
	/**
	 * Sanitizes HTML using HTMLPurifier - accepts custom config object.
	 * 
	 * @param string html string needing sanitization
	 * @param HTMLPurifier_Config custom configuation - if provided we use HTML Purifier regardless of HTML_SANITIZATION_FUNCTION value.
	 * @return string sanitized html string
	 *
	 * @todo remove support for HTML_SANITIZATION_FUNCTION when Reason 4.5 is released.
	 */
	function carl_get_safer_html($raw_html, $config = NULL)
	{
		if (defined('HTML_SANITIZATION_FUNCTION') && is_null($config))
		{
			$func_name = HTML_SANITIZATION_FUNCTION;
			return $func_name($raw_html);
		}
		else
		{
			return get_safer_html_html_purifier($raw_html, $config);
		}
	}

	/**
	 * Return a safer string using HTML Purifier to do sanitization. You may pass in your own configuration object, or use defaults.
	 *
	 * The default settings use HTMLPurifier's defaults, except for these two things:
	 *
	 * - We allow the id attribute - recognizing this could break validation and confuse JavaScript
	 * - We add a rule to transform b to strong.
	 * - We add a rule to transform i to em.
	 *
	 * @param string raw unsanitizted html
	 * @param object HTMLPurifier config object
	 */
	function get_safer_html_html_purifier($string, $config = NULL)
	{
		if (is_null($config))
		{
			require_once( HTML_PURIFIER_INC . 'htmlpurifier.php' );
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.DefinitionID', 'allow_anchors_transform_em_and_strong');
			$config->set('HTML.DefinitionRev', 1);
			$config->set('Attr.EnableID', true);
			if (defined("HTMLPURIFIER_CACHE")) $config->set('Cache.SerializerPath', HTMLPURIFIER_CACHE);
			if ($def = $config->maybeGetRawHTMLDefinition())
			{
				// lets transform b to strong and i to em
				$def->info_tag_transform['b'] = new HTMLPurifier_TagTransform_Simple('strong');
				$def->info_tag_transform['i'] = new HTMLPurifier_TagTransform_Simple('em');
			}
		}
		$purifier = new HTMLPurifier($config);
		return $purifier->purify( $string );
	}

	/**
	 * @deprecated use carl_get_safer_html
	 */
	function get_safer_html($string)
	{
		return carl_get_safer_html($string);
	}

	/**
	 * @deprecated HTML Safe is not good enough (blacklist instead of whitelist based).
	 */
	function get_safer_html_html_safe($string)
	{
		require_once('HTML/Safe.php');
		$parser = new HTML_Safe();
		$parser->attributes = array('dynsrc');
		return $parser->parse($string);
	}
	
	/**
	 * Converts certain characters in a string to underscores, returning the same string that
	 * php would use as a key in the request
	 * see: http://us.php.net/manual/en/language.variables.external.php#81080
	 *
	 * Note that single, unmatched square brackets will be passed through, even though php will
	 * convert them to underscores (since a key can be a square-bracket-based array, and we
	 * want to leave that construct as-is... and this function is not yet smart enough to identify
	 * matched vs. unmatched square brackets).
	 *
	 * @param string $string
	 * @return string key converted as PHP does on request/post
	 * @todo find non-matched square brackets and also replace them with underscores
	 */
	function request_key_convert($string)
	{
		static $find;
		if (empty($find))
		{
			$find = array(' ','.');
			for($i = 128; $i<=159; $i++) $find[] = chr($i);
		}
		return str_replace($find, '_', $string);
	}

?>
