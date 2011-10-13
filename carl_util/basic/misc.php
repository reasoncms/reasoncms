<?php
/**
 * A collection of generally useful functions
 *
 * Includes string, array, object, and date/time manipulation functions.
 *
 * @package carl_util
 * @subpackage basic
 */

if( !defined( '__INC_DAVE_MISC' ) )
{
	define( '__INC_DAVE_MISC', true );
	include_once('date_funcs.php');
	include_once('url_funcs.php');
	include_once('cleanup_funcs.php');
	include_once('html_funcs.php');
	
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
	
	/**
	 * Get a request variable if it exists.
	 *
	 * @param $key
	 * @return request value for that key, if provided in request. Otherwise will return an empty string.
	 */
	function localize( $var ) // {{{
	{
		return isset( $_REQUEST[$var] ) ? $_REQUEST[ $var ] : '';
	} // }}}
	
// Just to be safe ;o)
if (!defined("ENT_COMPAT")) define("ENT_COMPAT", 2);
if (!defined("ENT_NOQUOTES")) define("ENT_NOQUOTES", 0);
if (!defined("ENT_QUOTES")) define("ENT_QUOTES", 3);

if (!function_exists('html_entity_decode')) {

	/**
	 * html_entity_decode replacement
	 *
	 * This function has a number of significant problems:
	 *
	 * 1. It does not translate into UTF-8, but into ISO-8859-1
	 *
	 * 2. It should only be defined prior to php 4.3. Since it is likely that 
	 * no Reason installs are running a version this old, and since Reason is 
	 * soon to be php5+, this function definition is therefore deprecated.
	 *
	 * @deprecated
	 * @todo remove this function once php4 support goes away
	 */
	function html_entity_decode ($string, $opt = ENT_COMPAT)
	{
		trigger_error('Running reason on php older than 4.3 is deprecated. Newer versions of Reason will not work under <4.3; please upgrade your php version.');
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

if(!function_exists('htmlspecialchars_decode'))
{
	function htmlspecialchars_decode($string,$style=ENT_COMPAT)
    {
        $translation = array_flip(get_html_translation_table(HTML_SPECIALCHARS,$style));
        if($style === ENT_QUOTES){ $translation['&#039;'] = '\''; }
        return strtr($string,$translation);
    }
}

	/**
	 *	Log a line to a file
	 *
	 *	If not passed a destination, this function will try to log to /tmp/dlog-hostname
	 *
	 *	@param string $msg The line to log
	 *	@param string $dest destination file to log to
	 *	@return boolean success
	 */
	function dlog( $msg, $dest = '' )
	{
		if( empty( $dest ) )
			$dest = '/tmp/dlog-'.$_SERVER['HTTP_HOST'];
		return error_log( $msg."\n", 3, $dest );
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
	   $filesizename = array(" bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
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

	function securest_available_protocol()
	{
		return (HTTPS_AVAILABLE) ? "https" : "http";
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
		static $is_windows = false;
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
	 * strtolower replacement that uses mb_strtolower where possible
	 *
	 * @param string str
	 * @param string encoding the encoding of the string - defaults to UTF-8, pass a null value to use the current mb_internal_encoding
	 */
	function carl_strtolower($str, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_strtolower') ? mb_strtolower($str, $encoding) : strtolower($str);
	}

	/**
	 * strtoupper replacement that uses mb_strtoupper where possible
	 *
	 * @param string str
	 * @param string encoding the encoding of the string - defaults to UTF-8, pass a null value to use the current mb_internal_encoding
	 */	
	function carl_strtoupper($str, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_strtoupper') ? mb_strtoupper($str, $encoding) : strtoupper($str);
	}

	/**
	 * substr replacement that uses mb_substr where possible
	 *
	 * @param string str
	 * @param string encoding the encoding of the string; pass a null value to use the current mb_internal_encoding
	 */		
	function carl_substr($str, $start, $length = NULL, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_substr') ? mb_substr($str, $start, ($length) ? $length : mb_strlen($str), $encoding) : substr($str, $start, $length);
	}

	/**
	 * strlen replacement that uses mb_strlen where possible
	 *
	 * @param string str
	 * @param string encoding the encoding of the string; pass a null value to use the current mb_internal_encoding
	 */	
	function carl_strlen($str, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_strlen') ? mb_strlen($str, $encoding) : strlen($str);
	}
	
	/**
	 * strpos replacement that uses mb_strpos where possible
	 *
	 * @param string haystack to search
	 * @param string needle to find
	 * @param string offset the search offset
	 * @param encoding the encoding of the strings; pass a null value to use the current mb_internal_encoding
	 */	
	function carl_strpos($haystack, $needle, $offset = NULL, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_strpos') ? mb_strpos($haystack, $needle, $offset, $encoding) : strpos($haystack, $needle, $offset);
	}
	
	/**
	 * Return unicode char by its code
	 *
	 * @param int $u
	 * @return char
	 */
	function carl_unichr($u)
	{
		if(function_exists('mb_convert_encoding'))
		{
			return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
		}
		else
		{
			if ($u <= 0x7F)
				return chr($u);
			else if ($u <= 0x7FF)
				return chr(0xC0 | $u >> 6) . chr(0x80 | $u & 0x3F);
			else if ($u <= 0xFFFF)
				return chr(0xE0 | $u >> 12) . chr(0x80 | $u >> 6 & 0x3F) . chr(0x80 | $u & 0x3F);
			else if ($u <= 0x10FFFF)
				return chr(0xF0 | $u >> 18) . chr(0x80 | $u >> 12 & 0x3F) . chr(0x80 | $u >> 6 & 0x3F) . chr(0x80 | $u & 0x3F);
			else
				return false;
		}
	}
	
	/**
	 * This is a replacement for referencing $_REQUEST directly, and gives us a consistent result that includes just $_GET and $_POST.
	 *
	 * It should be used instead of $_REQUEST, as the makeup of $_REQUEST depends upon the PHP version and settings.
	 *
	 * If $_POST and $_GET are set for a named item, $_POST is preferred.
	 *
	 * @param boolean run_conditional_stripslashes - defaults to true ... makes sure stripslashes gets run if magic_quotes is on.
	 * @author Nathan White
	 * @return array
	 */
	function carl_get_request( $run_conditional_stripslashes = true )
	{
		$merged = array_merge_recursive2( (array) $_GET, (array) $_POST);
		return ($run_conditional_stripslashes) ? conditional_stripslashes($merged) : $merged;
	}
	
	/**
	 * Returns the path components after some directory in the path.
	 *
	 * @author Nathan White
	 *
	 * @param string full_path - absolute path
	 * @param string suffix - extension to strip
	 * @param string dir - some directory
	 *
	 * @return string
	 */
	function carl_basename( $full_path, $suffix, $dir )
	{
		$strlength = carl_strlen($dir);
		$strpos = carl_strpos($full_path, $dir);
		if (is_numeric($strpos)) // found the string
		{
			$partial_path = carl_substr($full_path, $strpos + $strlength);
			$filebasename = basename($partial_path, $suffix);
			$dirname = dirname($partial_path);
			return $dirname . '/' . $filebasename;			
		}
		else
		{
			trigger_error('The directory ' . $dir . ' was not found in the full path string ' . $full_path . ' - returning just the file basename');
			return basename($full_path, $suffix);
		}
	}
}
?>
