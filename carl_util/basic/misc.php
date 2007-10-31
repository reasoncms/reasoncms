<?php

if( !defined( '__INC_DAVE_MISC' ) )
{
	define( '__INC_DAVE_MISC', true );
	include_once('date_funcs.php');
	include_once('url_funcs.php');
	include_once('cleanup_funcs.php');
	
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

	function add_backticks_array(&$val)
	{
		$value = ($z = strstr($val, " ")) ? substr($val, 0, strpos($val, " ")) : $val;
		$value = explode('.', $value);
		foreach ($value as $v)
		{
			$v2[] = ($v != '*') ? '`'.$v.'`' : $v;
		}
		$val = implode (".", $v2) . $z;
	}
	
	function add_backticks($val)
	{
		if (!empty($val)) return ($val != "*") ? '`'.$val.'`' : $val;
		else return '';
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
			// ...crashes php 5 - nwhite
			//if (method_exists($object, '__clone')) {
			//	//clone $object;
			//	//$object->__clone();
			//}
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
	
	/**
	 * Returns true if the version of php is php5
	 * @author Nathan White
	 */
	function carl_is_php5()
	{
		static $is_php5;
		if (!isset($is_php5))
		{
			if(version_compare(phpversion(), '5.0') < 0) $is_php5 = false;
			else $is_php5 = true;
		}
		return $is_php5;
	}
	
	/**
	 * Convert a MySQL TIMESTAMP field to a UNIX timestamp.
	 * @param string
	 * @return int or false if conversion fails
	 * @see carl_mktime
	 * @author Eric Naeseth
	 */
	function mysql_timestamp_to_unix($timestamp)
	{
		// MySQL 4.1 backwards compatible fix - MySQL 4.1 returns timestamps in datetime format
		// only execute if $dt is a numeric timestamp, otherwise try prettify_mysql_datetime
		if (is_numeric($timestamp)) {
			$year = substr( $dt, 0, 4 );
			$month = substr( $dt, 4, 2 );
			$day = substr( $dt, 6, 2 );
			$hour = substr( $dt, 8, 2);
			$min = substr( $dt, 10, 2);
			$sec = substr( $dt, 12, 2);
			
			return ((int) $year && (int) $month && (int) $day)
				? carl_mktime($hour, $min, $sec, $month, $day, $year)
				: false;
		} else {
			return mysql_datetime_to_unix($timestamp);
		}
	}
	
	/**
	 * Convert a MySQL DATETIME field to a UNIX timestamp.
	 * @param string
	 * @return int or false if conversion fails
	 * @see carl_mktime
	 * @author Eric Naeseth
	 */
	function mysql_datetime_to_unix($dt)
	{
		$year = $month = $day = $hour = $minute = $second = '';
		// y/m/d: if zero, subsequent values must be empty
		if($year = substr( $dt, 0, 4 ))
		{
			if($month = substr( $dt, 5, 2 ))
			{
				if($day = substr( $dt, 8, 2 ))
				{
					// h/m/s: can be zero with subsequent items being nonzero integers
					$hour = substr( $dt, 11, 2);
					$minute = substr( $dt, 14, 2);
					$second = substr( $dt, 17, 2);
				}
			}
		}

		// check for all 0s (an empty date)
		return ((int) $year && (int) $month && (int) $day)
			? carl_mktime($hour, $minute, $second, $month, $day, $year)
			: false;
	}
			
	/**
	 * Make a good looking date from a mysql timestamp
	 * @param string $dt format of mysql timestamp
	 * @param string $format the format string according to {@link http://us2.php.net/manual/en/function.date.php php's date function}
	 * @return string The formatted date or the empty string on conversion failure
	 */ 
	function prettify_mysql_timestamp( $dt , $format = 'M jS, Y') // {{{
	{
		return (false !== $time = mysql_timestamp_to_unix($dt))
			? carl_date($format, $time)
			: '';
	} // }}}
	/**
	 * Make a good looking date from a mysql datetime
	 * @param string $dt format of mysql datetime
	 * @param string $format the format string according to {@link http://us2.php.net/manual/en/function.date.php php's date function}
	 * @return string The formatted date or the empty string on conversion failure
	 */ 
	function prettify_mysql_datetime( $dt , $format = 'M jS, Y' ) // {{{
	{
		return (false !== $time = mysql_datetime_to_unix($dt))
			? carl_date($format, $time)
			: '';
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
	
	/**
	 * Performs tag replacement in an XHTML string
	 * @param string $xhtml the xhtml string needing tag replacement
	 * @param array $tag_array an array mapping any number of html tags (keys) to replacement tags (values)
	 * @return string $output the xhtml string with replacement tags
	 */
	function tagTransform($xhtml, $tag_array)
	{
		$output = preg_replace("/(<\/?)(\w+)([^>]*>)/e", "'\\1'._tagMap('\\2', \$tag_array ).stripslashes('\\3')", $xhtml);
		return $output;
	}
	
	/**
	 * Performs a search / replace of a tag in an XHTML string
	 * @param string $xhtml the xhtml string needing tag replacement
	 * @param string $tag_original xhtml tag to be replaced
	 * @param string $tag_replace the xhtml tag which replaces $tag_original
	 * @return string $output the xhtml string with $tag_replace substituted for $tag_original
	 */
	function tagSearchReplace($xhtml, $tag_original, $tag_replace)
	{
		$tag_array = array($tag_original => $tag_replace);
		$output = tagTransform($xhtml, $tag_array);
		return $output;
	}

	/**
	 * Helper function for tagTransform
	 * @param string $value a single xhtml tag extracted from an xhtml string
	 * @param array $transform_array an array mapping tags which need to be replaced
	 * @return string $value returns the replaced value
	 */
	 
	function _tagMap($value, $transform_array)
	{
		if (isset($transform_array[$value]))
		{
			return $transform_array[$value];
		}
		else return $value;
	}

	function securest_available_protocol()
	{
		if( HTTPS_AVAILABLE ) return 'https';
		return 'http';
	}
	
	/**
	 * Windows behaves differently from other systems in a variety of ways
	 * This function wraps up the logic to determine if we are running under Windows
	 *
	 * NOTE: The applications in the Reason/carl_util package do not yet run under Windows --
	 * this function may allow us to make that happen, but do not assume that everyhing is
	 * using it yet.
	 *
	 * @return boolean
	 */
	function server_is_windows()
	{
		static $is_windows;
		static $tested = false;
		if(!$tested)
		{
			if(strtoupper(substr(PHP_OS,0,3) == 'WIN') )
				$is_windows = true;
			$tested = true;
		}
		return $is_windows;
	}
	
	/**
	 * Determine if an HTML snippet is essentially empty -- e.g. is there no actual content in the HTML?
	 *
	 * e.g. '<p><em></em><br /></p>' should return true -- there is no content here
	 * '<img src="foo.gif" alt="foo" />' and '<p>foo</p>' should return false -- these contain real content
	 *
	 * @param string $string
	 * @return boolean
	 */
	function carl_empty_html($string)
	{
		if( empty( $string ) )
				return true;
		elseif(strlen($string) < 256)
		{
			$trimmed = trim(strip_tags($string,'<img><hr><script><embed><object><form><iframe><input><select><textarea>'));
			if(empty($trimmed))
				return true;
		}
		return false;
	}
}
?>
