<?php

if( !defined( '__INC_DAVE_MISC' ) )
{
	define( '__INC_DAVE_MISC', true );
	include_once('date_funcs.php');
	
	/**
	 * Use both keys and values to produce imploded string
	 *
	 * Useful for building query strings
	 *
	 * Typical usage:
	 * $foo = array('key1'=>'val1','key2'=>'val2');
	 * echo implode_with_keys('&amp;',$foo);
	 * 
	 * Output:
	 * key1=val1&amp;key2=val2
	 *
	 * @param string $glue
	 * @param array $array
	 * @return string $imploded_value
	 */
	function implode_with_keys($glue, $array)
	{
        	$output = array();
        	foreach( array_keys($array) as $key )
        	{
                	if ($array[$key])
                        	$output[] = $key . "=" . $array[$key];
        	}
        	return implode($glue, $output);
	}

	function localize( $var ) // {{{
	{
		return isset( $_REQUEST[$var] ) ? $_REQUEST[ $var ] : '';
	} // }}}
	
	/**
	 * Takes a typical field name or array key (e.g. all lower case, underscores) and makes it look better by replacing underscores with spaces and capitalizing the first letters of words.
	 *
	 * @param string $s
	 * @return string $pretty_string
	 */
	function prettify_string( $s ) // {{{
	{
		$parts = explode( '_', $s );
		foreach( $parts AS $part )
			$new_parts[] = strtoupper( substr( $part, 0, 1 ) ).substr( $part, 1 );
		return implode( ' ', $new_parts );
	} // }}}
	/**
	 * Recursively prettify all values of an array using prettify_string
	 * @author Matt Ryan
	 * @date 2006-05-18
	 * @param array $array
	 * @return array $prettified_array
	 */
	function prettify_array( $array )
	{
		foreach($array as $k=>$v)
		{
			if(is_array($v))
			{
				$array[$k] = prettify_array($v);
			}
			else
			{
				$array[$k] = prettify_string($v);
			}
		}
		return $array;
	}
	function is_mixed_case( $str )
	{
		// quick check to see if the string is equal to it's lower case self or upper cased self
		if( ( $str == strtoupper( $str ) ) || ( $str == strtolower( $str ) ) )
			return false;
		else
			return true;
	}
	function unhtmlentities( $entities ) // {{{
	{
		static $trans;
		if(!$trans)
		{
			$trans = array_flip( get_html_translation_table( HTML_ENTITIES ) );
		}
		return strtr( $entities, $trans );
	} // }}}
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
	
	function get_current_url( $scheme = '' )
	{
		// without $scheme, we figure out whether we're in SSL or not.  Providing a scheme will return the current URI
		// with the new scheme
		$url = '';
		if( empty($scheme) )
		{
			if( !empty( $_SERVER['HTTPS'] ) AND strtolower($_SERVER['HTTPS']) == 'on' )
			{
				$scheme = 'https';
			}
			else
			{
				$scheme = 'http';
			}
		}
		$host = $_SERVER['SERVER_NAME'];
		$path = $_SERVER['REQUEST_URI'];
		$url = $scheme.'://'.$host.$path;
		return $url;
	}
	function on_secure_page()
	{
		return (!empty( $_SERVER['HTTPS'] ) AND strtolower( $_SERVER['HTTPS'] ) == 'on' );
	}
	
if (!function_exists('html_entity_decode')) {

	function html_entity_decode ($string, $opt = ENT_COMPAT)
	{
		$trans_tbl = get_html_translation_table (HTML_ENTITIES);
		$trans_tbl = array_flip ($trans_tbl);
		
		// Translating single quotes
		if ($opt & 1)
		{
			// Add single quote to translation table;
			// doesn't appear to be there by default
			$trans_tbl["&apos;"] = "'";
		}

		// Not translating double quotes
		if (!($opt & 2))
		{
			// Remove double quote from translation table
			unset( $trans_tbl["&quot;"] );
		}

		return strtr ($string, $trans_tbl);
	}
}

// Just to be safe ;o)
if (!defined("ENT_COMPAT")) define("ENT_COMPAT", 2);
if (!defined("ENT_NOQUOTES")) define("ENT_NOQUOTES", 0);
if (!defined("ENT_QUOTES")) define("ENT_QUOTES", 3);

	function dlog( $msg, $dest = '' )
	{
		if( empty( $dest ) )
			$dest = '/tmp/dlog-'.$_SERVER['HTTP_HOST'];
		error_log( $msg."\n", 3, $dest );
	}

	function quote_walk( &$val, $key )
	{
		$val = '"'.addslashes($val).'"';
	}

	function db_prep_walk( &$val, $key )
	{
		$val = '"'.addslashes($val).'"';
	}
	
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
					$z = ((is_array($v)) ? array_map("stripslashes",$v) : stripslashes($v));
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
	 * Provides a nice, human-readable version of a size given in bytes
	 * @param integer $size
	 * @param integer $decimal how many decimal places to provide
	 * @return string English-formatted filesize
	 */
	function format_bytes_as_human_readable( $size, $decimal = 2 )
	{
		if($size == 0) {
		   return("0 Bytes");
	   }
	   $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
	   return round($size/pow(1024, ($i = floor(log($size, 1024)))), $decimal) . $filesizename[$i];
	
	}
	
	/**
	 * Provides a php size setting (like for max upload) in bytes
	 *
	 * php size settings are in g (gigabytes), m (megabytes), or k (kilobytes).
	 * This function makes it easier to compare filesizes to php settings by transforming
	 * a requested setting to simple byte counts
	 *
	 * @param string $setting_name
	 * @return integer $size in bytes
	 */
	function get_php_size_setting_as_bytes($setting_name)
	{
		$size = trim(ini_get($setting_name));
		$letter = strtolower($size{strlen($size)-1});
		switch($letter)
		{
			case 'g':
				$size *= 1024;
			case 'm':
				$size *= 1024;
			case 'k':
				$size *= 1024;
		}
		return $size;
	}
	
	function array_diff_assoc_recursive($array1, $array2)
	{
		foreach($array1 as $key => $value)
		{
			if(is_array($value))
			{
				if(!isset($array2[$key]) || !is_array($array2[$key])) // added !isset to reduce warnings
				{
					//echo 'hitit';
					//echo $array2[$key];
					$difference[$key] = $value;
				}
				else
				{
					$new_diff = array_diff_assoc_recursive($value, $array2[$key]);
					if($new_diff != FALSE)
					{
						$difference[$key] = $new_diff;
					}
				}
			}
			elseif(!isset($array2[$key]) || $array2[$key] != $value)
			{
				$difference[$key] = $value;
			}
		}
		return !isset($difference) ? 0 : $difference;
	}
	
	function array_merge_recursive2($paArray1, $paArray2)
	{
		if (!is_array($paArray1) or !is_array($paArray2)) { return $paArray2; }
		foreach ($paArray2 AS $sKey2 => $sValue2)
   		{
       		$paArray1[$sKey2] = array_merge_recursive2(@$paArray1[$sKey2], $sValue2);
   		}
   		return $paArray1;
	}
	/**
	 * Clone an object.
	 *
	 * This function allows for compatibility with both php4 and php5.
	 * It should be used rather than using the = operator when object duplication is desired
	 *
	 * Note that there is some shared code between carl_clone and carl_clone_if_needed.
	 * This duplication exists because profiling indicated that abstracting out
	 * the determination of current version into a separate function caused a 2x - 4x slowdown
	 * in execution time -- not insignificant in a script where many clones occur.
	 *
	 * @author Matt Ryan
	 * @link http://php.net/language.oop5.cloning
	 * @param object $object
	 * @return object $cloned_object
	 */
	function carl_clone($object)
	{
		static $version_checked = false;
		static $version_supports_cloning;
		
		if(!$version_checked)
		{
			if(version_compare(phpversion(), '5.0') < 0) // php version 4
			{
				$version_supports_cloning = false;
			}
			else // php version 5
			{
				$version_supports_cloning = true;
			}
			$version_checked = true;
		}
		if($version_supports_cloning)
		{
			return clone($object);
		}
		else
		{
			/* The PEAR compat library does this.
			   I'm not sure what benefits this method offers,
			   since the object should already be a copy at this point in php4. */
			//$object = unserialize(serialize($object));
			
			// If there is a __clone method call it on the copied object
			if (method_exists($object, '__clone')) {
				$object->__clone();
			}
			return $object;
		}
	}
	/**
	 * Clone an object if the current version does not automatically duplicate objects when they passed to functions.
	 *
	 * This function allows for compatibility with both php4 and php5.
	 * It should be used either inside or outside a function when passing by reference is NOT desired.
	 * Note that the results of this function should be assigned by reference for least work by php
	 *
	 * Note that there is some shared code between carl_clone and carl_clone_if_needed.
	 * This duplication exists because profiling indicated that abstracting out
	 * the determination of current version into a separate function caused a 2x - 4x slowdown
	 * in execution time -- not insignificant in a script where many clones occur.
	 *
	 * @author Matt Ryan
	 * @link http://php.net/language.oop5.cloning
	 * @param object $object
	 * @return object $possibly_cloned_object
	 */
	function carl_clone_if_needed(&$object)
	{
		static $version_checked = false;
		static $version_supports_cloning;
		
		if(!$version_checked)
		{
			if(version_compare(phpversion(), '5.0') < 0) // php version 4
			{
				$version_supports_cloning = false;
			}
			else // php version 5
			{
				$version_supports_cloning = true;
			}
			$version_checked = true;
		}
		if($version_supports_cloning)
		{
			return clone($object);
		}
		else
		{
			return $object;
		}
	}
}
?>
